<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Firewall
 * Full MikroTik Firewall management module (Filter, NAT, Mangle, RAW, Address Lists).
 */
class Firewall
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    // --- FILTER RULES ---

    public function getFilterRules(): array
    {
        return $this->conn->read('/ip/firewall/filter/print');
    }

    public function addFilterRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/filter/add', $params);
    }

    public function removeFilterRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/filter/remove', $id);
    }

    // --- NAT RULES ---

    public function getNatRules(): array
    {
        return $this->conn->read('/ip/firewall/nat/print');
    }

    public function addNatRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/nat/add', $params);
    }

    public function addMasquerade(string $outInterface, string $comment = 'Default Masquerade NAT'): array
    {
        return $this->addNatRule([
            'chain'         => 'srcnat',
            'action'        => 'masquerade',
            'out-interface' => $outInterface,
            'comment'       => $comment,
        ]);
    }

    public function removeNatRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/nat/remove', $id);
    }

    // --- MANGLE RULES ---

    public function getMangleRules(): array
    {
        return $this->conn->read('/ip/firewall/mangle/print');
    }

    public function addMangleRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/mangle/add', $params);
    }

    public function removeMangleRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/mangle/remove', $id);
    }

    // --- RAW RULES ---

    public function getRawRules(): array
    {
        return $this->conn->read('/ip/firewall/raw/print');
    }

    public function addRawRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/raw/add', $params);
    }

    public function removeRawRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/raw/remove', $id);
    }

    // --- ADDRESS LISTS ---

    public function getAddressLists(): array
    {
        return $this->conn->read('/ip/firewall/address-list/print');
    }

    public function addAddressList(string $list, string $address, string $comment = '', string $timeout = ''): array
    {
        $params = [
            'list'    => $list,
            'address' => $address,
        ];
        if (!empty($comment)) $params['comment'] = $comment;
        if (!empty($timeout)) $params['timeout'] = $timeout;

        return $this->conn->write('/ip/firewall/address-list/add', $params);
    }

    public function removeAddressList(string $id): array
    {
        return $this->conn->remove('/ip/firewall/address-list/remove', $id);
    }
}
