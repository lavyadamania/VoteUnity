<?php
require_once __DIR__ . '/../../config/database.php';
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
header('Location: ' . BASE_URL . '/pages/admin/login.php');
exit;
?>