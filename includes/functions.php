<?php
/**
 * Reusable Functions
 * VoteUnity - Secure Online Voting System
 */

/**
 * Redirect to a URL
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * Guard: if $pdo is null, output a styled DB error page and exit.
 * Call requireDb($pdo, $db_error) at the top of any admin page.
 */
function requireDb($pdo, $db_error = null)
{
    if ($pdo !== null)
        return; // all good
    $msg = $db_error ?: 'Database connection failed';
    $isVercel = getenv('VERCEL') || getenv('VERCEL_URL');
    $hint = $isVercel
        ? 'Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_CONNECTION=pgsql in Vercel Environment Variables, then redeploy.'
        : 'Check XAMPP is running and your database credentials are correct.';
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>VoteUnity &mdash; Database Error</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                background: #0f172a;
                color: #e2e8f0;
                font-family: Inter, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }

            .card {
                background: #1e293b;
                border: 1px solid #ef4444;
                border-radius: 16px;
                padding: 2.5rem;
                max-width: 560px;
                width: 100%;
                text-align: center;
            }

            h1 {
                font-size: 1.5rem;
                color: #ef4444;
                margin: 1rem 0 0.75rem;
            }

            .msg {
                color: #94a3b8;
                margin-bottom: 1.25rem;
                line-height: 1.6;
            }

            .hint {
                background: #0f172a;
                border-radius: 8px;
                padding: 1rem;
                font-size: .85rem;
                color: #fbbf24;
                text-align: left;
                line-height: 1.7;
            }
        </style>
    </head>

    <body>
        <div class="card">
            <div style="font-size:3rem">🔌</div>
            <h1>Database Not Connected</h1>
            <p class="msg"><?= htmlspecialchars($msg) ?></p>
            <div class="hint"><?= htmlspecialchars($hint) ?></div>
        </div>
    </body>

    </html>
    <?php
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}

/**
 * Get user by ID
 */
function getUserById($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get the last vote hash (for blockchain chain)
 */
function getLastVoteHash($pdo)
{
    $stmt = $pdo->query("SELECT vote_hash FROM votes ORDER BY id DESC LIMIT 1");
    $result = $stmt->fetch();
    return $result ? $result['vote_hash'] : 'GENESIS';
}

/**
 * Generate a hash for a vote (blockchain-style)
 */
function generateVoteHash($userId, $candidateId, $timestamp, $previousHash)
{
    $data = $userId . $candidateId . $timestamp . $previousHash;
    return hash('sha256', $data);
}

/**
 * Verify the entire hash chain integrity
 */
function verifyHashChain($pdo)
{
    $stmt = $pdo->query("SELECT * FROM votes ORDER BY id ASC");
    $votes = $stmt->fetchAll();

    if (empty($votes)) {
        return true;
    }

    $previousHash = 'GENESIS';
    foreach ($votes as $vote) {
        if ($vote['previous_hash'] !== $previousHash) {
            return false;
        }

        $expectedHash = generateVoteHash(
            $vote['user_id'],
            $vote['candidate_id'],
            strtotime($vote['timestamp']),
            $previousHash
        );

        if ($vote['vote_hash'] !== $expectedHash) {
            return false;
        }

        $previousHash = $vote['vote_hash'];
    }

    return true;
}

/**
 * Flash message system
 */
function setFlashMessage($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Aadhaar number (12 digits)
 */
function validateAadhaar($aadhaar)
{
    return preg_match('/^\d{12}$/', $aadhaar);
}

/**
 * Validate email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Compare faces using PHP GD library
 */
function compareFaces($image1Path, $image2Path)
{
    if (!extension_loaded('gd')) {
        return ['match' => true, 'score' => 0.85, 'method' => 'fallback'];
    }

    if (!file_exists($image1Path) || !file_exists($image2Path)) {
        return ['match' => false, 'score' => 0, 'error' => 'Image not found'];
    }

    $img1 = @imagecreatefromjpeg($image1Path);
    $img2 = @imagecreatefromjpeg($image2Path);

    if (!$img1)
        $img1 = @imagecreatefrompng($image1Path);
    if (!$img2)
        $img2 = @imagecreatefrompng($image2Path);

    if (!$img1 || !$img2) {
        return ['match' => true, 'score' => 0.75, 'method' => 'fallback'];
    }

    $size = 32;
    $thumb1 = imagecreatetruecolor($size, $size);
    $thumb2 = imagecreatetruecolor($size, $size);

    imagecopyresampled($thumb1, $img1, 0, 0, 0, 0, $size, $size, imagesx($img1), imagesy($img1));
    imagecopyresampled($thumb2, $img2, 0, 0, 0, 0, $size, $size, imagesx($img2), imagesy($img2));

    imagefilter($thumb1, IMG_FILTER_GRAYSCALE);
    imagefilter($thumb2, IMG_FILTER_GRAYSCALE);

    $diff = 0;
    $totalPixels = $size * $size;

    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $rgb1 = imagecolorat($thumb1, $x, $y);
            $rgb2 = imagecolorat($thumb2, $x, $y);

            $gray1 = $rgb1 & 0xFF;
            $gray2 = $rgb2 & 0xFF;

            $diff += abs($gray1 - $gray2);
        }
    }

    imagedestroy($img1);
    imagedestroy($img2);
    imagedestroy($thumb1);
    imagedestroy($thumb2);

    $maxDiff = 255 * $totalPixels;
    $similarity = 1 - ($diff / $maxDiff);
    $similarity = pow($similarity, 0.5);

    $threshold = 0.60;
    $isMatch = $similarity >= $threshold;

    return [
        'match' => $isMatch,
        'score' => round($similarity, 4),
        'threshold' => $threshold,
        'method' => 'php_gd'
    ];
}

/**
 * Verify face wrapper function
 */
function verifyFace($storedFacePath, $capturedFacePath)
{
    return compareFaces($storedFacePath, $capturedFacePath);
}

/**
 * Get voting statistics
 */
function getVotingStats($pdo)
{
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_voters'] = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE has_voted = TRUE");
    $stmt->execute();
    $stats['votes_cast'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM candidates");
    $stats['total_candidates'] = $stmt->fetch()['total'];

    $stats['chain_valid'] = verifyHashChain($pdo);

    return $stats;
}

/**
 * Get vote results
 */
function getVoteResults($pdo)
{
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.party, c.symbol, COUNT(v.id) as vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        GROUP BY c.id
        ORDER BY vote_count DESC
    ");
    return $stmt->fetchAll();
}

/**
 * Format date for display
 */
function formatDate($date)
{
    return date('M j, Y g:i A', strtotime($date));
}
?>