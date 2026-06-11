<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

try {
    $pdo = getConnection();
    $metrics = getMetrics($pdo);
    jsonResponse($metrics);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Erreur base de données'], 500);
}
