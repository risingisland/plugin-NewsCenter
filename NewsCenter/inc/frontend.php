<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * NewsCenter Frontend Engine
 */

add_action('index-pretemplate', 'nc_frontend_handler');
add_filter('content', 'nc_content_shortcode_filter');
add_action('theme-header', 'nc_inject_frontend_css');
add_action('theme-footer', 'nc_inject_frontend_share_script');

/**
 * Active context trackers
 */
function nc_current_article($set = null) {
    static $article = null;
    if ($set !== null) $article = $set;
    return $article;
}

function nc_current_category($set = null) {
    static $category = null;
    if ($set !== null) $category = $set;
    return $category;
}

function nc_current_author($set = null) {
    static $author = null;
    if ($set !== null) $author = $set;
    return $author;
}

/**
 * Resolves the native GetSimple thumbnail for an article's featured image
 * using nc_thumb_url() from helpers.php.
 */
function nc_get_article_thumb_url($image_val) {
    if (empty($image_val)) {
        return '';
    }

    $clean = trim((string)$image_val);

    global $SITEURL;
    $uploads_base = rtrim((string)$SITEURL, '/') . '/data/uploads/';
    if (strpos($clean, $uploads_base) === 0) {
        $clean = substr($clean, strlen($uploads_base));
    } else {
        $clean = ltrim($clean, '/');
        if (strpos($clean, 'data/uploads/') === 0) {
            $clean = substr($clean, strlen('data/uploads/'));
        }
    }

    if (function_exists('nc_thumb_url')) {
        return nc_thumb_url($clean);
    }

    $parts = explode('/', $clean);
    $base  = array_pop($parts);
    $sub   = count($parts) ? implode('/', $parts) . '/' : '';

    if (defined('GSTHUMBNAILPATH') && is_file(GSTHUMBNAILPATH . $sub . 'thumbnail.' . $base)) {
        $site = defined('SITEURL') ? SITEURL : (isset($GLOBALS['SITEURL']) ? $GLOBALS['SITEURL'] : '/');
        $site = rtrim($site, '/') . '/';
        return $site . 'data/thumbs/' . implode('/', array_map('rawurlencode', explode('/', $sub . 'thumbnail.' . $base)));
    }

    return function_exists('nc_image_url') ? nc_image_url($clean) : (string)$image_val;
}

/**
 * Renders an accessible, zero-dependency share widget dropdown.
 */
function nc_render_share_widget($url, $title = '') {
    if (empty($url)) {
        return '';
    }

    $raw_url   = (string)$url;
    $raw_title = (string)$title;
    $enc_url   = rawurlencode($raw_url);
    $enc_title = rawurlencode($raw_title);
    $widget_id = 'nc-share-' . substr(md5($raw_url . $raw_title . mt_rand()), 0, 8);

    $fb_url       = "https://www.facebook.com/sharer/sharer.php?u={$enc_url}";
    $x_url        = "https://twitter.com/intent/tweet?url={$enc_url}&text={$enc_title}";
    $whatsapp_url = "https://api.whatsapp.com/send?text=" . rawurlencode("{$raw_title} - {$raw_url}");
    $email_url    = "mailto:?subject={$enc_title}&body=" . rawurlencode("Check this out: {$raw_url}");

    $lbl_share     = i18n_r('NewsCenter/SHARE') ?: 'Share';
    $lbl_copy_link = i18n_r('NewsCenter/COPY_LINK') ?: 'Copy link';
    $lbl_copied    = i18n_r('NewsCenter/COPIED') ?: 'Copied!';

    ob_start();
    ?>
    <div class="nc-share-container" id="<?= $widget_id ?>" 
         data-share-url="<?= htmlspecialchars($raw_url, ENT_QUOTES, 'UTF-8') ?>" 
         data-share-title="<?= htmlspecialchars($raw_title, ENT_QUOTES, 'UTF-8') ?>"
         data-text-copy="<?= htmlspecialchars($lbl_copy_link, ENT_QUOTES, 'UTF-8') ?>"
         data-text-copied="<?= htmlspecialchars($lbl_copied, ENT_QUOTES, 'UTF-8') ?>">
        <button type="button" class="nc-share-trigger" aria-haspopup="true" aria-expanded="false" title="<?= htmlspecialchars($lbl_share, ENT_QUOTES, 'UTF-8') ?>">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor">
                <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92z"/>
            </svg>
            <span class="nc-share-label"><?= htmlspecialchars($lbl_share, ENT_QUOTES, 'UTF-8') ?></span>
        </button>

        <div class="nc-share-dropdown" role="menu">
            <button type="button" class="nc-share-item nc-share-copy" role="menuitem">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span class="nc-copy-text"><?= htmlspecialchars($lbl_copy_link, ENT_QUOTES, 'UTF-8') ?></span>
            </button>

            <a href="<?= $x_url ?>" class="nc-share-item" target="_blank" rel="noopener noreferrer" role="menuitem">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span>X (Twitter)</span>
            </a>

            <a href="<?= $fb_url ?>" class="nc-share-item" target="_blank" rel="noopener noreferrer" role="menuitem">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
            </a>

            <a href="<?= $whatsapp_url ?>" class="nc-share-item" target="_blank" rel="noopener noreferrer" role="menuitem">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                    <path d="M17.472 14.382c-.301-.15-1.782-.879-2.058-.98-.276-.1-.477-.15-.678.15-.201.3-.778.98-.954 1.18-.175.2-.351.226-.652.076-.301-.151-1.27-.468-2.42-1.494-.894-.799-1.498-1.785-1.674-2.086-.176-.301-.019-.464.132-.614.136-.135.301-.351.452-.527.15-.176.201-.301.301-.502.1-.201.05-.376-.025-.527-.075-.151-.678-1.633-.929-2.235-.245-.588-.493-.508-.678-.517-.176-.008-.376-.01-.577-.01-.201 0-.527.075-.803.376-.276.301-1.054 1.03-1.054 2.511 0 1.482 1.079 2.912 1.23 3.113.15.201 2.123 3.242 5.143 4.548.718.311 1.279.497 1.716.636.723.23 1.381.198 1.901.12.58-.087 1.782-.728 2.033-1.431.251-.703.251-1.305.176-1.431-.076-.126-.276-.201-.577-.351zM12.04 21.785h-.002c-1.758 0-3.483-.473-4.996-1.368l-.358-.212-3.715.974.991-3.621-.233-.371a9.715 9.715 0 0 1-1.491-5.187C2.227 6.64 6.629 2.238 12.04 2.238c2.62 0 5.083 1.021 6.936 2.875 1.853 1.854 2.873 4.318 2.873 6.939 0 5.4-4.402 9.733-9.809 9.733zM20.52 3.48A11.934 11.934 0 0 0 12.04.004C5.412.004.021 5.395.021 12.023c0 2.115.553 4.181 1.604 6.002L0 24l6.155-1.614a11.944 11.944 0 0 0 5.885 1.541h.005c6.627 0 12.019-5.391 12.019-12.019 0-3.21-1.25-6.227-3.544-8.428z"/>
            </svg>
            <span>WhatsApp</span>
        </button>

        <a href="<?= $email_url ?>" class="nc-share-item" role="menuitem">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            <span>Email</span>
        </a>
    </div>
</div>
<?php
    return trim(ob_get_clean());
}

