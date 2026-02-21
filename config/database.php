<?php
/**
 * Database Configuration
 * VoteUnity - Secure Online Voting System
 * 
 * Supports local (XAMPP/MySQL) and cloud (Vercel/Supabase PostgreSQL) deployment
 */

// Detect deployment environment
$isVercel = (bool) (getenv('VERCEL') || getenv('VERCEL_URL'));

// Configure session for Vercel (serverless needs /tmp)
if ($isVercel) {
    ini_set('session.save_path', '/tmp');
    ini_set('session.gc_maxlifetime', 3600);
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect base URL for dynamic paths
if (getenv('VERCEL') || getenv('VERCEL_URL')) {
    // On Vercel - use relative paths
    define('BASE_URL', '');
} else {
    // Local development
    define('BASE_URL', '/voting');
}

// Database type - 'mysql' for local, 'pgsql' for Supabase
$dbConnection = trim(getenv('DB_CONNECTION') ?: 'mysql');

// Database credentials - Environment variables (cloud) or defaults (local)
define('DB_HOST', trim(getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost'));
define('DB_PORT', trim(getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3307'));
define('DB_NAME', trim(getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'voting_system'));
define('DB_USER', trim(getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root'));
define('DB_PASS', trim(getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: ''));

// Initialize pdo as null — pages must check before use
$pdo = null;
$db_error = null;

// Only attempt connection if DB_HOST env is explicitly set (i.e. cloud DB configured)
// or we are in local environment (not on Vercel)
$hasDbConfig = (bool) (getenv('DB_HOST') || getenv('MYSQL_HOST'));

if (!$isVercel || $hasDbConfig) {
    try {
        $dsn = $dbConnection . ":host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

        // Add charset for MySQL, SSL for PostgreSQL (required by Neon)
        if ($dbConnection === 'mysql') {
            $dsn .= ";charset=utf8mb4";
        } elseif ($dbConnection === 'pgsql') {
            $dsn .= ";sslmode=require";
        }

        // PDO options — connect_timeout avoids hanging on unreachable hosts
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 3,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        $pdo = null;
    }
}
