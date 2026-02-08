<?php
/**
 * Admin Registration Page
 * New admins register here, then wait for Super Admin approval
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 4) {
        $errors[] = 'Password must be at least 4 characters';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }

    // Check if username exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO admins (username, password, is_approved, can_view_votes) VALUES (?, ?, 0, 1)");
        $stmt->execute([$username, $hashedPassword]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
        <div class="nav-links">
            <a href="login.php">Login</a>
            <a href="<?= BASE_URL ?>/">← Back to Main</a>
        </div>
    </nav>

    <main class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="card-header">
                    <h2>👤 Admin Registration</h2>
                    <p>Register as a new admin</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <strong>✅ Registration successful!</strong><br>
                        Your account is pending approval from the Super Admin.<br>
                        You will be able to login once approved.
                    </div>
                    <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
                <?php else: ?>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <div>•
                                    <?= $error ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                placeholder="Choose a username" required
                                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control"
                                placeholder="Choose a password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                placeholder="Confirm your password" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Register as Admin
                        </button>
                    </form>

                    <div class="alert alert-info" style="margin-top: 1.5rem;">
                        <strong>Note:</strong> After registration, your account will need approval from the Super Admin
                        before you can login.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel -