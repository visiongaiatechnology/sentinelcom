<?php
if (!defined('ABSPATH')) exit;

/**
 * BRIDGE: PAGE BUILDERS (Elementor, Divi, etc.)
 * Deaktiviert aggressive Sicherheitsfeatures während des Editierens,
 * um Konflikte im Frontend-Editor zu vermeiden.
 */
class VIS_Bridge_PageBuilders {

    public function __construct() {
        // Wenn wir im Editor-Modus sind, entschärfen wir das System
        if ($this->is_editing_mode()) {
            $this->disable_interventions();
        }
    }

    private function is_editing_mode() {
        // Elementor
        if (isset($_GET['elementor-preview'])) return true;
        
        // Divi
        if (isset($_GET['et_fb'])) return true;
        
        // Oxygen
        if (defined('SHOW_CT_BUILDER') && SHOW_CT_BUILDER) return true;
        
        // WP Customizer
        if (is_customize_preview()) return true;

        return false;
    }

    private function disable_interventions() {
        // Deaktiviere Hades Output Buffering (falls aktiv)
        // Hinweis: Hades v3 nutzt Filter, aber falls wir Output-Processing hinzufügen:
        add_filter('vis_hades_skip_buffer', '__return_true');

        // Deaktiviere Aegis HTML Injection (falls vorhanden)
        add_filter('vis_aegis_skip_injection', '__return_true');

        // Headers lockern (X-Frame-Options verhindern oft das Laden des Editors in iFrames)
        header_remove('X-Frame-Options');
        header('X-Frame-Options: SAMEORIGIN'); // Fallback, oft toleranter
    }
}