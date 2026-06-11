<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

try {
    $pdo = getConnection();
    $limit = min(100, max(1, (int) ($_GET['limit'] ?? 50)));

    $stmt = $pdo->prepare(
        'SELECT id, event_time, event_type, message, severity, device_id, created_at
         FROM activity_logs
         ORDER BY created_at DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();

    foreach ($logs as &$log) {
        $log['id'] = (int) $log['id'];
        $log['device_id'] = $log['device_id'] !== null ? (int) $log['device_id'] : null;
        if (strlen($log['event_time']) > 8) {
            $log['event_time'] = substr($log['event_time'], 0, 8);
        }
    }
    unset($log);

    jsonResponse($logs);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
