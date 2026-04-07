<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) exit; 

$opt = get_option('vis_config', []);
?>

<div class="vis-card">
    <h3 style="color:var(--vis-accent);">AEGIS FIREWALL MATRIX</h3>
    
    <!-- TOGGLE ROW -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>ENABLE FIREWALL ENGINE</strong>
            <p>Deep Packet Inspection for SQLi, XSS, RCE, and LFI vectors.</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[aegis_enabled]" value="1" <?php checked(!empty($opt['aegis_enabled'])); ?>>
            <span class="slider"></span>
        </label>
    </div>

    <!-- SELECT ROW -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>PROTECTION PROTOCOL</strong>
            <p>Define how Aegis reacts to positive threat signatures.</p>
        </div>
        <div>
            <select name="vis_config[aegis_mode]" style="background: #0f172a; color: #fff; border: 1px solid #334155; padding: 6px 12px; border-radius: 4px; font-size: 13px; outline: none;">
                <option value="strict" <?php selected($opt['aegis_mode'] ?? '', 'strict'); ?>>STRICT (Instant Ban)</option>
                <option value="learning" <?php selected($opt['aegis_mode'] ?? '', 'learning'); ?>>LEARNING (Log & Observe)</option>
            </select>
        </div>
    </div>
</div>

<!-- ZERO-TRUST WHITELIST PANEL -->
<div class="vis-card" style="border-color:#00ffaa; background: linear-gradient(145deg, rgba(0,255,170,0.05), transparent);">
    <h3 style="color:#00ffaa; margin-bottom: 5px; display: flex; align-items: center; gap: 8px;">
        <span class="dashicons dashicons-shield"></span> ZERO-TRUST WHITELIST (Creator's Immunity)
    </h3>
    <p style="color:#94a3b8; font-size:13px; margin-top: 0; margin-bottom:20px;">
        Bypasses are system failures. Configure explicit IPs and User-Agents here to grant immunity from AEGIS and Cerberus. Local server loopbacks (127.0.0.1) and WP Cron are immune by default.
    </p>

    <div style="margin-bottom: 20px;">
        <label style="display:block; font-weight:600; margin-bottom:8px; color:#cbd5e1; font-size: 13px; letter-spacing: 0.5px;">IMMUNE IP ADDRESSES</label>
        <textarea name="vis_config[aegis_whitelist_ips]" rows="4" placeholder="192.168.1.100&#10;203.0.113.5" style="width:100%; background:#020617; border:1px solid #334155; color:#00ffaa; padding:12px; font-family:'JetBrains Mono', monospace; border-radius: 6px; resize: vertical; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); outline: none; transition: border-color 0.3s;"><?php echo esc_textarea($opt['aegis_whitelist_ips'] ?? ''); ?></textarea>
        <span style="font-size:12px; color:#64748b; margin-top: 5px; display: inline-block; font-family: monospace;">One IP per line. Exact match.</span>
    </div>

    <div>
        <label style="display:block; font-weight:600; margin-bottom:8px; color:#cbd5e1; font-size: 13px; letter-spacing: 0.5px;">IMMUNE USER-AGENTS (PARTIAL MATCH)</label>
        <textarea name="vis_config[aegis_whitelist_uas]" rows="4" placeholder="MyCustomAdminApp/1.0&#10;SpecificBot" style="width:100%; background:#020617; border:1px solid #334155; color:#00ffaa; padding:12px; font-family:'JetBrains Mono', monospace; border-radius: 6px; resize: vertical; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5); outline: none; transition: border-color 0.3s;"><?php echo esc_textarea($opt['aegis_whitelist_uas'] ?? ''); ?></textarea>
        <span style="font-size:12px; color:#64748b; margin-top: 5px; display: inline-block; font-family: monospace;">One phrase per line. If the User-Agent contains this phrase, it bypasses the WAF.</span>
    </div>
</div>

<div class="vis-card" style="border-color:var(--vis-accent); background: linear-gradient(145deg, rgba(6,182,212,0.05), transparent);">
    <h3 style="margin-bottom: 15px;"><span class="dashicons dashicons-info"></span> ACTIVE PATTERNS</h3>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-top:15px;">
        <div class="vis-badge" style="text-align:center; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 8px; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; font-size: 12px;">SQL INJECTION</div>
        <div class="vis-badge" style="text-align:center; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 8px; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; font-size: 12px;">CROSS-SITE SCRIPTING</div>
        <div class="vis-badge" style="text-align:center; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 8px; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; font-size: 12px;">REMOTE CODE EXECUTION</div>
        <div class="vis-badge" style="text-align:center; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); padding: 8px; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px; font-size: 12px;">BAD USER AGENTS</div>
    </div>
</div>