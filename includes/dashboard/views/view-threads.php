<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;


global $wpdb;


$table_logs = defined('VIS_TABLE_LOGS') ? $wpdb->prefix . VIS_TABLE_LOGS : $wpdb->prefix . 'vis_omega_logs';
$table_bans = defined('VIS_TABLE_BANS') ? $wpdb->prefix . VIS_TABLE_BANS : $wpdb->prefix . 'vis_apex_bans';


$count_aegis = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_logs} WHERE module LIKE '%AEGIS%'");
$count_bans  = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans}");
$count_trap  = (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_bans} WHERE reason LIKE '%GHOST TRAP%'");


$max_val = max(100, $count_aegis * 1.2, $count_bans * 1.2, $count_trap * 1.5);

$pct_aegis = min(100, round(($count_aegis / $max_val) * 100));
$pct_bans  = min(100, round(($count_bans / $max_val) * 100));
$pct_trap  = min(100, round(($count_trap / $max_val) * 100));


$query_logs = "SELECT timestamp, module as event_type, message, ip FROM {$table_logs} ORDER BY id DESC LIMIT 6";
$query_bans = "SELECT banned_at as timestamp, 'CERBERUS BAN' as event_type, reason as message, ip FROM {$table_bans} ORDER BY id DESC LIMIT 6";

$raw_logs = $wpdb->get_results($query_logs, ARRAY_A);
$raw_bans = $wpdb->get_results($query_bans, ARRAY_A);

$threat_stream = array_merge($raw_logs ?: [], $raw_bans ?: []);
usort($threat_stream, function($a, $b) {
    return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
});
$threat_stream = array_slice($threat_stream, 0, 6);
?>

<style>
    .vgt-radar-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    .vgt-gauge-card {
        background: rgba(13, 17, 30, 0.65);
        border: 1px solid var(--vis-border);
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.02);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s ease, border-color 0.3s ease;
    }
    .vgt-gauge-card:hover {
        transform: translateY(-4px);
    }
    
    .vgt-circular-chart {
        display: block;
        margin: 0 auto;
        max-width: 140px;
        max-height: 140px;
    }
    .vgt-circle-bg {
        fill: none;
        stroke: rgba(255, 255, 255, 0.05);
        stroke-width: 2.5;
    }
    .vgt-circle {
        fill: none;
        stroke-width: 2.5;
        stroke-linecap: round;
        stroke-dasharray: 0, 100;
        transition: stroke-dasharray 1.5s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .vgt-percentage {
        fill: #fff;
        font-family: 'JetBrains Mono', monospace, sans-serif;
        font-size: 8px;
        font-weight: 800;
        text-anchor: middle;
        text-shadow: 0 0 10px rgba(255,255,255,0.2);
    }
    .vgt-gauge-label {
        margin-top: 15px;
        font-size: 13px;
        font-weight: 800;
        color: #fff;
        letter-spacing: 1px;
    }
    .vgt-gauge-desc {
        font-size: 11px;
        color: var(--vis-text-secondary);
        margin-top: 5px;
        line-height: 1.4;
    }

    .vgt-card-aegis { border-top: 3px solid #00e5ff; }
    .vgt-card-aegis:hover { border-color: rgba(0, 229, 255, 0.4); box-shadow: 0 10px 30px rgba(0, 229, 255, 0.1); }
    .vgt-card-aegis .vgt-circle { stroke: #00e5ff; filter: drop-shadow(0 0 4px rgba(0,229,255,0.6)); }

    .vgt-card-cerberus { border-top: 3px solid #ff2a5f; }
    .vgt-card-cerberus:hover { border-color: rgba(255, 42, 95, 0.4); box-shadow: 0 10px 30px rgba(255, 42, 95, 0.1); }
    .vgt-card-cerberus .vgt-circle { stroke: #ff2a5f; filter: drop-shadow(0 0 4px rgba(255,42,95,0.6)); }

    .vgt-card-trap { border-top: 3px solid #ffb703; }
    .vgt-card-trap:hover { border-color: rgba(255, 183, 3, 0.4); box-shadow: 0 10px 30px rgba(255, 183, 3, 0.1); }
    .vgt-card-trap .vgt-circle { stroke: #ffb703; filter: drop-shadow(0 0 4px rgba(255,183,3,0.6)); }

    .vgt-card-upsell {
        background: repeating-linear-gradient(45deg, rgba(15,23,42,0.4), rgba(15,23,42,0.4) 10px, rgba(2,6,23,0.6) 10px, rgba(2,6,23,0.6) 20px);
        border: 1px dashed rgba(176, 38, 255, 0.4);
        opacity: 0.85;
    }
    .vgt-card-upsell:hover { opacity: 1; border-color: #b026ff; box-shadow: 0 0 20px rgba(176, 38, 255, 0.15); }
    .vgt-card-upsell .vgt-circle { stroke: #334155; }
    .vgt-card-upsell .vgt-percentage { fill: #ef4444; font-size: 5px; }
    .vgt-upsell-lock {
        position: absolute;
        top: 15px; right: 15px;
        color: #b026ff;
        font-size: 18px;
    }
    
    .vgt-stream-container {
        margin-top: 10px;
        background: rgba(13, 17, 30, 0.4);
        border: 1px solid var(--vis-border);
        border-radius: 8px;
        overflow: hidden;
    }
    .vgt-stream-row {
        display: grid;
        grid-template-columns: 140px 100px 1fr 130px;
        gap: 15px;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        align-items: center;
        transition: background 0.2s;
    }
    .vgt-stream-row:last-child { border-bottom: none; }
    .vgt-stream-row:hover { background: rgba(255, 255, 255, 0.02); }
    .vgt-stream-time { color: var(--vis-text-muted); font-size: 11px; font-family: 'JetBrains Mono', monospace; }
    .vgt-stream-ip { color: #fff; font-size: 12px; font-family: 'JetBrains Mono', monospace; font-weight: bold; text-align: right; }
    .vgt-stream-msg { color: var(--vis-text-secondary); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vgt-badge-stream { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-align: center; }
    
    .vgt-stream-type-aegis { color: #00e5ff; background: rgba(0, 229, 255, 0.1); border: 1px solid rgba(0, 229, 255, 0.2); }
    .vgt-stream-type-ban { color: #ff2a5f; background: rgba(255, 42, 95, 0.1); border: 1px solid rgba(255, 42, 95, 0.2); }
    .vgt-stream-type-trap { color: #ffb703; background: rgba(255, 183, 3, 0.1); border: 1px solid rgba(255, 183, 3, 0.2); }
</style>

<div class="vis-card" style="background: transparent; border: none; padding: 0; box-shadow: none;">
    <h3 style="color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <span class="dashicons dashicons-performance" style="color: #00e5ff;"></span> THREAT RADAR & PSYCHOMETRICS
    </h3>

    <div class="vgt-radar-container">
        
        <div class="vgt-gauge-card vgt-card-aegis">
            <svg viewBox="0 0 36 36" class="vgt-circular-chart">
                <path class="vgt-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgt-circle" data-pct="<?php echo $pct_aegis; ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgt-percentage" data-count="<?php echo $count_aegis; ?>">0</text>
            </svg>
            <div class="vgt-gauge-label">AEGIS INTERCEPTIONS</div>
            <div class="vgt-gauge-desc">Neutralisierte Payloads (SQLi, XSS, RCE) auf Stream-Ebene.</div>
        </div>

        <div class="vgt-gauge-card vgt-card-cerberus">
            <svg viewBox="0 0 36 36" class="vgt-circular-chart">
                <path class="vgt-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgt-circle" data-pct="<?php echo $pct_bans; ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgt-percentage" data-count="<?php echo $count_bans; ?>">0</text>
            </svg>
            <div class="vgt-gauge-label">CERBERUS BANS</div>
            <div class="vgt-gauge-desc">Permanente Perimeter-Sperren (Brute-Force & Attack Vectors).</div>
        </div>

        <div class="vgt-gauge-card vgt-card-trap">
            <svg viewBox="0 0 36 36" class="vgt-circular-chart">
                <path class="vgt-circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <path class="vgt-circle" data-pct="<?php echo $pct_trap; ?>" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                <text x="18" y="21.5" class="vgt-percentage" data-count="<?php echo $count_trap; ?>">0</text>
            </svg>
            <div class="vgt-gauge-label">GHOST TRAP TRIGGERS</div>
            <div class="vgt-gauge-desc">Erfolgreiche Tarpit-Fallen gegen automatisierte Botnetze.</div>
        </div>

        <a href="https://visiongaiatechnology.de/visiongaiadefensehub/" target="_blank" style="text-decoration: none;">
            <div class="vgt-gauge-card vgt-card-upsell">
                <span class="dashicons dashicons-lock vgt-upsell-lock"></span>
                <svg viewBox="0 0 36 36" class="vgt-circular-chart">
                    <path class="vgt-circle-bg" stroke-dasharray="2,2" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path class="vgt-circle" stroke-dasharray="0, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <text x="18" y="20.5" class="vgt-percentage" style="animation: glitch 2s infinite;">BLIND</text>
                    <text x="18" y="25" class="vgt-percentage" style="font-size:3px; fill:var(--vis-text-secondary);">SPOT</text>
                </svg>
                <div class="vgt-gauge-label" style="color: #b026ff;">POLYMORPHIC INFERENCE</div>
                <div class="vgt-gauge-desc" style="color: #ef4444;">Zero-Day und L7-DDoS Analyse deaktiviert. Erfordert VGT ORACLE KI.</div>
            </div>
        </a>

    </div>

    <h4 style="color: #fff; margin: 30px 0 15px 0; font-size: 14px; display: flex; align-items: center; gap: 8px;">
        <span class="dashicons dashicons-rss" style="color: var(--vis-text-muted);"></span> LIVE THREAT STREAM
    </h4>
    
    <div class="vgt-stream-container">
        <?php if (empty($threat_stream)): ?>
            <div style="padding: 30px; text-align: center; color: var(--vis-text-muted); font-size: 12px;">
                <span class="dashicons dashicons-shield" style="font-size: 24px; width: 24px; height: 24px; margin-bottom: 10px; opacity: 0.5;"></span><br>
                System-Perimeter ist absolut sauber. Keine Vorfälle im Speicher.
            </div>
        <?php else: ?>
            <?php foreach ($threat_stream as $event): 
                $type_class = 'vgt-stream-type-aegis';
                $type_label = 'AEGIS BLOCK';
                
                if (strpos($event['message'], 'GHOST TRAP') !== false || strpos($event['event_type'], 'TRAP') !== false) {
                    $type_class = 'vgt-stream-type-trap';
                    $type_label = 'GHOST TRAP';
                } elseif (strpos($event['event_type'], 'BAN') !== false) {
                    $type_class = 'vgt-stream-type-ban';
                    $type_label = 'HARD BAN';
                }
            ?>
            <div class="vgt-stream-row">
                <div class="vgt-stream-time"><?php echo esc_html($event['timestamp']); ?></div>
                <div class="vgt-badge-stream <?php echo $type_class; ?>"><?php echo $type_label; ?></div>
                <div class="vgt-stream-msg" title="<?php echo esc_attr($event['message']); ?>">
                    <?php echo esc_html($event['message']); ?>
                </div>
                <div class="vgt-stream-ip"><?php echo esc_html($event['ip']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', () => {
    
    const circles = document.querySelectorAll('.vgt-circle');
    circles.forEach(circle => {
        const pct = circle.getAttribute('data-pct');
        if (pct) {
            setTimeout(() => {
                circle.style.strokeDasharray = `${pct}, 100`;
            }, 300 + Math.random() * 200);
        }
    });

    const texts = document.querySelectorAll('.vgt-percentage[data-count]');
    texts.forEach(text => {
        const target = parseInt(text.getAttribute('data-count'), 10);
        if (target === 0) return;
        
        const duration = 1500; 
        const frameRate = 30;
        const totalFrames = Math.round(duration / frameRate);
        let frame = 0;

        const counter = setInterval(() => {
            frame++;
            const progress = 1 - Math.pow(1 - frame / totalFrames, 3);
            const currentCount = Math.round(target * progress);
            
            text.textContent = currentCount;

            if (frame === totalFrames) {
                clearInterval(counter);
                text.textContent = target; 
            }
        }, frameRate);
    });
});
</script>
<?php

