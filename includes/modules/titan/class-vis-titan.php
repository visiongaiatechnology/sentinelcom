<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: TITAN (The Strength) - OMEGA REVISION 2.1
 * Status: HARDENED
 * Updates: Blocks access to Vault and Version Control Systems via .htaccess
 */
class VIS_Titan {

    private $options;
    private $htaccess_marker = 'VisionGaia Titan Firewall';

    public function __construct($options) {
        $this->options = $options;

        if (empty($options['titan_enabled'])) return;

        add_action('send_headers', [$this, 'manage_headers']);
        add_filter('wp_headers', [$this, 'filter_wp_headers']);

        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        $this->enforce_protocols();
        add_action('init', [$this, 'block_sensitive_files']);

        // Cleanup
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');

        if (!empty($options['titan_disable_feeds'])) {
            $this->disable_feeds();
        }

        // Auto-Update .htaccess on Settings Save
        if (is_admin() && isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            $this->update_htaccess();
        }
    }

    public function manage_headers() {
        if (headers_sent()) return;

        header('X-XSS-Protection: 1; mode=block');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()'); // New: Feature Policy
        
        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        
        header_remove('X-Powered-By'); 
        header_remove('X-Pingback');

        switch ($fake_tech) {
            case 'laravel':
                header('X-Powered-By: Laravel'); break;
            case 'drupal':
                header('X-Powered-By: Drupal 9'); break;
            case 'django':
                header('X-Powered-By: Django/4.2'); break;
        }
    }

    public function filter_wp_headers($headers) {
        unset($headers['X-Pingback']);
        unset($headers['X-Powered-By']);
        return $headers;
    }

    private function enforce_protocols() {
        if (!empty($this->options['titan_block_xmlrpc'])) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('xmlrpc_methods', '__return_empty_array');
        }

        if (!empty($this->options['titan_block_rest']) && !is_user_logged_in()) {
            add_filter('rest_authentication_errors', function($result) {
                if (!empty($result)) return $result;
                if (!is_user_logged_in()) {
                    return new WP_Error('rest_forbidden', 'VisionGaia: REST API Restricted.', ['status' => 401]);
                }
                return $result;
            });
        }
    }

    private function disable_feeds() {
        $feeds = ['do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom'];
        foreach ($feeds as $feed) {
            add_action($feed, function() {
                wp_die('VisionGaia: Feeds disabled.', 'Security', ['response' => 403]);
            }, 1);
        }
    }

    public function block_sensitive_files() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = strtolower($_SERVER['REQUEST_URI']);
            // Extended Blacklist
            $patterns = ['debug.log', 'readme.html', '.git', '.env', 'wp-config.php', 'composer.json', 'vis-vault-omega'];
            
            foreach ($patterns as $p) {
                if (strpos($uri, $p) !== false) {
                    wp_die('TITAN SHIELD: Access Denied.', 'Titan', ['response' => 403]);
                }
            }
        }
    }

    private function update_htaccess() {
        $htaccess_path = ABSPATH . '.htaccess';
        if (!file_exists($htaccess_path) || !is_writable($htaccess_path)) return;

        $rules = $this->generate_htaccess_rules();
        $current_content = file_get_contents($htaccess_path);

        $start_marker = "# BEGIN " . $this->htaccess_marker;
        $end_marker   = "# END " . $this->htaccess_marker;

        $pattern = "/".preg_quote($start_marker, '/').".*?".preg_quote($end_marker, '/')."/s";
        $clean_content = preg_replace($pattern, '', $current_content);
        
        $new_content = $start_marker . "\n" . $rules . "\n" . $end_marker . "\n" . trim($clean_content);
        file_put_contents($htaccess_path, $new_content);
    }

    private function generate_htaccess_rules() {
        $rules = "";
        
        $rules .= "<IfModule mod_headers.c>\n";
        $rules .= "Header set X-XSS-Protection \"1; mode=block\"\n";
        $rules .= "Header set X-Frame-Options \"SAMEORIGIN\"\n";
        $rules .= "Header set X-Content-Type-Options \"nosniff\"\n";
        $rules .= "</IfModule>\n";

        $rules .= "Options -Indexes\n";

        // HARDENING: Block Sensitive Files Regex
        // Includes: .env, .git, .htaccess, composer.json, debug.log, and the Vault
        $rules .= "<FilesMatch \"^.*(error_log|wp-config\.php|php\.ini|\.[hH][tT]|composer\.json|\.env|\.git|vis-vault-omega)[a-zA-Z0-9_]*$\">\n";
        $rules .= "Order deny,allow\n";
        $rules .= "Deny from all\n";
        $rules .= "</FilesMatch>\n";

        if (!empty($this->options['titan_block_xmlrpc'])) {
            $rules .= "<Files xmlrpc.php>\n";
            $rules .= "Order Deny,Allow\n";
            $rules .= "Deny from all\n";
            $rules .= "</Files>\n";
        }

        return $rules;
    }
}