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

// Use trimmed values
$dbConnection = trim(getenv('DB_CONNECTION') ?: 'mysql');
$host = trim(getenv('DB_HOST') ?: 'localhost');
$port = trim(getenv('DB_PORT') ?: '3307');
$dbname = trim(getenv('DB_NAME') ?: 'voting_system');
$user = trim(getenv('DB_USER') ?: 'root');
$pass = trim(getenv('DB_PASS') ?: '');

echo "<h2>Trimmed Values Debug:</h2>";
echo "<pre>";
echo "dbConnection bytes: " . bin2hex($dbConnection) . "\n";
echo "dbConnection length: " . strlen($dbConnection) . "\n";
echo "dbConnection value: '" . $dbConnection . "'\n";
echo "</pre>";

echo "<h2>Testing PDO Connection:</h2>";

$dsn = "{$dbConnection}:host={$host};port={$port};dbname={$dbname}";
echo "<pre>DSN: {$dsn}</pre>";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color: green;'>✅ CONNECTION SUCCESSFUL!</p>";

    // Try a simple query
    $result = $pdo->query("SELECT 1 as test");
    echo "<p style='color: green;'>✅ Query test passed!</p>";

    // Check tables
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✅ Tables: " . implode(', ', $tables) . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ CONNECTION FAILED!</p>";
    echo "<pre style='color: red;'>Error: " . $e->getMessage() . "</pre>";
}

echo "<h2>PHP Info:</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";
echo "</pre>";
