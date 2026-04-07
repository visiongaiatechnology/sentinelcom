<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 

if (!class_exists('VIS_Hades')) require_once VIS_PATH . 'includes/modules/hades/class-vis-hades.php';
$hades_instance = new VIS_Hades([]); 
$is_nginx = (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false);

if (!isset($opt)) {
    $opt = get_option('vis_config', []);
}

$hades_active = !empty($opt['hades_enabled']) ? 1 : 0;
$login_slug = isset($opt['hades_login_slug']) && !empty($opt['hades_login_slug']) ? esc_attr($opt['hades_login_slug']) : 'wp-login.php';
$admin_slug = isset($opt['hades_admin_slug']) && !empty($opt['hades_admin_slug']) ? esc_attr($opt['hades_admin_slug']) : 'wp-admin';

$path_mappings = [
    ['old' => 'wp-content/themes',  'new' => 'content/ui'],
    ['old' => 'wp-content/plugins', 'new' => 'content/lib'],
    ['old' => 'wp-content/uploads', 'new' => 'storage'],
    ['old' => 'wp-includes',        'new' => 'core']
];
?>

<style>
    @keyframes visFadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
    
    .vis-lang-en { display: none !important; }
    .vis-lang-de { animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .vis-state-en .vis-lang-de { display: none !important; }
    .vis-state-en span.vis-lang-en { display: inline !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .vis-state-en strong.vis-lang-en,
    .vis-state-en h4.vis-lang-en,
    .vis-state-en p.vis-lang-en, 
    .vis-state-en div.vis-lang-en { display: block !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .vis-toggle-wrapper { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .vis-toggle-label { display: flex; align-items: center; cursor: pointer; gap: 12px; background: rgba(15, 23, 42, 0.6); padding: 6px 14px; border-radius: 20px; border: 1px solid #334155; backdrop-filter: blur(4px); transition: all 0.3s ease; }
    .vis-toggle-label:hover { border-color: rgba(6, 182, 212, 0.4); box-shadow: 0 0 10px rgba(6, 182, 212, 0.1); }
    .vis-toggle-text { font-size: 11px; font-weight: 800; letter-spacing: 0.5px; transition: color 0.3s ease; }
    .vis-text-de { color: #8b5cf6; text-shadow: 0 0 8px rgba(139, 92, 246, 0.4); } /* Adjusted to Hades Purple */
    .vis-text-en { color: #475569; }
    .vis-state-en .vis-text-de { color: #475569; text-shadow: none; }
    .vis-state-en .vis-text-en { color: #8b5cf6; text-shadow: 0 0 8px rgba(139, 92, 246, 0.4); } /* Adjusted to Hades Purple */
    
    .vis-switch-track { position: relative; width: 38px; height: 18px; background: #0b0f19; border-radius: 18px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.05); }
    .vis-switch-thumb { position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; background: #8b5cf6; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 8px rgba(139, 92, 246, 0.6); }
    .vis-state-en .vis-switch-thumb { transform: translateX(20px); }

    .vgt-hades-toggle { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .vgt-hades-toggle input { opacity: 0; width: 0; height: 0; }
    .vgt-hades-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 24px; }
    .vgt-hades-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .vgt-hades-toggle input:checked + .vgt-hades-slider { background-color: #8b5cf6; } /* VGT Purple */
    .vgt-hades-toggle input:checked + .vgt-hades-slider:before { transform: translateX(20px); }
    .vgt-hades-toggle input:focus + .vgt-hades-slider { box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2); }
    
    .vgt-matrix-row { display: flex; align-items: center; justify-content: space-between; background: rgba(15, 23, 42, 0.4); padding: 12px 20px; border-radius: 6px; border: 1px solid #1e293b; transition: all 0.2s ease; }
    .vgt-matrix-row:hover { border-color: #8b5cf6; background: rgba(139, 92, 246, 0.05); }
</style>

<div id="vis-hades-container">
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-hades-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card" style="border-top: 3px solid #8b5cf6; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
            <span class="dashicons dashicons-hidden" style="font-size: 32px; width: 32px; height: 32px; color: #8b5cf6;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">HADES STEALTH ENGINE</h2>
                <p class="vis-lang-de" style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Camouflage, Pfad-Verschleierung & Ghost Mode</p>
                <p class="vis-lang-en" style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Camouflage, Path Obfuscation & Ghost Mode</p>
            </div>
        </div>

        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">GHOST MODE (CLOAKING)</h4>
                    <p class="vis-lang-de" style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Aktiviert die globale Maskierung von Systempfaden und blockiert direkte Zugriffe auf den Core.</p>
                    <p class="vis-lang-en" style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Activates global masking of system paths and unconditionally blocks direct access to the core.</p>
                </div>
                
                <label class="vgt-hades-toggle">
                    <input type="checkbox" name="vis_config[hades_enabled]" value="1" <?php checked($hades_active, 1); ?>>
                    <span class="vgt-hades-slider"></span>
                </label>
            </div>
        </div>

        <div style="margin-bottom: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-randomize" style="color: #8b5cf6;"></span>
                VIRTUAL PATH ROUTING MATRIX
            </h4>
            <p class="vis-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 15px;">
                Hades schreibt die WordPress-Kernverzeichnisse virtuell um. Automatisierte Enumerations-Angriffe (WPScan, DirBuster) scannen nach Standardpfaden und laufen so mathematisch garantiert ins Leere. <br>
                Nach Aktivierung muss die Permalinkstruktur einmal unter Einstellungen neu gespeichert werden. 
            </p>
            <p class="vis-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 15px;">
                Hades virtually rewrites WordPress core directories. Automated enumeration attacks (WPScan, DirBuster) scanning for standard signatures are mathematically guaranteed to fail. <br>
                After activation, the permalink structure must be re-saved once in the settings to flush rewrite rules. 
            </p>

            <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                <?php foreach ($path_mappings as $mapping): ?>
                <div class="vgt-matrix-row">
                    <div style="display: flex; align-items: center; gap: 10px; width: 45%;">
                        <span class="dashicons dashicons-folder" style="color: #64748b;"></span>
                        <code style="color: #ef4444; background: transparent; padding: 0; font-size: 13px; border: none;">/<?php echo $mapping['old']; ?></code>
                    </div>
                    <div style="color: #8b5cf6; display: flex; align-items: center; opacity: 0.7;">
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; width: 45%; justify-content: flex-end;">
                        <code style="color: #10b981; background: transparent; padding: 0; font-size: 13px; font-weight: 700; border: none;">/<?php echo $mapping['new']; ?></code>
                        <span class="dashicons dashicons-shield" style="color: #10b981; font-size: 16px; width: 16px; height: 16px;"></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="padding-top: 20px; border-top: 1px solid var(--vis-border);">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;">CUSTOM ENTRY POINTS</h4>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <div>
                    <strong style="color: #e2e8f0; font-size: 13px;">LOGIN SLUG</strong>
                    <p class="vis-lang-de" style="margin: 3px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Ersetzt <code>wp-login.php</code></p>
                    <p class="vis-lang-en" style="margin: 3px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Replaces <code>wp-login.php</code></p>
                </div>
                <div>
                    <input type="text" name="vis_config[hades_login_slug]" value="<?php echo $login_slug; ?>" 
                           style="background:#0f172a; border:1px solid #334155; color:#8b5cf6; padding:8px 12px; border-radius:4px; font-family:monospace; font-weight: 700; text-align: right; width: 200px; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#334155'">
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: #e2e8f0; font-size: 13px;">DASHBOARD SLUG</strong>
                    <p class="vis-lang-de" style="margin: 3px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Ersetzt <code>wp-admin</code> <strong style="color:#f59e0b;">(Vorsicht: Kann Plugins brechen!)</strong></p>
                    <p class="vis-lang-en" style="margin: 3px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Replaces <code>wp-admin</code> <strong style="color:#f59e0b;">(Warning: May break plugins!)</strong></p>
                </div>
                <div>
                    <input type="text" name="vis_config[hades_admin_slug]" value="<?php echo $admin_slug; ?>" 
                           style="background:#0f172a; border:1px solid #334155; color:#8b5cf6; padding:8px 12px; border-radius:4px; font-family:monospace; font-weight: 700; text-align: right; width: 200px; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#334155'">
                </div>
            </div>
        </div>

        <?php if ($is_nginx && $hades_active): ?>
            <div style="margin-top:30px; padding:20px; background:rgba(245, 158, 11, 0.05); border:1px solid rgba(245, 158, 11, 0.3); border-radius:6px;">
                <div style="display:flex; gap:10px; color:#f59e0b; margin-bottom:10px; align-items: center;">
                    <span class="dashicons dashicons-warning" style="font-size:20px;"></span>
                    <strong class="vis-lang-de" style="font-size:14px; letter-spacing: 0.5px; display:inline;">NGINX DETECTED - MANUELLE KONFIGURATION NÖTIG</strong>
                    <strong class="vis-lang-en" style="font-size:14px; letter-spacing: 0.5px; display:inline;">NGINX DETECTED - MANUAL CONFIGURATION REQUIRED</strong>
                </div>
                
                <p class="vis-lang-de" style="font-size:13px; color:var(--vis-text-secondary); margin-bottom:15px; line-height: 1.5;">
                    Da Hades auf einem NGINX-Server operiert, müssen die Routing-Regeln manuell in den Server-Block (<code>nginx.conf</code>) injiziert werden. Kopieren Sie den folgenden Block:
                </p>
                <p class="vis-lang-en" style="font-size:13px; color:var(--vis-text-secondary); margin-bottom:15px; line-height: 1.5;">
                    Because Hades is operating on an NGINX environment, the routing rules must be manually injected into the server block (<code>nginx.conf</code>). Copy the following payload:
                </p>
                
                <textarea readonly style="width:100%; height:160px; background:#020617; color:#a5b4fc; border:1px solid #334155; border-radius:4px; padding:15px; font-family:monospace; font-size:12px; resize: none;" onfocus="this.select();">
# VisionGaia Hades Stealth Rules
rewrite ^/content/ui/(.*) /wp-content/themes/$1 last;
rewrite ^/content/lib/(.*) /wp-content/plugins/$1 last;
rewrite ^/storage/(.*) /wp-content/uploads/$1 last;
rewrite ^/content/(.*) /wp-content/$1 last;
rewrite ^/core/(.*) /wp-includes/$1 last;
<?php echo $hades_instance->get_nginx_routing_rules(); ?>
</textarea>
            </div>
        <?php endif; ?>

        <?php if (!$is_nginx && $hades_active): ?>
             <div style="margin-top:30px; padding:15px 20px; background:rgba(16, 185, 129, 0.05); border:1px solid rgba(16, 185, 129, 0.2); border-radius:6px; color:#10b981; font-size:13px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-yes" style="font-size: 20px; width: 20px; height: 20px;"></span>
                <div>
                    <strong style="letter-spacing: 0.5px;">APACHE MODE ACTIVE:</strong> 
                    <span class="vis-lang-de">Routing & Asset Rules wurden erfolgreich in die System-<code>.htaccess</code> injiziert.</span>
                    <span class="vis-lang-en">Routing & asset rules have been successfully injected into the system <code>.htaccess</code>.</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vis-hades-lang-toggle');
    const wrapper  = document.getElementById('vis-hades-container');
    const langKey  = 'vis_v7_lang_preference';

    if (localStorage.getItem(langKey) === 'en') {
        toggle.checked = true;
        wrapper.classList.add('vis-state-en');
    }

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
