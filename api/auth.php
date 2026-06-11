<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if ($username === '' || $password === '') {
        jsonResponse(['error' => 'Identifiants requis'], 400);
    }

    try {
        $pdo = getConnection();
        $admin = authenticateAdmin($pdo, $username, $password);

        if (!$admin) {
            jsonResponse(['error' => 'Identifiants invalides'], 401);
        }

        setAdminSession($admin);

        jsonResponse([
            'success' => true,
            'admin' => [
                'id' => (int) $admin['id'],
                'username' => $admin['username'],
                'full_name' => $admin['full_name'],
                'role_level' => $admin['role_level'],
                'avatar_url' => $admin['avatar_url'],
            ],
        ]);
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Erreur base de données'], 500);
    }
}

if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();
    jsonResponse(['admin' => getAdminSession()]);
}

jsonResponse(['error' => 'Action non supportée'], 405);
