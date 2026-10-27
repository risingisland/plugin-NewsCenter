<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

$db = nc_get_db();

// Registered template choices
$template_options = [
    'single'          => 'Single Article Template (single)',
    'list_item'       => 'Article List Item Template (list_item)',
    'home_posts'      => 'Home Page Posts Grid Container (home_posts)',
    'home_item'       => 'Home Page Post Card Item (home_item)',
    'authors_list'    => 'Authors Directory Grid Container (authors_list)',
    'author_item'     => 'Author Profile Card Item (author_item)',
    'categories_list' => 'Categories Directory Grid Container (categories_list)',
    'category_item'   => 'Category Card Item (category_item)',
    'frontend_css'    => 'Frontend CSS Stylesheet (frontend_css)'
];

// Active key
$active_key = isset($_GET['key']) ? nc_sanitize_slug($_GET['key']) : 'single';
if (!array_key_exists($active_key, $template_options)) {
    $active_key = 'single';
}

// Fetch template content from database
$stmt = $db->prepare("SELECT * FROM nc_templates WHERE template_key = ? LIMIT 1");
$stmt->execute([$active_key]);
$current_tpl = $stmt->fetch();

// If template doesn't exist yet, seed and reload
if (!$current_tpl) {
    if (function_exists('nc_reset_default_templates')) {
        nc_reset_default_templates();
    }
    $stmt->execute([$active_key]);
    $current_tpl = $stmt->fetch();
}

$content = $current_tpl ? $current_tpl['content'] : '';
$is_css = ($active_key === 'frontend_css');
?>

<link rel="stylesheet" href="<?= NC_URL ?>assets/vendor/codemirror/codemirror.css">
<link rel="stylesheet" href="<?= NC_URL ?>assets/vendor/codemirror/blackboard.css">

<div class="nc-container">
  <div class="nc-page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h2><?= i18n_r('NewsCenter/TAB_TEMPLATES') ?></h2>
    <div>
      <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=templates&do=delete&restore_template=1&key=<?= urlencode($active_key) ?>&nc_csrf=<?= nc_csrf_token() ?>" 
         class="nc-btn nc-btn-danger" 
         onclick="return confirm('Reset this template back to factory defaults? Any custom edits to this template will be lost.');">
        &#8634; Restore Default
      </a>
    </div>
  </div>

  <div class="nc-card">
    <div style="margin-bottom: 18px; display:flex; align-items:center; gap:12px;">
      <label for="nc-template-picker" style="font-weight:600; font-size:13px;">Select Template to Edit:</label>
      <select id="nc-template-picker" class="nc-select" style="max-width:420px;" onchange="location.href='load.php?id=<?= NC_PLUGIN_ID ?>&action=templates&key=' + encodeURIComponent(this.value);">
        <?php foreach ($template_options as $key => $label): ?>
          <option value="<?= htmlspecialchars($key) ?>" <?= ($active_key === $key) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <form action="load.php?id=<?= NC_PLUGIN_ID ?>&action=save_template" method="POST">
      <input type="hidden" name="nc_csrf" value="<?= nc_csrf_token() ?>">
      <input type="hidden" name="template_key" value="<?= htmlspecialchars($active_key) ?>">

      <div class="nc-form-group">
        <textarea id="nc-codemirror-editor" name="content"><?= htmlspecialchars($content) ?></textarea>
      </div>

      <div class="nc-actions-bar" style="margin-top:15px;">
        <button type="submit" class="nc-btn nc-btn-primary nc-btn-save">&#128190; Save Template</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= NC_URL ?>assets/vendor/codemirror/codemirror.js"></script>
<script src="<?= NC_URL ?>assets/vendor/codemirror/xml.js"></script>
<script src="<?= NC_URL ?>assets/vendor/codemirror/javascript.js"></script>
<script src="<?= NC_URL ?>assets/vendor/codemirror/css.js"></script>
<script src="<?= NC_URL ?>assets/vendor/codemirror/htmlmixed.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('nc-codemirror-editor');
    const isCss = <?= $is_css ? 'true' : 'false' ?>;

    if (textarea && typeof CodeMirror !== 'undefined') {
        const editor = CodeMirror.fromTextArea(textarea, {
            mode: isCss ? 'css' : 'htmlmixed',
            theme: 'blackboard',
            lineNumbers: true,
            lineWrapping: true,
            indentUnit: 2,
            tabSize: 2
        });
        editor.setSize('100%', 520);
    }
});
</script>