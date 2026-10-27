<?php
/**
 * Plugin Name: NewsCenter
 * Description: Modern news and article management plugin for GetSimple CMS CE with SQLite, Autumnnote, and CodeMirror.
 * Version: 1.0.1
 * Author: NewsCenter Team
 * Website: https://getsimple-ce.ovh/
 */

if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

# Constants & Paths
define('NC_VERSION', '1.0.1');
define('NC_PLUGIN_ID', 'NewsCenter');

# Filesystem Paths
define('NC_DIR', GSPLUGINPATH . 'NewsCenter/');
define('NC_DATA_DIR', GSDATAOTHERPATH . 'newscenter/');
define('NC_DB_FILE', NC_DATA_DIR . 'newscenter.db');

# URL Paths
global $SITEURL;
$nc_site_url = rtrim($SITEURL, '/') . '/';
define('NC_URL', $nc_site_url . 'plugins/NewsCenter/');

# Core Includes
require_once(NC_DIR . 'inc/database.php');
require_once(NC_DIR . 'inc/security.php');
require_once(NC_DIR . 'inc/helpers.php');
require_once(NC_DIR . 'inc/repository.php');
require_once(NC_DIR . 'inc/templates.php');
require_once(NC_DIR . 'inc/frontend.php');

# Register i18n
i18n_merge(NC_PLUGIN_ID) || i18n_merge(NC_PLUGIN_ID, 'en_US');

# Register with GetSimple CMS CE under custom 'newscenter' tab
register_plugin(
    NC_PLUGIN_ID,
    'NewsCenter',
    NC_VERSION,
    'NewsCenter Team',
    'https://getsimple-ce.ovh/',
    i18n_r('NewsCenter/PLUGIN_DESC'),
    'newscenter',
    'nc_admin_router'
);

# Top Nav Tab (Native GetSimple CMS Tab Binder)
add_action('nav-tab', 'createNavTab', ['newscenter', NC_PLUGIN_ID, i18n_r('NewsCenter/NAV_TITLE')]);

# Admin Sidebar Menu
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_DASHBOARD'), 'action=dashboard']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_ARTICLES'), 'action=articles']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/NEW_ARTICLE'), 'action=edit-article']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_CATEGORIES'), 'action=categories']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_TAGS'), 'action=tags']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, 'Authors', 'action=authors']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_TEMPLATES'), 'action=templates']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_SETTINGS'), 'action=settings']);
add_action('newscenter-sidebar', 'createSideMenu', [NC_PLUGIN_ID, i18n_r('NewsCenter/TAB_DOCS'), 'action=docs']);

# Inject Head Stylesheets in Admin
add_action('header', 'nc_admin_header_include');

# Early Request Interceptor (Runs BEFORE admin template header is sent)
add_action('admin-pre-header', 'nc_admin_pre_header_intercept');

# Frontend & Sitemap Hooks
add_action('index-pretemplate', 'nc_frontend_handler');
add_action('sitemap-aftersave', 'nc_generate_sitemap');
add_action('theme-header', 'nc_header_meta');

/**
 * Intercept POST submissions, GET deletes and AJAX endpoints before load.php prints template/header.php
 */
function nc_admin_pre_header_intercept() {
    if (!isset($_GET['id']) || $_GET['id'] !== NC_PLUGIN_ID) {
        return;
    }

    nc_init_database();

    if (!nc_check_permission()) {
        die(i18n_r('NewsCenter/ACCESS_DENIED'));
    }

    $action = 'dashboard';
    if (!empty($_GET['action'])) {
        $action = nc_sanitize_slug($_GET['action']);
    } else {
        $known_actions = ['dashboard', 'articles', 'edit-article', 'categories', 'tags', 'authors', 'templates', 'settings', 'docs'];
        foreach ($known_actions as $candidate) {
            if (isset($_GET[$candidate])) {
                $action = $candidate;
                break;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'ajax_upload') {
        nc_handle_post_actions($action);
    }

    if (isset($_GET['do']) && $_GET['do'] === 'delete') {
        nc_handle_get_delete_actions();
    }

    if ($action === 'ajax_media') {
        nc_ajax_get_media();
        exit;
    }

    if ($action === 'ajax_upload') {
        nc_handle_upload();
        exit;
    }
}

/**
 * Enqueue NewsCenter CSS into admin <head>
 */
function nc_admin_header_include() {
    if (isset($_GET['id']) && $_GET['id'] === NC_PLUGIN_ID) {
        echo '<link rel="stylesheet" href="' . NC_URL . 'assets/css/admin.css?v=' . NC_VERSION . '">' . "\n";
        if (file_exists(NC_DIR . 'assets/css/custom.css')) {
            echo '<link rel="stylesheet" href="' . NC_URL . 'assets/css/custom.css?v=' . NC_VERSION . '">' . "\n";
        }
    }
}

/**
 * Admin Router & Controller (Output views inside page body)
 */
function nc_admin_router() {
    if (!nc_check_permission()) {
        die(i18n_r('NewsCenter/ACCESS_DENIED'));
    }

    $action = 'dashboard';
    if (!empty($_GET['action'])) {
        $action = nc_sanitize_slug($_GET['action']);
    } else {
        $known_actions = ['dashboard', 'articles', 'edit-article', 'categories', 'tags', 'authors', 'templates', 'settings', 'docs'];
        foreach ($known_actions as $candidate) {
            if (isset($_GET[$candidate])) {
                $action = $candidate;
                break;
            }
        }
    }

    $view_map = [
        'dashboard'     => 'admin/dashboard.php',
        'articles'      => 'admin/articles.php',
        'edit-article'  => 'admin/edit-article.php',
        'categories'    => 'admin/categories.php',
        'tags'          => 'admin/tags.php',
        'authors'       => 'admin/authors.php',
        'templates'     => 'admin/templates.php',
        'settings'      => 'admin/settings.php',
        'docs'          => 'admin/documentation.php'
    ];

    $file = isset($view_map[$action]) ? NC_DIR . $view_map[$action] : NC_DIR . 'admin/dashboard.php';

    require_once(NC_DIR . 'admin/header.php');
    require_once($file);
    require_once(NC_DIR . 'admin/footer.php');
}