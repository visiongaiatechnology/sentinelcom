<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
/**
 * SIDEBAR VIEW: COMMUNITY CORE EDITION
 * Status: DIAMANT VGT SUPREME
 * Logic: Dynamische Iteration über Controller-Tabs via State Injection.
 * Fix: DOM Class Namespace Sync (vis- -> vgts-). CSS Redundanz eliminiert.
 */
?>

<aside class="vgts-sidebar">
    <div class="vgts-brand">
        <img src="<?php echo esc_url(defined('VGTS_SENTINEL_ICON') ? VGTS_SENTINEL_ICON : ''); ?>" alt="Sentinel Icon" class="vgts-logo-glitch" style="width: 24px; height: 24px; object-fit: contain; filter: drop-shadow(0 0 8px rgba(212, 175, 55, 0.4));">
        
        <div>
            <h2 style="margin:0; font-size:16px; color:#fff; font-weight:700; letter-spacing:0.5px;">
                VGT<span style="color:var(--vgts-accent);">SENTINEL</span>
            </h2>
            <small style="font-size:10px; color:var(--vgts-text-secondary); text-transform:uppercase; letter-spacing:1px; font-weight:600;">
                COMMUNITY EDITION
            </small>
        </div>
    </div>

    <nav class="vgts-nav">
        <?php 
        // Dependency Injection Check
        if (!isset($tabs) || !is_array($tabs)) {
            $tabs = [];
        }
        
        foreach ($tabs as $slug => $data): 
            $is_active = (isset($active_tab) && $active_tab === $slug);
            
            // Heuristik: Oracle visuell abtrennen
            if ($slug === 'oracle') {
                echo '<div style="height:1px; background:var(--vgts-border); margin:10px 15px; opacity:0.5;"></div>';
            }
        ?>
            <a href="?page=vgts-sentinel&tab=<?php echo esc_attr($slug); ?>" 
               class="vgts-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($data['icon']); ?>"></span>
                <span class="vgts-nav-label"><?php echo esc_html($data['label']); ?></span>
                
                <?php if ($is_active): ?>
                    <span class="vgts-active-indicator"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="vgts-sidebar-footer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;">STATUS</span>
            <span style="color: #10b981; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                <span style="display: block; width: 6px; height: 6px; background: #10b981; border-radius: 50%; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);"></span>
                ONLINE
            </span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; font-family: monospace;">
            <span style="color: #64748b; font-weight: 700; letter-spacing: 0.5px;">CORE</span>
            <span style="color: #94a3b8; font-weight: 700;"><?php echo defined('VGTS_VERSION') ? esc_html(VGTS_VERSION) : '1.5.0'; ?></span>
        </div>
        
        <div style="margin-top: 20px; padding: 10px; background: rgba(6, 182, 212, 0.05); border-radius: 4px; border: 1px solid rgba(6, 182, 212, 0.1);">
            <p style="margin: 0; font-size: 9px; color: #06b6d4; text-align: center; font-weight: 800; letter-spacing: 0.5px;">
                VGT OMEGA PROTOCOL
            </p>
        </div>
    </div>
</aside>