<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CERBERUS (The Gatekeeper)
 * STATUS: GOLD/PLATIN HYBRID (Shared Hosting Optimized)
 * KOGNITIVE UPGRADES:
 * - Filesystem State Tracking: Null DB-Hits während einer Brute-Force Welle.
 * - Anti-Timing Attack Sleep Delays bei positiven Ban-Hits.
 * - VGT FIX: Global Perimeter Lockdown (Blockiert die gesamte Website, nicht nur Login).
 */
class VIS_Cerberus {

    private int $max_retries = 3;
    private int $lockout_time = 3600; // 1 Stunde Lockout
    private string $vault_dir;

    public function __construct() {
        $this->vault_dir = defined('VIS_VAULT_DIR') ? VIS_VAULT_DIR : wp_upload_dir()['basedir'] . '/vis-vault-omega';

        // VGT KERNEL FIX: GLOBAL PERIMETER GUARD
        // Feuert bei JEDEM Seitenaufruf. Ist die IP in der DB, stirbt der Request sofort.
        add_action('plugins_loaded', [$this, 'enforce_global_perimeter'], 0);

        // Priority 1: Sofortiges Tarpitting/Blocking vor WP-Auth (Für gezielte Login-Angriffe)
        add_filter('authenticate', [$this, 'enforce_access_control'], 1, 3);
        
        // Tracking von fehlgeschlagenen Logins via Disk I/O
        add_action('wp_login_failed', [$this, 'register_auth_failure']);
    }

    /**
     * VGT GLOBAL PERIMETER LOCKDOWN
     * Schützt die komplette Website vor IP-Adressen, die von AEGIS oder Cerberus gebannt wurden.
     */
    public function enforce_global_perimeter(): void {
        if (defined('WP_CLI') && WP_CLI) return;
        if (defined('DOING_CRON') && DOING_CRON) return;

        global $wpdb;
        $ip = class_exists('VIS_Network') && method_exists('VIS_Network', 'resolve_true_ip') 
              ? VIS_Network::resolve_true_ip() 
              : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
              
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans');

        // O(1) Lookup: Existiert die IP in der Ban-Liste?
        $is_banned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE ip = %s LIMIT 1", $ip));

        if ($is_banned) {
            http_response_code(403);
            header('Connection: Close');
            die('<h1>403 Forbidden</h1><hr>VISIONGAIA CERBERUS: Access Denied. Your IP has been permanently banned from this network.');
        }
    }

    /**
     * ACCESS CONTROL & TARPITTING (Login Guard)
     */
    public function enforce_access_control($user, $username, $password) {
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $ip = class_exists('VIS_Network') ? VIS_Network::resolve_true_ip() : $_SERVER['REMOTE_ADDR'];
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans');

        $is_banned = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE ip = %s LIMIT 1", $ip));

        if ($is_banned) {
            // TARPITTING: Asymmetrische Verzögerung bindet Threads des Angreifers.
            usleep(random_int(400000, 900000)); 

            return new WP_Error(
                'vis_banned', 
                "<strong>VISIONGAIA CERBERUS:</strong> Access Denied. Integrity matrix compromised by origin IP ({$ip})."
            );
        }

        return $user;
    }

    /**
     * FILESYSTEM-BASED STATE TRACKING
     * Vermeidet den MySQL Max-Connection Tod auf Shared Hostings.
     */
    public function register_auth_failure(string $username): void {
        $ip = class_exists('VIS_Network') ? VIS_Network::resolve_true_ip() : $_SERVER['REMOTE_ADDR'];
        
        // Vault Absicherung
        if (!is_dir($this->vault_dir)) {
            @mkdir($this->vault_dir, 0755, true);
        }

        $state_file = $this->vault_dir . '/cerb_' . md5($ip) . '.dat';
        $current_time = time();
        $retries = 0;
        $last_attempt = 0;

        // Atomic Read
        if (file_exists($state_file)) {
            $data = explode(':', (string) @file_get_contents($state_file));
            if (count($data) === 2) {
                $last_attempt = (int) $data[0];
                $retries = (int) $data[1];
            }
        }

        // Lockout Zeit-Fenster evaluieren
        if (($current_time - $last_attempt) > $this->lockout_time) {
            $retries = 0; // Reset
        }

        $retries++;

        if ($retries >= $this->max_retries) {
            // Ausführung des Hard-Bans (Die einzige DB-Interaktion)
            $this->execute_hard_ban($ip, "CERBERUS: Authentication threshold exceeded (Target: {$username})");
            @unlink($state_file); // Cleanup State
        } else {
            // Atomic Write mit LOCK_EX um Concurrency-Corruption zu verhindern
            $payload = $current_time . ':' . $retries;
            @file_put_contents($state_file, $payload, LOCK_EX);
        }
    }

    /**
     * ATOMIC DB INSERT (Nur bei tatsächlichem Ban)
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
