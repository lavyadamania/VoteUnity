"""
DeepFace Face Verification — Vercel Python Serverless Function

POST /api/verify_face
Body: {"image1": "data:image/jpeg;base64,...", "image2": "data:image/jpeg;base64,..."}
Returns: {"match": true/false, "score": 0.85, "threshold": 0.60, "method": "deepface_api"}
"""

from http.server import BaseHTTPRequestHandler
import json
import base64
import io

import numpy as np
from PIL import Image

THRESHOLD = 0.60


def decode_image_array(image_data):
    """Decode base64 data URL to RGB numpy array."""
    if image_data.startswith('data:'):
        image_data = image_data.split(',', 1)[1]
    img_bytes = base64.b64decode(image_data)
    return np.array(Image.open(io.BytesIO(img_bytes)).convert('RGB'))


def verify_with_deepface(img1_data, img2_data):
    """Run DeepFace verification with free open models."""
    from deepface import DeepFace

    img1 = decode_image_array(img1_data)
    img2 = decode_image_array(img2_data)

    # Use a free model family and OpenCV detector for serverless speed.
    result = DeepFace.verify(
        img1_path=img1,
        img2_path=img2,
        model_name='Facenet512',
        detector_backend='opencv',
        enforce_detection=True,
        align=True,
    )

    similarity = 1.0 - float(result.get('distance', 1.0))
    return bool(result.get('verified', False)), similarity


class handler(BaseHTTPRequestHandler):
    def do_POST(self):
        try:
            body = self.rfile.read(int(self.headers.get('Content-Length', 0)))
            data = json.loads(body)
            img1, img2 = data.get('image1', ''), data.get('image2', '')

            if not img1 or not img2:
                return self._json(400, {'error': 'image1 and image2 are required'})

            is_match, score = verify_with_deepface(img1, img2)
            return self._json(200, {
                'match': is_match,
                'score': round(score, 4),
                'threshold': THRESHOLD,
                'method': 'deepface_api'
            })
        except Exception as e:
            return self._json(500, {'error': str(e)})

    def do_GET(self):
        return self._json(200, {
            'service': 'DeepFace Verification Service',
            'primary_model': 'DeepFace Facenet512',
            'threshold': THRESHOLD
        })

    def _json(self, status, data):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
