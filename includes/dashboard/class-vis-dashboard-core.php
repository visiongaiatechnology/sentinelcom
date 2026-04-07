<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

class VIS_Dashboard_Core {
    private $page_hook;

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_vis_run_scan', [$this, 'ajax_scan']);
        add_action('wp_ajax_vis_approve_changes', [$this, 'ajax_approve']);
        add_action('wp_ajax_vis_dashboard_unban_ip', [self::class, 'handle_unban_ip']);
    }

    public function menu() {
        $this->page_hook = add_menu_page(
            'Sentinel', 'Sentinel', 'manage_options', 'vis-sentinel', 
            [new VIS_Dashboard_View(), 'render'], VIS_SENTINEL_ICON, 99
        );
    }

    public static function handle_unban_ip(): void {
        // Passe 'vis_dashboard_nonce' an deinen tatsächlich verwendeten Nonce-String an
        check_ajax_referer('vis_dashboard_nonce', 'nonce'); 
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('VGT SECURITY ALERT: Unauthorized access.');
        }

        $ip = sanitize_text_field($_POST['ip'] ?? '');
        if (empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP)) {
            wp_send_json_error('VGT KERNEL ERROR: Invalid IP format.');
        }

        global $wpdb;
        $table_bans = defined('VIS_TABLE_BANS') ? $wpdb->prefix . VIS_TABLE_BANS : $wpdb->prefix . 'vis_bans';
        
        $deleted = $wpdb->delete($table_bans, ['ip' => $ip]);
        
        if ($deleted !== false) {
            wp_send_json_success('IP unbanned.');
        } else {
            wp_send_json_error('VGT DB ERROR: Unban failed.');
        }
    }

    public function enqueue_assets($hook) {
        if ($hook !== $this->page_hook) return;
        wp_enqueue_style('vis-dashboard-css', VIS_URL . 'assets/css/vis-dashboard.css', [], VIS_VERSION);
        wp_enqueue_script('vis-dashboard-js', VIS_URL . 'assets/js/vis-dashboard.js', ['jquery'], VIS_VERSION, true);
        wp_localize_script('vis-dashboard-js', 'visConfig', [
            'nonce' => wp_create_nonce('vis_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ]);
    }

    public function save_settings() {
        if (isset($_POST['vis_save_config']) && check_admin_referer('vis_save_config')) {
            $current = get_option('vis_config', []);
            
            // VGT KERNEL: wp_unslash entfernt die automatischen WordPress Escape-Slashes aus dem POST-Payload
            $new     = isset($_POST['vis_config']) && is_array($_POST['vis_config']) ? wp_unslash($_POST['vis_config']) : [];
            $context = isset($_POST['vis_context']) ? sanitize_key($_POST['vis_context']) : 'all';

            // 1. CHEKCBOX HANDLING (Fehlende Checkboxen auf 0 setzen)
            $scope_map = [
                'aegis'   => ['aegis_enabled'],
                'titan'   => ['titan_enabled', 'titan_block_xmlrpc', 'titan_block_rest', 'titan_disable_feeds', 'titan_cleanup_emojis', 'titan_cleanup_embeds'],
                'hades'   => ['hades_enabled'],
                'styx'    => ['styx_kill_telemetry'],
                'airlock' => ['airlock_enabled']
            ];

            $checkboxes_to_check = $scope_map[$context] ?? [];
            if ($context === 'all') {
                $checkboxes_to_check = array_merge(...array_values($scope_map));
            }

            foreach ($checkboxes_to_check as $cb) {
                if (!isset($new[$cb])) {
                    $new[$cb] = 0;
                }
            }

            // 2. VGT SANITIZATION: Sicherstellen, dass Text/Select Eingaben strikt gereinigt werden
            if (isset($new['aegis_mode'])) {
                $new['aegis_mode'] = sanitize_key($new['aegis_mode']); // Erlaubt nur a-z, 0-9, -, _
            }
            if (isset($new['aegis_whitelist_ips'])) {
                $new['aegis_whitelist_ips'] = sanitize_textarea_field($new['aegis_whitelist_ips']);
            }
            if (isset($new['aegis_whitelist_uas'])) {
                $new['aegis_whitelist_uas'] = sanitize_textarea_field($new['aegis_whitelist_uas']);
            }

            $final_data = array_merge($current, $new);
            update_option('vis_config', $final_data);
            
            wp_redirect(add_query_arg('settings-updated', 'true', $_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public function ajax_scan() {
        if (!check_ajax_referer('vis_nonce', 'nonce', false) || !current_user_can('manage_options')) wp_send_json_error();
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $state  = isset($_POST['current_state']) ? (array)$_POST['current_state'] : [];
        if (!class_exists('VIS_Scanner_Engine')) require_once VIS_PATH . 'includes/scanner/class-vis-scanner-engine.php';
        $scanner = new VIS_Scanner_Engine();
        $result  = $scanner->perform_scan_batch($offset, $state);
        wp_send_json_success($result);
    }

    public function ajax_approve() {
        if (!check_ajax_referer('vis_nonce', 'nonce', false) || !current_user_can('manage_options')) wp_send_json_error();
        if (!class_exists('VIS_Scanner_Engine')) require_once VIS_PATH . 'includes/scanner/class-vis-scanner-engine.php';
        $scanner = new VIS_Scanner_Engine();
        $success = $scanner->regenerate_baseline();
        if ($success) wp_send_json_success(['message' => 'System Baseline re-indexed.']);
        else wp_send_json_error(['message' => 'Re-Index failed.']);
    }
}