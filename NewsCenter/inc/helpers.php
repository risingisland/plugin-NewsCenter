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
 * Handle administrative GET delete actions before output starts.
 */
function nc_handle_get_delete_actions() {
    nc_verify_csrf();

    if (isset($_GET['article_id'])) {
        $del_id = (int)$_GET['article_id'];
        nc_delete_article($del_id);
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

        // Process Gallery JSON
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
        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=edit-article&article_id=' . $saved_id . '&status=saved');
    }

    if ($action === 'save_category') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $slug = nc_sanitize_slug($_POST['slug'] ?? $name);
        $desc = trim($_POST['description'] ?? '');

        if (!empty($name) && !empty($slug)) {
            nc_save_category($name, $slug, $desc, $id);
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

        if (!empty($name) && !empty($slug)) {
            nc_save_author($name, $slug, $bio, $id);
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
            'custom_css'       => trim($_POST['custom_css'] ?? ''),
            'sitemap_priority' => in_array($_POST['sitemap_priority'] ?? '', ['1.0', '0.8', '0.6', '0.4']) ? $_POST['sitemap_priority'] : '0.8'
        ];

        nc_save_settings($settings);

        $custom_css_file = NC_DIR . 'assets/css/custom.css';
        if (!empty($settings['custom_css'])) {
            file_put_contents($custom_css_file, $settings['custom_css']);
        } elseif (file_exists($custom_css_file)) {
            unlink($custom_css_file);
        }

        redirect('load.php?id=' . NC_PLUGIN_ID . '&action=settings&status=saved');
    }
}