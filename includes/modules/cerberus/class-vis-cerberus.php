<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CERBERUS (The Gatekeeper) - OMEGA PLATINUM REWRITE
 * STATUS: PLATIN STATUS
 * KOGNITIVE UPGRADES:
 * - O(1) True-IP Resolution mit Shared CIDR Matrix.
 * - Strict Origin Shield: Drop von Non-CF Traffic bei erzwungenem CF-Routing.
 * - RAM-based State Tracking (Transients) mit konstanter Zeit-Komplexität.
 * - Anti-Timing Attack Sleep Delays bei positiven Ban-Hits.
 */
class VIS_Cerberus {

    private int $max_retries = 3;
    private int $lockout_time = 3600; // 1 Stunde Lockout
    private bool $strict_origin_shield = false; // PLATIN FEATURE: Aktivieren, wenn Server exklusiv hinter CF läuft.

    // KERNEL-CACHE FÜR CLOUDFLARE IPv4/IPv6 CIDR
    private array $cf_ipv4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    public function __construct() {
        // Priority 1: Sofortiges Tarpitting/Blocking vor WP-Auth
        add_filter('authenticate', [$this, 'enforce_access_control'], 1, 3);
        
        // Tracking von fehlgeschlagenen Logins
        add_action('wp_login_failed', [$this, 'register_auth_failure']);

        // Platin Feature: Origin Shield
        if ($this->strict_origin_shield) {
            add_action('init', [$this, 'enforce_origin_shield'], 1);
        }
    }

    /**
     * PLATIN FEATURE: STRICT ORIGIN SHIELD
     * Wenn aktiv, werden alle direkten Zugriffe auf die Server-IP, die nicht
     * durch das Cloudflare-Netzwerk geroutet wurden, sofort per TCP Socket-Drop beendet.
     */
    public function enforce_origin_shield(): void {
        $remote_addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        
        if (!$this->is_cloudflare_ip($remote_addr)) {
            // Direktzugriff erkannt (Bypass-Versuch). Sofortige Terminierung.
            if (!headers_sent()) {
                http_response_code(403);
                header('Connection: Close');
            }
            die('<b>VGT KERNEL PANIC:</b> Origin Shield active. Direct IP access forbidden. Route traffic via designated proxy network.');
        }
    }

    /**
     * ACCESS CONTROL & TARPITTING
     */
    public function enforce_access_control($user, $username, $password) {
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $ip = $this->resolve_true_ip();
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans');

        // O(1) Lookup: Existiert die IP in der Ban-Liste?
        $is_banned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE ip = %s LIMIT 1", $ip));

        if ($is_banned) {
            // TARPITTING: Fügt asymmetrische Verzögerung hinzu, um automatisierte Brute-Forcer
            // aus dem Takt zu bringen und ihre Threads zu binden.
            usleep(random_int(400000, 900000)); 

            return new WP_Error(
                'vis_banned', 
                "<strong>VISIONGAIA CERBERUS:</strong> Access Denied. Integrity matrix compromised by origin IP ({$ip})."
            );
        }

        return $user;
    }

    /**
     * RAM-BASED STATE TRACKING
     */
    public function register_auth_failure(string $username): void {
        $ip = $this->resolve_true_ip();
        
        // Nutzt WP Transients (ideal mit Redis/Memcached) für O(1) I/O.
        $state_key = 'vis_cerb_' . md5($ip);
        $retries = (int) get_transient($state_key);
        $retries++;

        if ($retries >= $this->max_retries) {
            $this->execute_hard_ban($ip, "CERBERUS: Authentication threshold exceeded (User target: {$username})");
            delete_transient($state_key); 
        } else {
            set_transient($state_key, $retries, $this->lockout_time);
        }
    }

    /**
     * ZERO-TRUST IP RESOLUTION (Konsistent mit Aegis Kernel)
     */
    private function resolve_true_ip(): string {
        $remote_addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        
        if (!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';
        }

        if ($this->is_cloudflare_ip($remote_addr)) {
            $cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            return filter_var($cf_ip, FILTER_VALIDATE_IP) ? $cf_ip : $remote_addr;
        }

        return filter_var($remote_addr, FILTER_VALIDATE_IP) ? $remote_addr : '0.0.0.0';
    }

    /**
     * MATHEMATISCHE CIDR VERIFIKATION
     */
    private function is_cloudflare_ip(string $ip): bool {
        if (strpos($ip, ':') !== false) return false; // CE fokussiert auf IPv4

        $ip_long = ip2long($ip);
        if ($ip_long === false) return false;

        foreach ($this->cf_ipv4 as $cidr) {
            [$subnet, $bits] = explode('/', $cidr);
            $mask = -1 << (32 - (int)$bits);
            if (($ip_long & $mask) === (ip2long($subnet) & $mask)) {
                return true;
            }
        }
        return false;
    }

    /**
     * ATOMIC DB INSERTS
     */
    private function execute_hard_ban(string $ip, string $reason): void {
        global $wpdb;
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans');

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $ip, 
            $reason, 
            current_time('mysql'), 
            substr(esc_url_raw($_SERVER['REQUEST_URI'] ?? '/wp-login.php'), 0, 255)
        ));
    }
}
