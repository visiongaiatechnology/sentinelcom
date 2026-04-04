<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: STYX LITE (Outbound Telemetry Control)
 * Status: PLATIN
 * Logic: Kappt native WordPress-Telemetrie-Verbindungen auf Netzwerkebene.
 */
class VIS_Styx_Lite {

    private $kill_telemetry;

    public function __construct($options) {
        // Fallback: Telemetry Kill ist standardmäßig an für maximale Privacy
        $this->kill_telemetry = isset($options['styx_kill_telemetry']) ? $options['styx_kill_telemetry'] : 1;

        if ($this->kill_telemetry) {
            add_filter('pre_http_request', [$this, 'intercept_outbound_traffic'], 10, 3);
        }
    }

    public function intercept_outbound_traffic($preempt, $parsed_args, $url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        // Blockiere WP Core Telemetrie & API
        $blocked_domains = [
            'api.wordpress.org',
            'downloads.wordpress.org',
            's.w.org' // WP Stats
        ];

        if (in_array($host, $blocked_domains)) {
            // Simuliere einen Timeout / Block
            return new WP_Error(
                'vis_styx_blocked', 
                'VISIONGAIA STYX: Outbound telemetry to ' . $host . ' blocked by zero-trust policy.'
            );
        }

        return $preempt; // Lass den Request passieren
    }
}