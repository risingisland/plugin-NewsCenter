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

$svgIcon = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="#1e70bf" d="M13.03 10c-.122 0-.255 0-.37.01c-.13.01-.3.036-.478.126a1.25 1.25 0 0 0-.546.547c-.09.176-.116.348-.127.478c-.01.114-.009.247-.009.369v1.19c0 .122 0 .255.01.37c.01.13.036.3.126.478c.12.235.311.426.547.546c.176.09.348.116.478.127c.114.01.247.009.369.009h1.94c.122 0 .255 0 .37-.01c.13-.01.3-.036.478-.126a1.25 1.25 0 0 0 .546-.546c.09-.177.116-.349.127-.479c.01-.114.009-.247.009-.369v-1.19c0-.122 0-.255-.01-.37a1.3 1.3 0 0 0-.126-.478a1.25 1.25 0 0 0-.546-.546a1.3 1.3 0 0 0-.479-.127A5 5 0 0 0 14.97 10zm-4.78.25a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5zm0 2.25a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5zm0 2.75a.75.75 0 0 0 0 1.5h7.5a.75.75 0 0 0 0-1.5zm0 2.5a.75.75 0 0 0 0 1.5h7.5a.75.75 0 0 0 0-1.5z" opacity=".5"/><path fill="#1e70bf" d="M17.83 2.25H6.17c-.535 0-.98 0-1.345.03c-.38.03-.736.098-1.073.27A2.75 2.75 0 0 0 2.55 3.752c-.172.337-.24.693-.27 1.073c-.03.365-.03.81-.03 1.345V21a.75.75 0 0 0 1.5 0V6.2c0-.572 0-.957.025-1.253c.023-.287.065-.424.111-.514a1.25 1.25 0 0 1 .547-.547c.09-.046.227-.088.514-.111c.296-.024.68-.025 1.253-.025h11.6c.572 0 .957 0 1.252.025c.288.023.425.065.515.111c.236.12.427.311.547.547c.046.09.088.227.111.514c.024.296.025.68.025 1.253V21a.75.75 0 0 0 1.5 0V6.17c0-.535 0-.98-.03-1.345c-.03-.38-.098-.736-.27-1.073a2.75 2.75 0 0 0-1.2-1.202c-.338-.172-.694-.24-1.074-.27c-.365-.03-.81-.03-1.345-.03"/><path fill="#1e70bf" d="M7.5 6.5a1 1 0 0 0 0 2h9a1 1 0 1 0 0-2z"/></svg>';

