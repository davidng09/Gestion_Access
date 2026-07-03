<?php

declare(strict_types=1);

/**
 * Configuration agent Android (collector / action listener).
 * Surchargez via config/agent.local.php (non versionné).
 */
function getAgentConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [
        'api_key' => 'gestion-access-dev-key-change-me',
        'hotspot_subnet' => '192.168.43.0/24',
        'device_stale_seconds' => 30,
        'rate_limit_seconds' => 1,
    ];

    $localPath = __DIR__ . '/agent.local.php';
    if (is_file($localPath)) {
        $local = require $localPath;
        if (is_array($local)) {
            $config = array_merge($config, $local);
        }
    }

    return $config;
}
