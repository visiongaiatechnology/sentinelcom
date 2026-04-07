<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- AUTONOMOUS STORAGE LOGIC (O(1) STATE MUTATION FOR DYNAMIC ARRAYS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vis_context']) && $_POST['vis_context'] === 'antibot') {
    if (isset($_POST['vis_save_config']) && check_admin_referer('vis_save_config')) {
        $opt = get_option('vis_config', []);
        
        // Sanitization and Binding
        $opt['antibot_enabled'] = isset($_POST['vis_config']['antibot_enabled']) ? 1 : 0;
        $opt['antibot_difficulty'] = intval($_POST['vis_config']['antibot_difficulty'] ?? 3);
        $opt['antibot_custom_hooks'] = isset($_POST['vis_config']['antibot_custom_hooks']) ? array_map('sanitize_text_field', $_POST['vis_config']['antibot_custom_hooks']) : [];
        
        // Native Integrations Binding
        $native_keys = ['antibot_comments', 'antibot_woo', 'antibot_cf7', 'antibot_wpforms', 'antibot_gform'];
        foreach ($native_keys as $key) {
            $opt[$key] = isset($_POST['vis_config'][$key]) ? 1 : 0;
        }
        
        update_option('vis_config', $opt);
        
        echo '<div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--vis-success); padding: 15px; margin-bottom: 20px; color: #fff; font-weight: 600; border-radius: 4px;">';
        echo '<span class="dashicons dashicons-yes-alt"></span> ANTIBOT Matrix successfully recalibrated.';
        echo '</div>';
    }
}

$opt = get_option('vis_config', []);
$antibot_active = !empty($opt['antibot_enabled']) ? 1 : 0;
$difficulty = $opt['antibot_difficulty'] ?? 3;
$custom_hooks = $opt['antibot_custom_hooks'] ?? [];

