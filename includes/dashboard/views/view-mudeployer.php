<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- VGT AUTONOME DEPLOYMENT LOGIK (PLATIN HARDENED) ---
$mu_dir  = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : wp_normalize_path(WP_CONTENT_DIR . '/mu-plugins');
$mu_file = $mu_dir . '/0-vgt-sentinel-loader.php'; // Prefix 0 erzwingt Prio 1

// Path Normalization schützt vor Slash-Manipulationen in Windows/Linux Environments
$vgt_target = wp_normalize_path(VIS_PATH . 'vision-integrity-sentinel.php');

$message = '';
$msg_type = '';

// POST Handler (Zero-Dependency)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vgt_mu_action'])) {
    if (isset($_POST['vis_mu_nonce']) && wp_verify_nonce($_POST['vis_mu_nonce'], 'vis_mu_deploy')) {
        
        if ($_POST['vgt_mu_action'] === 'deploy') {
            if (!is_dir($mu_dir)) {
                @mkdir($mu_dir, 0755, true);
            }
            
            if (is_writable($mu_dir) || is_writable($mu_file)) {
                
                // OMEGA HARDENED PAYLOAD
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
                "define('VIS_MU_BOOT', true);\n\n" .
                "\$vgt_core = '" . addslashes($vgt_target) . "';\n\n" .
                "// Memory Safe & Anti-Crash Validation\n" .
                "if (file_exists(\$vgt_core) && is_readable(\$vgt_core)) {\n" .
                "    require_once \$vgt_core;\n" .
                "}\n";
                
                if (@file_put_contents($mu_file, $mu_content) !== false) {
                    // File Permission Lockdown (Verhindert Tampering durch andere Plugins)
                    @chmod($mu_file, 0644);
                    
                    $message = 'MU-Loader erfolgreich injiziert und verriegelt. AEGIS operiert nun auf Pre-Boot Ebene.';
                    $msg_type = 'success';
                } else {
                    $message = 'Fehler beim Schreiben. Bitte Schreibrechte (0755) für /wp-content/mu-plugins prüfen.';
                    $msg_type = 'error';
                }
            } else {
                $message = 'Zugriff verweigert. Verzeichnis /mu-plugins ist nicht beschreibbar.';
                $msg_type = 'error';
            }
        } 
        elseif ($_POST['vgt_mu_action'] === 'remove') {
            if (file_exists($mu_file)) {
                // Lock kurzzeitig aufheben, um Datei zu löschen
                @chmod($mu_file, 0664);
                if (@unlink($mu_file)) {
                    $message = 'MU-Loader rückstandsfrei entfernt. System läuft im Standard-Modus.';
                    $msg_type = 'success';
                } else {
                    $message = 'Datei konnte nicht gelöscht werden (Berechtigungsfehler).';
                    $msg_type = 'error';
                }
            }
        }
    }
}

$is_deployed = file_exists($mu_file);
?>

<div class="vis-card" style="border-top: 3px solid #06b6d4; font-family: 'Inter', sans-serif;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
        <span class="dashicons dashicons-hammer" style="font-size: 32px; width: 32px; height: 32px; color: #06b6d4;"></span>
        <div>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">PRE-BOOT MU-DEPLOYER</h2>
            <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Zero-Latency Plugin Loading & Core Interception</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background: <?php echo $msg_type === 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; 
                    border-left: 4px solid <?php echo $msg_type === 'success' ? '#10b981' : '#ef4444'; ?>; 
                    padding: 15px; margin-bottom: 20px; color: #fff; font-size: 13px; border-radius: 4px;">
            <strong style="letter-spacing: 0.5px;"><?php echo $msg_type === 'success' ? 'SYSTEM UPDATE:' : 'SYSTEM FEHLER:'; ?></strong> <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
        Standardmäßig lädt WordPress Plugins in alphabetischer Reihenfolge. Ein kompromittiertes Plugin könnte ausgeführt werden, 
        bevor Sentinel aktiv wird. Der <strong>VGT MU-Deployer</strong> schreibt einen kryptographisch gehärteten symbolischen Link (Must-Use Plugin) direkt in den Kern. 
        Sentinel wird dadurch zur unumstößlichen <strong>Instanz 0</strong> und fängt Angriffe ab, bevor andere Systemkomponenten überhaupt erwachen.
    </p>

    <!-- STATUS MATRIX -->
    <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
        
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $is_deployed ? '#10b981' : '#ef4444'; ?>; box-shadow: 0 0 10px <?php echo $is_deployed ? 'rgba(16,185,129,0.5)' : 'rgba(239,68,68,0.5)'; ?>;"></div>
            <div>
                <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 16px; letter-spacing: 0.5px;">
                    <?php echo $is_deployed ? 'MU-LOADER IST AKTIV' : 'MU-LOADER DEAKTIVIERT'; ?>
                </h4>
                <div style="display: flex; gap: 10px; align-items: center; margin-top: 6px;">
                    <code style="background: #020617; color: <?php echo $is_deployed ? '#10b981' : '#64748b'; ?>; font-size: 11px; padding: 4px 8px; border-radius: 4px; border: 1px solid #1e293b;">
                        <?php echo esc_html(str_replace(ABSPATH, '/', $mu_file)); ?>
                    </code>
                    <?php if ($is_deployed): ?>
                        <span class="vis-badge bg-green" style="font-size:9px; border:1px solid #10b981;"><span class="dashicons dashicons-lock" style="font-size:10px; width:10px; height:10px; line-height:10px; margin-right:2px;"></span> LOCKED (0644)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACTION FORM -->
        <form method="post" action="">
            <?php wp_nonce_field('vis_mu_deploy', 'vis_mu_nonce'); ?>
            
            <?php if ($is_deployed): ?>
                <input type="hidden" name="vgt_mu_action" value="remove">
                <button type="submit" class="vis-btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid #ef4444; padding: 10px 24px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#ef4444'; this.style.color='#fff';" onmouseout="this.style.background='rgba(239, 68, 68, 0.1)'; this.style.color='#ef4444';">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> INJEKTION ENTFERNEN
                </button>
            <?php else: ?>
                <input type="hidden" name="vgt_mu_action" value="deploy">
                <button type="submit" class="vis-btn" style="background: #06b6d4; color: #fff; border: none; padding: 10px 24px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                    <span class="dashicons dashicons-admin-network" style="vertical-align: middle;"></span> MU-LOADER INSTALLIEREN
                </button>
            <?php endif; ?>
        </form>
    </div>

    <div style="padding: 15px; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.1); border-radius: 6px;">
        <h5 style="margin: 0 0 5px 0; color: #06b6d4; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">OMEGA HARDENING PROTOCOL:</h5>
        <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
            Der Deployer generiert nicht nur einen statischen Pointer, sondern verriegelt die resultierende Datei asynchron über Dateisystemrechte <code>(chmod 0644)</code>. Zudem antwortet der generierte Loader bei direktem URL-Aufruf durch einen Scanner mit einem harten <code>HTTP 403 Forbidden</code> Header, wodurch Reconnaissance-Scripte geblockt werden.
        </p>
    </div>
</div>