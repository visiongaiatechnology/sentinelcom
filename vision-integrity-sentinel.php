<?php
/**
 * Plugin Name: VGT Sentinel CE
 * Description: A zero-trust Web Application Firewall (WAF) and security framework featuring robust brute-force protection, file integrity monitoring, and kernel-level system hardening.
 * Version: 1.6.0
 * Author: VisionGaiaTechnology
 * Author URI: https://visiongaiatechnology.de
 * License: AGPLv3
 * Requires PHP: 7.4
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- SYSTEM KONSTANTEN ---
define('VGTS_VERSION', '1.5.0');
define('VGTS_PATH', plugin_dir_path(__FILE__));
define('VGTS_URL', plugin_dir_url(__FILE__));
define('VGTS_SENTINEL_ICON', VGTS_URL . 'Sentinel.png');

// VAULT ARCHITEKTUR
$upload_dir = wp_upload_dir();
define('VGTS_VAULT_DIR', $upload_dir['basedir'] . '/vgts-vault-omega');
define('VGTS_MANIFEST_FILE', VGTS_VAULT_DIR . '/integrity_matrix.json');

define('VGTS_TABLE_BANS', 'vgts_apex_bans');
define('VGTS_TABLE_LOGS', 'vgts_omega_logs');

// --- INTELLIGENT AUTOLOADER ---
spl_autoload_register(function ($class) {
    if (strpos($class, 'VGTS_') !== 0) return;

    $map = [
        // Core Logic
        'VGTS_Network'               => 'includes/core/class-vis-network.php',
        
        // Security Modules
        'VGTS_Scanner_Engine'        => 'includes/scanner/class-vis-scanner-engine.php',
        'VGTS_Aegis'                 => 'includes/modules/aegis/class-vis-aegis.php',
        'VGTS_Titan'                 => 'includes/modules/titan/class-vis-titan.php',
        'VGTS_Hades'                 => 'includes/modules/hades/class-vis-hades.php',
        'VGTS_Oracle'                => 'includes/modules/oracle/class-vis-oracle.php',
        'VGTS_Chronos'               => 'includes/modules/chronos/class-vis-chronos.php',
        'VGTS_Ghost_Trap'            => 'includes/modules/trap/class-vis-ghost-trap.php',
        'VGTS_Airlock'               => 'includes/modules/airlock/class-vis-airlock.php',
        'VGTS_Cerberus'              => 'includes/modules/cerberus/class-vis-cerberus.php',
        'VGTS_Styx_Lite'             => 'includes/modules/styx/class-vis-styx-lite.php',
        'VGTS_Filesystem_Guard'      => 'includes/modules/filesystem/class-vis-filesystem-guard.php',
        'VGTS_Antibot'               => 'includes/modules/antibot/class-vis-antibot.php',
        
        // UI
        'VGTS_Dashboard_Core'        => 'includes/dashboard/class-vis-dashboard-core.php',
        'VGTS_Dashboard_View'        => 'includes/dashboard/class-vis-dashboard-view.php',
        
        // Compatibility Layer
        'VGTS_Compatibility_Manager' => 'includes/compatibility/class-vis-compatibility-manager.php',
    ];

    if (isset($map[$class]) && file_exists(VGTS_PATH . $map[$class])) {
        require_once VGTS_PATH . $map[$class];
    }
});

// --- ACTIVATION / SCHEMA ---
register_activation_hook(__FILE__, function() {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if (!file_exists(VGTS_VAULT_DIR)) {
        mkdir(VGTS_VAULT_DIR, 0755, true);
        file_put_contents(VGTS_VAULT_DIR . '/index.php', '<?php // SILENCE IS GOLDEN ?>');
        file_put_contents(VGTS_VAULT_DIR . '/.htaccess', "Order Deny,Allow\nDeny from all");
    }

    $charset_collate = $wpdb->get_charset_collate();
    
    $sql_bans = "CREATE TABLE " . $wpdb->prefix . VGTS_TABLE_BANS . " (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ip varchar(45) NOT NULL,
        reason text NOT NULL,
        banned_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        request_uri varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY ip (ip),
        KEY banned_at (banned_at)
    ) $charset_collate;";

    $sql_logs = "CREATE TABLE " . $wpdb->prefix . VGTS_TABLE_LOGS . " (
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
    wp_clear_scheduled_hook('vgts_hourly_scan_event');
    flush_rewrite_rules();
});

// --- BOOTSTRAP (VGT KERNEL PRIORITY QUEUE) ---
add_action('plugins_loaded', function() {
    
    // ==========================================
    // TIER 1: PERIMETER & PAYLOAD DEFENSE (CRITICAL)
    // ==========================================
    new VGTS_Cerberus();
    $options = get_option('vgts_config', []);
    new VGTS_Aegis($options); 

    // ==========================================
    // TIER 2: COMPATIBILITY LAYER
    // ==========================================
    new VGTS_Compatibility_Manager();

    // ==========================================
    // TIER 3: SECONDARY SECURITY MODULES & ENGINE FUSION
    // ==========================================
    new VGTS_Titan($options);
    new VGTS_Hades($options);
    new VGTS_Styx_Lite($options);
    new VGTS_Airlock();
    new VGTS_Ghost_Trap();
    new VGTS_Chronos();
    
    // V2 ARCHITECTURE: ANTIBOT PROOF-OF-WORK ENGINE
    new VGTS_Antibot($options);

    // ==========================================
    // TIER 4: ADMIN DASHBOARD
    // ==========================================
    if (is_admin()) {
        new VGTS_Dashboard_Core();
    }
    
}, -9999);
