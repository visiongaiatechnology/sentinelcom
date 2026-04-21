<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
global $wpdb;

// Verwende das korrekte Prefix (nach Anpassung: VGT_SENTINEL_TABLE_LOGS)
$table = defined('VIS_TABLE_LOGS') ? VIS_TABLE_LOGS : 'vis_omega_logs';
$logs = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . $table . " ORDER BY id DESC LIMIT 50");
?>

<div class="vis-card">
    <h3><span class="dashicons dashicons-list-view"></span> EVENT LOGS</h3>
    
    <?php if(empty($logs)): ?>
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
                    <td class="text-mono" style="color:var(--vis-text-secondary);"><?php echo esc_html($log->timestamp); ?></td>
                    <td><span class="vis-badge" style="background:rgba(255,255,255,0.1);"><?php echo esc_html($log->module); ?></span></td>
                    <td class="text-mono" style="color:var(--vis-accent);"><?php echo esc_html($log->ip); ?></td>
                    <td><?php echo esc_html($log->message); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
