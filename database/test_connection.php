<?php

declare(strict_types=1);

/**
 * Vérifie la connexion BDD et les données seed.
 * Usage : php database/test_connection.php
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

try {
    $pdo = getConnection();
    $auth = authenticateAdmin($pdo, 'jeremie', 'admin123');
    $devices = $pdo->query('SELECT COUNT(*) AS c FROM devices')->fetch();

    echo 'auth_ok=' . ($auth ? 'yes' : 'no') . PHP_EOL;
    echo 'devices=' . $devices['c'] . PHP_EOL;
    exit($auth ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
