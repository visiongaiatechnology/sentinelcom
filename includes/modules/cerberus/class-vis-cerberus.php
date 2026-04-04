<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: CERBERUS (APEX FUSION V2.4)
 * Status: ACTIVE / ANTI-SPOOFING
 * Logic: Login Guard mit True-IP-Validation. Verhindert Header-Spoofing durch CIDR-Check.
 */
class VIS_Cerberus {

    private $max_retries = 3;
    private $lockout_time = 3600; // 1 Stunde

    // CLOUDFLARE IP RANGES (Stand: OMEGA PROTOCOL)
    private $cf_ipv4 = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
    ];

    public function __construct() {
        // Login Failure Hook
        add_action('wp_login_failed', [$this, 'handle_failed_login']);
        
        // Pre-Auth Check (Priority 1 = First Line of Defense)
        add_filter('authenticate', [$this, 'check_pre_auth'], 1, 3);
    }

    /**
     * Zählt Fehlversuche via High-Speed Transient (RAM/Object Cache)
     */
    public function handle_failed_login($username) {
        $ip = $this->get_validated_ip();
        
        $transient_name = 'vis_retries_' . md5($ip);
        $retries = get_transient($transient_name) ?: 0;
        $retries++;

        if ($retries >= $this->max_retries) {
            $this->ban_ip($ip, "CERBERUS: $retries fail-events (User: $username)");
            delete_transient($transient_name); 
        } else {
            set_transient($transient_name, $retries, $this->lockout_time);
        }
    }

    /**
     * Prüft VOR der Authentifizierung, ob die IP gebannt ist.
     */
    public function check_pre_auth($user, $username, $password) {
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $ip = $this->get_validated_ip();
        $table = $wpdb->prefix . VIS_TABLE_BANS;

        // O(1) Lookup
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ip = %s LIMIT 1", $ip));

        if ($id) {
            // Tarpit: Verzögerung gegen Timing-Attacks
            usleep(300000); 

            return new WP_Error(
                'vis_banned', 
                "<strong>VISIONGAIA CERBERUS:</strong> Access Denied. Your IP ($ip) is flagged active threat."
            );
        }

        return $user;
    }

    private function ban_ip($ip, $reason) {
        global $wpdb;
        $table = $wpdb->prefix . VIS_TABLE_BANS;

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $table (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $ip, $reason, current_time('mysql'), $_SERVER['REQUEST_URI'] ?? 'wp-login.php'
        ));
    }

    /**
     * TRUE IP VALIDATION ENGINE
     * Vertraut Headern NUR, wenn sie von vertrauenswürdigen Proxies kommen.
     */
    private function get_validated_ip() {
        $direct_ip = $_SERVER['REMOTE_ADDR'];

        // 1. Check if direct IP is Cloudflare
        if ($this->is_cloudflare($direct_ip)) {
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                return $_SERVER['HTTP_CF_CONNECTING_IP'];
            }
        }

        // 2. Fallback: Trust X-Forwarded-For ONLY if configured manually (Advanced Config)
        // Im Standard-Modus ignorieren wir XFF, um Spoofing zu verhindern,
        // es sei denn, der Server Admin whitelisted den Load Balancer.
        // if (defined('VIS_TRUSTED_PROXY') && $direct_ip === VIS_TRUSTED_PROXY) ...

        return $direct_ip;
    }

    private function is_cloudflare($ip) {
        // Schnell-Check für IPv4
        if (strpos($ip, ':') === false) {
            foreach ($this->cf_ipv4 as $cidr) {
                if ($this->ip_in_range($ip, $cidr)) return true;
            }
        }
        // IPv6 Support hier optional erweiterbar
        return false;
    }

    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) return $ip == $range;
        
        list($subnet, $bits) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;
        
        return ($ip_long & $mask) == $subnet_long;
    }
}