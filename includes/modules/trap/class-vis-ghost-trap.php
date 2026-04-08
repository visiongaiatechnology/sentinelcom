<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * MODULE: GHOST TRAP (The Bait)
 * Status: COMMUNITY STATUS (MULTI-VECTOR HONEYPOT)
 * Architecture: Deception Grid & Tarpitting
 * Logic: Platziert 5 hochattraktive, simulierte Vulnerability-Endpoints.
 * Der Zugriff führt zum atomaren Hard-Ban und einer psychologischen Täuschung (Mimicry).
 */
class VIS_Ghost_Trap {

    /**
     * VGT DOKTRIN: Die 5 Luxusgüter der Hacker.
     * Jeder automatisierte Scanner sucht nach diesen spezifischen Fehlkonfigurationen.
     */
    private array $baits = [
        '.env.backup',
        'wp-config.old.php',
        'db-dump-master.sql.php',
        'admin-shell-console.php',
        'debug-logs-temp.php'
    ];

    public function __construct() {
        add_action('admin_init', [$this, 'deploy_matrix']);
        // Priority 1: Sofortige Terminierung, bevor WP Core vollständig bootet
        add_action('plugins_loaded', [$this, 'check_trap_trigger'], 1);
    }

    /**
     * Streut das Deception-Grid in das Dateisystem (Idempotent).
     */
    public function deploy_matrix(): void {
        $root = wp_normalize_path(ABSPATH);

        foreach ($this->baits as $bait) {
            $path = $root . '/' . $bait;

            if (!file_exists($path)) {
                $this->forge_artifact($path, $bait);
            }
        }
    }

    /**
     * Generiert den Honeypot-Payload. Minimalistischer Footprint.
     */
    private function forge_artifact(string $path, string $bait_name): void {
        $wp_load = wp_normalize_path(ABSPATH . 'wp-load.php');
        
        // VGT HARDENING: Payload lädt nur das Nötigste, um den Core-Hook auszulösen
        $content = "<?php\n"
                 . "/** VGT OMEGA ARTIFACT */\n"
                 . "define('VIS_TRAP_ACTIVE', true);\n"
                 . "define('VIS_TRAP_VECTOR', '" . addslashes($bait_name) . "');\n"
                 . "require_once('{$wp_load}');\n";

        // File-Locking um Korruption durch parallele Prozesse zu verhindern
        @file_put_contents($path, $content, LOCK_EX);
        @chmod($path, 0644);
    }

    /**
     * Der Fang-Mechanismus (The Snap).
     * Feuert, sobald der Bot den Honeypot aufruft.
     */
    public function check_trap_trigger(): void {
        if (defined('VIS_TRAP_ACTIVE') && VIS_TRAP_ACTIVE) {
            
            global $wpdb;
            $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
            $vector = defined('VIS_TRAP_VECTOR') ? (string) VIS_TRAP_VECTOR : 'unknown_artifact';
            
            // 1. ATOMIC BAN PROTOCOL
            if (defined('VIS_TABLE_BANS')) {
                $table = $wpdb->prefix . VIS_TABLE_BANS;
                $reason = "GHOST TRAP TRIGGERED [VECTOR: {$vector}]";

                $wpdb->query($wpdb->prepare(
                    "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
                    $ip, 
                    $reason, 
                    current_time('mysql'), 
                    esc_url_raw( (string) ($_SERVER['REQUEST_URI'] ?? '/unknown') )
                ));
            }

            // 2. ALERTING (Asynchron)
            $admin_email = (string) get_option('admin_email');
            if (!empty($admin_email)) {
                wp_mail(
                    $admin_email, 
                    '[VGT APEX] TRAP SNAP: ' . $ip, 
                    "A malicious scanner ($ip) tried to access the honeypot artifact: $vector.\n\nThe IP has been permanently banned from the server."
                );
            }

            // 3. TARPITTING & MIMICRY (Anti-Forensik)
            // Verzögert die Antwort, um Threads des Angreifers zu binden
            usleep(random_int(400000, 900000)); 

            // Socket Drop: TCP Verbindung hart kappen (VGT Signature Move)
            if (!headers_sent()) {
                header('Connection: Close');
                header('X-Powered-By: PHP/5.4.16'); // Fake veralteten Header zur Verwirrung
                http_response_code(500); // Täusche einen internen Serverfehler vor
            }

            // Fake Stack-Trace um den Bot in Sicherheit zu wiegen
            die("<b>Fatal error</b>: Uncaught PDOException: SQLSTATE[HY000] [1040] Too many connections in /var/www/html/core/db.php:42\nStack trace:\n#0 {main}\n  thrown in <b>/var/www/html/core/db.php</b> on line <b>42</b>");
        }
    }
}
