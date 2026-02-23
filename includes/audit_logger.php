<?php
/**
 * Audit Logger
 * Records all security-relevant events for compliance and forensics.
 */

// Event type constants
define('AUDIT_LOGIN', 'LOGIN');
define('AUDIT_LOGIN_FAIL', 'LOGIN_FAIL');
define('AUDIT_LOGOUT', 'LOGOUT');
define('AUDIT_VOTE_CAST', 'VOTE_CAST');
define('AUDIT_VOTE_TAMPER', 'VOTE_TAMPER');
define('AUDIT_ADMIN_ACTION', 'ADMIN_ACTION');
define('AUDIT_CHAIN_VERIFY', 'CHAIN_VERIFY');
define('AUDIT_REGISTER', 'REGISTER');
define('AUDIT_FACE_VERIFY', 'FACE_VERIFY');
define('AUDIT_SYSTEM', 'SYSTEM');

// Actor type constants
define('ACTOR_VOTER', 'voter');
define('ACTOR_ADMIN', 'admin');
define('ACTOR_SYSTEM', 'system');

/**
 * Log an audit event
 *
 * @param PDO    $pdo       — database connection
 * @param string $eventType — one of the AUDIT_* constants
 * @param string $actorType — 'voter', 'admin', or 'system'
 * @param int|null $actorId — ID of the actor (user or admin), null for system events
 * @param string $details   — human-readable description of the event
 * @param string|null $ipAddress — override IP (auto-detected if null)
 */
function logAuditEvent($pdo, $eventType, $actorType, $actorId, $details, $ipAddress = null)
{
    if ($pdo === null) {
        return; // silently skip if no DB
    }

    try {
        $ip = $ipAddress ?: ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        // Take only the first IP if forwarded through proxies
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (event_type, actor_type, actor_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$eventType, $actorType, $actorId, $details, $ip, $userAgent]);
    } catch (PDOException $e) {
        // Audit logging should never crash the app
        error_log('Audit log error: ' . $e->getMessage());
    }
}

/**
 * Get audit logs with optional filters
 *
 * @param PDO    $pdo        — database connection
 * @param array  $filters    — optional: ['event_type' => ..., 'actor_type' => ..., 'limit' => ..., 'offset' => ...]
 * @return array             — array of audit log entries
 */
function getAuditLogs($pdo, $filters = [])
{
    $where = [];
    $params = [];

    if (!empty($filters['event_type'])) {
        $where[] = 'event_type = ?';
        $params[] = $filters['event_type'];
    }

    if (!empty($filters['actor_type'])) {
        $where[] = 'actor_type = ?';
        $params[] = $filters['actor_type'];
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $limit = intval($filters['limit'] ?? 100);
    $offset = intval($filters['offset'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT * FROM audit_logs 
        $whereClause
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get total count of audit logs (for pagination)
 */
function getAuditLogCount($pdo, $filters = [])
{
    $where = [];
    $params = [];

    if (!empty($filters['event_type'])) {
        $where[] = 'event_type = ?';
        $params[] = $filters['event_type'];
    }

    if (!empty($filters['actor_type'])) {
        $where[] = 'actor_type = ?';
        $params[] = $filters['actor_type'];
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs $whereClause");
    $stmt->execute($params);
    return $stmt->fetchColumn();
}
?>