/**
 * Hooked to 'theme-footer' to guarantee the script outputs intact on every page.
 */
function nc_inject_frontend_share_script() {
    static $injected = false;
    if ($injected) {
        return;
    }
    $injected = true;
    ?>
<script>
(function() {
    if (window._ncShareScriptLoaded) return;
    window._ncShareScriptLoaded = true;

    function initShareHandler() {
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.nc-share-trigger');
            if (trigger) {
                e.preventDefault();
                e.stopPropagation();

                var container = trigger.closest('.nc-share-container');
                if (!container) return;

                if (navigator.share && window.matchMedia('(max-width: 768px)').matches) {
                    navigator.share({
                        title: container.getAttribute('data-share-title') || document.title,
                        url: container.getAttribute('data-share-url') || window.location.href
                    }).catch(function() {});
                    return;
                }

                var isCurrentlyActive = container.classList.contains('active');
                document.querySelectorAll('.nc-share-container.active').forEach(function(c) {
                    if (c !== container) c.classList.remove('active');
                });

                container.classList.toggle('active', !isCurrentlyActive);
                return;
            }

            var copyBtn = e.target.closest('.nc-share-copy');
            if (copyBtn) {
                e.preventDefault();
                e.stopPropagation();

                var container = copyBtn.closest('.nc-share-container');
                var url = container ? container.getAttribute('data-share-url') : window.location.href;
                var textSpan = copyBtn.querySelector('.nc-copy-text');
                var copyLabel = container ? (container.getAttribute('data-text-copy') || 'Copy link') : 'Copy link';
                var copiedLabel = container ? (container.getAttribute('data-text-copied') || 'Copied!') : 'Copied!';

                var onCopySuccess = function() {
                    if (textSpan) textSpan.textContent = copiedLabel;
                    setTimeout(function() {
                        if (textSpan) textSpan.textContent = copyLabel;
                        if (container) container.classList.remove('active');
                    }, 1500);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(onCopySuccess).catch(function() {
                        fallbackExecCopy(url, onCopySuccess);
                    });
                } else {
                    fallbackExecCopy(url, onCopySuccess);
                }
                return;
            }

            if (e.target.closest('.nc-share-dropdown')) {
                e.stopPropagation();
                return;
            }

            document.querySelectorAll('.nc-share-container.active').forEach(function(c) {
                c.classList.remove('active');
            });
        }, true);
    }

    function fallbackExecCopy(text, cb) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        ta.style.top = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } catch (err) {}
        document.body.removeChild(ta);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShareHandler);
    } else {
        initShareHandler();
    }
})();
</script>
    <?php
}

/**
 * Fallback helper to return script markup if theme lacks get_footer() hook.
 */
function nc_get_frontend_share_script() {
    ob_start();
    nc_inject_frontend_share_script();
    return ob_get_clean();
}

/**
 * Comprehensive SEO Image resolver for BetterSeo
 */
function nc_get_current_seo_image() {
    global $SITEURL;
    $site = rtrim((string)$SITEURL, '/') . '/';

    // 1. Article view
    $art = nc_current_article();
    if (!empty($art['featured_image'])) {
        $img = trim((string)$art['featured_image']);
        return preg_match('#^(https?:)?//#i', $img) ? $img : $site . ltrim($img, '/');
    }

    // 2. Category view
    $cat = nc_current_category();
    if (!empty($cat['image'])) {
        $img = trim((string)$cat['image']);
        return preg_match('#^(https?:)?//#i', $img) ? $img : $site . ltrim($img, '/');
    }

    // 3. Author view
    $au = nc_current_author();
    if (!empty($au['image'])) {
        $img = trim((string)$au['image']);
        return preg_match('#^(https?:)?//#i', $img) ? $img : $site . ltrim($img, '/');
    }

    return '';
}

function nc_get_current_featured_image() {
    return nc_get_current_seo_image();
}

/**
 * Clean URL Generator supporting Fancy URLs and query strings
 */
function nc_build_url($type = '', $slug = '') {
    $settings = nc_get_settings();
    $page_slug = !empty($settings['page_slug']) ? $settings['page_slug'] : 'news';
    $trailing_slash = (!isset($settings['trailing_slash']) || $settings['trailing_slash'] === '1');
    $fancy = (defined('PRETTYURLS') && PRETTYURLS == 1) || (isset($GLOBALS['PRETTYURLS']) && $GLOBALS['PRETTYURLS'] == 1);

    if (empty($type) || empty($slug)) {
        $url = find_url($page_slug, '');
        return ($fancy && !$trailing_slash) ? rtrim($url, '/') : $url;
    }

    if ($fancy) {
        $base_url = find_url($page_slug, '');
        $clean_base = rtrim($base_url, '/') . '/';
        $full = $clean_base . rawurlencode($type) . '/' . rawurlencode($slug);
        return $trailing_slash ? $full . '/' : $full;
    } else {
        global $SITEURL;
        $site_url = rtrim((string)$SITEURL, '/') . '/';
        return $site_url . 'index.php?id=' . urlencode($page_slug) . '&' . rawurlencode($type) . '=' . rawurlencode($slug);
    }
}

function nc_build_pagination_url($page_num, $filter_type = 'all', $filter_val = '') {
    $settings = nc_get_settings();
    $page_slug = !empty($settings['page_slug']) ? $settings['page_slug'] : 'news';
    $trailing_slash = (!isset($settings['trailing_slash']) || $settings['trailing_slash'] === '1');
    $fancy = (defined('PRETTYURLS') && PRETTYURLS == 1) || (isset($GLOBALS['PRETTYURLS']) && $GLOBALS['PRETTYURLS'] == 1);

    $page_num = max(1, (int)$page_num);

    if ($filter_type === 'all') {
        if ($fancy) {
            $base_url = rtrim(find_url($page_slug, ''), '/');
            if ($page_num === 1) {
                return $trailing_slash ? $base_url . '/' : $base_url;
            }
            $full = $base_url . '/page/' . $page_num;
            return $trailing_slash ? $full . '/' : $full;
        } else {
            global $SITEURL;
            $site_url = rtrim((string)$SITEURL, '/') . '/';
            if ($page_num === 1) {
                return $site_url . 'index.php?id=' . urlencode($page_slug);
            }
            return $site_url . 'index.php?id=' . urlencode($page_slug) . '&page=' . $page_num;
        }
    }

    if ($fancy && in_array($filter_type, ['category', 'tag', 'author', 'archive'])) {
        $base_url = rtrim(find_url($page_slug, ''), '/');
        $tax_base = $base_url . '/' . rawurlencode($filter_type) . '/' . rawurlencode($filter_val);
        $tax_base = $trailing_slash ? $tax_base . '/' : $tax_base;
        return ($page_num === 1) ? $tax_base : $tax_base . '?page=' . $page_num;
    }

    global $SITEURL;
    $site_url = rtrim((string)$SITEURL, '/') . '/';
    $params = ['id' => $page_slug];
    if ($filter_type === 'search') {
        $params['nc_query'] = $filter_val;
    } else {
        $params[$filter_type] = $filter_val;
    }
    if ($page_num > 1) {
        $params['page'] = $page_num;
    }
    return $site_url . 'index.php?' . http_build_query($params);
}

