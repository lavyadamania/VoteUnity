"""
ArcFace Face Verification Script - Secure Online Voting System
Powered by DeepFace (ArcFace model)

This script replaces the insightface-based implementation to avoid build errors 
on Python 3.13. It maintains same CLI interface and output format.

Output format:
  MATCH:<score>    - if faces match
  NO_MATCH:<score> - if faces don't match
"""

import sys
import os
import base64
import numpy as np
import cv2

# Suppress TensorFlow logging
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

try:
    from deepface import DeepFace
    _has_deepface = True
except ImportError:
    _has_deepface = False

# DeepFace returns distance; ArcFace distance threshold (Cosine) is usually around 0.40
# We want to output a similarity score (1 - distance) for compatibility
THRESHOLD = 0.40


def load_image(input_str):
    """Load image from file or base64."""
    if input_str.startswith('data:'):
        try:
            b64_data = input_str.split(',', 1)[1]
            img_bytes = base64.b64decode(b64_data)
            img_array = np.frombuffer(img_bytes, dtype=np.uint8)
            return cv2.imdecode(img_array, cv2.IMREAD_COLOR)
        except Exception as e:
            print(f"ERROR: Base64 decode failed: {e}", file=sys.stderr)
            return None
    return cv2.imread(input_str)


def verify_faces(input1, input2):
    """Verify faces using DeepFace ArcFace."""
    if not _has_deepface:
        print("ERROR: DeepFace library not installed", file=sys.stderr)
        return False, 0.0

    try:
        # DeepFace.verify handles strings (paths) or numpy arrays
        # We'll pass pre-loaded images to handle base64 inputs properly
        img1 = load_image(input1)
        img2 = load_image(input2)

        if img1 is None or img2 is None:
            return False, 0.0

        # Run verification
        # model_name: ArcFace, VGG-Face, Facenet, OpenFace, DeepFace, DeepID, Dlib, SFace
        # detector_backend: opencv, retinaface, mtcnn, ssd, dlib, mediapipe, yolov8
        # We'll use 'opencv' for speed or 'retinaface' for accuracy. 
        # On CPU, 'opencv' is much faster.
        result = DeepFace.verify(
            img1_path=img1,
            img2_path=img2,
            model_name="ArcFace",
            detector_backend="opencv",
            enforce_detection=False, # Don't crash if no face found
            align=True
        )

        distance = result['distance']
        # For Cosine distance, similarity is 1 - distance
        similarity = 1 - distance
        is_match = result['verified']

        return is_match, similarity

    except Exception as e:
        print(f"ERROR: DeepFace verification failed: {e}", file=sys.stderr)
        return False, 0.0


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python arcface_verify.py <image1> <image2>")
        sys.exit(2)

    input1 = sys.argv[1]
    input2 = sys.argv[2]

    try:
        is_match, score = verify_faces(input1, input2)

        if is_match:
            print(f"MATCH:{score:.4f}")
        else:
            print(f"NO_MATCH:{score:.4f}")

        sys.exit(0 if is_match else 1)

    except Exception as e:
        print(f"ERROR: {e}", file=sys.stderr)
        sys.exit(2)
