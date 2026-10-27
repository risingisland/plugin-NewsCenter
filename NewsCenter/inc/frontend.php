<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * NewsCenter Frontend Engine
 */

add_action('index-pretemplate', 'nc_frontend_handler');
add_filter('content', 'nc_content_shortcode_filter');
add_action('theme-header', 'nc_inject_frontend_css');

/**
 * Clean URL Generator supporting both Fancy (pretty) URLs and standard query parameters,
 * with optional trailing slash control.
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

/**
 * Generates the complete sitemap.xml file containing both native CMS pages
 * and NewsCenter published articles, saving directly to root directory.
 */
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
                $sitemap_xml .= '    <loc>' . htmlspecialchars($purl, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;

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
            $sitemap_xml .= '    <loc>' . htmlspecialchars($art_url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;
            $sitemap_xml .= '    <lastmod>' . date('Y-m-d', $mod_time) . '</lastmod>' . PHP_EOL;
            $sitemap_xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $sitemap_xml .= '    <priority>' . htmlspecialchars($priority_art, ENT_QUOTES, 'UTF-8') . '</priority>' . PHP_EOL;
            $sitemap_xml .= '  </url>' . PHP_EOL;
        }
    }

    $sitemap_xml .= '</urlset>' . PHP_EOL;

    @file_put_contents($sitemap_file, $sitemap_xml);
    return true;
}

/**
 * Sanitize plain text strings for meta description attributes.
 */
function nc_clean_meta_description($text, $limit = 160) {
    if (empty($text)) return '';
    $clean = strip_tags((string)$text);
    $clean = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $clean);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);
    if (mb_strlen($clean, 'UTF-8') > $limit) {
        $clean = mb_substr($clean, 0, $limit - 3, 'UTF-8') . '...';
    }
    return htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Stores active canonical URL for current request
 */
function nc_current_canonical($set = null) {
    static $canonical = null;
    if ($set !== null) {
        $canonical = $set;
    }
    return $canonical;
}

/**
 * Injects user-editable Frontend CSS from SQLite into the theme <head>
 */
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

/**
 * Helper to ensure dynamic CSS is printed even if theme lacks get_header() hook
 */
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

/**
 * Output Canonical & OpenGraph head metadata
 */
