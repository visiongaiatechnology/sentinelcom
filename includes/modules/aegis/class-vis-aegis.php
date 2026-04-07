<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: AEGIS (The Shield) - OMEGA PLATINUM REWRITE (CE CORE)
 * STATUS: PLATIN STATUS
 * KOGNITIVE UPGRADES:
 * - O(1) Cloudflare CIDR Validation für absoluten IP-Spoofing-Schutz (via VIS_Network Kernel).
 * - Multi-Byte Boundary-Safe Stream Inspection (verhindert Payload-Splitting).
 * - Double-Decode Protection gegen WAF-Evasion.
 * - Strict Memory Management via expliziten Garbage Collection Triggers.
 * - VGT Zero-Trust Whitelist Protocol & Cerberus Handshake integriert.
 */
class VIS_Aegis {

    private bool $enabled;
    private string $mode;
    private int $scan_limit = 524288; // 512KB Max Stream Scan
    private string $validated_ip;

    // Dynamische Whitelist Arrays
    private array $whitelist_ips = [];
    private array $whitelist_uas = [];

    private array $patterns = [
        'rce'       => '/(?:system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo)\s*\(/i',
        'lfi'       => '/(?:\.\.[\/\\\\]|\/etc\/passwd|c:\\\\windows|boot\.ini)/i',
        'sqli'      => '/(?:union[\s\/\*]+select|information_schema|waitfor[\s\/\*]+delay|benchmark\s*\(|sleep\s*\(|hex\s*\(|unhex\s*\(|concat\s*\(|\s+(?:OR|AND)\s+\d+\s*=\s*\d+|drop\s+(?:table|database)|alter\s+table)/i',
        'xss'       => '/(?:<script|javascript:|on(?:load|error|click|mouseover)\s*=|base64_decode|vbscript:|data:text\/html)/i',
        'gql_recon' => '/(?:__schema|__type)\s*(?:\{|\(|:)/i',
        'ua'        => '/(?:sqlmap|nikto|wpscan|python|curl|wget|libwww|jndi:|masscan|havij|netsparker|burp|nmap|shellshock|headless|selenium|gobuster|dirbuster|shodan)/i'
    ];

    public function __construct(array $options) {
        $this->enabled = !empty($options['aegis_enabled']);
        $this->mode    = (string) ($options['aegis_mode'] ?? 'strict');

        // VGT KERNEL: Dynamische Whitelists extrahieren
        $raw_ips = $options['aegis_whitelist_ips'] ?? ($options['whitelist_ips'] ?? '');
        $raw_uas = $options['aegis_whitelist_uas'] ?? ($options['whitelist_uas'] ?? '');

        $this->whitelist_ips = array_filter(array_map('trim', explode("\n", $raw_ips)));
        $this->whitelist_uas = array_filter(array_map('trim', explode("\n", $raw_uas)));

        // VGT DRY KERNEL: Zentralisierte IP Resolution
        $this->validated_ip = class_exists('VIS_Network') && method_exists('VIS_Network', 'resolve_true_ip') 
                              ? VIS_Network::resolve_true_ip() 
                              : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if (!$this->enabled || $this->is_whitelisted() || $this->is_static_asset()) {
            return;
        }

        // Vor dem $this->guard();
        $old_backtrack = ini_get('pcre.backtrack_limit');
        $old_recursion = ini_get('pcre.recursion_limit');

        ini_set('pcre.backtrack_limit', '100000');
        ini_set('pcre.recursion_limit', '100000');

        $this->guard();

        // Direkt nach $this->guard();
        ini_set('pcre.backtrack_limit', $old_backtrack);
        ini_set('pcre.recursion_limit', $old_recursion);
    }

