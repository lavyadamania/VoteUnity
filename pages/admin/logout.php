<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Audit log before destroying session
if (isset($_SESSION['admin_id']) && $pdo) {
    logAuditEvent($pdo, AUDIT_LOGOUT, ACTOR_ADMIN, $_SESSION['admin_id'], 'Admin logged out: ' . ($_SESSION['admin_username'] ?? 'unknown'));
}

// Clear JWT cookie
clearJWTCookie();

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
header('Location: ' . BASE_URL . '/pages/admin/login.php');
exit;
?>