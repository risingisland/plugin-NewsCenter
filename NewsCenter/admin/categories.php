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
$cat_img = $edit_category ? ($edit_category['image'] ?? '') : '';
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/TAB_CATEGORIES') ?></h2>
  </div>

  <div class="nc-form-grid">

    <!-- Table Col -->
    <div class="nc-card">
      <div class="nc-card-header">
        <h3><?= i18n_r('NewsCenter/CATEGORIES') ?></h3>
      </div>
      <?php if (!empty($categories)): ?>
        <table class="nc-table">
          <thead>
            <tr>
              <th style="width:40px;"><?= i18n_r('NewsCenter/IMAGE') ?></th>
              <th><?= i18n_r('NewsCenter/NAME') ?></th>
              <th><?= i18n_r('NewsCenter/SLUG') ?></th>
              <th><?= i18n_r('NewsCenter/ARTICLES_COUNT') ?></th>
              <th class="nc-text-right"><?= i18n_r('NewsCenter/ACTIONS') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td>
                  <?php if (!empty($cat['image'])): ?>
                    <img src="<?= htmlspecialchars($cat['image']) ?>" alt="" style="width:36px; height:36px; border-radius:4px; object-fit:cover; display:block;">
                  <?php else: ?>
                    <div style="width:36px; height:36px; border-radius:4px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:11px; color:#64748b;"><?= $svgFolder ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= htmlspecialchars($cat['name']) ?></strong>
                  <?php if (!empty($cat['description'])): ?>
                    <div class="nc-subtext"><?= htmlspecialchars(substr($cat['description'], 0, 75)) . (strlen($cat['description']) > 75 ? '...' : '') ?></div>
                  <?php endif; ?>
                </td>
                <td><code>/<?= htmlspecialchars($cat['slug']) ?></code></td>
                <td>
                  <span class="nc-badge-pill"><?= (int)$cat['article_count'] ?></span>
                </td>
                <td class="nc-text-right nc-actions-cell">
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories&edit_id=<?= (int)$cat['id'] ?>" class="nc-btn-action" title="<?= i18n_r('NewsCenter/EDIT') ?>"><?= $svgEdit ?></a>
                  <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories&do=delete&category_id=<?= (int)$cat['id'] ?>&nc_csrf=<?= nc_csrf_token() ?>" 
                     class="nc-btn-action nc-btn-action-delete" 
                     title="<?= i18n_r('NewsCenter/DELETE') ?>"
                     onclick="return confirm('<?= i18n_r('NewsCenter/CONFIRM_DELETE_CAT') ?>');"><?= $svgDelete ?></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="nc-empty-state"><?= i18n_r('NewsCenter/NO_ARTICLES_FOUND') ?>.</p>
      <?php endif; ?>
    </div>
	
    <!-- Form Col -->
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

        <!-- Split Row: 1/3 Image & 2/3 Description -->
        <div class="nc-form-row-split">
          <!-- 1/3 Image -->
          <div class="nc-form-col-third">
            <label><?= i18n_r('NewsCenter/CATEGORY_IMAGE') ?></label>
            <input type="hidden" id="nc-cat-image-input" name="image" value="<?= htmlspecialchars($cat_img) ?>">
            <div class="nc-featured-preview-box" id="nc-cat-preview-box">
              <img id="nc-cat-thumb-img" class="nc-featured-thumb-img" src="<?= htmlspecialchars($cat_img) ?>" alt="Image" style="<?= empty($cat_img) ? 'display:none;' : '' ?>">
              <span id="nc-cat-empty" class="nc-featured-empty" style="<?= !empty($cat_img) ? 'display:none;' : '' ?>"><?= i18n_r('NewsCenter/NO_IMAGE_SELECTED') ?></span>
              <button type="button" class="nc-featured-remove-btn" id="nc-cat-remove-btn" title="<?= i18n_r('NewsCenter/DELETE') ?>" style="<?= empty($cat_img) ? 'display:none;' : '' ?>">&times;</button>
            </div>
            <button type="button" id="nc-select-cat-img-btn" class="nc-btn nc-btn-small nc-btn-secondary" style="margin-top:8px; width:100%;">
              <?= !empty($cat_img) ? i18n_r('NewsCenter/CHANGE_IMAGE') : i18n_r('NewsCenter/CHOOSE_IMAGE') ?>
            </button>
          </div>

          <!-- 2/3 Description -->
          <div class="nc-form-col-twothirds">
            <label for="cat-desc"><?= i18n_r('NewsCenter/DESCRIPTION') ?></label>
            <textarea id="cat-desc" name="description" class="nc-textarea" rows="5" placeholder="<?= i18n_r('NewsCenter/DESCRIPTION') ?>"><?= ($edit_category) ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
          </div>
        </div>

        <div class="nc-actions-bar">
          <button type="submit" class="nc-btn nc-btn-primary nc-btn-save">
            <?= ($edit_category) ? i18n_r('NewsCenter/UPDATE_CATEGORY') : i18n_r('NewsCenter/SAVE_CATEGORY') ?>
          </button>
          <?php if ($edit_category): ?>
            <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=categories" class="nc-btn nc-btn-outline"><?= i18n_r('NewsCenter/CANCEL') ?></a>
          <?php endif; ?>
        </div>
      </form>
    </div>
	
  </div>
</div>

<!-- Modal Picker Container -->
<div id="nc-media-modal" class="nc-modal" style="display:none;" data-csrf="<?= nc_csrf_token() ?>">
  <div class="nc-modal-content">
    <div class="nc-modal-header">
      <h4><?= i18n_r('NewsCenter/CHOOSE_IMAGE') ?></h4>
      <span class="nc-modal-close" id="nc-modal-x-close">&times;</span>
    </div>
    <div class="nc-modal-pane">
      <div id="nc-modal-file-list"><p><?= i18n_r('NewsCenter/LOADING_MEDIA') ?></p></div>
    </div>
    <div class="nc-modal-footer">
      <button type="button" class="nc-btn nc-btn-outline nc-modal-close-btn"><?= i18n_r('NewsCenter/CANCEL') ?></button>
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
                slugInput.value = nameInput.value.toLowerCase().trim().replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
            }
        });
        slugInput.addEventListener('change', () => { slugInput.dataset.touched = 'true'; });
    }

    const modal = document.getElementById('nc-media-modal');
    const selectBtn = document.getElementById('nc-select-cat-img-btn');
    const removeBtn = document.getElementById('nc-cat-remove-btn');
    const input = document.getElementById('nc-cat-image-input');
    const imgEl = document.getElementById('nc-cat-thumb-img');
    const emptyEl = document.getElementById('nc-cat-empty');
    const listContainer = document.getElementById('nc-modal-file-list');

    function setCatImg(url) {
        const clean = (url || '').trim();
        input.value = clean;
        if (clean) {
            imgEl.src = clean;
            imgEl.style.display = 'block';
            emptyEl.style.display = 'none';
            removeBtn.style.display = 'block';
            selectBtn.textContent = <?= json_encode(i18n_r('NewsCenter/CHANGE_IMAGE')) ?>;
        } else {
            imgEl.src = '';
            imgEl.style.display = 'none';
            emptyEl.style.display = 'flex';
            removeBtn.style.display = 'none';
            selectBtn.textContent = <?= json_encode(i18n_r('NewsCenter/CHOOSE_IMAGE')) ?>;
        }
    }

    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            setCatImg('');
        });
    }

    if (selectBtn) {
        selectBtn.addEventListener('click', () => {
            modal.style.display = 'block';
            listContainer.innerHTML = '<p>' + <?= json_encode(i18n_r('NewsCenter/LOADING_MEDIA')) ?> + '</p>';
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
                            setCatImg(f.url);
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