<?php 
if (!defined('ABSPATH')) exit; 
/**
 * SIDEBAR VIEW: COMMUNITY CORE EDITION
 * Status: PLATIN STATUS
 * Logic: Dynamische Iteration über Controller-Tabs. Minimale DOM-Last.
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
    
    <!-- SYSTEM STATUS FOOTER -->
    <div class="vis-sidebar-footer">
        <div class="vis-status-row">
            <span class="vis-status-label">STATUS</span>
            <span class="vis-status-value status-online">ONLINE</span>
        </div>
        <div class="vis-status-row">
            <span class="vis-status-label">CORE</span>
            <span class="vis-status-value"><?php echo esc_html(VIS_VERSION); ?></span>
        </div>
        
        <!-- Subtle Upgrade Hint (Optional for Community) -->
        <div style="margin-top:15px; padding:10px; background:rgba(6, 182, 212, 0.05); border-radius:4px; border:1px solid rgba(6, 182, 212, 0.1);">
            <p style="margin:0; font-size:9px; color:#06b6d4; text-align:center; font-weight:700; letter-spacing:0.5px;">
                VGT OMEGA PROTOCOL ACTIVE
            </p>
        </div>
    </div>
</aside>