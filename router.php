<?php
/**
 * Root Router for Vercel
 */

// Get the requested path
header("X-Vercel-Router: Active");
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($basePath, '/');

// Routing logic
if ($path === '' || $path === 'index.php') {
    require_once __DIR__ . '/index.php';
} elseif (preg_match('/\.php$/', $path)) {
    if (file_exists(__DIR__ . '/' . $path)) {
        require_once __DIR__ . '/' . $path;
    } else {
        http_response_code(404);
        echo "404 - File Not Found: " . htmlspecialchars($path);
    }
} else {
    // Try appending .php
    if (file_exists(__DIR__ . '/' . $path . '.php')) {
        require_once __DIR__ . '/' . $path . '.php';
    } else {
        require_once __DIR__ . '/index.php';
    }
}
