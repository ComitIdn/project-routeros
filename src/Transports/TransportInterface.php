<?php

namespace RouterOSFramework\Transports;

/**
 * Interface TransportInterface
 * Universal contract for communicating with MikroTik RouterOS across protocols:
 * Binary API, Web REST API (v7+), SSH CLI, and Telnet CLI.
 */
interface TransportInterface
{
    /**
     * Establish connection to RouterOS.
     *
     * @return bool
     */
    public function connect(): bool;

    /**
     * Close the connection.
     */
    public function disconnect(): void;

    /**
     * Check if currently connected.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Get the last error message or null if none.
     *
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * Read records from a print endpoint.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/print' or '/ip/hotspot/user'
     * @param array $where Filter conditions
     * @return array
     */
    public function read(string $endpoint, array $where = []): array;

    /**
     * Write/add record to an endpoint.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/add'
     * @param array $params
     * @return array
     */
    public function write(string $endpoint, array $params = []): array;

    /**
     * Set/update record by ID.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/set'
     * @param string $id
     * @param array $params
     * @return array
     */
    public function set(string $endpoint, string $id, array $params): array;

    /**
     * Remove record by ID.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/remove'
     * @param string $id
     * @return array
     */
    public function remove(string $endpoint, string $id): array;

    /**
     * Execute arbitrary command with parameters.
     *
     * @param string $command e.g. '/system/reboot' or '/tool/ping'
     * @param array $params
     * @return array
     */
    public function execute(string $command, array $params = []): array;

    /**
     * Execute raw CLI terminal string and return text output.
     *
     * @param string $cliCommand
     * @return string
     */
    public function execRaw(string $cliCommand): string;

    /**
     * Get protocol identifier ('api', 'rest', 'ssh', 'telnet').
     *
     * @return string
     */
    public function getProtocol(): string;
}
