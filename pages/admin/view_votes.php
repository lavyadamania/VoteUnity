<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

// Get all votes with chain information
$votes = $pdo->query("
    SELECT v.*, u.name as voter_name, u.aadhaar_number, c.name as candidate_name, c.party
    FROM votes v 
    JOIN users u ON v.user_id = u.id 
    JOIN candidates c ON v.candidate_id = c.id 
    ORDER BY v.id ASC
")->fetchAll();

// Check overall chain integrity
$chainValid = verifyHashChain($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Audit - VoteUnity Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .chain-link {
            text-align: center;
            color: var(--gray);
            padding: 0.5rem 0;
        }

        .chain-link::after {
            content: '↓';
            display: block;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .genesis-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--gradient-primary);
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_votes.php" class="active">Vote Audit</a>
            <a href="location_tracker.php">📍 Locations</a>
            <?php
            $stmt = $pdo->prepare("SELECT is_super_admin FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            if ($stmt->fetchColumn()): ?>
                <a href="system_audit.php" style="color: #a855f7;">🔍 System Audit</a>
            <?php endif; ?>
            <a href="manage_admins.php">👥 Admins</a>
            <a href="tamper_demo.php" style="color: #f59e0b;">🎭 Demo</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="admin-header">
            <div>
                <h1>🔗 Blockchain-Style Vote Audit</h1>
                <p style="color: var(--gray);">Verify the integrity of all recorded votes</p>
            </div>
            <div>
                <span class="<?= $chainValid ? 'chain-valid' : 'chain-invalid' ?>"
                    style="font-weight: 600; font-size: 1.1rem;">
                    <?= $chainValid ? '✓ Chain Integrity: VALID' : '✗ Chain Integrity: BROKEN!' ?>
                </span>
            </div>
        </div>

        <?php if ($chainValid): ?>
            <div class="alert alert-success">
                <strong>✓ All hashes verified!</strong> The vote chain is intact. No tampering detected.
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                <strong>⚠️ Warning:</strong> Hash chain integrity check failed. Possible data tampering detected.
            </div>
        <?php endif; ?>

        <!-- Explanation -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">📚 How Hash Chaining Works</h3>
            <p style="color: var(--gray); line-height: 1.8;">
                Each vote is secured using a cryptographic hash that includes the previous vote's hash, creating an
                unbreakable chain. If anyone attempts to modify a vote, the chain breaks, and the tampering is detected.
                <br><br>
                <strong>Hash Formula:</strong> <code>SHA256(user_id + candidate_id + timestamp + previous_hash)</code>
            </p>
        </div>

        <?php if (empty($votes)): ?>
            <div class="card">
                <div class="alert alert-info">No votes have been recorded yet.</div>
            </div>
        <?php else: ?>

            <!-- Genesis Block -->
            <div class="card"
                style="text-align: center; margin-bottom: 0; border-radius: var(--border-radius) var(--border-radius) 0 0;">
                <span class="genesis-badge">GENESIS BLOCK</span>
                <p style="margin-top: 0.5rem; color: var(--gray);">Previous Hash: <code>GENESIS</code></p>
            </div>

            <!-- Vote Chain -->
            <?php
            $previousHash = 'GENESIS';
            foreach ($votes as $index => $vote):
                $isFirst = $index === 0;
                $isLast = $index === count($votes) - 1;
                $linkValid = $vote['previous_hash'] === $previousHash;
                ?>

                <div class="chain-link"></div>

                <div class="card"
                    style="margin-bottom: 0; border-radius: <?= $isLast ? '0 0 var(--border-radius) var(--border-radius)' : '0' ?>;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h4 style="color: var(--primary-light);">Vote #
                                <?= $vote['id'] ?>
                            </h4>
                            <p style="color: var(--gray); margin: 0.5rem 0;">
                                <strong>Voter:</strong>
                                <?= htmlspecialchars($vote['voter_name']) ?>
                                (Aadhaar:
                                <?= substr($vote['aadhaar_number'], 0, 4) ?>****
                                <?= substr($vote['aadhaar_number'], -4) ?>)
                            </p>
                            <p style="color: var(--gray); margin: 0.5rem 0;">
                                <strong>Candidate:</strong>
                                <?= htmlspecialchars($vote['candidate_name']) ?>
                                (
                                <?= htmlspecialchars($vote['party']) ?>)
                            </p>
                            <p style="color: var(--gray); margin: 0.5rem 0;">
                                <strong>Time:</strong>
                                <?= date('M j, Y g:i:s A', strtotime($vote['timestamp'])) ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <span class="<?= $linkValid ? 'chain-valid' : 'chain-invalid' ?>">
                                <?= $linkValid ? '✓ Valid Link' : '✗ Broken Link' ?>
                            </span>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                        <div style="display: grid; gap: 0.5rem;">
                            <div>
                                <small style="color: var(--gray);">Previous Hash:</small>
                                <div class="hash-cell" style="max-width: none;">
                                    <?= $vote['previous_hash'] ?>
                                </div>
                            </div>
                            <div>
                                <small style="color: var(--gray);">Vote Hash:</small>
                                <div class="hash-cell" style="max-width: none; color: var(--success);">
                                    <?= $vote['vote_hash'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $previousHash = $vote['vote_hash'];
            endforeach;
            ?>

        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel -