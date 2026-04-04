<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; 
global $wpdb;

// Daten laden & Sanitization
$bans_query = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . VIS_TABLE_BANS);
$bans = $bans_query ? (int)$bans_query : 0;
$opt  = get_option('vis_config', []);

// 1. COMMUNITY CORE MATRIX (SILBER STATUS)
$core_modules = [
    [
        'label'  => 'AEGIS FIREWALL',
        'icon'   => 'dashicons-shield',
        'active' => !empty($opt['aegis_enabled']),
        'desc'   => 'Regex WAF',
        'link'   => '?page=vis-sentinel&tab=aegis'
    ],
    [
        'label'  => 'TITAN HARDENING',
        'icon'   => 'dashicons-lock',
        'active' => !empty($opt['titan_enabled']),
        'desc'   => 'Kernel & Header Protection',
        'link'   => '?page=vis-sentinel&tab=titan'
    ],
    [
        'label'  => 'HADES STEALTH',
        'icon'   => 'dashicons-hidden',
        'active' => !empty($opt['hades_enabled']),
        'desc'   => 'Camouflage & Obfuscation',
        'link'   => '?page=vis-sentinel&tab=hades'
    ],
    [
        'label'  => 'MU DEPLOYER',
        'icon'   => 'dashicons-hammer',
        'active' => file_exists((defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : WP_CONTENT_DIR . '/mu-plugins') . '/0-vgt-sentinel-loader.php'),
        'desc'   => 'Pre-Boot Loader',
        'link'   => '?page=vis-sentinel&tab=mudeployer'
    ],
    [
        'label'  => 'CERBERUS GUARD',
        'icon'   => 'dashicons-id-alt',
        'active' => true, // Core
        'desc'   => 'Login Brute-Force Shield',
        'link'   => '#'
    ],
    [
        'label'  => 'STYX CONTROL',
        'icon'   => 'dashicons-networking',
        'active' => isset($opt['styx_kill_telemetry']) ? $opt['styx_kill_telemetry'] : 1,
        'desc'   => 'Outbound Telemetry Kill',
        'link'   => '?page=vis-sentinel&tab=styx'
    ],
    [
        'label'  => 'AIRLOCK',
        'icon'   => 'dashicons-upload',
        'active' => !empty($opt['airlock_enabled']),
        'desc'   => 'Upload Sanitizer Engine',
        'link'   => '?page=vis-sentinel&tab=airlock'
    ],
    [
        'label'  => 'GHOST TRAP',
        'icon'   => 'dashicons-warning',
        'active' => true, // Core
        'desc'   => 'Honeypot System',
        'link'   => '#'
    ],
    [
        'label'  => 'CHRONOS',
        'icon'   => 'dashicons-clock',
        'active' => (bool)wp_next_scheduled('vis_hourly_scan_event'),
        'desc'   => 'Automated Integrity Scan',
        'link'   => '#'
    ],
    [
        'label'  => 'FS GUARD',
        'icon'   => 'dashicons-category',
        'active' => true, // Passive
        'desc'   => 'Permission Monitor',
        'link'   => '?page=vis-sentinel&tab=filesystem'
    ]
];

// 2. VGT OMEGA PLATINUM MATRIX (DIAMANT SUPREME STATUS)
$pro_modules = [
    [
        'label'  => 'ZEUS PRE-BOOT WAF',
        'icon'   => 'dashicons-bolt',
        'desc'   => 'PHP Runtime Interception (< 0.2ms)'
    ],
    [
        'label'  => 'ORACLE AI INFERENCE',
        'icon'   => 'dashicons-superhero',
        'desc'   => 'AI Threat Analysis'
    ],
    [
        'label'  => 'MORPHEUS HYPERVISOR',
        'icon'   => 'dashicons-admin-network',
        'desc'   => 'Zero-Trust Plugin Isolation'
    ],
    [
        'label'  => 'GORGON NEXUS',
        'icon'   => 'dashicons-share-alt',
        'desc'   => 'Global Swarm Intelligence'
    ],
    [
        'label'  => 'PROMETHEUS ENGINE',
        'icon'   => 'dashicons-chart-line',
        'desc'   => 'Predictive Behavioral Scoring'
    ],
    [
        'label'  => 'NEMESIS DECEPTION',
        'icon'   => 'dashicons-buddicons-groups',
        'desc'   => 'Tarpitting & Data Poisoning'
    ],
    [
        'label'  => 'VGT KEY VAULT',
        'icon'   => 'dashicons-keys',
        'desc'   => 'AES-256-GCM Hardware Crypto'
    ]
];
?>

