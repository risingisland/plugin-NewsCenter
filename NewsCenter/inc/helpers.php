<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Sanitize URL slugs.
 */
function nc_sanitize_slug($str) {
    $str = strtolower(trim((string)$str));
    $str = str_replace(' ', '-', $str);
    $str = preg_replace('/[^a-z0-9-_]/', '', $str);
    return trim($str, '-_');
}

/**
 * Human-readable file size formatter.
 */
function nc_format_file_size($bytes) {
    if ($bytes <= 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

/**
 * Permission check for GetSimple CMS CE backend requests.
 */
function nc_check_permission() {
    if (!defined('IN_GS')) return false;

    if (!function_exists('cookie_check') && defined('GSADMININCPATH') && file_exists(GSADMININCPATH . 'inc/cookie_functions.php')) {
        require_once(GSADMININCPATH . 'inc/cookie_functions.php');
    }

    return function_exists('cookie_check') ? (bool)cookie_check() : false;
}

/**
 * Get accurate site URL with HTTPS detection.
 */
function nc_site_url() {
    global $SITEURL;
    $url   = (string)$SITEURL;
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    if ($https) {
        $url = preg_replace('~^http://~i', 'https://', $url);
    }
    return rtrim($url, '/') . '/';
}

/**
 * Clean relative file path.
 */
function nc_clean_file($file) {
    $file  = str_replace('\\', '/', (string)$file);
    $parts = [];
    foreach (explode('/', $file) as $p) {
        if ($p === '' || $p === '.' || $p === '..') {
            continue;
        }
        $parts[] = $p;
    }
    return implode('/', $parts);
}

/**
 * Build web URL for uploaded image.
 */
function nc_image_url($file) {
    return nc_site_url() . 'data/uploads/' . implode('/', array_map('rawurlencode', explode('/', $file)));
}

/**
 * Build web URL for thumbnail.
 */
function nc_thumb_url($file) {
    $parts = explode('/', $file);
    $base  = array_pop($parts);
    $sub   = count($parts) ? implode('/', $parts) . '/' : '';
    if (defined('GSTHUMBNAILPATH') && is_file(GSTHUMBNAILPATH . $sub . 'thumbnail.' . $base)) {
        return nc_site_url() . 'data/thumbs/' . implode('/', array_map('rawurlencode', explode('/', $sub . 'thumbnail.' . $base)));
    }
    return nc_image_url($file);
}

/**
 * Check if filename has an image extension.
 */
function nc_is_image_name($name) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];
    return in_array(strtolower(pathinfo((string)$name, PATHINFO_EXTENSION)), $allowed, true);
}

/**
 * Scan uploads directory.
 */
function nc_list_uploads() {
    $root = defined('GSDATAUPLOADPATH') ? rtrim(GSDATAUPLOADPATH, '/\\') : rtrim(GSDATAPATH . 'uploads', '/\\');
    $out  = [];

    if (!is_dir($root)) {
        return $out;
    }

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS)
        );
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $pathname = str_replace('\\', '/', $f->getPathname());
            $cleanRoot = str_replace('\\', '/', $root);
            $rel = nc_clean_file(substr($pathname, strlen($cleanRoot)));
            if ($rel === '' || !nc_is_image_name($rel) || strpos('/' . $rel, '/.') !== false) {
                continue;
            }
            $out[] = [
                'name'  => basename($rel),
                'file'  => $rel,
                'path'  => $rel,
                'url'   => nc_image_url($rel),
                'thumb' => nc_thumb_url($rel),
                'mtime' => $f->getMTime(),
            ];
        }
    } catch (\Throwable $e) {
    }

    if (empty($out)) {
        $items = @scandir($root);
        if ($items !== false) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..' || $item[0] === '.') continue;
                $p = $root . DIRECTORY_SEPARATOR . $item;
                if (is_file($p)) {
                    $rel = nc_clean_file($item);
                    if (nc_is_image_name($rel)) {
                        $out[] = [
                            'name'  => basename($rel),
                            'file'  => $rel,
                            'path'  => $rel,
                            'url'   => nc_image_url($rel),
                            'thumb' => nc_thumb_url($rel),
                            'mtime' => filemtime($p) ?: 0,
                        ];
                    }
                }
            }
        }
    }

    usort($out, function ($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });

    return $out;
}

