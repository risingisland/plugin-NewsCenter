<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$settings = nc_get_settings();

$page_slug        = $settings['page_slug'] ?? 'news';
$posts_per_page   = $settings['posts_per_page'] ?? '10';
$date_format      = $settings['date_format'] ?? 'F j, Y';
$excerpt_length   = $settings['excerpt_length'] ?? '250';
$custom_css       = $settings['custom_css'] ?? '';
$sitemap_priority = $settings['sitemap_priority'] ?? '0.8';
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/TAB_SETTINGS') ?></h2>
  </div>

  <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_settings" method="POST" class="nc-form-grid">
    <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">

    <div class="nc-main-col">
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/GENERAL_SETTINGS') ?></h3>
        </div>

        <div class="nc-form-group">
          <label for="page_slug"><?= i18n_r('NewsCenter/SETTING_PAGE_SLUG') ?></label>
          <input type="text" id="page_slug" name="page_slug" value="<?= htmlspecialchars($page_slug) ?>" class="nc-input" required>
          <span class="nc-help-text"><?= i18n_r('NewsCenter/SETTING_PAGE_SLUG_HELP') ?></span>
        </div>

        <div class="nc-form-row">
          <div class="nc-form-group nc-col-half">
            <label for="posts_per_page"><?= i18n_r('NewsCenter/SETTING_POSTS_PER_PAGE') ?></label>
            <input type="number" id="posts_per_page" name="posts_per_page" value="<?= (int)$posts_per_page ?>" min="1" max="100" class="nc-input" required>
          </div>

          <div class="nc-form-group nc-col-half">
            <label for="excerpt_length"><?= i18n_r('NewsCenter/SETTING_EXCERPT_LENGTH') ?></label>
            <input type="number" id="excerpt_length" name="excerpt_length" value="<?= (int)$excerpt_length ?>" min="50" max="1000" class="nc-input" required>
          </div>
        </div>

        <div class="nc-form-group">
          <label for="date_format"><?= i18n_r('NewsCenter/SETTING_DATE_FORMAT') ?></label>
          <input type="text" id="date_format" name="date_format" value="<?= htmlspecialchars($date_format) ?>" class="nc-input" required>
          <span class="nc-help-text"><?= i18n_r('NewsCenter/SETTING_DATE_FORMAT_HELP') ?> (<code><?= date($date_format) ?></code>)</span>
        </div>
      </div>

      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/CUSTOM_CSS') ?></h3>
        </div>
        <p class="nc-help-text"><?= i18n_r('NewsCenter/CUSTOM_CSS_HELP') ?></p>
        <div class="nc-form-group">
          <textarea id="custom_css" name="custom_css" class="nc-textarea nc-code-textarea" rows="10" placeholder=":root { --nc-primary: #1e70bf; }"><?= htmlspecialchars($custom_css) ?></textarea>
        </div>
      </div>
    </div>

    <div class="nc-side-col">
      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/SEO_AND_FEEDS') ?></h3>
        </div>

        <div class="nc-form-group">
          <label for="sitemap_priority"><?= i18n_r('NewsCenter/SITEMAP_PRIORITY') ?></label>
          <select id="sitemap_priority" name="sitemap_priority" class="nc-select">
            <option value="1.0" <?= ($sitemap_priority === '1.0') ? 'selected' : '' ?>>1.0 (Highest)</option>
            <option value="0.8" <?= ($sitemap_priority === '0.8') ? 'selected' : '' ?>>0.8 (High)</option>
            <option value="0.6" <?= ($sitemap_priority === '0.6') ? 'selected' : '' ?>>0.6 (Medium)</option>
            <option value="0.4" <?= ($sitemap_priority === '0.4') ? 'selected' : '' ?>>0.4 (Low)</option>
          </select>
          <span class="nc-help-text"><?= i18n_r('NewsCenter/SITEMAP_PRIORITY_HELP') ?></span>
        </div>

        <button type="submit" class="nc-btn nc-btn-primary nc-btn-block"><?= i18n_r('NewsCenter/SAVE_SETTINGS') ?></button>
      </div>

      <div class="nc-card">
        <div class="nc-card-header">
          <h3><?= i18n_r('NewsCenter/BACKUP_AND_DATABASE') ?></h3>
        </div>
        <p class="nc-help-text"><?= i18n_r('NewsCenter/BACKUP_INFO') ?></p>
        <ul class="nc-status-info">
          <li><span><?= i18n_r('NewsCenter/LOCATION') ?>:</span> <code>data/other/newscenter/</code></li>
          <li><span><?= i18n_r('NewsCenter/FILE') ?>:</span> <code>newscenter.db</code></li>
        </ul>
      </div>
    </div>
  </form>
</div>