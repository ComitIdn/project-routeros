<?php

namespace RouterOSFramework\Integrations\CodeIgniter;

use RouterOSFramework\Core\RouterOS;

/**
 * Class RouterOSLibrary
 * Drop-in library for CodeIgniter 3 and CodeIgniter 4 applications.
 *
 * Usage in CI3:
 *   $this->load->library('RouterOSLibrary', $config);
 *   $users = $this->routeroslibrary->hotspot()->getUsers();
 *
 * Usage in CI4:
 *   $ros = new \RouterOSFramework\Integrations\CodeIgniter\RouterOSLibrary();
 *   $ros->hotspot()->setLimit('budi', '2h');
 */
class RouterOSLibrary
{
    private RouterOS $framework;

    public function __construct(array $params = [])
    {
        if (empty($params)) {
            // Auto-detect config.php if exists
            $configFile = __DIR__ . '/../../../config.php';
            if (file_exists($configFile)) {
                $cfg = require $configFile;
                $params = $cfg['mikrotik'] ?? [];
                if (!empty($cfg['database'])) {
                    $params['database'] = $cfg['database'];
                }
            }
        }

        $this->framework = new RouterOS($params);
    }

    /**
     * Get underlying unified framework instance.
     */
    public function framework(): RouterOS
    {
        return $this->framework;
    }

    /**
     * Proxy all method calls directly to RouterOS facade.
     */
    public function __call(string $name, array $arguments)
    {
        return $this->framework->{$name}(...$arguments);
    }
}
