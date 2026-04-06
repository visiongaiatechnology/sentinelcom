<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_bans = defined('VIS_TABLE_BANS') ? $wpdb->prefix . VIS_TABLE_BANS : $wpdb->prefix . 'vis_bans';

// --- PAGINATION LOGIK ---
$bans_per_page = 20; // Anzahl der Bans pro Seite
$current_page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
$offset = ($current_page - 1) * $bans_per_page;

// Gesamtzahl ermitteln (für Seiten-Kalkulation)
$total_bans = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans}");
$total_pages = ceil($total_bans / $bans_per_page);

// Bans für die aktuelle Seite laden
$bans = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_bans} ORDER BY banned_at DESC LIMIT %d OFFSET %d",
    $bans_per_page, $offset
));
?>

<style>
/* VGT Cerberus UI Styles */

h2, h3 {
    color: #ffffff !important;
    margin: 1em 0;
}

.vgt-cerberus-header {
    color: #ffffff !important;
    margin-bottom: 30px;
}
.vgt-cerberus-table-wrap {
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.5);
}
.vgt-cerberus-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}
.vgt-cerberus-table th {
    background: #0f172a;
    padding: 16px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.05em;
    border-bottom: 2px solid rgba(212, 175, 55, 0.3); /* VGT Gold Accent */
}
.vgt-cerberus-table td {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    vertical-align: top;
}
.vgt-cerberus-table tr:last-child td {
    border-bottom: none;
}
.vgt-cerberus-table tr:hover td {
    background: rgba(255, 255, 255, 0.02);
}
.vgt-cerberus-ip {
    color: #ef4444; /* VGT Red */
    font-family: 'JetBrains Mono', monospace, sans-serif;
    font-size: 15px;
    font-weight: 700;
    display: inline-block;
    padding: 4px 8px;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 4px;
    border: 1px solid rgba(239, 68, 68, 0.2);
}
.vgt-cerberus-time {
    font-size: 13px;
    color: #cbd5e1;
}
.vgt-cerberus-payload {
    white-space: pre-wrap;
    word-wrap: break-word;
    background: #020617; /* Deep terminal black */
    color: #fbbf24; /* Alert yellow */
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #334155;
    font-family: 'JetBrains Mono', monospace, sans-serif;
    font-size: 12px;
    margin: 0;
    max-width: 500px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
}
.vgt-cerberus-btn-unban {
    background: transparent;
    color: #ef4444;
    border: 1px solid #ef4444;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.vgt-cerberus-btn-unban:hover {
    background: #ef4444;
    color: #fff;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);
}
/* Pagination Styles */
.vgt-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 30px;
}
.vgt-pagination .page-numbers {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    background: #1e293b;
    color: #94a3b8;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
}
.vgt-pagination .page-numbers:hover {
    background: #334155;
    color: #fff;
    border-color: rgba(255, 255, 255, 0.3);
}
.vgt-pagination .page-numbers.current {
    background: rgba(212, 175, 55, 0.15);
    color: #D4AF37;
    border-color: rgba(212, 175, 55, 0.4);
    pointer-events: none;
}
.vgt-pagination .dots {
    background: transparent;
    border: none;
    color: #64748b;
}
.vgt-cerberus-stats {
    font-size: 14px;
    color: #94a3b8;
    margin-bottom: 15px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.vgt-cerberus-stats-highlight {
    color: #fff;
    font-weight: 700;
}
</style>

<div class="vis-dashboard-wrap">
    <header class="vis-module-header vgt-cerberus-header">
        <h2>Cerberus <span>Perimeter Guard</span></h2>
        <p>Verwaltung der globalen IP-Sperren auf Opcache-Niveau.</p>
    </header>

    <div class="vis-card">
        <div class="vgt-cerberus-stats">
            <div>
                <h3>Aktive System-Bans (Layer 1)</h3>
                <p style="margin-top: 5px; color: #94a3b8;">Diese IPs wurden von Cerberus oder AEGIS permanent blockiert und werden vor dem WordPress-Bootvorgang abgewiesen.</p>
            </div>
            <div>
                Gesamtzahl geblockter IPs: <span class="vgt-cerberus-stats-highlight"><?php echo number_format_i18n($total_bans); ?></span>
            </div>
        </div>
        
        <div class="vgt-cerberus-table-wrap">
            <table class="vgt-cerberus-table">
                <thead>
                    <tr>
                        <th>IP-Adresse</th>
                        <th>Zeitpunkt</th>
                        <th>Blockierungs-Grund (Isoliert)</th>
                        <th style="text-align: right;">Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bans)): ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #64748b;">
                                <span class="dashicons dashicons-shield" style="font-size: 40px; width: 40px; height: 40px; opacity: 0.5; margin-bottom: 15px;"></span><br>
                                Perimeter sauber. Keine aktiven Sperren in der Datenbank.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bans as $ban): ?>
                            <tr>
                                <td>
                                    <span class="vgt-cerberus-ip"><?php echo esc_html($ban->ip); ?></span>
                                </td>
                                <td>
                                    <span class="vgt-cerberus-time">
                                        <?php echo esc_html(wp_date(get_option('date_format') . ' H:i:s', strtotime($ban->banned_at))); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- VGT KERNEL FIX: Strikte XSS-Isolation für Payload-Logs -->
                                    <pre class="vgt-cerberus-payload"><?php 
                                        echo esc_html($ban->reason); 
                                        if (!empty($ban->request_uri)) {
                                            echo "\n\nTarget: " . esc_html($ban->request_uri);
                                        }
                                    ?></pre>
                                </td>
                                <td style="text-align: right;">
                                    <button class="vgt-cerberus-btn-unban" onclick="vis_unban_ip('<?php echo esc_js($ban->ip); ?>')">
                                        <span class="dashicons dashicons-unlock"></span> Freischalten
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
        // --- PAGINATION RENDERER ---
        if ($total_pages > 1): 
            $pagination_links = paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'prev_text' => '&laquo; Zurück',
                'next_text' => 'Weiter &raquo;',
                'total'     => $total_pages,
                'current'   => $current_page,
                'type'      => 'plain'
            ]);
            
            if ($pagination_links) {
                echo '<div class="vgt-pagination">' . $pagination_links . '</div>';
            }
        endif; 
        ?>

    </div>
</div>

<script>
function vis_unban_ip(ip) {
    if (!confirm('VGT SECURITY ALERT:\nMöchten Sie die IP ' + ip + ' wirklich wieder für das System freischalten?')) {
        return;
    }
    
    // AJAX Request an das Dashboard
    jQuery.post(ajaxurl, {
        action: 'vis_dashboard_unban_ip',
        ip: ip,
        nonce: '<?php echo wp_create_nonce("vis_dashboard_nonce"); ?>' 
    }, function(response) {
        if (response.success) {
            // Seite neu laden um die Liste zu aktualisieren
            location.reload();
        } else {
            alert('FEHLER: ' + (response.data || 'Datenbank-Operation fehlgeschlagen.'));
        }
    });
}
</script>