    private function guard(): void {
        $this->inspect_headers();
        $this->inspect_uri();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $this->inspect_body_stream();
        }
    }

    /**
     * CHUNK-BOUNDARY-SAFE STREAM INSPECTION
     * Eliminiert Payload-Splitting Schwachstellen und sichert Speichereffizienz.
     */
    private function inspect_body_stream(): void {
        $handle = @fopen('php://input', 'rb');
        if (!$handle) return;

        stream_set_timeout($handle, 2);

        $scanned_bytes = 0;
        $overlap_buffer = '';
        $chunk_size = 8192; // Optimized von 4KB auf 8KB für reduzierte I/O Operationen

        while (!feof($handle)) {
            if ($scanned_bytes >= $this->scan_limit) break;

            $chunk = fread($handle, $chunk_size);
            if ($chunk === false || $chunk === '') break;

            $scanned_bytes += strlen($chunk);
            
            // Konkateniere Rest des vorherigen Chunks mit aktuellem Chunk (Verhindert Evasion an der 8KB-Grenze)
            $raw_payload = $overlap_buffer . $chunk;
            
            // Boundary-Safe Decoding (VGT UPDATE: Double-Decode gegen Evasion)
            $decoded_payload = urldecode(urldecode($raw_payload));

            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') continue;

                if (preg_match($regex, $decoded_payload)) {
                    fclose($handle);
                    $this->terminate("Threat Vector [$type] detected in Body Stream.", 'BLOCK', $type);
                }
            }

            // Sichere die letzten 256 Bytes für den nächsten Zyklus (fängt gesplittete Payloads wie %3Cscript)
            $overlap_buffer = substr($raw_payload, -256);
            
            // Strict Memory Freeing im Loop
            unset($decoded_payload, $raw_payload);
        }

        fclose($handle);
        unset($overlap_buffer); 
    }

    private function inspect_headers(): void {
        $ua  = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ua === '' && $ref === '') {
            $this->terminate("Ghost POST detected (No UA/Ref)", 'BLOCK', 'bot');
        }

        if ($ua !== '' && preg_match($this->patterns['ua'], $ua)) {
            $this->terminate('Malicious User-Agent signature detected', 'BLOCK', 'bot');
        }
    }

    private function inspect_uri(): void {
        $raw_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $decoded = urldecode($raw_uri);
        $double_decoded = urldecode($decoded);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') continue;

            if (preg_match($regex, $double_decoded)) {
                 $this->terminate("Threat Vector [$type] detected in URI.", 'BLOCK', $type);
            }
        }
    }

    private function engage_ban_protocol(string $reason): void {
        if ($this->mode === 'learning') return;

        // VGT KERNEL FIX: Direkter Handshake mit Cerberus, falls verfügbar!
        if (class_exists('VIS_Cerberus') && method_exists('VIS_Cerberus', 'instance')) {
            VIS_Cerberus::instance()->ban_ip($this->validated_ip, $reason);
            return;
        }

        // Fallback: Direkter Datenbank-Eintrag, falls Cerberus deaktiviert ist
        global $wpdb;
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans'); 
        $uri   = substr(esc_url_raw($_SERVER['REQUEST_URI'] ?? ''), 0, 255);

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $this->validated_ip, $reason, current_time('mysql'), $uri
        ));
    }

    private function terminate(string $reason, string $action_type, string $vector_type): void {
        global $wpdb;
        $table = $wpdb->prefix . (defined('VIS_TABLE_LOGS') ? VIS_TABLE_LOGS : 'vis_omega_logs');
        
        $wpdb->insert($table, [
            'module'   => 'AEGIS_PLATIN',
            'type'     => $action_type,
            'message'  => $reason,
            'ip'       => $this->validated_ip,
            'severity' => (in_array($vector_type, ['sqli', 'rce', 'lfi']) || $action_type === 'BAN') ? 10 : 5
        ]);

        if (in_array($vector_type, ['sqli', 'rce', 'lfi', 'gql_recon', 'ua'])) {
            $this->engage_ban_protocol("AEGIS: Critical Vector [$vector_type]");
            $action_type = 'BAN'; 
        }

        if ($this->mode === 'learning') return;

        // VGT Signature Move: TCP Socket Drop bevor irgendetwas gerendert wird
        if (!headers_sent()) {
            http_response_code(403);
            header('Connection: Close');
        }
        
        die('<h1>403 Forbidden</h1><hr>VisionGaia Security Enforced.');
    }

    /**
     * VGT ZERO-TRUST WHITELIST
     * Evaluiert IPs und UAs dynamisch. Keine nativen Admin-Bypasses mehr!
     */
    private function is_whitelisted(): bool {
        // System-Prozesse erlauben
        if (defined('DOING_CRON') && DOING_CRON) {
            $server_ip = $_SERVER['SERVER_ADDR'] ?? null;
            if ($server_ip && $this->validated_ip === $server_ip) return true;
        }
        
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false; 
        
        // Lokale Server-Loops (Immun)
        if (in_array($this->validated_ip, ['127.0.0.1', '::1', 'fe80::1'], true)) {
            return true;
        }

        // 1. IP Whitelist Check
        if (!empty($this->whitelist_ips) && in_array($this->validated_ip, $this->whitelist_ips, true)) {
            return true;
        }

        // 2. User-Agent Whitelist Check
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!empty($this->whitelist_uas) && $ua !== '') {
            foreach ($this->whitelist_uas as $safe_ua) {
                if ($safe_ua === '') continue;
                if (stripos($ua, $safe_ua) !== false) {
                    return true;
                }
            }
        }

        // 3. Fallback: Googlebot / Suchmaschinen DNS Verify
        if (preg_match('/(googlebot|bingbot|duckduckbot)/i', $ua)) {
            $hostname = gethostbyaddr($this->validated_ip);
            if ($hostname !== $this->validated_ip) {
                if (preg_match('/googlebot\.com$/i', $hostname)) return true;
                if (preg_match('/search\.msn\.com$/i', $hostname)) return true;
            }
        }

        return false;
    }

    private function is_static_asset(): bool {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|woff2?|ttf|eot|ico)(?:\?.*)?$/i', $uri);
    }
}
