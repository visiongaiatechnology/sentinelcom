<?php if (!defined('ABSPATH')) exit; 
global $wpdb;
$logs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . VIS_TABLE_LOGS . " ORDER BY id DESC LIMIT 50");
?>

<div class="vis-card">
    <h3><span class="dashicons dashicons-list-view"></span> EVENT LOGS</h3>
    
    <?php if(empty($logs)): ?>
        <div style="padding:40px; text-align:center; color:var(--vis-text-secondary);">
            <span class="dashicons dashicons-yes" style="font-size:40px; margin-bottom:10px; display:block; width:auto; height:auto;"></span>
            <p>No security incidents recorded. System is clean.</p>
        </div>
    <?php else: ?>
        <table class="vis-table">
            <thead>
                <tr>
                    <th width="180">TIMESTAMP</th>
                    <th width="100">MODULE</th>
                    <th width="150">IP ADDRESS</th>
                    <th>EVENT DETAILS</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($logs as $log): ?>
                <tr>
                    <td class="text-mono" style="color:var(--vis-text-secondary);"><?php echo $log->timestamp; ?></td>
                    <td><span class="vis-badge" style="background:rgba(255,255,255,0.1);"><?php echo $log->module; ?></span></td>
                    <td class="text-mono" style="color:var(--vis-accent);"><?php echo $log->ip; ?></td>
                    <td><?php echo esc_html($log->message); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>