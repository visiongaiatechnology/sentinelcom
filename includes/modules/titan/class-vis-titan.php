<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: TITAN (The Strength) - OMEGA REVISION 2.4
 * Status: PLATIN STATUS
 * Updates: Native Antibot Handshake - Dynamischer REST API Bypass.
 */
class VIS_Titan {

    private $options;
    private $htaccess_marker = 'VisionGaia Titan Firewall';

    public function __construct($options) {
        $this->options = $options;

        if (empty($options['titan_enabled'])) return;

        add_action('init', [$this, 'inject_global_headers'], 9999);
        add_filter('wp_headers', [$this, 'filter_wp_headers'], 9999);

        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        $this->enforce_protocols();
        add_action('init', [$this, 'block_sensitive_files']);

        add_action('init', [$this, 'source_cleanup'], 20);
        add_action('wp_head', [$this, 'inject_cms_meta'], 1);

        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        if (!empty($this->options['titan_hide_version']) || $fake_tech !== 'none') {
            add_filter('style_loader_src', [$this, 'remove_ver_string'], 9999);
            add_filter('script_loader_src', [$this, 'remove_ver_string'], 9999);
        }

        if (!empty($options['titan_disable_feeds'])) {
            $this->disable_feeds();
        }

        if (is_admin() && isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            $this->update_htaccess();
        }
    }

    public function inject_global_headers() {
        if (headers_sent()) return;

        header('X-XSS-Protection: 1; mode=block', true);
        header('X-Frame-Options: SAMEORIGIN', true);
        header('X-Content-Type-Options: nosniff', true);
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()', true);
        
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By'); 
            header_remove('X-Pingback');
        }

        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        if ($fake_tech !== 'none') {
            $map = [
                'laravel' => 'Laravel',
                'drupal'  => 'Drupal 9',
                'django'  => 'Django/4.2',
                'joomla'  => 'Joomla!'
            ];
            if (isset($map[$fake_tech])) {
                header('X-Powered-By: ' . $map[$fake_tech], true);
            }
        }
    }

    public function filter_wp_headers($headers) {
        if (isset($headers['X-Pingback'])) unset($headers['X-Pingback']);
        if (isset($headers['X-Powered-By'])) unset($headers['X-Powered-By']);
        
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Frame-Options']  = 'SAMEORIGIN';
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['Referrer-Policy']  = 'strict-origin-when-cross-origin';
        $headers['Permissions-Policy'] = 'geolocation=(), camera=(), microphone=()';
        
        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        if ($fake_tech !== 'none') {
            $map = [
                'laravel' => 'Laravel',
                'drupal'  => 'Drupal 9',
                'django'  => 'Django/4.2',
                'joomla'  => 'Joomla!'
            ];
            if (isset($map[$fake_tech])) {
                $headers['X-Powered-By'] = $map[$fake_tech];
            }
        }

        return $headers;
    }

    public function inject_cms_meta(): void {
        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        
        $meta = '';
        if ($fake_tech === 'drupal') {
            $meta = '<meta name="generator" content="Drupal 9 (https://www.drupal.org)" />' . "\n" .
                    '<meta name="MobileOptimized" content="width" />' . "\n" .
                    '<meta name="HandheldFriendly" content="true" />';
        } elseif ($fake_tech === 'joomla') {
            $meta = '<meta name="generator" content="Joomla! - Open Source Content Management" />';
        }

        if ($meta) {
            echo "\n<!-- VGT_OS Camouflage -->\n" . $meta . "\n";
        }
    }

    public function source_cleanup(): void {
        $actions = ['wp_generator', 'wlwmanifest_link', 'rsd_link', 'wp_shortlink_wp_head', 'rest_output_link_wp_head'];
        foreach ($actions as $action) remove_action('wp_head', $action);
        remove_action('template_redirect', 'rest_output_link_header', 11);

        if (!empty($this->options['titan_cleanup_emojis'])) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            add_filter('emoji_svg_url', '__return_false');
        }

        if (!empty($this->options['titan_cleanup_embeds'])) {
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            add_filter('embed_oembed_discover', '__return_false');
        }
    }

    public function remove_ver_string($src) {
        if (!is_string($src) || empty($src)) return $src;
        if (strpos($src, 'ver=') !== false) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    private function enforce_protocols() {
        if (!empty($this->options['titan_block_xmlrpc'])) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('xmlrpc_methods', '__return_empty_array');
        }

        if (!empty($this->options['titan_block_rest'])) {
            add_filter('rest_authentication_errors', function($result) {
                if (!empty($result)) return $result;
                
                // VGT SUPREME KERNEL FIX: Antibot API Handshake
                // Die PoW-Engine operiert asynchron und erfordert anonymen REST Zugang für den Challenge-Endpoint.
                $request_uri = $_SERVER['REQUEST_URI'] ?? '';
                if (!empty($this->options['antibot_enabled']) && strpos($request_uri, '/vis-antibot/v1/challenge') !== false) {
                    return $result; // Whitelist Pass-through
                }

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
        
        $new_content = $start_marker . "\n" . $rules . "\n" . $end_marker . "\n" . trim((string)$clean_content);
        file_put_contents($htaccess_path, $new_content);
    }

    private function generate_htaccess_rules() {
        $rules = "";
        
        $rules .= "<IfModule mod_headers.c>\n";
        $rules .= "Header set X-XSS-Protection \"1; mode=block\"\n";
        $rules .= "Header set X-Frame-Options \"SAMEORIGIN\"\n";
        $rules .= "Header set X-Content-Type-Options \"nosniff\"\n";
        $rules .= "Header set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
        $rules .= "Header set Permissions-Policy \"geolocation=(), camera=(), microphone=()\"\n";
        
        $rules .= "Header unset X-Powered-By\n";
        $rules .= "Header unset X-Pingback\n";
        
        $fake_tech = $this->options['titan_camouflage_mode'] ?? 'none';
        if ($fake_tech === 'laravel') {
            $rules .= "Header set X-Powered-By \"Laravel\"\n";
        } elseif ($fake_tech === 'drupal') {
            $rules .= "Header set X-Powered-By \"Drupal 9\"\n";
        } elseif ($fake_tech === 'django') {
            $rules .= "Header set X-Powered-By \"Django/4.2\"\n";
        } elseif ($fake_tech === 'joomla') {
            $rules .= "Header set X-Powered-By \"Joomla!\"\n";
        }
        $rules .= "</IfModule>\n\n";

        $rules .= "Options -Indexes\n";

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