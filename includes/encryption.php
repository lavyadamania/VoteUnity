<?php
/**
 * Vote Encryption — AES-256-GCM
 * Provides end-to-end encryption for vote data at rest.
 */

define('ENCRYPTION_CIPHER', 'aes-256-gcm');
define('ENCRYPTION_TAG_LENGTH', 16);

/**
 * Get the encryption key (32 bytes / 64 hex chars)
 *
 * @return string — raw 32-byte key
 */
function getEncryptionKey()
{
    $hexKey = getenv('ENCRYPTION_KEY');
    if ($hexKey && strlen($hexKey) === 64) {
        return hex2bin($hexKey);
    }

    // Local dev: auto-generate a stable key
    $keyFile = __DIR__ . '/../config/.encryption_key';
    if (file_exists($keyFile)) {
        return hex2bin(trim(file_get_contents($keyFile)));
    }

    $key = random_bytes(32);
    @file_put_contents($keyFile, bin2hex($key));
    return $key;
}

/**
 * Encrypt vote data
 *
 * @param string $plaintext — the data to encrypt (e.g. JSON of vote details)
 * @return string           — base64-encoded string in format "iv:tag:ciphertext"
 */
function encryptVote($plaintext)
{
    $key = getEncryptionKey();
    $iv = random_bytes(openssl_cipher_iv_length(ENCRYPTION_CIPHER));
    $tag = '';

    $ciphertext = openssl_encrypt(
        $plaintext,
        ENCRYPTION_CIPHER,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',                       // AAD (additional authenticated data)
        ENCRYPTION_TAG_LENGTH
    );

    if ($ciphertext === false) {
        return false;
    }

    // Pack as iv:tag:ciphertext (all base64)
    return base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($ciphertext);
}

/**
 * Decrypt vote data
 *
 * @param string $encrypted — the string produced by encryptVote()
 * @return string|false     — decrypted plaintext, or false on failure
 */
function decryptVote($encrypted)
{
    $key = getEncryptionKey();
    $parts = explode(':', $encrypted);

    if (count($parts) !== 3) {
        return false;
    }

    $iv = base64_decode($parts[0]);
    $tag = base64_decode($parts[1]);
    $ciphertext = base64_decode($parts[2]);

    $plaintext = openssl_decrypt(
        $ciphertext,
        ENCRYPTION_CIPHER,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $plaintext;
}
?>