$svgImg = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 16 16"><path d="M0 0h16v16H0z" fill="none"/><g fill="none"><path fill="url(#SVGtPYHCdKu)" d="M2 4.5A2.5 2.5 0 0 1 4.5 2h7A2.5 2.5 0 0 1 14 4.5v7a2.5 2.5 0 0 1-2.5 2.5h-7A2.5 2.5 0 0 1 2 11.5z"/><path fill="url(#SVGOzrsyedH)" d="M13.586 12.879A2.5 2.5 0 0 1 11.5 14h-7a2.5 2.5 0 0 1-2.086-1.121l4.384-4.384a1.7 1.7 0 0 1 2.404 0z"/><path fill="url(#SVGXC0gZbAj)" d="M11.5 5.502a1.002 1.002 0 1 1-2.004 0a1.002 1.002 0 0 1 2.004 0"/><defs><linearGradient id="SVGOzrsyedH" x1="6.286" x2="7.572" y1="7.997" y2="14.347" gradientUnits="userSpaceOnUse"><stop stop-color="#b3e0ff"/><stop offset="1" stop-color="#8cd0ff"/></linearGradient><linearGradient id="SVGXC0gZbAj" x1="10.097" x2="10.829" y1="4.277" y2="6.913" gradientUnits="userSpaceOnUse"><stop stop-color="#fdfdfd"/><stop offset="1" stop-color="#b3e0ff"/></linearGradient><radialGradient id="SVGtPYHCdKu" cx="0" cy="0" r="1" gradientTransform="rotate(51.687 3.546 -5.177)scale(33.182 30.1812)" gradientUnits="userSpaceOnUse"><stop offset=".338" stop-color="#0fafff"/><stop offset=".529" stop-color="#367af2"/></radialGradient></defs></g></svg>';

$svgEdit = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 16 16"><path d="M0 0h16v16H0z" fill="none"/><g fill="none"><path fill="url(#SVGavZttdMj)" d="M2.657 9.635L9.75 2.542l3.707 3.708l-7.093 7.092l-2.267-1.439z"/><path fill="url(#SVG3XugvbPv)" d="m2.964 9.328l-.307.307a2.95 2.95 0 0 0-.772 1.355l-.87 3.385a.5.5 0 0 0 .61.609l3.385-.87c.512-.13.98-.398 1.354-.772l.307-.307s-1.285-.3-2.345-1.361s-1.362-2.346-1.362-2.346"/><path fill="url(#SVGI9hz4djb)" d="M10.529 1.764a2.621 2.621 0 1 1 3.707 3.707l-1.908 1.908l-3.707-3.707z"/><path fill="url(#SVGSYVyI33f)" d="M8.266 4.026s.295 1.291 1.356 2.352a5.4 5.4 0 0 0 2.351 1.356L13.4 6.308s-1.29-.298-2.35-1.358A5.4 5.4 0 0 1 9.692 2.6z"/><defs><linearGradient id="SVGavZttdMj" x1="8.631" x2="10.281" y1="5.563" y2="10.095" gradientUnits="userSpaceOnUse"><stop stop-color="#ffa43d"/><stop offset="1" stop-color="#fb5937"/></linearGradient><linearGradient id="SVG3XugvbPv" x1=".187" x2="4.275" y1="10.908" y2="14.972" gradientUnits="userSpaceOnUse"><stop offset=".255" stop-color="#ffd394"/><stop offset="1" stop-color="#ff921f"/></linearGradient><linearGradient id="SVGI9hz4djb" x1="13.669" x2="11.316" y1="2.324" y2="4.537" gradientUnits="userSpaceOnUse"><stop stop-color="#f97dbd"/><stop offset="1" stop-color="#dd3ce2"/></linearGradient><linearGradient id="SVGSYVyI33f" x1="10.921" x2="7.295" y1="6.114" y2="4.528" gradientUnits="userSpaceOnUse"><stop stop-color="#ff921f"/><stop offset="1" stop-color="#ffe994"/></linearGradient></defs></g></svg>';

$svgDelete = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><g fill="#c33"><path d="M9.42578 11.4815C9.83777 11.4383 10.2049 11.7548 10.2461 12.1885L10.7461 17.4522C10.7871 17.8858 10.4862 18.2721 10.0742 18.3154C9.66223 18.3586 9.29511 18.0421 9.25391 17.6084L8.75391 12.3457C8.71269 11.9119 9.01362 11.5248 9.42578 11.4815Z"/><path d="M14.5742 11.4815C14.9864 11.5248 15.2873 11.9119 15.2461 12.3457L14.7461 17.6084C14.7049 18.0422 14.3378 18.3586 13.9258 18.3154C13.5138 18.2721 13.2129 17.8858 13.2539 17.4522L13.7539 12.1885C13.7951 11.7548 14.1623 11.4384 14.5742 11.4815Z"/><path d="M13.7393 2.00002C13.9618 1.99986 14.1558 1.99998 14.3389 2.03322C15.0621 2.16462 15.688 2.67737 16.0264 3.41603C16.112 3.60305 16.1729 3.8126 16.2432 4.05275L16.3584 4.44435C16.3778 4.51052 16.383 4.52913 16.3877 4.54396C16.5678 5.11018 17.1279 5.49355 17.6572 5.50881H20.2285C20.6545 5.50881 20.9999 5.90141 21 6.38576C21 6.87022 20.6546 7.26271 20.2285 7.26271H3.77148C3.34544 7.26271 3 6.87022 3 6.38576C3.00011 5.90141 3.3455 5.50881 3.77148 5.50881H6.43555C6.9649 5.49355 7.43218 5.11018 7.6123 4.54396C7.61702 4.52913 7.6222 4.51055 7.6416 4.44435L7.75684 4.05275C7.82709 3.8126 7.88799 3.60305 7.97363 3.41603C8.31193 2.67739 8.93792 2.16467 9.66113 2.03322C9.84415 1.99999 10.0383 1.99986 10.2607 2.00002H13.7393Z"/><path d="M11.5956 22.0001H12.4044C15.1871 22.0001 16.5785 22.0001 17.4831 21.1142C18.3878 20.2283 18.4803 18.7751 18.6654 15.8686L18.9321 11.6807C19.0326 10.1037 19.0828 9.31524 18.6289 8.81558C18.1751 8.31592 17.4087 8.31592 15.876 8.31592H8.12405C6.59127 8.31592 5.82488 8.31592 5.37105 8.81558C4.91722 9.31524 4.96744 10.1037 5.06788 11.6807L5.33459 15.8686C5.5197 18.7751 5.61225 20.2283 6.51689 21.1142C7.42153 22.0001 8.81289 22.0001 11.5956 22.0001Z" opacity=".5"/></g></svg>';

$svgSave = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 80 80"><path d="M0 0h80v80H0z" fill="none"/><g fill="none"><path fill="#2f80ed" fill-rule="evenodd" d="M53.172 13.172A4 4 0 0 0 50.343 12H16a4 4 0 0 0-4 4v48a4 4 0 0 0 4 4h48a4 4 0 0 0 4-4V29.657a4 4 0 0 0-1.172-2.829z" clip-rule="evenodd"/><path fill="#2f80ed" d="m66.828 26.828l1.415-1.414zM53.172 13.172l-1.415 1.414zM16 14h34.343v-4H16zm-2 50V16h-4v48zm50 2H16v4h48zm2-36.343V64h4V29.657zM51.757 14.586l13.657 13.657l2.829-2.829l-13.657-13.657zM70 29.656a6 6 0 0 0-1.757-4.242l-2.829 2.829A2 2 0 0 1 66 29.657zM64 70a6 6 0 0 0 6-6h-4a2 2 0 0 1-2 2zm-54-6a6 6 0 0 0 6 6v-4a2 2 0 0 1-2-2zm40.343-50a2 2 0 0 1 1.414.586l2.829-2.829A6 6 0 0 0 50.343 10zM16 10a6 6 0 0 0-6 6h4a2 2 0 0 1 2-2z"/><path fill="#f2f2f2" stroke="#f2f2f2" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M59 44H21a1 1 0 0 0-1 1v22a1 1 0 0 0 1 1h38a1 1 0 0 0 1-1V45a1 1 0 0 0-1-1"/><path stroke="#56ccf2" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M28 52h24m-24 8h24"/><path fill="#56ccf2" stroke="#56ccf2" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M48 27V13a1 1 0 0 0-1-1H21a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h26a1 1 0 0 0 1-1"/></g></svg>';

$svgFolder = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 32 32"><path d="M0 0h32v32H0z" fill="none"/><g fill="none"><path fill="#ffb02e" d="M16.833 10H25c1.149 0 2 .851 2 2v1L14 23.5L2.61 28.23C2.22 27.63 2 26.774 2 26V8.08A2.08 2.08 0 0 1 4.08 6h6.675c.809 0 1.585.32 2.158.89l2.453 2.498c.39.387.918.612 1.467.612"/><path fill="#fcd53f" d="M27.911 13H10.886a3.68 3.68 0 0 0-3.463 2.439C2.832 28.604 3.211 27.658 3.095 27.806a.55.55 0 0 1-.453.25a.35.35 0 0 1-.182-.054a3.78 3.78 0 0 0 3.585 2h17.952a2.03 2.03 0 0 0 1.939-1.453l3.962-12.835A2.086 2.086 0 0 0 27.911 13"/></g></svg>';

$svgUser = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><g fill="none"><path fill="#1e70bf" d="M17.35 7.9a5.35 5.35 0 1 1-10.7 0a5.35 5.35 0 0 1 10.7 0" opacity=".2"/><path fill="#1e70bf" d="M20.3 21.72H3.7a8.4 8.4 0 0 1 8.3-8.46c4.59 0 8.3 3.79 8.3 8.46" opacity=".2"/><path stroke="#1e70bf" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.35 7.9A5.33 5.33 0 0 1 12 13.27a5.35 5.35 0 1 1 5.35-5.35"/><path stroke="#1e70bf" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.3 21.56a8.3 8.3 0 0 0-16.6 0"/></g></svg>';

$svgCopy = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path fill="currentColor" d="M20 2H10c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2m0 12H10V4h10z"/><path fill="currentColor" d="M14 20H4V10h2V8H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2v-2h-2zM12 6h6v2h-6zm0 4h6v2h-6z"/></svg>';

$svgRefresh = '<svg xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle;" width="1.5em" height="1.5em" viewBox="0 0 100 100"><path d="M0 0h100v100H0z" fill="none"/><path fill="currentColor" d="M84.539 21.586a1.515 1.515 0 0 0-2.393-1.222l-5.944 4.261l-.468.337c-6.405-6.392-15.196-10.389-24.937-10.389c-19.535 0-35.427 15.894-35.427 35.428s15.893 35.428 35.427 35.428a35.42 35.42 0 0 0 29.374-15.618a1.77 1.77 0 0 0-.475-2.462l-8.863-6.151a1.9 1.9 0 0 0-2.628.512c-3.918 5.792-10.41 9.25-17.375 9.25c-11.558 0-20.962-9.402-20.962-20.957s9.404-20.957 20.962-20.957c4.878 0 9.352 1.696 12.914 4.5l-1.001.72l-5.948 4.26a1.513 1.513 0 0 0 .397 2.656l25.446 8.669c.461.161.966.083 1.368-.203c.399-.29.629-.747.627-1.231z"/></svg>';

?>

<div class="nc-wrap">
  <div class="nc-main-header">
    <div class="nc-brand">
      <h1> <?= $svgIcon . i18n_r('NewsCenter/ADMIN_TITLE') ?> </h1>
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
    <a href="load.php?id=<?= NC_PLUGIN_ID ?>&action=authors" class="nc-tab <?= ($current_action === 'authors') ? 'active' : '' ?>"><?= i18n_r('NewsCenter/TAB_AUTHORS') ?></a>
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