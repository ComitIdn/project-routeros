<?php

namespace RouterOSFramework\Core;

/**
 * Class Helper
 * Utility methods for formatting, byte conversion, random strings, and input validation.
 */
class Helper
{
    /**
     * Convert bytes to human-readable format (B, KB, MB, GB, TB).
     *
     * @param int|float $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float)$bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min((int)$pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Parse human-readable byte string (e.g., '1G', '500M', '1024K') to raw bytes integer.
     *
     * @param string|int|float $size
     * @return int
     */
    public static function parseBytes($size): int
    {
        if (is_numeric($size)) {
            return (int)$size;
        }

        $size = trim((string)$size);
        $unit = strtoupper(substr($size, -1));
        $val = (float)substr($size, 0, -1);

        switch ($unit) {
            case 'G':
                return (int)($val * 1024 * 1024 * 1024);
            case 'M':
                return (int)($val * 1024 * 1024);
            case 'K':
                return (int)($val * 1024);
            default:
                return (int)$size;
        }
    }

    /**
     * Generate secure random voucher or password string.
     *
     * @param int $length
     * @param string $type ('alphanumeric'|'numeric'|'alpha'|'alphanumeric_upper')
     * @return string
     */
    public static function randomString(int $length = 6, string $type = 'alphanumeric'): string
    {
        switch ($type) {
            case 'numeric':
            case 'pin':
                // Avoid confusing 0 and 1
                $chars = '23456789';
                break;
            case 'alpha':
                $chars = 'abcdefghjkmnpqrstuvwxyz';
                break;
            case 'alphanumeric_upper':
                $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
                break;
            case 'alphanumeric':
            default:
                $chars = '23456789abcdefghjkmnpqrstuvwxyz';
                break;
        }

        $charsLen = strlen($chars);
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $charsLen - 1)];
        }
        return $result;
    }

    /**
     * Format and standardize MAC address to XX:XX:XX:XX:XX:XX.
     *
     * @param string $mac
     * @return string
     */
    public static function formatMac(string $mac): string
    {
        $clean = preg_replace('/[^0-9a-fA-F]/', '', $mac);
        if (strlen($clean) === 12) {
            return strtoupper(implode(':', str_split($clean, 2)));
        }
        return strtoupper($mac);
    }

    /**
     * Check if a string is a valid MAC address.
     *
     * @param string $mac
     * @return bool
     */
    public static function isValidMac(string $mac): bool
    {
        return (bool)preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
    }

    /**
     * Check if a string is a valid IP address.
     *
     * @param string $ip
     * @return bool
     */
    public static function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