if (!function_exists('get_plugins')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins = get_plugins();
?>

<style>
    /* VGT Language State CSS */
    @keyframes visFadeIn { from { opacity: 0; transform: translateY(2px); } to { opacity: 1; transform: translateY(0); } }
    .vis-lang-en { display: none !important; }
    .vis-lang-de { animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .vis-state-en .vis-lang-de { display: none !important; }
    .vis-state-en span.vis-lang-en { display: inline !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .vis-state-en strong.vis-lang-en, .vis-state-en h4.vis-lang-en, .vis-state-en p.vis-lang-en, .vis-state-en div.vis-lang-en { display: block !important; animation: visFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .vis-toggle-wrapper { display: flex; justify-content: flex-end; margin-bottom: 20px; }
    .vis-toggle-label { display: flex; align-items: center; cursor: pointer; gap: 12px; background: rgba(15, 23, 42, 0.6); padding: 6px 14px; border-radius: 20px; border: 1px solid #334155; backdrop-filter: blur(4px); transition: all 0.3s ease; }
    .vis-toggle-label:hover { border-color: rgba(6, 182, 212, 0.4); box-shadow: 0 0 10px rgba(6, 182, 212, 0.1); }
    .vis-toggle-text { font-size: 11px; font-weight: 800; letter-spacing: 0.5px; transition: color 0.3s ease; }
    .vis-text-de { color: #f59e0b; text-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
    .vis-text-en { color: #475569; }
    .vis-state-en .vis-text-de { color: #475569; text-shadow: none; }
    .vis-state-en .vis-text-en { color: #f59e0b; text-shadow: 0 0 8px rgba(245, 158, 11, 0.4); }
    
    .vis-switch-track { position: relative; width: 38px; height: 18px; background: #0b0f19; border-radius: 18px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.05); }
    .vis-switch-thumb { position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; background: #f59e0b; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 8px rgba(245, 158, 11, 0.6); }
    .vis-state-en .vis-switch-thumb { transform: translateX(20px); }

    .vis-hook-list { max-height: 280px; overflow-y: auto; background: #020617; padding: 16px; border-radius: 8px; border: 1px solid var(--vis-border); margin-top: 15px; }
    .vis-hook-list::-webkit-scrollbar { width: 6px; }
    .vis-hook-list::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    .vis-hook-item { display: flex; align-items: center; margin-bottom: 12px; font-family: var(--vis-font-mono); font-size: 13px; color: var(--vis-text-secondary); padding: 8px; border-radius: 6px; transition: background 0.2s ease; }
    .vis-hook-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
    .vis-hook-item input { margin-right: 14px; accent-color: #f59e0b; cursor: pointer; }
</style>

<div id="vis-antibot-container">
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-antibot-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card" style="border-top: 3px solid #f59e0b;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
            <span class="dashicons dashicons-shield-alt" style="font-size: 32px; width: 32px; height: 32px; color: #f59e0b;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">ANTIBOT ENGINE (POW)</h2>
                <p class="vis-lang-de" style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">DSGVO-konforme Zero-UI Proof-of-Work Bot-Abwehr</p>
                <p class="vis-lang-en" style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">GDPR-compliant Zero-UI Proof-of-Work Bot Defense</p>
            </div>
        </div>

        <p class="vis-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            VGT Antibot eliminiert die Notwendigkeit für Captchas oder Checkboxen. Legitime Nutzer lösen unsichtbar im Hintergrund eine kryptografische SHA-256 Aufgabe (Proof-of-Work). Bots werden dadurch mathematisch und wirtschaftlich ineffizient blockiert. Die Engine ist tief im TITAN Netzwerk verankert.
        </p>
        <p class="vis-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            VGT Antibot eliminates the need for captchas or checkboxes. Legitimate users invisibly solve a cryptographic SHA-256 challenge (Proof-of-Work) in the background. Bots are blocked because solving these challenges is computationally and economically inefficient for mass attacks. The engine is deeply anchored within the TITAN network.
        </p>

        <!-- CORE SETTINGS -->
        <div class="vis-switch-row" style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid var(--vis-border); margin-bottom: 20px;">
            <div class="vis-label-group">
                <strong class="vis-lang-de">Global Infiltration aktivieren</strong>
                <strong class="vis-lang-en">Enable Global Infiltration</strong>
                <p class="vis-lang-de">Injiziert Listener für alle DOM-Events und Formulare automatisch.</p>
                <p class="vis-lang-en">Automatically injects listeners for all DOM events and forms.</p>
            </div>
            <label class="vis-switch">
                <input type="checkbox" name="vis_config[antibot_enabled]" value="1" <?php checked($antibot_active, 1); ?>>
                <span class="slider" style="background-color: <?php echo $antibot_active ? '#f59e0b' : '#334155'; ?>"></span>
            </label>
        </div>

        <div class="vis-switch-row" style="background: rgba(15, 23, 42, 0.4); padding: 20px; border-radius: 8px; border: 1px solid var(--vis-border); margin-bottom: 30px;">
            <div class="vis-label-group">
                <strong class="vis-lang-de">Kryptografische Komplexität</strong>
                <strong class="vis-lang-en">Cryptographic Difficulty</strong>
                <p class="vis-lang-de">Höhere Level beanspruchen mehr CPU beim Client (0-Padding Target).</p>
                <p class="vis-lang-en">Higher levels demand more CPU capacity from the client (0-padding target).</p>
            </div>
            <div>
                <select name="vis_config[antibot_difficulty]" style="background: #020617; border-color: #334155;">
                    <option value="2" <?php selected($difficulty, 2); ?>>Level 2: Low-Latency Mode</option>
                    <option value="3" <?php selected($difficulty, 3); ?>>Level 3: VGT Standard Protocol</option>
                    <option value="4" <?php selected($difficulty, 4); ?>>Level 4: Maximum Security (High CPU)</option>
                </select>
            </div>
        </div>

        <!-- NATIVE INTEGRATIONS -->
        <div style="margin-bottom: 30px;">
            <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-admin-plugins" style="color:#f59e0b;"></span> NATIVE INTEGRATIONEN
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px;">
                <?php 
                $native_hooks_data = [
                    'antibot_comments' => ['label' => 'WP Comments', 'desc_de' => 'Schützt den nativen WP Kommentarbereich.', 'desc_en' => 'Protects native WP comments.'],
                    'antibot_woo'      => ['label' => 'WooCommerce', 'desc_de' => 'Sichert Login, Registrierung & Checkout.', 'desc_en' => 'Secures login, register & checkout.'],
                    'antibot_cf7'      => ['label' => 'Contact Form 7', 'desc_de' => 'Blockiert Spam-Mails über CF7.', 'desc_en' => 'Blocks spam emails via CF7.'],
                    'antibot_wpforms'  => ['label' => 'WPForms', 'desc_de' => 'Validiert alle WPForms Einsendungen.', 'desc_en' => 'Validates WPForms submissions.'],
                    'antibot_gform'    => ['label' => 'Gravity Forms', 'desc_de' => 'Verhindert Bot-Einsendungen.', 'desc_en' => 'Prevents bot submissions.']
                ];
                foreach($native_hooks_data as $key => $data):
                    $is_active = !empty($opt[$key]) ? 1 : 0;
                ?>
                <div style="background: rgba(15, 23, 42, 0.4); padding: 15px 20px; border-radius: 8px; border: 1px solid var(--vis-border); display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#f59e0b';" onmouseout="this.style.borderColor='var(--vis-border)';">
                    <div>
                        <strong style="color: #fff; font-size: 13px; display: block; margin-bottom: 4px; letter-spacing: 0.5px;"><?php echo $data['label']; ?></strong>
                        <span class="vis-lang-de" style="color: var(--vis-text-secondary); font-size: 11px;"><?php echo $data['desc_de']; ?></span>
                        <span class="vis-lang-en" style="color: var(--vis-text-secondary); font-size: 11px;"><?php echo $data['desc_en']; ?></span>
                    </div>
                    <label class="vis-switch" style="transform: scale(0.85); transform-origin: right center; margin-left: 15px;">
                        <input type="checkbox" name="vis_config[<?php echo $key; ?>]" value="1" <?php checked($is_active, 1); ?>>
                        <span class="slider" style="background-color: <?php echo $is_active ? '#f59e0b' : '#334155'; ?>"></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
            <!-- SCANNER -->
            <div style="border: 1px solid var(--vis-border); border-radius: 8px; padding: 25px; background: rgba(255,255,255,0.02);">
                <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;"><span class="dashicons dashicons-search" style="color:#f59e0b;"></span> DEEP PLUGIN SCANNER</h4>
                <p class="vis-lang-de" style="color: var(--vis-text-secondary); font-size: 12px; margin-bottom: 15px;">Extrahieren Sie Ausführungspfade (Hooks) aus beliebigen installierten Plugins via AST-Regex Parsing.</p>
                <p class="vis-lang-en" style="color: var(--vis-text-secondary); font-size: 12px; margin-bottom: 15px;">Extract execution pathways (hooks) from any installed module via AST-Regex parsing.</p>
                
                <div style="display: flex; gap: 10px;">
                    <select id="vis-plugin-select" style="flex-grow: 1;">
                        <option value="">Select Target Module...</option>
                        <?php foreach ($all_plugins as $path => $plugin): ?>
                            <option value="<?php echo esc_attr($path); ?>"><?php echo esc_html($plugin['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="vis-btn vis-btn-ghost" id="vis-scan-hooks-btn" style="color:#f59e0b; border-color:#f59e0b;">
                        SCAN
                    </button>
                </div>

                <div id="vis-scan-results" style="display:none; margin-top: 15px;">
                    <strong style="color: #fff; font-size: 12px;">Identified Neural Pathways:</strong>
                    <div class="vis-hook-list" id="vis-hook-container"></div>
                </div>
            </div>

            <!-- ACTIVE HOOKS MATRIX -->
            <div style="border: 1px solid var(--vis-border); border-radius: 8px; padding: 25px; background: rgba(255,255,255,0.02);">
                <h4 style="margin: 0 0 15px 0; color: #fff; font-size: 14px;"><span class="dashicons dashicons-admin-links" style="color:#f59e0b;"></span> DYNAMIC EXECUTION HOOKS</h4>
                <p class="vis-lang-de" style="color: var(--vis-text-secondary); font-size: 12px;">Hooks, die zusätzlich durch die PoW Matrix geschützt werden.</p>
                <p class="vis-lang-en" style="color: var(--vis-text-secondary); font-size: 12px;">Hooks that are additionally protected by the PoW Matrix.</p>

                <div class="vis-hook-list" id="vis-active-hooks" style="margin-top: 0;">
                    <?php if (empty($custom_hooks)): ?>
                        <span class="vis-lang-de" style="color:var(--vis-text-secondary); font-size:12px;">Keine dynamischen Hooks aktiv.</span>
                        <span class="vis-lang-en" style="color:var(--vis-text-secondary); font-size:12px;">No dynamic hooks active.</span>
                    <?php else: ?>
                        <?php foreach ($custom_hooks as $hook): ?>
                            <div class="vis-hook-item">
                                <input type="checkbox" name="vis_config[antibot_custom_hooks][]" value="<?php echo esc_attr($hook); ?>" checked>
                                <?php echo esc_html($hook); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Language Toggle
    const toggle = document.getElementById('vis-antibot-lang-toggle');
    const wrapper = document.getElementById('vis-antibot-container');
    const langKey = 'vis_v7_lang_preference';

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
        toggle.checked = localStorage.getItem(langKey) === 'en';
        if (toggle.checked) wrapper.classList.add('vis-state-en');
        else wrapper.classList.remove('vis-state-en');
    });

    // Scanner Logic (AJAX)
    const scanBtn = document.getElementById('vis-scan-hooks-btn');
    if (scanBtn) {
        scanBtn.addEventListener('click', async () => {
            const plugin = document.getElementById('vis-plugin-select').value;
            if (!plugin) return alert('Select a target module first.');

            scanBtn.innerHTML = '<span class="dashicons dashicons-update spin"></span>';
            scanBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'vis_scan_plugin');
            formData.append('plugin_file', plugin);
            formData.append('nonce', visConfig.nonce);

            try {
                const response = await fetch(visConfig.ajaxUrl, { method: 'POST', body: formData });
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('vis-hook-container');
                    container.innerHTML = '';
                    
                    data.data.hooks.forEach(hook => {
                        if (hook.includes('submit') || hook.includes('process') || hook.includes('validate') || hook.includes('error') || hook.includes('save') || hook.includes('insert')) {
                            container.innerHTML += `
                                <div class="vis-hook-item">
                                    <input type="checkbox" form="dummy-form" onchange="addHookToActive(this)" value="${hook}">
                                    ${hook}
                                </div>
                            `;
                        }
                    });
                    document.getElementById('vis-scan-results').style.display = 'block';
                } else {
                    alert('VGT SENTINEL Scan Error: ' + data.data);
                }
            } catch (e) {
                alert('System Failure during scan.');
            }
            
            scanBtn.innerHTML = 'SCAN';
            scanBtn.disabled = false;
        });
    }
});

function addHookToActive(checkbox) {
    if (checkbox.checked) {
        const activeContainer = document.getElementById('vis-active-hooks');
        
        if (activeContainer.innerHTML.includes('Keine dynamischen Hooks aktiv.') || activeContainer.innerHTML.includes('No dynamic hooks active.')) {
            activeContainer.innerHTML = '';
        }
        
        if (!document.querySelector(`input[name="vis_config[antibot_custom_hooks][]"][value="${checkbox.value}"]`)) {
            activeContainer.insertAdjacentHTML('beforeend', `
                <div class="vis-hook-item">
                    <input type="checkbox" name="vis_config[antibot_custom_hooks][]" value="${checkbox.value}" checked>
                    ${checkbox.value}
                </div>
            `);
        }
    }
}
</script>