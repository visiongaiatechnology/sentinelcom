<?php
if (!defined('ABSPATH')) exit;

/**
 * MODULE: AIRLOCK (APEX FUSION V2.1)
 * Status: PLATIN STATUS / AUTONOMOUS HEALING
 * Architecture: Strict Allowlisting & Multi-Stage Stream Inspection.
 */
class VIS_Airlock {

    private $enabled = false;
    private $max_memory_scan = 2097152; // 2 MB Threshold

    /**
     * @param array|null $options Zentrale VGT Konfigurations-Matrix (Optional für Fallback)
     */
    public function __construct($options = null) {
        // AUTONOMOUS HEALING: Falls Bootstrap die Options vergisst, holt das Modul sie selbst.
        if ($options === null) {
            $options = get_option('vis_config', []);
        }

        $this->enabled = !empty($options['airlock_enabled']);

        if ($this->enabled) {
            add_filter('wp_handle_upload_prefilter', [$this, 'inspect_payload'], 10, 1);
        }
    }

    /**
     * APEX INSPECTION ENGINE
     * Validiert Dateierweiterung und Scans Payload-Integrität.
     */
    public function inspect_payload($file) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // OMEGA PROTOCOL: STRICT ALLOWLIST (VGT Standard)
        $allowed_extensions = [
            // Images & Graphics
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif',
            // Documents & Data
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'rtf',
            // Media & Archives
            'mp3', 'mp4', 'mov', 'wav', 'avi', 'zip', 'rar'
        ];

        // 1. EXTENSION VALIDATION
        if (!in_array($ext, $allowed_extensions)) {
            $file['error'] = 'APEX AIRLOCK: File extension (' . strtoupper($ext) . ') rejected by zero-trust policy.';
            return $file;
        }

        // 2. DEEP CONTENT INSPECTION
        if (isset($file['tmp_name']) && file_exists($file['tmp_name'])) {
            $filesize = filesize($file['tmp_name']);

            // Optimization: Memory-Safe Read Strategy
            if ($filesize > $this->max_memory_scan) {
                // Chunked Stream Scan für große Dateien (O(1) Memory)
                $content = $this->read_boundary_chunks($file['tmp_name']);
            } else {
                // Full Scan für kleine Dateien
                $content = file_get_contents($file['tmp_name']);
            }

            // PATTERN MATCHING ENGINE (APEX FUSION)
            $patterns = [
                '/<\?php/i', 
                '/<\?=/i', 
                '/<script\s+language\s*=\s*[\"\']php[\"\']/i', 
                '/<\%/i', // ASP/JSP Tags
                '/base64_decode/i',
                '/eval\s*\(/i'
            ];

            foreach ($patterns as $regex) {
                if (preg_match($regex, $content)) {
                    $this->log_threat($file['name']);
                    $file['error'] = 'APEX AIRLOCK: Malicious code pattern detected in file payload. Integrity compromised.';
                    return $file;
                }
            }
        }

        return $file;
    }

    /**
     * BOUNDARY SCANNER: Liest Kopf (4KB) und Fuß (4KB) für Magic Byte Analyse.
     */
    private function read_boundary_chunks($filepath) {
        $handle = @fopen($filepath, 'rb');
        if (!$handle) return '';

        // Header Scan
        $header = fread($handle, 4096);
        
        // Footer Scan
        fseek($handle, -4096, SEEK_END);
        $footer = fread($handle, 4096);
        
        fclose($handle);
        return $header . $footer;
    }

    /**
     * Internes Telemetry-Logging für blockierte Angriffe
     */
    private function log_threat($filename) {
        if (!class_exists('VIS_Dashboard_Core')) return;
        
        error_log('[VISIONGAIA AIRLOCK] Neutralized Threat: ' . sanitize_file_name($filename) . ' from ' . $_SERVER['REMOTE_ADDR']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'vis_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'")) {
            $wpdb->insert($table, [
                'module'   => 'AIRLOCK',
                'type'     => 'BLOCK',
                'message'  => 'Malicious Payload in file: ' . $filename,
                'ip'       => $_SERVER['REMOTE_ADDR'],
                'severity' => 10
            ]);
        }
    }
}