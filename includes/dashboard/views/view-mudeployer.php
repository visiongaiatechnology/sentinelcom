<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$mu_dir  = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : wp_normalize_path(WP_CONTENT_DIR . '/mu-plugins');
$mu_file = $mu_dir . '/0-vgt-sentinel-loader.php';

// Dynamische Pfad-Generierung für den konkreten Server
$vgt_target = wp_normalize_path(VGT_SENTINEL_PATH . 'vision-integrity-sentinel.php');

$is_deployed = file_exists($mu_file);

// Der Code, den der Admin manuell anlegen muss
$mu_content = "<?php\n" .
"/**\n" .
" * Plugin Name: VGT Sentinel MU-Loader (Hardened)\n" .
" * Description: O(1) Pre-Boot Interception. Läd Sentinel isoliert vor allen anderen Plugins.\n" .
" * Version: 2.0.0\n" .
" * Author: VisionGaiaTechnology\n" .
" */\n" .
"// VGT HARDENING: Strict Access Protocol\n" .
"if (!defined('ABSPATH')) { header('HTTP/1.0 403 Forbidden'); exit('VGT: Protocol Violation'); }\n\n" .
"// Kognitive Boot-Signatur setzen\n" .
"define('VGT_SENTINEL_MU_BOOT', true);\n\n" .
"\$vgt_core = '" . esc_html($vgt_target) . "';\n\n" .
"// Memory Safe & Anti-Crash Validation\n" .
"if (file_exists(\$vgt_core) && is_readable(\$vgt_core)) {\n" .
"    require_once \$vgt_core;\n" .
"}\n";
?>

<div id="vis-mu-container">
    <div class="vis-card" style="border-top: 3px solid #06b6d4; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
            <span class="dashicons dashicons-hammer" style="font-size: 32px; width: 32px; height: 32px; color: #06b6d4;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">PRE-BOOT MU-DEPLOYER (MANUAL)</h2>
                <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Zero-Latency Plugin Loading & Core Interception</p>
            </div>
        </div>

        <p class="vis-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            Aus architektonischen Sicherheitsgründen (WP.org Compliance) schreibt Sentinel nicht selbstständig in das Dateisystem. 
            Um Sentinel auf <strong>Instanz 0</strong> zu heben, erstelle manuell die Datei <code>0-vgt-sentinel-loader.php</code> im Verzeichnis <code>/wp-content/mu-plugins/</code> und kopiere den untenstehenden Code hinein.
        </p>

        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 25px; margin-bottom: 25px;">
            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $is_deployed ? '#10b981' : '#ef4444'; ?>; box-shadow: 0 0 10px <?php echo $is_deployed ? 'rgba(16,185,129,0.5)' : 'rgba(239,68,68,0.5)'; ?>;"></div>
                <div>
                    <h4 class="vis-lang-de" style="margin: 0 0 5px 0; color: #fff; font-size: 16px; letter-spacing: 0.5px;">
                        <?php echo $is_deployed ? 'MU-LOADER IST AKTIV UND VERRIEGELT' : 'MU-LOADER FEHLT (STANDARD-MODUS)'; ?>
                    </h4>
                </div>
            </div>

            <?php if (!$is_deployed): ?>
                <div style="position: relative;">
                    <textarea readonly style="width: 100%; height: 280px; background: #020617; color: #10b981; font-family: monospace; font-size: 11px; padding: 15px; border-radius: 4px; border: 1px solid #1e293b; resize: none;"><?php echo esc_textarea($mu_content); ?></textarea>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
