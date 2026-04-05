<?php 
declare(strict_types=1);

if (!defined('ABSPATH')) exit; 
$guard = new VIS_Filesystem_Guard();
$files = $guard->scan_permissions();
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
    
    /* Block elements restoration */
    .vis-state-en strong.vis-lang-en,
    .vis-state-en h3.vis-lang-en,
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

<div id="vis-fs-container">
    <!-- UI LANGUAGE TOGGLE -->
    <div class="vis-toggle-wrapper">
        <label class="vis-toggle-label">
            <span class="vis-toggle-text vis-text-de">DE</span>
            <div class="vis-switch-track">
                <div class="vis-switch-thumb"></div>
            </div>
            <span class="vis-toggle-text vis-text-en">EN</span>
            <input type="checkbox" id="vis-fs-lang-toggle" style="display: none;">
        </label>
    </div>

    <div class="vis-card">
        <h3>
            <span class="dashicons dashicons-category"></span> 
            <span class="vis-lang-de" style="display:inline;">DATEISYSTEM SICHERHEIT (PERMISSIONS)</span>
            <span class="vis-lang-en" style="display:inline;">FILE SYSTEM SECURITY (PERMISSIONS)</span>
        </h3>
        
        <p class="vis-lang-de" style="color:var(--vis-text-secondary); margin-bottom:20px;">
            Prüft kritische WordPress-Dateien auf korrekte chmod-Rechte (Linux/Unix Standard). <br>
            Empfehlung: Ordner <code>0755</code>, Dateien <code>0644</code>, wp-config.php <code>0600</code>.
        </p>
        <p class="vis-lang-en" style="color:var(--vis-text-secondary); margin-bottom:20px;">
            Verifies critical WordPress files for correct chmod permissions (Linux/Unix Standard). <br>
            Recommendation: Directories <code>0755</code>, Files <code>0644</code>, wp-config.php <code>0600</code>.
        </p>

        <table class="vis-table">
            <thead>
                <tr>
                    <th>
                        <span class="vis-lang-de">DATEI / ORDNER</span>
                        <span class="vis-lang-en">FILE / DIRECTORY</span>
                    </th>
                    <th>
                        <span class="vis-lang-de">PFAD (ABSOLUT)</span>
                        <span class="vis-lang-en">PATH (ABSOLUTE)</span>
                    </th>
                    <th>
                        <span class="vis-lang-de">AKTUELL</span>
                        <span class="vis-lang-en">CURRENT</span>
                    </th>
                    <th>
                        <span class="vis-lang-de">SOLL</span>
                        <span class="vis-lang-en">REQUIRED</span>
                    </th>
                    <th>
                        <span class="vis-lang-de">STATUS</span>
                        <span class="vis-lang-en">STATUS</span>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($files as $f): 
                $color = 'var(--vis-text-secondary)';
                $badge = 'bg-green';
                $icon = 'yes';
                
                if ($f['status'] === 'warning') {
                    $color = 'var(--vis-danger)';
                    $badge = 'bg-red';
                    $icon = 'warning';
                } elseif ($f['status'] === 'missing') {
                    $color = 'var(--vis-warning)';
                    $badge = 'bg-red'; // oder orange
                    $icon = 'hidden';
                }
            ?>
                <tr>
                    <td style="font-weight:600; color:#fff;">
                        <span class="dashicons dashicons-media-default" style="font-size:14px; margin-right:5px;"></span>
                        <?php echo $f['label']; ?>
                    </td>
                    <td class="text-mono" style="font-size:12px; color:var(--vis-text-secondary); word-break:break-all;">
                        <?php echo $f['path']; ?>
                    </td>
                    <td class="text-mono" style="color:<?php echo $color; ?>; font-weight:bold;">
                        <?php echo $f['perms']; ?>
                    </td>
                    <td class="text-mono" style="color:var(--vis-text-secondary);">
                        <?php echo $f['rec']; ?>
                    </td>
                    <td>
                        <?php if($f['status'] === 'secure'): ?>
                            <span class="vis-badge bg-green">SECURE</span>
                        <?php else: ?>
                            <!-- Die PHP-generierte Fehlermeldung bleibt unangetastet, um Backend-Kopplung nicht zu brechen -->
                            <span class="vis-badge bg-red"><?php echo $f['msg']; ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top:20px; padding:15px; border-left:3px solid var(--vis-accent); background:rgba(6,182,212,0.05);">
            <strong class="vis-lang-de" style="color:var(--vis-accent); display:inline;">HINWEIS:</strong>
            <strong class="vis-lang-en" style="color:var(--vis-accent); display:inline;">NOTICE:</strong>
            
            <span class="vis-lang-de">Wenn Rechte als "Warning" angezeigt werden, ändern Sie diese bitte über Ihren FTP-Client (FileZilla) oder das Hosting-Panel (Plesk/cPanel). Das Plugin ändert Dateirechte aus Sicherheitsgründen nicht automatisch.</span>
            <span class="vis-lang-en">If permissions trigger a "Warning", please adjust them manually via your FTP client (FileZilla) or hosting panel (Plesk/cPanel). For security reasons, this plugin does not mutate file permissions automatically.</span>
        </div>
    </div>
</div>

<script>
/**
 * VGT LANGUAGE KERNEL (ZERO-OVERHEAD DOM MANAGER)
 * Synchronisiert sich mit dem globalen LocalStorage State (vis_v7_lang_preference).
 */
document.addEventListener('DOMContentLoaded', () => {
    const toggle   = document.getElementById('vis-fs-lang-toggle');
    const wrapper  = document.getElementById('vis-fs-container');
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
            window.dispatchEvent(new Event('vgt_lang_sync'));
        } else {
            wrapper.classList.remove('vis-state-en');
            localStorage.setItem(langKey, 'de');
            window.dispatchEvent(new Event('vgt_lang_sync'));
        }
    });

    // 3. Listener für Cross-Tab Synchronisation
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