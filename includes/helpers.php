<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/agent.php';

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function logEvent(PDO $pdo, string $type, string $message, string $severity = 'info', ?int $deviceId = null): void
{
    $time = (new DateTime())->format('H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (event_time, event_type, message, severity, device_id)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$time, $type, $message, $severity, $deviceId]);
}

function recalcMetrics(PDO $pdo): void
{
    $counts = $pdo->query(
        "SELECT
            SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) AS active_users,
            SUM(CASE WHEN is_online = 1 AND device_type = 'laptop' THEN 1 ELSE 0 END) AS laptops_count,
            SUM(CASE WHEN is_online = 1 AND device_type = 'mobile' THEN 1 ELSE 0 END) AS mobile_count
         FROM devices"
    )->fetch();

    $stmt = $pdo->prepare(
        'UPDATE network_metrics
         SET active_users = ?, laptops_count = ?, mobile_count = ?, updated_at = NOW()
         WHERE id = 1'
    );
    $stmt->execute([
        (int) ($counts['active_users'] ?? 0),
        (int) ($counts['laptops_count'] ?? 0),
        (int) ($counts['mobile_count'] ?? 0),
    ]);
}

function getDeviceById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM devices WHERE id = ?');
    $stmt->execute([$id]);
    $device = $stmt->fetch();

    return $device ?: null;
}

function toggleDevice(PDO $pdo, int $id, bool $isOnline): ?array
{
    $device = getDeviceById($pdo, $id);
    if (!$device) {
        return null;
    }

    if ($device['status'] === 'blocked') {
        return ['error' => 'Appareil bloqué — débloquez avant de réactiver'];
    }

    if ($isOnline) {
        $message = "{$device['hostname']} est de nouveau en ligne";
        $type = 'Reconnexion';
    } else {
        $message = "Session de {$device['hostname']} terminée";
        $type = 'Hors ligne';
    }
    $severity = 'info';

    $stmt = $pdo->prepare('UPDATE devices SET is_online = ? WHERE id = ?');
    $stmt->execute([$isOnline ? 1 : 0, $id]);

    logEvent($pdo, $type, $message, $severity, $id);
    recalcMetrics($pdo);

    return getDeviceById($pdo, $id);
}

function blockDevice(PDO $pdo, int $id): ?array
{
    $device = getDeviceById($pdo, $id);
    if (!$device) {
        return null;
    }

    $stmt = $pdo->prepare("UPDATE devices SET status = 'blocked', is_online = 0 WHERE id = ?");
    $stmt->execute([$id]);

    if (($device['data_source'] ?? 'real') === 'real') {
        enqueuePhoneAction($pdo, $id, 'block');
    }

    logEvent(
        $pdo,
        'Blocage sécurité',
        "Blocage manuel : {$device['hostname']} ajouté à la liste noire",
        'error',
        $id
    );
    recalcMetrics($pdo);

    return getDeviceById($pdo, $id);
}

function unblockDevice(PDO $pdo, int $id): ?array
{
    $device = getDeviceById($pdo, $id);
    if (!$device) {
        return null;
    }

    if ($device['status'] !== 'blocked') {
        return ['error' => 'Appareil non bloqué'];
    }

    $stmt = $pdo->prepare("UPDATE devices SET status = 'authorized', is_online = 1 WHERE id = ?");
    $stmt->execute([$id]);

    if (($device['data_source'] ?? 'real') === 'real') {
        enqueuePhoneAction($pdo, $id, 'unblock');
    }

    logEvent(
        $pdo,
        'Déblocage',
        "Déblocage manuel : {$device['hostname']} retiré de la liste noire",
        'info',
        $id
    );
    recalcMetrics($pdo);

    return getDeviceById($pdo, $id);
}

function requireApiKey(): void
{
    $config = getAgentConfig();
    $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if ($provided === '' || !hash_equals((string) $config['api_key'], $provided)) {
        jsonResponse(['error' => 'Clé API invalide'], 401);
    }

    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isLocalAgentIp($clientIp)) {
        return;
    }

    $subnet = (string) ($config['hotspot_subnet'] ?? '');
    if ($subnet !== '' && $subnet !== '*' && !isIpInSubnet($clientIp, $subnet)) {
        jsonResponse(['error' => 'IP non autorisée'], 403);
    }
}

