<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * MODULE: AEGIS (The Shield) - VGT SUPREME DIAMOND
 * STATUS: DIAMANT STATUS (OPEN SOURCE HARDENED)
 * KOGNITIVE UPGRADES:
 * - [ FIXED ]: Zero-Trust Forward-Confirmed Reverse DNS (FCrDNS) implementiert. Blockiert PTR-Spoofing.
 * - [ FIXED ]: PCRE Fail-Closed Architecture. Ein Regex-Absturz führt zum sofortigen Block (ReDoS-Immunität).
 * - [ FIXED ]: Umfassende Header-Inspektion inklusive Authorization und Content-Type.
 * - [ RED TEAM PATCHED ]: O(1) Signatur-Updates für Command Chaining, Subshells, ORDER BY und Trailing Comments.
 * - [ RED TEAM PATCHED ]: Rekursives Payload-Decoding & OS-Level Comment Stripping (Double-Encoding Abwehr).
 * - [ RED TEAM PATCHED ]: Baseline Polyglot Detection (Head-Scan für Multipart Uploads).
 * - [ NEW | JSON DPI ]: Deep Packet Inspection für application/json inkl. rekursiver Unicode-De-Obfuscation.
 * - Multi-Byte Boundary-Safe Stream Inspection mit optimierter I/O und Garbage Collection.
 */
class VIS_Aegis {

    private bool $enabled;
    private string $mode;
    private int $scan_limit = 524288; // 512KB Max Stream Scan
    private string $validated_ip;

    private array $whitelist_ips = [];
    private array $whitelist_uas = [];

    // VGT SUPREME REGEX: Gehärtet gegen ReDoS, optimiert mit atomaren Gruppen + RED TEAM Patches
    private array $patterns = [
        // RCE Update: Shellshock () { :; }; hinzugefügt
        'rce'         => '/(?i)(?>\b(?>system|exec|passthru|shell_exec|eval|proc_open|assert|phpinfo|pcntl_exec|popen|create_function|call_user_func(?:_array)?|putenv|mail|dl|ffi_load)\b\s*[\(\[])|`[^`]{1,255}`|\$\{(?>jndi|env):[^\}]+\}|\$\([^)]+\)|(?:\(\)\s*\{\s*:;\s*\}\s*;)|(?:;|\|\||\||&&)\s*(?>whoami|net\s+user|id|cat|ls|pwd|wget|curl|nc|bash|sh|ping|type|dir)|\\\\x[0-9a-fA-F]{2}|(?>O:\d+:"[^"]+":\d+:{)/S',
        'lfi'         => '/(?i)(?>\.\.[\/\\\\])|(?>\/etc\/(?>passwd|shadow|hosts|group|issue))|(?>c:\\\\(?>windows|winnt))|(?>\bboot\.ini\b)|(?>wp-config\.php)|(?>php:\/\/(?>filter|input|temp|memory))|(?>\b(?>zip|phar|data|expect|input|glob|ssh2):\/\/)|(?>\/proc\/(?>self|version|cmdline|environ))|(?>\/var\/log\/(?>nginx|apache2|access|error))|%00/S',
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

        ini_set('pcre.backtrack_limit', '1000000');
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

        // VGT Baseline: GET, POST und COOKIE Superglobals iterativ prüfen
        if (!empty($_GET)) $this->recursive_array_scan($_GET, 'GET');
        if (!empty($_POST)) $this->recursive_array_scan($_POST, 'POST');
        if (!empty($_COOKIE)) $this->recursive_array_scan($_COOKIE, 'COOKIE');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $content_type = strtolower($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
            
            // Deterministisches Branching basierend auf Content-Type (DPI)
            if (strpos($content_type, 'multipart/form-data') !== false) {
                if (!empty($_FILES)) {
                    $this->inspect_files_baseline($_FILES);
                }
            } elseif (strpos($content_type, 'application/json') !== false) {
                $this->inspect_json_stream();
            } else {
                $this->inspect_body_stream();
            }
        }
    }

    /**
     * VGT OPEN SOURCE: Recursive Baseline Normalization
     * Zerstört Double-URL-Encoding & Basis-Kommentare.
     */
    private function normalize_payload(string $input): string {
        $normalized = str_replace(["\0", "\r", "\n", "\t"], ' ', $input);
        
        $loops = 0;
        do {
            $old = $normalized;
            $normalized = urldecode($normalized);
            $loops++;
        } while ($old !== $normalized && $loops < 3);

        // Strippt SQL/HTML Kommentare (Abwehr von Tautologie-Obfuskation ' OR/**/1=1)
        $normalized = preg_replace('/(?:\/\*.*?\*\/|<!\-\-.*?\-\->)/s', ' ', $normalized) ?? $normalized;

        return $normalized;
    }

    private function match_pattern(string $regex, string $subject, string $type): void {
        $result = preg_match($regex, $subject);
        
        if ($result === 1) {
            $this->terminate("Threat Vector [$type] detected.", 'BLOCK', $type);
        } elseif ($result === false) {
            $this->terminate("PCRE Engine Limit Exhaustion detected (Possible ReDoS) on Vector [$type].", 'BLOCK', 'pcre_evasion');
        }
    }

