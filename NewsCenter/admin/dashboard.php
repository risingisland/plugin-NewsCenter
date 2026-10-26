<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$stats = nc_get_dashboard_stats();
$recent_articles = nc_get_recent_articles(5);
?>

<div class="nc-dashboard">
  <!-- Key Metrics Row -->
  <div class="nc-stats-grid">
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= (int)$stats['total'] ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/TOTAL_ARTICLES') ?></div>
    </div>
    <div class="nc-stat-card nc-stat-published">
      <div class="nc-stat-number"><?= (int)$stats['published'] ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/PUBLISHED_ARTICLES') ?></div>
    </div>
    <div class="nc-stat-card nc-stat-draft">
      <div class="nc-stat-number"><?= (int)$stats['draft'] ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/DRAFT_ARTICLES') ?></div>
    </div>
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= (int)$stats['categories'] ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/TOTAL_CATEGORIES') ?></div>
    </div>
    <div class="nc-stat-card">
      <div class="nc-stat-number"><?= (int)$stats['tags'] ?></div>
      <div class="nc-stat-label"><?= i18n_r('NewsCenter/TOTAL_TAGS') ?></div>
    </div>
  </div>

  <div class="nc-dashboard-layout">
    <!-- Recent Articles Table -->
    <div class="nc-dashboard-main">
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/RECENT_ARTICLES') ?></h3>
          <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-btn nc-btn-small nc-btn-secondary"><?= i18n_r('NewsCenter/VIEW_ALL') ?></a>
        </div>

        <?php if (!empty($recent_articles)): ?>
          <table class="nc-table">
            <thead>
              <tr>
                <th><?= i18n_r('NewsCenter/TITLE') ?></th>
                <th><?= i18n_r('NewsCenter/STATUS') ?></th>
                <th><?= i18n_r('NewsCenter/DATE') ?></th>
                <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_articles as $art): ?>
                <tr>
                  <td>
                    <strong>
                      <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>">
                        <?= htmlspecialchars($art['title']) ?>
                      </a>
                    </strong>
                    <div class="nc-subtext">/<?= htmlspecialchars($art['slug']) ?></div>
                  </td>
                  <td>
                    <span class="nc-badge-status nc-status-<?= htmlspecialchars($art['status']) ?>">
                      <?= htmlspecialchars(ucfirst($art['status'])) ?>
                    </span>
                  </td>
                  <td><?= date('Y-m-d H:i', strtotime($art['published_at'])) ?></td>
                  <td class="nc-text-right">
                    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article&article_id=<?= (int)$art['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>">&#9998;</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_ARTICLES_YET') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Action Sidebar Panel -->
    <div class="nc-dashboard-sidebar">
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/QUICK_ACTIONS') ?></h3>
        </div>
        <ul class="nc-action-list">
          <li>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article" class="nc-btn nc-btn-primary nc-btn-block">
              + <?= i18n_r('NewsCenter/NEW_ARTICLE') ?>
            </a>
          </li>
          <li>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories" class="nc-btn nc-btn-secondary nc-btn-block">
              <?= i18n_r('NewsCenter/MANAGE_CATEGORIES') ?>
            </a>
          </li>
          <li>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags" class="nc-btn nc-btn-secondary nc-btn-block">
              <?= i18n_r('NewsCenter/MANAGE_TAGS') ?>
            </a>
          </li>
          <li>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=templates" class="nc-btn nc-btn-secondary nc-btn-block">
              <?= i18n_r('NewsCenter/EDIT_TEMPLATES') ?>
            </a>
          </li>
        </ul>
      </div>

      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/SYSTEM_STATUS') ?></h3>
        </div>
        <ul class="nc-status-info">
          <li><span><?= i18n_r('NewsCenter/STORAGE_ENGINE') ?>:</span> <strong>SQLite 3</strong></li>
          <li><span><?= i18n_r('NewsCenter/DATABASE_SIZE') ?>:</span> <strong><?= file_exists(NC_DB_FILE) ? nc_format_file_size(filesize(NC_DB_FILE)) : '0 B' ?></strong></li>
          <li><span><?= i18n_r('NewsCenter/VERSION') ?>:</span> <strong><?= NC_VERSION ?></strong></li>
        </ul>
      </div>
    </div>
  </div>
</div>