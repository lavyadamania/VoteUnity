<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Require admin login
if (!isAdminLoggedIn()) {
    redirect('/voting/pages/admin/login.php');
}

// Get statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVoted = $pdo->query("SELECT COUNT(*) FROM users WHERE has_voted = 1")->fetchColumn();
$totalVotes = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();

// Get vote distribution
$voteDistribution = $pdo->query("
    SELECT c.name, c.party, COUNT(v.id) as vote_count 
    FROM candidates c 
    LEFT JOIN votes v ON c.id = v.candidate_id 
    GROUP BY c.id 
    ORDER BY vote_count DESC
")->fetchAll();

// Check hash chain integrity
$chainValid = verifyHashChain($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VoteUnity</title>
    <link rel="stylesheet" href="/voting/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="view_votes.php">Vote Audit</a>
            <a href="location_tracker.php">📍 Locations</a>
            <a href="manage_admins.php">👥 Admins</a>
            <a href="tamper_demo.php" style="color: #f59e0b;">🎭 Demo</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="admin-header">
            <div>
                <h1>📊 Election Dashboard</h1>
                <p style="color: var(--gray);">Welcome,
                    <?= htmlspecialchars($_SESSION['admin_username']) ?>
                </p>
            </div>
            <div>
                <span class="<?= $chainValid ? 'chain-valid' : 'chain-invalid' ?>" style="font-weight: 600;">
                    <?= $chainValid ? '🔗 Hash Chain: Intact ✓' : '⚠️ Hash Chain: Broken!' ?>
                </span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">
                    <?= $totalUsers ?>
                </div>
                <div class="stat-label">Registered Voters</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= $totalVoted ?>
                </div>
                <div class="stat-label">Votes Cast</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= $totalUsers > 0 ? round(($totalVoted / $totalUsers) * 100, 1) : 0 ?>%
                </div>
                <div class="stat-label">Voter Turnout</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= $totalCandidates ?>
                </div>
                <div class="stat-label">Candidates</div>
            </div>
        </div>

        <!-- Vote Distribution -->
        <div class="card">
            <h2 style="margin-bottom: 1.5rem;">📈 Vote Distribution</h2>

            <?php if (empty($voteDistribution) || $totalVotes == 0): ?>
                <div class="alert alert-info">No votes have been cast yet.</div>
            <?php else: ?>
                <div style="display: grid; gap: 1rem;">
                    <?php foreach ($voteDistribution as $candidate):
                        $percentage = $totalVotes > 0 ? ($candidate['vote_count'] / $totalVotes) * 100 : 0;
                        ?>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 150px; flex-shrink: 0;">
                                <strong>
                                    <?= htmlspecialchars($candidate['name']) ?>
                                </strong>
                                <br><small style="color: var(--gray);">
                                    <?= htmlspecialchars($candidate['party']) ?>
                                </small>
                            </div>
                            <div
                                style="flex: 1; background: rgba(255,255,255,0.1); height: 30px; border-radius: 4px; overflow: hidden;">
                                <div
                                    style="width: <?= $percentage ?>%; height: 100%; background: var(--gradient-primary); transition: width 0.5s;">
                                </div>
                            </div>
                            <div style="width: 100px; text-align: right;">
                                <strong>
                                    <?= $candidate['vote_count'] ?>
                                </strong> votes
                                <br><small style="color: var(--gray);">
                                    <?= round($percentage, 1) ?>%
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card" style="margin-top: 1.5rem;">
            <h2 style="margin-bottom: 1.5rem;">🕐 Recent Votes</h2>

            <?php
            $recentVotes = $pdo->query("
                SELECT v.*, u.name as voter_name, c.name as candidate_name 
                FROM votes v 
                JOIN users u ON v.user_id = u.id 
                JOIN candidates c ON v.candidate_id = c.id 
                ORDER BY v.timestamp DESC 
                LIMIT 5
            ")->fetchAll();
            ?>

            <?php if (empty($recentVotes)): ?>
                <div class="alert alert-info">No votes recorded yet.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Voter</th>
                                <th>Candidate</th>
                                <th>Hash (partial)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVotes as $vote): ?>
                                <tr>
                                    <td>
                                        <?= date('M j, g:i A', strtotime($vote['timestamp'])) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($vote['voter_name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($vote['candidate_name']) ?>
                                    </td>
                                    <td class="hash-cell">
                                        <?= substr($vote['vote_hash'], 0, 16) ?>...
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem; text-align: right;">
                    <a href="view_votes.php" class="btn btn-secondary">View All Votes →</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel - 