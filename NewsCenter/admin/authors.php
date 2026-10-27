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
$author_img = $edit_author ? ($edit_author['image'] ?? '') : '';
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

        <!-- Author Photo / Avatar -->
        <div class="nc-form-group">
          <label>Author Photo / Avatar</label>
          <input type="hidden" id="nc-author-image-input" name="image" value="<?= htmlspecialchars($author_img) ?>">
          <div class="nc-featured-preview-box" id="nc-author-preview-box">
            <img id="nc-author-thumb-img" class="nc-featured-thumb-img" src="<?= htmlspecialchars($author_img) ?>" alt="Avatar" style="<?= empty($author_img) ? 'display:none;' : '' ?>">
            <span id="nc-author-empty" class="nc-featured-empty" style="<?= !empty($author_img) ? 'display:none;' : '' ?>">No avatar selected</span>
            <button type="button" class="nc-featured-remove-btn" id="nc-author-remove-btn" title="Remove" style="<?= empty($author_img) ? 'display:none;' : '' ?>">&times;</button>
          </div>
          <button type="button" id="nc-select-author-img-btn" class="nc-btn nc-btn-small nc-btn-secondary" style="margin-top:8px; width:100%;">
            <?= !empty($author_img) ? 'Change Photo' : 'Choose Photo' ?>
          </button>
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
              <th style="width:40px;">Photo</th>
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
                  <?php if (!empty($au['image'])): ?>
                    <img src="<?= htmlspecialchars($au['image']) ?>" alt="" style="width:36px; height:36px; border-radius:50%; object-fit:cover; display:block;">
                  <?php else: ?>
                    <div style="width:36px; height:36px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:11px; color:#64748b;">👤</div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($au['name']) ?></strong>
                  <?php if (!empty($au['bio'])): ?>
                    <div class="nc-subtext"><?= htmlspecialchars(substr($au['bio'], 0, 75)) . (strlen($au['bio']) > 75 ? '...' : '') ?></div>
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
                     onclick="return confirm('Are you sure you want to delete this author?');">&#128465;</a>
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

<!-- Modal Picker Container -->
<div id="nc-media-modal" class="nc-modal" style="display:none;" data-csrf="<?= nc_csrf_token() ?>">
  <div class="nc-modal-content">
    <div class="nc-modal-header">
      <h4>Choose Author Image</h4>
      <span class="nc-modal-close" id="nc-modal-x-close">&times;</span>
    </div>
    <div class="nc-modal-pane">
      <div id="nc-modal-file-list"><p>Loading media...</p></div>
    </div>
    <div class="nc-modal-footer">
      <button type="button" class="nc-btn nc-btn-outline nc-modal-close-btn"><?= i18n_r('NewsCenter/CANCEL') ?></button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Slug generation
    const nameInput = document.getElementById('author-name');
    const slugInput = document.getElementById('author-slug');
    if (nameInput && slugInput && !slugInput.value) {
        nameInput.addEventListener('input', () => {
            if (!slugInput.dataset.touched) {
                slugInput.value = nameInput.value.toLowerCase().trim().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
            }
        });
        slugInput.addEventListener('change', () => { slugInput.dataset.touched = 'true'; });
    }

    // Modal Image Picker
    const modal = document.getElementById('nc-media-modal');
    const selectBtn = document.getElementById('nc-select-author-img-btn');
    const removeBtn = document.getElementById('nc-author-remove-btn');
    const input = document.getElementById('nc-author-image-input');
    const imgEl = document.getElementById('nc-author-thumb-img');
    const emptyEl = document.getElementById('nc-author-empty');
    const listContainer = document.getElementById('nc-modal-file-list');

    function setAuthorImg(url) {
        const clean = (url || '').trim();
        input.value = clean;
        if (clean) {
            imgEl.src = clean;
            imgEl.style.display = 'block';
            emptyEl.style.display = 'none';
            removeBtn.style.display = 'block';
            selectBtn.textContent = 'Change Photo';
        } else {
            imgEl.src = '';
            imgEl.style.display = 'none';
            emptyEl.style.display = 'flex';
            removeBtn.style.display = 'none';
            selectBtn.textContent = 'Choose Photo';
        }
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setAuthorImg('');
        });
    }

    if (selectBtn) {
        selectBtn.addEventListener('click', () => {
            modal.style.display = 'block';
            listContainer.innerHTML = '<p>Loading media...</p>';
            fetch('load.php?id=NewsCenter&action=ajax_media', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    const files = d.files || [];
                    listContainer.innerHTML = '';
                    const grid = document.createElement('div');
                    grid.className = 'nc-media-grid';
                    files.forEach(f => {
                        const thumb = document.createElement('div');
                        thumb.className = 'nc-media-thumb';
                        thumb.innerHTML = `<img src="${f.thumb || f.url}" alt="${f.name}"><p>${f.name}</p>`;
                        thumb.addEventListener('click', () => {
                            setAuthorImg(f.url);
                            modal.style.display = 'none';
                        });
                        grid.appendChild(thumb);
                    });
                    listContainer.appendChild(grid);
                });
        });
    }

    document.querySelectorAll('.nc-modal-close, .nc-modal-close-btn').forEach(b => {
        b.addEventListener('click', () => { modal.style.display = 'none'; });
    });
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
});
</script>