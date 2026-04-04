<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- AUTONOME SPEICHER-LOGIK (O(1) STATE MUTATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vis_context']) && $_POST['vis_context'] === 'styx') {
    // Basic Security Check
    if (isset($_POST['vis_styx_nonce']) && wp_verify_nonce($_POST['vis_styx_nonce'], 'vis_save_styx')) {
        $opt = get_option('vis_config', []);
        
        // Explizites Type-Casting zur Vermeidung von Type-Juggling Vulnerabilities
        $opt['styx_kill_telemetry'] = isset($_POST['styx_kill_telemetry']) ? 1 : 0;
        
        update_option('vis_config', $opt);
        
        // VGT Clinical Feedback
        echo '<div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--vis-success); padding: 15px; margin-bottom: 20px; color: #fff; font-weight: 600; border-radius: 4px;">';
        echo '<span class="dashicons dashicons-yes-alt"></span> STYX-Matrix erfolgreich rekalibriert.';
        echo '</div>';
    }
}

// Daten laden (Default = 1 / Active, für maximalen Zero-Trust out of the box)
$opt = get_option('vis_config', []);
$styx_kill = isset($opt['styx_kill_telemetry']) ? $opt['styx_kill_telemetry'] : 1;
?>

<div class="vis-card" style="border-top: 3px solid #6366f1;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
        <span class="dashicons dashicons-networking" style="font-size: 32px; width: 32px; height: 32px; color: #6366f1;"></span>
        <div>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">STYX LITE: OUTBOUND CONTROL</h2>
            <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Data Exfiltration & Telemetry Shield</p>
        </div>
    </div>

    <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
        STYX operiert auf Netzwerkebene und überwacht ausgehende HTTP-Anfragen des WordPress-Kernels. 
        Im aktivierten Zustand kappt das System native Verbindungen zur wp.org API (Telemetry, Core-Updates, Stats). 
        Dies blockiert Supply-Chain Leaks und verhindert, dass kompromittierte Plugins Daten an externe C&C-Server exfiltrieren.
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('vis_save_styx', 'vis_styx_nonce'); ?>
        <input type="hidden" name="vis_context" value="styx">

        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">WP Telemetry Kill Switch</h4>
                    <p style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Blockiert <code>api.wordpress.org</code> und verknüpfte Tracker.</p>
                </div>
                
                <!-- VGT UI Toggle Switch -->
                <label style="position: relative; display: inline-block; width: 44px; height: 24px;">
                    <input type="checkbox" name="styx_kill_telemetry" value="1" <?php checked($styx_kill, 1); ?> style="opacity: 0; width: 0; height: 0;">
                    <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $styx_kill ? '#6366f1' : '#334155'; ?>; transition: .4s; border-radius: 24px;">
                        <span style="position: absolute; content: ''; height: 18px; width: 18px; left: <?php echo $styx_kill ? '22px' : '3px'; ?>; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%;"></span>
                    </span>
                </label>
            </div>
        </div>

        <button type="submit" class="vis-btn" style="background: #6366f1; color: #fff; border: none; padding: 10px 24px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: all 0.2s;">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span> PROTOKOLL SPEICHERN
        </button>
    </form>
</div>