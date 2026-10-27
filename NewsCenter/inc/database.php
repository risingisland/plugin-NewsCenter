<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Returns active PDO SQLite connection instance.
 */
function nc_get_db() {
    static $db = null;
    if ($db === null) {
        if (!extension_loaded('pdo_sqlite')) {
            die('<h3>Error: PHP PDO SQLite extension is required for NewsCenter.</h3>');
        }

        if (!is_dir(NC_DATA_DIR)) {
            mkdir(NC_DATA_DIR, 0755, true);
            file_put_contents(NC_DATA_DIR . '.htaccess', "Deny from all\n<Files ~ \"\\.db$\">\nOrder deny,allow\nDeny from all\n</Files>");
        }

        $db = new PDO('sqlite:' . NC_DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA foreign_keys = ON;');
    }
    return $db;
}

/**
 * Initialize database tables, seed default templates, and migrate schema.
 */
function nc_init_database() {
    $db = nc_get_db();

    // 1. Core tables
    $schema = "
    CREATE TABLE IF NOT EXISTS nc_schema (version INTEGER PRIMARY KEY);
    CREATE TABLE IF NOT EXISTS nc_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT);
    CREATE TABLE IF NOT EXISTS nc_articles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT NOT NULL UNIQUE,
        title TEXT NOT NULL,
        excerpt TEXT,
        content TEXT NOT NULL,
        featured_image TEXT,
        gallery TEXT,
        status TEXT NOT NULL DEFAULT 'draft',
        author TEXT,
        meta_title TEXT,
        meta_description TEXT,
        published_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_nc_articles_status_pub ON nc_articles(status, published_at);
    CREATE INDEX IF NOT EXISTS idx_nc_articles_slug ON nc_articles(slug);

    CREATE TABLE IF NOT EXISTS nc_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        image TEXT,
        sort_order INTEGER DEFAULT 0
    );

    CREATE TABLE IF NOT EXISTS nc_article_categories (
        article_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        PRIMARY KEY (article_id, category_id),
        FOREIGN KEY (article_id) REFERENCES nc_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES nc_categories(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS nc_tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE
    );

    CREATE TABLE IF NOT EXISTS nc_article_tags (
        article_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        PRIMARY KEY (article_id, tag_id),
        FOREIGN KEY (article_id) REFERENCES nc_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES nc_tags(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS nc_authors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        bio TEXT,
        image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS nc_article_authors (
        article_id INTEGER NOT NULL,
        author_id INTEGER NOT NULL,
        PRIMARY KEY (article_id, author_id),
        FOREIGN KEY (article_id) REFERENCES nc_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (author_id) REFERENCES nc_authors(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS nc_templates (
        template_key TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $db->exec($schema);

    // Schema Migrations
    $art_cols = array_column($db->query("PRAGMA table_info(nc_articles)")->fetchAll(), 'name');
    if (!in_array('gallery', $art_cols, true)) {
        $db->exec("ALTER TABLE nc_articles ADD COLUMN gallery TEXT;");
    }

    $cat_cols = array_column($db->query("PRAGMA table_info(nc_categories)")->fetchAll(), 'name');
    if (!in_array('image', $cat_cols, true)) {
        $db->exec("ALTER TABLE nc_categories ADD COLUMN image TEXT;");
    }

    $author_cols = array_column($db->query("PRAGMA table_info(nc_authors)")->fetchAll(), 'name');
    if (!in_array('image', $author_cols, true)) {
        $db->exec("ALTER TABLE nc_authors ADD COLUMN image TEXT;");
    }

    // Default Author Seeding
    $authorCount = (int)$db->query("SELECT COUNT(*) FROM nc_authors")->fetchColumn();
    if ($authorCount === 0) {
        $defaultName = defined('USR') && USR ? USR : 'Admin';
        $defaultSlug = strtolower(preg_replace('/[^a-z0-9-_]/', '', $defaultName));
        $stmt = $db->prepare("INSERT INTO nc_authors (name, slug, bio, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$defaultName, $defaultSlug ?: 'admin', 'Default site author.', '']);
    }

    // Default Settings Seeding
    $settingCount = (int)$db->query("SELECT COUNT(*) FROM nc_settings")->fetchColumn();
    if ($settingCount === 0) {
        $stmt = $db->prepare("INSERT INTO nc_settings (setting_key, setting_value) VALUES (?, ?)");
        $defaults = [
            'page_slug'        => 'news',
            'posts_per_page'   => '10',
            'date_format'      => 'F j, Y',
            'excerpt_length'   => '250',
            'sitemap_priority' => '0.8',
            'trailing_slash'   => '1'
        ];
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
        nc_reset_default_templates();
    }
}