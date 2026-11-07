<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Queue
 * Bandwidth limiting and Queue management module (Simple Queue & Queue Tree).
 */
class Queue
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->conn->read('/queue/simple/print');
    }

    public function getByName(string $name): array
    {
        return $this->conn->read('/queue/simple/print', ['name' => $name]);
    }

    public function add(
        string $name,
        string $target,
        string $maxLimit,
        string $comment = ''
    ): array {
        $params = [
            'name'      => $name,
            'target'    => $target,
            'max-limit' => $maxLimit,
        ];
        if (!empty($comment)) $params['comment'] = $comment;

        return $this->conn->write('/queue/simple/add', $params);
    }

    public function limitBandwidth(string $targetIp, string $maxLimit = '5M/5M', string $name = ''): array
    {
        $queueName = $name ?: 'Limit-' . str_replace(['/', '.'], ['_', '-'], $targetIp);
        return $this->add($queueName, $targetIp, $maxLimit, 'Auto bandwidth limit');
    }

    public function set(string $name, array $params): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/queue/simple/print', $name) ?? $name);
        return $this->conn->set('/queue/simple/set', $id, $params);
    }

    public function remove(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/queue/simple/print', $name) ?? $name);
        return $this->conn->remove('/queue/simple/remove', $id);
    }

    public function getTree(): array
    {
        return $this->conn->read('/queue/tree/print');
    }

    public function addTree(array $params): array
    {
        return $this->conn->write('/queue/tree/add', $params);
    }
}
