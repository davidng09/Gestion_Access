<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

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

function simulateTraffic(PDO $pdo): array
{
    $spike = 1200;

    $pdo->prepare('UPDATE network_metrics SET traffic_mbps = ?, updated_at = NOW() WHERE id = 1')
        ->execute([$spike]);

    $pdo->prepare('INSERT INTO traffic_history (value_mbps) VALUES (?)')->execute([$spike]);

    logEvent(
        $pdo,
        'Simulation',
        'Simulation de pic de trafic : pic à 1,2 Gbps',
        'info'
    );

    return ['success' => true, 'traffic_mbps' => $spike];
}

function resetTraffic(PDO $pdo, int $value = 850): void
{
    $pdo->prepare('UPDATE network_metrics SET traffic_mbps = ?, updated_at = NOW() WHERE id = 1')
        ->execute([$value]);
}

function simulateIntrusion(PDO $pdo): array
{
    $pdo->exec('UPDATE network_metrics SET alert_active = 1, updated_at = NOW() WHERE id = 1');

    $stmt = $pdo->prepare(
        "INSERT INTO devices (device_type, hostname, ip_address, mac_address, signal_level, status, is_online)
         VALUES ('unknown', 'Unknown_10.0.0.122', '10.0.0.122', '00:00:00:00:00:00', 1, 'blocked', 0)"
    );
    $stmt->execute();
    $deviceId = (int) $pdo->lastInsertId();

    logEvent(
        $pdo,
        'ALERTE',
        'Tentative de force brute détectée depuis IP inconnue (10.0.0.122)',
        'error',
        $deviceId
    );

    return [
        'success' => true,
        'alert_active' => true,
        'device_id' => $deviceId,
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
