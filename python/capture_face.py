"""
Face Capture Script - Secure Online Voting System
Uses Haar Cascade for real-time capture (fast) and relies 
on DeepFace for subsequent robust verification.
"""

import cv2
import sys
import os

def load_face_cascade():
    """Load the Haar Cascade for face detection."""
    return cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')


def capture_face(output_path=None, show_preview=True):
    """Capture face from webcam using Haar Cascade for preview."""
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("ERROR: Could not open webcam")
        return False

    face_cascade = load_face_cascade()
    print("Camera ready. Face detection: Haar Cascade")
    print("Press 'c' to capture, 'q' to quit.")

    captured = False
    while True:
        ret, frame = cap.read()
        if not ret: break

        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        faces = face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(100, 100))

        display_frame = frame.copy()
        for (x, y, w, h) in faces:
            cv2.rectangle(display_frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
            cv2.putText(display_frame, "Face Detected", (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)

        cv2.putText(display_frame, "Press 'c' to capture, 'q' to quit", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
        if show_preview: cv2.imshow('Face Capture - VoteUnity', display_frame)

        key = cv2.waitKey(1) & 0xFF
        if key == ord('c'):
            if len(faces) > 0:
                if output_path: cv2.imwrite(output_path, frame)
                captured = True
                break
        elif key == ord('q'): break

    cap.release()
    cv2.destroyAllWindows()
    return captured


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python capture_face.py <output_path>")
        sys.exit(1)
    
    if sys.argv[1] == "--test":
        cap = cv2.VideoCapture(0)
        ret = cap.isOpened()
        cap.release()
        print("Webcam OK" if ret else "Webcam Error")
        sys.exit(0 if ret else 1)
    else:
        success = capture_face(sys.argv[1])
        sys.exit(0 if success else 1)
