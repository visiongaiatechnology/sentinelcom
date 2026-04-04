<?php
if (!defined('ABSPATH')) exit;

/**
 * BRIDGE: VISION LEGAL PRO
 * Stellt sicher, dass Privacy-Banner und Shadow-Net-Assets
 * die von Hades maskierten Pfade nutzen.
 */
class VIS_Bridge_VisionLegalPro {

    public function __construct() {
        // Hook in den Output Buffer, falls Hades aktiv ist
        $opt = get_option('vis_config', []);
        if (!empty($opt['hades_enabled'])) {
            add_action('template_redirect', [$this, 'start_buffer_patch'], 999);
        }
    }

    public function start_buffer_patch() {
        // Nicht im Admin oder bei AJAX/API Requests
        if (is_admin() || wp_doing_ajax() || defined('REST_REQUEST')) return;

        ob_start([$this, 'rewrite_vlp_paths']);
    }

    /**
     * Ersetzt die physischen Upload-Pfade von VLP durch Hades-Aliase.
     */
    public function rewrite_vlp_paths($buffer) {
        if (empty($buffer)) return $buffer;

        // Hole die echten Pfade (so wie VLP sie nutzt)
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl']; // z.B. .../wp-content/uploads
        
        // Definiere Hades Mapping (manuell, da Hades Config private ist oder nicht direkt zugreifbar)
        // Wir gehen vom Standard 'storage' aus, prüfen aber die Map später dynamisch wenn möglich.
        // Für OMEGA v3.0 ist 'storage' der Standard für Uploads.
        
        $hades_upload_alias = 'storage'; 
        
        // Suche: .../wp-content/uploads/vgt-shadow-net
        $search = $base_url . '/vgt-shadow-net';
        
        // Ersetze: .../storage/vgt-shadow-net
        $replace = str_replace('wp-content/uploads', $hades_upload_alias, $search);

        // Führe Replacement durch
        $buffer = str_replace($search, $replace, $buffer);

        return $buffer;
    }
}