function isLocalAgentIp(string $ip): bool
{
    return in_array($ip, ['127.0.0.1', '::1'], true);
}

function isIpInSubnet(string $ip, string $subnet): bool
{
    if (!str_contains($subnet, '/')) {
        return $ip === $subnet;
    }

    [$network, $bits] = explode('/', $subnet, 2);
    $bits = (int) $bits;

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return false;
    }
    if (filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return false;
    }

    $ipLong = ip2long($ip);
    $netLong = ip2long($network);
    if ($ipLong === false || $netLong === false || $bits < 0 || $bits > 32) {
        return false;
    }

    $mask = $bits === 0 ? 0 : (-1 << (32 - $bits));
    return ($ipLong & $mask) === ($netLong & $mask);
}

function normalizeMacAddress(string $mac): string
{
    $clean = strtoupper(preg_replace('/[^a-fA-F0-9]/', '', $mac) ?? '');
    if (strlen($clean) !== 12) {
        return '';
    }

    return implode(':', str_split($clean, 2));
}

function guessDeviceTypeFromHostname(string $hostname): string
{
    $name = strtolower($hostname);
    if (str_contains($name, 'phone') || str_contains($name, 'android') || str_contains($name, 'mobile')) {
        return 'mobile';
    }
    if (str_contains($name, 'laptop') || str_contains($name, 'pc') || str_contains($name, 'desktop')) {
        return 'laptop';
    }

    return 'unknown';
}

function guessHostnameFromClient(string $ip, string $mac): string
{
    $suffix = str_replace(':', '', strtoupper($mac));
    return 'Client_' . substr($suffix, -6) . '_' . $ip;
}

function checkAgentRateLimit(PDO $pdo, string $clientIp): void
{
    $config = getAgentConfig();
    $limitSeconds = (int) ($config['rate_limit_seconds'] ?? 0);
    if ($limitSeconds <= 0) {
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT last_ping FROM agent_heartbeats WHERE agent_id = ? AND ip_address = ? LIMIT 1'
    );
    $stmt->execute(['rate-' . $clientIp, $clientIp]);
    $row = $stmt->fetch();

    if ($row && strtotime((string) $row['last_ping']) > time() - $limitSeconds) {
        jsonResponse(['error' => 'Trop de requêtes'], 429);
    }

    $pdo->prepare(
        'INSERT INTO agent_heartbeats (agent_id, ip_address, clients_count, last_ping)
         VALUES (?, ?, 0, NOW())
         ON DUPLICATE KEY UPDATE last_ping = NOW()'
    )->execute(['rate-' . $clientIp, $clientIp]);
}

function recordAgentHeartbeat(PDO $pdo, string $agentId, string $ipAddress, int $clientsCount): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO agent_heartbeats (agent_id, ip_address, clients_count, last_ping)
         VALUES (?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), clients_count = VALUES(clients_count), last_ping = NOW()'
    );
    $stmt->execute([$agentId, $ipAddress, $clientsCount]);
}

function getAgentStatus(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT agent_id, last_ping, ip_address, clients_count
         FROM agent_heartbeats
         WHERE agent_id = 'android-collector'
         LIMIT 1"
    );
    $row = $stmt->fetch();

    if (!$row) {
        return [
            'connected' => false,
            'agent_id' => 'android-collector',
            'last_ping' => null,
            'ip_address' => null,
            'clients_count' => 0,
            'seconds_since_ping' => null,
        ];
    }

    $lastPing = strtotime((string) $row['last_ping']);
    $secondsSince = $lastPing !== false ? max(0, time() - $lastPing) : null;
    $staleSeconds = (int) (getAgentConfig()['device_stale_seconds'] ?? 30);

    return [
        'connected' => $secondsSince !== null && $secondsSince <= $staleSeconds * 2,
        'agent_id' => $row['agent_id'],
        'last_ping' => $row['last_ping'],
        'ip_address' => $row['ip_address'],
        'clients_count' => (int) $row['clients_count'],
        'seconds_since_ping' => $secondsSince,
    ];
}

