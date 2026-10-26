<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

// Determine active action for tab highlighting
$current_action = 'dashboard';
if (!empty($_GET['action'])) {
    $current_action = nc_sanitize_slug($_GET['action']);
} else {
    $known_actions = ['dashboard', 'articles', 'edit-article', 'categories', 'tags', 'authors', 'templates', 'settings', 'docs'];
    foreach ($known_actions as $candidate) {
        if (isset($_GET[$candidate])) {
            $current_action = $candidate;
            break;
        }
    }
}
?>

<div class="nc-wrap">
  <div class="nc-main-header">
    <div class="nc-brand">
      <h1>NewsCenter <span class="nc-badge">v<?= NC_VERSION ?></span></h1>
    </div>
    <div class="nc-quick-status">
      <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=edit-article" class="nc-btn nc-btn-primary">+ <?= i18n_r('NewsCenter/NEW_ARTICLE') ?></a>
    </div>
  </div>

  <!-- NewsCenter Tab Navigation Bar -->
  <nav class="nc-tabs-bar">
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=dashboard" class="nc-tab <?= ($current_action === 'dashboard') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_DASHBOARD') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=articles" class="nc-tab <?= (in_array($current_action, ['articles', 'edit-article'])) ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_ARTICLES') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories" class="nc-tab <?= ($current_action === 'categories') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_CATEGORIES') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags" class="nc-tab <?= ($current_action === 'tags') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_TAGS') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors" class="nc-tab <?= ($current_action === 'authors') ? 'active' : '' ?>">Authors</a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=templates" class="nc-tab <?= ($current_action === 'templates') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_TEMPLATES') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=settings" class="nc-tab <?= ($current_action === 'settings') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_SETTINGS') ?></a>
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=docs" class="nc-tab <?= ($current_action === 'docs') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_DOCS') ?></a>
  </nav>

  <?php if (isset($_GET['status']) && $_GET['status'] === 'saved'): ?>
    <div class="nc-alert nc-alert-success"><?= i18n_r('NewsCenter/MSG_SAVED_SUCCESS') ?></div>
  <?php elseif (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
    <div class="nc-alert nc-alert-info"><?= i18n_r('NewsCenter/MSG_DELETED_SUCCESS') ?></div>
  <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
    <div class="nc-alert nc-alert-error"><?= i18n_r('NewsCenter/MSG_ERROR') ?></div>
  <?php endif; ?>

  <div class="nc-content-body">