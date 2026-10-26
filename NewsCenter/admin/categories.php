<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit_category = null;

if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM nc_categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

$categories = nc_get_categories_with_counts();
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/TAB_CATEGORIES') ?></h2>
  </div>

  <div class="nc-form-grid">
    <div class="nc-card">
      <div class="nc-card-header">
        <h3><?= ($edit_category) ? i18n_r('NewsCenter/EDIT_CATEGORY') : i18n_r('NewsCenter/ADD_CATEGORY') ?></h3>
      </div>
      <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_category" method="POST">
        <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
        <input type="hidden" name="id" value="<?= ($edit_category) ? (int)$edit_category['id'] : 0 ?>">

        <div class="nc-form-group">
          <label for="cat-name"><?= i18n_r('NewsCenter/CATEGORY_NAME') ?></label>
          <input type="text" id="cat-name" name="name" value="<?= ($edit_category) ? htmlspecialchars($edit_category['name']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-form-group">
          <label for="cat-slug"><?= i18n_r('NewsCenter/SLUG') ?></label>
          <input type="text" id="cat-slug" name="slug" value="<?= ($edit_category) ? htmlspecialchars($edit_category['slug']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-form-group">
          <label for="cat-desc"><?= i18n_r('NewsCenter/DESCRIPTION') ?></label>
          <textarea id="cat-desc" name="description" class="nc-textarea" rows="4"><?= ($edit_category) ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
        </div>

        <div class="nc-actions-bar">
          <button type="submit" class="nc-btn nc-btn-primary">
            <?= ($edit_category) ? i18n_r('NewsCenter/UPDATE_CATEGORY') : i18n_r('NewsCenter/SAVE_CATEGORY') ?>
          </button>
          <?php if ($edit_category): ?>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/CANCEL') ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="nc-card">
      <div class="nc-card-header">
        <h3><?= i18n_r('NewsCenter/ALL_CATEGORIES') ?></h3>
      </div>
      <?php if (!empty($categories)): ?>
        <table class="nc-table">
          <thead>
            <tr>
              <th><?= i18n_r('NewsCenter/CATEGORY_NAME') ?></th>
              <th><?= i18n_r('NewsCenter/SLUG') ?></th>
              <th><?= i18n_r('NewsCenter/ARTICLES_COUNT') ?></th>
              <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($cat['name']) ?></strong>
                  <?php if (!empty($cat['description'])): ?>
                    <div class="nc-subtext"><?= htmlspecialchars($cat['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td><code>/<?= htmlspecialchars($cat['slug']) ?></code></td>
                <td>
                  <span class="nc-badge-pill"><?= (int)$cat['article_count'] ?></span>
                </td>
                <td class="nc-text-right nc-actions-cell">
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories&edit_id=<?= (int)$cat['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>">&#9998;</a>
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories&do=delete&category_id=<?= (int)$cat['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                     class="nc-btn-action nc-btn-action-delete" 
                     title="<?= i18n_r('NewsCenter/DELETE') ?>" 
                     onclick="return confirm('<?= i18n_r('NewsCenter/CONFIRM_DELETE_CATEGORY') ?>');">&#128465;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_CATEGORIES_YET') ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('cat-name');
    const slugInput = document.getElementById('cat-slug');
    if (nameInput && slugInput && !slugInput.value) {
        nameInput.addEventListener('input', () => {
            if (!slugInput.dataset.touched) {
                slugInput.value = nameInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
        });
        slugInput.addEventListener('change', () => {
            slugInput.dataset.touched = 'true';
        });
    }
});
</script>