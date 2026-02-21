<?php
/**
 * Vercel Serverless Function Entry Point (Smart Router)
 */

// Get the requested path and clean it
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($basePath, '/');

// Set root directory
$rootDir = dirname(__DIR__);

// Routing logic
if ($path === '' || $path === 'index.php') {
    // Homepage
    require_once $rootDir . '/index.php';
} elseif (preg_match('/\.php$/', $path)) {
    // Direct PHP file request
    $filePath = $rootDir . '/' . $path;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(404);
        echo "404 - File Not Found: " . htmlspecialchars($path);
    }
} else {
    // Try to append .php if it's a page name (e.g. /pages/login -> /pages/login.php)
    $filePath = $rootDir . '/' . $path . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        // Fallback to index.php for potential SPA behavior or root requests
        require_once $rootDir . '/index.php';
    }
}
