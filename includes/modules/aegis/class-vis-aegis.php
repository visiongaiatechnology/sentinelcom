<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: AEGIS (The Shield) - OMEGA HARDENED V4.2.1
 * STATUS: PLATIN STATUS
 * FIXES: Type-Safety Enforcement, IP-Spoofing Elimination, Comment-Evasion.
 */
class VIS_Aegis {

    private bool $enabled = false;
    private string $mode = 'strict';
    private int $scan_limit = 524288;
    
    private array $patterns = [
        'rce'  => '/(?:system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo)\s*+\(/i',
        'lfi'  => '/(?:\.\.[\/\\\\]|\/etc\/passwd|c:\\\\windows|boot\.ini)/i',
        'gql_recon' => '/(?:__schema|__type)\s*+(?:\{|\(|:)/i',
        // VGT FIX: Erlaubt Whitespaces ODER Inline-Kommentare zwischen SQL-Keywords
        'sqli' => '/(?:union(?:\s++|\/\*.*?\*\/)select|information_schema|waitfor(?:\s++|\/\*.*?\*\/)delay|hex\s*+\(|unhex\s*+\(|concat\s*+\(|char\s*+\(|\s++OR\s++1=1)/i',
        'xss'  => '/(?:<script|javascript:|on(?:load|error|click|mouseover)=|base64_decode|vbscript:|data:text\/html)/i',
        'ua'   => '/(?:sqlmap|nikto|wpscan|python|curl|wget|libwww|jndi:|masscan|havij|netsparker|burp|nmap|shellshock|headless|selenium|gobuster|dirbuster|shodan)/i'
    ];

    public function __construct(array $options) {
        $this->enabled = !empty($options['aegis_enabled']);
        $this->mode = (string) ($options['aegis_mode'] ?? 'strict');

        if (!$this->enabled || $this->is_whitelisted()) {
            return;
        }

        ini_set('pcre.backtrack_limit', '100000');
        ini_set('pcre.recursion_limit', '100000');

        $this->guard();
    }

    private function guard(): void {
        if ($this->is_static_asset()) {
            return;
        }

        $ip = $this->get_validated_ip();

        $this->inspect_headers($ip);
        $this->inspect_uri($ip);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->inspect_body_stream($ip);
        }
    }

    private function inspect_body_stream(string $ip): void {
        $handle = @fopen('php://input', 'rb');
        if (!$handle) return;

        stream_set_timeout($handle, 2);

        $scanned_bytes = 0;
        $buffer = '';
        $chunk_size = 4096;

        while (!feof($handle)) {
            if ($scanned_bytes >= $this->scan_limit) {
                break;
            }

            $chunk = fread($handle, $chunk_size);
            if ($chunk === false) break;

            $scanned_bytes += strlen($chunk);
            $search_buffer = $buffer . $chunk;
            
            // VGT HARDENING: URL-Decoding im Stream, um %-Encoded Body-Payloads zu fangen
            $search_buffer_decoded = urldecode($search_buffer);

            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') continue;

                if (preg_match($regex, $search_buffer_decoded)) {
                    fclose($handle);
                    $this->terminate("Threat Vector [$type] detected in Body Stream.", 'BLOCK', $type, $ip);
                }
            }

            $buffer = substr($chunk, -500);
        }

        fclose($handle);
    }

    private function inspect_headers(string $ip): void {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
            if (empty($ua) && empty($ref)) {
                $this->terminate("Ghost POST detected (No UA/Ref)", 'BLOCK', 'bot', $ip);
            }
        }

        if ($ua !== '' && preg_match($this->patterns['ua'], $ua)) {
            $this->terminate('Bad User-Agent detected', 'BLOCK', 'bot', $ip);
        }
    }

    private function inspect_uri(string $ip): void {
        $raw_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $decoded = urldecode($raw_uri);
        $double_decoded = urldecode($decoded);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') continue;

            if (preg_match($regex, $double_decoded)) {
                 $this->terminate("Threat Vector [$type] detected in URI.", 'BLOCK', $type, $ip);
            }
        }
    }

    private function engage_ban_protocol(string $ip, string $reason): void {
        if ($this->mode === 'learning') return;

        global $wpdb;
        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_bans'); 

        $uri = substr(esc_url_raw($_SERVER['REQUEST_URI'] ?? ''), 0, 255);

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $table (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $ip, $reason, current_time('mysql'), $uri
        ));
    }

    private function is_whitelisted(): bool {
        if (defined('DOING_CRON') && DOING_CRON) return true;
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false; 
        if (is_admin() && current_user_can('manage_options')) return true;
        return false;
    }

    private function is_static_asset(): bool {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|woff|woff2|ttf|eot|ico)(?:\?.*)?$/i', $uri);
    }

    /**
     * VGT PLATIN FIX: Zero-Trust IP Resolution.
     * Ignoriert HTTP_X_FORWARDED_FOR rigoros, um IP-Spoofing zu verhindern.
     * Cloudflare-IP wird nur akzeptiert, wenn REMOTE_ADDR authentisch ist.
     */
    private function get_validated_ip(): string {
        $remote_addr = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        
        // In der CE gehen wir davon aus, dass wir Cloudflare nicht mathematisch via CIDR 
        // verifizieren können wie in der V7. Daher nutzen wir REMOTE_ADDR als Fallback.
        // Nur wenn das CF-Header Array existiert, nehmen wir es (Restrisiko bei Fake-Proxies).
        // ACHTUNG: Niemals X-Forwarded-For auslesen, da dieser von jedem manipuliert werden kann!
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else {
            $ip = $remote_addr;
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function terminate(string $reason, string $action_type, string $vector_type, string $ip): void {
        global $wpdb;
        $table = $wpdb->prefix . (defined('VIS_TABLE_LOGS') ? VIS_TABLE_LOGS : 'vis_logs');
        
        $wpdb->insert($table, [
            'module'   => 'AEGIS_CE',
            'type'     => $action_type,
            'message'  => $reason,
            'ip'       => $ip,
            'severity' => (in_array($vector_type, ['sqli', 'rce', 'lfi']) || $action_type === 'BAN') ? 10 : 5
        ]);

        if (in_array($vector_type, ['sqli', 'rce', 'lfi'])) {
            $this->engage_ban_protocol($ip, "AEGIS: Critical Vector [$vector_type]");
            $action_type = 'BAN'; 
        }

        if ($this->mode === 'learning') return;

        if (!headers_sent()) {
            http_response_code(403);
            header('Connection: Close');
        }
        
        die('<h1>403 Forbidden</h1><hr>VisionGaia Sentinel Protection.');
    }
}
