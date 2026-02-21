<?php
/**
 * Location Tracking API
 * Receives and saves admin location data
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['latitude']) || !isset($input['longitude'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Latitude and longitude required']);
    exit;
}

$adminId = $_SESSION['admin_id'];
$latitude = floatval($input['latitude']);
$longitude = floatval($input['longitude']);
$accuracy = isset($input['accuracy']) ? floatval($input['accuracy']) : null;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $stmt = $pdo->prepare("INSERT INTO admin_locations (admin_id, latitude, longitude, accuracy, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$adminId, $latitude, $longitude, $accuracy, $ipAddress, $userAgent]);

    echo json_encode([
        'success' => true,
        'message' => 'Location saved',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>