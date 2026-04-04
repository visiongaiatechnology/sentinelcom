<?php
if (!defined('ABSPATH')) exit;

/**
 * CORE: COMPATIBILITY MANAGER
 * Verwaltet die Interoperabilität mit dem VisionGaia-Ökosystem und Drittanbieter-Software.
 * Basiert auf dem Registry-Pattern für modulare Erweiterbarkeit.
 */
class VIS_Compatibility_Manager {

    private $bridges = [
        // VisionGaia Ecosystem
        'VisionLegalPro/vision-legal-pro.php' => 'VIS_Bridge_VisionLegalPro',
        
        // Page Builders (Verhindert Konflikte im Editor)
        'elementor/elementor.php'             => 'VIS_Bridge_PageBuilders',
        'divi-builder/divi-builder.php'       => 'VIS_Bridge_PageBuilders',
        'oxygen/functions.php'                => 'VIS_Bridge_PageBuilders',
        
        // Caching Systems (Optional für später)
        'wp-rocket/wp-rocket.php'             => 'VIS_Bridge_Cache',
    ];

    public function __construct() {
        // Wir laden die Bridges früh, aber nachdem WP Core bereit ist
        add_action('plugins_loaded', [$this, 'load_bridges'], 5);
    }

    public function load_bridges() {
        // Prüfe aktive Plugins und lade entsprechende Bridges
        foreach ($this->bridges as $plugin_path => $class_name) {
            if ($this->is_plugin_active($plugin_path)) {
                $this->load_bridge_class($class_name);
            }
        }

        // Builder-Check (Spezialfall für Themes, die Builder integriert haben)
        if ($this->is_builder_active()) {
            $this->load_bridge_class('VIS_Bridge_PageBuilders');
        }
    }

    private function load_bridge_class($class_name) {
        // Autoloader-Simulation für Bridges (liegen im Unterordner)
        $file_path = VIS_PATH . 'includes/compatibility/bridges/class-vis-bridge-' . strtolower(str_replace('VIS_Bridge_', '', $class_name)) . '.php';
        
        // Mapping für Kebab-Case Dateinamen
        $file_path = str_replace('visionlegalpro', 'vision-legal-pro', $file_path);
        $file_path = str_replace('pagebuilders', 'page-builders', $file_path);

        if (file_exists($file_path)) {
            require_once $file_path;
            if (class_exists($class_name)) {
                new $class_name();
            }
        }
    }

    private function is_plugin_active($plugin_path) {
        return in_array($plugin_path, (array) get_option('active_plugins', [])) || is_plugin_active_for_network($plugin_path);
    }

    private function is_builder_active() {
        // Erkennt aktive Editoren anhand von URL-Parametern
        return isset($_GET['elementor-preview']) || isset($_GET['et_fb']) || isset($_GET['ct_builder']);
    }
}