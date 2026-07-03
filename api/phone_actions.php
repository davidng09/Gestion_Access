<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

requireApiKey();

try {
    $pdo = getConnection();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $status = $_GET['status'] ?? 'pending';
        if ($status !== 'pending') {
            jsonResponse(['error' => 'Statut non supporté'], 400);
        }

        $actions = getPendingPhoneActions($pdo);
        foreach ($actions as &$action) {
            $action['id'] = (int) $action['id'];
            $action['device_id'] = (int) $action['device_id'];
        }
        unset($action);

        jsonResponse(['actions' => $actions]);
    }

    if ($method === 'POST') {
        $body = getJsonBody();
        $actionId = (int) ($body['action_id'] ?? 0);
        $status = (string) ($body['status'] ?? '');

        if ($actionId <= 0) {
            jsonResponse(['error' => 'action_id invalide'], 400);
        }

        $error = isset($body['error']) ? trim((string) $body['error']) : null;
        if ($error === '') {
            $error = null;
        }

        $ok = acknowledgePhoneAction($pdo, $actionId, $status, $error);
        if (!$ok) {
            jsonResponse(['error' => 'Action introuvable ou déjà traitée'], 404);
        }

        jsonResponse(['success' => true, 'action_id' => $actionId, 'status' => $status]);
    }

    jsonResponse(['error' => 'Méthode non autorisée'], 405);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
