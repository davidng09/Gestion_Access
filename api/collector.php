<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

requireApiKey();

try {
    $pdo = getConnection();
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    checkAgentRateLimit($pdo, $clientIp);

    $body = getJsonBody();
    $clients = $body['clients'] ?? [];
    if (!is_array($clients)) {
        jsonResponse(['error' => 'Champ clients invalide'], 400);
    }

    $agentId = trim((string) ($body['agent_id'] ?? 'android-collector'));
    if ($agentId === '') {
        $agentId = 'android-collector';
    }

    $result = upsertRealDevices($pdo, $clients);
    recordAgentHeartbeat($pdo, $agentId, $clientIp, count($clients));

    if ($result['inserted'] > 0) {
        logEvent(
            $pdo,
            'Scan réseau',
            sprintf('Agent Android : %d nouveau(x) client(s) détecté(s)', $result['inserted']),
            'info'
        );
    }

    jsonResponse([
        'success' => true,
        'clients_received' => count($clients),
        'inserted' => $result['inserted'],
        'updated' => $result['updated'],
    ]);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
