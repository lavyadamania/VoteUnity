<?php
/**
 * Audit Logs Viewer - Admin Panel
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

requireDb($pdo, $db_error ?? null);

// Get current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

// Filters
$filterType = $_GET['type'] ?? '';
$filterActor = $_GET['actor'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 25;

$filters = [
    'event_type' => $filterType,
    'actor_type' => $filterActor,
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage
];

$logs = getAuditLogs($pdo, $filters);
$totalLogs = getAuditLogCount($pdo, $filters);
$totalPages = ceil($totalLogs / $perPage);

// Event type colors
$eventColors = [
    'LOGIN' => '#10b981',
    'LOGIN_FAIL' => '#ef4444',
    'LOGOUT' => '#6b7280',
    'VOTE_CAST' => '#6366f1',
    'VOTE_TAMPER' => '#ef4444',
    'ADMIN_ACTION' => '#f59e0b',
    'CHAIN_VERIFY' => '#8b5cf6',
    'REGISTER' => '#3b82f6',
    'FACE_VERIFY' => '#ec4899',
    'SYSTEM' => '#94a3b8',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - VoteUnity Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-family: inherit;
        }

        .event-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .actor-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .pagination a {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .pagination a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .pagination .current {
            background: #6366f1;
            color: white;
        }

        .log-details {
            max-width: 300px;
            word-break: break-word;
            font-size: 0.85rem;
            color: var(--gray);
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
            <a href="view_votes.php">Vote Audit</a>
            <a href="location_tracker.php">📍 Locations</a>
            <?php if (isset($currentAdmin) && $currentAdmin['is_super_admin']): ?>
                <a href="system_audit.php" style="color: #a855f7;">🔍 System Audit</a>
            <?php endif; ?>
            <a href="manage_admins.php">👥 Admins</a>
            <a href="audit_logs.php" class="active" style="color: #10b981;">📋 Audit Logs</a>
            <a href="tamper_demo.php" style="color: #f59e0b;">🎭 Demo</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <div class="admin-header">
            <div>
                <h1>📋 Audit Logs</h1>
                <p style="color: var(--gray);">Security event trail —
                    <?= number_format($totalLogs) ?> events recorded
                </p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-bar">
            <select name="type" onchange="this.form.submit()">
                <option value="">All Event Types</option>
                <?php foreach ($eventColors as $type => $color): ?>
                    <option value="<?= $type ?>" <?= $filterType === $type ? 'selected' : '' ?>>
                        <?= $type ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="actor" onchange="this.form.submit()">
                <option value="">All Actors</option>
                <option value="voter" <?= $filterActor === 'voter' ? 'selected' : '' ?>>Voter</option>
                <option value="admin" <?= $filterActor === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="system" <?= $filterActor === 'system' ? 'selected' : '' ?>>System</option>
            </select>
            <?php if ($filterType || $filterActor): ?>
                <a href="audit_logs.php" class="btn btn-secondary" style="font-size: 0.85rem;">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Logs Table -->
        <div class="card">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">No audit logs found matching the current filters.</div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Event</th>
                                <th>Actor</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small>
                                            <?= date('M j, g:i:s A', strtotime($log['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="event-badge"
                                            style="background: <?= $eventColors[$log['event_type']] ?? '#94a3b8' ?>; color: white;">
                                            <?= htmlspecialchars($log['event_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="actor-badge">
                                            <?= $log['actor_type'] === 'voter' ? '👤' : ($log['actor_type'] === 'admin' ? '🔑' : '⚙️') ?>
                                            <?= htmlspecialchars($log['actor_type']) ?>
                                            <?= $log['actor_id'] ? '#' . $log['actor_id'] : '' ?>
                                        </span>
                                    </td>
                                    <td class="log-details">
                                        <?= htmlspecialchars($log['details'] ?? '') ?>
                                    </td>
                                    <td><code
                                            style="font-size: 0.8rem;"><?= htmlspecialchars($log['ip_address'] ?? '') ?></code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a
                                href="?page=<?= $page - 1 ?>&type=<?= urlencode($filterType) ?>&actor=<?= urlencode($filterActor) ?>">←
                                Prev</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="current">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>&type=<?= urlencode($filterType) ?>&actor=<?= urlencode($filterActor) ?>">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a
                                href="?page=<?= $page + 1 ?>&type=<?= urlencode($filterType) ?>&actor=<?= urlencode($filterActor) ?>">Next
                                →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel — Audit Trail</p>
        </div>
    </footer>
</body>

</html>