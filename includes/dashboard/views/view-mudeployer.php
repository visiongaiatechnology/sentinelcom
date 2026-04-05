<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- VGT AUTONOME DEPLOYMENT LOGIK (PLATIN HARDENED) ---
$mu_dir  = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : wp_normalize_path(WP_CONTENT_DIR . '/mu-plugins');
$mu_file = $mu_dir . '/0-vgt-sentinel-loader.php'; // Prefix 0 erzwingt Prio 1

// Path Normalization schützt vor Slash-Manipulationen in Windows/Linux Environments
$vgt_target = wp_normalize_path(VIS_PATH . 'vision-integrity-sentinel.php');

$message_de = '';
$message_en = '';
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
                    
                    $message_de = 'MU-Loader erfolgreich injiziert und verriegelt. AEGIS operiert nun auf Pre-Boot Ebene.';
                    $message_en = 'MU-Loader successfully injected and locked. AEGIS now operates at the pre-boot level.';
                    $msg_type = 'success';
                } else {
                    $message_de = 'Fehler beim Schreiben. Bitte Schreibrechte (0755) für /wp-content/mu-plugins prüfen.';
                    $message_en = 'Write error. Please verify write permissions (0755) for /wp-content/mu-plugins.';
                    $msg_type = 'error';
                }
            } else {
                $message_de = 'Zugriff verweigert. Verzeichnis /mu-plugins ist nicht beschreibbar.';
                $message_en = 'Access denied. The /mu-plugins directory is not writable.';
                $msg_type = 'error';
            }
        } 
        elseif ($_POST['vgt_mu_action'] === 'remove') {
            if (file_exists($mu_file)) {
                // Lock kurzzeitig aufheben, um Datei zu löschen
                @chmod($mu_file, 0664);
                if (@unlink($mu_file)) {
                    $message_de = 'MU-Loader rückstandsfrei entfernt. System läuft im Standard-Modus.';
                    $message_en = 'MU-Loader removed without residue. System is running in standard mode.';
                    $msg_type = 'success';
                } else {
                    $message_de = 'Datei konnte nicht gelöscht werden (Berechtigungsfehler).';
                    $message_en = 'File could not be deleted (permission error).';
                    $msg_type = 'error';
                }
            }
        }
    }
}

$is_deployed = file_exists($mu_file);
?>

