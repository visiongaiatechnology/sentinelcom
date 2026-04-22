<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: DASHBOARD VIEW ENGINE
 * STATUS: DIAMANT SUPREME (WP.ORG COMPLIANT)
 * KOGNITIVE UPGRADES:
 * - [WP.ORG FIXED]: Static i18n strings for all module labels.
 * - [WP.ORG FIXED]: Strict Output Escaping (esc_attr, esc_html).
 * - [WP.ORG FIXED]: Safe Superglobal Access via wp_unslash.
 * - Architecture: Modular Tab-based Rendering with VGT State-of-the-Art UI.
 * - Fix: Deterministic Variable Injection for Sidebar State.
 */
class VGTS_Dashboard_View {
    
    /**
     * @return array[] Die Tab-Definitionen mit lokalisierten Labels.
     */
    private function get_tabs(): array {
        return [
            'overview'   => ['icon' => 'dashicons-chart-area',    'label' => __('COMMAND CENTER', 'vgt-sentinel-ce')],
            'threads'    => ['icon' => 'dashicons-hidden',        'label' => __('THREADS', 'vgt-sentinel-ce')],
            'integrity'  => ['icon' => 'dashicons-search',        'label' => __('INTEGRITY MONITOR', 'vgt-sentinel-ce')],
            'aegis'      => ['icon' => 'dashicons-shield',        'label' => __('AEGIS FIREWALL', 'vgt-sentinel-ce')],
            'antibot'    => ['icon' => 'dashicons-shield-alt',    'label' => __('ANTIBOT ENGINE', 'vgt-sentinel-ce')],
            'cerberus'   => ['icon' => 'dashicons-shield',        'label' => __('CERBERUS BAN', 'vgt-sentinel-ce')],
            'titan'      => ['icon' => 'dashicons-lock',          'label' => __('TITAN HARDENING', 'vgt-sentinel-ce')],
            'mudeployer' => ['icon' => 'dashicons-admin-network', 'label' => __('MU-DEPLOYER', 'vgt-sentinel-ce')],
            'airlock'    => ['icon' => 'dashicons-upload',        'label' => __('AIRLOCK GUARD', 'vgt-sentinel-ce')],
            'filesystem' => ['icon' => 'dashicons-category',      'label' => __('FILE SECURITY', 'vgt-sentinel-ce')],
            'hades'      => ['icon' => 'dashicons-hidden',        'label' => __('HADES STEALTH', 'vgt-sentinel-ce')],
            'styx'       => ['icon' => 'dashicons-networking',    'label' => __('STYX CONTROL', 'vgt-sentinel-ce')],
            'oracle'     => ['icon' => 'dashicons-list-view',     'label' => __('ORACLE SCANNER', 'vgt-sentinel-ce')],
            'console'    => ['icon' => 'dashicons-editor-code',   'label' => __('VGT CONSOLE', 'vgt-sentinel-ce')],
            'logs'       => ['icon' => 'dashicons-list-view',     'label' => __('SYSTEM LOGS', 'vgt-sentinel-ce')],
        ];
    }

    /**
     * Haupt-Render-Methode für das Sentinel Dashboard.
     */
    public function render(): void {
        // [WP.ORG COMPLIANCE]: Safe Access to $_GET
        $tabs = $this->get_tabs();
        $active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'overview';
        
        // Falls der Tab nicht existiert, Fallback auf Overview
        if (!isset($tabs[$active_tab])) {
            $active_tab = 'overview';
        }

        $is_config_tab = in_array($active_tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot'], true);
        
        // Wrapper Start
        echo '<div class="vgts-omega-wrapper">';
        
        // Sidebar Inclusion (State Injection)
        $sidebar_path = VGTS_PATH . 'includes/dashboard/views/view-sidebar.php';
        if (file_exists($sidebar_path)) {
            // Wir injizieren $tabs und $active_tab direkt, damit die Sidebar keinen
            // illegalen State-Access auf $this->tabs versuchen muss.
            require $sidebar_path;
        }

        echo '<main class="vgts-content">';
        
        // Custom Hook für Erweiterungen
        do_action('vgts_dashboard_before_render');
        
        // Form-Wrapper für Konfigurations-Tabs
        if ($is_config_tab) {
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="vgts_context" value="' . esc_attr($active_tab) . '">';
            wp_nonce_field('vgts_save_config');
        }

        $this->render_header($active_tab, $tabs);
        
        // View Routing
        $view_file = VGTS_PATH . 'includes/dashboard/views/view-' . $active_tab . '.php';
        
        echo '<div class="vgts-view-animate">';
        if (file_exists($view_file)) {
            require $view_file;
        } else {
            $overview_file = VGTS_PATH . 'includes/dashboard/views/view-overview.php';
            if (file_exists($overview_file)) {
                require $overview_file;
            }
        }
        echo '</div>';
        
        if ($is_config_tab) {
            echo '</form>';
        }
        
        echo '</main></div>';
    }

    /**
     * Rendert die obere Bar des Dashboards mit Titeln und Action-Buttons.
     * @param string $tab Aktueller Tab-Slug
     * @param array $tabs Tab-Definitionen
     */
    private function render_header(string $tab, array $tabs): void {
        $label = $tabs[$tab]['label'] ?? __('MODULE', 'vgt-sentinel-ce');
        $icon  = $tabs[$tab]['icon'] ?? 'dashicons-admin-generic';
        
        echo '<header class="vgts-topbar">
                <div class="vgts-header-title">
                    <span class="vgts-header-icon dashicons ' . esc_attr($icon) . '"></span>
                    <h1>' . esc_html($label) . '</h1>
                </div>';
        
        // Speicher-Button für Config-Tabs einblenden
        if (in_array($tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot'], true)) {
            echo '<button type="submit" name="vgts_save_config" value="1" class="vgts-btn vgts-btn-primary">
                    <span class="dashicons dashicons-saved"></span> ' . esc_html__('SAVE CONFIG', 'vgt-sentinel-ce') . '
                  </button>';
        }
        echo '</header>';
    }
}