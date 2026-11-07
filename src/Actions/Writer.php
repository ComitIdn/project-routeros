<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Writer
 * Dedicated operational class for writing commands, raw payloads, scripts, and terminal commands.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Writer
{
    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Write command or parameters to any endpoint.
     *
     * @param string $endpoint e.g. '/system/reboot' or '/ip/hotspot/user/add'
     * @param array $params
     * @return array
     */
    public function write(string $endpoint, array $params = []): array
    {
        return $this->conn->write($endpoint, $params);
    }

    /**
     * Alias of write() for raw commands.
     */
    public function raw(string $endpoint, array $params = []): array
    {
        return $this->write($endpoint, $params);
    }

    /**
     * Execute raw CLI terminal command string (works across API, REST, SSH, and Telnet).
     *
     * @param string $cliCommand e.g. '/interface wireless registration-table print'
     * @return string
     */
    public function terminal(string $cliCommand): string
    {
        return $this->conn->execRaw($cliCommand);
    }

    /**
     * Execute a batch of commands sequentially.
     *
     * @param array $commands Array of ['endpoint' => '...', 'params' => [...]]
     * @return array
     */
    public function batch(array $commands): array
    {
        $results = [];
        foreach ($commands as $key => $item) {
            $endpoint = is_array($item) ? ($item['endpoint'] ?? $key) : $item;
            $params = is_array($item) ? ($item['params'] ?? []) : [];
            $results[$key] = $this->write($endpoint, $params);
        }
        return $results;
    }

    /**
     * Run a RouterOS script by name or create temporary script to execute.
     *
     * @param string $scriptName
     * @return array
     */
    public function runScript(string $scriptName): array
    {
        return $this->write('/system/script/run', ['number' => $scriptName]);
    }

    /**
     * Execute a dynamic RouterOS command block via temporary script execution.
     *
     * @param string $scriptSource RouterOS script syntax code
     * @param string $tempName
     * @return array
     */
    public function executeScriptSource(string $scriptSource, string $tempName = 'temp_runner'): array
    {
        // 1. Remove old temp script if exists
        $old = $this->conn->read('/system/script/print', ['name' => $tempName]);
        if (!empty($old) && isset($old[0]['.id'])) {
            $this->conn->remove('/system/script/remove', $old[0]['.id']);
        }

        // 2. Add temporary script
        $this->conn->write('/system/script/add', [
            'name'   => $tempName,
            'source' => $scriptSource,
            'policy' => 'ftp,reboot,read,write,policy,test,password,sniff,sensitive,romon',
        ]);

        // 3. Run script
        $runRes = $this->write('/system/script/run', ['number' => $tempName]);

        // 4. Cleanup
        $created = $this->conn->read('/system/script/print', ['name' => $tempName]);
        if (!empty($created) && isset($created[0]['.id'])) {
            $this->conn->remove('/system/script/remove', $created[0]['.id']);
        }

        return ['status' => 'script_executed', 'result' => $runRes];
    }
}
