<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$settings = nc_get_settings();
$active_slug = !empty($settings['page_slug']) ? $settings['page_slug'] : 'news';
$trailing_slash = (!isset($settings['trailing_slash']) || $settings['trailing_slash'] === '1');

// Collect all existing GetSimple CMS CE pages from data/pages/
$pages = [];
$page_files = glob(GSDATAPAGESPATH . '*.xml');
if ($page_files) {
    foreach ($page_files as $pf) {
        $slug = basename($pf, '.xml');
        $xml = @simplexml_load_file($pf);
        $page_title = $xml ? (string)$xml->title : $slug;
        $pages[] = [
            'slug'  => $slug,
            'title' => $page_title
        ];
    }
    usort($pages, function($a, $b) {
        return strcmp($a['title'], $b['title']);
    });
}

// 1. Check for SimpleBlog database
$sb_db_file = defined('GSDATAOTHERPATH') ? GSDATAOTHERPATH . 'blog.db' : '';
$sb_exists = file_exists($sb_db_file);
$sb_post_count = 0;
$sb_cat_count = 0;

if ($sb_exists) {
    try {
        $sb_db = new SQLite3($sb_db_file, SQLITE3_OPEN_READONLY);
        $sb_post_count = (int)$sb_db->querySingle("SELECT COUNT(*) FROM posts");
        $sb_cat_count  = (int)$sb_db->querySingle("SELECT COUNT(*) FROM categories");
        $sb_db->close();
    } catch (\Throwable $e) {
        $sb_exists = false;
    }
}

