<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAuthenticated(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
}

function getAdminSession(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? '',
        'full_name' => $_SESSION['admin_full_name'] ?? '',
        'role_level' => $_SESSION['admin_role_level'] ?? '',
        'avatar_url' => $_SESSION['admin_avatar_url'] ?? '',
    ];
}

function setAdminSession(array $admin): void
{
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_role_level'] = $admin['role_level'];
    $_SESSION['admin_avatar_url'] = $admin['avatar_url'] ?? '';
}

function destroyAdminSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
