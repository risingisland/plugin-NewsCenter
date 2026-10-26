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
</style>

<div class="nc-container">
  <div class="nc-page-header" style="margin-bottom:20px;">
    <h2>NewsCenter Documentation &amp; Integration Guide</h2>
    <p style="color:var(--nc-text-muted); margin:4px 0 0 0;">Integration tags, theme PHP calls, and shortcode cheat-sheets for your GetSimple CMS CE templates.</p>
  </div>

  <!-- 1. Homepage Integration (Shortcodes & PHP) -->
  <div class="nc-doc-card">
    <h3>1. Homepage &amp; Custom Page Integration (Latest Posts)</h3>
    <p>Display your latest news stories with responsive cards and grid containers on your homepage or landing pages.</p>

    <h4>Option A: GetSimple Page Editor Shortcode</h4>
    <p>Paste this shortcode anywhere inside a regular page content editor (e.g. <strong>Pages &rarr; Edit &rarr; index</strong>):</p>
    
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>[get-newscenter-recent limit=3]</code></pre>
    </div>

    <p>Filter by category or author slug:</p>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Filtered Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>[get-newscenter-recent limit=3 category="featured" author="john-doe"]</code></pre>
    </div>

    <h4>Option B: Theme PHP Call (template.php)</h4>
    <p>Call the function directly inside your theme's <code>template.php</code>:</p>
    
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Theme Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3); } ?&gt;</code></pre>
    </div>

    <p>Custom title, category, and author parameters:</p>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Parameterized Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3, 'announcements', 'Company Updates', 'john-doe'); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 2. Sidebar Widgets -->
  <div class="nc-doc-card">
    <h3>2. Sidebar Widgets</h3>
    <p>Lightweight PHP calls designed for narrow sidebars or footer columns.</p>

    <h4>Recent Posts (List View)</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Sidebar List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts_list')) { nc_show_recent_posts_list(5); } ?&gt;</code></pre>
    </div>

    <h4>Monthly Archives</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Archives List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_archives')) { nc_show_archives(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 3. Template Engine Placeholders -->
  <div class="nc-doc-card">
    <h3>3. Template Placeholders Reference</h3>
    <p>These variables can be used when editing templates under <strong>NewsCenter &rarr; Templates</strong>:</p>

    <table class="nc-table">
      <thead>
        <tr>
          <th>Placeholder Tag</th>
          <th>Available Context</th>
          <th>Description</th>
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
          <td>List, Home</td>
          <td>Full web URL linking to the post.</td>
        </tr>
        <tr>
          <td><code>{{article.author}}</code></td>
          <td>Single, List, Home</td>
          <td>Assigned author name(s).</td>
        </tr>
        <tr>
          <td><code>{{article.author_url}}</code></td>
          <td>Single, List, Home</td>
          <td>URL linking to all articles written by this author (<code>?author=[slug]</code>).</td>
        </tr>
        <tr>
          <td><code>{{article.authors}}</code></td>
          <td>Single, List, Home</td>
          <td>HTML links for all assigned authors.</td>
        </tr>
        <tr>
          <td><code>{{article.excerpt}}</code></td>
          <td>Single, List, Home</td>
          <td>Trimmed summary text.</td>
        </tr>
        <tr>
          <td><code>{{article.content}}</code></td>
          <td>Single</td>
          <td>Full post body including bottom GLightbox gallery.</td>
        </tr>
        <tr>
          <td><code>{{article.featured_image}}</code></td>
          <td>Single, List, Home</td>
          <td>Featured image web URL.</td>
        </tr>
        <tr>
          <td><code>{{article.date}}</code></td>
          <td>Single, List, Home</td>
          <td>Formatted date matching your settings.</td>
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
          <td><code>{{section.title}}</code></td>
          <td>Home Posts Container</td>
          <td>Heading title passed to the home grid.</td>
        </tr>
        <tr>
          <td><code>{{posts.items}}</code></td>
          <td>Home Posts Container</td>
          <td>Renders all individual <code>home_item</code> cards.</td>
        </tr>
        <tr>
          <td><code>frontend_css</code></td>
          <td>Templates Dropdown</td>
          <td>Full frontend stylesheet controlling typography, grid layout, cards, and lightbox galleries.</td>
        </tr>
      </tbody>
    </table>

    <h4 style="margin-top:20px;">Conditionals Syntax</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Conditional Syntax</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>{{if:article.has_image}}
  &lt;div class="featured-img"&gt;&lt;img src="{{article.featured_image}}" alt="{{article.title}}"&gt;&lt;/div&gt;
{{/if:article.has_image}}</code></pre>
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