// 2. Check for News Manager posts
$nm_posts_dir = defined('GSDATAPATH') ? GSDATAPATH . 'posts/' : '';
$nm_files = is_dir($nm_posts_dir) ? glob($nm_posts_dir . '*.xml') : [];
$nm_exists = !empty($nm_files);
$nm_post_count = count($nm_files);
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2><?= i18n_r('NewsCenter/SETTINGS_TITLE') ?></h2>
  </div>

  <?php if (isset($_GET['status']) && $_GET['status'] === 'migrated'): ?>
    <div class="nc-alert nc-alert-success" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; border-radius:6px; padding:12px 16px; margin-bottom:20px; font-weight:500;">
      <?= !empty($_GET['msg']) ? htmlspecialchars((string)urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8') : i18n_r('NewsCenter/MIGRATION_SUCCESS') ?>
    </div>
  <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error' && !empty($_GET['msg'])): ?>
    <div class="nc-alert nc-alert-error" style="background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; border-radius:6px; padding:12px 16px; margin-bottom:20px; font-weight:500;">
      <?= htmlspecialchars((string)urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="nc-card">
    <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_settings" method="POST">
      <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">

      <div class="nc-form-group">
        <label for="nc-select-page"><?= i18n_r('NewsCenter/TARGET_NEWS_PAGE') ?></label>
        <div style="display:flex; gap:10px; align-items:center;">
          <select id="nc-select-page" class="nc-select" style="max-width:320px;">
            <option value=""><?= i18n_r('NewsCenter/CHOOSE_EXISTING_PAGE') ?></option>
            <?php foreach ($pages as $p): ?>
              <option value="<?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= ($p['slug'] === $active_slug) ? 'selected' : '' ?>>
                <?= htmlspecialchars((string)$p['title'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$p['slug'], ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <span style="font-size:13px; color:var(--nc-text-muted);"><?= i18n_r('NewsCenter/AUTOFILL_SLUG_HINT') ?></span>
        </div>
      </div>

      <div class="nc-form-group">
        <label for="page_slug"><?= i18n_r('NewsCenter/SETTING_PAGE_SLUG') ?></label>
        <input type="text" id="page_slug" name="page_slug" value="<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>" class="nc-input" style="max-width:320px;" required>
        <span class="nc-help-text"><?= i18n_r('NewsCenter/SETTING_PAGE_SLUG_HELP') ?></span>
      </div>

      <!-- Trailing Slash Checkbox -->
      <div class="nc-form-group" style="margin: 20px 0;">
        <label style="display:inline-flex; align-items:center; gap:8px; font-weight:600; cursor:pointer;">
          <input type="checkbox" id="trailing_slash" name="trailing_slash" value="1" <?= $trailing_slash ? 'checked' : '' ?> style="width:auto; margin:0;">
          <?= i18n_r('NewsCenter/TRAILING_SLASH_LABEL') ?>
        </label>
        <span class="nc-help-text" style="margin-left:22px;">
          <?= i18n_r('NewsCenter/TRAILING_SLASH_HELP') ?>
        </span>
      </div>

      <div class="nc-form-row">
        <div class="nc-form-group nc-col-half">
          <label for="posts_per_page"><?= i18n_r('NewsCenter/SETTING_POSTS_PER_PAGE') ?></label>
          <input type="number" id="posts_per_page" name="posts_per_page" value="<?= (int)($settings['posts_per_page'] ?? 10) ?>" class="nc-input" min="1" max="100">
        </div>

        <div class="nc-form-group nc-col-half">
          <label for="excerpt_length"><?= i18n_r('NewsCenter/SETTING_EXCERPT_LENGTH') ?></label>
          <input type="number" id="excerpt_length" name="excerpt_length" value="<?= (int)($settings['excerpt_length'] ?? 250) ?>" class="nc-input" min="50" max="1000">
        </div>
      </div>

      <div class="nc-form-row">
        <div class="nc-form-group nc-col-half">
          <label for="date_format"><?= i18n_r('NewsCenter/SETTING_DATE_FORMAT') ?></label>
          <input type="text" id="date_format" name="date_format" value="<?= htmlspecialchars((string)($settings['date_format'] ?? 'F j, Y'), ENT_QUOTES, 'UTF-8') ?>" class="nc-input">
          <span class="nc-help-text"><?= i18n_r('NewsCenter/SETTING_DATE_FORMAT_HELP') ?></span>
        </div>

        <div class="nc-form-group nc-col-half">
          <label for="sitemap_priority"><?= i18n_r('NewsCenter/SITEMAP_PRIORITY') ?></label>
          <select id="sitemap_priority" name="sitemap_priority" class="nc-select">
            <?php foreach (['1.0', '0.8', '0.6', '0.4'] as $p): ?>
              <option value="<?= $p ?>" <?= (($settings['sitemap_priority'] ?? '0.8') === $p) ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="nc-actions-bar" style="margin-top:20px;">
        <button type="submit" class="nc-btn nc-btn-primary nc-btn-save">&#128190; <?= i18n_r('NewsCenter/SAVE_SETTINGS') ?></button>
      </div>
    </form>
  </div>

  <!-- Migration from Other Plugins -->
  <?php if ($sb_exists || $nm_exists): ?>
  <div class="nc-card" style="margin-top:25px;">
    <div class="nc-card-header">
      <h3 style="margin:0;"><?= i18n_r('NewsCenter/MIGRATION_TITLE') ?></h3>
    </div>
    <div style="padding:16px 0; display:flex; flex-direction:column; gap:20px;">
      
      <?php if ($sb_exists): ?>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:16px;">
        <h4 style="margin:0 0 6px 0; font-size:15px; color:#1e293b;">SimpleBlog</h4>
        <p style="font-size:13px; color:var(--nc-text); margin:0 0 10px 0;">
          <?= i18n_r('NewsCenter/MIGRATE_SB_DETECTED') ?>
        </p>
        <div style="font-size:12.5px; color:var(--nc-text-muted); margin-bottom:12px;">
          &bull; <strong><?= $sb_post_count ?></strong> <?= i18n_r('NewsCenter/POSTS') ?> &bull; <strong><?= $sb_cat_count ?></strong> <?= i18n_r('NewsCenter/CATEGORIES') ?>
        </div>
        <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=migrate_simpleblog" method="POST" onsubmit="return confirm(<?= json_encode(i18n_r('NewsCenter/MIGRATE_SB_CONFIRM')) ?>);">
          <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
          <button type="submit" class="nc-btn nc-btn-primary" style="background:#4f46e5; border-color:#4338ca;">
            &#128229; <?= i18n_r('NewsCenter/MIGRATE_SB_BTN') ?>
          </button>
        </form>
      </div>
      <?php endif; ?>

      <?php if ($nm_exists): ?>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:16px;">
        <h4 style="margin:0 0 6px 0; font-size:15px; color:#1e293b;">News Manager</h4>
        <p style="font-size:13px; color:var(--nc-text); margin:0 0 10px 0;">
          <?= i18n_r('NewsCenter/MIGRATE_NM_DETECTED') ?>
        </p>
        <div style="font-size:12.5px; color:var(--nc-text-muted); margin-bottom:12px;">
          &bull; <strong><?= $nm_post_count ?></strong> <?= i18n_r('NewsCenter/POSTS') ?> in <code>data/posts/</code>
        </div>
        <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=migrate_news_manager" method="POST" onsubmit="return confirm(<?= json_encode(i18n_r('NewsCenter/MIGRATE_NM_CONFIRM')) ?>);">
          <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
          <button type="submit" class="nc-btn nc-btn-primary" style="background:#0284c7; border-color:#0369a1;">
            &#128229; <?= i18n_r('NewsCenter/MIGRATE_NM_BTN') ?>
          </button>
        </form>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php endif; ?>

  <!-- Sitemap Rebuilder Card -->
  <div class="nc-card" style="margin-top:25px;">
    <div class="nc-card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;"><?= i18n_r('NewsCenter/SITEMAP_MAINTENANCE') ?></h3>
      <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=rebuild_sitemap" method="POST" style="margin:0;">
        <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
        <button type="submit" class="nc-btn nc-btn-small nc-btn-secondary"><?= $svgRefresh ?? '🔄' ?> <?= i18n_r('NewsCenter/REBUILD_SITEMAP_NOW') ?></button>
      </form>
    </div>
    <p style="font-size:13px; color:var(--nc-text-muted); margin:0;">
      <?= i18n_r('NewsCenter/SITEMAP_HELP') ?>
    </p>
  </div>

  <!-- Dynamic .htaccess Rules Card -->
  <div class="nc-card" style="margin-top:25px;">
    <div class="nc-card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;"><?= i18n_r('NewsCenter/HTACCESS_RULES') ?></h3>
      <button type="button" class="nc-btn nc-btn-small nc-btn-primary" id="nc-copy-rules-btn" onclick="ncCopyHtaccess()"><?= $svgCopy ?? '📋' ?> <?= i18n_r('NewsCenter/COPY_RULES') ?></button>
    </div>
    <p style="font-size:13px; color:var(--nc-text-muted); margin:0 0 12px 0;">
      <?= i18n_r('NewsCenter/HTACCESS_HELP') ?>
    </p>

    <div style="background:#0f172a; border-radius:6px; overflow:hidden; border:1px solid #1e293b;">
      <pre style="margin:0; padding:16px; font-family:'Consolas','Monaco',monospace; font-size:13px; color:#f8fafc; overflow-x:auto; line-height:1.6;" id="nc-htaccess-code"><code># NewsCenter Fancy Sub-paths for "<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>"
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/post/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&post=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/category/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&category=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/tag/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&tag=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/author/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&author=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/archive/([0-9]{4}-[0-9]{2})/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&archive=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>/page/([0-9]+)/?$ index.php?id=<?= htmlspecialchars((string)$active_slug, ENT_QUOTES, 'UTF-8') ?>&page=$1 [QSA,L]</code></pre>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageSelect = document.getElementById('nc-select-page');
    const slugInput = document.getElementById('page_slug');
    const codeEl = document.getElementById('nc-htaccess-code');

    function updateHtaccessText(slug) {
        if (!slug) slug = 'news';
        codeEl.innerHTML = `<code># NewsCenter Fancy Sub-paths for "${slug}"
RewriteRule ^${slug}/post/([A-Za-z0-9_-]+)/?$ index.php?id=${slug}&post=$1 [QSA,L]
RewriteRule ^${slug}/category/([A-Za-z0-9_-]+)/?$ index.php?id=${slug}&category=$1 [QSA,L]
RewriteRule ^${slug}/tag/([A-Za-z0-9_-]+)/?$ index.php?id=${slug}&tag=$1 [QSA,L]
RewriteRule ^${slug}/author/([A-Za-z0-9_-]+)/?$ index.php?id=${slug}&author=$1 [QSA,L]
RewriteRule ^${slug}/archive/([0-9]{4}-[0-9]{2})/?$ index.php?id=${slug}&archive=$1 [QSA,L]
RewriteRule ^${slug}/page/([0-9]+)/?$ index.php?id=${slug}&page=$1 [QSA,L]</code>`;
    }

    if (pageSelect && slugInput) {
        pageSelect.addEventListener('change', () => {
            if (pageSelect.value) {
                slugInput.value = pageSelect.value;
                updateHtaccessText(pageSelect.value);
            }
        });

        slugInput.addEventListener('input', () => {
            updateHtaccessText(slugInput.value.trim());
        });
    }
});

function ncCopyHtaccess() {
    const code = document.getElementById('nc-htaccess-code');
    const btn = document.getElementById('nc-copy-rules-btn');
    if (!code || !btn) return;

    const text = code.innerText.trim();
    const copiedText = <?= json_encode(i18n_r('NewsCenter/COPIED')) ?>;
    const defaultText = '📋 ' + <?= json_encode(i18n_r('NewsCenter/COPY_RULES')) ?>;

    navigator.clipboard.writeText(text).then(() => {
        btn.innerText = '✓ ' + copiedText;
        setTimeout(() => { btn.innerText = defaultText; }, 1800);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.innerText = '✓ ' + copiedText;
        setTimeout(() => { btn.innerText = defaultText; }, 1800);
    });
}
</script>