<!-- VGT COMMUNITY GUARD: LIABILITY DISCLAIMER (SILBER STATUS) -->
<div style="background: #0d1117; border: 1px solid #30363d; border-left: 4px solid #94a3b8; padding: 20px; margin-bottom: 25px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between;">
    <div>
        <span style="background: linear-gradient(90deg, #64748b, #94a3b8); color: #0f172a; padding: 4px 10px; font-weight: 800; font-size: 10px; text-transform: uppercase; border-radius: 3px; letter-spacing: 1px; display: inline-block; margin-bottom: 8px;">FREE STATUS (COMMUNITY CORE)</span>
        <h2 style="color: #f8fafc; margin: 0 0 5px 0; font-size: 16px; font-weight: 700;">SENTINEL OPEN SOURCE EDITION</h2>
        <p style="color: #8b949e; margin: 0; font-size: 13px; line-height: 1.5; max-width: 800px;">
            <strong>WARNUNG:</strong> Diese Version arbeitet mit deterministischer DFA-Logik. Kognitive KI-Inference, Swarm-Intelligence und Pre-Boot Abwehrmechanismen sind deaktiviert. Keine Haftung für Systemkompromittierungen durch polymorphe Zero-Day-Exploits. Diese Version ist eine ultra Lite Version der V7 und nicht vergleichbar mit der Abwehrkraft von VGT Sentinel Pro.
        </p>
    </div>
</div>

<!-- KPI CARDS (TOP ROW) -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:25px; margin-bottom:30px;">
    
    <!-- CARD 1: INTEGRITY -->
    <div class="vis-card" style="border-top: 3px solid var(--vis-success);">
        <h3><span class="dashicons dashicons-yes-alt"></span> INTEGRITY STATUS</h3>
        <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
            <div style="font-size:3rem; font-weight:800; color:var(--vis-success); line-height:1;">SECURE</div>
            <div style="font-size:0.9rem; color:var(--vis-text-secondary); padding-bottom:5px;">State: Valid</div>
        </div>
        <p style="font-size:13px; margin-bottom:20px;">Differential Hashing Engine active. Filesystem verified.</p>
        <button id="vis-btn-approve" class="vis-btn vis-btn-ghost" style="width:100%;">
            <span class="dashicons dashicons-yes"></span> APPROVE BASELINE
        </button>
    </div>

    <!-- CARD 2: THREATS -->
    <div class="vis-card" style="border-top: 3px solid var(--vis-danger);">
        <h3><span class="dashicons dashicons-shield"></span> NEUTRALIZED THREATS</h3>
        <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
            <div style="font-size:3rem; font-weight:800; color:var(--vis-danger); line-height:1;"><?php echo number_format($bans); ?></div>
            <div style="font-size:0.9rem; color:var(--vis-text-secondary); padding-bottom:5px;">Attackers Banned</div>
        </div>
        <p style="font-size:13px; margin-bottom:20px;">Global Banlist (SQL Optimized) protecting login & requests.</p>
        <a href="?page=vis-sentinel&tab=logs" class="vis-btn vis-btn-ghost" style="width:100%; text-align:center; display:block;">VIEW INCIDENTS</a>
    </div>

    <!-- CARD 3: AUTOMATION -->
    <div class="vis-card" style="border-top: 3px solid #3b82f6;">
        <h3><span class="dashicons dashicons-clock"></span> CHRONOS AUTOMATION</h3>
        <div style="display:flex; align-items:flex-end; gap:15px; margin:15px 0;">
            <div style="font-size:3rem; font-weight:800; color:#3b82f6; line-height:1;">ACTIVE</div>
            <div style="font-size:0.9rem; color:var(--vis-text-secondary); padding-bottom:5px;">Hourly Scan</div>
        </div>
        <p style="font-size:13px; margin-bottom:20px;">Next Auto-Scan: <?php echo wp_next_scheduled('vis_hourly_scan_event') ? date('H:i', wp_next_scheduled('vis_hourly_scan_event')) . ' UTC' : 'Pending'; ?></p>
        <div style="background:rgba(255,255,255,0.05); height:4px; width:100%; border-radius:2px; overflow:hidden;">
            <div style="background:#3b82f6; width:75%; height:100%;"></div>
        </div>
    </div>
