<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: ORACLE (The Sight)
 * Führt On-Demand System-Audits durch.
 * Optimiert auf maximale Informationsdichte und Erweiterbarkeit.
 */
class VIS_Oracle {

    public function run_prophecy() {
        $r = [];

        // 1. CONFIGURATION & FILESYSTEM
        $r[] = $this->check(is_writable(ABSPATH . 'wp-config.php'), 'Config Writeable', 'Critical: wp-config.php ist beschreibbar!', 'Secured (Read-Only).');
        $r[] = $this->check(file_exists(WP_CONTENT_DIR . '/debug.log'), 'Debug Log Exposure', 'debug.log ist öffentlich zugänglich.', 'Kein Log gefunden.');
        $r[] = $this->check(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT, 'File Editor Status', 'Editor deaktiviert (Sicher).', 'Editor aktiv (RCE Risiko).', true);

        // 2. DATABASE & USERS
        global $wpdb;
        $r[] = $this->check($wpdb->prefix === 'wp_', 'DB Prefix Hardening', 'Custom Prefix aktiv.', 'Standard "wp_" Prefix gefunden (Risk).', true);
        
        $admin_exists = get_user_by('login', 'admin');
        $r[] = $this->check($admin_exists, 'Default Admin User', 'User "admin" existiert (Brute-Force Ziel).', 'Kein Standard-Admin gefunden.');

        // 3. NETWORK & ENVIRONMENT
        $is_https = is_ssl();
        $r[] = $this->check($is_https, 'SSL/TLS Encryption', 'Verbindung verschlüsselt.', 'Unverschlüsselt (HTTP).', true);

        // 4. DIRECTORY LISTING CHECK
        $r[] = $this->check_directory_listing();

        // 5. REST API EXPOSURE
        $r[] = $this->check(isset($_SERVER['HTTP_AUTHORIZATION']), 'Auth Header Protection', 'Authorization Headers erkannt.', 'Headers fehlen (Mögliche API-Einschränkung).', true);

        return $r;
    }

    /**
     * Versucht zu prüfen, ob Directory Listing aktiv ist (simulierter Check)
     */
    private function check_directory_listing() {
        $upload_dir = wp_upload_dir();
        $target = $upload_dir['baseurl'];
        // In einer echten Umgebung würde man hier einen HTTP-Request absetzen.
        // Wir prüfen hier auf das Vorhandensein von Schutz-Dateien.
        $has_protection = file_exists(ABSPATH . 'wp-content/index.php');
        return $this->check($has_protection, 'Directory Browsing', 'Basisschutz aktiv.', 'Mögliches Directory Listing (index.php fehlt).', true);
    }

    /**
     * Zentraler Logic-Kernel für die Validierung
     * @param bool $condition Die zu prüfende Bedingung
     * @param string $name Name des Checks
     * @param string $msg_a Nachricht für Fall A
     * @param string $msg_b Nachricht für Fall B
     * @param bool $reverse Invertiert die Logik (True = Condition ist gut)
     */
    private function check($condition, $name, $msg_a, $msg_b, $reverse = false) {
        $fail = $reverse ? !$condition : $condition;
        return [
            'check'  => $name,
            'status' => $fail ? 'FAIL' : 'PASS',
            'msg'    => $fail ? $msg_a : $msg_b
        ];
    }
}