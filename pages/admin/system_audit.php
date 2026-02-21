<?php
/**
 * System Audit Page
 * Master database viewer for Super-Admins only
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

// Guard: require DB
requireDb($pdo, $db_error ?? null);

// Check if current user is Super Admin
$stmt = $pdo->prepare("SELECT is_super_admin FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$isAdmin = $stmt->fetch();

if (!$isAdmin || !$isAdmin['is_super_admin']) {
    setFlashMessage('error', 'Access Denied: Super-Admin privileges required.');
    redirect(BASE_URL . '/pages/admin/dashboard.php');
}

// Fetch all data
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$candidates = $pdo->query("SELECT * FROM candidates ORDER BY id ASC")->fetchAll();
$votes = $pdo->query("
    SELECT v.*, u.name as voter_name, c.name as candidate_name 
    FROM votes v 
    JOIN users u ON v.user_id = u.id 
    JOIN candidates c ON v.candidate_id = c.id 
    ORDER BY v.timestamp DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Audit - VoteUnity Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .audit-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1rem;
        }

        .audit-tab {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray);
            transition: all 0.3s ease;
        }

        .audit-tab.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }

        .audit-section {
            display: none;
        }

        .audit-section.active {
            display: block;
        }

        .avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .code-block {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85rem;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🛡️</span>
            <span class="nav-title">VoteUnity Master Audit</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_votes.php">Vote Audit</a>
            <a href="location_tracker.php">📍 Locations</a>
            <a href="system_audit.php" class="active" style="color: #a855f7;">🔍 System Audit</a>
            <a href="manage_admins.php">👥 Admins</a>
            <a href="tamper_demo.php" style="color: #f59e0b;">🎭 Demo</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="admin-header">
            <div>
                <h1>🔍 Master System Audit</h1>
                <p style="color: var(--gray);">Direct Database Access - Super-Admin Level</p>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-super" style="background: #8b5cf6; color: white; padding: 0.5rem 1rem; border-radius: 20px;">👑 Super Admin: lavya</span>
            </div>
        </div>

        <div class="audit-tabs">
            <div class="audit-tab active" onclick="showSection('users')">👥 Registered Voters (<?= count($users) ?>)</div>
            <div class="audit-tab" onclick="showSection('candidates')">🗳️ Candidates (<?= count($candidates) ?>)</div>
            <div class="audit-tab" onclick="showSection('votes')">🔗 Hash-Chain Votes (<?= count($votes) ?>)</div>
        </div>

        <!-- Users Table -->
        <section id="section-users" class="audit-section active">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Aadhaar</th>
                                <th>Registration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?php if ($user['face_image']): ?>
                                            <img src="<?= str_starts_with($user['face_image'], 'data:') ? $user['face_image'] : BASE_URL . '/uploads/' . $user['face_image'] ?>" class="avatar-small">
                                        <?php else: ?>
                                            <div class="avatar-small" style="background: #333; display: flex; align-items: center; justify-content: center;">👤</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($user['name']) ?></strong><br><small><?= htmlspecialchars($user['email']) ?></small></td>
                                    <td><code><?= htmlspecialchars($user['aadhaar_number']) ?></code></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <span class="badge" style="background: <?= $user['has_voted'] ? '#10b981' : '#3b82f6' ?>; color: white;">
                                            <?= $user['has_voted'] ? 'Voted ✓' : 'Eligible' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Candidates Table -->
        <section id="section-candidates" class="audit-section">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th>Symbol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td>#<?= $candidate['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($candidate['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($candidate['party']) ?></td>
                                    <td style="font-size: 1.5rem;"><?= htmlspecialchars($candidate['symbol']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Votes Table -->
        <section id="section-votes" class="audit-section">
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Voter</th>
                                <th>Selection</th>
                                <th>Vote Hash (Audit)</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($votes as $vote): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vote['voter_name']) ?></td>
                                    <td><span class="badge" style="background: rgba(255,255,255,0.1);"><?= htmlspecialchars($vote['candidate_name']) ?></span></td>
                                    <td><div class="code-block" title="<?= $vote['vote_hash'] ?>"><?= $vote['vote_hash'] ?></div></td>
                                    <td><?= date('M j, g:i A', strtotime($vote['timestamp'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <script>
        function showSection(section) {
            // Update tabs
            document.querySelectorAll('.audit-tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            // Update sections
            document.querySelectorAll('.audit-section').forEach(sec => sec.classList.remove('active'));
            document.getElementById('section-' + section).classList.add('active');
        }
    </script>
</body>

</html>
