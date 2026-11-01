<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$now = date('Y-m-d H:i:s');

// Metric counts
$total = (int)$db->query("SELECT COUNT(*) FROM nc_articles")->fetchColumn();
$published = (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'published' AND published_at <= '{$now}'")->fetchColumn();
$scheduled = (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'published' AND published_at > '{$now}'")->fetchColumn();
$draft = (int)$db->query("SELECT COUNT(*) FROM nc_articles WHERE status = 'draft'")->fetchColumn();
$categories = (int)$db->query("SELECT COUNT(*) FROM nc_categories")->fetchColumn();
$authors = (int)$db->query("SELECT COUNT(*) FROM nc_authors")->fetchColumn();

// Recent articles
$recent_articles = $db->query("SELECT * FROM nc_articles ORDER BY created_at DESC LIMIT 5")->fetchAll();
$settings = nc_get_settings();
$date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'M j, Y';
?>

<div class="nc-container">
  <!-- Metrics Overview Cards -->
  <div class="nc-stats-grid">
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= $total ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/TOTAL_ARTICLES') ?></div>
    </div>
    <div class="nc-stat-card nc-stat-published">
      <div class="nc-stat-number"><?= $published ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/PUBLISHED') ?></div>
    </div>
    <div class="nc-stat-card nc-stat-scheduled">
      <div class="nc-stat-number" style="color:#4f46e5;"><?= $scheduled ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/SCHEDULED') ?></div>
    </div>
    <div class="nc-stat-card nc-stat-draft">
      <div class="nc-stat-number"><?= $draft ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/DRAFT') ?></div>
    </div>
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= $categories ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/TAB_CATEGORIES') ?></div>
    </div>
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= $authors ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/AUTHORS') ?></div>
    </div>
  </div>

  <!-- Recent Articles Table -->
  <div class="nc-card">
    <div class="nc-card-header">
      <h3><?= i18n_r('NewsCenter/RECENT_ARTICLES') ?></h3>
      <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-sublink"><?= i18n_r('NewsCenter/VIEW_ALL') ?> &rarr;</a>
    </div>

    <?php if (!empty($recent_articles)): ?>
      <table class="nc-table">
        <thead>
          <tr>
            <th><?= i18n_r('NewsCenter/TITLE') ?></th>
            <th><?= i18n_r('NewsCenter/AUTHOR') ?></th>
            <th><?= i18n_r('NewsCenter/STATUS') ?></th>
            <th><?= i18n_r('NewsCenter/PUB_DATE') ?></th>
            <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_articles as $art): ?>
            <?php
              $gallery_items = !empty($art['gallery']) ? json_decode($art['gallery'], true) : [];
              $has_gallery = !empty($gallery_items) && is_array($gallery_items) && count($gallery_items) > 0;
              $is_future = strtotime($art['published_at']) > time();
              $author_data = nc_get_article_authors_data($art['id'], $art['author']);

              if ($art['status'] === 'published' && $is_future) {
                  $status_label = i18n_r('NewsCenter/SCHEDULED');
                  $status_class = 'nc-status-scheduled';
              } elseif ($art['status'] === 'published') {
                  $status_label = i18n_r('NewsCenter/PUBLISHED');
                  $status_class = 'nc-status-published';
              } else {
                  $status_label = i18n_r('NewsCenter/DRAFT');
                  $status_class = 'nc-status-draft';
              }
            ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>" style="font-weight:600; color:var(--nc-text); text-decoration:none;">
                    <?= htmlspecialchars($art['title']) ?>
                  </a>
                  <?php if ($has_gallery): ?>
                    <span class="nc-gallery-indicator" title="<?= i18n_r('NewsCenter/GALLERY') ?>: <?= count($gallery_items) ?>"> <?= $svgImg . count($gallery_items) ?></span>
                  <?php endif; ?>
                </div>
                <!--div class="nc-subtext">/<?= htmlspecialchars($art['slug']) ?></div-->
              </td>
              <td>
                <span class="nc-authors-list" style="font-size:13px; color:#475569;">
                  <?= htmlspecialchars($author_data['author_name']) ?>
                </span>
              </td>
              <td>
                <span class="nc-badge-status <?= $status_class ?>"><?= $status_label ?></span>
              </td>
              <td><?= nc_date($date_format, strtotime($art['published_at'])) ?></td>
              <td class="nc-text-right nc-actions-cell">
                <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>"><?= $svgEdit ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_ARTICLES_FOUND') ?>. <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article"><?= i18n_r('NewsCenter/ADD_ARTICLE') ?></a></p>
    <?php endif; ?>
  </div>
</div>