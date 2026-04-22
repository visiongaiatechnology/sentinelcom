<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ENGINE: SCANNER CORE (HYBRID FUSION)
 * Status: OMEGA HARDENED V4.0 (PHP-VAULT / WP.ORG COMPLIANT)
 * Security Upgrade: Manifest storage migrated to executable PHP format to prevent leakage on Nginx/IIS.
 */
class VGTS_Scanner_Engine {

    private array $exclude_dirs = [
        'node_modules', '.git', 'cache', 'upgrade', 'languages', 'vgts-vault-omega'
    ];
    
    private array $monitored_extensions = ['php', 'php5', 'phtml', 'html', 'htm', 'js', 'htaccess', 'py', 'pl'];
    
    private int $time_limit = 5; 
    private int $batch_size = 500; 
    private float $start_time;

    // HARDENING: Manifest ist nun eine PHP Datei
    private string $manifest_file;

    public function __construct() {
        $this->start_time = microtime(true);
        
        // Sicherer Pfaddefinition
        $upload_dir = wp_upload_dir();
        $vault_dir  = $upload_dir['basedir'] . '/vgts-vault-omega';
        $this->manifest_file = $vault_dir . '/integrity_matrix.php';
    }

    public function perform_scan_batch(int $offset = 0, array $partial_state = []): array {
        $root     = wp_normalize_path(ABSPATH);
        $baseline = $this->load_manifest();
        
        $directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory);
        
        $count     = 0;
        $new_state = $partial_state;
        $completed = false;

        try {
            foreach ($iterator as $file) {
                if ($count < $offset) { 
                    $count++; 
                    continue; 
                }

                if ((microtime(true) - $this->start_time) > $this->time_limit) {
                    return [
                        'status'        => 'processing',
                        'offset'        => $count,
                        /* translators: %d: Number of files analyzed */
                        'message'       => sprintf(esc_html__('Scanning Sector: %d files analyzed...', 'vgt-sentinel-ce'), $count),
                        'current_state' => $new_state
                    ];
                }

                if ($file->isLink()) { 
                    $count++; 
                    continue; 
                }

                $path = wp_normalize_path($file->getPathname());
                foreach ($this->exclude_dirs as $ex) {
                    if (strpos($path, '/' . $ex . '/') !== false) {
                        $count++; 
                        continue 2; 
                    }
                }

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->monitored_extensions, true)) { 
                    $count++; 
                    continue; 
                }

                $rel_path = str_replace($root, '', $path);
                $mtime    = $file->getMTime();
                $size     = $file->getSize();

                // OPTIMIZATION: Check mtime/size first before hashing (Pre-Filter)
                if (isset($baseline[$rel_path]) && 
                    $baseline[$rel_path]['mtime'] === $mtime && 
                    $baseline[$rel_path]['size'] === $size) {
                    $hash = $baseline[$rel_path]['hash'];
                } else {
                    $hash = hash_file('sha256', $path);
                }

                $new_state[$rel_path] = [
                    'hash'  => $hash,
                    'mtime' => $mtime,
                    'size'  => $size
                ];

