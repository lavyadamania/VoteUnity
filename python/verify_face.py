"""
Face Verification Script - Secure Online Voting System
Uses DeepFace (ArcFace model) with fallback to histogram correlation.
"""

import cv2
import numpy as np
import sys
import os

# Matching threshold
MATCH_THRESHOLD_ARCFACE = 0.60 # Higher for DeepFace verification
MATCH_THRESHOLD_LEGACY = 0.6

# Try to import DeepFace
_use_deepface = False
try:
    from deepface import DeepFace
    _use_deepface = True
except ImportError:
    _use_deepface = False


def load_face_cascade():
    """Load the Haar Cascade for face detection (legacy fallback)."""
    return cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')


def compare_faces_deepface(path1, path2):
    """Compare faces using DeepFace ArcFace."""
    if not _use_deepface:
        return 0.0, False

    try:
        result = DeepFace.verify(
            img1_path=path1,
            img2_path=path2,
            model_name="ArcFace",
            detector_backend="opencv",
            enforce_detection=False,
            align=True
        )
        # DeepFace 'verified' boolean uses internal calibrated thresholds
        return 1 - result['distance'], result['verified']
    except Exception as e:
        print(f"ERROR: DeepFace failed: {e}", file=sys.stderr)
        return 0.0, False


def compute_histogram(image):
    """Compute color histogram (legacy)."""
    image = cv2.resize(image, (100, 100))
    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
    hist = cv2.calcHist([hsv], [0, 1, 2], None, [8, 8, 8], [0, 180, 0, 256, 0, 256])
    cv2.normalize(hist, hist)
    return hist.flatten()


def verify_faces(stored_path, captured_path):
    """Main verification function."""
    if not os.path.exists(stored_path) or not os.path.exists(captured_path):
        return False, 0.0

    if _use_deepface:
        score, is_match = compare_faces_deepface(stored_path, captured_path)
        return is_match, score
    else:
        # Legacy fallback
        stored_img = cv2.imread(stored_path)
        captured_img = cv2.imread(captured_path)
        if stored_img is None or captured_img is None:
            return False, 0.0
        
        # Simple histogram match
        score = (cv2.compareHist(compute_histogram(stored_img).reshape(-1, 1).astype(np.float32), 
                                compute_histogram(captured_img).reshape(-1, 1).astype(np.float32), 
                                cv2.HISTCMP_CORREL) + 1) / 2
        return score >= MATCH_THRESHOLD_LEGACY, score


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python verify_face.py <stored_path> <captured_path>")
        sys.exit(1)

    stored_path = sys.argv[1]
    captured_path = sys.argv[2]

    is_match, score = verify_faces(stored_path, captured_path)

    if is_match:
        print(f"MATCH:{score:.4f}")
    else:
        print(f"NO_MATCH:{score:.4f}")

    sys.exit(0 if is_match else 1)
