<?php
/**
 * TEMPORARY: Reset Admin Password
 * Visit /pages/admin/fix_admin.php on your browser to fix login.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Cleanest way to reset the admin credentials
$username = 'lavya';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE admins SET password = ?, is_super_admin = TRUE, is_approved = TRUE WHERE username = ?");
        $stmt->execute([$hash, $username]);
        echo "<h1>✅ Admin 'lavya' password has been reset to 'admin123'</h1>";
    } else {
        // Create new
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, is_super_admin, is_approved) VALUES (?, ?, TRUE, TRUE)");
        $stmt->execute([$username, $hash]);
        echo "<h1>✅ Admin 'lavya' created with password 'admin123'</h1>";
    }
    echo "<p>You can now go to <a href='login.php'>Login Page</a> and use these credentials.</p>";
} catch (PDOException $e) {
    echo "<h1>❌ Error: " . $e->getMessage() . "</h1>";
    echo "<p>Make sure your Neon environment variables are set in Vercel.</p>";
}
?>