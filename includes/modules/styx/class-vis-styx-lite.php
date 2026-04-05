<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: STYX LITE (Outbound Telemetry Control)
 * Status: PLATIN STATUS
 * Logic: Kappt native WordPress-Telemetrie auf Netzwerkebene durch Phantom-Responses.
 * Fix: Eliminierung von Core-Warnings im Debug-Log durch HTTP 200 Simulation.
 */
class VIS_Styx_Lite {

    private $kill_telemetry;

    public function __construct($options) {
        $this->kill_telemetry = isset($options['styx_kill_telemetry']) ? $options['styx_kill_telemetry'] : 1;

        if ($this->kill_telemetry) {
            // Priorität 999: STYX hat das letzte Wort vor dem tatsächlichen Netzwerk-Call
            add_filter('pre_http_request', [$this, 'intercept_outbound_traffic'], 999, 3);
        }
    }

    public function intercept_outbound_traffic($preempt, $parsed_args, $url) {
        $host = parse_url($url, PHP_URL_HOST);
        
        // VGT Zero-Trust Blacklist
        $blocked_domains = [
            'api.wordpress.org',
            'downloads.wordpress.org',
            's.w.org' // WP Stats & Telemetry
        ];

        if (in_array($host, $blocked_domains)) {
            
            // VGT OMEGA FIX: PHANTOM RESPONSE MATRIX
            // Anstatt einen Fehler (WP_Error) zu werfen, der das WP-Core-Log zumüllt,
            // füttern wir die WP-internen Funktionen mit einer perfekten, leeren API-Antwort.
            // Der Core denkt, die Kommunikation war erfolgreich und es gibt einfach "keine Updates".
            
            return [
                'headers'  => [],
                'body'     => json_encode([
                    'translations' => [], 
                    'update'       => [], 
                    'no_update'    => [],
                    'offers'       => []
                ]),
                'response' => [
                    'code'    => 200,
                    'message' => 'OK'
                ],
                'cookies'  => [],
                'filename' => null
            ];
        }

        // Andere Requests passieren lassen
        return $preempt; 
    }
}
