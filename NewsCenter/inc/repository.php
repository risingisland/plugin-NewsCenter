<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Unified Repository Layer: SQLite Persistent Storage Engine
 */

/* ==========================================================================
   Dashboard Metrics
   ========================================================================== */

function nc_get_dashboard_stats() {
    $db = nc_get_db();
    
    $total = (int)$db->query("SELECT COUNT(*) FROM nc_articles")->fetchColumn();
    $published = (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'published'")->fetchColumn();
    $draft = (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'draft'")->fetchColumn();
    $categories = (int)$db->query("SELECT COUNT(*) FROM nc_categories")->fetchColumn();
    $tags = (int)$db->query("SELECT COUNT(*) FROM nc_tags")->fetchColumn();
    $authors = (int)$db->query("SELECT COUNT(*) FROM nc_authors")->fetchColumn();

    return [
        'total'      => $total,
        'published'  => $published,
        'draft'      => $draft,
        'categories' => $categories,
        'tags'       => $tags,
        'authors'    => $authors
    ];
}

function nc_get_recent_articles($limit = 5) {
    $db = nc_get_db();
    $stmt = $db->prepare("SELECT * FROM nc_articles ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/* ==========================================================================
   Articles CRUD
   ========================================================================== */

function nc_get_article($identifier, $by = 'id') {
    $db = nc_get_db();
    $field = ($by === 'slug') ? 'slug' : 'id';
    $stmt = $db->prepare("SELECT * FROM nc_articles WHERE {$field} = ? LIMIT 1");
    $stmt->execute([$identifier]);
    return $stmt->fetch();
}

function nc_save_article($data, $category_ids = [], $tag_names = [], $author_ids = []) {
    $db = nc_get_db();
    $db->beginTransaction();

    try {
        $id = !empty($data['id']) ? (int)$data['id'] : 0;
        $now = date('Y-m-d H:i:s');
        $gallery = !empty($data['gallery']) ? $data['gallery'] : '[]';

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE nc_articles SET 
                    slug = :slug,
                    title = :title,
                    excerpt = :excerpt,
                    content = :content,
                    featured_image = :featured_image,
                    gallery = :gallery,
                    status = :status,
                    author = :author,
                    meta_title = :meta_title,
                    meta_description = :meta_description,
                    published_at = :published_at,
                    updated_at = :updated_at
                WHERE id = :id
            ");
            $stmt->execute([
                ':slug'             => $data['slug'],
                ':title'            => $data['title'],
                ':excerpt'          => $data['excerpt'],
                ':content'          => $data['content'],
                ':featured_image'   => $data['featured_image'],
                ':gallery'          => $gallery,
                ':status'           => $data['status'],
                ':author'           => $data['author'],
                ':meta_title'       => $data['meta_title'],
                ':meta_description' => $data['meta_description'],
                ':published_at'     => $data['published_at'],
                ':updated_at'       => $now,
                ':id'               => $id
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO nc_articles (
                    slug, title, excerpt, content, featured_image, gallery, status, author, 
                    meta_title, meta_description, published_at, created_at, updated_at
                ) VALUES (
                    :slug, :title, :excerpt, :content, :featured_image, :gallery, :status, :author,
                    :meta_title, :meta_description, :published_at, :created_at, :updated_at
                )
            ");
            $stmt->execute([
                ':slug'             => $data['slug'],
                ':title'            => $data['title'],
                ':excerpt'          => $data['excerpt'],
                ':content'          => $data['content'],
                ':featured_image'   => $data['featured_image'],
                ':gallery'          => $gallery,
                ':status'           => $data['status'],
                ':author'           => $data['author'],
                ':meta_title'       => $data['meta_title'],
                ':meta_description' => $data['meta_description'],
                ':published_at'     => $data['published_at'],
                ':created_at'       => $now,
                ':updated_at'       => $now
            ]);
            $id = (int)$db->lastInsertId();
        }

        // Sync Categories
        $db->prepare("DELETE FROM nc_article_categories WHERE article_id = ?")->execute([$id]);
        if (!empty($category_ids)) {
            $catStmt = $db->prepare("INSERT INTO nc_article_categories (article_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cid) {
                $catStmt->execute([$id, (int)$cid]);
            }
        }

        // Sync Tags
        $db->prepare("DELETE FROM nc_article_tags WHERE article_id = ?")->execute([$id]);
        if (!empty($tag_names)) {
            $tagRelStmt = $db->prepare("INSERT OR IGNORE INTO nc_article_tags (article_id, tag_id) VALUES (?, ?)");
            foreach ($tag_names as $tname) {
                $tname = trim($tname);
                if ($tname === '') continue;
                $tag_id = nc_resolve_or_create_tag($tname);
                $tagRelStmt->execute([$id, $tag_id]);
            }
        }

        // Sync Authors
        $db->prepare("DELETE FROM nc_article_authors WHERE article_id = ?")->execute([$id]);
        if (!empty($author_ids)) {
            $authorStmt = $db->prepare("INSERT INTO nc_article_authors (article_id, author_id) VALUES (?, ?)");
            foreach ($author_ids as $aid) {
                $authorStmt->execute([$id, (int)$aid]);
            }
        }

        $db->commit();
        return $id;
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function nc_delete_article($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_articles WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

function nc_get_articles_admin($search = '', $status = '', $category_id = 0, $limit = 20, $offset = 0) {
    $db = nc_get_db();
    $conditions = [];
    $params = [];

    if (!empty($search)) {
        $conditions[] = "(a.title LIKE :search OR a.slug LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if (!empty($status)) {
        $conditions[] = "a.status = :status";
        $params[':status'] = $status;
    }

    if ($category_id > 0) {
        $conditions[] = "EXISTS (SELECT 1 FROM nc_article_categories ac WHERE ac.article_id = a.id AND ac.category_id = :cat_id)";
        $params[':cat_id'] = $category_id;
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countSql = "SELECT COUNT(*) FROM nc_articles a {$where}";
    $cStmt = $db->prepare($countSql);
    $cStmt->execute($params);
    $total = (int)$cStmt->fetchColumn();

    $sql = "SELECT a.* FROM nc_articles a {$where} ORDER BY a.published_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'total' => $total,
        'items' => $stmt->fetchAll()
    ];
}

/* ==========================================================================
   Taxonomies: Categories, Tags & Authors
   ========================================================================== */

function nc_get_categories_with_counts() {
    $db = nc_get_db();
    $sql = "
        SELECT c.*, COUNT(ac.article_id) AS article_count 
        FROM nc_categories c
        LEFT JOIN nc_article_categories ac ON c.id = ac.category_id
        GROUP BY c.id
        ORDER BY c.sort_order ASC, c.name ASC
    ";
    return $db->query($sql)->fetchAll();
}

function nc_save_category($name, $slug, $description = '', $id = 0) {
    $db = nc_get_db();
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE nc_categories SET name = ?, slug = ?, description = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $description, (int)$id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nc_categories (name, slug, description) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $slug, $description]);
    }
}

function nc_delete_category($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_categories WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

function nc_resolve_or_create_tag($name) {
    $db = nc_get_db();
    $slug = nc_sanitize_slug($name);

    $stmt = $db->prepare("SELECT id FROM nc_tags WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        return (int)$existing;
    }

    $insert = $db->prepare("INSERT INTO nc_tags (name, slug) VALUES (?, ?)");
    $insert->execute([$name, $slug]);
    return (int)$db->lastInsertId();
}

function nc_get_tags_with_counts() {
    $db = nc_get_db();
    $sql = "
        SELECT t.*, COUNT(at.article_id) AS article_count 
        FROM nc_tags t
        LEFT JOIN nc_article_tags at ON t.id = at.tag_id
        GROUP BY t.id
        ORDER BY t.name ASC
    ";
    return $db->query($sql)->fetchAll();
}

function nc_delete_tag($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_tags WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

function nc_get_authors_with_counts() {
    $db = nc_get_db();
    $sql = "
        SELECT au.*, COUNT(aa.article_id) AS article_count 
        FROM nc_authors au
        LEFT JOIN nc_article_authors aa ON au.id = aa.author_id
        GROUP BY au.id
        ORDER BY au.name ASC
    ";
    return $db->query($sql)->fetchAll();
}

function nc_save_author($name, $slug, $bio = '', $id = 0) {
    $db = nc_get_db();
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE nc_authors SET name = ?, slug = ?, bio = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $bio, (int)$id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nc_authors (name, slug, bio) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $slug, $bio]);
    }
}

function nc_delete_author($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_authors WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/* ==========================================================================
   Settings & Template Persistence
   ========================================================================== */

function nc_get_settings() {
    $db = nc_get_db();
    $rows = $db->query("SELECT setting_key, setting_value FROM nc_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    return $settings;
}

function nc_save_settings($settings = []) {
    $db = nc_get_db();
    $stmt = $db->prepare("INSERT OR REPLACE INTO nc_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
        $stmt->execute([$key, $val]);
    }
    return true;
}

function nc_save_template_content($template_key, $content) {
    $db = nc_get_db();
    $stmt = $db->prepare("UPDATE nc_templates SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE template_key = ?");
    return $stmt->execute([$content, $template_key]);
}