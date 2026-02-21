<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Guard: require DB before any usage
requireDb($pdo, $db_error ?? null);

// Redirect if already logged in
if (isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/dashboard.php');
}

$errors = [];
$step = isset($_SESSION['admin_login_step']) ? $_SESSION['admin_login_step'] : 'credentials';
$pendingAdminId = isset($_SESSION['pending_admin_id']) ? $_SESSION['pending_admin_id'] : null;

// Handle credentials submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_step']) && $_POST['login_step'] === 'credentials') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors[] = 'Username and password are required';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            $admin = false;
        }

        // Only check login if database query succeeded
        if ($admin !== false) {
            if ($admin && password_verify($password, $admin['password'])) {
                // Check if admin is approved (super admin is always approved)
                $isSuperAdmin = $admin['is_super_admin'] ?? 0;
                $isApproved = $admin['is_approved'] ?? 0;

                if (!$isSuperAdmin && !$isApproved) {
                    $errors[] = 'Your account is pending approval from the Super Admin.';
                } else {
                    // Credentials valid, proceed to face verification
                    $_SESSION['admin_login_step'] = 'face';
                    $_SESSION['pending_admin_id'] = $admin['id'];
                    $step = 'face';
                    $pendingAdminId = $admin['id'];
                }
            } else {
                $errors[] = 'Invalid username or password';
            }
        }
    }
}

// Handle face verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_step']) && $_POST['login_step'] === 'face') {
    $faceData = $_POST['faceData'] ?? '';

    if (empty($faceData)) {
        $errors[] = 'Please capture your face for verification';
    } else if ($pendingAdminId) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$pendingAdminId]);
        $admin = $stmt->fetch();

        if ($admin) {
            $faceVerified = false;
            $verificationMessage = '';

            if ($admin['face_image']) {
                if (str_starts_with($admin['face_image'], 'data:')) {
                    // Vercel: face stored as base64 — strict comparison
                    $result = compareFaces($admin['face_image'], $faceData);
                    if ($result['match']) {
                        $faceVerified = true;
                        $verificationMessage = "Face verified (" . round($result['score'] * 100, 1) . "%)";
                    } else {
                        $score = round($result['score'] * 100, 1);
                        $errors[] = "Face verification failed! Face does not match. (Score: {$score}%, Required: 60%)";
                    }
                } else {
                    // Local: file-based comparison
                    $uploadsDir = dirname(dirname(__DIR__)) . '/uploads/';
                    $tempFacePath = $uploadsDir . 'temp_admin_' . $admin['id'] . '.jpg';
                    $imageData = explode(',', $faceData)[1];
                    file_put_contents($tempFacePath, base64_decode($imageData));

                    if (file_exists($uploadsDir . $admin['face_image'])) {
                        $storedFacePath = $uploadsDir . $admin['face_image'];
                        $result = compareFaces($storedFacePath, $tempFacePath);

                        if ($result['match']) {
                            $faceVerified = true;
                        } else {
                            $score = round($result['score'] * 100, 1);
                            $errors[] = "Face verification failed! Face does not match. (Score: {$score}%, Required: 60%)";
                        }
                    } else {
                        // Stored face file missing — save this as their face
                        $faceImagePath = 'admin_' . $admin['id'] . '.jpg';
                        rename($tempFacePath, $uploadsDir . $faceImagePath);
                        $stmt = $pdo->prepare("UPDATE admins SET face_image = ? WHERE id = ?");
                        $stmt->execute([$faceImagePath, $admin['id']]);
                        $faceVerified = true;
                    }
                    @unlink($tempFacePath);
                }
            } else {
                // No stored face — save this face (first login)
                if ($isVercel) {
                    $stmt = $pdo->prepare("UPDATE admins SET face_image = ? WHERE id = ?");
                    $stmt->execute([$faceData, $admin['id']]);
                } else {
                    $uploadsDir = dirname(dirname(__DIR__)) . '/uploads/';
                    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                    $faceImagePath = 'admin_' . $admin['id'] . '.jpg';
                    $imageData = explode(',', $faceData)[1];
                    file_put_contents($uploadsDir . $faceImagePath, base64_decode($imageData));
                    $stmt = $pdo->prepare("UPDATE admins SET face_image = ? WHERE id = ?");
                    $stmt->execute([$faceImagePath, $admin['id']]);
                }
                $faceVerified = true;
            }

            if ($faceVerified) {
                $isSuperAdmin = $admin['is_super_admin'] ?? 0;
                if (!$isSuperAdmin) {
                    $_SESSION['admin_requires_location'] = true;
                    $_SESSION['admin_pending_login'] = $admin['id'];
                    $_SESSION['admin_pending_username'] = $admin['username'];
                    unset($_SESSION['admin_login_step']);
                    unset($_SESSION['pending_admin_id']);
                    redirect(BASE_URL . '/pages/admin/capture_location.php');
                } else {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    unset($_SESSION['admin_login_step']);
                    unset($_SESSION['pending_admin_id']);
                    redirect(BASE_URL . '/pages/admin/dashboard.php');
                }
            }
        }
    }
}

