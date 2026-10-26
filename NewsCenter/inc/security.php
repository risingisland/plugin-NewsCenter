<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Generate CSRF Token / Nonce using GetSimple CMS native nonce functions
 */
function nc_csrf_token($action = 'newscenter_action') {
    if (function_exists('get_nonce')) {
        return get_nonce($action, 'newscenter');
    }
    if (function_exists('create_nonce')) {
        return create_nonce($action, 'newscenter');
    }
    // Fallback if core nonce functions are not available
    return md5($action . (defined('GSADMININCPATH') ? GSADMININCPATH : 'nc_token'));
}

/**
 * Verify CSRF Token from POST request using GetSimple CMS native nonce check
 */
function nc_verify_csrf($action = 'newscenter_action') {
    $token = $_POST['nc_csrf'] ?? $_GET['nc_csrf'] ?? '';

    if (empty($token)) {
        die(i18n_r('NewsCenter/CSRF_ERROR'));
    }

    if (function_exists('check_nonce')) {
        $valid = check_nonce($token, $action, 'newscenter')
              || check_nonce($token, $action)
              || check_nonce($token, 'load.php?id=' . NC_PLUGIN_ID);
        if (!$valid) {
            die(i18n_r('NewsCenter/CSRF_ERROR'));
        }
        return true;
    }

    // Fallback check
    $expected = md5($action . (defined('GSADMININCPATH') ? GSADMININCPATH : 'nc_token'));
    if (!hash_equals($expected, $token)) {
        die(i18n_r('NewsCenter/CSRF_ERROR'));
    }
    return true;
}

/**
 * Defined HTML sanitizer for Article rich text before saving/rendering
 */
function nc_sanitize_html($html) {
    if (empty($html)) {
        return '';
    }
    // Remove script tags and contents
    $cleaned = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);
    // Remove iframe tags and contents
    $cleaned = preg_replace('#<iframe\b[^>]*>(.*?)</iframe>#is', '', $cleaned);
    // Remove on* event handlers (double-quoted, single-quoted, or unquoted)
    $cleaned = preg_replace('#\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $cleaned);
    // Remove javascript: and vbscript: URLs in href/src attributes
    $cleaned = preg_replace('#\b(href|src)\s*=\s*(["\'])\s*(javascript|vbscript|data):.*?\2#i', '$1="#"', $cleaned);
    return $cleaned;
}