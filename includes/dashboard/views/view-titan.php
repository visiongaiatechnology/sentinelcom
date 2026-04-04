<?php 
declare(strict_types=1);
if (!defined('ABSPATH')) exit; ?>

<div class="vis-card">
    <h3><span class="dashicons dashicons-lock"></span> KERNEL HÄRTUNG & .HTACCESS</h3>
    <p style="color:var(--vis-text-secondary); margin-bottom:20px; font-size:13px;">
        Alle Aktivierungen in diesem Modul werden automatisch in die <code>.htaccess</code> geschrieben, um maximalen Schutz auf Server-Ebene zu gewährleisten.
    </p>

    <!-- SECTION 1: CAMOUFLAGE -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>HEADER CAMOUFLAGE (TÄUSCHUNG)</strong>
            <p>Verschleiert WordPress und injiziert Fake-Header (z.B. Laravel), um Angreifer zu verwirren.</p>
        </div>
        <div>
            <select name="vis_config[titan_camouflage_mode]">
                <option value="none" <?php selected($opt['titan_camouflage_mode'] ?? '', 'none'); ?>>Deaktiviert (Standard)</option>
                <option value="laravel" <?php selected($opt['titan_camouflage_mode'] ?? '', 'laravel'); ?>>Laravel Framework (Empfohlen)</option>
                <option value="drupal" <?php selected($opt['titan_camouflage_mode'] ?? '', 'drupal'); ?>>Drupal CMS</option>
                <option value="django" <?php selected($opt['titan_camouflage_mode'] ?? '', 'django'); ?>>Django (Python)</option>
            </select>
        </div>
    </div>

    <!-- SECTION 2: API & PROTOCOLS -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>XML-RPC BLOCKIEREN</strong>
            <p>Schließt die <code>xmlrpc.php</code> Schnittstelle komplett (Stoppt DDoS & Brute Force).</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[titan_block_xmlrpc]" <?php checked(!empty($opt['titan_block_xmlrpc'])); ?>>
            <span class="slider"></span>
        </label>
    </div>

    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>REST API EINSCHRÄNKEN</strong>
            <p>Erlaubt Zugriff auf die REST API nur für eingeloggte Benutzer.</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[titan_block_rest]" <?php checked(!empty($opt['titan_block_rest'])); ?>>
            <span class="slider"></span>
        </label>
    </div>

    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>RSS & ATOM FEEDS DEAKTIVIEREN</strong>
            <p>Verhindert Content-Scraping durch Bots. Gibt 403 Forbidden bei Feed-Zugriff.</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[titan_disable_feeds]" <?php checked(!empty($opt['titan_disable_feeds'])); ?>>
            <span class="slider"></span>
        </label>
    </div>

    <!-- SECTION 3: BASE PROTECTION -->
    <div class="vis-switch-row">
        <div class="vis-label-group">
            <strong>SECURITY HEADERS INJECTION</strong>
            <p>Erzwingt HSTS, X-Frame-Options und XSS-Protection.</p>
        </div>
        <label class="vis-switch">
            <input type="checkbox" name="vis_config[titan_enabled]" <?php checked(!empty($opt['titan_enabled'])); ?>>
            <span class="slider"></span>
        </label>
    </div>
</div>