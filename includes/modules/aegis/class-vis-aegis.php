<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: AEGIS (The Shield) - VGT SUPREME PLATINUM REWRITE
 * STATUS: PLATIN STATUS
 * KOGNITIVE UPGRADES:
 * - [ FIXED ]: Zero-Trust Forward-Confirmed Reverse DNS (FCrDNS) implementiert. Blockiert PTR-Spoofing.
 * - [ FIXED ]: PCRE Fail-Closed Architecture. Ein Regex-Absturz führt zum sofortigen Block (ReDoS-Immunität).
 * - [ FIXED ]: Umfassende Header-Inspektion inklusive Authorization und Content-Type.
 * - O(1) Cloudflare CIDR Validation (via externem VIS_Network Kernel).
 * - Multi-Byte Boundary-Safe Stream Inspection mit optimierter I/O und Garbage Collection.
 * - Double-Decode Protection mit Fehler-Toleranz-Abfangung.
 */
class VIS_Aegis {

    private bool $enabled;
    private string $mode;
    private int $scan_limit = 524288; // 512KB Max Stream Scan
    private string $validated_ip;

    private array $whitelist_ips = [];
    private array $whitelist_uas = [];

    // VGT SUPREME REGEX: Gehärtet gegen ReDoS, optimiert mit atomaren Gruppen
    private array $patterns = [
        // RCE PATCHED: Subshells $(...), Pipelines |, und Verkettungen && hinzugefügt
        'rce'         => '/(?i)(?>\b(?>system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo|pcntl_exec|popen|create_function|call_user_func(?:_array)?|putenv|mail|dl|ffi_load)\b\s*[\(\[])|`[^`]{1,255}`|\$\{(?>jndi|env):[^\}]+\}|\$\([^)]+\)|(?:;|\|\||\||&&)\s*(?>whoami|net\s+user|id|cat|ls|pwd|wget|curl|nc|bash|sh|ping|type|dir)|\\\\x[0-9a-fA-F]{2}|(?>O:\d+:"[^"]+":\d+:{)/S',
        'lfi'         => '/(?i)(?>\.\.[\/\\\\])|(?>\/etc\/(?>passwd|shadow|hosts|group|issue))|(?>c:\\\\(?>windows|winnt))|(?>\bboot\.ini\b)|(?>wp-config\.php)|(?>php:\/\/(?>filter|input|temp|memory))|(?>\b(?>zip|phar|data|expect|input|glob|ssh2):\/\/)|(?>\/proc\/(?>self|version|cmdline|environ))|(?>\/var\/log\/(?>nginx|apache2|access|error))|%00/S',
        // SQLI PATCHED: Trailing Comments (-- , --+) und ORDER BY / GROUP BY Injections hinzugefügt
        'sqli'        => '/(?i)(?>u[\W_]*n[\W_]*i[\W_]*o[\W_]*n(?:[\W_]+|\/\*!?\d*\*\/)+s[\W_]*e[\W_]*l[\W_]*e[\W_]*c[\W_]*t)|information_schema|waitfor[\W_]+delay|(?>\b(?>benchmark|sleep|extractvalue|updatexml|hex|unhex|concat)\s*\()|(?>\s+(?>OR|AND)\s+[\d\'"`]+\s*(?>=|>|<|LIKE)\s*[\d\'"`]+)|(?>drop\s+(?>table|database))|(?>alter\s+table)|(?>\{oj\s+)|(?>\$(?>where|ne|regex|gt|lt|exists|expr|nin)(?:"|\')?\s*:)|(?>\b(?>order|group)\s+by\b)|(?:--[ \+]*$)/S',
        'xss'         => '/(?i)(?><script)|(?>\bjavascript:)|(?>on(?>load|error|click|mouseover|pointer)\s*=)|base64_decode|(?>\bvbscript:)|(?>data:text\/(?>html|xml))|%ef%bc%9c|＜|\\\\uFF1C|%c0%bc|\{\{\$on\.constructor/S',
        'ua'          => '/(?i)\b(?>sqlmap|nikto|wpscan|python|curl|wget|libwww|jndi|masscan|havij|netsparker|burp|nmap|shellshock|headless|selenium|gobuster|dirbuster|shodan|zgrab|projectdiscovery|nuclei)/S',
        'framework'   => '/(?i)(?>\b(?>wp_set_current_user|wp_insert_user|wp_update_user)\b)|(?>update_option\s*\(\s*[\'"](?>siteurl|home|users_can_register|default_role)[\'"])|eval-stdin|_ignition\/execute-solution|telescope\/requests|api\/swagger|actuator\/(?>env|refresh|restart|heapdump)|(?>__(?>schema|type)\s*(?>\{|\(|:))|\.(?>env|git|svn)(?>\/|\b)/S',
        'db_direct'   => '/(?i)\$wpdb->|(?>\b(?>mysql_query|mysqli_query|pg_query|sqlite_query|PDO::exec)\b)/S',
        'gql_recon'   => '/(?i)(?>__(?>schema|type)\s*(?>\{|\(|:))/S',
        'array_bypass'=> '/(?i)(?>\b[a-z0-9_]+(?:\[|%5B)[a-z0-9_\'"%]*?(?:\]|%5D)\s*=(?>\s|%20)*(?>system|exec|shell_exec|eval|assert|passthru|popen|proc_open|pcntl_exec|phpinfo))/S',
    ];

