<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
$article = [
    'id' => 0, 'slug' => '', 'title' => '', 'excerpt' => '', 'content' => '',
    'featured_image' => '', 'gallery' => '[]', 'status' => 'draft', 'author' => defined('USR') ? USR : 'admin',
    'meta_title' => '', 'meta_description' => '', 'published_at' => date('Y-m-d H:i')
];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM nc_articles WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    if ($res) { $article = $res; }
}

$cats = $db->query("SELECT * FROM nc_categories ORDER BY name ASC")->fetchAll();
$tags = $db->query("SELECT * FROM nc_tags ORDER BY name ASC")->fetchAll();
$all_authors = $db->query("SELECT * FROM nc_authors ORDER BY name ASC")->fetchAll();

$assigned_cats = [];
$assigned_tags = [];
$assigned_authors = [];

if ($id > 0) {
    $c_stmt = $db->prepare("SELECT category_id FROM nc_article_categories WHERE article_id = ?");
    $c_stmt->execute([$id]);
    $assigned_cats = $c_stmt->fetchAll(PDO::FETCH_COLUMN);

    $t_stmt = $db->prepare("
        SELECT t.name FROM nc_tags t 
        JOIN nc_article_tags at ON t.id = at.tag_id 
        WHERE at.article_id = ?
    ");
    $t_stmt->execute([$id]);
    $assigned_tags = $t_stmt->fetchAll(PDO::FETCH_COLUMN);

    $a_stmt = $db->prepare("SELECT author_id FROM nc_article_authors WHERE article_id = ?");
    $a_stmt->execute([$id]);
    $assigned_authors = $a_stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    if (!empty($all_authors)) {
        $assigned_authors = [(int)$all_authors[0]['id']];
    }
}

// Determine initial status text
$is_future = strtotime($article['published_at']) > time();
if ($article['status'] === 'published' && $is_future) {
    $current_status_label = 'Scheduled';
} elseif ($article['status'] === 'published') {
    $current_status_label = 'Published';
} else {
    $current_status_label = 'Draft';
}
?>

<link rel="stylesheet" href="<?= NC_URL ?>assets/vendor/autumnnote/autumnnote.css">

<div class="nc-container nc-edit-article-wrapper">
  <div class="nc-page-header">
    <h2><?= ($id > 0) ? i18n_r('NewsCenter/EDIT_ARTICLE') : i18n_r('NewsCenter/ADD_ARTICLE') ?></h2>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-btn nc-btn-secondary">&larr; <?= i18n_r('NewsCenter/BACK_TO_ARTICLES') ?></a>
  </div>

  <form id="nc-article-form" action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_article" method="POST">
    <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$article['id'] ?>">
    <input type="hidden" name="gallery" id="nc-gallery-data" value="<?= htmlspecialchars($article['gallery'] ?: '[]') ?>">

    <!-- ROW 1: [Title / URL Slug] [Featured Image] [Publishing Options] -->
    <div class="nc-row-grid nc-row-top">
      <!-- Col 1: Title & Slug -->
      <div class="nc-card nc-col-title-slug">
        <div class="nc-form-group">
          <label for="nc-title-input"><?= i18n_r('NewsCenter/TITLE') ?></label>
          <input type="text" id="nc-title-input" name="title" value="<?= htmlspecialchars($article['title']) ?>" class="nc-input" required placeholder="Enter article title...">
        </div>
        <div class="nc-form-group" style="margin-bottom:0;">
          <label for="nc-slug-input"><?= i18n_r('NewsCenter/SLUG') ?></label>
          <input type="text" id="nc-slug-input" name="slug" value="<?= htmlspecialchars($article['slug']) ?>" class="nc-input" required placeholder="article-slug">
        </div>
      </div>

      <!-- Col 2: Featured Image -->
      <div class="nc-card nc-col-featured">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/FEATURED_IMAGE') ?></h3>
        </div>
        <input type="hidden" id="nc-featured-image-input" name="featured_image" value="<?= htmlspecialchars($article['featured_image']) ?>">
        
        <div class="nc-featured-preview-box" id="nc-featured-thumb-wrap">
          <img id="nc-featured-thumb-img" class="nc-featured-thumb-img" src="<?= htmlspecialchars($article['featured_image']) ?>" alt="Featured Thumbnail" style="<?= empty($article['featured_image']) ? 'display:none;' : 'display:block;' ?>">
          <div id="nc-featured-placeholder" class="nc-featured-empty" style="<?= !empty($article['featured_image']) ? 'display:none;' : 'display:flex;' ?>">
            <span>No image selected</span>
          </div>
          <button type="button" class="nc-featured-remove-btn" id="nc-featured-remove-btn" title="Remove Image" style="<?= empty($article['featured_image']) ? 'display:none;' : 'display:block;' ?>">&times;</button>
        </div>

        <button type="button" id="nc-select-featured-btn" class="nc-btn nc-btn-small nc-btn-secondary" style="margin-top:10px; width:100%;">
          <?= !empty($article['featured_image']) ? 'Change Image' : i18n_r('NewsCenter/CHOOSE_IMAGE') ?>
        </button>
      </div>

      <!-- Col 3: Publishing Options -->
      <div class="nc-card nc-col-publishing">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/PUBLISHING') ?></h3>
        </div>
        <div class="nc-form-group">
          <label><?= i18n_r('NewsCenter/STATUS') ?></label>
          <select name="status" id="nc-status-select" class="nc-select">
            <option value="draft" <?= ($article['status'] === 'draft') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/DRAFT') ?></option>
            <option value="published" <?= ($article['status'] === 'published') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/PUBLISHED') ?></option>
          </select>
        </div>

        <div class="nc-form-group">
          <label><?= i18n_r('NewsCenter/PUB_DATE') ?></label>
          <input type="datetime-local" id="nc-published-at-input" name="published_at" value="<?= date('Y-m-d\TH:i', strtotime($article['published_at'])) ?>" class="nc-input">
        </div>

        <button type="submit" class="nc-btn nc-btn-primary nc-btn-block"><?= i18n_r('NewsCenter/SAVE_ARTICLE') ?></button>
      </div>
    </div>

    <!-- MAIN WYSIWYG EDITOR -->
    <div class="nc-card nc-row-editor">
      <div class="nc-form-group" style="margin-bottom:0;">
        <label><?= i18n_r('NewsCenter/CONTENT') ?></label>
        <textarea id="nc-article-content" name="content"><?= htmlspecialchars($article['content']) ?></textarea>
      </div>
    </div>

    <!-- ROW 2: [Authors] [Categories] [All Tags] -->
    <div class="nc-row-grid nc-row-taxonomies">
      <!-- Authors -->
      <div class="nc-card">
        <div class="nc-card-header">
          <h3>Authors</h3>
          <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors" class="nc-sublink">Manage</a>
        </div>
        <div class="nc-checkbox-list nc-scroll-box">
          <?php if (!empty($all_authors)): ?>
            <?php foreach ($all_authors as $au): ?>
              <label class="nc-checkbox-label">
                <input type="checkbox" name="authors[]" value="<?= (int)$au['id'] ?>" <?= in_array((int)$au['id'], $assigned_authors, true) ? 'checked' : '' ?>>
                <?= htmlspecialchars($au['name']) ?>
              </label>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="nc-subtext">No authors. <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors">Add author</a></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Categories -->
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/CATEGORIES') ?></h3>
          <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories" class="nc-sublink">Manage</a>
        </div>
        <div class="nc-checkbox-list nc-scroll-box">
          <?php if (!empty($cats)): ?>
            <?php foreach ($cats as $c): ?>
              <label class="nc-checkbox-label">
                <input type="checkbox" name="categories[]" value="<?= $c['id'] ?>" <?= in_array($c['id'], $assigned_cats) ? 'checked' : '' ?>> 
                <?= htmlspecialchars($c['name']) ?>
              </label>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="nc-subtext">No categories. <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories">Add category</a></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Tags -->
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/ALL_TAGS') ?></h3>
          <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags" class="nc-sublink">Manage</a>
        </div>
        <div class="nc-form-group" style="margin-bottom:0;">
          <label for="nc-tags-input">Tags (comma-separated)</label>
          <input type="text" id="nc-tags-input" name="tags" value="<?= htmlspecialchars(implode(', ', $assigned_tags)) ?>" class="nc-input" placeholder="news, updates, general">
          <span class="nc-help-text">Separate tags with commas.</span>
        </div>
      </div>
    </div>

    <!-- ROW 3: [Article Gallery with Drag Sorting] -->
    <div class="nc-card nc-row-gallery">
      <div class="nc-gallery-panel-header">
        <div>
          <h3 style="margin:0 0 2px 0;">Article Gallery</h3>
          <p style="margin:0; font-size:12px; color:var(--nc-text-muted);">
            Drag cards by the &#9776; handle to reorder images. Images display with GLightbox on the article page.
          </p>
        </div>
        <button type="button" id="nc-open-media-modal" class="nc-btn-add-images">+ Add Images</button>
      </div>
      <div id="nc-gallery-cards" class="nc-gallery-cards-grid"></div>
    </div>

    <!-- ROW 4: [Excerpt] [SEO Options] -->
    <div class="nc-row-grid nc-row-bottom">
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/EXCERPT') ?></h3>
        </div>
        <div class="nc-form-group" style="margin-bottom:0;">
          <textarea name="excerpt" id="excerpt" class="nc-textarea" rows="4" placeholder="Optional short summary..."><?= htmlspecialchars($article['excerpt']) ?></textarea>
        </div>
      </div>

      <div class="nc-card">
        <div class="nc-card-header">
          <h3>SEO Options</h3>
        </div>
        <div class="nc-form-group">
          <label for="meta_title">Meta Title</label>
          <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($article['meta_title'] ?? '') ?>" class="nc-input" placeholder="Custom SEO browser title">
        </div>
        <div class="nc-form-group" style="margin-bottom:0;">
          <label for="meta_description">Meta Description</label>
          <textarea id="meta_description" name="meta_description" class="nc-textarea" rows="2" placeholder="Search engine description..."><?= htmlspecialchars($article['meta_description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Floating Sticky Bottom Bar for Save Button -->
    <div class="nc-sticky-save-bar" id="nc-sticky-bar">
      <span class="nc-status-indicator" id="nc-bar-status-text">Status: <?= $current_status_label ?></span>
      <button type="submit" class="nc-btn nc-btn-primary" style="padding:10px 24px; font-size:14px;">&#128190; <?= i18n_r('NewsCenter/SAVE_ARTICLE') ?></button>
    </div>
  </form>
</div>

<!-- Integrated Media Browser & Uploader Modal -->
<div id="nc-media-modal" class="nc-modal" style="display:none;" data-csrf="<?= nc_csrf_token() ?>">
  <div class="nc-modal-content">
    <div class="nc-modal-header">
      <h4><?= i18n_r('NewsCenter/CHOOSE_IMAGES') ?></h4>
      <span class="nc-modal-close" id="nc-modal-x-close">&times;</span>
    </div>

    <div class="nc-modal-tabs">
      <button type="button" class="nc-modal-tab active" data-tab="browse">🖼️ Browse Uploads</button>
      <button type="button" class="nc-modal-tab" data-tab="upload">⬆️ Upload Images</button>
    </div>

    <div class="nc-modal-pane" id="nc-pane-browse">
      <div style="margin-bottom: 12px;">
        <input type="search" id="nc-media-search" class="nc-input" placeholder="Search images by filename..." style="padding:6px 10px; font-size:13px;">
      </div>
      <div class="nc-modal-body" id="nc-modal-file-list">
        <p>Loading media...</p>
      </div>
    </div>

    <div class="nc-modal-pane" id="nc-pane-upload" style="display:none;">
      <label class="nc-dropzone" id="nc-dropzone">
        <span style="font-size: 28px; display:block; margin-bottom:8px;">📁</span>
        <strong>Click or drop images here to upload</strong>
        <small style="color:var(--nc-text-muted); display:block; margin-top:4px;">Supports JPG, PNG, GIF, WebP</small>
        <input type="file" id="nc-file-input" multiple accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
      </label>
      <div id="nc-upload-status" style="margin-top:12px; font-size:13px;"></div>
    </div>

    <div class="nc-modal-footer">
      <span id="nc-selection-info" style="margin-right:auto; font-size:13px; color:var(--nc-text-muted);">0 images selected</span>
      <button type="button" class="nc-btn nc-btn-outline nc-modal-close-btn"><?= i18n_r('NewsCenter/CANCEL') ?></button>
      <button type="button" id="nc-insert-gallery-btn" class="nc-btn nc-btn-primary" disabled>Add to Gallery</button>
    </div>
  </div>
</div>

<script src="<?= NC_URL ?>assets/vendor/autumnnote/autumnnote.umd.js"></script>
<script src="<?= NC_URL ?>assets/js/articles.js?v=<?= NC_VERSION ?>.<?= time() ?>"></script>