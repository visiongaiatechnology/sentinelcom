<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * CORE: DASHBOARD VIEW CONTROLLER
 * Status: GOLD STATUS (HARDENED)
 * Modifikation: V2 Antibot Integration
 */
class VIS_Dashboard_View {
    
    private $tabs = [
        'overview'   => ['icon' => 'dashicons-chart-area', 'label' => 'COMMAND CENTER'],
        'threads'    => ['icon' => 'dashicons-hidden',     'label' => 'THREADS'],
        'integrity'  => ['icon' => 'dashicons-search',     'label' => 'INTEGRITY MONITOR'],
        'aegis'      => ['icon' => 'dashicons-shield',     'label' => 'AEGIS FIREWALL'],
        'antibot'    => ['icon' => 'dashicons-shield-alt', 'label' => 'ANTIBOT ENGINE'], // V2 ADDITION
        'cerberus'   => ['icon' => 'dashicons-shield',     'label' => 'CERBERUS BAN'],
        'titan'      => ['icon' => 'dashicons-lock',       'label' => 'TITAN HARDENING'],
        'mudeployer' => ['icon' => 'dashicons-admin-network', 'label' => 'MU-DEPLOYER'],
        'airlock'    => ['icon' => 'dashicons-upload',     'label' => 'AIRLOCK GUARD'],
        'filesystem' => ['icon' => 'dashicons-category',   'label' => 'DATENSICHERHEIT'],
        'hades'      => ['icon' => 'dashicons-hidden',     'label' => 'HADES STEALTH'],
        'styx'       => ['icon' => 'dashicons-networking', 'label' => 'STYX CONTROL'],
        'oracle'     => ['icon' => 'dashicons-list-view',  'label' => 'ORACLE SCANNER'],
        'console'    => ['icon' => 'dashicons-editor-code','label' => 'VGT CONSOLE'],
        'logs'       => ['icon' => 'dashicons-list-view',  'label' => 'SYSTEM LOGS'],
    ];

    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        $is_config_tab = in_array($active_tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot']);
        
        $opt = get_option('vis_config', []); 
        
        echo '<div class="vis-omega-wrapper">';
        require VIS_PATH . 'includes/dashboard/views/view-sidebar.php'; 

        echo '<main class="vis-content">';
        do_action('vis_dashboard_before_render');
        
        if ($is_config_tab) {
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="vis_context" value="' . $active_tab . '">';
            wp_nonce_field('vis_save_config');
        }

        $this->render_header($active_tab);
        
        $file = VIS_PATH . 'includes/dashboard/views/view-' . $active_tab . '.php';
        
        echo '<div class="vis-view-animate">';
        if (file_exists($file)) {
            require $file;
        } else {
            require VIS_PATH . 'includes/dashboard/views/view-overview.php';
        }
        echo '</div>';
        
        if ($is_config_tab) {
            echo '</form>';
        }
        
        echo '</main></div>';
    }

    private function render_header($tab) {
        $label = $this->tabs[$tab]['label'] ?? 'MODULE';
        $icon  = $this->tabs[$tab]['icon'] ?? 'dashicons-admin-generic';
        
        echo '<header class="vis-topbar">
                <div class="vis-header-title">
                    <span class="vis-header-icon dashicons ' . $icon . '"></span>
                    <h1>' . $label . '</h1>
                </div>';
        
        if (in_array($tab, ['aegis', 'titan', 'hades', 'styx', 'airlock', 'mudeployer', 'antibot'])) {
            echo '<button type="submit" name="vis_save_config" value="1" class="vis-btn vis-btn-primary">
                    <span class="dashicons dashicons-saved"></span> CONFIG SAVE
                  </button>';
        }
        echo '</header>';
    }
}