    /**
     * VGT BASELINE: Polyglot Upload Head-Scan
     * Scannt nur die ersten 8KB der Datei. Ausreichend für Standard-Scripte.
     * (Premium Version scannt unendlich Chunks für Padding-Attack Abwehr).
     */
    private function inspect_files_baseline(array $files): void {
        foreach ($files as $fileInfo) {
            if (!is_array($fileInfo)) continue;

            if (isset($fileInfo['tmp_name']) && is_string($fileInfo['tmp_name'])) {
                $this->scan_file_head_only($fileInfo['tmp_name']);
                
                if (isset($fileInfo['name']) && is_string($fileInfo['name'])) {
                    $norm_name = $this->normalize_payload($fileInfo['name']);
                    foreach ($this->patterns as $type => $regex) {
                        if ($type === 'ua') continue;
                        $this->match_pattern($regex, $norm_name, $type . '_file_name');
                    }
                }
            } elseif (isset($fileInfo['tmp_name']) && is_array($fileInfo['tmp_name'])) {
                // Behandle multidimensionale Array-Struktur (bei multiple="multiple" Uploads)
                foreach ($fileInfo['tmp_name'] as $idx => $tmp_path) {
                    if (is_string($tmp_path)) {
                        $this->scan_file_head_only($tmp_path);
                        $name = $fileInfo['name'][$idx] ?? '';
                        if (is_string($name)) {
                            $norm_name = $this->normalize_payload($name);
                            foreach ($this->patterns as $type => $regex) {
                                if ($type === 'ua') continue;
                                $this->match_pattern($regex, $norm_name, $type . '_file_name');
                            }
                        }
                    }
                }
            }
        }
    }

    private function scan_file_head_only(string $tmp_path): void {
        if (!is_readable($tmp_path)) return;
        $handle = @fopen($tmp_path, 'rb');
        if (!$handle) return;

        // OS Limit: Lese nur den ersten Chunk (8KB). Ignoriert Deep-Padding Attacks.
        $chunk = fread($handle, 8192); 
        fclose($handle);

        if ($chunk && preg_match('/<\?php|<\?=|#! \/bin\/|eval\s*\(/i', $chunk)) {
            $this->terminate("Basic Polyglot Code Injection in Upload", 'BLOCK', 'rce_upload_basic');
        }
    }

    private function inspect_json_stream(): void {
        $raw_body = file_get_contents('php://input', false, null, 0, $this->scan_limit);
        if (empty($raw_body)) {
            return;
        }

        try {
            $parsed_json = json_decode($raw_body, true, 512, JSON_THROW_ON_ERROR);
            $this->recursive_array_scan($parsed_json, 'JSON');
        } catch (JsonException $e) {
            $normalized_raw = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
            }, $raw_body);

            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') {
                    continue;
                }
                $this->match_pattern($regex, (string)$normalized_raw, $type . '_json_raw_fallback');
            }
        }
        
        unset($raw_body);
    }

    private function recursive_array_scan(array $data, string $context = 'DATA'): void {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->recursive_array_scan($value, $context);
            } elseif (is_string($value)) {
                $normalized = $this->normalize_payload($value);
                foreach ($this->patterns as $type => $regex) {
                    if ($type === 'ua') {
                        continue;
                    }
                    $this->match_pattern($regex, $normalized, $type . '_' . strtolower($context));
                }
            }
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
            
            // Baseline Decoding Integration
            $decoded_payload = $this->normalize_payload($raw_payload);

            foreach ($this->patterns as $type => $regex) {
                if ($type === 'ua') {
                    continue;
                }
                $this->match_pattern($regex, $decoded_payload, $type . '_body');
            }

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
            
            // Baseline Decoding für Headers
            $decoded = $this->normalize_payload($header_val);
            foreach ($this->patterns as $type => $regex) {
                $this->match_pattern($regex, $decoded, $type . '_header');
            }
        }
    }

    private function inspect_uri(): void {
        $raw_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $normalized_uri = $this->normalize_payload($raw_uri);

        foreach ($this->patterns as $type => $regex) {
            if ($type === 'ua') {
                continue;
            }
            $this->match_pattern($regex, $normalized_uri, $type . '_uri');
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

        $will_ban = ($this->mode !== 'learning' && in_array(str_replace(['_body', '_header', '_uri', '_json_tree', '_json_raw_fallback', '_post', '_get', '_cookie', '_file_name'], '', $vector_type), ['sqli', 'rce', 'lfi', 'framework', 'ua'], true));
        if ($will_ban) {
            $action_type = 'BAN'; 
        }

        if (isset($wpdb)) {
            $table = $wpdb->prefix . (defined('VIS_TABLE_LOGS') ? VIS_TABLE_LOGS : 'vis_omega_logs');
            $wpdb->insert($table, [
                'module'   => 'AEGIS_OS_HARDENED',
                'type'     => $action_type,
                'message'  => $reason,
                'ip'       => $this->validated_ip,
                'severity' => (in_array(str_replace(['_body', '_header', '_uri', '_json_tree', '_json_raw_fallback', '_post', '_get', '_cookie', '_file_name'], '', $vector_type), ['sqli', 'rce', 'lfi'], true) || $action_type === 'BAN') ? 10 : 5
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
        
        die(json_encode([
            'status' => 'error',
            'code' => 403,
            'message' => 'VISIONGAIATECHNOLOGY AEGIS PROTOCOL: Access Denied',
            'vector' => $vector_type
        ], JSON_THROW_ON_ERROR));
    }

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

        if ($ua !== '' && preg_match('/(googlebot|bingbot|duckduckbot|yandexbot)/i', $ua)) {
            $hostname = gethostbyaddr($this->validated_ip);
            if ($hostname !== false && $hostname !== $this->validated_ip) {
                $forward_ips = gethostbynamel($hostname);
                if (is_array($forward_ips) && in_array($this->validated_ip, $forward_ips, true)) {
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