// Cancel face verification
if (isset($_GET['cancel'])) {
    unset($_SESSION['admin_login_step']);
    unset($_SESSION['pending_admin_id']);
    redirect(BASE_URL . '/pages/admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/">← Back to Main Site</a>
        </div>
    </nav>

    <main class="container">
        <div class="auth-container">
            <div class="auth-card">
                <?php if ($step === 'credentials'): ?>
                    <div class="card-header">
                        <h2>🔑 Admin Login</h2>
                        <p>Step 1: Enter your credentials</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="login_step" value="credentials">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Next: Face Verification →</button>
                    </form>
                    <div style="margin-top: 1.5rem; text-align: center; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <a href="register.php" class="btn btn-secondary btn-block">📝 Register as Admin</a>
                    </div>

                <?php else: ?>
                    <div class="card-header">
                        <h2>👤 Face Verification</h2>
                        <p>Step 2: Verify your identity</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <div>• <?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <strong>⚠️ Security:</strong> Strict biometric matching is enabled.
                    </div>

                    <form method="POST" action="" id="faceForm">
                        <input type="hidden" name="login_step" value="face">
                        <input type="hidden" id="faceData" name="faceData">
                        <div class="webcam-container">
                            <video id="webcamVideo" class="webcam-preview" autoplay playsinline style="display: none;"></video>
                            <canvas id="webcamCanvas" style="display: none;"></canvas>
                            <div id="capturePreview"></div>
                            <div class="webcam-controls">
                                <button type="button" id="startWebcam" class="btn btn-secondary">📷 Start Camera</button>
                                <button type="button" id="capturePhoto" class="btn btn-primary hidden">📸 Capture Face</button>
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <a href="?cancel=1" class="btn btn-secondary" style="flex: 1;">← Back</a>
                            <button type="submit" id="verifyBtn" class="btn btn-success" style="flex: 2;" disabled>✓ Verify & Login</button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const video = document.getElementById('webcamVideo');
                            const canvas = document.getElementById('webcamCanvas');
                            const startBtn = document.getElementById('startWebcam');
                            const captureBtn = document.getElementById('capturePhoto');
                            const verifyBtn = document.getElementById('verifyBtn');
                            const faceDataInput = document.getElementById('faceData');
                            const previewContainer = document.getElementById('capturePreview');
                            let stream = null;

                            startBtn.addEventListener('click', async () => {
                                try {
                                    stream = await navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240, facingMode: 'user' } });
                                    video.srcObject = stream;
                                    video.play();
                                    video.style.display = 'block';
                                    startBtn.classList.add('hidden');
                                    captureBtn.classList.remove('hidden');
                                } catch (err) {
                                    alert('Could not access webcam.');
                                }
                            });

                            captureBtn.addEventListener('click', () => {
                                canvas.width = video.videoWidth;
                                canvas.height = video.videoHeight;
                                canvas.getContext('2d').drawImage(video, 0, 0);
                                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                                faceDataInput.value = imageData;
                                previewContainer.innerHTML = `<img src="${imageData}" style="max-width: 200px; border-radius: 8px; margin: 1rem auto; display: block;"><p style="color: #10b981; text-align: center;">✓ Captured!</p>`;
                                if(stream) stream.getTracks().forEach(track => track.stop());
                                video.style.display = 'none';
                                captureBtn.classList.add('hidden');
                                verifyBtn.disabled = false;
                            });
                        });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>