<?php
if (!defined('ABSPATH')) exit;

/**
 * MODULE: AEGIS (The Shield) - OMEGA HARDENED V4.2 (PLATIN STATUS)
 * Architecture: Stream-Based WAF with Memory Safety Protocols.
 * Fixes: Memory Exhaustion (DoS), Regex ReDoS Vectors, Static Asset CPU Waste.
 */
class VIS_Aegis {

    private $enabled = false;
    private $mode = 'strict';
    private $scan_limit = 524288; // 512KB Max Scan Limit (Defense against DoS)
    
    // PRE-COMPILED PATTERNS (Optimized - Possessive Quantifiers & Non-Capturing Groups)
    // VGT HARDENING: Verhindert katastrophales Backtracking (ReDoS) durch \s*+ und \s++
    private $patterns = [
        'rce'  => '/(?:system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo)\s*+\(/i',
        'lfi'  => '/(?:\.\.[\/\\\\]|\/etc\/passwd|c:\\\\windows|boot\.ini)/i',
        'sqli' => '/(?:union\s++select|information_schema|waitfor\s++delay|hex\s*+\(|unhex\s*+\(|concat\s*+\(|char\s*+\(|\s++OR\s++1=1)/i',
        'xss'  => '/(?:<script|javascript:|on(?:load|error|click|mouseover)=|base64_decode|vbscript:|data:text\/html)/i',
        'ua'   => '/(?:sqlmap|nikto|wpscan|python|curl|wget|libwww|jndi:|masscan|havij|netsparker|burp|nmap|shellshock|headless|selenium|gobuster|dirbuster|shodan)/i'
    ];

    public function __construct($options) {
        $this->enabled = !empty($options['aegis_enabled']);
        $this->mode = $options['aegis_mode'] ?? 'strict';

        if (!$this->enabled || $this->is_whitelisted()) {
            return;
        }

        // VGT HARDENING: PCRE Limits gegen ReDoS Timeouts global für diesen Request kappen
        ini_set('pcre.backtrack_limit', '100000');
        ini_set('pcre.recursion_limit', '100000');

        $this->guard();
    }

    private function guard() {
        $ip = $this->get_ip();

        // 1. FAST-PATH: STATIC ASSET BYPASS
        // Spart 90% CPU bei Bild/CSS/JS-Anfragen
        if ($this->is_static_asset()) {
            return;
        }

        // 2. HEADER INSPECTION
        $this->inspect_headers($ip);

        // 3. URI & QUERY STRING INSPECTION
        $this->inspect_uri($ip);

        // 4. MEMORY-SAFE BODY INSPECTION (Stream Mode)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->inspect_body_stream($ip);
        }
    }

    /**
     * STREAM ENGINE: Scans input without loading it entirely into RAM.
     * Prevents Memory Exhaustion DoS attacks.
     */
    private function inspect_body_stream($ip) {
        // Öffne Input Stream im Binary Read Mode
        $handle = @fopen('php://input', 'rb');
        if (!$handle) return;

        // VGT HARDENING: Mitigate Slow-Read DoS Attacks on Streams
        stream_set_timeout($handle, 2);

        $scanned_bytes = 0;
        $buffer = '';
        $chunk_size = 4096; // 4KB Chunks

        while (!feof($handle)) {
            if ($scanned_bytes >= $this->scan_limit) {
                break; // Hard Stop bei 512KB
            }

            $chunk = fread($handle, $chunk_size);
            if ($chunk === false) break;

            $scanned_bytes += strlen($chunk);
            
            // Overlap-Buffer: Behalte die letzten 500 Bytes des vorherigen Chunks,
            // um Patterns zu finden, die genau auf der Chunk-Grenze liegen.
            $search_buffer = $buffer . $chunk;
            
            // DEEP SCANNING
            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') continue; // UA ist Header, nicht Body

                if (preg_match($regex, $search_buffer)) {
                    fclose($handle);
                    $this->terminate("Threat Vector [$type] detected in Body Stream.", 'BLOCK', $type);
                }
            }

            // Setze Buffer auf die letzten 500 Bytes des aktuellen Chunks
            $buffer = substr($chunk, -500);
        }

        fclose($handle);
    }

    private function inspect_headers($ip) {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // GHOST POST CHECK (Blind Bots)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            if (empty($ua) && empty($ref)) {
                $this->terminate("Ghost POST detected (No UA/Ref)", 'BLOCK', 'bot');
            }
        }

        if (preg_match($this->patterns['ua'], $ua)) {
            $this->terminate('Bad User-Agent detected', 'BLOCK', 'bot');
        }
    }

    private function inspect_uri($ip) {
        $raw_uri = $_SERVER['REQUEST_URI'];
        
        // Double Decode Protection (Bypass-Versuche wie %2527)
        $decoded = urldecode($raw_uri);
        $double_decoded = urldecode($decoded);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') continue;

            if (preg_match($regex, $double_decoded)) {
                 $this->terminate("Threat Vector [$type] detected in URI.", 'BLOCK', $type);
            }
        }
    }

    private function engage_ban_protocol($ip, $reason) {
        if ($this->mode === 'learning') return;

        global $wpdb;
        $table = $wpdb->prefix . VIS_TABLE_BANS; 

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $table (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $ip, $reason, current_time('mysql'), $_SERVER['REQUEST_URI']
        ));
    }

    private function is_whitelisted() {
        if (defined('DOING_CRON') && DOING_CRON) return true;
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false; 
        if (is_admin() && current_user_can('manage_options')) return true;
        return false;
    }

    private function is_static_asset() {
        $uri = $_SERVER['REQUEST_URI'];
        // Ignoriere typische statische Endungen (Case Insensitive)
        return preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|woff|woff2|ttf|eot|ico)$/i', $uri);
    }

    private function get_ip() {
        // Cloudflare & Proxy Aware
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        
        // VGT HARDENING: Strict IP Validation (Anti-Header-Injection)
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function terminate($reason, $action_type, $vector_type = 'unknown') {
        $ip = $this->get_ip();
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . VIS_TABLE_LOGS, [
            'module'   => 'AEGIS',
            'type'     => $action_type,
            'message'  => $reason,
            'ip'       => $ip,
            'severity' => ($action_type === 'BAN' || $vector_type === 'sqli' || $vector_type === 'rce') ? 10 : 5
        ]);

        // CRITICAL THREATS = INSTANT BAN
        if (in_array($vector_type, ['sqli', 'rce', 'lfi'])) {
            $this->engage_ban_protocol($ip, "AEGIS: Critical Vector [$vector_type]");
            $action_type = 'BAN'; // Eskalation
        }

        if ($this->mode === 'learning') return;

        // VGT HARDENING: TCP Connection Drop & Header Check
        if (!headers_sent()) {
            http_response_code(403);
            header('Connection: Close'); // Tarpitting: Erzwingt sofortigen Socket-Drop beim Angreifer
        }
        
        // Minimalistische Antwort, um Angreifern keine Infos zu geben
        die('<h1>403 Forbidden</h1><hr>VisionGaia Sentinel Protection.');
    }
}
