<?php
require_once __DIR__ . '/../includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/pages/vote.php');
}

$errors = [];

// First-run guard: if no voter exists, route user to registration instead of looping on login.
$hasAnyVoter = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() > 0;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$hasAnyVoter) {
    setFlashMessage('error', 'No voter account exists yet. Please register first.');
    redirect(BASE_URL . '/pages/register.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $faceData = $_POST['faceData'] ?? '';
    $bypassFaceId = isset($_POST['bypassFaceId']) && $_POST['bypassFaceId'] === '1';

    // Validation
    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required';
    }

    if (empty($errors)) {
        try {
            // Find user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
            $user = false;
        }

        // Only check login if database query succeeded
        if ($user !== false) {
            if ($user && password_verify($password, $user['password'])) {
                // Face verification with first-login auto enrollment
                $faceVerified = $bypassFaceId; // Skip face verification if bypass is enabled
                $isFirstFaceEnrollment = false;

                $saveFaceTemplate = function ($dataUri) use (&$pdo, &$user, $isVercel) {
                    if ($isVercel) {
                        $faceImagePath = $dataUri;
                    } else {
                        $uploadsDir = dirname(__DIR__) . '/uploads/faces/';
                        if (!is_dir($uploadsDir)) {
                            mkdir($uploadsDir, 0755, true);
                        }
                        $faceImagePath = 'faces/user_' . $user['id'] . '_faceid.jpg';
                        $imageData = explode(',', $dataUri, 2)[1] ?? '';
                        file_put_contents(dirname(__DIR__) . '/uploads/' . $faceImagePath, base64_decode($imageData));
                    }

                    $stmt = $pdo->prepare("UPDATE users SET face_image = ? WHERE id = ?");
                    $stmt->execute([$faceImagePath, $user['id']]);
                    $user['face_image'] = $faceImagePath;
                };

                if (empty($faceData)) {
                    $errors[] = 'Face capture is required for login verification.';
                    $faceVerified = false;
                }

                if ($faceVerified && !$user['face_image']) {
                    $saveFaceTemplate($faceData);
                    $isFirstFaceEnrollment = true;
                }

                // Skip remaining face verification if bypass is enabled
                if ($bypassFaceId) {
                    $faceVerified = true;
                } elseif ($faceVerified && $faceData && $user['face_image'] && !$isFirstFaceEnrollment) {
                    if (str_starts_with($user['face_image'], 'data:')) {
                        // Vercel/PostgreSQL: face stored as base64 — strict comparison
                        $result = compareFaces($user['face_image'], $faceData);
                        $faceVerified = $result['match'];
                        $methodInfo = getFaceMethodInfo($result['method'] ?? null);
                        if (!$faceVerified) {
                            $score = round($result['score'] * 100, 1);
                            $errors[] = "Face verification failed! Your face does not match. (Score: {$score}%, Required: 60%) [Engine: {$methodInfo['label']}]";
                        }
                    } else {
                        // Local: file-based comparison
                        $tempFacePath = dirname(__DIR__) . '/uploads/temp_login_' . $user['id'] . '.jpg';
                        $imageData = explode(',', $faceData, 2)[1] ?? '';
                        file_put_contents($tempFacePath, base64_decode($imageData));

                        $storedFacePath = dirname(__DIR__) . '/uploads/' . $user['face_image'];

                        if (!file_exists($storedFacePath)) {
                            // Missing local template: treat as first-time enrollment
                            $saveFaceTemplate($faceData);
                            $isFirstFaceEnrollment = true;
                        } else {
                            $result = compareFaces($storedFacePath, $tempFacePath);
                            $faceVerified = $result['match'];
                            $methodInfo = getFaceMethodInfo($result['method'] ?? null);

                            if (!$faceVerified) {
                                $score = round($result['score'] * 100, 1);
                                $errors[] = "Face verification failed! Face does not match stored ID. (Score: {$score}%, Required: 60%) [Engine: {$methodInfo['label']}]";
                            }
                        }

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

                    // Issue JWT token
                    $jwt = generateJWT([
                        'user_id' => $user['id'],
                        'email' => $user['email'],
                        'role' => 'voter'
                    ]);
                    setJWTCookie($jwt);

                    // Audit log
                    $auditMessage = 'Voter login successful: ' . $user['email'];
                    if ($bypassFaceId) {
                        $auditMessage .= ' (Face ID Bypassed - Dev Mode)';
                    }
                    logAuditEvent($pdo, AUDIT_LOGIN, ACTOR_VOTER, $user['id'], $auditMessage);

                    if ($isFirstFaceEnrollment) {
                        setFlashMessage('success', 'Face ID registered successfully. Welcome, ' . $user['name'] . '!');
                    } else {
                        setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                    }
                    redirect(BASE_URL . '/pages/vote.php');
                }
            } else {
                logAuditEvent($pdo, AUDIT_LOGIN_FAIL, ACTOR_VOTER, null, 'Failed voter login attempt for email: ' . $email);
                if (!$hasAnyVoter) {
                    $errors[] = 'No voter account exists yet. Please register first.';
                } elseif (!$user) {
                    $errors[] = 'No voter account found for this email. Please register first.';
                } else {
                    $errors[] = 'Invalid email or password';
                }
            }
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
                    <div>• <?= htmlspecialchars($error) ?></div>
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

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="bypassFaceId" name="bypassFaceId" value="1" onchange="toggleFaceVerification()">
                    <span>🚫 Bypass Face Verification (Development Only)</span>
                </label>
                <p style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
                    ⚠️ Skip face authentication and sign in with email/password only. Use for testing only.
                </p>
            </div>

            <div class="form-group" id="faceVerifyContainer">
                <label>Face Verification (Required if set)</label>
                <?php
                $faceMethod = detectFaceRecognitionMethod();
                $faceInfo = getFaceMethodInfo($faceMethod);
                ?>
                <div class="face-method-box">
                    <div>
                        <div class="method-label">Face Recognition Engine</div>
                        <div class="method-detail"><?= htmlspecialchars($faceInfo['description']) ?></div>
                    </div>
                    <span class="face-method-badge" style="color: <?= $faceInfo['color'] ?>; border-color: <?= $faceInfo['color'] ?>33; background: <?= $faceInfo['color'] ?>15;">
                        <span class="method-icon"><?= $faceInfo['icon'] ?></span>
                        <?= htmlspecialchars($faceInfo['label']) ?>
                        <span class="method-tier"><?= htmlspecialchars($faceInfo['tier']) ?></span>
                    </span>
                </div>
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

<style>
.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 1rem;
    margin: 0;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 10px;
    cursor: pointer;
}

.checkbox-label span {
    user-select: none;
}

.face-hidden {
    display: none !important;
}
</style>

<script>
function toggleFaceVerification() {
    const bypass = document.getElementById('bypassFaceId').checked;
    const faceContainer = document.getElementById('faceVerifyContainer');
    const webcamVideo = document.getElementById('webcamVideo');
    
    if (bypass) {
        // Hide face verification section
        faceContainer.classList.add('face-hidden');
        // Stop any active webcam
        if (webcamVideo && webcamVideo.srcObject) {
            webcamVideo.srcObject.getTracks().forEach(track => track.stop());
        }
    } else {
        // Show face verification section
        faceContainer.classList.remove('face-hidden');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>