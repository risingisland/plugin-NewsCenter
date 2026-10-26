<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$edit_author = null;

if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM nc_authors WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_author = $stmt->fetch();
}

$authors = nc_get_authors_with_counts();
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2>Authors Management</h2>
  </div>

  <div class="nc-form-grid">
    <!-- Form Col -->
    <div class="nc-card">
      <div class="nc-card-header">
        <h3><?= ($edit_author) ? 'Edit Author' : 'Add New Author' ?></h3>
      </div>
      <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_author" method="POST">
        <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
        <input type="hidden" name="id" value="<?= ($edit_author) ? (int)$edit_author['id'] : 0 ?>">

        <div class="nc-form-group">
          <label for="author-name">Author Name</label>
          <input type="text" id="author-name" name="name" value="<?= ($edit_author) ? htmlspecialchars($edit_author['name']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-form-group">
          <label for="author-slug">Slug</label>
          <input type="text" id="author-slug" name="slug" value="<?= ($edit_author) ? htmlspecialchars($edit_author['slug']) : '' ?>" class="nc-input" required>
        </div>

        <div class="nc-form-group">
          <label for="author-bio">Bio / Description</label>
          <textarea id="author-bio" name="bio" class="nc-textarea" rows="4" placeholder="Brief author bio..."><?= ($edit_author) ? htmlspecialchars($edit_author['bio']) : '' ?></textarea>
        </div>

        <div class="nc-actions-bar">
          <button type="submit" class="nc-btn nc-btn-primary">
            <?= ($edit_author) ? 'Update Author' : 'Save Author' ?>
          </button>
          <?php if ($edit_author): ?>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/CANCEL') ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Table Col -->
    <div class="nc-card">
      <div class="nc-card-header">
        <h3>All Authors</h3>
      </div>
      <?php if (!empty($authors)): ?>
        <table class="nc-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Slug</th>
              <th>Articles</th>
              <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($authors as $au): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($au['name']) ?></strong>
                  <?php if (!empty($au['bio'])): ?>
                    <div class="nc-subtext"><?= htmlspecialchars($au['bio']) ?></div>
                  <?php endif; ?>
                </td>
                <td><code>/<?= htmlspecialchars($au['slug']) ?></code></td>
                <td>
                  <span class="nc-badge-pill"><?= (int)$au['article_count'] ?></span>
                </td>
                <td class="nc-text-right nc-actions-cell">
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors&edit_id=<?= (int)$au['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>">&#9998;</a>
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors&do=delete&author_id=<?= (int)$au['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                     class="nc-btn-action nc-btn-action-delete" 
                     title="<?= i18n_r('NewsCenter/DELETE') ?>" 
                     onclick="return confirm('Are you sure you want to delete this author? Associated articles will remain unaffected.');">&#128465;</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="nc-empty-state">No authors found.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('author-name');
    const slugInput = document.getElementById('author-slug');
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