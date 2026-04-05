<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: STYX LITE (Outbound Telemetry Control)
 * Status: DIAMANT VGT SUPREME
 * Logic: Kappt native WordPress-Telemetrie auf Netzwerkebene durch struktur-perfekte Phantom-Responses.
 * Fix: Eliminierung von Core-Warnings und Fatal Errors (array_walk) durch exaktes Payload-Matching.
 */
class VIS_Styx_Lite {

    private $kill_telemetry;

    public function __construct($options) {
        $this->kill_telemetry = isset($options['styx_kill_telemetry']) ? $options['styx_kill_telemetry'] : 1;

        if ($this->kill_telemetry) {
            // Priorität 999: STYX hat das absolute letzte Wort vor dem tatsächlichen Netzwerk-Call.
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
            
            // VGT OMEGA FIX: PHANTOM RESPONSE MATRIX 2.0 (APEX STATE)
            // Der WP Core erwartet bei API Calls strikt definierte JSON-Strukturen. 
            // Fehlen die Keys 'plugins' oder 'themes', wirft der Core Fatal Errors bei array_walk().
            // Wir injizieren ein perfektes, leeres Datenmodell. Der Core interpretiert dies als "System ist aktuell".
            
            $mock_body = json_encode([
                'plugins'      => [], // Zwingend erforderlich für /plugins/update-check/
                'themes'       => [], // Zwingend erforderlich für /themes/update-check/
                'translations' => [], // Zwingend für Translations-Updates
                'update'       => [], // Legacy / Fallback
                'no_update'    => [], // Legacy / Fallback
                'offers'       => []  // Zwingend erforderlich für /core/version-check/
            ]);

            return [
                'headers'  => [],
                'body'     => $mock_body,
                'response' => [
                    'code'    => 200,
                    'message' => 'OK'
                ],
                'cookies'  => [],
                'filename' => null
            ];
        }

        // Nicht-Telemetrie-Traffic ungehindert passieren lassen
        return $preempt; 
    }
}
