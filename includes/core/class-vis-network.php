<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * CORE: NETWORK UTILITIES
 * Status: PLATIN STATUS
 * Zentralisierte O(1) IP-Resolution und CIDR-Validation.
 */
class VIS_Network {
    
    // KERNEL-CACHE FÜR CLOUDFLARE IPv4 CIDR (Hardcoded für O(1) Lookup Speed)
    private static array $cf_ipv4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    /**
     * Ermittelt die echte IP-Adresse und verifiziert Cloudflare-Header mathematisch.
     */
    public static function resolve_true_ip(): string {
        $remote_addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        
        if (!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';
        }

        if (self::is_cloudflare_ip($remote_addr)) {
            $cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            return filter_var($cf_ip, FILTER_VALIDATE_IP) ? $cf_ip : $remote_addr;
        }

        // Spoofing-Versuch: Fallback auf physische Remote-IP
        return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';
    }

    /**
     * Verifiziert, ob eine IP zum Cloudflare-Netzwerk gehört via Bitwise CIDR Check.
     */
    public static function is_cloudflare_ip(string $ip): bool {
        if (strpos($ip, ':') !== false) return false; // CE operiert primär auf IPv4 Vektoren

        $ip_long = ip2long($ip);
        if ($ip_long === false) return false;

        foreach (self::$cf_ipv4 as $cidr) {
            [$subnet, $bits] = explode('/', $cidr);
            $mask = -1 << (32 - (int)$bits);
            if (($ip_long & $mask) === (ip2long($subnet) & $mask)) {
                return true;
            }
        }
        return false;
    }
}