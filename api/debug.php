<?php
/**
 * Debug endpoint to test database connection
 * URL: /api/debug.php
 */

echo "<h1>VoteUnity Debug</h1>";

echo "<h2>Environment Variables:</h2>";
echo "<pre>";
echo "DB_CONNECTION: " . (getenv('DB_CONNECTION') ?: '(not set)') . "\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: '(not set)') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: '(not set)') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: '(not set)') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: '(not set)') . "\n";
echo "DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : '(not set)') . "\n";
echo "VERCEL: " . (getenv('VERCEL') ?: '(not set)') . "\n";
echo "</pre>";

echo "<h2>Testing PDO Connection:</h2>";

$dbConnection = getenv('DB_CONNECTION') ?: 'mysql';
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3307';
$dbname = getenv('DB_NAME') ?: 'voting_system';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

echo "<pre>";
echo "DSN: {$dbConnection}:host={$host};port={$port};dbname={$dbname}\n";
echo "</pre>";

try {
    $dsn = "{$dbConnection}:host={$host};port={$port};dbname={$dbname}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✅ CONNECTION SUCCESSFUL!</p>";

    // Try a simple query
    $result = $pdo->query("SELECT 1 as test");
    echo "<p style='color: green;'>✅ Query test passed!</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ CONNECTION FAILED!</p>";
    echo "<pre style='color: red;'>Error: " . $e->getMessage() . "</pre>";
}

echo "<h2>PHP Info:</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
echo "</pre>";
