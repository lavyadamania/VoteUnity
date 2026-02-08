<?php
/**
 * Database Configuration
 * VoteUnity - Secure Online Voting System
 * 
 * Supports both local (XAMPP) and cloud (Railway) deployment
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database credentials - Environment variables (cloud) or defaults (local)
define('DB_HOST', getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'voting_system');
define('DB_USER', getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    // Show friendly error in development, generic in production
    if (getenv('RAILWAY_ENVIRONMENT')) {
        die("Database connection failed. Please check your configuration.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}