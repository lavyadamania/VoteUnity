<?php
require_once '../includes/header.php';

// Require login
if (!isLoggedIn()) {
    setFlashMessage('error', 'Please login to access the voting page');
    redirect('/voting/pages/login.php');
}

// Get user info
$user = getUserById($pdo, $_SESSION['user_id']);
$hasVoted = $user['has_voted'];

// Get candidates
$stmt = $pdo->query("SELECT * FROM candidates ORDER BY id");
$candidates = $stmt->fetchAll();

// Check if face verification is pending
$faceVerified = isset($_SESSION['face_verified_for_vote']) && $_SESSION['face_verified_for_vote'];

// Handle face verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_face'])) {
    $faceData = $_POST['faceData'] ?? '';

    if (!empty($faceData) && $user['face_image']) {
        // Save captured face temporarily
        $uploadsDir = dirname(__DIR__) . '/uploads/';
        $tempFacePath = $uploadsDir . 'temp_vote_' . $user['id'] . '.jpg';
        $imageData = explode(',', $faceData)[1];
        file_put_contents($tempFacePath, base64_decode($imageData));

        $storedFacePath = $uploadsDir . $user['face_image'];

        // Actually compare faces using verifyFace function
        if (file_exists($storedFacePath)) {
            $result = verifyFace($storedFacePath, $tempFacePath);

            if (is_array($result)) {
                // New function returns array with match and score
                if ($result['match']) {
                    $_SESSION['face_verified_for_vote'] = true;
                    $faceVerified = true;
                    $score = round($result['score'] * 100, 1);
                    setFlashMessage('success', "Face verified successfully! (Match score: {$score}%) You can now cast your vote.");
                } else {
                    $score = round($result['score'] * 100, 1);
                    setFlashMessage('error', "Face verification failed! Your face does not match the registered photo. (Score: {$score}%, Required: 60%)");
                }
            } else {
                // Legacy function returns boolean
                if ($result) {
                    $_SESSION['face_verified_for_vote'] = true;
                    $faceVerified = true;
                    setFlashMessage('success', 'Face verified successfully! You can now cast your vote.');
                } else {
                    setFlashMessage('error', 'Face verification failed! Your face does not match the registered photo.');
                }
            }
        } else {
            setFlashMessage('error', 'No registered face found. Please contact admin.');
        }

        // Clean up temp file
        @unlink($tempFacePath);
    } else {
        setFlashMessage('error', 'Please capture your face for verification.');
    }

    redirect('/voting/pages/vote.php');
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id']) && !$hasVoted) {
    // Check face verification
    if (!$faceVerified) {
        setFlashMessage('error', 'You must verify your face before voting.');
        redirect('/voting/pages/vote.php');
    }

    $candidateId = intval($_POST['candidate_id'] ?? 0);

    if ($candidateId <= 0) {
        setFlashMessage('error', 'Please select a valid candidate');
    } else {
        // Verify candidate exists
        $stmt = $pdo->prepare("SELECT id FROM candidates WHERE id = ?");
        $stmt->execute([$candidateId]);

        if ($stmt->fetch()) {
            // Get previous hash for blockchain chain
            $previousHash = getLastVoteHash($pdo);
            $timestamp = time();

            // Generate vote hash
            $voteHash = generateVoteHash(
                $_SESSION['user_id'],
                $candidateId,
                $timestamp,
                $previousHash
            );

            try {
                $pdo->beginTransaction();

                // Insert vote
                $stmt = $pdo->prepare("
                    INSERT INTO votes (user_id, candidate_id, vote_hash, previous_hash, timestamp) 
                    VALUES (?, ?, ?, ?, FROM_UNIXTIME(?))
                ");
                $stmt->execute([$_SESSION['user_id'], $candidateId, $voteHash, $previousHash, $timestamp]);

                // Update user's voted flag
                $stmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);

                $pdo->commit();

                // Update session and clear face verification
                $_SESSION['has_voted'] = 1;
                unset($_SESSION['face_verified_for_vote']);
                $hasVoted = 1;

                setFlashMessage('success', 'Your vote has been cast successfully!');
            } catch (PDOException $e) {
                $pdo->rollBack();
                setFlashMessage('error', 'Failed to record vote. Please try again.');
            }
        } else {
            setFlashMessage('error', 'Invalid candidate selected');
        }
    }

    // Refresh page to show updated state
    redirect('/voting/pages/vote.php');
}
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <?php if ($hasVoted): ?>
        <!-- Already Voted State -->
        <div class="vote-success">
            <div class="vote-success-icon">✅</div>
            <h2>Thank You for Voting!</h2>
            <p style="color: var(--gray); margin: 1rem 0 2rem;">
                Your vote has been recorded and secured with blockchain-style hash chaining.
            </p>

            <div class="alert alert-info" style="text-align: left; max-width: 500px; margin: 0 auto;">
                <strong>Vote Details:</strong><br>
                <small>
                    • Voter ID: <?= $_SESSION['user_id'] ?><br>
                    • Status: Verified & Recorded<br>
                    • Face Verification: ✓ Passed<br>
                    • Hash Chain: Intact ✓
                </small>
            </div>

            <div style="margin-top: 2rem;">
                <p style="color: var(--gray);">You may now safely logout.</p>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>

    <?php elseif (!$faceVerified): ?>
        <!-- Face Verification Required -->
        <div class="card-header">
            <h2>👤 Face Verification Required</h2>
            <p>Please verify your identity before casting your vote</p>
        </div>

        <div class="alert alert-warning">
            <strong>⚠️ Security Check:</strong> Your face must match the photo taken during registration.
        </div>

        <form method="POST" action="">
            <input type="hidden" name="verify_face" value="1">

            <div class="webcam-container" style="max-width: 400px; margin: 0 auto;">
                <video id="webcamVideo" class="webcam-preview" autoplay playsinline style="display: none;"></video>
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

            <div style="text-align: center; margin-top: 1.5rem;">
                <button type="submit" id="verifyBtn" class="btn btn-success" disabled>
                    ✓ Verify My Identity
                </button>
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
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: { width: 320, height: 240, facingMode: 'user' }
                        });
                        video.srcObject = stream;
                        video.play();
                        video.style.display = 'block';
                        startBtn.classList.add('hidden');
                        captureBtn.classList.remove('hidden');
                    } catch (err) {
                        alert('Could not access webcam. Please allow camera permissions.');
                    }
                });

                captureBtn.addEventListener('click', () => {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);

                    const imageData = canvas.toDataURL('image/jpeg', 0.8);
                    faceDataInput.value = imageData;

                    previewContainer.innerHTML = `
                <img src="${imageData}" alt="Captured face" style="max-width: 200px; border-radius: 8px; margin: 1rem auto; display: block;">
                <p style="color: #10b981; text-align: center;">✓ Face captured!</p>
            `;

                    stream.getTracks().forEach(track => track.stop());
                    video.style.display = 'none';
                    captureBtn.classList.add('hidden');
                    verifyBtn.disabled = false;
                });
            });
        </script>

    <?php else: ?>
        <!-- Voting Form (after face verification) -->
        <div class="card-header">
            <h2>🗳️ Cast Your Vote</h2>
            <p>Select your preferred candidate below. You can only vote once.</p>
        </div>

        <div class="alert alert-success">
            <strong>✓ Face Verified!</strong> Your identity has been confirmed. You may now cast your vote.
        </div>

        <div class="alert alert-warning">
            <strong>⚠️ Important:</strong> Your vote is final and cannot be changed.
            Please review your selection carefully before submitting.
        </div>

        <form id="voteForm" method="POST" action="">
            <input type="hidden" id="selectedCandidate" name="candidate_id" value="">

            <div class="candidates-grid">
                <?php
                $colors = ['#6366f1', '#ec4899', '#10b981', '#f59e0b'];
                $index = 0;
                foreach ($candidates as $candidate):
                    $color = $colors[$index % count($colors)];
                    $initial = strtoupper(substr($candidate['name'], 0, 1));
                    ?>
                    <div class="candidate-card" data-candidate-id="<?= $candidate['id'] ?>">
                        <div class="candidate-avatar" style="background: <?= $color ?>;">
                            <?= $initial ?>
                        </div>
                        <div class="candidate-name"><?= htmlspecialchars($candidate['name']) ?></div>
                        <div class="candidate-party"><?= htmlspecialchars($candidate['party']) ?></div>
                    </div>
                    <?php
                    $index++;
                endforeach;
                ?>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" id="submitVote" class="btn btn-success" disabled
                    style="font-size: 1.1rem; padding: 1rem 3rem;">
                    ✓ Confirm & Cast Vote
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>