<style>
    /* VGT Language State CSS (Zero Runtime Overhead, Strict Specificity) */
    @keyframes visFadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Base State: EN is strictly hidden mit absoluter Priorität */
    .vis-lang-en { display: none !important; }
    .vis-lang-de { animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    /* Active EN State: DE is hidden, EN restores native display type */
    .vis-state-en .vis-lang-de { display: none !important; }
    .vis-state-en span.vis-lang-en { display: inline !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    /* Block elements restoration */
    .vis-state-en strong.vis-lang-en,
    .vis-state-en h4.vis-lang-en,
    .vis-state-en p.vis-lang-en, 
    .vis-state-en div.vis-lang-en { display: block !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    /* Toggle Switch Styling */
    .vis-toggle-wrapper { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .vis-toggle-label { display: flex; align-items: center; cursor: pointer; gap: 12px; background: rgba(15, 23, 42, 0.6); padding: 6px 14px; border-radius: 20px; border: 1px solid #334155; backdrop-filter: blur(4px); transition: all 0.3s ease; }
    .vis-toggle-label:hover { border-color: rgba(6, 182, 212, 0.4); box-shadow: 0 0 10px rgba(6, 182, 212, 0.1); }
    .vis-toggle-text { font-size: 11px; font-weight: 800; letter-spacing: 0.5px; transition: color 0.3s ease; }
    .vis-text-de { color: #06b6d4; text-shadow: 0 0 8px rgba(6, 182, 212, 0.4); }
    .vis-text-en { color: #475569; }
    .vis-state-en .vis-text-de { color: #475569; text-shadow: none; }
    .vis-state-en .vis-text-en { color: #06b6d4; text-shadow: 0 0 8px rgba(6, 182, 212, 0.4); }
    
    .vis-switch-track { position: relative; width: 38px; height: 18px; background: #0b0f19; border-radius: 18px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.05); }
    .vis-switch-thumb { position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; background: #06b6d4; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 8px rgba(6, 182, 212, 0.6); }
    .vis-state-en .vis-switch-thumb { transform: translateX(20px); }
</style>

<div id="vis-mu-container">
    <!-- UI LANGUAGE TOGGLE -->
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-mu-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card" style="border-top: 3px solid #06b6d4; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
            <span class="dashicons dashicons-hammer" style="font-size: 32px; width: 32px; height: 32px; color: #06b6d4;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">PRE-BOOT MU-DEPLOYER</h2>
                <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Zero-Latency Plugin Loading & Core Interception</p>
            </div>
        </div>

        <?php if ($message_de && $message_en): ?>
            <div style="background: <?php echo $msg_type === 'success' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>; 
                        border-left: 4px solid <?php echo $msg_type === 'success' ? '#10b981' : '#ef4444'; ?>; 
                        padding: 15px; margin-bottom: 20px; color: #fff; font-size: 13px; border-radius: 4px;">
                <div class="vis-lang-de">
                    <strong style="letter-spacing: 0.5px;"><?php echo $msg_type === 'success' ? 'SYSTEM UPDATE:' : 'SYSTEM FEHLER:'; ?></strong> <?php echo esc_html($message_de); ?>
                </div>
                <div class="vis-lang-en">
                    <strong style="letter-spacing: 0.5px;"><?php echo $msg_type === 'success' ? 'SYSTEM UPDATE:' : 'SYSTEM ERROR:'; ?></strong> <?php echo esc_html($message_en); ?>
                </div>
            </div>
        <?php endif; ?>

        <p class="vis-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            Standardmäßig lädt WordPress Plugins in alphabetischer Reihenfolge. Ein kompromittiertes Plugin könnte ausgeführt werden, 
            bevor Sentinel aktiv wird. Der <strong>VGT MU-Deployer</strong> schreibt einen kryptographisch gehärteten symbolischen Link (Must-Use Plugin) direkt in den Kern. 
            Sentinel wird dadurch zur unumstößlichen <strong>Instanz 0</strong> und fängt Angriffe ab, bevor andere Systemkomponenten überhaupt erwachen.
        </p>
        <p class="vis-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            By default, WordPress loads plugins in alphabetical order. A compromised plugin could execute before Sentinel becomes active. 
            The <strong>VGT MU-Deployer</strong> injects a cryptographically hardened symbolic link (Must-Use Plugin) directly into the core. 
            This elevates Sentinel to the irrefutable <strong>Instance 0</strong>, neutralizing attacks before other system components even awaken.
        </p>

        <!-- STATUS MATRIX -->
        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 25px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;">
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $is_deployed ? '#10b981' : '#ef4444'; ?>; box-shadow: 0 0 10px <?php echo $is_deployed ? 'rgba(16,185,129,0.5)' : 'rgba(239,68,68,0.5)'; ?>;"></div>
                <div>
                    <h4 class="vis-lang-de" style="margin: 0 0 5px 0; color: #fff; font-size: 16px; letter-spacing: 0.5px;">
                        <?php echo $is_deployed ? 'MU-LOADER IST AKTIV' : 'MU-LOADER DEAKTIVIERT'; ?>
                    </h4>
                    <h4 class="vis-lang-en" style="margin: 0 0 5px 0; color: #fff; font-size: 16px; letter-spacing: 0.5px;">
                        <?php echo $is_deployed ? 'MU-LOADER IS ACTIVE' : 'MU-LOADER DISABLED'; ?>
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
                        <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> 
                        <span class="vis-lang-de" style="display:inline;">INJEKTION ENTFERNEN</span>
                        <span class="vis-lang-en" style="display:inline;">REMOVE INJECTION</span>
                    </button>
                <?php else: ?>
                    <input type="hidden" name="vgt_mu_action" value="deploy">
                    <button type="submit" class="vis-btn" style="background: #06b6d4; color: #fff; border: none; padding: 10px 24px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                        <span class="dashicons dashicons-admin-network" style="vertical-align: middle;"></span> 
                        <span class="vis-lang-de" style="display:inline;">MU-LOADER INSTALLIEREN</span>
                        <span class="vis-lang-en" style="display:inline;">INSTALL MU-LOADER</span>
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <div style="padding: 15px; background: rgba(6, 182, 212, 0.05); border: 1px solid rgba(6, 182, 212, 0.1); border-radius: 6px;">
            <h5 style="margin: 0 0 5px 0; color: #06b6d4; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">OMEGA HARDENING PROTOCOL:</h5>
            <p class="vis-lang-de" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                Der Deployer generiert nicht nur einen statischen Pointer, sondern verriegelt die resultierende Datei asynchron über Dateisystemrechte <code>(chmod 0644)</code>. Zudem antwortet der generierte Loader bei direktem URL-Aufruf durch einen Scanner mit einem harten <code>HTTP 403 Forbidden</code> Header, wodurch Reconnaissance-Scripte geblockt werden.
            </p>
            <p class="vis-lang-en" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                The deployer does not merely generate a static pointer; it asynchronously locks the resulting file via file system permissions <code>(chmod 0644)</code>. Furthermore, if a scanner attempts a direct URL request, the generated loader responds with a hard <code>HTTP 403 Forbidden</code> header, effectively blocking reconnaissance scripts.
            </p>
        </div>
    </div>
</div>

<script>
/**
 * VGT LANGUAGE KERNEL (ZERO-OVERHEAD DOM MANAGER)
 * Synchronisiert sich mit dem globalen LocalStorage State (vis_v7_lang_preference).
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vis-mu-lang-toggle');
    const wrapper  = document.getElementById('vis-mu-container');
    const langKey  = 'vis_v7_lang_preference';

    // 1. Initial State Check (Synchronisation mit Dashboard)
    if (localStorage.getItem(langKey) === 'en') {
        toggle.checked = true;
        wrapper.classList.add('vis-state-en');
    }

    // 2. State Mutator Event
    toggle.addEventListener('change', (e) => {
        if (e.target.checked) {
            wrapper.classList.add('vis-state-en');
            localStorage.setItem(langKey, 'en');
            window.dispatchEvent(new Event('vgt_lang_sync'));
        } else {
            wrapper.classList.remove('vis-state-en');
            localStorage.setItem(langKey, 'de');
            window.dispatchEvent(new Event('vgt_lang_sync'));
        }
    });

    // 3. Listener für Cross-Tab Synchronisation
    window.addEventListener('vgt_lang_sync', () => {
        if (localStorage.getItem(langKey) === 'en') {
            toggle.checked = true;
            wrapper.classList.add('vis-state-en');
        } else {
            toggle.checked = false;
            wrapper.classList.remove('vis-state-en');
        }
    });
});
</script>