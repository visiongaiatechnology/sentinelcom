<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
global $wpdb;
$opt = get_option('vis_config', []);
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
    
    /* Fix: strong muss block sein für das vis-label-group Layout */
    .vis-state-en strong.vis-lang-en,
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

<div id="vis-titan-container">
    <!-- UI LANGUAGE TOGGLE -->
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-titan-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card">
        <h3>
            <span class="dashicons dashicons-lock"></span> 
            <span class="vis-lang-de">KERNEL HÄRTUNG & .HTACCESS</span>
            <span class="vis-lang-en">KERNEL HARDENING & .HTACCESS</span>
        </h3>
        <p class="vis-lang-de" style="color:var(--vis-text-secondary); margin-bottom:20px; font-size:13px;">
            Alle Aktivierungen in diesem Modul werden automatisch in die <code>.htaccess</code> geschrieben, um maximalen Schutz auf Server-Ebene zu gewährleisten.
        </p>
        <p class="vis-lang-en" style="color:var(--vis-text-secondary); margin-bottom:20px; font-size:13px;">
            All activations within this module are dynamically injected into the <code>.htaccess</code> file to establish maximum server-level protection.
        </p>

        <!-- SECTION 1: CAMOUFLAGE -->
        <div class="vis-switch-row">
            <div class="vis-label-group">
                <strong class="vis-lang-de">HEADER CAMOUFLAGE (TÄUSCHUNG)</strong>
                <strong class="vis-lang-en">HEADER CAMOUFLAGE (DECEPTION)</strong>
                <p class="vis-lang-de">Verschleiert WordPress und injiziert Fake-Header (z.B. Laravel), um Angreifer zu verwirren.</p>
                <p class="vis-lang-en">Obfuscates WordPress footprints and injects forged headers (e.g., Laravel) to disorient attackers.</p>
            </div>
            <div>
                <!-- O(1) Form Integrity: Options are bilingual to prevent duplicate name attributes in DOM -->
                <select name="vis_config[titan_camouflage_mode]">
                    <option value="none" <?php selected($opt['titan_camouflage_mode'] ?? '', 'none'); ?>>Deaktiviert | Disabled (Default)</option>
                    <option value="laravel" <?php selected($opt['titan_camouflage_mode'] ?? '', 'laravel'); ?>>Laravel Framework (Recommended)</option>
                    <option value="drupal" <?php selected($opt['titan_camouflage_mode'] ?? '', 'drupal'); ?>>Drupal CMS</option>
                    <option value="django" <?php selected($opt['titan_camouflage_mode'] ?? '', 'django'); ?>>Django (Python)</option>
                </select>
            </div>
        </div>

        <!-- SECTION 2: API & PROTOCOLS -->
        <div class="vis-switch-row">
            <div class="vis-label-group">
                <strong class="vis-lang-de">XML-RPC BLOCKIEREN</strong>
                <strong class="vis-lang-en">BLOCK XML-RPC</strong>
                <p class="vis-lang-de">Schließt die <code>xmlrpc.php</code> Schnittstelle komplett (Stoppt DDoS & Brute Force).</p>
                <p class="vis-lang-en">Completely seals the <code>xmlrpc.php</code> interface (Neutralizes DDoS & Brute Force vectors).</p>
            </div>
            <label class="vis-switch">
                <input type="checkbox" name="vis_config[titan_block_xmlrpc]" <?php checked(!empty($opt['titan_block_xmlrpc'])); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="vis-switch-row">
            <div class="vis-label-group">
                <strong class="vis-lang-de">REST API EINSCHRÄNKEN</strong>
                <strong class="vis-lang-en">RESTRICT REST API</strong>
                <p class="vis-lang-de">Erlaubt Zugriff auf die REST API nur für eingeloggte Benutzer.</p>
                <p class="vis-lang-en">Restricts REST API access exclusively to authenticated users.</p>
            </div>
            <label class="vis-switch">
                <input type="checkbox" name="vis_config[titan_block_rest]" <?php checked(!empty($opt['titan_block_rest'])); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="vis-switch-row">
            <div class="vis-label-group">
                <strong class="vis-lang-de">RSS & ATOM FEEDS DEAKTIVIEREN</strong>
                <strong class="vis-lang-en">DISABLE RSS & ATOM FEEDS</strong>
                <p class="vis-lang-de">Verhindert Content-Scraping durch Bots. Gibt 403 Forbidden bei Feed-Zugriff.</p>
                <p class="vis-lang-en">Prevents automated content scraping. Returns a 403 Forbidden status on feed access.</p>
            </div>
            <label class="vis-switch">
                <input type="checkbox" name="vis_config[titan_disable_feeds]" <?php checked(!empty($opt['titan_disable_feeds'])); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <!-- SECTION 3: BASE PROTECTION -->
        <div class="vis-switch-row">
            <div class="vis-label-group">
                <strong class="vis-lang-de">SECURITY HEADERS INJECTION</strong>
                <strong class="vis-lang-en">SECURITY HEADERS INJECTION</strong>
                <p class="vis-lang-de">Erzwingt HSTS, X-Frame-Options und XSS-Protection.</p>
                <p class="vis-lang-en">Enforces strict HSTS, X-Frame-Options, and XSS-Protection.</p>
            </div>
            <label class="vis-switch">
                <input type="checkbox" name="vis_config[titan_enabled]" <?php checked(!empty($opt['titan_enabled'])); ?>>
                <span class="slider"></span>
            </label>
        </div>
    </div>
</div>

<script>
/**
 * VGT LANGUAGE KERNEL (ZERO-OVERHEAD DOM MANAGER)
 * Synchronisiert sich mit dem globalen LocalStorage State (vis_v7_lang_preference).
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vis-titan-lang-toggle');
    const wrapper  = document.getElementById('vis-titan-container');
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
            
            // Optional: Trigger custom event to sync other tabs simultaneously if loaded in DOM
            window.dispatchEvent(new Event('vgt_lang_sync'));
        } else {
            wrapper.classList.remove('vis-state-en');
            localStorage.setItem(langKey, 'de');
            window.dispatchEvent(new Event('vgt_lang_sync'));
        }
    });

    // 3. Listener für Cross-Tab Synchronisation (falls andere Toggles existieren)
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
