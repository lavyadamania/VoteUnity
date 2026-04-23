<?php
/**
* Database Connection Setup
*/

// Load local environment file for non-Vercel runs.
// This keeps local settings aligned with Vercel env keys.
$envFile = dirname(__DIR__) . '/.env.local';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

@ob_start(); // Buffer output to prevent header issues

// Detect deployment environment
$isVercel = (bool) (getenv('VERCEL') || getenv('VERCEL_URL'));

// Configure session for Vercel environment
if ($isVercel && session_status() === PHP_SESSION_NONE) {
    @ini_set('session.save_path', '/tmp');
    @ini_set('session.gc_maxlifetime', 3600);
}


// Start session if not started. If default session path is not writable,
// fall back to a local temp directory to avoid login redirect loops.
if (session_status() === PHP_SESSION_NONE) {
    $sessionStarted = @session_start();

    if (!$sessionStarted) {
        $fallbackSessionPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'voteunity_sessions';
        if (!is_dir($fallbackSessionPath)) {
            @mkdir($fallbackSessionPath, 0777, true);
        }

        if (is_dir($fallbackSessionPath) && is_writable($fallbackSessionPath)) {
            @ini_set('session.save_path', $fallbackSessionPath);
            @session_start();
        }
    }
}

// Detect base URL for dynamic paths
$isPhpBuiltInServer = (PHP_SAPI === 'cli-server');
if ($isVercel || $isPhpBuiltInServer) {
    // Vercel and php -S both serve from project root
    define('BASE_URL', '');
} else {
    // Local development (e.g., XAMPP subfolder)
    // Dynamically detect the base path by comparing project root with document root
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    if (!empty($docRoot) && strpos($projectRoot, $docRoot) === 0) {
        $baseUrl = substr($projectRoot, strlen($docRoot));
        define('BASE_URL', rtrim($baseUrl, '/'));
    } else {
        // Fallback for CLI or cases where DOCUMENT_ROOT is not set correctly
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $projectName = basename($projectRoot);
        $pos = strpos($scriptDir, '/' . $projectName);
        if ($pos !== false) {
            $baseUrl = substr($scriptDir, 0, $pos + strlen($projectName) + 1);
            define('BASE_URL', rtrim($baseUrl, '/'));
        } else {
            define('BASE_URL', '/voting');
        }
    }
}

// Setup connection based on environment variables
$dbConnection = trim(
    getenv('DB_CONNECTION') ?:
    (getenv('PGHOST') ? 'pgsql' : 'mysql')
);

// Use connection-specific precedence to avoid mixed pgsql/mysql values.
if ($dbConnection === 'pgsql') {
    define('DB_HOST', trim(getenv('PGHOST') ?: 'localhost'));
    define('DB_PORT', trim(getenv('PGPORT') ?: '5432'));
    define('DB_NAME', trim(getenv('PGDATABASE') ?: 'voting_system'));
    define('DB_USER', trim(getenv('PGUSER') ?: 'postgres'));
    define('DB_PASS', trim(getenv('PGPASSWORD') ?: ''));
} else {
    define('DB_HOST', trim(getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: 'localhost'));
    define('DB_PORT', trim(getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3307'));
    define('DB_NAME', trim(getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'voting_system'));
    define('DB_USER', trim(getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root'));
    define('DB_PASS', trim(getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: ''));
}

// Initialize pdo as null — pages must check before use
$pdo = null;
$db_error = null;

// Attempt to establish connection
$hasDbConfig = (bool) (getenv('DB_HOST') || getenv('PGHOST') || getenv('MYSQL_HOST'));

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
            PDO::ATTR_EMULATE_PREPARES => true, // Better pgbouncer support
            PDO::ATTR_TIMEOUT => 3,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
        $pdo = null;
    }
}
