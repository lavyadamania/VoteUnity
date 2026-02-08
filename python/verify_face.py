"""
Face Verification Script - Secure Online Voting System
"""

import cv2
import numpy as np
import sys
import os

# Matching threshold (0.0 to 1.0)
# Higher = stricter matching
MATCH_THRESHOLD = 0.6


def load_face_cascade():
    """Load the Haar Cascade for face detection."""
    return cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')


def detect_and_crop_face(image):
    """
    Detect face in image and return cropped face region.
    
    Args:
        image: BGR image from OpenCV
    
    Returns:
        Cropped face image or None if no face found
    """
    face_cascade = load_face_cascade()
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    faces = face_cascade.detectMultiScale(
        gray, 
        scaleFactor=1.1, 
        minNeighbors=5,
        minSize=(50, 50)
    )
    
    if len(faces) == 0:
        return None
    
    # Get the largest face
    largest = max(faces, key=lambda rect: rect[2] * rect[3])
    x, y, w, h = largest
    
    # Add some padding
    padding = int(w * 0.1)
    x = max(0, x - padding)
    y = max(0, y - padding)
    w = w + (2 * padding)
    h = h + (2 * padding)
    
    return image[y:y+h, x:x+w]


def compute_histogram(image):
    """
    Compute color histogram of an image.
    
    Args:
        image: BGR image from OpenCV
    
    Returns:
        Normalized histogram
    """
    # Resize for consistency
    image = cv2.resize(image, (100, 100))
    
    # Convert to HSV for better color comparison
    hsv = cv2.cvtColor(image, cv2.COLOR_BGR2HSV)
    
    # Compute histogram
    hist = cv2.calcHist(
        [hsv], 
        [0, 1, 2],  # All channels
        None, 
        [8, 8, 8],  # Bins per channel
        [0, 180, 0, 256, 0, 256]  # Ranges
    )
    
    # Normalize
    cv2.normalize(hist, hist)
    
    return hist.flatten()


def compare_faces(image1, image2):
    """
    Compare two face images using histogram correlation.
    
    Args:
        image1: First face image
        image2: Second face image
    
    Returns:
        Similarity score (0.0 to 1.0)
    """
    hist1 = compute_histogram(image1)
    hist2 = compute_histogram(image2)
    
    # Compare using correlation
    score = cv2.compareHist(
        hist1.reshape(-1, 1).astype(np.float32),
        hist2.reshape(-1, 1).astype(np.float32),
        cv2.HISTCMP_CORREL
    )
    
    # Normalize to 0-1 range
    score = (score + 1) / 2
    
    return score


def verify_faces(stored_path, captured_path):
    """
    Main verification function.
    
    Args:
        stored_path: Path to stored reference face image
        captured_path: Path to newly captured face image
    
    Returns:
        Tuple of (is_match, score)
    """
    # Check if files exist
    if not os.path.exists(stored_path):
        print(f"ERROR: Stored image not found: {stored_path}", file=sys.stderr)
        return False, 0.0
    
    if not os.path.exists(captured_path):
        print(f"ERROR: Captured image not found: {captured_path}", file=sys.stderr)
        return False, 0.0
    
    # Load images
    stored_img = cv2.imread(stored_path)
    captured_img = cv2.imread(captured_path)
    
    if stored_img is None or captured_img is None:
        print("ERROR: Could not load images", file=sys.stderr)
        return False, 0.0
    
    # Detect and crop faces
    stored_face = detect_and_crop_face(stored_img)
    captured_face = detect_and_crop_face(captured_img)
    
    # If can't detect face in either, fall back to full image comparison
    if stored_face is None:
        print("WARNING: No face detected in stored image, using full image", file=sys.stderr)
        stored_face = stored_img
    
    if captured_face is None:
        print("WARNING: No face detected in captured image, using full image", file=sys.stderr)
        captured_face = captured_img
    
    # Compare faces
    score = compare_faces(stored_face, captured_face)
    is_match = score >= MATCH_THRESHOLD
    
    return is_match, score


if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python verify_face.py <stored_image_path> <captured_image_path>")
        print("")
        print("Output format:")
        print("  MATCH:<score>    - if faces match")
        print("  NO_MATCH:<score> - if faces don't match")
        sys.exit(1)
    
    stored_path = sys.argv[1]
    captured_path = sys.argv[2]
    
    is_match, score = verify_faces(stored_path, captured_path)
    
    if is_match:
        print(f"MATCH:{score:.4f}")
    else:
        print(f"NO_MATCH:{score:.4f}")
    
    sys.exit(0 if is_match else 1)
