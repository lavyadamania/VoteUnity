/**
 * Main JavaScript - VoteUnity Secure Voting System
 */

// Webcam Capture Class
class WebcamCapture {
    constructor(video, canvas) {
        this.video = video;
        this.canvas = canvas;
        this.stream = null;
    }

    async start() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 320, height: 240, facingMode: 'user' }
            });
            this.video.srcObject = this.stream;
            this.video.play();
            return true;
        } catch (err) {
            console.error('Webcam error:', err);
            alert('Could not access webcam. Please allow camera permissions.');
            return false;
        }
    }

    capture() {
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;
        this.canvas.getContext('2d').drawImage(this.video, 0, 0);
        return this.canvas.toDataURL('image/jpeg', 0.8);
    }

    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }
    }
}

// Validation functions
function validateAadhaar(aadhaar) {
    return /^\d{12}$/.test(aadhaar);
}

function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

// Registration form handling
function initRegistrationForm() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    const startBtn = document.getElementById('startWebcam');
    const captureBtn = document.getElementById('capturePhoto');
    const faceDataInput = document.getElementById('faceData');
    const previewContainer = document.getElementById('capturePreview');

    let webcam = null;

    if (startBtn) {
        startBtn.addEventListener('click', async () => {
            webcam = new WebcamCapture(video, canvas);
            const started = await webcam.start();
            if (started) {
                video.classList.remove('hidden');
                startBtn.classList.add('hidden');
                captureBtn.classList.remove('hidden');
            }
        });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', () => {
            if (webcam) {
                const imageData = webcam.capture();
                if (imageData) {
                    faceDataInput.value = imageData;
                    previewContainer.innerHTML = `
                        <img src="${imageData}" alt="Captured face" style="max-width: 200px; border-radius: 8px; margin: 1rem auto; display: block;">
                        <p style="color: #10b981; text-align: center;">✓ Photo captured!</p>
                    `;
                }

                webcam.stop();
                video.classList.add('hidden');
                captureBtn.classList.add('hidden');
            }
        });
    }

    form.addEventListener('submit', (e) => {
        const aadhaar = document.getElementById('aadhaar').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        let errors = [];

        if (!validateAadhaar(aadhaar)) {
            errors.push('Aadhaar must be exactly 12 digits');
        }

        if (!validateEmail(email)) {
            errors.push('Please enter a valid email address');
        }

        if (!validatePassword(password)) {
            errors.push('Password must be at least 6 characters');
        }

        if (password !== confirmPassword) {
            errors.push('Passwords do not match');
        }

        if (!faceDataInput.value) {
            errors.push('Please capture your face photo');
        }

        if (errors.length > 0) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
    });
}

// Login form handling
function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    const startBtn = document.getElementById('startWebcam');
    const captureBtn = document.getElementById('capturePhoto');
    const faceDataInput = document.getElementById('faceData');
    const previewContainer = document.getElementById('capturePreview');

    let webcam = null;

    if (startBtn) {
        startBtn.addEventListener('click', async () => {
            webcam = new WebcamCapture(video, canvas);
            const started = await webcam.start();
            if (started) {
                video.classList.remove('hidden');
                startBtn.classList.add('hidden');
                captureBtn.classList.remove('hidden');
            }
        });
    }

    if (captureBtn) {
        captureBtn.addEventListener('click', () => {
            if (webcam) {
                const imageData = webcam.capture();
                if (imageData) {
                    faceDataInput.value = imageData;
                    previewContainer.innerHTML = `
                        <img src="${imageData}" alt="Captured face" style="max-width: 200px; border-radius: 8px; margin: 1rem auto; display: block;">
                        <p style="color: #10b981; text-align: center;">✓ Face captured!</p>
                    `;
                }

                webcam.stop();
                video.classList.add('hidden');
                captureBtn.classList.add('hidden');
            }
        });
    }
}

// Voting page handling
function initVotingPage() {
    const cards = document.querySelectorAll('.candidate-card');
    const form = document.getElementById('voteForm');
    const selectedInput = document.getElementById('selectedCandidate');
    const submitBtn = document.getElementById('submitVote');

    if (!cards.length) return;

    cards.forEach(card => {
        card.addEventListener('click', () => {
            // Remove selection from all
            cards.forEach(c => c.classList.remove('selected'));

            // Select this one
            card.classList.add('selected');
            selectedInput.value = card.dataset.candidateId;
            submitBtn.disabled = false;
        });
    });

    if (form) {
        form.addEventListener('submit', (e) => {
            if (!selectedInput.value) {
                e.preventDefault();
                alert('Please select a candidate');
                return;
            }

            if (!confirm('Are you sure you want to cast your vote? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initRegistrationForm();
    initLoginForm();
    initVotingPage();

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
