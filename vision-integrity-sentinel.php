<?php
/**
 * Plugin Name: VGT Sentinel
 * Description: Community Edition
 * Version: 1.0.0 COMPATIBILITY LAYER
 * Author: VisionGaiaTechnology
 * Author URI: https://visiongaiatechnology.de
 * License: AGPLv3
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// --- SYSTEM KONSTANTEN ---
define('VIS_VERSION', '1.0.0');
define('VIS_PATH', plugin_dir_path(__FILE__));
define('VIS_URL', plugin_dir_url(__FILE__));

// VAULT ARCHITEKTUR
$upload_dir = wp_upload_dir();
define('VIS_VAULT_DIR', $upload_dir['basedir'] . '/vis-vault-omega');
define('VIS_MANIFEST_FILE', VIS_VAULT_DIR . '/integrity_matrix.json');

define('VIS_TABLE_BANS', 'vis_apex_bans');
define('VIS_TABLE_LOGS', 'vis_omega_logs');

// --- INTELLIGENT AUTOLOADER ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'VIS_') !== 0) return;

    $map = [
        // Core Modules
        'VIS_Scanner_Engine'        => 'includes/scanner/class-vis-scanner-engine.php',
        'VIS_Aegis'                 => 'includes/modules/aegis/class-vis-aegis.php',
        'VIS_Titan'                 => 'includes/modules/titan/class-vis-titan.php',
        'VIS_Hades'                 => 'includes/modules/hades/class-vis-hades.php',
        'VIS_Oracle'                => 'includes/modules/oracle/class-vis-oracle.php',
        'VIS_Chronos'               => 'includes/modules/chronos/class-vis-chronos.php',
        'VIS_Ghost_Trap'            => 'includes/modules/trap/class-vis-ghost-trap.php',
        'VIS_Airlock'               => 'includes/modules/airlock/class-vis-airlock.php',
        'VIS_Cerberus'              => 'includes/modules/cerberus/class-vis-cerberus.php',
        'VIS_Styx_Lite'             => 'includes/modules/styx/class-vis-styx-lite.php',
        'VIS_Filesystem_Guard'      => 'includes/modules/filesystem/class-vis-filesystem-guard.php',
        // UI
        'VIS_Dashboard_Core'        => 'includes/dashboard/class-vis-dashboard-core.php',
        'VIS_Dashboard_View'        => 'includes/dashboard/class-vis-dashboard-view.php',
        // Compatibility Layer (NEU)
        'VIS_Compatibility_Manager' => 'includes/compatibility/class-vis-compatibility-manager.php',
    ];

    if (isset($map[$class]) && file_exists(VIS_PATH . $map[$class])) {
        require_once VIS_PATH . $map[$class];
    }
});

// --- ACTIVATION / SCHEMA ---
register_activation_hook(__FILE__, function() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if (!file_exists(VIS_VAULT_DIR)) {
        mkdir(VIS_VAULT_DIR, 0755, true);
        file_put_contents(VIS_VAULT_DIR . '/index.php', '<?php // SILENCE IS GOLDEN ?>');
        file_put_contents(VIS_VAULT_DIR . '/.htaccess', "Order Deny,Allow\nDeny from all");
    }

    $charset_collate = $wpdb->get_charset_collate();
    
    $sql_bans = "CREATE TABLE " . $wpdb->prefix . VIS_TABLE_BANS . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ip varchar(45) NOT NULL,
        reason text NOT NULL,
        banned_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        request_uri varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY ip (ip),
        KEY banned_at (banned_at)
    ) $charset_collate;";

    $sql_logs = "CREATE TABLE " . $wpdb->prefix . VIS_TABLE_LOGS . " (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        module varchar(32) NOT NULL,
        type varchar(32) NOT NULL,
        message text NOT NULL,
        ip varchar(45) NOT NULL,
        severity tinyint(1) DEFAULT 1,
        PRIMARY KEY  (id),
        KEY module_timestamp (module, timestamp)
    ) $charset_collate;";

    dbDelta($sql_bans);
    dbDelta($sql_logs);

    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('vis_hourly_scan_event');
    flush_rewrite_rules();
});

// --- BOOTSTRAP ---
add_action('plugins_loaded', function() {
    $options = get_option('vis_config', []);

    // 1. Compatibility Layer (First Line)
    new VIS_Compatibility_Manager();

    // 2. Security Modules
    new VIS_Aegis($options);
    new VIS_Titan($options);
    new VIS_Hades($options);
    new VIS_Styx_Lite($options);
    new VIS_Cerberus();
    new VIS_Airlock();
    new VIS_Ghost_Trap();
    new VIS_Chronos();

    // 3. Admin UI
    if (is_admin()) {
        new VIS_Dashboard_Core();
    }
});