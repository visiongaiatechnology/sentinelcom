<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// Redundante Daten-Extraktion (O(1) Fallback)
if (!isset($opt)) {
    $opt = get_option('vis_config', []);
}
$airlock_active = !empty($opt['airlock_enabled']) ? 1 : 0;
?>

<!-- VGT ISOLATED STYLESHEET (ZERO-DEPENDENCY) -->
<style>
    .vgt-airlock-toggle { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .vgt-airlock-toggle input { opacity: 0; width: 0; height: 0; }
    .vgt-airlock-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 24px; }
    .vgt-airlock-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .vgt-airlock-toggle input:checked + .vgt-airlock-slider { background-color: #f59e0b; }
    .vgt-airlock-toggle input:checked + .vgt-airlock-slider:before { transform: translateX(20px); }
    .vgt-airlock-toggle input:focus + .vgt-airlock-slider { box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2); }
</style>

<div class="vis-card" style="border-top: 3px solid #f59e0b; font-family: 'Inter', sans-serif;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
        <span class="dashicons dashicons-upload" style="font-size: 32px; width: 32px; height: 32px; color: #f59e0b;"></span>
        <div>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">AIRLOCK GUARD</h2>
            <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Real-Time Upload Sanitization & Binary Analysis</p>
        </div>
    </div>

    <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
        Airlock überwacht den gesamten <code>multipart/form-data</code> Stream während Datei-Uploads. 
        Bevor WordPress eine Datei in die Mediathek aufnimmt, führt Airlock eine binäre Tiefenanalyse durch, um PHP-Wrapper, 
        obfuskierte Skripte oder "Polyglot"-Files (Bilder mit verstecktem Code) zu identifizieren und zu neutralisieren.
    </p>

    <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">Enable Airlock Protection</h4>
                <p style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Aktiviert die Echtzeit-Sanitierung für alle Uploads (Strict Allowlisting).</p>
            </div>
            
            <!-- VGT ZERO-DEPENDENCY TOGGLE SWITCH (DYNAMIC STATE) -->
            <label class="vgt-airlock-toggle">
                <input type="checkbox" name="vis_config[airlock_enabled]" value="1" <?php checked($airlock_active, 1); ?>>
                <span class="vgt-airlock-slider"></span>
            </label>
        </div>
    </div>

    <div style="padding: 15px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.1); border-radius: 6px;">
        <h5 style="margin: 0 0 5px 0; color: #f59e0b; font-size: 12px; font-weight: 700; letter-spacing: 0.5px;">PRO-TIP:</h5>
        <p style="margin: 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
            In der Platin-Version von Sentinel nutzt Airlock zusätzlich die <strong>ORACLE AI</strong>, um auch komplexeste Steganographie-Angriffe in Bilddateien heuristisch zu erkennen.
        </p>
    </div>
</div>