"""
Face Capture Script - Secure Online Voting System
"""

import cv2
import sys
import os

def load_face_cascade():
    """Load the Haar Cascade for face detection."""
    # Try different possible locations
    cascade_paths = [
        cv2.data.haarcascades + 'haarcascade_frontalface_default.xml',
        'haarcascade_frontalface_default.xml',
    ]
    
    for path in cascade_paths:
        if os.path.exists(path):
            return cv2.CascadeClassifier(path)
    
    return cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')


def capture_face(output_path=None, show_preview=True):
    """
    Capture a face from webcam.
    
    Args:
        output_path: Path to save the captured image
        show_preview: Whether to show camera preview
    
    Returns:
        True if face captured successfully, False otherwise
    """
    # Initialize camera
    cap = cv2.VideoCapture(0)
    
    if not cap.isOpened():
        print("ERROR: Could not open webcam")
        return False
    
    # Load face detector
    face_cascade = load_face_cascade()
    
    print("Camera ready. Press 'c' to capture, 'q' to quit.")
    
    captured = False
    
    while True:
        ret, frame = cap.read()
        if not ret:
            print("ERROR: Could not read frame")
            break
        
        # Convert to grayscale for face detection
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        
        # Detect faces
        faces = face_cascade.detectMultiScale(
            gray, 
            scaleFactor=1.1, 
            minNeighbors=5, 
            minSize=(100, 100)
        )
        
        # Draw rectangles around detected faces
        display_frame = frame.copy()
        for (x, y, w, h) in faces:
            cv2.rectangle(display_frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
            cv2.putText(display_frame, "Face Detected", (x, y-10), 
                       cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
        
        # Add instructions
        cv2.putText(display_frame, "Press 'c' to capture, 'q' to quit", 
                   (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
        
        if show_preview:
            cv2.imshow('Face Capture - VoteUnity', display_frame)
        
        # Handle keypresses
        key = cv2.waitKey(1) & 0xFF
        
        if key == ord('c'):
            if len(faces) > 0:
                if output_path:
                    cv2.imwrite(output_path, frame)
                    print(f"SUCCESS: Face saved to {output_path}")
                captured = True
                break
            else:
                print("WARNING: No face detected. Please position your face in the frame.")
        elif key == ord('q'):
            print("Cancelled by user")
            break
    
    # Cleanup
    cap.release()
    cv2.destroyAllWindows()
    
    return captured


def test_webcam():
    """Test if webcam is working."""
    print("Testing webcam...")
    cap = cv2.VideoCapture(0)
    
    if not cap.isOpened():
        print("ERROR: Webcam not accessible")
        return False
    
    ret, frame = cap.read()
    cap.release()
    
    if ret:
        print("SUCCESS: Webcam is working!")
        print(f"Frame size: {frame.shape[1]}x{frame.shape[0]}")
        return True
    else:
        print("ERROR: Could not read from webcam")
        return False


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python capture_face.py <output_path>")
        print("       python capture_face.py --test")
        sys.exit(1)
    
    if sys.argv[1] == "--test":
        success = test_webcam()
        sys.exit(0 if success else 1)
    else:
        output_path = sys.argv[1]
        success = capture_face(output_path)
        sys.exit(0 if success else 1)