                $count++;
            }
            $completed = true;

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => sanitize_text_field($e->getMessage())];
        }

        if ($completed) {
            return $this->finalize_scan($baseline, $new_state);
        }

        return ['status' => 'error', 'message' => esc_html__('Unknown scan interruption.', 'vgt-sentinel-ce')];
    }

    private function finalize_scan(array $baseline, array $current_state): array {
        if (!$this->ensure_vault_exists()) {
             return [
                'status'  => 'error',
                'message' => esc_html__('CRITICAL: Vault Access Denied.', 'vgt-sentinel-ce')
            ];
        }

        $report = [];
        
        if (empty($baseline)) {
            $this->save_manifest($current_state);
            $report = [
                'status'    => 'init', 
                'message'   => esc_html__('Initial manifest (Secure PHP Storage) created.', 'vgt-sentinel-ce'),
                'changes'   => [],
                'timestamp' => current_time('mysql')
            ];
        } else {
            $diff = $this->compare_manifests($baseline, $current_state);
            
            if (empty($diff)) {
                $this->save_manifest($current_state); 
                $report = [
                    'status'    => 'clean',
                    'message'   => esc_html__('System integrity confirmed.', 'vgt-sentinel-ce'),
                    'changes'   => [],
                    'timestamp' => current_time('mysql')
                ];
            } else {
                $report = [
                    'status'    => 'warning',
                    'message'   => esc_html__('Integrity violation detected!', 'vgt-sentinel-ce'),
                    'changes'   => $diff,
                    'timestamp' => current_time('mysql')
                ];
            }
        }

        update_option('vgts_scan_report', $report);
        return $report;
    }

    private function compare_manifests(array $old, array $new): array {
        $changes = [];
        foreach ($new as $path => $data) {
            $hash = $data['hash'];
            if (!isset($old[$path])) {
                $changes[] = ['type' => 'NEW', 'file' => $path, 'desc' => esc_html__('New file detected', 'vgt-sentinel-ce')];
            } elseif ($old[$path]['hash'] !== $hash) {
                $changes[] = ['type' => 'MODIFIED', 'file' => $path, 'desc' => esc_html__('Content modified (Hash mismatch)', 'vgt-sentinel-ce')];
            }
        }
        foreach ($old as $path => $data) {
            if (!isset($new[$path])) {
                $changes[] = ['type' => 'DELETED', 'file' => $path, 'desc' => esc_html__('File removed', 'vgt-sentinel-ce')];
            }
        }
        return $changes;
    }

    public function regenerate_baseline(): bool {
        if (!$this->ensure_vault_exists()) return false;

        if (function_exists('set_time_limit') && !in_array('set_time_limit', explode(',', ini_get('disable_functions')), true)) {
            set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        $root      = wp_normalize_path(ABSPATH);
        $directory = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator  = new RecursiveIteratorIterator($directory);
        
        $new_state = [];

        try {
            foreach ($iterator as $file) {
                if ($file->isLink()) continue;

                $path = wp_normalize_path($file->getPathname());
                foreach ($this->exclude_dirs as $ex) {
                    if (strpos($path, '/' . $ex . '/') !== false) continue 2; 
                }

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, $this->monitored_extensions, true)) continue;

                $rel_path = str_replace($root, '', $path);
                $new_state[$rel_path] = [
                    'hash'  => hash_file('sha256', $path),
                    'mtime' => $file->getMTime(),
                    'size'  => $file->getSize()
                ];
            }
        } catch (Exception $e) {
            return false;
        }

        $this->save_manifest($new_state);
        
        update_option('vgts_scan_report', [
            'status'    => 'clean',
            'message'   => esc_html__('System manually verified (Re-Indexed).', 'vgt-sentinel-ce'),
            'changes'   => [],
            'timestamp' => current_time('mysql')
        ]);

        return true;
    }

    // --- HARDENED STORAGE LOGIC ---

    private function load_manifest(): array {
        if (file_exists($this->manifest_file)) {
            // SECURITY: Include statt file_get_contents. 
            // Die Datei muss validen PHP Code enthalten: <?php return [...];
            $data = include $this->manifest_file;
            return is_array($data) ? $data : [];
        }
        return [];
    }

    private function save_manifest(array $data): void {
        // ATOMIC WRITE OPERATION mit Locking
        // Wir speichern es als executable PHP, das das Array returned.
        // Das verhindert Direct-Access-Leaks via Browser.
        $content = "<?php defined('ABSPATH') || exit; return " . var_export($data, true) . ";";
        
        $temp_file = $this->manifest_file . '_tmp.php';
        
        if (file_put_contents($temp_file, $content, LOCK_EX) !== false) {
            rename($temp_file, $this->manifest_file);
            // Optional: OpCache invalidieren, damit Änderungen sofort greifen
            if (function_exists('opcache_invalidate') && file_exists($this->manifest_file)) {
                opcache_invalidate($this->manifest_file, true);
            }
        }
    }

    private function ensure_vault_exists(): bool {
        $dir = dirname($this->manifest_file);
        if (!file_exists($dir)) {
            // [WP.ORG COMPLIANCE]: Use wp_mkdir_p instead of @mkdir
            if (!wp_mkdir_p($dir)) {
                return false;
            }
            // Fallback Security (trotz PHP Storage)
            file_put_contents($dir . '/index.php', '<?php // SILENCE IS GOLDEN ?>');
            file_put_contents($dir . '/.htaccess', "Order Deny,Allow\nDeny from all");
        }
        return is_writable($dir);
    }
}