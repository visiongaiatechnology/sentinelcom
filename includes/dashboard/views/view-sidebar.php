<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
/**
 * SIDEBAR VIEW: COMMUNITY CORE EDITION
 * Status: PLATIN STATUS
 * Logic: Dynamische Iteration über Controller-Tabs. Minimale DOM-Last.
 * Fix: Footer-Offset +10px zur Vermeidung von Overflow.
 */
?>
<aside class="vis-sidebar">
    <!-- BRANDING SECTION -->
    <div class="vis-brand">
        <span class="dashicons dashicons-shield-alt vis-logo-glitch"></span>
        <div>
            <h2 style="margin:0; font-size:16px; color:#fff; font-weight:700; letter-spacing:0.5px;">
                VGT<span style="color:var(--vis-accent);">SENTINEL</span>
            </h2>
            <small style="font-size:10px; color:var(--vis-text-secondary); text-transform:uppercase; letter-spacing:1px; font-weight:600;">
                COMMUNITY EDITION
            </small>
        </div>
    </div>

    <!-- DYNAMISCHE NAVIGATION -->
    <nav class="vis-nav">
        <?php 
        // Wir nutzen die im Controller (class-vis-dashboard-view.php) 
        // definierte $tabs Matrix für maximale Konsistenz.
        foreach ($this->tabs as $slug => $data): 
            $is_active = ($active_tab === $slug);
            
            // Heuristik: Oracle & Logs visuell abtrennen (Optional)
            if ($slug === 'oracle') {
                echo '<div style="height:1px; background:var(--vis-border); margin:10px 15px; opacity:0.5;"></div>';
            }
        ?>
            <a href="?page=vis-sentinel&tab=<?php echo esc_attr($slug); ?>" 
               class="vis-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($data['icon']); ?>"></span>
                <span class="vis-nav-label"><?php echo esc_html($data['label']); ?></span>
                
                <?php if ($is_active): ?>
                    <span class="vis-active-indicator"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- SYSTEM STATUS FOOTER (VGT ZERO-DEPENDENCY UI) -->
    <!-- FIX: margin-bottom: 10px sorgt für den nötigen Abstand zum unteren Rand -->
    <div style="margin-top: auto; padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); background: #020617; margin-bottom: 10px; border-radius: 0 0 8px 8px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;">STATUS</span>
            <span style="color: #10b981; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                <span style="display: block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);"></span>
                ONLINE
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;">CORE</span>
            <span style="color: #94a3b8; font-weight: 700;"><?php echo defined('VIS_VERSION') ? esc_html(VIS_VERSION) : '4.7.0'; ?></span>
        </div>
        
        <!-- Subtle Upgrade Hint -->
        <div style="margin-top: 20px; padding: 10px; background: rgba(6, 182, 212, 0.05); border-radius: 4px; border: 1px solid rgba(6, 182, 212, 0.1);">
            <p style="margin: 0; font-size: 9px; color: #06b6d4; text-align: center; font-weight: 800; letter-spacing: 0.5px;">
                VGT OMEGA PROTOCOL
            </p>
        </div>
    </div>
</aside>
