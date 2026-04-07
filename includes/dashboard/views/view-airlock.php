<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

if (!isset($opt)) {
    $opt = get_option('vis_config', []);
}
$airlock_active = !empty($opt['airlock_enabled']) ? 1 : 0;
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
    .vis-text-de { color: #06b6d4; text-shadow: 0 0 8px rgba(6, 182, 212, 0.4); }
    .vis-text-en { color: #475569; }
    .vis-state-en .vis-text-de { color: #475569; text-shadow: none; }
    .vis-state-en .vis-text-en { color: #06b6d4; text-shadow: 0 0 8px rgba(6, 182, 212, 0.4); }
    
    .vis-switch-track { position: relative; width: 38px; height: 18px; background: #0b0f19; border-radius: 18px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5), inset 0 0 0 1px rgba(255,255,255,0.05); }
    .vis-switch-thumb { position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; background: #06b6d4; border-radius: 50%; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 8px rgba(6, 182, 212, 0.6); }
    .vis-state-en .vis-switch-thumb { transform: translateX(20px); }

    .vgt-airlock-toggle { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .vgt-airlock-toggle input { opacity: 0; width: 0; height: 0; }
    .vgt-airlock-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 24px; }
    .vgt-airlock-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .vgt-airlock-toggle input:checked + .vgt-airlock-slider { background-color: #f59e0b; }
    .vgt-airlock-toggle input:checked + .vgt-airlock-slider:before { transform: translateX(20px); }
    .vgt-airlock-toggle input:focus + .vgt-airlock-slider { box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2); }
</style>

<div id="vis-airlock-container">
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-airlock-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card" style="border-top: 3px solid #f59e0b; font-family: 'Inter', sans-serif;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
            <span class="dashicons dashicons-upload" style="font-size: 32px; width: 32px; height: 32px; color: #f59e0b;"></span>
            <div>
                <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">AIRLOCK GUARD</h2>
                <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Real-Time Upload Sanitization & Binary Analysis</p>
            </div>
        </div>

        <p class="vis-lang-de" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            Airlock überwacht den gesamten <code>multipart/form-data</code> Stream während Datei-Uploads. 
            Bevor WordPress eine Datei in die Mediathek aufnimmt, führt Airlock eine binäre Tiefenanalyse durch, um PHP-Wrapper, 
            obfuskierte Skripte oder "Polyglot"-Files (Bilder mit verstecktem Code) zu identifizieren und zu neutralisieren.
        </p>
        <p class="vis-lang-en" style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
            Airlock monitors the entire <code>multipart/form-data</code> stream during file uploads. 
            Before WordPress processes a file into the media library, Airlock executes a deep binary analysis to identify and neutralize PHP wrappers, 
            obfuscated scripts, or "polyglot" files (images embedded with hidden malicious code).
        </p>

        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 class="vis-lang-de" style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">Airlock Schutz aktivieren</h4>
                    <h4 class="vis-lang-en" style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">Enable Airlock Protection</h4>
                    
                    <p class="vis-lang-de" style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Aktiviert die Echtzeit-Sanitierung für alle Uploads (Strict Allowlisting).</p>
                    <p class="vis-lang-en" style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Activates real-time sanitization for all uploads (Strict Allowlisting).</p>
                </div>
                
                <label class="vgt-airlock-toggle">
                    <input type="checkbox" name="vis_config[airlock_enabled]" value="1" <?php checked($airlock_active, 1); ?>>
                    <span class="vgt-airlock-slider"></span>
                </label>
            </div>
        </div>

        <div style="padding: 15px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.1); border-radius: 6px;">
            <h5 style="margin: 0 0 5px 0; color: #f59e0b; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">PRO-TIP:</h5>
            <p class="vis-lang-de" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                In der Platin-Version von Sentinel nutzt Airlock zusätzlich die <strong>ORACLE AI</strong>, um auch komplexeste Steganographie-Angriffe in Bilddateien heuristisch zu erkennen.
            </p>
            <p class="vis-lang-en" style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                In the Platinum version of Sentinel, Airlock additionally leverages the <strong>ORACLE AI</strong> to heuristically detect even the most complex steganography attacks hidden within image files.
            </p>
        </div>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vis-airlock-lang-toggle');
    const wrapper  = document.getElementById('vis-airlock-container');
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
