<?php
require_once 'includes/header.php';
?>

<div class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <span>🚀</span> Next-Gen Voting Platform
        </div>
        <h1>Secure Digital <span>Democracy</span></h1>
        <p>Experience the future of voting with blockchain-style verification, biometric authentication, and
            tamper-proof audit trails.</p>

        <?php if (!isLoggedIn()): ?>
            <div class="hero-buttons">
                <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary">
                    <span>🗳️</span> Get Started
                </a>
                <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-outline">
                    <span>→</span> Sign In
                </a>
            </div>
        <?php else: ?>
            <div class="hero-buttons">
                <a href="pages/vote.php" class="btn btn-primary btn-lg">
                    <span>✓</span> Cast Your Vote
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="section-title">
        <h2>Why VoteUnity?</h2>
        <p>Built with cutting-edge security features to ensure every vote counts</p>
    </div>

    <div class="features">
        <div class="feature-card">
            <div class="feature-icon">🆔</div>
            <h3>Aadhaar Verification</h3>
            <p>Unique identity verification ensures one person, one vote. No duplicates.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">👤</div>
            <h3>Face Recognition</h3>
            <p>Advanced biometric authentication using real-time webcam verification.</p>
            <?php
            $faceMethod = detectFaceRecognitionMethod();
            $faceInfo = getFaceMethodInfo($faceMethod);
            ?>
            <div style="margin-top: 1rem;">
                <span class="face-method-badge" style="color: <?= $faceInfo['color'] ?>; border-color: <?= $faceInfo['color'] ?>33; background: <?= $faceInfo['color'] ?>15;">
                    <span class="method-icon"><?= $faceInfo['icon'] ?></span>
                    <?= htmlspecialchars($faceInfo['label']) ?>
                    <span class="method-tier"><?= htmlspecialchars($faceInfo['tier']) ?></span>
                </span>
            </div>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔗</div>
            <h3>Hash Chain Audit</h3>
            <p>Blockchain-inspired cryptographic hashing creates an immutable audit trail.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Secure Sessions</h3>
            <p>Enterprise-grade session management protects your voting journey.</p>
        </div>
    </div>

    <div class="glass" style="padding: 3rem; margin-top: 3rem;">
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; text-align: center;">
            <div>
                <h3 style="color: var(--primary); margin-bottom: 1rem;">🛠️ Technologies</h3>
                <div class="tech-list">
                    <span>PHP 8+</span>
                    <span>MySQL</span>
                    <span>JavaScript</span>
                    <span>Python</span>
                </div>
            </div>
            <div>
                <h3 style="color: var(--primary); margin-bottom: 1rem;">🛡️ Security</h3>
                <div class="security-list">
                    <span>Hash Chaining</span>
                    <span>Face Auth</span>
                    <span>Location Tracking</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>