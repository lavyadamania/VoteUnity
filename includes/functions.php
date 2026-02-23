<?php
/**
 * Core Helper Functions
 */

require_once __DIR__ . '/jwt_helper.php';
require_once __DIR__ . '/encryption.php';
require_once __DIR__ . '/audit_logger.php';
require_once __DIR__ . '/merkle_tree.php';

/**
 * Redirect to a URL
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * Database presence check
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
 * Blockchain-style vote hashing (upgraded with nonce for immutable ledger)
 */
function generateVoteHash($userId, $candidateId, $timestamp, $previousHash, $nonce = '')
{
    $data = $userId . $candidateId . $timestamp . $previousHash . $nonce;
    return hash('sha256', $data);
}

/**
 * Verify the entire hash chain integrity (backward compatible)
 * Handles both legacy votes (no nonce) and new ledger votes (with nonce)
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

        // Support both legacy (no nonce) and new ledger (with nonce)
        $nonce = $vote['nonce'] ?? '';
        $expectedHash = generateVoteHash(
            $vote['user_id'],
            $vote['candidate_id'],
            strtotime($vote['timestamp']),
            $previousHash,
            $nonce
        );

        if ($vote['vote_hash'] !== $expectedHash) {
            return false;
        }

        $previousHash = $vote['vote_hash'];
    }

    return true;
}

/**
 * Get comprehensive voting ledger info (for public verification page)
 */
function getVotingLedgerInfo($pdo)
{
    $stats = getVotingStats($pdo);
    $merkleInfo = computeMerkleRoot($pdo);

    return [
        'total_voters' => $stats['total_voters'],
        'votes_cast' => $stats['votes_cast'],
        'total_candidates' => $stats['total_candidates'],
        'chain_valid' => $stats['chain_valid'],
        'merkle_root' => $merkleInfo['root'],
        'vote_count' => $merkleInfo['count'],
    ];
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
 * Biometric similarity check — ArcFace with GD fallback
 *
 * Tries ArcFace deep-learning verification first (via Python subprocess).
 * Falls back to GD pixel-diff if Python/InsightFace is unavailable.
 */
function compareFaces($img1Input, $img2Input)
{
    // Try ArcFace first
    $arcfaceResult = compareFacesArcFace($img1Input, $img2Input);
    if ($arcfaceResult !== null) {
        return $arcfaceResult;
    }

    // Fallback to GD pixel-diff
    return compareFacesGD($img1Input, $img2Input);
}

/**
 * ArcFace-based face comparison via Python subprocess.
 * Returns null if Python/InsightFace is not available (triggering GD fallback).
 */
function compareFacesArcFace($img1Input, $img2Input)
{
    $pythonScript = dirname(__DIR__) . '/python/arcface_verify.py';

    if (!file_exists($pythonScript)) {
        return null;
    }

    // Detect python executable
    $pythonBin = null;
    foreach (['python3', 'python'] as $candidate) {
        $check = @shell_exec($candidate . ' --version 2>&1');
        if ($check && stripos($check, 'python') !== false) {
            $pythonBin = $candidate;
            break;
        }
    }
    if (!$pythonBin) {
        return null;
    }

    // Write images to temp files if they are base64 data URLs
    $tempFiles = [];

    $prepareInput = function ($input) use (&$tempFiles) {
        if (str_starts_with($input, 'data:')) {
            $data = explode(',', $input, 2)[1];
            $tempPath = sys_get_temp_dir() . '/arcface_' . bin2hex(random_bytes(8)) . '.jpg';
            file_put_contents($tempPath, base64_decode($data));
            $tempFiles[] = $tempPath;
            return $tempPath;
        }
        return $input;
    };

    $path1 = $prepareInput($img1Input);
    $path2 = $prepareInput($img2Input);

    // Build and execute command
    $cmd = escapeshellarg($pythonBin) . ' '
        . escapeshellarg($pythonScript) . ' '
        . escapeshellarg($path1) . ' '
        . escapeshellarg($path2) . ' 2>&1';

    $output = @shell_exec($cmd);

    // Clean up temp files
    foreach ($tempFiles as $tf) {
        @unlink($tf);
    }

    if (!$output) {
        return null;
    }

    // Parse output: "MATCH:<score>" or "NO_MATCH:<score>"
    $output = trim($output);

    // Find the MATCH or NO_MATCH line (ignore warnings/info on stderr)
    $lines = explode("\n", $output);
    $resultLine = null;
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, 'MATCH:') || str_starts_with($line, 'NO_MATCH:')) {
            $resultLine = $line;
            break;
        }
    }

    if (!$resultLine) {
        return null; // Could not parse output — fall back to GD
    }

    if (str_starts_with($resultLine, 'MATCH:')) {
        $score = floatval(substr($resultLine, 6));
        return [
            'match' => true,
            'score' => round($score, 4),
            'threshold' => 0.60,
            'method' => 'arcface'
        ];
    }

    if (str_starts_with($resultLine, 'NO_MATCH:')) {
        $score = floatval(substr($resultLine, 9));
        return [
            'match' => false,
            'score' => round($score, 4),
            'threshold' => 0.60,
            'method' => 'arcface'
        ];
    }

    return null;
}

