<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="VoteUnity - Secure Online Voting System with Face Verification">
    <title>VoteUnity - Secure Online Voting</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🗳️</text></svg>">
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="<?= BASE_URL ?>/">
                <span class="nav-icon">🗳️</span>
                <span class="nav-title">VoteUnity</span>
            </a>
        </div>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/pages/vote.php" class="<?= $currentPage === 'vote' ? 'active' : '' ?>">🗳️ Vote</a>
                <a href="<?= BASE_URL ?>/pages/logout.php">🚪 Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/login.php" class="<?= $currentPage === 'login' ? 'active' : '' ?>">🔑
                    Login</a>
                <a href="<?= BASE_URL ?>/pages/register.php" class="<?= $currentPage === 'register' ? 'active' : '' ?>">📝
                    Register</a>
                <a href="<?= BASE_URL ?>/pages/admin/login.php" style="color: #f6ad55;">🔐 Admin</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <?php
        $flash = getFlashMessage();
        if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>