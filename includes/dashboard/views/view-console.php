<?php
declare(strict_types=1);
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VIEW: VGT CONSOLE (Easter Egg / CLI Interface)
 * STATUS: PLATIN STATUS (MODULAR ASSET ARCHITECTURE)
 */
?>

<div class="vgts-card vgts-term-wrapper">
    <div class="vgts-term-header">
        <div style="display: flex; gap: 8px;">
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444;"></div>
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b;"></div>
            <div style="width: 12px; height: 12px; border-radius: 50%; background: #10b981;"></div>
        </div>
        <div style="color: #666; font-size: 12px; letter-spacing: 1px;">vgts-nexus-terminal_v1.5.sh</div>
        <div style="width: 44px;"></div> 
    </div>

    <div class="vgts-term-body">
        <div id="vgts-term-output" class="vgts-term-output">
            <div style="color: #fff; font-weight: bold;">VISIONGAIA TECHNOLOGY KERNEL [Version <?php echo defined('VGTS_VERSION') ? esc_html(VGTS_VERSION) : '1.5.0'; ?>]</div>
            <div style="color: #64748b;">(c) VisionGaia Technology. All rights reserved.</div><br>
            <div style="color: #64748b;">[System: ONLINE] Waiting for input. Type <span style="color:#fff;">'help'</span> for available commands.</div><br>
        </div>

        <div class="vgts-term-input-row">
            <span style="color: #10b981; margin-right: 10px;">root@vgts-nexus:~$</span>
            <input type="text" id="vgts-term-input" class="vgts-term-input" autocomplete="off" spellcheck="false" autofocus>
        </div>

        <div class="vgts-scanline"></div>
    </div>
</div>