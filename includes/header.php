<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Guard: if DB connection failed, show a clear error instead of a fatal crash
if ($pdo === null) {
    $dbErrMsg = $db_error ?? 'Database not configured';
    $isVercelConfig = getenv('VERCEL') || getenv('VERCEL_URL');
    $setupMsg = $isVercelConfig
        ? 'Please set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, and DB_CONNECTION=pgsql in your Vercel environment variables, then redeploy.'
        : 'Please check your local database config (XAMPP running? Correct port?)';
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>VoteUnity — Database Error</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                background: #0f172a;
                color: #e2e8f0;
                font-family: Inter, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }

            .card {
                background: #1e293b;
                border: 1px solid #ef4444;
                border-radius: 16px;
                padding: 2.5rem;
                max-width: 560px;
                width: 100%;
                text-align: center;
            }

            .icon {
                font-size: 3rem;
                margin-bottom: 1rem;
            }

            h1 {
                font-size: 1.5rem;
                color: #ef4444;
                margin-bottom: 0.75rem;
            }

            .msg {
                color: #94a3b8;
                line-height: 1.6;
                margin-bottom: 1.5rem;
            }

            .setup {
                background: #0f172a;
                border-radius: 8px;
                padding: 1rem;
                font-size: 0.85rem;
                color: #fbbf24;
                text-align: left;
                line-height: 1.6;
            }

            code {
                background: #1e293b;
                padding: 0.15rem 0.4rem;
                border-radius: 4px;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <div class="icon">🔌</div>
            <h1>Database Not Connected</h1>
            <p class="msg"><?= htmlspecialchars($dbErrMsg) ?></p>
            <div class="setup"><?= htmlspecialchars($setupMsg) ?></div>
        </div>
    </body>

    </html>
    <?php
    exit;
}
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