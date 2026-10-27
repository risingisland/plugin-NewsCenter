<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Return summary counts for the administrative dashboard metrics.
 */
function nc_get_dashboard_stats() {
    $db = nc_get_db();
    $now = date('Y-m-d H:i:s');

    return [
        'total'      => (int)$db->query("SELECT COUNT(*) FROM nc_articles")->fetchColumn(),
        'published'  => (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'published' AND published_at <= '{$now}'")->fetchColumn(),
        'scheduled'  => (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'published' AND published_at > '{$now}'")->fetchColumn(),
        'draft'      => (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'draft'")->fetchColumn(),
        'categories' => (int)$db->query("SELECT COUNT(*) FROM nc_categories")->fetchColumn(),
        'tags'       => (int)$db->query("SELECT COUNT(*) FROM nc_tags")->fetchColumn(),
        'authors'    => (int)$db->query("SELECT COUNT(*) FROM nc_authors")->fetchColumn()
    ];
}

/**
 * Fetch all categories with associated article counts.
 */
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

/**
 * Insert or update a category record.
 */
function nc_save_category($name, $slug, $description = '', $image = '', $id = 0) {
    $db = nc_get_db();
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE nc_categories SET name = ?, slug = ?, description = ?, image = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $description, $image, (int)$id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nc_categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $slug, $description, $image]);
    }
}

/**
 * Delete a category record by ID.
 */
function nc_delete_category($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_categories WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/**
 * Fetch all authors with associated article counts.
 */
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

/**
 * Insert or update an author record.
 */
function nc_save_author($name, $slug, $bio = '', $image = '', $id = 0) {
    $db = nc_get_db();
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE nc_authors SET name = ?, slug = ?, bio = ?, image = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, $bio, $image, (int)$id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nc_authors (name, slug, bio, image) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $slug, $bio, $image]);
    }
}

/**
 * Delete an author record by ID.
 */
function nc_delete_author($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_authors WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/**
 * Insert or update a tag record.
 */
function nc_save_tag($name, $slug, $id = 0) {
    $db = nc_get_db();
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE nc_tags SET name = ?, slug = ? WHERE id = ?");
        return $stmt->execute([$name, $slug, (int)$id]);
    } else {
        $stmt = $db->prepare("INSERT INTO nc_tags (name, slug) VALUES (?, ?)");
        return $stmt->execute([$name, $slug]);
    }
}

/**
 * Delete a tag record by ID.
 */
function nc_delete_tag($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_tags WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/**
 * Persist an article with related categories, tags, and authors in a single transaction.
 */
function nc_save_article($data, $categories = [], $tags = [], $authors = []) {
    $db = nc_get_db();
    $db->beginTransaction();

    try {
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if ($id > 0) {
            $stmt = $db->prepare("
                UPDATE nc_articles 
                SET title = :title, 
                    slug = :slug, 
                    content = :content, 
                    excerpt = :excerpt, 
                    status = :status, 
                    featured_image = :featured_image, 
                    gallery = :gallery, 
                    published_at = :published_at, 
                    author = :author, 
                    meta_title = :meta_title, 
                    meta_description = :meta_description, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $data[':id'] = $id;
        } else {
            $stmt = $db->prepare("
                INSERT INTO nc_articles (
                    title, slug, content, excerpt, status, 
                    featured_image, gallery, published_at, author, 
                    meta_title, meta_description, created_at, updated_at
                ) VALUES (
                    :title, :slug, :content, :excerpt, :status, 
                    :featured_image, :gallery, :published_at, :author, 
                    :meta_title, :meta_description, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ");
            unset($data['id']);
        }

        $params = [];
        foreach ($data as $k => $v) {
            $paramKey = (strpos($k, ':') === 0) ? $k : ':' . $k;
            $params[$paramKey] = $v;
        }

        $stmt->execute($params);

        if ($id <= 0) {
            $id = (int)$db->lastInsertId();
        }

        // 1. Sync Categories
        $db->prepare("DELETE FROM nc_article_categories WHERE article_id = ?")->execute([$id]);
        if (!empty($categories) && is_array($categories)) {
            $catStmt = $db->prepare("INSERT INTO nc_article_categories (article_id, category_id) VALUES (?, ?)");
            foreach ($categories as $catId) {
                if ((int)$catId > 0) {
                    $catStmt->execute([$id, (int)$catId]);
                }
            }
        }

        // 2. Sync Tags
        $db->prepare("DELETE FROM nc_article_tags WHERE article_id = ?")->execute([$id]);
        if (!empty($tags) && is_array($tags)) {
            $tagInsert = $db->prepare("INSERT OR IGNORE INTO nc_tags (name, slug) VALUES (?, ?)");
            $tagFind   = $db->prepare("SELECT id FROM nc_tags WHERE slug = ? LIMIT 1");
            $tagMap    = $db->prepare("INSERT INTO nc_article_tags (article_id, tag_id) VALUES (?, ?)");

            foreach ($tags as $rawTag) {
                $tagName = trim($rawTag);
                if ($tagName === '') continue;

                $tagSlug = nc_sanitize_slug($tagName);
                if ($tagSlug === '') continue;

                $tagInsert->execute([$tagName, $tagSlug]);
                $tagFind->execute([$tagSlug]);
                $tagId = $tagFind->fetchColumn();

                if ($tagId) {
                    $tagMap->execute([$id, (int)$tagId]);
                }
            }
        }

        // 3. Sync Authors
        $db->prepare("DELETE FROM nc_article_authors WHERE article_id = ?")->execute([$id]);
        if (!empty($authors) && is_array($authors)) {
            $authStmt = $db->prepare("INSERT INTO nc_article_authors (article_id, author_id) VALUES (?, ?)");
            foreach ($authors as $authId) {
                if ((int)$authId > 0) {
                    $authStmt->execute([$id, (int)$authId]);
                }
            }
        }

        $db->commit();
        return $id;
    } catch (\Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Delete an article record by ID. Cascading constraints clear related pivot tables.
 */
function nc_delete_article($id) {
    $db = nc_get_db();
    $stmt = $db->prepare("DELETE FROM nc_articles WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/**
 * Save custom template content to SQLite.
 */
function nc_save_template_content($key, $content) {
    $db = nc_get_db();
    $stmt = $db->prepare("
        INSERT INTO nc_templates (template_key, title, content, updated_at) 
        VALUES (:key, :key, :content, CURRENT_TIMESTAMP)
        ON CONFLICT(template_key) DO UPDATE SET 
            content = :content, 
            updated_at = CURRENT_TIMESTAMP
    ");
    return $stmt->execute([':key' => $key, ':content' => $content]);
}

/**
 * Retrieve configuration settings dictionary.
 */
function nc_get_settings() {
    $db = nc_get_db();
    $rows = $db->query("SELECT setting_key, setting_value FROM nc_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }
    return $settings;
}

/**
 * Persist configuration settings key-value pairs into SQLite.
 */
function nc_save_settings($settings) {
    $db = nc_get_db();
    $stmt = $db->prepare("INSERT OR REPLACE INTO nc_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, (string)$v]);
    }
    return true;
}