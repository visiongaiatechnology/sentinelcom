<?php if (!defined('ABSPATH')) exit; 
$guard = new VIS_Filesystem_Guard();
$files = $guard->scan_permissions();
?>

<div class="vis-card">
    <h3><span class="dashicons dashicons-category"></span> DATEISYSTEM SICHERHEIT (PERMISSIONS)</h3>
    <p style="color:var(--vis-text-secondary); margin-bottom:20px;">
        Prüft kritische WordPress-Dateien auf korrekte chmod-Rechte (Linux/Unix Standard). 
        <br>Empfehlung: Ordner <code>0755</code>, Dateien <code>0644</code>, wp-config.php <code>0600</code>.
    </p>

    <table class="vis-table">
        <thead>
            <tr>
                <th>DATEI / ORDNER</th>
                <th>PFAD (ABSOLUT)</th>
                <th>AKTUELL</th>
                <th>SOLL</th>
                <th>STATUS</th>
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
                        <span class="vis-badge bg-red"><?php echo $f['msg']; ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top:20px; padding:15px; border-left:3px solid var(--vis-accent); background:rgba(6,182,212,0.05);">
        <strong style="color:var(--vis-accent);">HINWEIS:</strong> Wenn Rechte als "Warning" angezeigt werden, ändern Sie diese bitte über Ihren FTP-Client (FileZilla) oder das Hosting-Panel (Plesk/cPanel). Das Plugin ändert Dateirechte aus Sicherheitsgründen nicht automatisch.
    </div>
</div>