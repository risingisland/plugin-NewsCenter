<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$search      = isset($_GET['q']) ? trim($_GET['q']) : '';
$status      = isset($_GET['status_filter']) && in_array($_GET['status_filter'], ['published', 'draft']) ? $_GET['status_filter'] : '';
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$page        = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit       = 15;
$offset      = ($page - 1) * $limit;

$data = nc_get_articles_admin($search, $status, $category_id, $limit, $offset);
$articles = $data['items'];
$total_items = $data['total'];
$total_pages = ceil($total_items / $limit);

$categories = nc_get_categories_with_counts();
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/TAB_ARTICLES') ?></h2>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article" class="nc-btn nc-btn-primary">+ <?= i18n_r('NewsCenter/NEW_ARTICLE') ?></a>
  </div>

  <div class="nc-card nc-filter-bar">
    <form method="GET" action="load.php" class="nc-filter-form">
      <input type="hidden" name="id" value="<?= NC_PLUGIN_ID ?>">
      <input type="hidden" name="action" value="articles">

      <div class="nc-filter-field">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="<?= i18n_r('NewsCenter/SEARCH_PLACEHOLDER') ?>" class="nc-input">
      </div>

      <div class="nc-filter-field">
        <select name="status_filter" class="nc-select">
          <option value=""><?= i18n_r('NewsCenter/ALL_STATUSES') ?></option>
          <option value="published" <?= ($status === 'published') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/PUBLISHED') ?></option>
          <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>><?= i18n_r('NewsCenter/DRAFT') ?></option>
        </select>
      </div>

      <div class="nc-filter-field">
        <select name="category_id" class="nc-select">
          <option value="0"><?= i18n_r('NewsCenter/ALL_CATEGORIES') ?></option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= ($category_id === (int)$cat['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?> (<?= (int)$cat['article_count'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="nc-filter-actions">
        <button type="submit" class="nc-btn nc-btn-secondary"><?= i18n_r('NewsCenter/FILTER') ?></button>
        <?php if (!empty($search) || !empty($status) || $category_id > 0): ?>
          <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/RESET') ?></a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="nc-card nc-table-card">
    <?php if (!empty($articles)): ?>
      <table class="nc-table">
        <thead>
          <tr>
            <th><?= i18n_r('NewsCenter/TITLE') ?></th>
            <th><?= i18n_r('NewsCenter/STATUS') ?></th>
            <th><?= i18n_r('NewsCenter/AUTHOR') ?></th>
            <th><?= i18n_r('NewsCenter/DATE') ?></th>
            <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($articles as $art): ?>
            <tr>
              <td>
                <div class="nc-article-title-row">
                  <strong>
                    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>">
                      <?= htmlspecialchars($art['title']) ?>
                    </a>
                  </strong>
                  <?php if (!empty($art['featured_image'])): ?>
                    <span class="nc-badge-thumb" title="<?= i18n_r('NewsCenter/HAS_IMAGE') ?>">&#128247;</span>
                  <?php endif; ?>
                </div>
                <div class="nc-subtext">/<?= htmlspecialchars($art['slug']) ?></div>
              </td>
              <td>
                <span class="nc-badge-status nc-status-<?= htmlspecialchars($art['status']) ?>">
                  <?= htmlspecialchars(ucfirst($art['status'])) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($art['author'] ?: 'admin') ?></td>
              <td>
                <time datetime="<?= htmlspecialchars($art['published_at']) ?>">
                  <?= date('Y-m-d H:i', strtotime($art['published_at'])) ?>
                </time>
              </td>
              <td class="nc-text-right nc-actions-cell">
                <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>">&#9998;</a>
                <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles&do=delete&article_id=<?= (int)$art['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                   class="nc-btn-action nc-btn-action-delete" 
                   title="<?= i18n_r('NewsCenter/DELETE') ?>" 
                   onclick="return confirm('<?= i18n_r('NewsCenter/CONFIRM_DELETE_ARTICLE') ?>');">&#128465;</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($total_pages > 1): ?>
        <div class="nc-pagination">
          <?php
          $query_params = $_GET;
          for ($i = 1; $i <= $total_pages; $i++):
            $query_params['p'] = $i;
            $page_url = 'load.php?' . http_build_query($query_params);
          ?>
            <a href="<?= htmlspecialchars($page_url) ?>" class="nc-page-link <?= ($i === $page) ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="nc-empty-state">
        <p><?= i18n_r('NewsCenter/NO_ARTICLES_FOUND') ?></p>
        <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article" class="nc-btn nc-btn-primary">+ <?= i18n_r('NewsCenter/CREATE_FIRST_ARTICLE') ?></a>
      </div>
    <?php endif; ?>
  </div>
</div>