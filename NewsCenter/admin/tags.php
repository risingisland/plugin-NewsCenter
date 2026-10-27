<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit_tag = null;

if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM nc_tags WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_tag = $stmt->fetch();
}

if (isset($_GET['do']) && $_GET['do'] === 'delete' && isset($_GET['tag_id'])) {
    $token = $_GET['nc_csrf'] ?? '';
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (!hash_equals($_SESSION['nc_csrf_token'] ?? '', $token)) {
        die(i18n_r('NewsCenter/CSRF_ERROR'));
    }

    $del_id = (int)$_GET['tag_id'];
    nc_delete_tag($del_id);
    redirect('load.php?id=' . NC_PLUGIN_ID . '&action=tags&status=deleted');
}

$tags = nc_get_tags_with_counts();
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/TAB_TAGS') ?></h2>
  </div>

  <div class="nc-form-grid">
	
	<div class="nc-card">
      <div class="nc-card-header">
        <h3><?= i18n_r('NewsCenter/ALL_TAGS') ?></h3>
      </div>
      <?php if (!empty($tags)): ?>
        <table class="nc-table">
          <thead>
            <tr>
              <th><?= i18n_r('NewsCenter/TAG_NAME') ?></th>
              <th><?= i18n_r('NewsCenter/SLUG') ?></th>
              <th><?= i18n_r('NewsCenter/ARTICLES_COUNT') ?></th>
              <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tags as $t): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($t['name']) ?></strong>
                </td>
                <td><code>/<?= htmlspecialchars($t['slug']) ?></code></td>
                <td>
                  <span class="nc-badge-pill"><?= (int)$t['article_count'] ?></span>
                </td>
                <td class="nc-text-right nc-actions-cell">
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags&edit_id=<?= (int)$t['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>">&#9998;</a>
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags&do=delete&tag_id=<?= (int)$t['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                     class="nc-btn-action nc-btn-action-delete" 
                     title="<?= i18n_r('NewsCenter/DELETE') ?>" 
                     onclick="return confirm('<?= i18n_r('NewsCenter/CONFIRM_DELETE_TAG') ?>');">&#128465;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_TAGS_YET') ?></p>
      <?php endif; ?>
    </div>
  
    <div class="nc-card">
      <div class="nc-card-header">
        <h3><?= ($edit_tag) ? i18n_r('NewsCenter/EDIT_TAG') : i18n_r('NewsCenter/ADD_TAG') ?></h3>
      </div>
      <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_tag" method="POST">
        <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
        <input type="hidden" name="id" value="<?= ($edit_tag) ? (int)$edit_tag['id'] : 0 ?>">

        <div class="nc-form-group">
          <label for="tag-name"><?= i18n_r('NewsCenter/TAG_NAME') ?></label>
          <input type="text" id="tag-name" name="name" value="<?= ($edit_tag) ? htmlspecialchars($edit_tag['name']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-form-group">
          <label for="tag-slug"><?= i18n_r('NewsCenter/SLUG') ?></label>
          <input type="text" id="tag-slug" name="slug" value="<?= ($edit_tag) ? htmlspecialchars($edit_tag['slug']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-actions-bar">
          <button type="submit" class="nc-btn nc-btn-primary nc-btn-save">
            <?= ($edit_tag) ? i18n_r('NewsCenter/UPDATE_TAG') : i18n_r('NewsCenter/SAVE_TAG') ?>
          </button>
          <?php if ($edit_tag): ?>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=tags" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/CANCEL') ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>
	
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('tag-name');
    const slugInput = document.getElementById('tag-slug');
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