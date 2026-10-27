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
    <p style="color:var(--nc-text-muted); margin:4px 0 0 0;">Integration tags, theme PHP hooks, shortcode references, and template engine placeholders for GetSimple CMS CE.</p>
  </div>

  <!-- 1. Homepage Integration (Shortcodes & PHP) -->
  <div class="nc-doc-card">
    <h3>1. Homepage &amp; Custom Page Integration (Latest Posts)</h3>
    <p>Display your latest news stories with responsive cards and grid containers on your homepage or landing pages.</p>

    <h4>Option A: Page Content Shortcode</h4>
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

    <h4>Option B: Theme PHP Hook (template.php)</h4>
    <p>Call the function directly inside your theme's <code>template.php</code>:</p>
    
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>PHP Theme Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3); } ?&gt;</code></pre>
    </div>

    <p>With optional category, custom heading, and author filter parameters:</p>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Parameterized PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3, 'announcements', 'Company Updates', 'john-doe'); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 2. Authors Directory Integration -->
  <div class="nc-doc-card">
    <h3>2. Authors Directory (Avatars, Bios &amp; Archives)</h3>
    <p>Display your contributors in a responsive grid showing their avatar/photo, biographical summary, article counts, and individual archive links.</p>

    <h4>Option A: Page Content Shortcode</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Authors Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>[get-newscenter-authors]</code></pre>
    </div>

    <h4>Option B: Theme PHP Hook</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Authors PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_authors')) { nc_show_authors(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 3. Categories Directory Integration -->
  <div class="nc-doc-card">
    <h3>3. Categories Directory (Banners, Descriptions &amp; Archives)</h3>
    <p>Display all taxonomy categories in a card grid showing category banner images, descriptions, post counts, and links.</p>

    <h4>Option A: Page Content Shortcode</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Categories Shortcode</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>[get-newscenter-categories]</code></pre>
    </div>

    <h4>Option B: Theme PHP Hook</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Categories PHP Hook</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_categories')) { nc_show_categories(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 4. Sidebar & Footer Widgets -->
  <div class="nc-doc-card">
    <h3>4. Sidebar &amp; Footer Widgets</h3>
    <p>Lightweight list functions designed for narrow sidebars or compact footer columns.</p>

    <h4>Recent Posts (Text List)</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Recent Posts List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_recent_posts_list')) { nc_show_recent_posts_list(5); } ?&gt;</code></pre>
    </div>

    <h4>Monthly Archives</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Monthly Archives List</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>&lt;?php if (function_exists('nc_show_archives')) { nc_show_archives(); } ?&gt;</code></pre>
    </div>
  </div>

  <!-- 5. Template Engine Placeholders Reference -->
  <div class="nc-doc-card">
    <h3>5. Template Placeholders Reference</h3>
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
          <td>Formatted date matching your settings.</td>
        </tr>
        <tr>
          <td><code>{{article.date_raw}}</code></td>
          <td>Single</td>
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
          <td>Author Item</td>
          <td>Author's display name.</td>
        </tr>
        <tr>
          <td><code>{{author.slug}}</code></td>
          <td>Author Item</td>
          <td>Author's slug string.</td>
        </tr>
        <tr>
          <td><code>{{author.image}}</code></td>
          <td>Author Item</td>
          <td>URL of the author's avatar/photo.</td>
        </tr>
        <tr>
          <td><code>{{author.bio}}</code></td>
          <td>Author Item</td>
          <td>Author's biographical text.</td>
        </tr>
        <tr>
          <td><code>{{author.url}}</code></td>
          <td>Author Item</td>
          <td>URL to the author's article archive.</td>
        </tr>
        <tr>
          <td><code>{{author.count}}</code></td>
          <td>Author Item</td>
          <td>Number of published articles by this author.</td>
        </tr>
        <tr>
          <td><code>{{authors.items}}</code></td>
          <td>Authors List Container</td>
          <td>Renders all individual author cards.</td>
        </tr>
        <tr>
          <td><code>{{category.name}}</code></td>
          <td>Category Item</td>
          <td>Category display name.</td>
        </tr>
        <tr>
          <td><code>{{category.slug}}</code></td>
          <td>Category Item</td>
          <td>Category slug.</td>
        </tr>
        <tr>
          <td><code>{{category.image}}</code></td>
          <td>Category Item</td>
          <td>URL of the category's banner image.</td>
        </tr>
        <tr>
          <td><code>{{category.description}}</code></td>
          <td>Category Item</td>
          <td>Category description text.</td>
        </tr>
        <tr>
          <td><code>{{category.url}}</code></td>
          <td>Category Item</td>
          <td>URL to the category's article archive.</td>
        </tr>
        <tr>
          <td><code>{{category.count}}</code></td>
          <td>Category Item</td>
          <td>Number of published articles in this category.</td>
        </tr>
        <tr>
          <td><code>{{categories.items}}</code></td>
          <td>Categories List Container</td>
          <td>Renders all individual category cards.</td>
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
          <td>Full frontend stylesheet controlling typography, grid layout, cards, and lightbox galleries via CodeMirror.</td>
        </tr>
      </tbody>
    </table>

    <h4 style="margin-top:20px;">Conditionals Syntax</h4>
    <div class="nc-code-wrapper">
      <div class="nc-code-header">
        <span>Image &amp; Bio Conditionals Example</span>
        <button type="button" class="nc-code-copy-btn" onclick="ncCopyCode(this)">📋 Copy</button>
      </div>
      <pre><code>{{if:article.has_image}}
  &lt;div class="featured-img"&gt;&lt;img src="{{article.featured_image}}" alt="{{article.title}}"&gt;&lt;/div&gt;
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