    public function __construct(array $options) {
        $this->enabled = !empty($options['aegis_enabled']);
        $this->mode    = (string) ($options['aegis_mode'] ?? 'strict');

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

        // Setup PCRE Limits für Fail-Closed Evaluierung
        $old_backtrack = ini_get('pcre.backtrack_limit');
        $old_recursion = ini_get('pcre.recursion_limit');

        ini_set('pcre.backtrack_limit', '1000000'); // Standard PHP 8 Limit, reduziert ReDoS False-Positives
        ini_set('pcre.recursion_limit', '1000000');

        $this->guard();

        // Wiederherstellung der Ursprungs-Limits
        if ($old_backtrack !== false) {
            ini_set('pcre.backtrack_limit', $old_backtrack);
        }
        if ($old_recursion !== false) {
            ini_set('pcre.recursion_limit', $old_recursion);
        }
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
     * Auswertung der regulären Ausdrücke unter Einsatz einer Fail-Closed-Policy.
     * Ein Rückgabewert von `false` bei preg_match impliziert einen Limit-Exhaust (ReDoS-Attacke)
     * und führt in VGT-Architektur zum direkten System-Block.
     */
    private function match_pattern(string $regex, string $subject, string $type): void {
        $result = preg_match($regex, $subject);
        
        if ($result === 1) {
            $this->terminate("Threat Vector [$type] detected.", 'BLOCK', $type);
        } elseif ($result === false) {
            // ReDoS-Protection: Wenn der Regex fehlschlägt, den Request abbrechen (Fail-Closed)
            $this->terminate("PCRE Engine Limit Exhaustion detected (Possible ReDoS) on Vector [$type].", 'BLOCK', 'pcre_evasion');
        }
    }

    private function inspect_body_stream(): void {
        $handle = @fopen('php://input', 'rb');
        if (!$handle) {
            return;
        }

        stream_set_timeout($handle, 2);

        $scanned_bytes = 0;
        $overlap_buffer = '';
        $chunk_size = 8192; 

        while (!feof($handle)) {
            if ($scanned_bytes >= $this->scan_limit) {
                break;
            }

            $chunk = fread($handle, $chunk_size);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $scanned_bytes += strlen($chunk);
            $raw_payload = $overlap_buffer . $chunk;
            
            // Boundary-Safe Decoding
            $decoded_payload = urldecode(urldecode($raw_payload));

            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') {
                    continue;
                }
                $this->match_pattern($regex, $decoded_payload, $type . '_body');
            }

            // Letzte 256 Bytes überlappen, um gesplittete Payloads an der Chunk-Grenze zu catchen
            $overlap_buffer = substr($raw_payload, -256);
            
            unset($decoded_payload, $raw_payload, $chunk);
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

        // Umfassende Header-Identifikation inklusive Authorization für JWT-Exploits
        $critical_headers = [
            'HTTP_USER_AGENT', 'HTTP_REFERER', 'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_REAL_IP', 'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE', 
            'HTTP_CACHE_CONTROL', 'HTTP_AUTHORIZATION', 'CONTENT_TYPE'
        ];

        $headers_to_scan = [];

        foreach ($critical_headers as $key) {
            if (isset($_SERVER[$key]) && is_string($_SERVER[$key])) {
                $headers_to_scan[] = $_SERVER[$key];
            }
        }
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_X_') === 0 && is_string($value)) {
                $headers_to_scan[] = $value;
            }
        }
        
        foreach ($_COOKIE as $val) {
            if (is_string($val)) {
                $headers_to_scan[] = $val;
            }
        }

        foreach ($headers_to_scan as $header_val) {
            if ($header_val === '') {
                continue;
            }
            
            $decoded = urldecode($header_val);
            foreach ($this->patterns as $type => $regex) {
                $this->match_pattern($regex, $decoded, $type . '_header');
            }
        }
    }

    private function inspect_uri(): void {
        $raw_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $decoded = urldecode($raw_uri);
        $double_decoded = urldecode($decoded);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') {
                continue;
            }
            $this->match_pattern($regex, $double_decoded, $type . '_uri');
        }
    }

    private function engage_ban_protocol(string $reason): void {
        if ($this->mode === 'learning') {
            return;
        }

        if (class_exists('VIS_Cerberus') && method_exists('VIS_Cerberus', 'instance')) {
            VIS_Cerberus::instance()->ban_ip($this->validated_ip, $reason);
            return;
        }

        global $wpdb;
        if (!isset($wpdb)) {
            return;
        }

        $table = $wpdb->prefix . (defined('VIS_TABLE_BANS') ? VIS_TABLE_BANS : 'vis_apex_bans'); 
        $uri   = substr(esc_url_raw($_SERVER['REQUEST_URI'] ?? ''), 0, 255);

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (ip, reason, banned_at, request_uri) VALUES (%s, %s, %s, %s)",
            $this->validated_ip, $reason, current_time('mysql'), $uri
        ));
    }

    private function terminate(string $reason, string $action_type, string $vector_type): void {
        global $wpdb;

        $will_ban = ($this->mode !== 'learning' && in_array(str_replace(['_body', '_header', '_uri'], '', $vector_type), ['sqli', 'rce', 'lfi', 'framework', 'ua'], true));
        if ($will_ban) {
            $action_type = 'BAN'; 
        }

        if (isset($wpdb)) {
            $table = $wpdb->prefix . (defined('VIS_TABLE_LOGS') ? VIS_TABLE_LOGS : 'vis_omega_logs');
            $wpdb->insert($table, [
                'module'   => 'AEGIS_PLATIN',
                'type'     => $action_type,
                'message'  => $reason,
                'ip'       => $this->validated_ip,
                'severity' => (in_array(str_replace(['_body', '_header', '_uri'], '', $vector_type), ['sqli', 'rce', 'lfi'], true) || $action_type === 'BAN') ? 10 : 5
            ]);
        }

        if ($will_ban) {
            $this->engage_ban_protocol("AEGIS: Critical Vector [$vector_type]");
        }

        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $safe_vector = preg_replace('/[^a-zA-Z0-9_]/', '', $vector_type); 

        // VGT Signature Move: TCP Socket Drop & Hard Protocol Override
        if (!headers_sent()) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            header("$protocol 403 Forbidden", true, 403);
            header('X-Aegis-Block: ' . strtoupper($safe_vector));
            header('Content-Type: application/json; charset=utf-8'); 
            header('Connection: close');
            header('Cache-Control: no-store, max-age=0');
        }
        
        // Strikte JSON Struktur für saubere API Integration
        die(json_encode([
            'status' => 'error',
            'code' => 403,
            'message' => 'VISIONGAIATECHNOLOGY AEGIS PROTOCOL: Access Denied',
            'vector' => $vector_type
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * VGT ZERO-TRUST WHITELIST - PLATIN FIX
     * Eliminiert IP Spoofing via Forward-Confirmed Reverse DNS (FCrDNS).
     */
    private function is_whitelisted(): bool {
        if (defined('DOING_CRON') && DOING_CRON) {
            $server_ip = $_SERVER['SERVER_ADDR'] ?? null;
            if ($server_ip && $this->validated_ip === $server_ip) {
                return true;
            }
        }
        
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return false; 
        }
        
        if (in_array($this->validated_ip, ['127.0.0.1', '::1', 'fe80::1'], true)) {
            return true;
        }

        if (!empty($this->whitelist_ips) && in_array($this->validated_ip, $this->whitelist_ips, true)) {
            return true;
        }

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!empty($this->whitelist_uas) && $ua !== '') {
            foreach ($this->whitelist_uas as $safe_ua) {
                if ($safe_ua === '') {
                    continue;
                }
                if (stripos($ua, $safe_ua) !== false) {
                    return true;
                }
            }
        }

        // [ VGT PLATIN FIX ]: Bot-Verifikation via Forward-Confirmed reverse DNS (FCrDNS)
        if ($ua !== '' && preg_match('/(googlebot|bingbot|duckduckbot|yandexbot)/i', $ua)) {
            $hostname = gethostbyaddr($this->validated_ip);
            
            // Wenn der PTR Record existiert und nicht einfach nur die IP ist
            if ($hostname !== false && $hostname !== $this->validated_ip) {
                // Forward DNS Lookup zur Validierung des Hostnames
                $forward_ips = gethostbynamel($hostname);
                
                // Wenn die Ursprungs-IP in den aufgelösten IPs des Hostnames liegt -> FCrDNS verifiziert
                if (is_array($forward_ips) && in_array($this->validated_ip, $forward_ips, true)) {
                    // Verifikation gegen legitime Domains der Crawler
                    if (preg_match('/(?:googlebot\.com|search\.msn\.com|yandex\.com|yandex\.net|yandex\.ru|duckduckgo\.com)$/i', $hostname)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function is_static_asset(): bool {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|woff2?|ttf|eot|ico)(?:\?.*)?$/i', $uri);
    }
}
