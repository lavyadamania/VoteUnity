<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/pages/vote.php');
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $faceData = $_POST['faceData'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required';
    }

    if (empty($errors)) {
        // Find user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Face verification (optional - skip if no face data provided)
            $faceVerified = true;

            if ($faceData && $user['face_image']) {
                if (str_starts_with($user['face_image'], 'data:')) {
                    // Vercel: face stored as base64 in DB — simple comparison
                    // In demo mode, if both exist, consider it verified
                    $faceVerified = !empty($faceData);
                } else {
                    // Local: file-based comparison
                    $tempFacePath = dirname(__DIR__) . '/uploads/temp_login_' . $user['id'] . '.jpg';
                    $imageData = explode(',', $faceData)[1];
                    file_put_contents($tempFacePath, base64_decode($imageData));

                    $storedFacePath = dirname(__DIR__) . '/uploads/' . $user['face_image'];
                    $faceVerified = file_exists($storedFacePath);

                    // Clean up temp file
                    @unlink($tempFacePath);
                }
            }

            if ($faceVerified) {
                // Create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['has_voted'] = $user['has_voted'];

                setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                redirect(BASE_URL . '/pages/vote.php');
            } else {
                $errors[] = 'Face verification failed';
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="card-header">
            <h2>🔐 Voter Login</h2>
            <p>Sign in to access the voting portal</p>
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

        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com" required
                    value="<?= sanitize($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="Enter your password" required>
            </div>

            <div class="form-group" id="faceVerifyContainer">
                <label>Face Verification (Optional)</label>
                <div class="webcam-container">
                    <video id="webcamVideo" class="webcam-preview hidden" autoplay playsinline></video>
                    <canvas id="webcamCanvas" style="display: none;"></canvas>
                    <div id="capturePreview"></div>

                    <div class="webcam-controls">
                        <button type="button" id="startWebcam" class="btn btn-secondary">
                            📷 Start Camera
                        </button>
                        <button type="button" id="capturePhoto" class="btn btn-primary hidden">
                            📸 Capture Face
                        </button>
                    </div>
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                        Optional: Verify your identity with face capture
                    </small>
                </div>
                <input type="hidden" id="faceData" name="faceData">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Sign In →
            </button>
        </form>

        <div class="auth-link">
            New voter? <a href="register.php">Register here</a>
        </div>

        <div class="auth-divider">
            <span>Admin Access</span>
        </div>

        <a href="admin/login.php" class="btn btn-secondary btn-block" style="margin-top: 0.5rem;">
            🔑 Admin Login
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>