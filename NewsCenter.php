<?php
/**
 * Plugin Name: NewsCenter
 * Description: Modern news and article management plugin for GetSimple CMS CE with SQLite, Autumnnote, and CodeMirror.
 * Version: 1.1
 * Author: risingisland
 * Website: https://github.com/risingisland
 */

if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

# Constants & Paths
define('NC_VERSION', '1.1');
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
    'risingisland',
    'https://github.com/risingisland',
    i18n_r('NewsCenter/PLUGIN_DESC'),
    'newscenter',
    'nc_admin_router'
);

$svgIcon = '<svg xmlns="http://www.w3.org/2000/svg" style="margin-right: 5px;" width="22" height="22" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="currentColor" d="M13.03 10c-.122 0-.255 0-.37.01c-.13.01-.3.036-.478.126a1.25 1.25 0 0 0-.546.547c-.09.176-.116.348-.127.478c-.01.114-.009.247-.009.369v1.19c0 .122 0 .255.01.37c.01.13.036.3.126.478c.12.235.311.426.547.546c.176.09.348.116.478.127c.114.01.247.009.369.009h1.94c.122 0 .255 0 .37-.01c.13-.01.3-.036.478-.126a1.25 1.25 0 0 0 .546-.546c.09-.177.116-.349.127-.479c.01-.114.009-.247.009-.369v-1.19c0-.122 0-.255-.01-.37a1.3 1.3 0 0 0-.126-.478a1.25 1.25 0 0 0-.546-.546a1.3 1.3 0 0 0-.479-.127A5 5 0 0 0 14.97 10zm-4.78.25a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5zm0 2.25a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5zm0 2.75a.75.75 0 0 0 0 1.5h7.5a.75.75 0 0 0 0-1.5zm0 2.5a.75.75 0 0 0 0 1.5h7.5a.75.75 0 0 0 0-1.5z" opacity=".5"/><path fill="currentColor" d="M17.83 2.25H6.17c-.535 0-.98 0-1.345.03c-.38.03-.736.098-1.073.27A2.75 2.75 0 0 0 2.55 3.752c-.172.337-.24.693-.27 1.073c-.03.365-.03.81-.03 1.345V21a.75.75 0 0 0 1.5 0V6.2c0-.572 0-.957.025-1.253c.023-.287.065-.424.111-.514a1.25 1.25 0 0 1 .547-.547c.09-.046.227-.088.514-.111c.296-.024.68-.025 1.253-.025h11.6c.572 0 .957 0 1.252.025c.288.023.425.065.515.111c.236.12.427.311.547.547c.046.09.088.227.111.514c.024.296.025.68.025 1.253V21a.75.75 0 0 0 1.5 0V6.17c0-.535 0-.98-.03-1.345c-.03-.38-.098-.736-.27-1.073a2.75 2.75 0 0 0-1.2-1.202c-.338-.172-.694-.24-1.074-.27c-.365-.03-.81-.03-1.345-.03"/><path fill="currentColor" d="M7.5 6.5a1 1 0 0 0 0 2h9a1 1 0 1 0 0-2z"/></svg>';

# Top Nav Tab (Native GetSimple CMS Tab Binder)
add_action('nav-tab', 'createNavTab', ['newscenter', NC_PLUGIN_ID, $svgIcon . i18n_r('NewsCenter/NAV_TITLE')]);

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