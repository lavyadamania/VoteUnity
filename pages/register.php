<?php
require_once '../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/voting/pages/vote.php');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $aadhaar = sanitize($_POST['aadhaar'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $faceData = $_POST['faceData'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }

    if (!validateAadhaar($aadhaar)) {
        $errors[] = 'Aadhaar must be exactly 12 digits';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($faceData)) {
        $errors[] = 'Face photo is required for registration';
    }

    // Check if email or aadhaar already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR aadhaar_number = ?");
        $stmt->execute([$email, $aadhaar]);
        if ($stmt->fetch()) {
            $errors[] = 'Email or Aadhaar number already registered';
        }
    }

    // Process registration
    if (empty($errors)) {
        // Save face image
        $faceImagePath = null;
        if ($faceData) {
            $uploadsDir = dirname(__DIR__) . '/uploads/faces/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Decode base64 and save
            $imageData = explode(',', $faceData)[1];
            $imageData = base64_decode($imageData);
            $faceImagePath = "faces/{$aadhaar}.jpg";
            file_put_contents($uploadsDir . "{$aadhaar}.jpg", $imageData);
        }

        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, aadhaar_number, face_image) 
            VALUES (?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([$name, $email, $hashedPassword, $aadhaar, $faceImagePath]);
            setFlashMessage('success', 'Registration successful! Please login to continue.');
            redirect('/voting/pages/login.php');
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="card-header">
            <h2>🆔 Voter Registration</h2>
            <p>Register with your Aadhaar to get voting access</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div>•
                        <?= $error ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name"
                    required value="<?= sanitize($_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required
                    value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="aadhaar">Aadhaar Number (Mock)</label>
                <input type="text" id="aadhaar" name="aadhaar" class="form-control" placeholder="123456789012"
                    maxlength="12" required value="<?= sanitize($_POST['aadhaar'] ?? '') ?>">
                <small style="color: var(--gray);">Enter any 12-digit number (simulated)</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" class="form-control"
                    placeholder="Re-enter password" required>
            </div>

            <div class="form-group">
                <label>Face Photo (for verification)</label>
                <div class="webcam-container">
                    <video id="webcamVideo" class="webcam-preview hidden" autoplay playsinline></video>
                    <canvas id="webcamCanvas" style="display: none;"></canvas>
                    <div id="capturePreview"></div>

                    <div class="webcam-controls">
                        <button type="button" id="startWebcam" class="btn btn-secondary">
                            📷 Start Camera
                        </button>
                        <button type="button" id="capturePhoto" class="btn btn-primary hidden">
                            📸 Capture Photo
                        </button>
                    </div>
                </div>
                <input type="hidden" id="faceData" name="faceData">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Register Now →
            </button>
        </form>

        <div class="auth-link">
            Already registered? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>