/**
 * Handle image uploads from the media browser modal.
 */
function nc_handle_upload() {
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    if (!nc_check_permission()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $target_dir = defined('GSDATAUPLOADPATH') ? rtrim(GSDATAUPLOADPATH, '/\\') . '/' : GSDATAPATH . 'uploads/';
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    if (empty($_FILES['file'])) {
        echo json_encode(['ok' => false, 'error' => 'No files uploaded']);
        exit;
    }

    $f = $_FILES['file'];
    $uploaded = [];
    $errors = [];

    $names = (array)$f['name'];
    $tmp_names = (array)$f['tmp_name'];
    $errs = (array)$f['error'];
    $sizes = (array)$f['size'];

    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    foreach ($names as $i => $orig) {
        $orig = (string)$orig;
        $tmp = (string)$tmp_names[$i];
        $err = (int)$errs[$i];
        $size = (int)$sizes[$i];

        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = $orig . ' - Upload error code ' . $err;
            continue;
        }

        $info = @getimagesize($tmp);
        if (!$info || !isset($types[$info[2]])) {
            $errors[] = $orig . ' - Invalid image file type';
            continue;
        }

        $ext = $types[$info[2]];
        $name = pathinfo($orig, PATHINFO_FILENAME);
        if (function_exists('to7bit')) {
            $name = to7bit($name);
        }
        $name = function_exists('clean_img_name') ? clean_img_name($name) : preg_replace('/[^a-zA-Z0-9\-_]/', '', str_replace(' ', '-', $name));
        $name = trim((string)$name, '-.');
        if ($name === '') {
            $name = 'image';
        }

        $base = $name . '.' . $ext;
        $count = 1;
        while (file_exists($target_dir . $base)) {
            $base = $count . '-' . $name . '.' . $ext;
            $count++;
        }

        $dest = $target_dir . $base;
        if (!move_uploaded_file($tmp, $dest)) {
            $errors[] = $orig . ' - Failed to move uploaded file';
            continue;
        }

        @chmod($dest, defined('GSCHMOD') ? GSCHMOD : 0644);

        $uploaded[] = [
            'name'  => $base,
            'file'  => $base,
            'path'  => $base,
            'url'   => nc_image_url($base),
            'thumb' => nc_thumb_url($base),
            'mtime' => time(),
        ];
    }

    echo json_encode([
        'ok'     => empty($errors) || !empty($uploaded),
        'files'  => $uploaded,
        'errors' => $errors
    ]);
    exit;
}

/**
 * Ajax endpoint: scan GetSimple CE uploads directory and return image objects as JSON.
 */
