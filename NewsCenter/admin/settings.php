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
?>

<div class="nc-container">
  <div class="nc-page-header">
    <h2>NewsCenter Settings</h2>
  </div>

  <div class="nc-card">
    <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_settings" method="POST">
      <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">

      <div class="nc-form-group">
        <label for="nc-select-page">Target News Page</label>
        <div style="display:flex; gap:10px; align-items:center;">
          <select id="nc-select-page" class="nc-select" style="max-width:320px;">
            <option value="">-- Choose Existing Page --</option>
            <?php foreach ($pages as $p): ?>
              <option value="<?= htmlspecialchars($p['slug']) ?>" <?= ($p['slug'] === $active_slug) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['title']) ?> (<?= htmlspecialchars($p['slug']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <span style="font-size:13px; color:var(--nc-text-muted);">&larr; Select to auto-fill slug</span>
        </div>
      </div>

      <div class="nc-form-group">
        <label for="page_slug">Target News Page Slug</label>
        <input type="text" id="page_slug" name="page_slug" value="<?= htmlspecialchars($active_slug) ?>" class="nc-input" style="max-width:320px;" required>
        <span class="nc-help-text">Slug of the GetSimple page that displays your news feed.</span>
      </div>

      <!-- Trailing Slash Checkbox -->
      <div class="nc-form-group" style="margin: 20px 0;">
        <label style="display:inline-flex; align-items:center; gap:8px; font-weight:600; cursor:pointer;">
          <input type="checkbox" id="trailing_slash" name="trailing_slash" value="1" <?= $trailing_slash ? 'checked' : '' ?> style="width:auto; margin:0;">
          Include Trailing Slash on Fancy Links
        </label>
        <span class="nc-help-text" style="margin-left:22px;">
          When checked: <code>/news/post/my-article/</code> &bull; When unchecked: <code>/news/post/my-article</code>
        </span>
      </div>

      <div class="nc-form-row">
        <div class="nc-form-group nc-col-half">
          <label for="posts_per_page">Articles Per Page</label>
          <input type="number" id="posts_per_page" name="posts_per_page" value="<?= (int)($settings['posts_per_page'] ?? 10) ?>" class="nc-input" min="1" max="100">
        </div>

        <div class="nc-form-group nc-col-half">
          <label for="excerpt_length">Excerpt Length (characters)</label>
          <input type="number" id="excerpt_length" name="excerpt_length" value="<?= (int)($settings['excerpt_length'] ?? 250) ?>" class="nc-input" min="50" max="1000">
        </div>
      </div>

      <div class="nc-form-row">
        <div class="nc-form-group nc-col-half">
          <label for="date_format">Date Format</label>
          <input type="text" id="date_format" name="date_format" value="<?= htmlspecialchars($settings['date_format'] ?? 'F j, Y') ?>" class="nc-input">
          <span class="nc-help-text">PHP Date formatting string (e.g. <code>F j, Y</code>, <code>Y-m-d</code>).</span>
        </div>

        <div class="nc-form-group nc-col-half">
          <label for="sitemap_priority">Sitemap Priority</label>
          <select id="sitemap_priority" name="sitemap_priority" class="nc-select">
            <?php foreach (['1.0', '0.8', '0.6', '0.4'] as $p): ?>
              <option value="<?= $p ?>" <?= (($settings['sitemap_priority'] ?? '0.8') === $p) ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="nc-actions-bar" style="margin-top:20px;">
        <button type="submit" class="nc-btn nc-btn-primary">&#128190; Save Settings</button>
      </div>
    </form>
  </div>

  <!-- Sitemap Rebuilder Card -->
  <div class="nc-card" style="margin-top:25px;">
    <div class="nc-card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;">XML Sitemap Maintenance</h3>
      <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=rebuild_sitemap" method="POST" style="margin:0;">
        <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
        <button type="submit" class="nc-btn nc-btn-small nc-btn-secondary">&#8634; Rebuild Sitemap Now</button>
      </form>
    </div>
    <p style="font-size:13px; color:var(--nc-text-muted); margin:0;">
      Articles are automatically appended to your root <code>sitemap.xml</code> whenever published or edited. Click the button above if you wish to manually regenerate the static XML file immediately.
    </p>
  </div>

  <!-- Dynamic .htaccess Rules Card -->
  <div class="nc-card" style="margin-top:25px;">
    <div class="nc-card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;">Apache .htaccess Rules</h3>
      <button type="button" class="nc-btn nc-btn-small nc-btn-primary" id="nc-copy-rules-btn" onclick="ncCopyHtaccess()">📋 Copy Rules</button>
    </div>
    <p style="font-size:13px; color:var(--nc-text-muted); margin:0 0 12px 0;">
      If Fancy (pretty) URLs are enabled in GetSimple, paste these rewrite rules into your root <code>.htaccess</code> file right before <code># default rewrite rule</code>.
    </p>

    <div style="background:#0f172a; border-radius:6px; overflow:hidden; border:1px solid #1e293b;">
      <pre style="margin:0; padding:16px; font-family:'Consolas','Monaco',monospace; font-size:13px; color:#f8fafc; overflow-x:auto; line-height:1.6;" id="nc-htaccess-code"><code># NewsCenter Fancy Sub-paths for "<?= htmlspecialchars($active_slug) ?>"
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/post/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&post=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/category/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&category=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/tag/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&tag=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/author/([A-Za-z0-9_-]+)/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&author=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/archive/([0-9]{4}-[0-9]{2})/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&archive=$1 [QSA,L]
RewriteRule ^<?= htmlspecialchars($active_slug) ?>/page/([0-9]+)/?$ index.php?id=<?= htmlspecialchars($active_slug) ?>&page=$1 [QSA,L]</code></pre>
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
    navigator.clipboard.writeText(text).then(() => {
        btn.innerText = '✓ Copied!';
        setTimeout(() => { btn.innerText = '📋 Copy Rules'; }, 1800);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.innerText = '✓ Copied!';
        setTimeout(() => { btn.innerText = '📋 Copy Rules'; }, 1800);
    });
}
</script>