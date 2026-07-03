<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$pdo = getConnection();

$pdo->prepare(
    "INSERT INTO devices (device_type, hostname, ip_address, mac_address, signal_level, status, is_online, data_source)
     VALUES ('laptop', 'Test_Device', '192.168.43.99', 'DE:AD:BE:EF:00:01', 4, 'authorized', 1, 'real')"
)->execute();
$id = (int) $pdo->lastInsertId();

toggleDevice($pdo, $id, false);
$m1 = getMetrics($pdo);

toggleDevice($pdo, $id, true);
$m2 = getMetrics($pdo);

$pdo->prepare('DELETE FROM devices WHERE id = ?')->execute([$id]);
recalcMetrics($pdo);

echo 'toggle_off_users=' . $m1['active_users'] . PHP_EOL;
echo 'toggle_on_users=' . $m2['active_users'] . PHP_EOL;
echo 'api_logic_ok=' . ($m2['active_users'] > $m1['active_users'] ? 'yes' : 'no') . PHP_EOL;