function nc_header_meta() {
    $canonical = nc_current_canonical();
    if (!empty($canonical)) {
        echo '<!-- NewsCenter SEO Meta -->' . "\n";
        echo '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
        echo '<meta property="og:url" content="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . '" />' . "\n";
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
            $data_index->title = !empty($article['meta_title']) ? $article['meta_title'] : $article['title'];
            $title = $data_index->title;

            $desc_source = !empty($article['meta_description']) ? $article['meta_description'] : (!empty($article['excerpt']) ? $article['excerpt'] : $article['content']);
            $clean_desc = nc_clean_meta_description($desc_source, 160);

            $data_index->metad = $clean_desc;
            $metad = $clean_desc;

            // Set exact canonical URL for post
            $post_url = nc_build_url('post', $article['slug']);
            nc_current_canonical($post_url);

            if ($fancy) {
                $sub = $target_slug . '/post/' . $article['slug'];
                $data_index->url = $trailing_slash ? $sub . '/' : $sub;
            }
        }
    } elseif (isset($_GET['category']) || isset($_GET['nc_cat'])) {
        $cat_slug = nc_sanitize_slug($_GET['category'] ?? $_GET['nc_cat']);
        $stmt = $db->prepare("SELECT name, description FROM nc_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$cat_slug]);
        $cat = $stmt->fetch();
        if ($cat) {
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
        $stmt = $db->prepare("SELECT name, bio FROM nc_authors WHERE slug = ? LIMIT 1");
        $stmt->execute([$author_slug]);
        $author = $stmt->fetch();
        if ($author) {
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
        $base_url = nc_build_url();
        nc_current_canonical($base_url);
    }

    $data_index->content = nc_dispatch_frontend();
}

/**
 * Shortcode Filter: supports recent posts, authors directory, and categories directory
 */
function nc_content_shortcode_filter($content) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    $pattern_recent = '/\[get-newscenter-recent(?:\s+limit=[\'"]?(\d+)[\'"]?)?(?:\s+category=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?)?(?:\s+author=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?)?\s*\]/i';
    $content = preg_replace_callback($pattern_recent, function($matches) {
        $limit = !empty($matches[1]) ? (int)$matches[1] : 3;
        $category = !empty($matches[2]) ? nc_sanitize_slug($matches[2]) : '';
        $author = !empty($matches[3]) ? nc_sanitize_slug($matches[3]) : '';
        return nc_get_recent_posts_html($limit, $category, 'Latest News', $author);
    }, $content);

    $pattern_authors = '/\[get-newscenter-authors\s*\]/i';
    $content = preg_replace_callback($pattern_authors, function() {
        return nc_get_authors_html();
    }, $content);

    $pattern_categories = '/\[get-newscenter-categories\s*\]/i';
    $content = preg_replace_callback($pattern_categories, function() {
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
 * Helper to get authors assigned to an article with SEO links
 */
function nc_get_article_authors_data($article_id, $default_author_string = 'Admin') {
    $db = nc_get_db();

    $stmt = $db->prepare("
        SELECT au.name, au.slug 
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

    if (!empty($authors)) {
        foreach ($authors as $i => $a) {
            $names[] = htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8');
            $url = nc_build_url('author', $a['slug']);
            if ($i === 0) {
                $first_url = $url;
            }
            $links[] = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="nc-author-link">' . htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
    } else {
        $fallback_name = !empty($default_author_string) ? htmlspecialchars($default_author_string, ENT_QUOTES, 'UTF-8') : 'Admin';
        $names[] = $fallback_name;
        $fallback_slug = nc_sanitize_slug($fallback_name);
        $first_url = nc_build_url('author', $fallback_slug);
        $links[] = '<a href="' . htmlspecialchars($first_url, ENT_QUOTES, 'UTF-8') . '" class="nc-author-link">' . $fallback_name . '</a>';
    }

    return [
        'author_name' => implode(', ', $names),
        'author_url'  => $first_url,
        'authors'     => implode(', ', $links)
    ];
}

/**
 * Render single article with GLightbox bottom gallery
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
        SELECT c.name, c.slug FROM nc_categories c
        JOIN nc_article_categories ac ON c.id = ac.category_id
        WHERE ac.article_id = ?
        ORDER BY c.name ASC
    ");
    $cat_stmt->execute([(int)$article['id']]);
    $cats = $cat_stmt->fetchAll();
    $cat_links = [];
    foreach ($cats as $c) {
        $cat_links[] = '<a href="' . htmlspecialchars(nc_build_url('category', $c['slug']), ENT_QUOTES, 'UTF-8') . '" class="nc-category-link">' . htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') . '</a>';
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
        $tag_links[] = '<a href="' . htmlspecialchars(nc_build_url('tag', $t['slug']), ENT_QUOTES, 'UTF-8') . '" class="nc-tag-link">#' . htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8') . '</a>';
    }

    $author_meta = nc_get_article_authors_data($article['id'], $article['author']);

    $gallery_html = '';
    $gallery_items = !empty($article['gallery']) ? json_decode($article['gallery'], true) : [];

    if (!empty($gallery_items) && is_array($gallery_items)) {
        $gallery_html .= "\n" . '<!-- NewsCenter GLightbox Gallery -->' . "\n";
        $gallery_html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">' . "\n";
        $gallery_html .= '<div class="nc-article-gallery">' . "\n";
        foreach ($gallery_items as $item) {
            $img_url  = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
            $thumb    = htmlspecialchars(!empty($item['thumb']) ? $item['thumb'] : $item['url'], ENT_QUOTES, 'UTF-8');
            $caption  = !empty($item['caption']) ? htmlspecialchars($item['caption']) : '';
            $alt_text = !empty($caption) ? $caption : htmlspecialchars($item['name'] ?? $article['title'], ENT_QUOTES, 'UTF-8');

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

        $gallery_html .= '<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>' . "\n";
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

    $vars = [
        'article.title'          => htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'),
        'article.slug'           => htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8'),
        'article.content'        => $content_with_gallery,
        'article.excerpt'        => htmlspecialchars($article['excerpt'], ENT_QUOTES, 'UTF-8'),
        'article.date'           => date($date_format, strtotime($article['published_at'])),
        'article.date_raw'       => $article['published_at'],
        'article.author'         => $author_meta['author_name'],
        'article.author_url'     => htmlspecialchars($author_meta['author_url'], ENT_QUOTES, 'UTF-8'),
        'article.authors'        => $author_meta['authors'],
        'article.featured_image' => htmlspecialchars($article['featured_image'], ENT_QUOTES, 'UTF-8'),
        'article.has_image'      => !empty($article['featured_image']),
        'article.categories'     => implode(', ', $cat_links),
        'article.tags'           => implode(' ', $tag_links),
        'news.url'               => $news_url
    ];

    return nc_render_template($template, $vars);
}

/**
 * Render archive / list view
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
    $total_pages = ceil($total_items / $limit);

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

    $output = '<div class="nc-news-feed">';

    if ($filter_type === 'search') {
        $output .= '<div class="nc-filter-heading"><h3>' . i18n_r('NewsCenter/SEARCH_RESULTS_FOR') . ' <em>"' . htmlspecialchars($filter_val, ENT_QUOTES, 'UTF-8') . '"</em></h3></div>';
    } elseif ($filter_type === 'author') {
        $stmt_au = $db->prepare("SELECT name FROM nc_authors WHERE slug = ? LIMIT 1");
        $stmt_au->execute([$filter_val]);
        $author_name = $stmt_au->fetchColumn() ?: $filter_val;
        $output .= '<div class="nc-filter-heading"><h3>Articles by <em>' . htmlspecialchars($author_name, ENT_QUOTES, 'UTF-8') . '</em></h3></div>';
    } elseif ($filter_type === 'category' || $filter_type === 'tag' || $filter_type === 'archive') {
        $output .= '<div class="nc-filter-heading"><h3>' . ucfirst($filter_type) . ': <em>' . htmlspecialchars($filter_val, ENT_QUOTES, 'UTF-8') . '</em></h3></div>';
    }

    if (!empty($articles)) {
        foreach ($articles as $art) {
            $excerpt = !empty($art['excerpt']) ? $art['excerpt'] : substr(strip_tags($art['content']), 0, $excerpt_len) . '...';
            $author_meta = nc_get_article_authors_data($art['id'], $art['author']);

            $vars = [
                'article.title'          => htmlspecialchars($art['title'], ENT_QUOTES, 'UTF-8'),
                'article.url'            => htmlspecialchars(nc_build_url('post', $art['slug']), ENT_QUOTES, 'UTF-8'),
                'article.excerpt'        => htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'),
                'article.date'           => date($date_format, strtotime($art['published_at'])),
                'article.author'         => $author_meta['author_name'],
                'article.author_url'     => htmlspecialchars($author_meta['author_url']),
                'article.authors'        => $author_meta['authors'],
                'article.featured_image' => htmlspecialchars($art['featured_image'], ENT_QUOTES, 'UTF-8'),
                'article.has_image'      => !empty($art['featured_image']),
                'news.url'               => $news_url
            ];
            $output .= nc_render_template($item_template, $vars);
        }

        if ($total_pages > 1) {
            $output .= '<nav class="nc-frontend-pagination">';
            $curr_params = $_GET;
            for ($i = 1; $i <= $total_pages; $i++) {
                $curr_params['page'] = $i;
                $link = '?' . http_build_query($curr_params);
                $active_cls = ($i === $page) ? ' class="active"' : '';
                $output .= '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' . $active_cls . '>' . $i . '</a> ';
            }
            $output .= '</nav>';
        }
    } else {
        $output .= '<p class="nc-no-results">' . i18n_r('NewsCenter/NO_ARTICLES_FOUND') . '</p>';
    }

    $output .= '</div>';
    return $output;
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
        $excerpt = !empty($art['excerpt']) ? $art['excerpt'] : substr(strip_tags($art['content']), 0, $excerpt_len) . '...';
        $author_meta = nc_get_article_authors_data($art['id'], $art['author']);

        $vars = [
            'article.title'          => htmlspecialchars($art['title'], ENT_QUOTES, 'UTF-8'),
            'article.url'            => htmlspecialchars(nc_build_url('post', $art['slug']), ENT_QUOTES, 'UTF-8'),
            'article.excerpt'        => htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'),
            'article.date'           => date($date_format, strtotime($art['published_at'])),
            'article.author'         => $author_meta['author_name'],
            'article.author_url'     => htmlspecialchars($author_meta['author_url']),
            'article.authors'        => $author_meta['authors'],
            'article.featured_image' => htmlspecialchars($art['featured_image']),
            'article.has_image'      => !empty($art['featured_image']),
            'news.url'               => $news_url
        ];
        $rendered_items .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $container_vars = [
        'section.title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        'posts.items'   => $rendered_items,
        'news.url'      => $news_url
    ];

    $html = nc_render_template($container_tpl, $container_vars);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * Return formatted HTML of all authors with bios and article links
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
        $bio = !empty($au['bio']) ? trim($au['bio']) : '';

        $vars = [
            'author.name'    => htmlspecialchars($au['name'], ENT_QUOTES, 'UTF-8'),
            'author.slug'    => htmlspecialchars($au['slug'], ENT_QUOTES, 'UTF-8'),
            'author.bio'     => nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')),
            'author.has_bio' => !empty($bio),
            'author.url'     => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'author.count'   => (int)$au['article_count']
        ];
        $items_html .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $html = nc_render_template($container_tpl, ['authors.items' => $items_html]);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * Return formatted HTML of all categories with descriptions and article links
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
        $desc = !empty($cat['description']) ? trim($cat['description']) : '';

        $vars = [
            'category.name'            => htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'),
            'category.slug'            => htmlspecialchars($cat['slug'], ENT_QUOTES, 'UTF-8'),
            'category.description'     => nl2br(htmlspecialchars($desc, ENT_QUOTES, 'UTF-8')),
            'category.has_description' => !empty($desc),
            'category.url'             => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
            'category.count'           => (int)$cat['article_count']
        ];
        $items_html .= nc_render_template($item_tpl, $vars) . "\n";
    }

    $html = nc_render_template($container_tpl, ['categories.items' => $items_html]);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * PHP Theme Function for Home Pages / Grid layouts
 */
function nc_show_recent_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

function nc_home_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

/**
 * PHP Theme Function for Authors Directory
 */
function nc_show_authors() {
    echo nc_get_authors_html();
}

/**
 * PHP Theme Function for Categories Directory
 */
function nc_show_categories() {
    echo nc_get_categories_html();
}

/**
 * Lightweight text-only list for narrow sidebars
 */
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
        echo '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($art['title'], ENT_QUOTES, 'UTF-8') . '</a> ';
        echo '<span class="nc-recent-date">(' . date($date_format, strtotime($art['published_at'])) . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Display monthly archives list
 */
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
        $label = date('F Y', $time);
        $link = nc_build_url('archive', $arch['archive_month']);
        echo '<li>';
        echo '<a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a> ';
        echo '<span class="nc-archive-count">(' . (int)$arch['post_count'] . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}