<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * NewsCenter Frontend Engine
 */

add_action('index-pretemplate', 'nc_frontend_handler');
add_filter('content', 'nc_content_shortcode_filter');
add_action('theme-header', 'nc_inject_frontend_css');

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

    if (isset($_GET['post'])) {
        $post_slug = nc_sanitize_slug($_GET['post']);
        $stmt = $db->prepare("SELECT * FROM nc_articles WHERE slug = ? AND status = 'published' AND published_at <= CURRENT_TIMESTAMP LIMIT 1");
        $stmt->execute([$post_slug]);
        $article = $stmt->fetch();

        if ($article) {
            $data_index->title = !empty($article['meta_title']) ? $article['meta_title'] : $article['title'];
            $data_index->metad = !empty($article['meta_description']) ? $article['meta_description'] : substr(strip_tags($article['content']), 0, 160);
            $title = $data_index->title;
            $metad = $data_index->metad;
        }
    } elseif (isset($_GET['category'])) {
        $cat_slug = nc_sanitize_slug($_GET['category']);
        $stmt = $db->prepare("SELECT name FROM nc_categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$cat_slug]);
        $cat_name = $stmt->fetchColumn();
        if ($cat_name) {
            $data_index->title = $cat_name . ' - ' . $data_index->title;
            $title = $data_index->title;
        }
    } elseif (isset($_GET['tag'])) {
        $tag_slug = nc_sanitize_slug($_GET['tag']);
        $stmt = $db->prepare("SELECT name FROM nc_tags WHERE slug = ? LIMIT 1");
        $stmt->execute([$tag_slug]);
        $tag_name = $stmt->fetchColumn();
        if ($tag_name) {
            $data_index->title = '#' . $tag_name . ' - ' . $data_index->title;
            $title = $data_index->title;
        }
    } elseif (isset($_GET['author'])) {
        $author_slug = nc_sanitize_slug($_GET['author']);
        $stmt = $db->prepare("SELECT name FROM nc_authors WHERE slug = ? LIMIT 1");
        $stmt->execute([$author_slug]);
        $author_name = $stmt->fetchColumn();
        if ($author_name) {
            $data_index->title = 'Articles by ' . $author_name . ' - ' . $data_index->title;
            $title = $data_index->title;
        }
    }

    $data_index->content = nc_dispatch_frontend();
}

/**
 * Shortcode Filter: supports [get-newscenter-recent limit="3"]
 */
