<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(title LIKE :q OR slug LIKE :q)";
    $params[':q'] = '%' . $search . '%';
}

if ($status_filter === 'published') {
    $where[] = "status = 'published' AND published_at <= CURRENT_TIMESTAMP";
} elseif ($status_filter === 'scheduled') {
    $where[] = "status = 'published' AND published_at > CURRENT_TIMESTAMP";
} elseif ($status_filter === 'draft') {
    $where[] = "status = 'draft'";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT * FROM nc_articles {$where_sql} ORDER BY published_at DESC");
$stmt->execute($params);
$articles = $stmt->fetchAll();

$settings = nc_get_settings();
$date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'M j, Y';
?>

<div class="nc-container">
  <div class="nc-page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h2><?= i18n_r('NewsCenter/TAB_ARTICLES') ?></h2>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article" class="nc-btn nc-btn-primary">+ <?= i18n_r('NewsCenter/NEW_ARTICLE') ?></a>
  </div>

  <div class="nc-card">
    <!-- Filter bar -->
    <form method="GET" action="load.php" style="display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; align-items:center;">
      <input type="hidden" name="id" value="<?= NC_PLUGIN_ID ?>">
      <input type="hidden" name="action" value="articles">

      <input type="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="<?= i18n_r('NewsCenter/SEARCH_ARTICLES') ?>" class="nc-input" style="max-width:280px;">

      <select name="status_filter" class="nc-select" style="max-width:180px;">
        <option value=""><?= i18n_r('NewsCenter/ALL_STATUSES') ?></option>
        <option value="published" <?= ($status_filter === 'published') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/PUBLISHED') ?></option>
        <option value="scheduled" <?= ($status_filter === 'scheduled') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/SCHEDULED') ?></option>
        <option value="draft" <?= ($status_filter === 'draft') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/DRAFT') ?></option>
      </select>

      <button type="submit" class="nc-btn nc-btn-secondary"><?= i18n_r('NewsCenter/FILTER') ?></button>
      <?php if (!empty($search) || !empty($status_filter)): ?>
        <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/RESET') ?></a>
      <?php endif; ?>
    </form>

    <?php if (!empty($articles)): ?>
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
          <?php foreach ($articles as $art): ?>
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
                <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles&do=delete&article_id=<?= (int)$art['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                   class="nc-btn-action nc-btn-action-delete" 
                   title="<?= i18n_r('NewsCenter/DELETE') ?>"
                   onclick="return confirm('<?= i18n_r('NewsCenter/CONFIRM_DELETE_ARTICLE') ?>');"><?= $svgDelete ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_ARTICLES_FOUND') ?>.</p>
    <?php endif; ?>
  </div>
</div>