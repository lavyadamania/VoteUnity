"""
ArcFace Face Verification — Vercel Python Serverless Function
Uses InsightFace ArcFace (MobileFaceNet backbone) via ONNX Runtime.

POST /api/verify_face
Body: {"image1": "data:image/jpeg;base64,...", "image2": "data:image/jpeg;base64,..."}
Returns: {"match": true/false, "score": 0.85, "threshold": 0.40, "method": "arcface"}
"""

from http.server import BaseHTTPRequestHandler
import json
import base64
import os
import io
import zipfile
import urllib.request

import numpy as np
from PIL import Image

# ── Configuration ──────────────────────────────────────────────
MODEL_DIR = '/tmp/arcface_models'
RECOGNITION_MODEL = os.path.join(MODEL_DIR, 'w600k_mbf.onnx')
MODEL_ZIP_URL = 'https://github.com/deepinsight/insightface/releases/download/v0.7/buffalo_sc.zip'
THRESHOLD = 0.40  # ArcFace cosine similarity threshold

# Lazy-loaded ONNX session (persists across warm invocations)
_session = None


# ── Model Management ──────────────────────────────────────────
def ensure_model():
    """Download ArcFace ONNX model from InsightFace releases if not cached."""
    if os.path.exists(RECOGNITION_MODEL):
        return True

    os.makedirs(MODEL_DIR, exist_ok=True)
    zip_path = os.path.join(MODEL_DIR, 'buffalo_sc.zip')

    try:
        print("[ArcFace] Downloading model from InsightFace releases...")
        urllib.request.urlretrieve(MODEL_ZIP_URL, zip_path)

        with zipfile.ZipFile(zip_path, 'r') as z:
            for name in z.namelist():
                if name.endswith('w600k_mbf.onnx'):
                    with open(RECOGNITION_MODEL, 'wb') as f:
                        f.write(z.read(name))
                    break

        if os.path.exists(zip_path):
            os.remove(zip_path)

        print(f"[ArcFace] Model ready: {RECOGNITION_MODEL}")
        return os.path.exists(RECOGNITION_MODEL)
    except Exception as e:
        print(f"[ArcFace] Model download failed: {e}")
        return False


def get_session():
    """Get or create ONNX Runtime inference session."""
    global _session
    if _session is None:
        import onnxruntime as ort
        if not ensure_model():
            raise RuntimeError("ArcFace model could not be downloaded")
        _session = ort.InferenceSession(
            RECOGNITION_MODEL,
            providers=['CPUExecutionProvider']
        )
    return _session


# ── Image Processing ──────────────────────────────────────────
def decode_image(image_data):
    """Decode base64 data URL to PIL Image."""
    if image_data.startswith('data:'):
        image_data = image_data.split(',', 1)[1]
    img_bytes = base64.b64decode(image_data)
    return Image.open(io.BytesIO(img_bytes)).convert('RGB')


def preprocess(image):
    """Preprocess for ArcFace: center-crop to square, resize 112x112, normalize."""
    w, h = image.size
    s = min(w, h)
    left, top = (w - s) // 2, (h - s) // 2
    image = image.crop((left, top, left + s, top + s))
    image = image.resize((112, 112), Image.LANCZOS)

    img = np.array(image, dtype=np.float32)
    img = (img - 127.5) / 127.5          # normalize to [-1, 1]
    img = np.transpose(img, (2, 0, 1))   # HWC → CHW
    return np.expand_dims(img, axis=0)    # add batch dim


def get_embedding(image_data):
    """Extract 512-d ArcFace embedding from a base64 image."""
    session = get_session()
    tensor = preprocess(decode_image(image_data))
    input_name = session.get_inputs()[0].name
    embedding = session.run(None, {input_name: tensor})[0][0]
    return embedding / np.linalg.norm(embedding)  # L2 normalize


def cosine_similarity(emb1, emb2):
    """Cosine similarity between two L2-normalized embeddings."""
    return float(np.dot(emb1, emb2))


# ── Vercel Serverless Handler ─────────────────────────────────
class handler(BaseHTTPRequestHandler):
    def do_POST(self):
        try:
            body = self.rfile.read(int(self.headers.get('Content-Length', 0)))
            data = json.loads(body)
            img1, img2 = data.get('image1', ''), data.get('image2', '')

            if not img1 or not img2:
                return self._json(400, {'error': 'image1 and image2 are required'})

            score = cosine_similarity(get_embedding(img1), get_embedding(img2))

            return self._json(200, {
                'match': score >= THRESHOLD,
                'score': round(score, 4),
                'threshold': THRESHOLD,
                'method': 'arcface'
            })
        except Exception as e:
            return self._json(500, {'error': str(e)})

    def do_GET(self):
        return self._json(200, {
            'service': 'ArcFace Face Verification',
            'model': 'w600k_mbf (MobileFaceNet + ArcFace loss)',
            'model_cached': os.path.exists(RECOGNITION_MODEL),
            'threshold': THRESHOLD
        })

    def _json(self, status, data):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
