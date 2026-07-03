<?php

declare(strict_types=1);

/**
 * Supprime tous les administrateurs et crée Jeremie BOKOTA.
 *
 * Usage : php database/create_admin.php
 *         php database/create_admin.php monMotDePasse
 */

require_once dirname(__DIR__) . '/config/database.php';

$username = 'jeremie';
$fullName = 'Jeremie BOKOTA';
$roleLevel = 'Administrateur Niveau 4';
$password = $argv[1] ?? 'admin123';

if (strlen($password) < 6) {
    fwrite(STDERR, "Erreur : le mot de passe doit contenir au moins 6 caractères.\n");
    exit(1);
}

try {
    $pdo = getConnection();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->exec('DELETE FROM admins');

    $stmt = $pdo->prepare(
        'INSERT INTO admins (username, password_hash, full_name, role_level, avatar_url)
         VALUES (?, ?, ?, ?, NULL)'
    );
    $stmt->execute([$username, $hash, $fullName, $roleLevel]);

    echo "Administrateurs supprimés.\n";
    echo "Nouvel admin créé :\n";
    echo "  Nom      : {$fullName}\n";
    echo "  Identifiant : {$username}\n";
    echo "  Mot de passe : {$password}\n";
    echo "  Rôle     : {$roleLevel}\n";
} catch (PDOException $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}
