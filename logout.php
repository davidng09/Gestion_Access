<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

destroyAdminSession();
header('Location: login.php');
exit;