function nc_ajax_get_media() {
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (!nc_check_permission()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized: Please log in to admin.']);
        exit;
    }

    $files = nc_list_uploads();

    echo json_encode([
        'files' => $files
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Migrates posts, categories, and tags from SimpleBlog SQLite database (blog.db) into NewsCenter.
 */
function nc_migrate_from_simpleblog() {
    $sb_file = defined('GSDATAOTHERPATH') ? GSDATAOTHERPATH . 'blog.db' : '';
    if (!file_exists($sb_file)) {
        return ['ok' => false, 'error' => 'SimpleBlog database (data/other/blog.db) not found.'];
    }

    try {
        $sb_db = new SQLite3($sb_file, SQLITE3_OPEN_READONLY);
    } catch (\Throwable $e) {
        return ['ok' => false, 'error' => 'Could not open SimpleBlog database: ' . $e->getMessage()];
    }

    $nc_db = nc_get_db();
    $counts = ['posts' => 0, 'categories' => 0, 'tags' => 0];

    // Ensure default author exists
    $default_author_name = defined('USR') ? USR : 'admin';
    $au_stmt = $nc_db->prepare("SELECT id FROM nc_authors WHERE name = ? LIMIT 1");
    $au_stmt->execute([$default_author_name]);
    $author_id = $au_stmt->fetchColumn();

    if (!$author_id) {
        $ins_au = $nc_db->prepare("INSERT INTO nc_authors (name, slug, bio, image) VALUES (?, ?, '', '')");
        $ins_au->execute([$default_author_name, nc_sanitize_slug($default_author_name)]);
        $author_id = (int)$nc_db->lastInsertId();
    }

    // 1. Migrate Categories
    $category_id_map = [];
    $sb_cats = $sb_db->query("SELECT * FROM categories");
    if ($sb_cats) {
        while ($cat = $sb_cats->fetchArray(SQLITE3_ASSOC)) {
            $cat_name = trim((string)$cat['name']);
            $cat_slug = nc_sanitize_slug($cat['slug'] ?: $cat_name);

            $chk = $nc_db->prepare("SELECT id FROM nc_categories WHERE slug = ? OR name = ? LIMIT 1");
            $chk->execute([$cat_slug, $cat_name]);
            $existing_id = $chk->fetchColumn();

            if ($existing_id) {
                $category_id_map[$cat['id']] = (int)$existing_id;
            } else {
                $ins_cat = $nc_db->prepare("INSERT INTO nc_categories (name, slug, description, image) VALUES (?, ?, '', '')");
                $ins_cat->execute([$cat_name, $cat_slug]);
                $new_id = (int)$nc_db->lastInsertId();
                $category_id_map[$cat['id']] = $new_id;
                $counts['categories']++;
            }
        }
    }

    // 2. Migrate Tags
    $tag_id_map = [];
    $sb_tags = $sb_db->query("SELECT * FROM tags");
    if ($sb_tags) {
        while ($t = $sb_tags->fetchArray(SQLITE3_ASSOC)) {
            $t_name = trim((string)$t['name']);
            $t_slug = nc_sanitize_slug($t['slug'] ?: $t_name);

            $chk = $nc_db->prepare("SELECT id FROM nc_tags WHERE slug = ? OR name = ? LIMIT 1");
            $chk->execute([$t_slug, $t_name]);
            $existing_id = $chk->fetchColumn();

            if ($existing_id) {
                $tag_id_map[$t['id']] = (int)$existing_id;
            } else {
                $ins_tag = $nc_db->prepare("INSERT INTO nc_tags (name, slug) VALUES (?, ?)");
                $ins_tag->execute([$t_name, $t_slug]);
                $new_id = (int)$nc_db->lastInsertId();
                $tag_id_map[$t['id']] = $new_id;
                $counts['tags']++;
            }
        }
    }

    // 3. Migrate Posts
    $sb_posts = $sb_db->query("SELECT * FROM posts ORDER BY id ASC");
    if ($sb_posts) {
        while ($p = $sb_posts->fetchArray(SQLITE3_ASSOC)) {
            $title   = trim((string)$p['title']);
            $slug    = nc_sanitize_slug($p['slug'] ?: $title);
            $content = (string)$p['content'];
            $excerpt = (string)($p['description'] ?? '');
            $meta_d  = (string)($p['metadata'] ?? '');

            $pub_ts  = !empty($p['date']) ? (int)$p['date'] : time();
            $upd_ts  = !empty($p['updated_at']) ? (int)$p['updated_at'] : $pub_ts;
            $pub_at  = date('Y-m-d H:i:s', $pub_ts);
            $upd_at  = date('Y-m-d H:i:s', $upd_ts);
            $status  = !empty($p['published']) ? 'published' : 'draft';

            $cover = trim((string)($p['cover_photo'] ?? ''));
            if (strpos($cover, 'data/uploads/') === 0) {
                $cover = substr($cover, strlen('data/uploads/'));
            }

            $raw_gallery = trim((string)($p['gallery'] ?? ''));
            $decoded_gallery = json_decode($raw_gallery, true);
            $nc_gallery = [];
            if (is_array($decoded_gallery)) {
                foreach ($decoded_gallery as $g_item) {
                    if (is_string($g_item) && !empty($g_item)) {
                        $clean_path = ltrim($g_item, '/');
                        if (strpos($clean_path, 'data/uploads/') === 0) {
                            $clean_path = substr($clean_path, strlen('data/uploads/'));
                        }
                        $nc_gallery[] = [
                            'url'     => $clean_path,
                            'thumb'   => function_exists('nc_thumb_url') ? nc_thumb_url($clean_path) : $clean_path,
                            'name'    => basename($clean_path),
                            'caption' => ''
                        ];
                    } elseif (is_array($g_item) && !empty($g_item['url'])) {
                        $nc_gallery[] = $g_item;
                    }
                }
            }
            $gallery_json = json_encode($nc_gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $base_slug = $slug;
            $counter = 1;
            while (true) {
                $chk = $nc_db->prepare("SELECT id FROM nc_articles WHERE slug = ? LIMIT 1");
                $chk->execute([$slug]);
                if (!$chk->fetchColumn()) {
                    break;
                }
                $slug = $base_slug . '-' . $counter;
                $counter++;
            }

            $ins_art = $nc_db->prepare("
                INSERT INTO nc_articles (slug, title, excerpt, content, featured_image, gallery, status, author, meta_title, meta_description, published_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins_art->execute([
                $slug,
                $title,
                $excerpt,
                $content,
                $cover,
                $gallery_json,
                $status,
                $default_author_name,
                $title,
                $meta_d,
                $pub_at,
                $upd_at
            ]);
            $new_post_id = (int)$nc_db->lastInsertId();
            $counts['posts']++;

            if ($author_id > 0) {
                $ins_aa = $nc_db->prepare("INSERT OR IGNORE INTO nc_article_authors (article_id, author_id) VALUES (?, ?)");
                $ins_aa->execute([$new_post_id, $author_id]);
            }

            $old_cat_id = !empty($p['category_id']) ? (int)$p['category_id'] : 0;
            if ($old_cat_id > 0 && isset($category_id_map[$old_cat_id])) {
                $ins_ac = $nc_db->prepare("INSERT OR IGNORE INTO nc_article_categories (article_id, category_id) VALUES (?, ?)");
                $ins_ac->execute([$new_post_id, $category_id_map[$old_cat_id]]);
            }

            $pt_stmt = $sb_db->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
            $pt_stmt->bindValue(1, (int)$p['id'], SQLITE3_INTEGER);
            $pt_res = $pt_stmt->execute();
            if ($pt_res) {
                while ($pt = $pt_res->fetchArray(SQLITE3_ASSOC)) {
                    $old_tag_id = (int)$pt['tag_id'];
                    if (isset($tag_id_map[$old_tag_id])) {
                        $ins_at = $nc_db->prepare("INSERT OR IGNORE INTO nc_article_tags (article_id, tag_id) VALUES (?, ?)");
                        $ins_at->execute([$new_post_id, $tag_id_map[$old_tag_id]]);
                    }
                }
            }
        }
    }

    $sb_db->close();

    if (function_exists('nc_generate_sitemap')) {
        nc_generate_sitemap();
    }

    return [
        'ok'         => true,
        'posts'      => $counts['posts'],
        'categories' => $counts['categories'],
        'tags'       => $counts['tags']
    ];
}

/**
 * Migrates posts and tags from News Manager XML files into NewsCenter,
 * resolving HTML entities in content, title, and metadata.
 */
function nc_migrate_from_news_manager() {
    $posts_dir = defined('GSDATAPATH') ? GSDATAPATH . 'posts/' : '';
    if (!is_dir($posts_dir)) {
        return ['ok' => false, 'error' => 'News Manager posts directory (data/posts/) not found.'];
    }

    $files = glob($posts_dir . '*.xml');
    if (empty($files)) {
        return ['ok' => false, 'error' => 'No News Manager post files found in data/posts/.'];
    }

    $nc_db = nc_get_db();
    $counts = ['posts' => 0, 'tags' => 0, 'authors' => 0];

    $default_author_name = defined('USR') ? USR : 'admin';
    $author_cache = [];

    foreach ($files as $file) {
        $slug = basename($file, '.xml');
        $xml = @simplexml_load_file($file);
        if (!$xml) {
            continue;
        }

        // 1. Decode entities for title, content, and metadata
        $raw_title = trim((string)$xml->title);
        $title = $raw_title !== '' ? html_entity_decode($raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $slug;

        // Decode content (handles single and double-encoded News Manager XML dumps)
        $content = (string)$xml->content;
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($content, '&lt;') !== false || strpos($content, '&gt;') !== false) {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Excerpt & Meta Description
        $raw_metad = (string)$xml->metad;
        $meta_d = html_entity_decode($raw_metad, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $excerpt = $meta_d;

        $private = trim((string)$xml->private);
        $status  = ($private === 'Y') ? 'draft' : 'published';

        // Parse date
        $raw_date = (string)$xml->date;
        $timestamp = !empty($raw_date) ? strtotime($raw_date) : filemtime($file);
        if (!$timestamp) $timestamp = time();
        $pub_at = date('Y-m-d H:i:s', $timestamp);
        $upd_at = date('Y-m-d H:i:s', filemtime($file) ?: $timestamp);

        // Normalize image
        $image = trim((string)$xml->image);
        if (strpos($image, 'data/uploads/') === 0) {
            $image = substr($image, strlen('data/uploads/'));
        }

        // Author assignment
        $raw_author = !empty($xml->author) ? trim((string)$xml->author) : $default_author_name;
        $author_name = html_entity_decode($raw_author, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (!isset($author_cache[$author_name])) {
            $au_stmt = $nc_db->prepare("SELECT id FROM nc_authors WHERE name = ? LIMIT 1");
            $au_stmt->execute([$author_name]);
            $a_id = $au_stmt->fetchColumn();
            if (!$a_id) {
                $ins_au = $nc_db->prepare("INSERT INTO nc_authors (name, slug, bio, image) VALUES (?, ?, '', '')");
                $ins_au->execute([$author_name, nc_sanitize_slug($author_name)]);
                $a_id = (int)$nc_db->lastInsertId();
                $counts['authors']++;
            }
            $author_cache[$author_name] = (int)$a_id;
        }
        $assigned_author_id = $author_cache[$author_name];

        // Ensure unique slug
        $base_slug = nc_sanitize_slug($slug);
        $curr_slug = $base_slug;
        $counter = 1;
        while (true) {
            $chk = $nc_db->prepare("SELECT id FROM nc_articles WHERE slug = ? LIMIT 1");
            $chk->execute([$curr_slug]);
            if (!$chk->fetchColumn()) {
                break;
            }
            $curr_slug = $base_slug . '-' . $counter;
            $counter++;
        }

        // Insert article
        $ins = $nc_db->prepare("
            INSERT INTO nc_articles (slug, title, excerpt, content, featured_image, gallery, status, author, meta_title, meta_description, published_at, updated_at)
            VALUES (?, ?, ?, ?, ?, '[]', ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $curr_slug,
            $title,
            $excerpt,
            $content,
            $image,
            $status,
            $author_name,
            $title,
            $meta_d,
            $pub_at,
            $upd_at
        ]);
        $new_post_id = (int)$nc_db->lastInsertId();
        $counts['posts']++;

        // Link author
        if ($assigned_author_id > 0) {
            $ins_aa = $nc_db->prepare("INSERT OR IGNORE INTO nc_article_authors (article_id, author_id) VALUES (?, ?)");
            $ins_aa->execute([$new_post_id, $assigned_author_id]);
        }

        // Process Tags
        $tags_str = trim((string)$xml->tags);
        if ($tags_str !== '') {
            $tags_arr = explode(',', $tags_str);
            foreach ($tags_arr as $tag_raw) {
                $tag_name = html_entity_decode(trim($tag_raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if ($tag_name === '') continue;
                $tag_slug = nc_sanitize_slug($tag_name);

                $t_stmt = $nc_db->prepare("SELECT id FROM nc_tags WHERE slug = ? OR name = ? LIMIT 1");
                $t_stmt->execute([$tag_slug, $tag_name]);
                $tid = $t_stmt->fetchColumn();

                if (!$tid) {
                    $ins_t = $nc_db->prepare("INSERT INTO nc_tags (name, slug) VALUES (?, ?)");
                    $ins_t->execute([$tag_name, $tag_slug]);
                    $tid = (int)$nc_db->lastInsertId();
                    $counts['tags']++;
                }

                $ins_at = $nc_db->prepare("INSERT OR IGNORE INTO nc_article_tags (article_id, tag_id) VALUES (?, ?)");
                $ins_at->execute([$new_post_id, (int)$tid]);
            }
        }
    }

    if (function_exists('nc_generate_sitemap')) {
        nc_generate_sitemap();
    }

    return [
        'ok'      => true,
        'posts'   => $counts['posts'],
        'tags'    => $counts['tags'],
        'authors' => $counts['authors']
    ];
}

/**
 * Handle administrative GET delete actions before output starts.
 */
function nc_handle_get_delete_actions() {
    nc_verify_csrf();

    if (isset($_GET['article_id'])) {
        $del_id = (int)$_GET['article_id'];
        nc_delete_article($del_id);
        if (function_exists('nc_generate_sitemap')) {
            nc_generate_sitemap();
        }
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=articles&status=deleted');
    }

    if (isset($_GET['category_id'])) {
        $del_id = (int)$_GET['category_id'];
        nc_delete_category($del_id);
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=categories&status=deleted');
    }

    if (isset($_GET['tag_id'])) {
        $del_id = (int)$_GET['tag_id'];
        nc_delete_tag($del_id);
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=tags&status=deleted');
    }

    if (isset($_GET['author_id'])) {
        $del_id = (int)$_GET['author_id'];
        nc_delete_author($del_id);
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=authors&status=deleted');
    }

    if (isset($_GET['restore_template']) && isset($_GET['key'])) {
        $key = nc_sanitize_slug($_GET['key']);
        nc_reset_default_templates();
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=templates&key=' . urlencode($key) . '&status=saved');
    }
}

/**
 * Handle administrative POST requests before output starts.
 */
function nc_handle_post_actions($action) {
    nc_verify_csrf();

    if ($action === 'migrate_simpleblog') {
        $res = nc_migrate_from_simpleblog();
        if (!empty($res['ok'])) {
            $msg = urlencode("Imported {$res['posts']} posts, {$res['categories']} categories, and {$res['tags']} tags from SimpleBlog.");
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=migrated&msg=' . $msg);
        } else {
            $err = urlencode($res['error'] ?? 'Migration failed.');
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=error&msg=' . $err);
        }
    }

    if ($action === 'migrate_news_manager') {
        $res = nc_migrate_from_news_manager();
        if (!empty($res['ok'])) {
            $msg = urlencode("Imported {$res['posts']} posts and {$res['tags']} tags from News Manager.");
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=migrated&msg=' . $msg);
        } else {
            $err = urlencode($res['error'] ?? 'Migration failed.');
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=error&msg=' . $err);
        }
    }

    if ($action === 'save_article') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $slug = nc_sanitize_slug($_POST['slug'] ?? $title);
        $content = nc_sanitize_html($_POST['content'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
        $featured_image = trim($_POST['featured_image'] ?? '');
        $published_at = !empty($_POST['published_at']) ? date('Y-m-d H:i:s', strtotime($_POST['published_at'])) : date('Y-m-d H:i:s');
        $author = defined('USR') ? USR : 'admin';

        $raw_gallery = $_POST['gallery'] ?? '[]';
        $decoded = json_decode($raw_gallery, true);
        $clean_gallery = [];
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                if (!empty($item['url'])) {
                    $clean_gallery[] = [
                        'url'     => trim($item['url']),
                        'thumb'   => !empty($item['thumb']) ? trim($item['thumb']) : trim($item['url']),
                        'name'    => !empty($item['name']) ? trim($item['name']) : basename($item['url']),
                        'caption' => !empty($item['caption']) ? trim(strip_tags($item['caption'])) : ''
                    ];
                }
            }
        }
        $gallery_json = json_encode($clean_gallery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $data = [
            'id'               => $id,
            'title'            => $title,
            'slug'             => $slug,
            'content'          => $content,
            'excerpt'          => $excerpt,
            'status'           => $status,
            'featured_image'   => $featured_image,
            'gallery'          => $gallery_json,
            'published_at'     => $published_at,
            'author'           => $author,
            'meta_title'       => trim($_POST['meta_title'] ?? $title),
            'meta_description' => trim($_POST['meta_description'] ?? '')
        ];

        $categories = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];
        $tags = !empty($_POST['tags']) ? explode(',', $_POST['tags']) : [];
        $authors = isset($_POST['authors']) && is_array($_POST['authors']) ? $_POST['authors'] : [];

        $saved_id = nc_save_article($data, $categories, $tags, $authors);

        if (function_exists('nc_generate_sitemap')) {
            nc_generate_sitemap();
        }

        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=edit-article&article_id=' . $saved_id . '&status=saved');
    }

    if ($action === 'rebuild_sitemap') {
        if (function_exists('nc_generate_sitemap')) {
            nc_generate_sitemap();
        }
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=saved');
    }

    if ($action === 'save_category') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = nc_sanitize_slug($_POST['slug'] ?? $name);
        $desc = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if (!empty($name) && !empty($slug)) {
            nc_save_category($name, $slug, $desc, $image, $id);
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=categories&status=saved');
        } else {
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=categories&status=error');
        }
    }

    if ($action === 'save_tag') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = nc_sanitize_slug($_POST['slug'] ?? $name);

        if (!empty($name) && !empty($slug)) {
            $db = nc_get_db();
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE nc_tags SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO nc_tags (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
            }
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=tags&status=saved');
        } else {
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=tags&status=error');
        }
    }

    if ($action === 'save_author') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = nc_sanitize_slug($_POST['slug'] ?? $name);
        $bio = trim($_POST['bio'] ?? '');
        $image = trim($_POST['image'] ?? '');

        if (!empty($name) && !empty($slug)) {
            nc_save_author($name, $slug, $bio, $image, $id);
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=authors&status=saved');
        } else {
            redirect('load.php?id=' . NC_PLUGIN_ID . '&action=authors&status=error');
        }
    }

    if ($action === 'save_template') {
        $key = nc_sanitize_slug($_POST['template_key'] ?? 'single');
        $content = $_POST['content'] ?? '';
        nc_save_template_content($key, $content);
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=templates&key=' . urlencode($key) . '&status=saved');
    }

    if ($action === 'save_settings') {
        $settings = [
            'page_slug'        => nc_sanitize_slug($_POST['page_slug'] ?? 'news'),
            'posts_per_page'   => max(1, (int)($_POST['posts_per_page'] ?? 10)),
            'excerpt_length'   => max(50, (int)($_POST['excerpt_length'] ?? 250)),
            'date_format'      => trim($_POST['date_format'] ?? 'F j, Y'),
            'sitemap_priority' => in_array($_POST['sitemap_priority'] ?? '', ['1.0', '0.8', '0.6', '0.4']) ? $_POST['sitemap_priority'] : '0.8',
            'trailing_slash'   => !empty($_POST['trailing_slash']) ? '1' : '0'
        ];

        nc_save_settings($settings);

        if (function_exists('nc_generate_sitemap')) {
            nc_generate_sitemap();
        }

        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=saved');
    }
}

/**
 * Localised replacement for date().
 */
function nc_date($format, $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $timestamp = (int)$timestamp;

    $lookup = function ($key, $fallback) {
        $val = i18n_r('NewsCenter/' . $key);
        return ($val === '' || $val === null || $val[0] === '{') ? $fallback : $val;
    };

    $out = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $c = $format[$i];

        if ($c === '\\') {
            $out .= $c;
            if ($i + 1 < $len) $out .= $format[++$i];
            continue;
        }

        $name = null;
        switch ($c) {
            case 'l': $name = $lookup('DAY_' . date('w', $timestamp),         date('l', $timestamp)); break;
            case 'D': $name = $lookup('DAY_SHORT_' . date('w', $timestamp),   date('D', $timestamp)); break;
            case 'F': $name = $lookup('MONTH_' . date('n', $timestamp),       date('F', $timestamp)); break;
            case 'M': $name = $lookup('MONTH_SHORT_' . date('n', $timestamp), date('M', $timestamp)); break;
        }

        if ($name !== null) {
            $out .= preg_replace('/([a-zA-Z])/', '\\\\$1', $name);
        } else {
            $out .= $c;
        }
    }
    return date($out, $timestamp);
}