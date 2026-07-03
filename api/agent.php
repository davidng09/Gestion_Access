<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Méthode non autorisée'], 405);
}

try {
    $pdo = getConnection();
    jsonResponse(getAgentStatus($pdo));
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
