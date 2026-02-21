<?php
/**
 * Tamper Demo - For Presentation/Viva
 * Demonstrates how the hash chain detects tampering
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tamper') {
        // Simulate tampering - change a random vote's candidate
        $randomFunc = ($dbConnection === 'pgsql') ? 'RANDOM()' : 'RAND()';
        $stmt = $pdo->query("SELECT id, candidate_id FROM votes ORDER BY $randomFunc LIMIT 1");
        $vote = $stmt->fetch();

        if ($vote) {
            // Get a different candidate
            $stmt = $pdo->prepare("SELECT id FROM candidates WHERE id != ? LIMIT 1");
            $stmt->execute([$vote['candidate_id']]);
            $newCandidate = $stmt->fetch();

            if ($newCandidate) {
                // Tamper the vote (change candidate without updating hash)
                $stmt = $pdo->prepare("UPDATE votes SET candidate_id = ? WHERE id = ?");
                $stmt->execute([$newCandidate['id'], $vote['id']]);

                $message = "🔴 TAMPERING SIMULATED! Vote #{$vote['id']} was changed. The hash chain is now BROKEN!";
                $messageType = 'error';
            }
        } else {
            $message = "No votes to tamper. Cast some votes first!";
            $messageType = 'warning';
        }
    }

    if ($action === 'restore') {
        // Reset - drop all votes and let users vote again
        $pdo->exec("DELETE FROM votes");
        $stmtReset = $pdo->prepare("UPDATE users SET has_voted = FALSE");
        $stmtReset->execute();
        $message = "✅ All votes cleared. System restored to clean state.";
        $messageType = 'success';
    }
}

// Check current hash chain status
$chainValid = verifyHashChain($pdo);
$voteCount = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tamper Demo - VoteUnity Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .demo-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .demo-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .demo-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #fff;
        }

        .status-box {
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            margin: 1.5rem 0;
        }

        .status-valid {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
            border: 2px solid #10b981;
        }

        .status-invalid {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
            border: 2px solid #ef4444;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .status-icon {
            font-size: 4rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .demo-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .explanation {
            background: rgba(99, 102, 241, 0.1);
            border-left: 4px solid #6366f1;
            padding: 1rem;
            margin: 1.5rem 0;
            border-radius: 0 8px 8px 0;
        }

        .step-list {
            list-style: none;
            padding: 0;
        }

        .step-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .step-number {
            background: #6366f1;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity - Tamper Demo</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_votes.php">View Votes</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="demo-container">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="demo-card">
                <h2 class="demo-title">📊 Current System Status</h2>

                <div class="status-box <?= $chainValid ? 'status-valid' : 'status-invalid' ?>">
                    <span class="status-icon">
                        <?= $chainValid ? '✅' : '🚨' ?>
                    </span>
                    <h3 style="font-size: 1.5rem; margin: 0;">
                        <?= $chainValid ? 'HASH CHAIN INTACT' : 'TAMPERING DETECTED!' ?>
                    </h3>
                    <p style="color: var(--gray); margin-top: 0.5rem;">
                        <?= $voteCount ?> votes recorded
                    </p>
                </div>

                <?php if (!$chainValid): ?>
                    <div class="alert alert-error">
                        <strong>⚠️ Security Alert:</strong> The hash chain verification failed.
                        One or more votes have been modified outside the system.
                        This proves that tampering CAN be detected!
                    </div>
                <?php endif; ?>
            </div>

            <div class="demo-card">
                <h2 class="demo-title">🎭 Demo Controls (For Viva)</h2>

                <div class="explanation">
                    <strong>How to demonstrate tamper detection:</strong>
                    <ol class="step-list">
                        <li>
                            <span class="step-number">1</span>
                            <span>First, cast some votes (register users and vote)</span>
                        </li>
                        <li>
                            <span class="step-number">2</span>
                            <span>Check the status above - should show "HASH CHAIN INTACT"</span>
                        </li>
                        <li>
                            <span class="step-number">3</span>
                            <span>Click "Simulate Tampering" button below</span>
                        </li>
                        <li>
                            <span class="step-number">4</span>
                            <span>Status changes to "TAMPERING DETECTED!" - proving the system works!</span>
                        </li>
                    </ol>
                </div>

                <div class="demo-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="tamper">
                        <button type="submit" class="btn btn-danger" <?= $voteCount == 0 ? 'disabled' : '' ?>>
                            🔴 Simulate Tampering
                        </button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="restore">
                        <button type="submit" class="btn btn-secondary">
                            🔄 Reset All Votes
                        </button>
                    </form>

                    <a href="view_votes.php" class="btn btn-primary">
                        📋 View Hash Chain Details
                    </a>
                </div>
            </div>

            <div class="demo-card">
                <h2 class="demo-title">💡 Viva Talking Points</h2>

                <div class="explanation">
                    <p><strong>Q: How does the hash chain work?</strong></p>
                    <p>Each vote's hash = SHA256(user_id + candidate_id + timestamp + previous_vote_hash).
                        This creates a chain where each vote depends on all previous votes.</p>
                </div>

                <div class="explanation">
                    <p><strong>Q: Why does tampering break the chain?</strong></p>
                    <p>If you change any vote's data, the stored hash no longer matches the recalculated hash.
                        Plus, all subsequent votes' hashes also become invalid because they included the old hash.</p>
                </div>

                <div class="explanation">
                    <p><strong>Q: Is this real blockchain?</strong></p>
                    <p>No, this is a <em>blockchain-style hash chain</em> for demonstration.
                        Real blockchain would be distributed across multiple servers with consensus mechanisms.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel -