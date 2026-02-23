<?php
/**
 * JWT Helper — Pure PHP HMAC-SHA256 Implementation
 * No external library required.
 */

/**
 * Get JWT secret key
 */
function getJWTSecret()
{
    $secret = getenv('JWT_SECRET');
    if ($secret)
        return $secret;

    // Local dev: auto-generate a stable secret
    $keyFile = __DIR__ . '/../config/.jwt_secret';
    if (file_exists($keyFile)) {
        return trim(file_get_contents($keyFile));
    }

    $secret = bin2hex(random_bytes(32));
    @file_put_contents($keyFile, $secret);
    return $secret;
}

/**
 * Base64 URL-safe encode
 */
function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL-safe decode
 */
function base64UrlDecode($data)
{
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generate a JWT token
 *
 * @param array $payload — custom claims (e.g. user_id, role)
 * @param int   $expiry  — seconds until token expires (default: 3600 = 1 hour)
 * @return string         — signed JWT string
 */
function generateJWT($payload, $expiry = 3600)
{
    $secret = getJWTSecret();

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload['iss'] = 'VoteUnity';
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiry;

    $headerEncoded = base64UrlEncode(json_encode($header));
    $payloadEncoded = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $signatureEncoded = base64UrlEncode($signature);

    return "$headerEncoded.$payloadEncoded.$signatureEncoded";
}

/**
 * Validate and decode a JWT token
 *
 * @param string $token — the JWT string
 * @return array|false  — decoded payload on success, false on failure
 */
function validateJWT($token)
{
    $secret = getJWTSecret();
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return false;
    }

    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

    // Verify signature
    $expectedSig = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
    $actualSig = base64UrlDecode($signatureEncoded);

    if (!hash_equals($expectedSig, $actualSig)) {
        return false;
    }

    // Decode payload
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);

    if (!$payload) {
        return false;
    }

    // Check expiry
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }

    return $payload;
}

/**
 * Get JWT from the current request (cookie or Authorization header)
 *
 * @return string|null — the token string, or null if not found
 */
function getJWTFromRequest()
{
    // Check cookie first
    if (isset($_COOKIE['jwt_token'])) {
        return $_COOKIE['jwt_token'];
    }

    // Check Authorization header
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Set JWT as an HttpOnly cookie
 *
 * @param string $token  — the JWT string
 * @param int    $expiry — cookie lifetime in seconds (default: 3600)
 */
function setJWTCookie($token, $expiry = 3600)
{
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie('jwt_token', $token, [
        'expires' => time() + $expiry,
        'path' => '/',
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax'
    ]);
}

/**
 * Clear the JWT cookie
 */
function clearJWTCookie()
{
    setcookie('jwt_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax'
    ]);
}

/**
 * Check if the current request has a valid JWT
 *
 * @return array|false — decoded payload or false
 */
function isJWTValid()
{
    $token = getJWTFromRequest();
    if (!$token) {
        return false;
    }
    return validateJWT($token);
}
?>