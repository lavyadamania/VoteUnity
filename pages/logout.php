<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Audit log before destroying session
if (isset($_SESSION['user_id']) && $pdo) {
    logAuditEvent($pdo, AUDIT_LOGOUT, ACTOR_VOTER, $_SESSION['user_id'], 'Voter logged out');
}

// Clear JWT cookie
clearJWTCookie();

session_destroy();
header('Location: ' . BASE_URL . '/index.php');
exit;
?>