function nc_generate_sitemap() {
    global $SITEURL;
    $db = nc_get_db();
    $settings = nc_get_settings();
    $priority_art = !empty($settings['sitemap_priority']) ? $settings['sitemap_priority'] : '0.8';
    $sitemap_file = GSROOTPATH . 'sitemap.xml';

    $sitemap_xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $sitemap_xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    // 1. Native GetSimple CMS Pages
    $pages_dir = GSDATAPAGESPATH;
    $pages = glob($pages_dir . '*.xml');
    $friendly_urls = (defined('PRETTYURLS') && PRETTYURLS == 1) || (isset($GLOBALS['PRETTYURLS']) && $GLOBALS['PRETTYURLS'] == 1);

    if ($pages) {
        foreach ($pages as $pfile) {
            $page_data = @getXML($pfile);
            if ($page_data && (string)$page_data->private !== 'Y') {
                $pslug = pathinfo($pfile, PATHINFO_FILENAME);

                if ($friendly_urls) {
                    $purl = ($pslug === 'index') ? $SITEURL : find_url($pslug, '');
                } else {
                    $purl = ($pslug === 'index') ? $SITEURL : $SITEURL . 'index.php?id=' . rawurlencode($pslug);
                }

                $sitemap_xml .= '  <url>' . PHP_EOL;
                $sitemap_xml .= '    <loc>' . htmlspecialchars((string)$purl, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;

                if (isset($page_data->pubDate)) {
                    $sitemap_xml .= '    <lastmod>' . date('Y-m-d', strtotime((string)$page_data->pubDate)) . '</lastmod>' . PHP_EOL;
                }

                $p_prio = ($pslug === 'index') ? '1.0' : '0.6';
                $sitemap_xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
                $sitemap_xml .= '    <priority>' . $p_prio . '</priority>' . PHP_EOL;
                $sitemap_xml .= '  </url>' . PHP_EOL;
            }
        }
    }

    // 2. Published NewsCenter Articles
    $stmt = $db->query("
        SELECT slug, updated_at, published_at 
        FROM nc_articles 
        WHERE status = 'published' AND published_at <= CURRENT_TIMESTAMP 
        ORDER BY published_at DESC
    ");
    $articles = $stmt->fetchAll();

    if ($articles) {
        foreach ($articles as $art) {
            $art_url = nc_build_url('post', $art['slug']);
            $mod_time = !empty($art['updated_at']) ? strtotime($art['updated_at']) : strtotime($art['published_at']);

            $sitemap_xml .= '  <url>' . PHP_EOL;
            $sitemap_xml .= '    <loc>' . htmlspecialchars((string)$art_url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;
            $sitemap_xml .= '    <lastmod>' . date('Y-m-d', $mod_time) . '</lastmod>' . PHP_EOL;
            $sitemap_xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $sitemap_xml .= '    <priority>' . htmlspecialchars((string)$priority_art, ENT_QUOTES, 'UTF-8') . '</priority>' . PHP_EOL;
            $sitemap_xml .= '  </url>' . PHP_EOL;
        }
    }

    $sitemap_xml .= '</urlset>' . PHP_EOL;

    @file_put_contents($sitemap_file, $sitemap_xml);
    return true;
}

function nc_clean_meta_description($text, $limit = 160) {
    if (empty($text)) return '';
    $clean = strip_tags((string)$text);
    $clean = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);
    if (mb_strlen($clean, 'UTF-8') > $limit) {
        $clean = mb_substr($clean, 0, $limit - 3, 'UTF-8') . '...';
    }
    return htmlspecialchars((string)$clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nc_current_canonical($set = null) {
    static $canonical = null;
    if ($set !== null) $canonical = $set;
    return $canonical;
}

function nc_inject_frontend_css() {
    static $injected = false;
    if ($injected) return;

    $db = nc_get_db();
    $css = $db->query("SELECT content FROM nc_templates WHERE template_key = 'frontend_css' LIMIT 1")->fetchColumn();

    if ($css) {
        echo "\n" . '<!-- NewsCenter Dynamic Stylesheet -->' . "\n";
        echo '<style id="newscenter-frontend-css">' . "\n" . $css . "\n" . '</style>' . "\n";
        $injected = true;
    }
}

function nc_get_frontend_css_tag() {
    static $printed = false;
    if ($printed) return '';

    $db = nc_get_db();
    $css = $db->query("SELECT content FROM nc_templates WHERE template_key = 'frontend_css' LIMIT 1")->fetchColumn();

    if (!$css) {
        nc_reset_default_templates();
        $css = $db->query("SELECT content FROM nc_templates WHERE template_key = 'frontend_css' LIMIT 1")->fetchColumn();
    }

    $printed = true;
    return '<style id="newscenter-frontend-css">' . "\n" . $css . "\n" . '</style>' . "\n";
}

function nc_header_meta() {
    $canonical = nc_current_canonical();
    if (!empty($canonical)) {
        echo '<!-- NewsCenter SEO Meta -->' . "\n";
        echo '<link rel="canonical" href="' . htmlspecialchars((string)$canonical, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        echo '<meta property="og:url" content="' . htmlspecialchars((string)$canonical, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
    }
}

/**
 * Main Frontend Interceptor (hooked to 'index-pretemplate')
 */
function nc_frontend_handler() {
    global $data_index, $title, $metad;
    $db = nc_get_db();

    $settings = nc_get_settings();
    $target_slug = !empty($settings['page_slug']) ? $settings['page_slug'] : 'news';

    if (!isset($data_index->url) || (string)$data_index->url !== (string)$target_slug) {
        return;
    }

    $fancy = (defined('PRETTYURLS') && PRETTYURLS == 1) || (isset($GLOBALS['PRETTYURLS']) && $GLOBALS['PRETTYURLS'] == 1);
    $trailing_slash = (!isset($settings['trailing_slash']) || $settings['trailing_slash'] === '1');

    if (isset($_GET['post'])) {
        $post_slug = nc_sanitize_slug($_GET['post']);
        $stmt = $db->prepare("SELECT * FROM nc_articles WHERE slug = ? AND status = 'published' AND published_at <= CURRENT_TIMESTAMP LIMIT 1");
        $stmt->execute([$post_slug]);
        $article = $stmt->fetch();

        if ($article) {
            nc_current_article($article);

            $data_index->title = !empty($article['meta_title']) ? $article['meta_title'] : $article['title'];
            $title = $data_index->title;

            $desc_source = !empty($article['meta_description']) ? $article['meta_description'] : (!empty($article['excerpt']) ? $article['excerpt'] : $article['content']);
            $clean_desc = nc_clean_meta_description($desc_source, 160);

            $data_index->metad = $clean_desc;
            $metad = $clean_desc;

            $post_url = nc_build_url('post', $article['slug']);
            nc_current_canonical($post_url);

            if ($fancy) {
                $sub = $target_slug . '/post/' . $article['slug'];
                $data_index->url = $trailing_slash ? $sub . '/' : $sub;
            }
        }
    } elseif (isset($_GET['category']) || isset($_GET['nc_cat'])) {
        $cat_slug = nc_sanitize_slug($_GET['category'] ?? $_GET['nc_cat']);
        $stmt = $db->prepare("SELECT * FROM nc_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$cat_slug]);
        $cat = $stmt->fetch();
        if ($cat) {
            nc_current_category($cat);

            $data_index->title = $cat['name'] . ' - ' . $data_index->title;
            $title = $data_index->title;

            if (!empty($cat['description'])) {
                $clean_desc = nc_clean_meta_description($cat['description'], 160);
                $data_index->metad = $clean_desc;
                $metad = $clean_desc;
            }

            $cat_url = nc_build_url('category', $cat_slug);
            nc_current_canonical($cat_url);

            if ($fancy) {
                $sub = $target_slug . '/category/' . $cat_slug;
                $data_index->url = $trailing_slash ? $sub . '/' : $sub;
            }
        }
    } elseif (isset($_GET['tag'])) {
        $tag_slug = nc_sanitize_slug($_GET['tag']);
        $stmt = $db->prepare("SELECT name FROM nc_tags WHERE slug = ? LIMIT 1");
        $stmt->execute([$tag_slug]);
        $tag_name = $stmt->fetchColumn();
        if ($tag_name) {
            $data_index->title = '#' . $tag_name . ' - ' . $data_index->title;
            $title = $data_index->title;

            $tag_url = nc_build_url('tag', $tag_slug);
            nc_current_canonical($tag_url);

            if ($fancy) {
                $sub = $target_slug . '/tag/' . $tag_slug;
                $data_index->url = $trailing_slash ? $sub . '/' : $sub;
            }
        }
    } elseif (isset($_GET['author']) || isset($_GET['nc_author'])) {
        $author_slug = nc_sanitize_slug($_GET['author'] ?? $_GET['nc_author']);
        $stmt = $db->prepare("SELECT * FROM nc_authors WHERE slug = ? LIMIT 1");
        $stmt->execute([$author_slug]);
        $author = $stmt->fetch();
        if ($author) {
            nc_current_author($author);

            $data_index->title = 'Articles by ' . $author['name'] . ' - ' . $data_index->title;
            $title = $data_index->title;

            if (!empty($author['bio'])) {
                $clean_desc = nc_clean_meta_description($author['bio'], 160);
                $data_index->metad = $clean_desc;
                $metad = $clean_desc;
            }

            $author_url = nc_build_url('author', $author_slug);
            nc_current_canonical($author_url);

            if ($fancy) {
                $sub = $target_slug . '/author/' . $author_slug;
                $data_index->url = $trailing_slash ? $sub . '/' : $sub;
            }
        }
    } else {
        $page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $canonical_url = nc_build_pagination_url($page_num, 'all');
        nc_current_canonical($canonical_url);

        if ($fancy && $page_num > 1) {
            $sub = $target_slug . '/page/' . $page_num;
            $data_index->url = $trailing_slash ? $sub . '/' : $sub;
        }
    }

    $data_index->content = nc_dispatch_frontend();
}

/**
 * Shortcode Filter supporting both quoted and unquoted parameters
 */
function nc_content_shortcode_filter($content) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    $pattern_recent = '/\[get-newscenter-recent(?:\s+([^\]]*))?\]/i';
    $content = preg_replace_callback($pattern_recent, function($matches) {
        $limit = 3;
        $category = '';
        $author = '';

        if (!empty($matches[1])) {
            $attrs_str = $matches[1];
            if (preg_match('/limit=[\'"]?(\d+)[\'"]?/i', $attrs_str, $m)) {
                $limit = (int)$m[1];
            }
            if (preg_match('/category=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?/i', $attrs_str, $m)) {
                $category = nc_sanitize_slug($m[1]);
            }
            if (preg_match('/author=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?/i', $attrs_str, $m)) {
                $author = nc_sanitize_slug($m[1]);
            }
        }

        return nc_get_recent_posts_html($limit, $category, 'Latest News', $author);
    }, $content);

    $content = preg_replace_callback('/\[get-newscenter-authors\s*\]/i', function() {
        return nc_get_authors_html();
    }, $content);

    $content = preg_replace_callback('/\[get-newscenter-categories\s*\]/i', function() {
        return nc_get_categories_html();
    }, $content);

    return $content;
}

/**
 * Dispatch frontend views
 */
function nc_dispatch_frontend() {
    $css_tag = nc_get_frontend_css_tag();

    if (isset($_GET['post'])) {
        $post_slug = nc_sanitize_slug($_GET['post']);
        return $css_tag . nc_render_single_post($post_slug);
    }

    if (isset($_GET['category']) || isset($_GET['nc_cat'])) {
        $cat_slug = nc_sanitize_slug($_GET['category'] ?? $_GET['nc_cat']);
        return $css_tag . nc_render_archive_list('category', $cat_slug);
    }

    if (isset($_GET['tag'])) {
        $tag_slug = nc_sanitize_slug($_GET['tag']);
        return $css_tag . nc_render_archive_list('tag', $tag_slug);
    }

    if (isset($_GET['author']) || isset($_GET['nc_author'])) {
        $author_slug = nc_sanitize_slug($_GET['author'] ?? $_GET['nc_author']);
        return $css_tag . nc_render_archive_list('author', $author_slug);
    }

    if (isset($_GET['archive'])) {
        $archive_date = preg_replace('/[^0-9\-]/', '', $_GET['archive']);
        return $css_tag . nc_render_archive_list('archive', $archive_date);
    }

    if (isset($_GET['nc_query'])) {
        $query = trim($_GET['nc_query']);
        return $css_tag . nc_render_archive_list('search', $query);
    }

    return $css_tag . nc_render_archive_list('all');
}

/**
 * Helper to get authors assigned to an article with individual SEO links and avatar
 */
function nc_get_article_authors_data($article_id, $default_author_string = 'Admin') {
    $db = nc_get_db();

    $stmt = $db->prepare("
        SELECT au.name, au.slug, au.image 
        FROM nc_authors au
        JOIN nc_article_authors aa ON au.id = aa.author_id
        WHERE aa.article_id = ?
        ORDER BY au.name ASC
    ");
    $stmt->execute([(int)$article_id]);
    $authors = $stmt->fetchAll();

    $names = [];
    $links = [];
    $first_url = nc_build_url();
    $first_img = '';

    if (!empty($authors)) {
        foreach ($authors as $i => $a) {
            $name_esc = htmlspecialchars((string)$a['name'], ENT_QUOTES, 'UTF-8');
            $names[] = $name_esc;
            $url = nc_build_url('author', $a['slug']);
            if ($i === 0) {
                $first_url = $url;
                $first_img = !empty($a['image']) ? $a['image'] : '';
            }
            $links[] = '<a href="' . htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8') . '" class="nc-author-link">' . $name_esc . '</a>';
        }
    } else {
        $fallback_name = !empty($default_author_string) ? htmlspecialchars((string)$default_author_string, ENT_QUOTES, 'UTF-8') : 'Admin';
        $names[] = $fallback_name;
        $fallback_slug = nc_sanitize_slug($fallback_name);
        $first_url = nc_build_url('author', $fallback_slug);
        $links[] = '<a href="' . htmlspecialchars((string)$first_url, ENT_QUOTES, 'UTF-8') . '" class="nc-author-link">' . $fallback_name . '</a>';
    }

    return [
        'author_name'  => implode(', ', $names),
        'author_url'   => $first_url,
        'authors'      => implode(', ', $links),
        'author_image' => $first_img
    ];
}

/**
 * Render single article with Local GLightbox bottom gallery
 */
function nc_render_single_post($slug) {
    $db = nc_get_db();
    $stmt = $db->prepare("SELECT * FROM nc_articles WHERE slug = ? AND status = 'published' AND published_at <= CURRENT_TIMESTAMP LIMIT 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();

    if (!$article) {
        return '<div class="nc-frontend-error">' . i18n_r('NewsCenter/ARTICLE_NOT_FOUND') . '</div>';
    }

    $settings = nc_get_settings();
    $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';
    $news_url = nc_build_url();

    $cat_stmt = $db->prepare("
        SELECT c.name, c.slug, c.image FROM nc_categories c
        JOIN nc_article_categories ac ON c.id = ac.category_id
        WHERE ac.article_id = ?
        ORDER BY c.name ASC
    ");
    $cat_stmt->execute([(int)$article['id']]);
    $cats = $cat_stmt->fetchAll();
    $cat_links = [];
    foreach ($cats as $c) {
        $cat_links[] = '<a href="' . htmlspecialchars((string)nc_build_url('category', $c['slug']), ENT_QUOTES, 'UTF-8') . '" class="nc-category-link">' . htmlspecialchars((string)$c['name'], ENT_QUOTES, 'UTF-8') . '</a>';
    }

    $tag_stmt = $db->prepare("
        SELECT t.name, t.slug FROM nc_tags t
        JOIN nc_article_tags at ON t.id = at.tag_id
        WHERE at.article_id = ?
        ORDER BY t.name ASC
    ");
    $tag_stmt->execute([(int)$article['id']]);
    $tags = $tag_stmt->fetchAll();
    $tag_links = [];
    foreach ($tags as $t) {
        $tag_links[] = '<a href="' . htmlspecialchars((string)nc_build_url('tag', $t['slug']), ENT_QUOTES, 'UTF-8') . '" class="nc-tag-link">#' . htmlspecialchars((string)$t['name'], ENT_QUOTES, 'UTF-8') . '</a>';
    }

    $author_meta = nc_get_article_authors_data($article['id'], $article['author']);

    $gallery_html = '';
    $gallery_items = !empty($article['gallery']) ? json_decode($article['gallery'], true) : [];

    if (!empty($gallery_items) && is_array($gallery_items)) {
        $gallery_html .= "\n" . '<!-- NewsCenter Local GLightbox -->' . "\n";
        $gallery_html .= '<link rel="stylesheet" href="' . NC_URL . 'assets/vendor/glightbox/glightbox.min.css">' . "\n";
        $gallery_html .= '<div class="nc-article-gallery">' . "\n";
        foreach ($gallery_items as $item) {
            $img_url  = htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8');
            $thumb    = htmlspecialchars((string)(!empty($item['thumb']) ? $item['thumb'] : $item['url']), ENT_QUOTES, 'UTF-8');
            $caption  = !empty($item['caption']) ? htmlspecialchars((string)$item['caption'], ENT_QUOTES, 'UTF-8') : '';
            $alt_text = !empty($caption) ? $caption : htmlspecialchars((string)($item['name'] ?? $article['title']), ENT_QUOTES, 'UTF-8');

            $gallery_html .= '  <figure class="nc-gallery-item">' . "\n";
            $gallery_html .= '    <a href="' . $img_url . '" class="glightbox" data-gallery="nc-gallery-' . (int)$article['id'] . '" data-title="' . $caption . '">' . "\n";
            $gallery_html .= '      <img src="' . $thumb . '" alt="' . $alt_text . '" loading="lazy">' . "\n";
            $gallery_html .= '    </a>' . "\n";
            if (!empty($caption)) {
                $gallery_html .= '    <figcaption class="nc-gallery-caption">' . $caption . '</figcaption>' . "\n";
            }
            $gallery_html .= '  </figure>' . "\n";
        }
        $gallery_html .= '</div>' . "\n";

        $gallery_html .= '<script src="' . NC_URL . 'assets/vendor/glightbox/glightbox.min.js"></script>' . "\n";
        $gallery_html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof GLightbox !== "undefined") {
                GLightbox({ selector: ".glightbox", loop: true });
            }
        });
        </script>' . "\n";
    }

    $t_stmt = $db->prepare("SELECT content FROM nc_templates WHERE template_key = 'single' LIMIT 1");
    $t_stmt->execute();
    $template = $t_stmt->fetchColumn();

    $content_with_gallery = nc_sanitize_html($article['content']) . $gallery_html;
    $post_url = nc_build_url('post', $article['slug']);
    $thumb_url = !empty($article['featured_image']) ? nc_get_article_thumb_url($article['featured_image']) : '';
    $share_widget = nc_render_share_widget($post_url, $article['title']);

    $vars = [
        'article.title'                => htmlspecialchars((string)$article['title'], ENT_QUOTES, 'UTF-8'),
        'article.slug'                 => htmlspecialchars((string)$article['slug'], ENT_QUOTES, 'UTF-8'),
        'article.url'                  => htmlspecialchars((string)$post_url, ENT_QUOTES, 'UTF-8'),
        'article.content'              => $content_with_gallery,
        'article.excerpt'              => htmlspecialchars((string)$article['excerpt'], ENT_QUOTES, 'UTF-8'),
        'article.date'                 => nc_date($date_format, strtotime($article['published_at'])),
        'article.date_raw'             => $article['published_at'],
        'article.author'               => $author_meta['author_name'],
        'article.author_url'           => htmlspecialchars((string)$author_meta['author_url'], ENT_QUOTES, 'UTF-8'),
        'article.authors'              => $author_meta['authors'],
        'article.author_image'         => htmlspecialchars((string)$author_meta['author_image'], ENT_QUOTES, 'UTF-8'),
        'article.has_author_image'     => !empty($author_meta['author_image']),
        'article.featured_image'       => htmlspecialchars((string)$article['featured_image'], ENT_QUOTES, 'UTF-8'),
        'article.featured_image_thumb' => htmlspecialchars((string)$thumb_url, ENT_QUOTES, 'UTF-8'),
        'article.has_image'            => !empty($article['featured_image']),
        'article.share'                => $share_widget,
        'article.categories'           => implode(', ', $cat_links),
        'article.tags'                 => implode(' ', $tag_links),
        'news.url'                     => $news_url
    ];

    return nc_render_template($template, $vars);
}

/**
 * Render archive / list view with dedicated category, author, and list_posts container templates
 */
function nc_render_archive_list($filter_type = 'all', $filter_val = '') {
    $db = nc_get_db();
    $settings = nc_get_settings();

    $limit = !empty($settings['posts_per_page']) ? (int)$settings['posts_per_page'] : 10;
    $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';
    $excerpt_len = !empty($settings['excerpt_length']) ? (int)$settings['excerpt_length'] : 250;
    $news_url = nc_build_url();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    $where = "WHERE a.status = 'published' AND a.published_at <= CURRENT_TIMESTAMP";
    $joins = "";
    $params = [];

    if ($filter_type === 'category') {
        $joins .= " JOIN nc_article_categories ac ON a.id = ac.article_id JOIN nc_categories c ON c.id = ac.category_id";
        $where .= " AND c.slug = :fval";
        $params[':fval'] = $filter_val;
    } elseif ($filter_type === 'tag') {
        $joins .= " JOIN nc_article_tags at ON a.id = at.article_id JOIN nc_tags t ON t.id = at.tag_id";
        $where .= " AND t.slug = :fval";
        $params[':fval'] = $filter_val;
    } elseif ($filter_type === 'author') {
        $joins .= " JOIN nc_article_authors aa ON a.id = aa.article_id JOIN nc_authors au ON au.id = aa.author_id";
        $where .= " AND au.slug = :fval";
        $params[':fval'] = $filter_val;
    } elseif ($filter_type === 'archive') {
        $where .= " AND strftime('%Y-%m', a.published_at) = :fval";
        $params[':fval'] = $filter_val;
    } elseif ($filter_type === 'search') {
        $where .= " AND (a.title LIKE :fval OR a.content LIKE :fval OR a.excerpt LIKE :fval)";
        $params[':fval'] = '%' . $filter_val . '%';
    }

    $count_sql = "SELECT COUNT(DISTINCT a.id) FROM nc_articles a {$joins} {$where}";
    $c_stmt = $db->prepare($count_sql);
    $c_stmt->execute($params);
    $total_items = (int)$c_stmt->fetchColumn();
    $total_pages = (int)ceil($total_items / $limit);

    $sql = "SELECT DISTINCT a.* FROM nc_articles a {$joins} {$where} ORDER BY a.published_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();

    $t_stmt = $db->prepare("SELECT content FROM nc_templates WHERE template_key = 'list_item' LIMIT 1");
    $t_stmt->execute();
    $item_template = $t_stmt->fetchColumn();

    $rendered_items = '';
    $pagination_html = '';

    if (!empty($articles)) {
        foreach ($articles as $art) {
            $excerpt = !empty($art['excerpt']) ? $art['excerpt'] : substr(strip_tags((string)$art['content']), 0, $excerpt_len) . '...';
            $author_meta = nc_get_article_authors_data($art['id'], $art['author']);
            $thumb_url = !empty($art['featured_image']) ? nc_get_article_thumb_url($art['featured_image']) : '';
            $art_url = nc_build_url('post', $art['slug']);
            $art_share = nc_render_share_widget($art_url, $art['title']);

            $vars = [
                'article.title'                => htmlspecialchars((string)$art['title'], ENT_QUOTES, 'UTF-8'),
                'article.url'                  => htmlspecialchars((string)$art_url, ENT_QUOTES, 'UTF-8'),
                'article.excerpt'              => htmlspecialchars((string)$excerpt, ENT_QUOTES, 'UTF-8'),
                'article.date'                 => nc_date($date_format, strtotime($art['published_at'])),
                'article.author'               => $author_meta['author_name'],
                'article.author_url'           => htmlspecialchars((string)$author_meta['author_url'], ENT_QUOTES, 'UTF-8'),
                'article.authors'              => $author_meta['authors'],
                'article.author_image'         => htmlspecialchars((string)$author_meta['author_image'], ENT_QUOTES, 'UTF-8'),
                'article.has_author_image'     => !empty($author_meta['author_image']),
                'article.featured_image'       => htmlspecialchars((string)$art['featured_image'], ENT_QUOTES, 'UTF-8'),
                'article.featured_image_thumb' => htmlspecialchars((string)$thumb_url, ENT_QUOTES, 'UTF-8'),
                'article.has_image'            => !empty($art['featured_image']),
                'article.share'                => $art_share,
                'news.url'                     => $news_url
            ];
            $rendered_items .= nc_render_template($item_template, $vars);
        }

        if ($total_pages > 1) {
            $pagination_html .= '<nav class="nc-frontend-pagination" aria-label="Page navigation">';

            // Previous Button
            if ($page > 1) {
                $prev_url = nc_build_pagination_url($page - 1, $filter_type, $filter_val);
                $pagination_html .= '<a href="' . htmlspecialchars((string)$prev_url, ENT_QUOTES, 'UTF-8') . '" class="nc-page-btn nc-page-prev" rel="prev">«</a> ';
            } else {
                $pagination_html .= '<span class="nc-page-btn nc-page-disabled">«</span> ';
            }

            // Numbered Boxed Pages
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i === $page) {
                    $pagination_html .= '<span class="nc-page-btn active" aria-current="page">' . $i . '</span> ';
                } else {
                    $link = nc_build_pagination_url($i, $filter_type, $filter_val);
                    $pagination_html .= '<a href="' . htmlspecialchars((string)$link, ENT_QUOTES, 'UTF-8') . '" class="nc-page-btn">' . $i . '</a> ';
                }
            }

            // Next Button
            if ($page < $total_pages) {
                $next_url = nc_build_pagination_url($page + 1, $filter_type, $filter_val);
                $pagination_html .= '<a href="' . htmlspecialchars((string)$next_url, ENT_QUOTES, 'UTF-8') . '" class="nc-page-btn nc-page-next" rel="next">»</a>';
            } else {
                $pagination_html .= '<span class="nc-page-btn nc-page-disabled">»</span>';
            }

            $pagination_html .= '</nav>';
        }
    } else {
        $rendered_items .= '<p class="nc-no-results">' . i18n_r('NewsCenter/NO_ARTICLES_FOUND') . '</p>';
    }

    $rendered_cards = $rendered_items . $pagination_html;

    if ($filter_type === 'category') {
        $cat = nc_current_category();
        if (!$cat) {
            $cat_stmt = $db->prepare("SELECT * FROM nc_categories WHERE slug = ? LIMIT 1");
            $cat_stmt->execute([$filter_val]);
            $cat = $cat_stmt->fetch();
        }

        $c_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'category_archive' LIMIT 1")->fetchColumn();
        if (!$c_tpl) {
            nc_reset_default_templates();
            $c_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'category_archive' LIMIT 1")->fetchColumn();
        }

        $cat_desc = !empty($cat['description']) ? trim((string)$cat['description']) : '';
        $cat_img  = !empty($cat['image']) ? trim((string)$cat['image']) : '';
        $cat_url  = nc_build_url('category', $filter_val);
        $cat_share = nc_render_share_widget($cat_url, $cat['name'] ?? $filter_val);

        $c_vars = [
            'category.name'            => htmlspecialchars((string)($cat['name'] ?? $filter_val), ENT_QUOTES, 'UTF-8'),
            'category.slug'            => htmlspecialchars((string)$filter_val, ENT_QUOTES, 'UTF-8'),
            'category.description'     => nl2br(htmlspecialchars((string)$cat_desc, ENT_QUOTES, 'UTF-8')),
            'category.has_description' => !empty($cat_desc),
            'category.image'           => htmlspecialchars((string)$cat_img, ENT_QUOTES, 'UTF-8'),
            'category.has_image'       => !empty($cat_img),
            'category.url'             => htmlspecialchars((string)$cat_url, ENT_QUOTES, 'UTF-8'),
            'category.share'           => $cat_share,
            'articles.list'            => $rendered_cards,
            'news.url'                 => $news_url
        ];
        return nc_render_template($c_tpl, $c_vars);
    }

    if ($filter_type === 'author') {
        $author = nc_current_author();
        if (!$author) {
            $au_stmt = $db->prepare("SELECT * FROM nc_authors WHERE slug = ? LIMIT 1");
            $au_stmt->execute([$filter_val]);
            $author = $au_stmt->fetch();
        }

        $a_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'author_archive' LIMIT 1")->fetchColumn();
        if (!$a_tpl) {
            nc_reset_default_templates();
            $a_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'author_archive' LIMIT 1")->fetchColumn();
        }

        $author_bio = !empty($author['bio']) ? trim((string)$author['bio']) : '';
        $author_img = !empty($author['image']) ? trim((string)$author['image']) : '';
        $author_url = nc_build_url('author', $filter_val);
        $author_share = nc_render_share_widget($author_url, 'Articles by ' . ($author['name'] ?? $filter_val));

        $a_vars = [
            'author.name'      => htmlspecialchars((string)($author['name'] ?? $filter_val), ENT_QUOTES, 'UTF-8'),
            'author.slug'      => htmlspecialchars((string)$filter_val, ENT_QUOTES, 'UTF-8'),
            'author.bio'       => nl2br(htmlspecialchars((string)$author_bio, ENT_QUOTES, 'UTF-8')),
            'author.has_bio'   => !empty($author_bio),
            'author.image'     => htmlspecialchars((string)$author_img, ENT_QUOTES, 'UTF-8'),
            'author.has_image' => !empty($author_img),
            'author.url'       => htmlspecialchars((string)$author_url, ENT_QUOTES, 'UTF-8'),
            'author.share'     => $author_share,
            'articles.list'    => $rendered_cards,
            'news.url'         => $news_url
        ];
        return nc_render_template($a_tpl, $a_vars);
    }

    // Main feed, search, tag, and monthly archive rendering via list_posts container
    $heading_html = '';
    if ($filter_type === 'search') {
        $heading_html = '<div class="nc-filter-heading"><h3>' . i18n_r('NewsCenter/SEARCH_RESULTS_FOR') . ' <em>"' . htmlspecialchars((string)$filter_val, ENT_QUOTES, 'UTF-8') . '"</em></h3></div>';
    } elseif ($filter_type === 'tag' || $filter_type === 'archive') {
        $heading_html = '<div class="nc-filter-heading"><h3>' . ucfirst($filter_type) . ': <em>' . htmlspecialchars((string)$filter_val, ENT_QUOTES, 'UTF-8') . '</em></h3></div>';
    }

    $list_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'list_posts' LIMIT 1")->fetchColumn();
    if (!$list_tpl) {
        nc_reset_default_templates();
        $list_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'list_posts' LIMIT 1")->fetchColumn();
    }

    $list_vars = [
        'list.items'       => $rendered_items,
        'posts.pagination' => $pagination_html,
        'news.url'         => $news_url
    ];

    return $heading_html . nc_render_template($list_tpl, $list_vars);
}

/**
 * Return formatted HTML of latest posts for Home Page integration
 */
function nc_get_recent_posts_html($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $news_url = nc_build_url();
    $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'M j, Y';
    $excerpt_len = !empty($settings['excerpt_length']) ? (int)$settings['excerpt_length'] : 140;

    $limit = max(1, (int)$limit);
    $joins = "";
    $where = "WHERE a.status = 'published' AND a.published_at <= CURRENT_TIMESTAMP";
    $params = [':limit' => $limit];

    if (!empty($category_slug)) {
        $joins .= " JOIN nc_article_categories ac ON a.id = ac.article_id JOIN nc_categories c ON c.id = ac.category_id";
        $where .= " AND c.slug = :catslug";
        $params[':catslug'] = $category_slug;
    }

    if (!empty($author_slug)) {
        $joins .= " JOIN nc_article_authors aa ON a.id = aa.article_id JOIN nc_authors au ON au.id = aa.author_id";
        $where .= " AND au.slug = :authslug";
        $params[':authslug'] = $author_slug;
    }

    $sql = "SELECT DISTINCT a.* FROM nc_articles a {$joins} {$where} ORDER BY a.published_at DESC LIMIT :limit";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $articles = $stmt->fetchAll();

    if (empty($articles)) {
        return '';
    }

    $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'home_posts' LIMIT 1")->fetchColumn();
    $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'home_item' LIMIT 1")->fetchColumn();

    if (!$container_tpl || !$item_tpl) {
        nc_reset_default_templates();
        $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'home_posts' LIMIT 1")->fetchColumn();
        $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'home_item' LIMIT 1")->fetchColumn();
    }

    $rendered_items = '';
    foreach ($articles as $art) {
        $excerpt = !empty($art['excerpt']) ? $art['excerpt'] : substr(strip_tags((string)$art['content']), 0, $excerpt_len) . '...';
        $author_meta = nc_get_article_authors_data($art['id'], $art['author']);
        $thumb_url = !empty($art['featured_image']) ? nc_get_article_thumb_url($art['featured_image']) : '';
        $art_url = nc_build_url('post', $art['slug']);
        $art_share = nc_render_share_widget($art_url, $art['title']);

        $vars = [
            'article.title'                => htmlspecialchars((string)$art['title'], ENT_QUOTES, 'UTF-8'),
            'article.url'                  => htmlspecialchars((string)$art_url, ENT_QUOTES, 'UTF-8'),
            'article.excerpt'              => htmlspecialchars((string)$excerpt, ENT_QUOTES, 'UTF-8'),
            'article.date'                 => nc_date($date_format, strtotime($art['published_at'])),
            'article.author'               => $author_meta['author_name'],
            'article.author_url'           => htmlspecialchars((string)$author_meta['author_url'], ENT_QUOTES, 'UTF-8'),
            'article.authors'              => $author_meta['authors'],
            'article.author_image'         => htmlspecialchars((string)$author_meta['author_image'], ENT_QUOTES, 'UTF-8'),
            'article.has_author_image'     => !empty($author_meta['author_image']),
            'article.featured_image'       => htmlspecialchars((string)$art['featured_image'], ENT_QUOTES, 'UTF-8'),
            'article.featured_image_thumb' => htmlspecialchars((string)$thumb_url, ENT_QUOTES, 'UTF-8'),
            'article.has_image'            => !empty($art['featured_image']),
            'article.share'                => $art_share,
            'news.url'                     => $news_url
        ];
        $rendered_items .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $container_vars = [
        'section.title' => htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8'),
        'posts.items'   => $rendered_items,
        'news.url'      => $news_url
    ];

    $html = nc_render_template($container_tpl, $container_vars);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * Return formatted HTML of all authors with bios, avatars, and article links
 */
function nc_get_authors_html() {
    $authors = nc_get_authors_with_counts();
    if (empty($authors)) {
        return '';
    }

    $db = nc_get_db();
    $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'authors_list' LIMIT 1")->fetchColumn();
    $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'author_item' LIMIT 1")->fetchColumn();

    if (!$container_tpl || !$item_tpl) {
        nc_reset_default_templates();
        $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'authors_list' LIMIT 1")->fetchColumn();
        $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'author_item' LIMIT 1")->fetchColumn();
    }

    $items_html = '';
    foreach ($authors as $au) {
        $url = nc_build_url('author', $au['slug']);
        $bio = !empty($au['bio']) ? trim((string)$au['bio']) : '';
        $image = !empty($au['image']) ? trim((string)$au['image']) : '';
        $au_share = nc_render_share_widget($url, 'Articles by ' . $au['name']);

        $vars = [
            'author.name'      => htmlspecialchars((string)$au['name'], ENT_QUOTES, 'UTF-8'),
            'author.slug'      => htmlspecialchars((string)$au['slug'], ENT_QUOTES, 'UTF-8'),
            'author.bio'       => nl2br(htmlspecialchars((string)$bio, ENT_QUOTES, 'UTF-8')),
            'author.has_bio'   => !empty($bio),
            'author.image'     => htmlspecialchars((string)$image, ENT_QUOTES, 'UTF-8'),
            'author.has_image' => !empty($image),
            'author.url'       => htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8'),
            'author.share'     => $au_share,
            'author.count'     => (int)$au['article_count']
        ];
        $items_html .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $html = nc_render_template($container_tpl, ['authors.items' => $items_html]);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * Return formatted HTML of all categories with images, descriptions, and article links
 */
function nc_get_categories_html() {
    $categories = nc_get_categories_with_counts();
    if (empty($categories)) {
        return '';
    }

    $db = nc_get_db();
    $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'categories_list' LIMIT 1")->fetchColumn();
    $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'category_item' LIMIT 1")->fetchColumn();

    if (!$container_tpl || !$item_tpl) {
        nc_reset_default_templates();
        $container_tpl = $db->query("SELECT content FROM nc_templates WHERE template_key = 'categories_list' LIMIT 1")->fetchColumn();
        $item_tpl      = $db->query("SELECT content FROM nc_templates WHERE template_key = 'category_item' LIMIT 1")->fetchColumn();
    }

    $items_html = '';
    foreach ($categories as $cat) {
        $url = nc_build_url('category', $cat['slug']);
        $desc = !empty($cat['description']) ? trim((string)$cat['description']) : '';
        $image = !empty($cat['image']) ? trim((string)$cat['image']) : '';
        $cat_share = nc_render_share_widget($url, $cat['name']);

        $vars = [
            'category.name'            => htmlspecialchars((string)$cat['name'], ENT_QUOTES, 'UTF-8'),
            'category.slug'            => htmlspecialchars((string)$cat['slug'], ENT_QUOTES, 'UTF-8'),
            'category.description'     => nl2br(htmlspecialchars((string)$desc, ENT_QUOTES, 'UTF-8')),
            'category.has_description' => !empty($desc),
            'category.image'           => htmlspecialchars((string)$image, ENT_QUOTES, 'UTF-8'),
            'category.has_image'       => !empty($image),
            'category.url'             => htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8'),
            'category.share'           => $cat_share,
            'category.count'           => (int)$cat['article_count']
        ];
        $items_html .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $html = nc_render_template($container_tpl, ['categories.items' => $items_html]);
    return nc_get_frontend_css_tag() . $html;
}

function nc_show_recent_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

function nc_home_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

function nc_show_authors() {
    echo nc_get_authors_html();
}

function nc_show_categories() {
    echo nc_get_categories_html();
}

function nc_show_recent_posts_list($limit = 5) {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';

    $stmt = $db->prepare("
        SELECT slug, title, published_at 
        FROM nc_articles 
        WHERE status = 'published' AND published_at <= CURRENT_TIMESTAMP 
        ORDER BY published_at DESC 
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();

    if (empty($articles)) return;

    echo '<ul class="nc-recent-posts-list">';
    foreach ($articles as $art) {
        $link = nc_build_url('post', $art['slug']);
        echo '<li>';
        echo '<a href="' . htmlspecialchars((string)$link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$art['title'], ENT_QUOTES, 'UTF-8') . '</a> ';
        echo '<span class="nc-recent-date">(' . nc_date($date_format, strtotime($art['published_at'])) . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}

function nc_show_archives() {
    $db = nc_get_db();

    $stmt = $db->query("
        SELECT strftime('%Y-%m', published_at) as archive_month, COUNT(id) as post_count
        FROM nc_articles
        WHERE status = 'published' AND published_at <= CURRENT_TIMESTAMP
        GROUP BY archive_month
        ORDER BY archive_month DESC
    ");
    $archives = $stmt->fetchAll();

    if (empty($archives)) return;

    echo '<ul class="nc-archives-list">';
    foreach ($archives as $arch) {
        $time = strtotime($arch['archive_month'] . '-01');
        $label = nc_date('F Y', $time);
        $link = nc_build_url('archive', $arch['archive_month']);
        echo '<li>';
        echo '<a href="' . htmlspecialchars((string)$link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</a> ';
        echo '<span class="nc-archive-count">(' . (int)$arch['post_count'] . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}