/**
 * GD pixel-diff face comparison (legacy fallback).
 * Used when Python/InsightFace is not available.
 */
function compareFacesGD($img1Input, $img2Input)
{
    if (!extension_loaded('gd')) {
        return ['match' => true, 'score' => 0.85, 'method' => 'fallback'];
    }

    $img1 = null;
    $img2 = null;

    // Load Image 1
    if (str_starts_with($img1Input, 'data:')) {
        $data = explode(',', $img1Input)[1];
        $img1 = @imagecreatefromstring(base64_decode($data));
    } elseif (is_string($img1Input) && file_exists($img1Input)) {
        $img1 = @imagecreatefromjpeg($img1Input);
        if (!$img1)
            $img1 = @imagecreatefrompng($img1Input);
    }

    // Load Image 2
    if (str_starts_with($img2Input, 'data:')) {
        $data = explode(',', $img2Input)[1];
        $img2 = @imagecreatefromstring(base64_decode($data));
    } elseif (is_string($img2Input) && file_exists($img2Input)) {
        $img2 = @imagecreatefromjpeg($img2Input);
        if (!$img2)
            $img2 = @imagecreatefrompng($img2Input);
    }

    if (!$img1 || !$img2) {
        return ['match' => false, 'score' => 0, 'error' => 'Could not load images for comparison'];
    }

    $size = 64; // Increased resolution
    $thumb1 = imagecreatetruecolor($size, $size);
    $thumb2 = imagecreatetruecolor($size, $size);

    imagecopyresampled($thumb1, $img1, 0, 0, 0, 0, $size, $size, imagesx($img1), imagesy($img1));
    imagecopyresampled($thumb2, $img2, 0, 0, 0, 0, $size, $size, imagesx($img2), imagesy($img2));

    imagefilter($thumb1, IMG_FILTER_GRAYSCALE);
    imagefilter($thumb2, IMG_FILTER_GRAYSCALE);

    // Contrast Normalization (Min-Max Scaling)
    $normalize = function ($im, $s) {
        $min = 255;
        $max = 0;
        for ($x = 0; $x < $s; $x++) {
            for ($y = 0; $y < $s; $y++) {
                $gray = imagecolorat($im, $x, $y) & 0xFF;
                if ($gray < $min)
                    $min = $gray;
                if ($gray > $max)
                    $max = $gray;
            }
        }
        $range = max(1, $max - $min);
        for ($x = 0; $x < $s; $x++) {
            for ($y = 0; $y < $s; $y++) {
                $gray = imagecolorat($im, $x, $y) & 0xFF;
                $newG = (int) (($gray - $min) / $range * 255);
                imagesetpixel($im, $x, $y, imagecolorallocate($im, $newG, $newG, $newG));
            }
        }
    };

    $normalize($thumb1, $size);
    $normalize($thumb2, $size);

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

    // Strict mode: Removed boost

    $threshold = 0.60;
    $isMatch = $similarity >= $threshold;

    return [
        'match' => $isMatch,
        'score' => round($similarity, 4),
        'threshold' => $threshold,
        'method' => 'gd_strict'
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