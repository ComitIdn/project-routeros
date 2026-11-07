<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Backup
 * Automated configuration export, binary backup, file management, and cloud/telegram dispatch.
 */
class Backup
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Create binary backup (.backup file on router).
     *
     * @param string $name Backup file name (without .backup)
     * @param string|null $password Optional encryption password
     * @param bool $dontEncrypt
     * @return array
     */
    public function saveBackup(string $name = '', ?string $password = null, bool $dontEncrypt = false): array
    {
        $backupName = $name ?: 'backup-' . date('Ymd-His');
        $params = ['name' => $backupName];

        if ($password !== null && $password !== '') {
            $params['password'] = $password;
        } elseif ($dontEncrypt) {
            $params['dont-encrypt'] = 'yes';
        }

        $res = $this->conn->write('/system/backup/save', $params);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Backup', 'Create Backup', $backupName, 'Binary backup created', 'success');
        }

        return $res;
    }

    /**
     * Export human-readable RouterOS configuration script (.rsc).
     *
     * @param string $fileName
     * @param bool $compact
     * @param bool $hideSensitive
     * @return array
     */
    public function exportConfig(string $fileName = '', bool $compact = true, bool $hideSensitive = true): array
    {
        $name = $fileName ?: 'export-' . date('Ymd-His');
        $query = (new Query('/export'))->equal('file', $name);

        if ($compact) {
            $query->equal('compact', 'yes');
        }
        if ($hideSensitive) {
            $query->equal('hide-sensitive', 'yes');
        }

        $res = $this->conn->executeQuery($query);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Backup', 'Export RSC', $name, 'Configuration script exported', 'success');
        }

        return $res;
    }

    /**
     * Get list of all files stored on router storage.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->conn->read('/file/print');
    }

    /**
     * Remove a file from router storage by name or .id.
     *
     * @param string $fileName
     * @return array
     */
    public function removeFile(string $fileName): array
    {
        $id = str_starts_with($fileName, '*') ? $fileName : ($this->conn->findIdByName('/file/print', $fileName) ?? $fileName);
        return $this->conn->remove('/file/remove', $id);
    }
}