function nc_content_shortcode_filter($content) {
    if (empty($content) || !is_string($content)) {
        return $content;
    }

    $pattern = '/\[get-newscenter-recent(?:\s+limit=[\'"]?(\d+)[\'"]?)?(?:\s+category=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?)?(?:\s+author=[\'"]?([a-zA-Z0-9\-_]+)[\'"]?)?\s*\]/i';

    $content = preg_replace_callback($pattern, function($matches) {
        $limit = !empty($matches[1]) ? (int)$matches[1] : 3;
        $category = !empty($matches[2]) ? nc_sanitize_slug($matches[2]) : '';
        $author = !empty($matches[3]) ? nc_sanitize_slug($matches[3]) : '';
        return nc_get_recent_posts_html($limit, $category, 'Latest News', $author);
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

    if (isset($_GET['category'])) {
        $cat_slug = nc_sanitize_slug($_GET['category']);
        return $css_tag . nc_render_archive_list('category', $cat_slug);
    }

    if (isset($_GET['tag'])) {
        $tag_slug = nc_sanitize_slug($_GET['tag']);
        return $css_tag . nc_render_archive_list('tag', $tag_slug);
    }

    if (isset($_GET['author'])) {
        $author_slug = nc_sanitize_slug($_GET['author']);
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
 * Helper to get authors assigned to an article
 */
function nc_get_article_authors_data($article_id, $default_author_string = 'Admin') {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');

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
    $first_url = $news_url;

    if (!empty($authors)) {
        foreach ($authors as $i => $a) {
            $names[] = htmlspecialchars($a['name']);
            $url = $news_url . '?author=' . urlencode($a['slug']);
            if ($i === 0) {
                $first_url = $url;
            }
            $links[] = '<a href="' . htmlspecialchars($url) . '" class="nc-author-link">' . htmlspecialchars($a['name']) . '</a>';
        }
    } else {
        $fallback_name = !empty($default_author_string) ? htmlspecialchars($default_author_string) : 'Admin';
        $names[] = $fallback_name;
        $fallback_slug = nc_sanitize_slug($fallback_name);
        $first_url = $news_url . '?author=' . urlencode($fallback_slug);
        $links[] = '<a href="' . htmlspecialchars($first_url) . '" class="nc-author-link">' . $fallback_name . '</a>';
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
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');

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
        $cat_links[] = '<a href="' . htmlspecialchars($news_url . '?category=' . urlencode($c['slug'])) . '" class="nc-category-link">' . htmlspecialchars($c['name']) . '</a>';
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
        $tag_links[] = '<a href="' . htmlspecialchars($news_url . '?tag=' . urlencode($t['slug'])) . '" class="nc-tag-link">#' . htmlspecialchars($t['name']) . '</a>';
    }

    $author_meta = nc_get_article_authors_data($article['id'], $article['author']);

    // Build GLightbox Gallery Markup
    $gallery_html = '';
    $gallery_items = !empty($article['gallery']) ? json_decode($article['gallery'], true) : [];

    if (!empty($gallery_items) && is_array($gallery_items)) {
        $gallery_html .= "\n" . '<!-- NewsCenter GLightbox Gallery -->' . "\n";
        $gallery_html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">' . "\n";
        $gallery_html .= '<div class="nc-article-gallery">' . "\n";
        foreach ($gallery_items as $item) {
            $img_url  = htmlspecialchars($item['url']);
            $thumb    = htmlspecialchars(!empty($item['thumb']) ? $item['thumb'] : $item['url']);
            $caption  = !empty($item['caption']) ? htmlspecialchars($item['caption']) : '';
            $alt_text = !empty($caption) ? $caption : htmlspecialchars($item['name'] ?? $article['title']);

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
        'article.title'          => htmlspecialchars($article['title']),
        'article.slug'           => htmlspecialchars($article['slug']),
        'article.content'        => $content_with_gallery,
        'article.excerpt'        => htmlspecialchars($article['excerpt']),
        'article.date'           => date($date_format, strtotime($article['published_at'])),
        'article.date_raw'       => $article['published_at'],
        'article.author'         => $author_meta['author_name'],
        'article.author_url'     => htmlspecialchars($author_meta['author_url']),
        'article.authors'        => $author_meta['authors'],
        'article.featured_image' => htmlspecialchars($article['featured_image']),
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
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');

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
        $output .= '<div class="nc-filter-heading"><h3>' . i18n_r('NewsCenter/SEARCH_RESULTS_FOR') . ' <em>"' . htmlspecialchars($filter_val) . '"</em></h3></div>';
    } elseif ($filter_type === 'author') {
        $stmt_au = $db->prepare("SELECT name FROM nc_authors WHERE slug = ? LIMIT 1");
        $stmt_au->execute([$filter_val]);
        $author_name = $stmt_au->fetchColumn() ?: $filter_val;
        $output .= '<div class="nc-filter-heading"><h3>Articles by <em>' . htmlspecialchars($author_name) . '</em></h3></div>';
    } elseif ($filter_type === 'category' || $filter_type === 'tag' || $filter_type === 'archive') {
        $output .= '<div class="nc-filter-heading"><h3>' . ucfirst($filter_type) . ': <em>' . htmlspecialchars($filter_val) . '</em></h3></div>';
    }

    if (!empty($articles)) {
        foreach ($articles as $art) {
            $excerpt = !empty($art['excerpt']) ? $art['excerpt'] : substr(strip_tags($art['content']), 0, $excerpt_len) . '...';
            $author_meta = nc_get_article_authors_data($art['id'], $art['author']);

            $vars = [
                'article.title'          => htmlspecialchars($art['title']),
                'article.url'            => htmlspecialchars($news_url . '?post=' . urlencode($art['slug'])),
                'article.excerpt'        => htmlspecialchars($excerpt),
                'article.date'           => date($date_format, strtotime($art['published_at'])),
                'article.author'         => $author_meta['author_name'],
                'article.author_url'     => htmlspecialchars($author_meta['author_url']),
                'article.authors'        => $author_meta['authors'],
                'article.featured_image' => htmlspecialchars($art['featured_image']),
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
                $output .= '<a href="' . htmlspecialchars($link) . '"' . $active_cls . '>' . $i . '</a> ';
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
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');
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
            'article.title'          => htmlspecialchars($art['title']),
            'article.url'            => htmlspecialchars($news_url . '?post=' . urlencode($art['slug'])),
            'article.excerpt'        => htmlspecialchars($excerpt),
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
        'section.title' => htmlspecialchars($title),
        'posts.items'   => $rendered_items,
        'news.url'      => $news_url
    ];

    $html = nc_render_template($container_tpl, $container_vars);
    return nc_get_frontend_css_tag() . $html;
}

/**
 * PHP Theme Function for Home Pages / Grid layouts
 */
function nc_show_recent_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

/**
 * Optional alias for home templates
 */
function nc_home_posts($limit = 3, $category_slug = '', $title = 'Latest News', $author_slug = '') {
    echo nc_get_recent_posts_html($limit, $category_slug, $title, $author_slug);
}

/**
 * Lightweight text-only list for narrow sidebars
 */
function nc_show_recent_posts_list($limit = 5) {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');
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
        $link = $news_url . '?post=' . urlencode($art['slug']);
        echo '<li>';
        echo '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($art['title']) . '</a> ';
        echo '<span class="nc-recent-date">(' . date($date_format, strtotime($art['published_at'])) . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}

/**
 * Sitemap URLs generator for GetSimple XML Sitemap integration
 */
function nc_sitemap_urls() {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');
    $priority = !empty($settings['sitemap_priority']) ? $settings['sitemap_priority'] : '0.8';

    $stmt = $db->query("
        SELECT slug, updated_at 
        FROM nc_articles 
        WHERE status = 'published' AND published_at <= CURRENT_TIMESTAMP 
        ORDER BY published_at DESC
    ");
    $articles = $stmt->fetchAll();

    foreach ($articles as $art) {
        $loc = $news_url . '?post=' . urlencode($art['slug']);
        $lastmod = date('Y-m-d', strtotime($art['updated_at']));
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>" . htmlspecialchars($priority) . "</priority>\n";
        echo "  </url>\n";
    }
}

/**
 * Display monthly archives list
 */
function nc_show_archives() {
    $db = nc_get_db();
    $settings = nc_get_settings();
    $news_url = find_url(!empty($settings['page_slug']) ? $settings['page_slug'] : 'news', '');

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
        $link = $news_url . '?archive=' . urlencode($arch['archive_month']);
        echo '<li>';
        echo '<a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($label) . '</a> ';
        echo '<span class="nc-archive-count">(' . (int)$arch['post_count'] . ')</span>';
        echo '</li>';
    }
    echo '</ul>';
}