function upsertRealDevices(PDO $pdo, array $clients): array
{
    $config = getAgentConfig();
    $staleSeconds = max(5, (int) ($config['device_stale_seconds'] ?? 30));
    $inserted = 0;
    $updated = 0;

    foreach ($clients as $client) {
        if (!is_array($client)) {
            continue;
        }

        $ip = trim((string) ($client['ip'] ?? ''));
        $mac = normalizeMacAddress((string) ($client['mac'] ?? ''));
        if ($ip === '' || $mac === '' || $mac === '00:00:00:00:00:00') {
            continue;
        }

        $hostname = trim((string) ($client['hostname'] ?? ''));
        if ($hostname === '') {
            $hostname = guessHostnameFromClient($ip, $mac);
        }

        $deviceType = guessDeviceTypeFromHostname($hostname);
        $stmt = $pdo->prepare('SELECT id, status FROM devices WHERE mac_address = ? LIMIT 1');
        $stmt->execute([$mac]);
        $existing = $stmt->fetch();

        if ($existing) {
            $pdo->prepare(
                'UPDATE devices
                 SET ip_address = ?, hostname = ?, device_type = ?, is_online = 1,
                     data_source = ?, last_seen_at = NOW()
                 WHERE id = ?'
            )->execute([$ip, $hostname, $deviceType, 'real', (int) $existing['id']]);
            $updated++;
            continue;
        }

        $pdo->prepare(
            "INSERT INTO devices (device_type, hostname, ip_address, mac_address, signal_level, status, is_online, data_source, last_seen_at)
             VALUES (?, ?, ?, ?, 4, 'authorized', 1, 'real', NOW())"
        )->execute([$deviceType, $hostname, $ip, $mac]);
        $inserted++;
    }

    $pdo->prepare(
        "UPDATE devices
         SET is_online = 0
         WHERE data_source = 'real'
           AND status != 'blocked'
           AND (last_seen_at IS NULL OR last_seen_at < DATE_SUB(NOW(), INTERVAL ? SECOND))"
    )->execute([$staleSeconds]);

    recalcMetrics($pdo);

    return [
        'inserted' => $inserted,
        'updated' => $updated,
        'processed' => $inserted + $updated,
    ];
}

function enqueuePhoneAction(PDO $pdo, int $deviceId, string $action): ?int
{
    if (!in_array($action, ['block', 'unblock'], true)) {
        return null;
    }

    $device = getDeviceById($pdo, $deviceId);
    if (!$device || ($device['data_source'] ?? 'real') !== 'real') {
        return null;
    }

    $pdo->prepare(
        "UPDATE phone_actions_queue
         SET status = 'done', executed_at = NOW(), error_message = 'Remplacée par une action plus récente'
         WHERE device_id = ? AND status = 'pending'"
    )->execute([$deviceId]);

    $stmt = $pdo->prepare(
        'INSERT INTO phone_actions_queue (device_id, mac_address, action, status)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$deviceId, $device['mac_address'], $action, 'pending']);

    return (int) $pdo->lastInsertId();
}

function getPendingPhoneActions(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT id, device_id, mac_address, action, status, created_at
         FROM phone_actions_queue
         WHERE status = 'pending'
         ORDER BY id ASC
         LIMIT 50"
    );

    return $stmt->fetchAll() ?: [];
}

function acknowledgePhoneAction(PDO $pdo, int $actionId, string $status, ?string $error = null): bool
{
    if (!in_array($status, ['done', 'failed'], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE phone_actions_queue
         SET status = ?, executed_at = NOW(), error_message = ?
         WHERE id = ? AND status = ?'
    );

    $stmt->execute([$status, $error, $actionId, 'pending']);

    return $stmt->rowCount() > 0;
}

function getMetrics(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM network_metrics WHERE id = 1');
    $metrics = $stmt->fetch();

    if (!$metrics) {
        return [];
    }

    return [
        'network_status' => $metrics['network_status'],
        'active_users' => (int) $metrics['active_users'],
        'laptops_count' => (int) $metrics['laptops_count'],
        'mobile_count' => (int) $metrics['mobile_count'],
        'traffic_mbps' => (int) $metrics['traffic_mbps'],
        'traffic_up_mbps' => (int) $metrics['traffic_up_mbps'],
        'traffic_down_mbps' => (int) $metrics['traffic_down_mbps'],
        'alert_active' => (bool) $metrics['alert_active'],
    ];
}

function authenticateAdmin(PDO $pdo, string $username, string $password): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return null;
    }

    return $admin;
}
