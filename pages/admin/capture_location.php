<?php
/**
 * Admin Location Capture Page
 * Captures location before allowing non-super admin login
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Guard: require DB
requireDb($pdo, $db_error ?? null);

// Check if admin has pending location requirement
if (!isset($_SESSION['admin_requires_location']) || !isset($_SESSION['admin_pending_login'])) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

$adminId = $_SESSION['admin_pending_login'];
$adminUsername = $_SESSION['admin_pending_username'];
$errors = [];

// Detect optional admin_locations columns for backward compatibility.
$locationColumns = [];
if ($dbConnection === 'pgsql') {
    $colStmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'admin_locations'");
} else {
    $colStmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'admin_locations'");
}
if ($colStmt) {
    $locationColumns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
}

$hasAccuracy = in_array('accuracy', $locationColumns, true);
$hasIpAddress = in_array('ip_address', $locationColumns, true);
$hasUserAgent = in_array('user_agent', $locationColumns, true);
$hasAddress = in_array('address', $locationColumns, true);

// Handle location submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $accuracy = floatval($_POST['accuracy'] ?? 0);

    if ($latitude == 0 && $longitude == 0) {
        $errors[] = 'Location is required. Please allow location access.';
    } else {
        // Save login location
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            $columns = ['admin_id', 'latitude', 'longitude'];
            $values = [$adminId, $latitude, $longitude];

            if ($hasAccuracy) {
                $columns[] = 'accuracy';
                $values[] = $accuracy;
            }
            if ($hasIpAddress) {
                $columns[] = 'ip_address';
                $values[] = $ipAddress;
            }
            if ($hasUserAgent) {
                $columns[] = 'user_agent';
                $values[] = $userAgent;
            }
            if ($hasAddress) {
                $columns[] = 'address';
                $values[] = $latitude . ', ' . $longitude;
            }

            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $sql = "INSERT INTO admin_locations (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            // Complete login
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['admin_username'] = $adminUsername;

            // Clear pending location
            unset($_SESSION['admin_requires_location']);
            unset($_SESSION['admin_pending_login']);
            unset($_SESSION['admin_pending_username']);

            redirect(BASE_URL . '/pages/admin/dashboard.php');
        } catch (PDOException $e) {
            $errors[] = 'Failed to save location: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Verification - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .location-card {
            max-width: 500px;
            margin: 2rem auto;
            text-align: center;
        }

        .location-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .location-status {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .status-waiting {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid #6366f1;
        }

        .status-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid #10b981;
        }

        .status-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid #ef4444;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
    </nav>

    <main class="container">
        <div class="card location-card">
            <div class="location-icon">📍</div>
            <h2>Location Verification Required</h2>
            <p style="color: var(--gray);">
                Hello <strong>
                    <?= htmlspecialchars($adminUsername) ?>
                </strong>!<br>
                Your location is required to complete login.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div>•
                            <?= $error ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div id="locationStatus" class="location-status status-waiting">
                <div class="spinner"></div>
                <p>Getting your location...</p>
            </div>

            <form id="locationForm" method="POST" action="" style="display: none;">
                <input type="hidden" name="latitude" id="lat">
                <input type="hidden" name="longitude" id="lng">
                <input type="hidden" name="accuracy" id="acc">
                <button type="submit" class="btn btn-primary btn-block">
                    ✓ Continue to Dashboard
                </button>
            </form>

            <div id="errorActions" style="display: none; margin-top: 1rem;">
                <button onclick="retryLocation()" class="btn btn-secondary">
                    🔄 Retry Location
                </button>
                <a href="<?= BASE_URL ?>/pages/admin/login.php?cancel=1" class="btn btn-secondary"
                    style="margin-top: 0.5rem;">
                    ← Back to Login
                </a>
            </div>

            <div class="alert alert-info" style="margin-top: 1.5rem;">
                <strong>Why is this needed?</strong><br>
                Location tracking helps ensure accountability and security of the admin panel.
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel - Secure Access Only</p>
        </div>
    </footer>

    <script>
        const statusDiv = document.getElementById('locationStatus');
        const form = document.getElementById('locationForm');
        const errorActions = document.getElementById('errorActions');

        function getLocation() {
            statusDiv.innerHTML = '<div class="spinner"></div><p>Getting your location...</p>';
            statusDiv.className = 'location-status status-waiting';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    document.getElementById('lat').value = position.coords.latitude;
                    document.getElementById('lng').value = position.coords.longitude;
                    document.getElementById('acc').value = position.coords.accuracy;

                    statusDiv.innerHTML = `
                        <p style="color: #10b981; font-size: 1.25rem;">✅ Location captured!</p>
                        <p style="color: var(--gray);">
                            Lat: ${position.coords.latitude.toFixed(4)}<br>
                            Lng: ${position.coords.longitude.toFixed(4)}<br>
                            Accuracy: ±${Math.round(position.coords.accuracy)}m
                        </p>
                    `;
                    statusDiv.className = 'location-status status-success';
                    form.style.display = 'block';
                    errorActions.style.display = 'none';
                },
                (error) => {
                    let msg = 'Location access denied';
                    if (error.code === 2) msg = 'Location unavailable';
                    if (error.code === 3) msg = 'Location request timed out';
                    showError(msg);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        }

        function showError(message) {
            statusDiv.innerHTML = `<p style="color: #ef4444;">❌ ${message}</p>`;
            statusDiv.className = 'location-status status-error';
            errorActions.style.display = 'block';
            form.style.display = 'none';
        }

        function retryLocation() {
            getLocation();
        }

        // Start getting location
        getLocation();
    </script>
</body>

</html>