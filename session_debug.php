<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');

echo "--- SESSION DEBUG ---\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (1=None, 2=Active)\n";
echo "Session Save Path: " . ini_get('session.save_path') . "\n";
echo "Is Vercel: " . ($isVercel ? 'Yes' : 'No') . "\n";
echo "BASE_URL: '" . BASE_URL . "'\n";
echo "\n--- SESSION DATA ---\n";
print_r($_SESSION);

echo "\n--- COOKIES ---\n";
print_r($_COOKIE);

echo "\n--- DB CONNECTION ---\n";
if ($pdo) {
    echo "Status: SUCCESS\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        echo "Admins in DB: " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        echo "DB Query Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Status: FAILED\n";
    echo "Error: " . ($db_error ?? 'Unknown error') . "\n";
}

echo "\n--- ENVIRONMENT ---\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_PORT: " . getenv('DB_PORT') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";
echo "DB_USER: " . getenv('DB_USER') . "\n";
?>
