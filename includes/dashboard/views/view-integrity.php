<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 

$report = get_option('vis_scan_report', false);
$has_report = !empty($report) && is_array($report);
$status = $has_report ? $report['status'] : 'unknown';
$changes = $has_report ? $report['changes'] : [];
$last_scan = $has_report ? $report['timestamp'] : 'Nie';

$status_color = 'var(--vis-text-secondary)';
$status_icon = 'dashicons-minus';

if ($status === 'clean') {
    $status_color = 'var(--vis-success)';
    $status_icon = 'dashicons-yes-alt';
} elseif ($status === 'warning') {
    $status_color = 'var(--vis-danger)';
    $status_icon = 'dashicons-warning';
}
?>

<div class="vis-card">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--vis-border); padding-bottom:20px; margin-bottom:20px;">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="
                width:50px; height:50px; 
                background:rgba(255,255,255,0.05); 
                border-radius:50%; 
                display:flex; align-items:center; justify-content:center;
                color:<?php echo $status_color; ?>;
            ">
                <span class="dashicons <?php echo $status_icon; ?>" style="font-size:30px; width:30px; height:30px;"></span>
            </div>
            <div>
                <h2 style="margin:0; font-size:18px; color:#fff;">SYSTEM INTEGRITY REPORT</h2>
                <div style="font-size:12px; color:var(--vis-text-secondary); margin-top:4px;">
                    Last Scan: <span class="text-mono" style="color:#fff;"><?php echo $last_scan; ?></span>
                </div>
            </div>
        </div>
        
        <div>
            <button id="vis-btn-scan" class="vis-btn vis-btn-neon">
                <span class="dashicons dashicons-search"></span> RUN DEEP SCAN
            </button>
        </div>
    </div>

    <?php if(!$has_report): ?>
        <div style="text-align:center; padding:40px; color:var(--vis-text-secondary);">
            <p>Kein Bericht verfügbar. Bitte starten Sie einen manuellen Scan.</p>
        </div>
    <?php elseif($status === 'clean' || $status === 'init'): ?>
        <div style="text-align:center; padding:40px;">
            <span class="dashicons dashicons-shield-alt" style="font-size:64px; color:var(--vis-success); width:auto; height:auto; display:block; margin-bottom:20px;"></span>
            <h3 style="color:#fff; margin-bottom:10px;">SYSTEM SECURE</h3>
            <p style="color:var(--vis-text-secondary); max-width:500px; margin:0 auto;">
                Alle überwachten Dateien stimmen mit der Baseline (Manifest) überein. Es wurden keine nicht-autorisierten Änderungen festgestellt.
            </p>
        </div>
    <?php else: ?>
        <div style="background:rgba(239, 68, 68, 0.1); border:1px solid rgba(239, 68, 68, 0.3); padding:15px; border-radius:6px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:10px; color:var(--vis-danger);">
                <span class="dashicons dashicons-warning" style="font-size:20px;"></span>
                <strong style="font-size:14px;">ANOMALIEN ERKANNT: <?php echo count($changes); ?> DATEIEN VERÄNDERT</strong>
            </div>
            <button id="vis-btn-approve" class="vis-btn vis-btn-ghost" style="border-color:var(--vis-danger); color:var(--vis-danger);">
                <span class="dashicons dashicons-yes"></span> BASELINE UPDATEN (APPROVE)
            </button>
        </div>

        <table class="vis-table">
            <thead>
                <tr>
                    <th width="80">TYPE</th>
                    <th>DATEIPFAD</th>
                    <th>DETAILS</th>
                    <th width="100">ACTION</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($changes as $change): 
                $type = $change['type'];
                $badge_class = 'bg-red';
                if ($type === 'NEW') $badge_class = 'bg-green';
                if ($type === 'MODIFIED') $badge_class = 'bg-red';
                if ($type === 'DELETED') $badge_class = 'bg-red'; 
                ?>
                <tr>
                    <td><span class="vis-badge <?php echo $badge_class; ?>"><?php echo $type; ?></span></td>
                    <td class="text-mono" style="color:#fff; font-size:13px; word-break:break-all;">
                        <?php echo esc_html($change['file']); ?>
                    </td>
                    <td style="color:var(--vis-text-secondary); font-size:12px;">
                        <?php echo esc_html($change['desc']); ?>
                    </td>
                    <td>
                        <a href="#" style="color:var(--vis-accent); font-size:11px; text-decoration:none;">VIEW SOURCE</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="vis-scan-progress" style="display:none; margin-top:20px; background:var(--vis-bg-sidebar); padding:15px; border-radius:6px; border:1px solid var(--vis-border); text-align:center; color:var(--vis-accent);">
    <span class="dashicons dashicons-update spin"></span> <span id="vis-scan-status-text">INITIALIZING SCAN...</span>
</div>
