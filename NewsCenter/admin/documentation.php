<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }
?>

<style>
.nc-doc-card {
  background: #ffffff;
  border: 1px solid var(--nc-border);
  border-radius: var(--nc-radius);
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: var(--nc-shadow);
}
.nc-doc-card h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
  color: #1e293b;
  border-bottom: 2px solid #f1f5f9;
  padding-bottom: 8px;
}
.nc-doc-card p {
  font-size: 14px;
  line-height: 1.6;
  color: #475569;
  margin: 0 0 16px 0;
}
.nc-code-wrapper {
  position: relative;
  margin: 14px 0 20px 0;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #1e293b;
  background: #0f172a;
}
.nc-code-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 6px 14px;
  background: #1e293b;
  color: #94a3b8;
  font-size: 12px;
  font-family: monospace;
}
.nc-code-copy-btn {
  background: #334155;
  color: #f8fafc;
  border: 1px solid #475569;
  border-radius: 4px;
  padding: 4px 10px;
  font-size: 11px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-weight: 600;
  transition: all 0.2s ease;
}
.nc-code-copy-btn:hover {
  background: var(--nc-primary);
  border-color: var(--nc-primary);
}
.nc-code-copy-btn.copied {
  background: var(--nc-success);
  border-color: var(--nc-success);
  color: #ffffff;
}
.nc-code-wrapper pre {
  margin: 0;
  padding: 14px 16px;
  overflow-x: auto;
  font-family: "Consolas", "Monaco", "Courier New", monospace;
  font-size: 13.5px;
  line-height: 1.5;
  color: #f1f5f9;
}
.nc-migration-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 12px;
}
.nc-migration-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 16px;
}
.nc-migration-box h4 {
  margin: 0 0 8px 0;
  font-size: 15px;
  color: #0f172a;
}
.nc-migration-box ul {
  margin: 8px 0 0 18px;
  padding: 0;
  font-size: 13.5px;
  color: #475569;
  line-height: 1.6;
}
</style>

