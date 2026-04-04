<?php
if (!defined('ABSPATH')) exit;

class VIS_Dashboard_View {
    
    private $tabs = [
        'overview'   => ['icon' => 'dashicons-chart-area', 'label' => 'COMMAND CENTER'],
        'integrity'  => ['icon' => 'dashicons-search',     'label' => 'INTEGRITY MONITOR'],
        'aegis'      => ['icon' => 'dashicons-shield',     'label' => 'AEGIS FIREWALL'],
        'titan'      => ['icon' => 'dashicons-lock',       'label' => 'TITAN HARDENING'],
        'airlock'    => ['icon' => 'dashicons-upload',     'label' => 'AIRLOCK GUARD'],
        'filesystem' => ['icon' => 'dashicons-category',   'label' => 'DATENSICHERHEIT'],
        'hades'      => ['icon' => 'dashicons-hidden',     'label' => 'HADES STEALTH'],
        'styx'       => ['icon' => 'dashicons-networking', 'label' => 'STYX CONTROL'],
        'mudeployer' => ['icon' => 'dashicons-hammer',     'label' => 'MU DEPLOYER'], // NEU INJIZIERT
        'oracle'     => ['icon' => 'dashicons-visible',    'label' => 'ORACLE SCANNER'],
        'logs'       => ['icon' => 'dashicons-list-view',  'label' => 'SYSTEM LOGS'],
    ];

    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        // Whitelist Update: mudeployer ist bewusst NICHT in diesem Array, 
        // da es einen eigenen autonomen POST-Handler im View hat und keinen "CONFIG SAVE" Button oben braucht.
        $is_config_tab = in_array($active_tab, ['aegis', 'titan', 'hades', 'styx', 'airlock']);
        
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
        
        if (in_array($tab, ['aegis', 'titan', 'hades', 'styx', 'airlock'])) {
            echo '<button type="submit" name="vis_save_config" value="1" class="vis-btn vis-btn-primary">
                    <span class="dashicons dashicons-saved"></span> CONFIG SAVE
                  </button>';
        }
        echo '</header>';
    }
}
