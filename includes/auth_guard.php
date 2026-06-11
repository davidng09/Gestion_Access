<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

if (!isAuthenticated()) {
    header('Location: login.php');
    exit;
}

$currentAdmin = getAdminSession();
