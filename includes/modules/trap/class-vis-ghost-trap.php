<?php
if (!defined('ABSPATH')) exit;

class VIS_Ghost_Trap {
    private $trap_file = 'wp-admin-backup-restore.php'; // Attraktiver Name

    public function __construct() {
        add_action('admin_init', [$this, 'deploy']);
        add_action('plugins_loaded', [$this, 'check_access'], 1);
    }

    public function deploy() {
        $path = ABSPATH . $this->trap_file;
        if (!file_exists($path)) {
            // Die Falle lädt WP Core minimal, um den Hook zu feuern
            $content = "<?php define('VIS_TRAP_ACTIVE', true); require('" . wp_normalize_path(ABSPATH . 'wp-load.php') . "'); ?>";
            file_put_contents($path, $content);
        }
    }

    public function check_access() {
        if (defined('VIS_TRAP_ACTIVE')) {
            global $wpdb;
            $ip = $_SERVER['REMOTE_ADDR'];
            $table = $wpdb->prefix . VIS_TABLE_BANS;

            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO $table (ip, reason, banned_at) VALUES (%s, 'GHOST TRAP TRIGGERED', %s)",
                $ip, current_time('mysql')
            ));

            wp_mail(get_option('admin_email'), '[APEX] TRAP SNAP: ' . $ip, "IP $ip tried to access honeypot file.");
            
            header("HTTP/1.1 200 OK"); // Fake OK
            die("System restoring... Error 0x5501.");
        }
    }
}