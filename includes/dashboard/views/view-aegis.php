<?php if (!defined('ABSPATH')) exit; ?>

<div class="vis-card">
    <h3 style="color:var(--vis-accent);">AEGIS FIREWALL MATRIX</h3>
    
    <!-- TOGGLE ROW -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>ENABLE FIREWALL ENGINE</strong>
            <p>Deep Packet Inspection for SQLi, XSS, RCE, and LFI vectors.</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[aegis_enabled]" <?php checked(!empty($opt['aegis_enabled'])); ?>>
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
            <select name="vis_config[aegis_mode]">
                <option value="strict" <?php selected($opt['aegis_mode'] ?? '', 'strict'); ?>>STRICT (Instant Ban)</option>
                <option value="learning" <?php selected($opt['aegis_mode'] ?? '', 'learning'); ?>>LEARNING (Log & Observe)</option>
            </select>
        </div>
    </div>
</div>

<div class="vis-card" style="border-color:var(--vis-accent); background: linear-gradient(145deg, rgba(6,182,212,0.05), transparent);">
    <h3><span class="dashicons dashicons-info"></span> ACTIVE PATTERNS</h3>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:15px;">
        <div class="vis-badge bg-green" style="text-align:center;">SQL INJECTION</div>
        <div class="vis-badge bg-green" style="text-align:center;">CROSS-SITE SCRIPTING</div>
        <div class="vis-badge bg-green" style="text-align:center;">REMOTE CODE EXECUTION</div>
        <div class="vis-badge bg-green" style="text-align:center;">BAD USER AGENTS</div>
    </div>
</div>