<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

// --- AUTONOMOUS STORAGE LOGIC (O(1) STATE MUTATION) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vis_context']) && $_POST['vis_context'] === 'styx') {
    // Basic Security Check
    if (isset($_POST['vis_styx_nonce']) && wp_verify_nonce($_POST['vis_styx_nonce'], 'vis_save_styx')) {
        $opt = get_option('vis_config', []);
        
        // Explicit Type-Casting to prevent Type-Juggling Vulnerabilities
        $opt['styx_kill_telemetry'] = isset($_POST['styx_kill_telemetry']) ? 1 : 0;
        
        update_option('vis_config', $opt);
        
        // VGT Clinical Feedback
        echo '<div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid var(--vis-success); padding: 15px; margin-bottom: 20px; color: #fff; font-weight: 600; border-radius: 4px;">';
        echo '<span class="dashicons dashicons-yes-alt"></span> STYX Matrix successfully recalibrated.';
        echo '</div>';
    }
}

// Load data (Default = 1 / Active, for maximum zero-trust out of the box)
$opt = get_option('vis_config', []);
$styx_kill = isset($opt['styx_kill_telemetry']) ? $opt['styx_kill_telemetry'] : 1;
?>

<!-- VGT ISOLATED STYLESHEET (ZERO-DEPENDENCY) -->
<style>
    .vgt-styx-toggle { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
    .vgt-styx-toggle input { opacity: 0; width: 0; height: 0; }
    .vgt-styx-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 24px; }
    .vgt-styx-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s cubic-bezier(0.4, 0.0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    .vgt-styx-toggle input:checked + .vgt-styx-slider { background-color: #6366f1; } /* VGT Indigo */
    .vgt-styx-toggle input:checked + .vgt-styx-slider:before { transform: translateX(20px); }
    .vgt-styx-toggle input:focus + .vgt-styx-slider { box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2); }
</style>

<div class="vis-card" style="border-top: 3px solid #6366f1; font-family: 'Inter', sans-serif;">
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--vis-border);">
        <span class="dashicons dashicons-networking" style="font-size: 32px; width: 32px; height: 32px; color: #6366f1;"></span>
        <div>
            <h2 style="margin: 0; color: #fff; font-size: 1.2rem; font-weight: 700;">STYX LITE: OUTBOUND CONTROL</h2>
            <p style="margin: 5px 0 0 0; color: var(--vis-text-secondary); font-size: 12px;">Data Exfiltration & Telemetry Shield</p>
        </div>
    </div>

    <p style="color: #94a3b8; font-size: 13px; line-height: 1.6; margin-bottom: 30px;">
        STYX operates at the network level and monitors outgoing HTTP requests from the WordPress kernel. 
        When activated, the system severs native connections to the wp.org API (Telemetry, Core-Updates, Stats). 
        This blocks supply-chain leaks and prevents compromised plugins from exfiltrating data to external C&C servers.
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('vis_save_styx', 'vis_styx_nonce'); ?>
        <input type="hidden" name="vis_context" value="styx">

        <div style="background: rgba(15, 23, 42, 0.4); border: 1px solid var(--vis-border); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 14px;">WP Telemetry Kill Switch</h4>
                    <p style="margin: 0; color: var(--vis-text-secondary); font-size: 12px;">Blocks <code>api.wordpress.org</code> and associated trackers.</p>
                </div>
                
                <!-- VGT ZERO-DEPENDENCY TOGGLE SWITCH (DYNAMIC STATE) -->
                <label class="vgt-styx-toggle">
                    <input type="checkbox" name="styx_kill_telemetry" value="1" <?php checked($styx_kill, 1); ?>>
                    <span class="vgt-styx-slider"></span>
                </label>
            </div>
        </div>

        <button type="submit" class="vis-btn" style="background: #6366f1; color: #fff; border: none; padding: 10px 24px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span> SAVE PROTOCOL
        </button>
    </form>
</div>
