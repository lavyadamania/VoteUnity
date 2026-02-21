<?php
/**
 * VoteUnity Diagnostic & Repair Tool
 * Visit this page on Vercel to fix common database/login issues.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

function checkTableColumnType($pdo, $table, $column)
{
    try {
        $stmt = $pdo->prepare("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = ? AND column_name = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        return $result ? $result['data_type'] : 'unknown';
    } catch (Exception $e) {
        return 'error: ' . $e->getMessage();
    }
}

echo "<html><body style='font-family:sans-serif; background:#0f172a; color:#e2e8f0; padding:2rem;'>";
echo "<h1>🛠️ System Diagnostic & Repair</h1>";

if (!$pdo) {
    echo "<div style='background:#ef4444; padding:1rem; border-radius:8px;'>";
    echo "<h3>❌ Database Not Connected</h3>";
    echo "<p>$db_error</p>";
    echo "</div>";
    exit;
}

echo "<div style='background:#1e293b; padding:1.5rem; border-radius:12px; border:1px solid #334155; margin-bottom:1rem;'>";
echo "<h3>📊 Database Status: CONNECTED ✅</h3>";

// 1. Check Column Types
$adminFaceType = checkTableColumnType($pdo, 'admins', 'face_image');
$userFaceType = checkTableColumnType($pdo, 'users', 'face_image');

echo "<h4>🔍 Schema Check:</h4>";
echo "<ul>";
echo "<li>admins.face_image: <b style='color:" . ($adminFaceType === 'text' ? '#10b981' : '#fbbf24') . "'>$adminFaceType</b></li>";
echo "<li>users.face_image: <b style='color:" . ($userFaceType === 'text' ? '#10b981' : '#fbbf24') . "'>$userFaceType</b></li>";
echo "</ul>";

if ($adminFaceType !== 'text' || $userFaceType !== 'text') {
    echo "<p style='color:#fbbf24;'>⚠️ Some columns are not correct. Fixing now...</p>";
    $pdo->exec("ALTER TABLE admins ALTER COLUMN face_image TYPE TEXT");
    $pdo->exec("ALTER TABLE users ALTER COLUMN face_image TYPE TEXT");
    echo "<p style='color:#10b981;'>✅ Columns converted to TEXT!</p>";
}

// 2. Reset Admin
$username = 'lavya';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin) {
        $stmt = $pdo->prepare("UPDATE admins SET password = ?, is_super_admin = TRUE, is_approved = TRUE WHERE username = ?");
        $stmt->execute([$hash, $username]);
        echo "<h4>✅ Admin 'lavya' credentials updated to 'admin123'</h4>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, is_super_admin, is_approved) VALUES (?, ?, TRUE, TRUE)");
        $stmt->execute([$username, $hash]);
        echo "<h4>✅ Admin 'lavya' created with password 'admin123'</h4>";
    }
} catch (Exception $e) {
    echo "<p style='color:#ef4444;'>❌ Error setting admin: " . $e->getMessage() . "</p>";
}

echo "</div>";
echo "<div style='text-align:center; margin-top:2rem;'>";
echo "<a href='login.php' style='background:#3b82f6; color:white; padding:0.75rem 1.5rem; text-decoration:none; border-radius:8px; font-weight:bold;'>Go to Login Page</a>";
echo "</div>";

echo "<p style='text-align:center; color:#64748b; font-size:0.9rem; margin-top:1rem;'>If you see 'cached plan' error, just refresh this page once.</p>";
echo "</body></html>";
?>