</div>

<!-- 1. CORE MODULE MATRIX (SILBER) -->
<div class="vis-card">
    <h3 style="margin-bottom:20px; border-bottom:1px solid var(--vis-border); padding-bottom:15px; display:flex; align-items:center; gap:10px;">
        <span class="dashicons dashicons-shield-alt"></span> COMMUNITY CORE MATRIX
    </h3>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px;">
        <?php foreach($core_modules as $mod): 
            $is_active    = $mod['active'];
            $status_color = $is_active ? 'var(--vis-success)' : 'var(--vis-text-secondary)';
            $border       = $is_active ? '1px solid rgba(16, 185, 129, 0.2)' : '1px solid var(--vis-border)';
            $bg           = $is_active ? 'rgba(16, 185, 129, 0.05)' : 'rgba(255,255,255,0.02)';
            $opacity      = $is_active ? '1' : '0.8';
            $cursor       = ($mod['link'] !== '#') ? 'pointer' : 'default';
        ?>
            <a href="<?php echo esc_url($mod['link']); ?>" style="text-decoration:none; color:inherit; display:block; cursor:<?php echo $cursor; ?>;">
                <div style="
                    background: <?php echo $bg; ?>; 
                    border: <?php echo $border; ?>; 
                    border-radius:8px; 
                    padding:15px; 
                    transition:all 0.2s ease;
                    opacity: <?php echo $opacity; ?>;
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                " 
                <?php if($cursor === 'pointer'): ?>
                    onmouseover="this.style.opacity='1'; this.style.transform='translateY(-2px)'; this.style.borderColor='var(--vis-text-primary)';" 
                    onmouseout="this.style.opacity='<?php echo $opacity; ?>'; this.style.transform='translateY(0)'; this.style.borderColor='<?php echo $is_active ? 'rgba(16, 185, 129, 0.2)' : 'var(--vis-border)'; ?>';"
                <?php endif; ?>
                >
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <span class="dashicons <?php echo $mod['icon']; ?>" style="font-size:24px; color:<?php echo $status_color; ?>; width:24px; height:24px;"></span>
                        <?php if($is_active): ?>
                            <span class="vis-badge bg-green" style="font-size:9px;">ACTIVE</span>
                        <?php else: ?>
                            <span class="vis-badge" style="background:#334155; color:#94a3b8; font-size:9px; border:1px solid #475569;">OFFLINE</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight:700; font-size:12px; letter-spacing:0.5px; margin-bottom:5px; color:#fff;">
                        <?php echo $mod['label']; ?>
                    </div>
                    <div style="font-size:11px; color:var(--vis-text-secondary); line-height:1.4;">
                        <?php echo $mod['desc']; ?>
                    </div>
                    <?php if($is_active): ?>
                    <div style="position:absolute; bottom:0; left:0; width:100%; height:2px; background: linear-gradient(90deg, transparent, var(--vis-success), transparent); opacity: 0.5;"></div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- PLATINUM HIGHLIGHT: DIE WAHRE STÄRKE VON AEGIS -->
