"""
Vercel-safe face verification endpoint.

The DeepFace model stack is too large for Lambda's 500 MB ephemeral storage
limit, so this endpoint uses a lightweight Pillow-only similarity check that
can deploy reliably on Vercel.

POST /api/verify_face
Body: {"image1": "data:image/jpeg;base64,...", "image2": "data:image/jpeg;base64,..."}
Returns: {"match": true/false, "score": 0.85, "threshold": 0.60, "method": "deepface_api"}
"""

from http.server import BaseHTTPRequestHandler
import json
import base64
import io

from PIL import Image, ImageOps

THRESHOLD = 0.60


def decode_image(image_data):
    """Decode a base64 data URL to a Pillow image."""
    if image_data.startswith('data:'):
        image_data = image_data.split(',', 1)[1]
    img_bytes = base64.b64decode(image_data)
    return Image.open(io.BytesIO(img_bytes)).convert('RGB')


def average_hash(image, hash_size=8):
    """Compute a compact perceptual hash for a Pillow image."""
    image = ImageOps.grayscale(image).resize((hash_size, hash_size), Image.Resampling.LANCZOS)
    pixels = list(image.getdata())
    avg = sum(pixels) / len(pixels)
    return [1 if pixel >= avg else 0 for pixel in pixels]


def hash_similarity(image1, image2):
    """Return a similarity score from 0.0 to 1.0."""
    hash1 = average_hash(image1)
    hash2 = average_hash(image2)
    matches = sum(1 for bit1, bit2 in zip(hash1, hash2) if bit1 == bit2)
    return matches / len(hash1)


def verify_with_lightweight_hash(img1_data, img2_data):
    """Run a Vercel-safe similarity check without heavy ML dependencies."""
    img1 = decode_image(img1_data)
    img2 = decode_image(img2_data)
    similarity = hash_similarity(img1, img2)
    return similarity >= THRESHOLD, similarity


class handler(BaseHTTPRequestHandler):
    def do_POST(self):
        try:
            body = self.rfile.read(int(self.headers.get('Content-Length', 0)))
            data = json.loads(body)
            img1, img2 = data.get('image1', ''), data.get('image2', '')

            if not img1 or not img2:
                return self._json(400, {'error': 'image1 and image2 are required'})

            is_match, score = verify_with_lightweight_hash(img1, img2)
            return self._json(200, {
                'match': is_match,
                'score': round(score, 4),
                'threshold': THRESHOLD,
                'method': 'vercel_lightweight_api'
            })
        except Exception as e:
            return self._json(500, {'error': str(e)})

    def do_GET(self):
        return self._json(200, {
            'service': 'Vercel-safe Face Verification Service',
            'primary_model': 'Pillow average-hash similarity',
            'threshold': THRESHOLD
        })

    def _json(self, status, data):
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.end_headers()
        self.wfile.write(json.dumps(data).encode())
