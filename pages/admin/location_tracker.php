<?php
/**
 * Admin Location Tracker Page
 * View real-time location of all admins on a map
 */
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check admin login
if (!isAdminLoggedIn()) {
    redirect(BASE_URL . '/pages/admin/login.php');
}

// Get current admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$currentAdmin = $stmt->fetch();

// Only super admin can view all locations
$isSuperAdmin = $currentAdmin['is_super_admin'] ?? 0;

// Get latest location for each admin
$locations = $pdo->query("
    SELECT a.id, a.username, a.is_super_admin,
           l.latitude, l.longitude, l.accuracy, l.ip_address, l.tracked_at
    FROM admins a
    LEFT JOIN (
        SELECT admin_id, latitude, longitude, accuracy, ip_address, tracked_at
        FROM admin_locations l1
        WHERE tracked_at = (
            SELECT MAX(tracked_at) FROM admin_locations l2 WHERE l2.admin_id = l1.admin_id
        )
    ) l ON a.id = l.admin_id
    WHERE a.is_approved = 1 OR a.is_super_admin = 1
    ORDER BY l.tracked_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Locations - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 12px;
            margin: 1rem 0;
        }

        .location-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .location-card.active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .tracking-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(16, 185, 129, 0.2);
            border-radius: 20px;
            margin-bottom: 1rem;
        }

        .pulse {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }
        }

        .time-ago {
            color: var(--gray);
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <span class="nav-icon">🗳️</span>
            <span class="nav-title">VoteUnity Admin</span>
        </div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="view_votes.php">Vote Audit</a>
            <a href="location_tracker.php" class="active">📍 Locations</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <main class="container">
        <h1>📍 Admin Location Tracker</h1>

        <div class="tracking-status">
            <div class="pulse"></div>
            <span>Your location is being tracked continuously</span>
        </div>

        <div class="card">
            <h2>🗺️ Live Map</h2>
            <div id="map"></div>
        </div>

        <div class="card" style="margin-top: 1.5rem;">
            <h2>👥 Admin Locations</h2>

            <?php if (empty($locations)): ?>
                <div class="alert alert-info">No location data available yet.</div>
            <?php else: ?>
                <?php foreach ($locations as $loc): ?>
                    <div class="location-card <?= $loc['latitude'] ? 'active' : '' ?>">
                        <div>
                            <strong>
                                <?= htmlspecialchars($loc['username']) ?>
                            </strong>
                            <?= $loc['is_super_admin'] ? '<span style="color: #8b5cf6;"> 👑</span>' : '' ?>
                            <?php if ($loc['latitude']): ?>
                                <br><small style="color: var(--gray);">
                                    📍
                                    <?= round($loc['latitude'], 4) ?>,
                                    <?= round($loc['longitude'], 4) ?>
                                    <?php if ($loc['accuracy']): ?> (±
                                        <?= round($loc['accuracy']) ?>m)
                                    <?php endif; ?>
                                </small>
                            <?php else: ?>
                                <br><small style="color: #f59e0b;">⚠️ Location not shared</small>
                            <?php endif; ?>
                        </div>
                        <div class="time-ago">
                            <?php if ($loc['tracked_at']): ?>
                                <?= date('M j, g:i A', strtotime($loc['tracked_at'])) ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity Admin Panel -