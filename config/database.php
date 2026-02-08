<?php
/**
 * Database Configuration
 * VoteUnity - Secure Online Voting System
 * 
 * Supports local (XAMPP/MySQL) and cloud (Vercel/Supabase PostgreSQL) deployment
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect base URL for dynamic paths
if (getenv('VERCEL_URL')) {
    // On Vercel
    define('BASE_URL', 'https://' . getenv('VERCEL_URL'));
} elseif (getenv('VERCEL')) {
    // On Vercel without VERCEL_URL set
    define('BASE_URL', '');
} else {
    // Local development
    define('BASE_URL', '/voting');
}

// Database type - 'mysql' for local, 'pgsql' for Supabase
$dbConnection = getenv('DB_CONNECTION') ?: 'mysql';

// Database credentials - Environment variables (cloud) or defaults (local)
define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'voting_system');
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '');

// Create PDO connection
try {
    $dsn = $dbConnection . ":host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;

    // Add charset for MySQL only
    if ($dbConnection === 'mysql') {
        $dsn .= ";charset=utf8mb4";
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Show friendly error in production, detailed in development
    if (getenv('VERCEL') || getenv('RAILWAY_ENVIRONMENT')) {
        die("Database connection failed. Please check your configuration.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}