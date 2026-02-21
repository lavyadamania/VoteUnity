<?php
/**
 * Admin Management Page
 * Super Admin can approve/reject admins and set their permissions
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

// Get current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

// Check if super admin or has manage_admins permission
$canManage = $currentAdmin['is_super_admin'] || $currentAdmin['can_manage_admins'];

if (!$canManage) {
    setFlashMessage('error', 'You do not have permission to manage admins.');
    redirect(BASE_URL . '/pages/admin/dashboard.php');
}

$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminId = intval($_POST['admin_id'] ?? 0);

    if ($adminId > 0 && $adminId !== $currentAdmin['id']) {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE admins SET is_approved = 1, approved_by = ? WHERE id = ?");
                $stmt->execute([$currentAdmin['id'], $adminId]);
                $message = "Admin approved successfully!";
                $messageType = 'success';
                break;

            case 'reject':
                $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND is_super_admin = 0");
                $stmt->execute([$adminId]);
                $message = "Admin rejected and removed.";
                $messageType = 'success';
                break;

            case 'revoke':
                $stmt = $pdo->prepare("UPDATE admins SET is_approved = 0 WHERE id = ? AND is_super_admin = 0");
                $stmt->execute([$adminId]);
                $message = "Admin access revoked.";
                $messageType = 'success';
                break;

            case 'update_permissions':
                $canViewVotes = isset($_POST['can_view_votes']) ? 1 : 0;
                $canManageCandidates = isset($_POST['can_manage_candidates']) ? 1 : 0;
                $canResetVotes = isset($_POST['can_reset_votes']) ? 1 : 0;
                $canManageAdmins = isset($_POST['can_manage_admins']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE admins SET 
                    can_view_votes = ?, 
                    can_manage_candidates = ?, 
                    can_reset_votes = ?, 
                    can_manage_admins = ? 
                    WHERE id = ? AND is_super_admin = 0");
                $stmt->execute([$canViewVotes, $canManageCandidates, $canResetVotes, $canManageAdmins, $adminId]);
                $message = "Permissions updated successfully!";
                $messageType = 'success';
                break;
        }
    }
}

// Get all admins
$admins = $pdo->query("SELECT a.*, approver.username as approved_by_name 
    FROM admins a 
    LEFT JOIN admins approver ON a.approved_by = approver.id 
    ORDER BY a.is_super_admin DESC, a.is_approved DESC, a.created_at DESC")->fetchAll();

$pendingCount = 0;
foreach ($admins as $admin) {
    if (!$admin['is_approved'] && !$admin['is_super_admin'])
        $pendingCount++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-card.pending {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }

        .admin-card.super {
            border-color: #8b5cf6;
            background: rgba(139, 92, 246, 0.1);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .admin-name {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-super {
            background: #8b5cf6;
            color: white;
        }

        .badge-approved {
            background: #10b981;
            color: white;
        }

        .badge-pending {
            background: #f59e0b;
            color: black;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        .permission-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .section-title {
            font-size: 1.5rem;
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
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
            <a href="tamper_demo.php">🎭 Tamper Demo</a>
            <a href="manage_admins.php" class="active">👥 Manage Admins</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1 style="margin-bottom: 0.5rem;">👥 Admin Management</h1>
        <p style="color: var(--gray); margin-bottom: 2rem;">
            Logged in as: <strong>
                <?= htmlspecialchars($currentAdmin['username']) ?>
            </strong>
            <?= $currentAdmin['is_super_admin'] ? '<span class="badge badge-super">Super Admin</span>' : '' ?>
        </p>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($pendingCount > 0): ?>
            <h2 class="section-title">⏳ Pending Approval (
                <?= $pendingCount ?>)
            </h2>
            <?php foreach ($admins as $admin): ?>
                <?php if (!$admin['is_approved'] && !$admin['is_super_admin']): ?>
                    <div class="admin-card pending">
                        <div class="admin-header">
                            <div>
                                <span class="admin-name">
                                    <?= htmlspecialchars($admin['username']) ?>
                                </span>
                                <span class="badge badge-pending">Pending</span>
                            </div>
                            <small style="color: var(--gray);">
                                Registered:
                                <?= date('M j, Y g:i A', strtotime($admin['created_at'])) ?>
                            </small>
                        </div>

                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                <button type="submit" class="btn btn-success">✓ Approve</button>
                            </form>
                            <form method="POST" style="display: inline;"
                                onsubmit="return confirm('Reject and delete this admin?');">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                <button type="submit" class="btn btn-danger">✗ Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="section-title">✅ Active Admins</h2>
        <?php foreach ($admins as $admin): ?>
            <?php if ($admin['is_approved'] || $admin['is_super_admin']): ?>
                <div class="admin-card <?= $admin['is_super_admin'] ? 'super' : '' ?>">
                    <div class="admin-header">
                        <div>
                            <span class="admin-name">
                                <?= htmlspecialchars($admin['username']) ?>
                            </span>
                            <?php if ($admin['is_super_admin']): ?>
                                <span class="badge badge-super">👑 Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-approved">Approved</span>
                            <?php endif; ?>
                        </div>
                        <small style="color: var(--gray);">
                            <?php if ($admin['approved_by_name']): ?>
                                Approved by:
                                <?= htmlspecialchars($admin['approved_by_name']) ?>
                            <?php endif; ?>
                        </small>
                    </div>

                    <?php if (!$admin['is_super_admin']): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_permissions">
                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">

                            <div class="permissions-grid">
                                <label class="permission-item">
                                    <input type="checkbox" name="can_view_votes" <?= $admin['can_view_votes'] ? 'checked' : '' ?>>
                                    <span>📊 View Votes & Dashboard</span>
                                </label>
                                <label class="permission-item">
                                    <input type="checkbox" name="can_manage_candidates" <?= $admin['can_manage_candidates'] ? 'checked' : '' ?>>
                                    <span>👥 Manage Candidates</span>
                                </label>
                                <label class="permission-item">
                                    <input type="checkbox" name="can_reset_votes" <?= $admin['can_reset_votes'] ? 'checked' : '' ?>>
                                    <span>🔄 Reset/Delete Votes</span>
                                </label>
                                <label class="permission-item">
                                    <input type="checkbox" name="can_manage_admins" <?= $admin['can_manage_admins'] ? 'checked' : '' ?>>
                                    <span>⚙️ Manage Other Admins</span>
                                </label>
                            </div>

                            <div class="action-buttons">
                                <button type="submit" class="btn btn-primary">💾 Save Permissions</button>
                                <button type="submit" name="action" value="revoke" class="btn btn-secondary"
                                    onclick="return confirm('Revoke access for this admin?');">
                                    🚫 Revoke Access
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <p style="color: var(--gray); margin-top: 1rem;">
                            <em>Super Admin has all permissions and cannot be modified.</em>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="card" style="margin-top: 2rem; text-align: center;">
            <h3>➕ Add New Admin</h3>
            <p style="color: var(--gray);">Share this link with new admins:</p>
            <code
                style="background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 6px; display: inline-block;">
                <?= 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/pages/admin/register.php' ?>
            </code>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel - 