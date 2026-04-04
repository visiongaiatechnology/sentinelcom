<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * MODULE: HADES ROUTES (The Maze)
 * Status: ACTIVE
 * Logic: Hides wp-login.php and wp-admin via URL Rewrite & Filtering.
 * Adaptiert von WP Ghost Rewrite Logic.
 */
class VIS_Hades_Routes {

    private $login_slug;
    private $admin_slug;
    private $enabled;

    public function __construct($options) {
        $this->login_slug = isset($options['hades_login_slug']) && !empty($options['hades_login_slug']) ? sanitize_title($options['hades_login_slug']) : 'wp-login.php';
        $this->admin_slug = isset($options['hades_admin_slug']) && !empty($options['hades_admin_slug']) ? sanitize_title($options['hades_admin_slug']) : 'wp-admin';
        $this->enabled    = !empty($options['hades_enabled']);

        if ($this->enabled) {
            // 1. URL Filters (Output)
            add_filter('site_url', [$this, 'rewrite_login_url'], 10, 4);
            add_filter('network_site_url', [$this, 'rewrite_login_url'], 10, 3);
            add_filter('wp_redirect', [$this, 'filter_redirect'], 10, 2);
            
            // 2. Admin URL Rewrite (Optional & Dangerous)
            if ($this->admin_slug !== 'wp-admin') {
                add_filter('admin_url', [$this, 'rewrite_admin_url'], 10, 3);
                add_filter('url_to_postid', [$this, 'fix_url_to_postid']); // Fix für Permalinks
            }

            // 3. Block Access to Old Paths (Active Defense)
            add_action('init', [$this, 'guard_paths']);
        }
    }

    /**
     * Ändert wp-login.php Links global in den neuen Slug.
     */
    public function rewrite_login_url($url, $path, $scheme, $blog_id = null) {
        if ($this->login_slug === 'wp-login.php') return $url;

        if (strpos($url, 'wp-login.php') !== false) {
            // Verhindere Doppel-Ersetzungen und Loop-Fehler
            $query = parse_url($url, PHP_URL_QUERY);
            $base  = str_replace('wp-login.php', $this->login_slug, $url);
            // Query String wieder anhängen falls verloren
            if ($query && strpos($base, $query) === false) {
                $base .= '?' . $query;
            }
            return $base;
        }
        return $url;
    }

    /**
     * Ändert wp-admin Links global.
     */
    public function rewrite_admin_url($url, $path, $blog_id) {
        if ($this->admin_slug === 'wp-admin') return $url;

        return str_replace('wp-admin/', $this->admin_slug . '/', $url);
    }

    /**
     * Verhindert Redirect-Schleifen (Loop Check).
     * Wenn WP versucht, zurück auf wp-login.php zu leiten, zwingen wir den neuen Slug.
     */
    public function filter_redirect($location, $status) {
        if ($this->login_slug !== 'wp-login.php' && strpos($location, 'wp-login.php') !== false) {
            return str_replace('wp-login.php', $this->login_slug, $location);
        }
        if ($this->admin_slug !== 'wp-admin' && strpos($location, 'wp-admin/') !== false) {
            return str_replace('wp-admin/', $this->admin_slug . '/', $location);
        }
        return $location;
    }

    /**
     * BLOCKT Zugriff auf die alten Pfade (Security).
     */
    public function guard_paths() {
        if (is_admin() || defined('DOING_AJAX')) return;

        $request = $_SERVER['REQUEST_URI'];

        // Login Block
        if ($this->login_slug !== 'wp-login.php' && strpos($request, 'wp-login.php') !== false) {
            // Ausnahme: Logout Action oder POST
            if (!isset($_GET['action']) || $_GET['action'] !== 'logout') {
                $this->deny_access();
            }
        }

        // Admin Block (Nur wenn nicht eingeloggt oder strikt)
        if ($this->admin_slug !== 'wp-admin' && strpos($request, '/wp-admin/') !== false) {
            // AJAX Ausnahmen zulassen
            if (strpos($request, 'admin-ajax.php') === false) {
                $this->deny_access();
            }
        }
    }

    private function deny_access() {
        // Zeige 404 statt 403, um Existenz zu verschleiern
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
        include(get_query_template('404'));
        exit;
    }

    public function fix_url_to_postid($url) {
        if ($this->admin_slug !== 'wp-admin') {
            return str_replace($this->admin_slug, 'wp-admin', $url);
        }
        return $url;
    }

    /**
     * Generiert die .htaccess Regeln für VIS_Hades
     */
    public function get_apache_rules() {
        $rules = "";

        // LOGIN REWRITE
        if ($this->login_slug !== 'wp-login.php') {
            $slug = $this->login_slug;
            $rules .= "RewriteRule ^{$slug}/?$ wp-login.php [QSA,L]\n";
        }

        // ADMIN REWRITE
        if ($this->admin_slug !== 'wp-admin') {
            $slug = $this->admin_slug;
            $rules .= "RewriteRule ^{$slug}/(.*) wp-admin/$1 [QSA,L]\n";
            $rules .= "RewriteRule ^{$slug}$ wp-admin/index.php [QSA,L]\n";
        }

        return $rules;
    }

    public function get_nginx_rules() {
        $rules = "";
        
        if ($this->login_slug !== 'wp-login.php') {
            $rules .= "rewrite ^/{$this->login_slug}/?$ /wp-login.php last;\n";
        }
        
        if ($this->admin_slug !== 'wp-admin') {
            $rules .= "rewrite ^/{$this->admin_slug}/(.*) /wp-admin/$1 last;\n";
            $rules .= "rewrite ^/{$this->admin_slug}$ /wp-admin/index.php last;\n";
        }

        return $rules;
    }
}