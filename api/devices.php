<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

try {
    $pdo = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM devices ORDER BY hostname ASC');
        $devices = $stmt->fetchAll();

        foreach ($devices as &$device) {
            $device['id'] = (int) $device['id'];
            $device['signal_level'] = (int) $device['signal_level'];
            $device['is_online'] = (bool) $device['is_online'];
        }
        unset($device);

        jsonResponse($devices);
    }

    if ($method === 'PATCH') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['error' => 'ID invalide'], 400);
        }

        $body = getJsonBody();

        if (($body['action'] ?? '') === 'block') {
            $device = blockDevice($pdo, $id);
            if (!$device) {
                jsonResponse(['error' => 'Appareil introuvable'], 404);
            }
            jsonResponse(['success' => true, 'device' => $device]);
        }

        if (array_key_exists('is_online', $body)) {
            $result = toggleDevice($pdo, $id, (bool) $body['is_online']);
            if ($result === null) {
                jsonResponse(['error' => 'Appareil introuvable'], 404);
            }
            if (isset($result['error'])) {
                jsonResponse(['error' => $result['error']], 400);
            }
            jsonResponse(['success' => true, 'device' => $result]);
        }

        jsonResponse(['error' => 'Corps de requête invalide'], 400);
    }

    jsonResponse(['error' => 'Méthode non autorisée'], 405);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