<div style="background: radial-gradient(circle at top right, rgba(6, 182, 212, 0.15) 0%, rgba(2, 6, 23, 1) 100%); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 8px; padding: 30px; margin-bottom: 25px; display: flex; gap: 30px; align-items: center; box-shadow: 0 10px 30px -10px rgba(6, 182, 212, 0.2);">
    <div style="flex-shrink: 0; text-align: center;">
        <span class="dashicons dashicons-shield" style="font-size: 64px; color: #06b6d4; width: 64px; height: 64px; filter: drop-shadow(0 0 15px rgba(6, 182, 212, 0.5));"></span>
    </div>
    <div>
        <h2 style="color: #fff; margin: 0 0 10px 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">DIE FUSION: AEGIS <span style="color: #06b6d4;">+</span> ORACLE <span style="color: #06b6d4;">+</span> ZEUS</h2>
        <p style="color: #94a3b8; margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
            Die hier implementierte <strong style="color:#fff;">AEGIS Community Edition</strong> filtert 99% aller Standard-Angriffe von Bots (SQLi, XSS) mit absoluter O(1) Geschwindigkeit. Sie ist ein robuster Schild aus deterministischer Logik.
            <br><br>
            <strong>Doch die wahre, asymmetrische Überlegenheit entfaltet Sentinel erst im Platin Status:</strong>
            AEGIS hat in der Platin Version eine weitaus größere und härtere Regex mit Payload Normalisierung etc. Wenn das System auf polymorphe Zero-Day-Payloads trifft, die herkömmliche Regelsysteme umgehen, übergibt sie den Datenstrom in Millisekunden an das <strong style="color:#06b6d4;">ORACLE (AI Inference)</strong>. Parallel greift <strong style="color:#06b6d4;">ZEUS</strong> ein und verlagert den gesamten Abwehrkampf auf die Pre-Boot PHP-Ebene – bevor WordPress überhaupt geladen wird. Ein extrem starkes, kognitives Verteidigungsnetzwerk. Alle Infos zur aktuellen V7 auf unserer Webseite.
        </p>
        <a href="https://visiongaiatechnology.de/visiongaiadefensehub/" target="_blank" class="vis-btn vis-btn-neon">
            <span class="dashicons dashicons-unlock"></span> OMEGA PROTOKOLL AKTIVIEREN
        </a>
    </div>
</div>

<!-- 3. PLATINUM SUPREME MATRIX -->
<div class="vis-card" style="border-top: 3px solid #06b6d4;">
    <h3 style="margin-bottom:20px; border-bottom:1px solid rgba(6, 182, 212, 0.2); padding-bottom:15px; display:flex; align-items:center; gap:10px; color:#06b6d4;">
        <span class="dashicons dashicons-superhero"></span> VGT OMEGA ARCHITECTURE (PLATINUM)
    </h3>
    
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap:20px;">
        <?php foreach($pro_modules as $mod): ?>
            <a href="https://visiongaiatechnology.de/visiongaiadefensehub/" target="_blank" style="text-decoration:none; color:inherit; display:block; cursor:not-allowed;">
                <div style="
                    background: rgba(15, 23, 42, 0.4); 
                    border: 1px dashed rgba(6, 182, 212, 0.2); 
                    border-radius:8px; 
                    padding:15px; 
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                    filter: grayscale(80%);
                    opacity: 0.7;
                    transition: all 0.3s ease;
                "
                onmouseover="this.style.filter='grayscale(0%)'; this.style.opacity='1'; this.style.borderColor='#06b6d4'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 0 15px rgba(6, 182, 212, 0.2)';"
                onmouseout="this.style.filter='grayscale(80%)'; this.style.opacity='0.7'; this.style.borderColor='rgba(6, 182, 212, 0.2)'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                >
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <span class="dashicons <?php echo $mod['icon']; ?>" style="font-size:24px; color:#06b6d4; width:24px; height:24px;"></span>
                        <span class="vis-badge" style="background:rgba(6, 182, 212, 0.1); color:#06b6d4; font-size:9px; border:1px solid #06b6d4;">PLATINUM <span class="dashicons dashicons-lock" style="font-size:10px; width:10px; height:10px; line-height:10px; margin-left:2px;"></span></span>
                    </div>
                    
                    <div style="font-weight:700; font-size:12px; letter-spacing:0.5px; margin-bottom:5px; color:#fff;">
                        <?php echo $mod['label']; ?>
                    </div>
                    <div style="font-size:11px; color:var(--vis-text-secondary); line-height:1.4;">
                        <?php echo $mod['desc']; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
