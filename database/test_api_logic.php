<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$pdo = getConnection();

// Reset Laptop_3 online
$pdo->exec("UPDATE devices SET is_online = 1, status = 'authorized' WHERE hostname = 'Laptop_3'");
recalcMetrics($pdo);

$device = $pdo->query("SELECT id FROM devices WHERE hostname = 'Laptop_3'")->fetch();
$id = (int) $device['id'];

toggleDevice($pdo, $id, false);
$m1 = getMetrics($pdo);

toggleDevice($pdo, $id, true);
$m2 = getMetrics($pdo);

echo 'toggle_off_users=' . $m1['active_users'] . PHP_EOL;
echo 'toggle_on_users=' . $m2['active_users'] . PHP_EOL;
echo 'api_logic_ok=' . ($m2['active_users'] > $m1['active_users'] ? 'yes' : 'no') . PHP_EOL;
