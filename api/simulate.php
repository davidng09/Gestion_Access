<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

$body = getJsonBody();
$type = $body['type'] ?? '';

try {
    $pdo = getConnection();

    if ($type === 'traffic') {
        $result = simulateTraffic($pdo);
        jsonResponse($result);
    }

    if ($type === 'intrusion') {
        $result = simulateIntrusion($pdo);
        jsonResponse($result);
    }

    if ($type === 'reset_traffic') {
        $value = (int) ($body['value'] ?? 850);
        resetTraffic($pdo, $value);
        jsonResponse(['success' => true, 'traffic_mbps' => $value]);
    }

    jsonResponse(['error' => 'Type de simulation invalide'], 400);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
