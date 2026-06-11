<?php

declare(strict_types=1);

/**
 * Script d'installation CLI — crée la BDD et importe les données.
 * Usage : php database/install.php
 */

$baseDir = dirname(__DIR__);
$schema = file_get_contents($baseDir . '/database/schema.sql');
$seed = file_get_contents($baseDir . '/database/seed.sql');

if ($schema === false || $seed === false) {
    fwrite(STDERR, "Fichiers SQL introuvables.\n");
    exit(1);
}

try {
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    foreach (explode(';', $schema) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }

    foreach (explode(';', $seed) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }

    echo "Installation terminée : base gestion_access prête.\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}