<div class="nc-container">
  <div class="nc-page-header" style="margin-bottom:20px;">
    <h2><?= i18n_r('NewsCenter/DOCS_TITLE') ?></h2>
    <p style="color:var(--nc-text-muted); margin:4px 0 0 0;"><?= i18n_r('NewsCenter/DOCS_SUBTITLE') ?></p>
  </div>

  <!-- 1. Homepage Integration (Shortcodes & PHP) -->
  <div class="nc-doc-card">
    <h3><?= i18n_r('NewsCenter/DOCS_SEC_HOME') ?></h3>
    <p><?= i18n_r('NewsCenter/DOCS_SEC_HOME_DESC') ?></p>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_A_SHORTCODE') ?></h4>
    <p><?= i18n_r('NewsCenter/DOCS_OPT_A_DESC') ?></p>
    
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>[get-newscenter-recent limit=3]</code></pre>
    </div>

    <p><?= i18n_r('NewsCenter/DOCS_SHORTCODE_FILTER') ?></p>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Filtered Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>[get-newscenter-recent limit=3 category="featured" author="john-doe"]</code></pre>
    </div>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_B_PHP') ?></h4>
    <p><?= i18n_r('NewsCenter/DOCS_OPT_B_DESC') ?></p>
    
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Theme Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3); } ?&gt;</code></pre>
    </div>

    <p><?= i18n_r('NewsCenter/DOCS_OPT_B_PARAMS') ?></p>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Parameterized PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3, 'announcements', 'Company Updates', 'john-doe'); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 2. Authors Directory Integration -->
  <div class="nc-doc-card">
    <h3><?= i18n_r('NewsCenter/DOCS_SEC_AUTHORS') ?></h3>
    <p><?= i18n_r('NewsCenter/DOCS_SEC_AUTHORS_DESC') ?></p>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_A_SHORTCODE') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Authors Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>[get-newscenter-authors]</code></pre>
    </div>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_B_PHP') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Authors PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_authors')) { nc_show_authors(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 3. Categories Directory Integration -->
  <div class="nc-doc-card">
    <h3><?= i18n_r('NewsCenter/DOCS_SEC_CATS') ?></h3>
    <p><?= i18n_r('NewsCenter/DOCS_SEC_CATS_DESC') ?></p>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_A_SHORTCODE') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Categories Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>[get-newscenter-categories]</code></pre>
    </div>

    <h4><?= i18n_r('NewsCenter/DOCS_OPT_B_PHP') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Categories PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_categories')) { nc_show_categories(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 4. Sidebar & Footer Widgets -->
  <div class="nc-doc-card">
    <h3><?= i18n_r('NewsCenter/DOCS_SEC_WIDGETS') ?></h3>
    <p><?= i18n_r('NewsCenter/DOCS_SEC_WIDGETS_DESC') ?></p>

    <h4><?= i18n_r('NewsCenter/DOCS_RECENT_POSTS_LIST') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Recent Posts List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts_list')) { nc_show_recent_posts_list(5); } ?&gt;</code></pre>
    </div>

    <h4><?= i18n_r('NewsCenter/DOCS_MONTHLY_ARCHIVES') ?></h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Monthly Archives List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_archives')) { nc_show_archives(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 5. Template Engine Placeholders Reference -->
  <div class="nc-doc-card">
    <h3><?= i18n_r('NewsCenter/DOCS_SEC_PLACEHOLDERS') ?></h3>
    <p><?= i18n_r('NewsCenter/DOCS_SEC_PLACEHOLDERS_DESC') ?></p>

    <table class="nc-table">
      <thead>
        <tr>
          <th><?= i18n_r('NewsCenter/PLACEHOLDER_TAG') ?></th>
          <th><?= i18n_r('NewsCenter/AVAILABLE_CONTEXT') ?></th>
          <th><?= i18n_r('NewsCenter/DESCRIPTION') ?></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>{{article.title}}</code></td>
          <td>Single, List, Home</td>
          <td>Title of the article.</td>
        </tr>
        <tr>
          <td><code>{{article.url}}</code></td>
          <td>Single, List, Home</td>
          <td>Full web URL linking to the post.</td>
        </tr>
        <tr>
          <td><code>{{article.slug}}</code></td>
          <td>Single</td>
          <td>URL slug of the article.</td>
        </tr>
        <tr>
          <td><code>{{article.authors}}</code></td>
          <td>Single, List, Home</td>
          <td>Individual HTML links for all assigned authors (e.g. <code>&lt;a href="..."&gt;Author 1&lt;/a&gt;, &lt;a href="..."&gt;Author 2&lt;/a&gt;</code>).</td>
        </tr>
        <tr>
          <td><code>{{article.author}}</code></td>
          <td>Single, List, Home</td>
          <td>Primary or comma-separated author plain text name.</td>
        </tr>
        <tr>
          <td><code>{{article.author_url}}</code></td>
          <td>Single, List, Home</td>
          <td>URL linking to the primary author's archive.</td>
        </tr>
        <tr>
          <td><code>{{article.author_image}}</code></td>
          <td>Single, List, Home</td>
          <td>URL of the author's avatar/photo.</td>
        </tr>
        <tr>
          <td><code>{{article.featured_image}}</code></td>
          <td>Single, List, Home</td>
          <td>Featured image web URL.</td>
        </tr>
        <tr>
          <td><code>{{article.featured_image_thumb}}</code></td>
          <td>List, Home, Single</td>
          <td>Optimized thumbnail URL of the featured image (using <code>data/thumbs/thumbnail.[name]</code>).</td>
        </tr>
        <tr>
          <td><code>{{article.share}}</code></td>
          <td>Single, List, Home</td>
          <td>Interactive dropdown button offering Copy Link, X (Twitter), Facebook, WhatsApp, and Email (plus native mobile Web Share).</td>
        </tr>
        <tr>
          <td><code>{{article.excerpt}}</code></td>
          <td>Single, List, Home</td>
          <td>Trimmed summary text.</td>
        </tr>
        <tr>
          <td><code>{{article.content}}</code></td>
          <td>Single</td>
          <td>Full article body text including the local GLightbox bottom gallery tray.</td>
        </tr>
        <tr>
          <td><code>{{article.date}}</code></td>
          <td>Single, List, Home</td>
          <td>Formatted date matching your settings and locale.</td>
        </tr>
        <tr>
          <td><code>{{article.date('FORMAT')}}</code></td>
          <td>Single, List, Home</td>
          <td>Custom localized PHP date format segment (e.g. <code>{{article.date('F')}}</code>, <code>{{article.date('j')}}</code>, <code>{{article.date('Y')}}</code>).</td>
        </tr>
        <tr>
          <td><code>{{article.date_raw}}</code></td>
          <td>Single, List, Home</td>
          <td>Raw ISO datetime string for HTML5 <code>&lt;time&gt;</code> tags.</td>
        </tr>
        <tr>
          <td><code>{{article.categories}}</code></td>
          <td>Single</td>
          <td>HTML links of assigned categories.</td>
        </tr>
        <tr>
          <td><code>{{article.tags}}</code></td>
          <td>Single</td>
          <td>HTML links of assigned tags.</td>
        </tr>
        <tr>
          <td><code>{{author.name}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>Author's display name.</td>
        </tr>
        <tr>
          <td><code>{{author.slug}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>Author's slug string.</td>
        </tr>
        <tr>
          <td><code>{{author.image}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>URL of the author's avatar/photo.</td>
        </tr>
        <tr>
          <td><code>{{author.bio}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>Author's biographical text.</td>
        </tr>
        <tr>
          <td><code>{{author.url}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>URL to the author's article archive.</td>
        </tr>
        <tr>
          <td><code>{{author.count}}</code></td>
          <td>Author Item</td>
          <td>Number of published articles by this author.</td>
        </tr>
        <tr>
          <td><code>{{author.share}}</code></td>
          <td>Author Archive, Author Item</td>
          <td>Interactive share dropdown for this author profile URL.</td>
        </tr>
        <tr>
          <td><code>{{authors.items}}</code></td>
          <td>Authors List Container</td>
          <td>Renders all individual author cards.</td>
        </tr>
        <tr>
          <td><code>{{category.name}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>Category display name.</td>
        </tr>
        <tr>
          <td><code>{{category.slug}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>Category slug.</td>
        </tr>
        <tr>
          <td><code>{{category.image}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>URL of the category's banner image.</td>
        </tr>
        <tr>
          <td><code>{{category.description}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>Category description text.</td>
        </tr>
        <tr>
          <td><code>{{category.url}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>URL to the category's article archive.</td>
        </tr>
        <tr>
          <td><code>{{category.count}}</code></td>
          <td>Category Item</td>
          <td>Number of published articles in this category.</td>
        </tr>
        <tr>
          <td><code>{{category.share}}</code></td>
          <td>Category Archive, Category Item</td>
          <td>Interactive share dropdown for this category URL.</td>
        </tr>
        <tr>
          <td><code>{{categories.items}}</code></td>
          <td>Categories List Container</td>
          <td>Renders all individual category cards.</td>
        </tr>
        <tr>
          <td><code>{{list.items}}</code></td>
          <td>Article List Container (<code>list_posts</code>)</td>
          <td>Renders all individual <code>list_item</code> article cards in the main feed and archive listings.</td>
        </tr>
        <tr>
          <td><code>{{posts.pagination}}</code></td>
          <td>Article List Container (<code>list_posts</code>)</td>
          <td>Renders boxed numeric pagination links (<code>« 1 2 3 »</code>) when total posts exceed articles per page.</td>
        </tr>
        <tr>
          <td><code>{{articles.list}}</code></td>
          <td>Category Archive, Author Archive</td>
          <td>Rendered list of article cards and pagination matching the active category or author archive view.</td>
        </tr>
        <tr>
          <td><code>{{section.title}}</code></td>
          <td>Home Posts Container</td>
          <td>Heading title passed to the grid container.</td>
        </tr>
        <tr>
          <td><code>{{posts.items}}</code></td>
          <td>Home Posts Container</td>
          <td>Renders all individual <code>home_item</code> cards.</td>
        </tr>
        <tr>
          <td><code>{{news.url}}</code></td>
          <td>All Templates</td>
          <td>Base web URL of the main news section.</td>
        </tr>
        <tr>
          <td><code>frontend_css</code></td>
          <td>Templates Dropdown</td>
          <td>Full frontend stylesheet controlling typography, grid layout, cards, share dropdowns, pagination, and lightbox galleries via CodeMirror.</td>
        </tr>
      </tbody>
    </table>

    <h4 style="margin-top:20px;"><?= i18n_r('NewsCenter/DOCS_CONDITIONALS_TITLE') ?></h4>
    <div class="nc-code-wrapper">
	<div class="nc-code-header">
		<span>Image, Bio &amp; Custom Date Block Example</span>
		<button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)"><?= $svgCopy ?> Copy</button>
	</div>
	<pre><code>
	&lt;div class="postdate">
      		&lt;div class="month">{{article.date('F')}}&lt;/div>
      		&lt;div class="day">{{article.date('j')}}&lt;/div>
		&lt;div class="year">{{article.date('Y')}}&lt;/div>
	&lt;/div>

{{if:article.has_image}}
  &lt;div class="featured-img"&gt;&lt;img src="{{article.featured_image_thumb}}" alt="{{article.title}}"&gt;&lt;/div&gt;
{{/if:article.has_image}}

{{if:article.has_author_image}}
  &lt;img src="{{article.author_image}}" alt="{{article.author}}" class="author-avatar"&gt;
{{/if:article.has_author_image}}

{{if:author.has_image}}
  &lt;img src="{{author.image}}" alt="{{author.name}}" class="author-photo"&gt;
{{/if:author.has_image}}

{{if:author.has_bio}}
  &lt;p class="author-bio"&gt;{{author.bio}}&lt;/p&gt;
{{/if:author.has_bio}}

{{if:category.has_image}}
  &lt;img src="{{category.image}}" alt="{{category.name}}" class="cat-banner"&gt;
{{/if:category.has_image}}</code></pre>
    </div>
  </div>

  <!-- 6. Data Migration from Other Plugins -->
  <div class="nc-doc-card">
    <h3>6. <?= i18n_r('NewsCenter/MIGRATION_TITLE') ?></h3>
    <p>NewsCenter includes built-in one-click importers under <strong>NewsCenter → Settings</strong> that automatically appear whenever existing data from supported plugins is detected on your installation. Source files and databases are read safely without modifying or deleting your original data.</p>

    <div class="nc-migration-grid">
      <div class="nc-migration-box">
        <h4>SimpleBlog Importer (<code>data/other/blog.db</code>)</h4>
        <p style="font-size:13px; margin-bottom:8px;">Automatically detected when <code>data/other/blog.db</code> exists:</p>
        <ul>
          <li>Imports all published and draft posts, preserving publication dates, excerpts, and SEO meta descriptions.</li>
          <li>Migrates all categories and tags, preserving post relationships.</li>
          <li>Converts SimpleBlog cover photos and JSON photo galleries into NewsCenter's native GLightbox schema.</li>
          <li>Assigns imported entries to the active CMS administrator and automatically rebuilds <code>sitemap.xml</code>.</li>
        </ul>
      </div>

      <div class="nc-migration-box">
        <h4>News Manager Importer (<code>data/posts/*.xml</code>)</h4>
        <p style="font-size:13px; margin-bottom:8px;">Automatically detected when XML post files exist in <code>data/posts/</code>:</p>
        <ul>
          <li>Parses all XML post files, mapping public posts to <em>Published</em> and <code><private>Y</private></code> posts to <em>Draft</em>.</li>
          <li>Decodes single- and double-encoded HTML entities in post content, titles, and metadata so rich formatting renders cleanly.</li>
          <li>Extracts comma-separated <code><tags></code> and <code><author></code> attributes, creating matching NewsCenter tags and author profiles.</li>
          <li>Normalizes featured image paths and regenerates <code>sitemap.xml</code> upon completion.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
function ncCopyCode(button) {
  const wrapper = button.closest('.nc-code-wrapper');
  if (!wrapper) return;
  const codeEl = wrapper.querySelector('code');
  if (!codeEl) return;

  const text = codeEl.innerText.trim();
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      button.innerText = '✓ Copied!';
      button.classList.add('copied');
      setTimeout(() => {
        button.innerText = '📋 Copy';
        button.classList.remove('copied');
      }, 1800);
    }).catch(() => {
      fallbackCopy(text, button);
    });
  } else {
    fallbackCopy(text, button);
  }
}

function fallbackCopy(text, button) {
  const ta = document.createElement('textarea');
  ta.value = text;
  ta.style.position = 'fixed';
  ta.style.opacity = '0';
  document.body.appendChild(ta);
  ta.select();
  document.execCommand('copy');
  document.body.removeChild(ta);
  button.innerText = '✓ Copied!';
  button.classList.add('copied');
  setTimeout(() => {
    button.innerText = '📋 Copy';
    button.classList.remove('copied');
  }, 1800);
}
</script>