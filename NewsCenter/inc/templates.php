<?php
if (!defined('IN_GS')) { die('Direct access denied.'); }

/**
 * Reset templates to default clean HTML & CSS schemas.
 */
if (!function_exists('nc_reset_default_templates')) {
    function nc_reset_default_templates() {
        $db = nc_get_db();
        $templates = [
            'single' => [
                'title'   => 'Single Article Template',
                'content' => '<article class="nc-article-single">
  <header class="nc-header">
    <h1 class="nc-title">{{article.title}}</h1>
    <div class="nc-meta">
      <time datetime="{{article.date_raw}}">{{article.date}}</time>
      <span class="nc-author">By <span class="nc-authors">{{article.authors}}</span></span>
      <span class="nc-categories">{{article.categories}}</span>
      <span class="nc-share-slot">{{article.share}}</span>
    </div>
  </header>
  {{if:article.has_image}}
  <figure class="nc-featured-image">
    <img src="{{article.featured_image}}" alt="{{article.title}}">
  </figure>
  {{/if:article.has_image}}
  <div class="nc-content">
    {{article.content}}
  </div>
  <footer class="nc-footer">
    <div class="nc-tags">{{article.tags}}</div>
    <div class="nc-nav"><a href="{{news.url}}">&larr; Back to News</a></div>
  </footer>
</article>'
            ],
            'list_posts' => [
                'title'   => 'Article List Container',
                'content' => '<div class="nc-news-feed">
  {{list.items}}
  {{posts.pagination}}
</div>'
            ],
            'list_item' => [
                'title'   => 'Article List Item Template',
                'content' => '<article class="nc-card">
  {{if:article.has_image}}
  <div class="nc-card-image">
    <a href="{{article.url}}"><img src="{{article.featured_image_thumb}}" alt="{{article.title}}" loading="lazy"></a>
  </div>
  {{/if:article.has_image}}
  <div class="nc-card-body">
    <h2 class="nc-card-title"><a href="{{article.url}}">{{article.title}}</a></h2>
    <div class="nc-meta">
      <time>{{article.date}}</time>
      <span class="nc-author">By <span class="nc-authors">{{article.authors}}</span></span>
      <span class="nc-share-slot">{{article.share}}</span>
    </div>
    <div class="nc-excerpt">{{article.excerpt}}</div>
    <a class="nc-read-more" href="{{article.url}}">Read More &rarr;</a>
  </div>
</article>'
            ],
            'category_archive' => [
                'title'   => 'Category Archive View Template',
                'content' => '<header class="nc-archive-header nc-category-header">
  {{if:category.has_image}}
  <div class="nc-archive-banner">
    <img src="{{category.image}}" alt="{{category.name}}" class="nc-category-cover">
  </div>
  {{/if:category.has_image}}
  <div class="nc-archive-info">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
      <h1 class="nc-archive-title" style="margin:0;">{{category.name}}</h1>
      <div>{{category.share}}</div>
    </div>
    {{if:category.has_description}}
    <div class="nc-archive-desc" style="margin-top:10px;">{{category.description}}</div>
    {{/if:category.has_description}}
  </div>
</header>
<div class="nc-news-feed">
  {{articles.list}}
</div>'
            ],
            'author_archive' => [
                'title'   => 'Author Archive View Template',
                'content' => '<header class="nc-archive-header nc-author-header">
  {{if:author.has_image}}
  <div class="nc-author-avatar-wrap">
    <img src="{{author.image}}" alt="{{author.name}}" class="nc-author-avatar-large">
  </div>
  {{/if:author.has_image}}
  <div class="nc-archive-info">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
      <h1 class="nc-archive-title" style="margin:0;">Articles by {{author.name}}</h1>
      <div>{{author.share}}</div>
    </div>
    {{if:author.has_bio}}
    <div class="nc-author-bio-full" style="margin-top:10px;">{{author.bio}}</div>
    {{/if:author.has_bio}}
  </div>
</header>
<div class="nc-news-feed">
  {{articles.list}}
</div>'
            ],
            'home_posts' => [
                'title'   => 'Home Page Posts Grid Container',
                'content' => '<section class="nc-home-news-section">
  <div class="nc-home-news-header">
    <h2 class="nc-home-news-title">{{section.title}}</h2>
    <a href="{{news.url}}" class="nc-home-view-all">View All News &rarr;</a>
  </div>
  <div class="nc-home-news-grid">
    {{posts.items}}
  </div>
</section>'
            ],
            'home_item' => [
                'title'   => 'Home Page Post Card Item',
                'content' => '<article class="nc-home-card">
  {{if:article.has_image}}
  <div class="nc-home-card-image">
    <a href="{{article.url}}"><img src="{{article.featured_image_thumb}}" alt="{{article.title}}" loading="lazy"></a>
  </div>
  {{/if:article.has_image}}
  <div class="nc-home-card-content">
    <div class="nc-home-card-meta">
      <span class="nc-home-card-date">{{article.date}}</span>
      <span class="nc-home-card-author">By <span class="nc-authors">{{article.authors}}</span></span>
      <span class="nc-share-slot" style="margin-left:auto;">{{article.share}}</span>
    </div>
    <h3 class="nc-home-card-title"><a href="{{article.url}}">{{article.title}}</a></h3>
    <p class="nc-home-card-excerpt">{{article.excerpt}}</p>
    <a href="{{article.url}}" class="nc-home-card-readmore">Read story &rarr;</a>
  </div>
</article>'
            ],
            'authors_list' => [
                'title'   => 'Authors Directory Grid Container',
                'content' => '<section class="nc-authors-section">
  <div class="nc-section-header">
    <h2 class="nc-section-title">Our Authors</h2>
  </div>
  <div class="nc-authors-grid">
    {{authors.items}}
  </div>
</section>'
            ],
            'author_item' => [
                'title'   => 'Author Profile Card Item',
                'content' => '<article class="nc-author-card">
  {{if:author.has_image}}
  <div class="nc-author-avatar-wrap">
    <a href="{{author.url}}"><img src="{{author.image}}" alt="{{author.name}}" class="nc-author-avatar"></a>
  </div>
  {{/if:author.has_image}}
  <div class="nc-author-card-body">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 class="nc-author-name" style="margin:0;"><a href="{{author.url}}">{{author.name}}</a></h3>
      <div>{{author.share}}</div>
    </div>
    <span class="nc-author-count">{{author.count}} Articles</span>
    {{if:author.has_bio}}
    <p class="nc-author-bio">{{author.bio}}</p>
    {{/if:author.has_bio}}
    <a href="{{author.url}}" class="nc-author-view-articles">View Articles &rarr;</a>
  </div>
</article>'
            ],
            'categories_list' => [
                'title'   => 'Categories Directory Grid Container',
                'content' => '<section class="nc-categories-section">
  <div class="nc-section-header">
    <h2 class="nc-section-title">Categories</h2>
  </div>
  <div class="nc-categories-grid">
    {{categories.items}}
  </div>
</section>'
            ],
            'category_item' => [
                'title'   => 'Category Card Item',
                'content' => '<article class="nc-category-card">
  {{if:category.has_image}}
  <div class="nc-category-img-wrap">
    <a href="{{category.url}}"><img src="{{category.image}}" alt="{{category.name}}" class="nc-category-thumb"></a>
  </div>
  {{/if:category.has_image}}
  <div class="nc-category-card-body">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 class="nc-category-title" style="margin:0;"><a href="{{category.url}}">{{category.name}}</a></h3>
      <div>{{category.share}}</div>
    </div>
    <span class="nc-category-count">{{category.count}} Articles</span>
    {{if:category.has_description}}
    <p class="nc-category-desc">{{category.description}}</p>
    {{/if:category.has_description}}
    <a href="{{category.url}}" class="nc-category-view-link">Browse Category &rarr;</a>
  </div>
</article>'
            ],
            'frontend_css' => [
                'title'   => 'Frontend CSS Stylesheet',
                'content' => '/* NewsCenter Frontend Core Styles */
.nc-news-feed { display: flex; flex-direction: column; gap: 24px; margin: 20px 0; }
.nc-card { display: flex; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: visible; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.nc-card-image { flex: 0 0 240px; background: #f1f5f9; border-top-left-radius: 7px; border-bottom-left-radius: 7px; overflow: hidden; }
.nc-card-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
.nc-card-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.nc-card-title { margin: 0 0 8px 0; font-size: 20px; }
.nc-card-title a { color: #0f172a; text-decoration: none; }
.nc-meta { font-size: 13px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.nc-meta a { color: #64748b; text-decoration: none; }
.nc-meta a:hover { color: #2563eb; text-decoration: underline; }
.nc-authors a { color: inherit; text-decoration: none; font-weight: 500; }
.nc-authors a:hover { color: #2563eb; text-decoration: underline; }
.nc-excerpt { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 15px; flex-grow: 1; }
.nc-read-more { font-size: 13px; color: #2563eb; text-decoration: none; font-weight: 600; }

/* Share Dropdown Button */
.nc-share-container { position: relative; display: inline-block; vertical-align: middle; z-index: 50; }
.nc-share-container.active { z-index: 10001; }
.nc-share-trigger { display: inline-flex; align-items: center; gap: 5px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 9px; font-size: 12px; color: #334155; cursor: pointer; transition: all 0.2s ease; line-height: 1.2; }
.nc-share-trigger:hover { background: #e2e8f0; color: #0f172a; }
.nc-share-dropdown { display: none; position: absolute; top: calc(100% + 6px); right: 0; z-index: 10002; min-width: 155px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); padding: 6px 0; }
.nc-share-container.active .nc-share-dropdown { display: flex !important; flex-direction: column; }
.nc-share-item { display: flex; align-items: center; gap: 9px; padding: 8px 14px; font-size: 12.5px; color: #334155; text-decoration: none; background: none; border: none; width: 100%; text-align: left; cursor: pointer; box-sizing: border-box; transition: background 0.15s ease, color 0.15s ease; }
.nc-share-item:hover { background: #f8fafc; color: #2563eb; }

/* Boxed Frontend Pagination */
.nc-frontend-pagination { display: flex; align-items: center; justify-content: center; gap: 6px; margin: 35px 0 20px 0; flex-wrap: wrap; }
.nc-page-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; font-size: 13.5px; font-weight: 600; color: #334155; text-decoration: none; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.2s ease; box-sizing: border-box; }
.nc-page-btn:hover:not(.nc-page-disabled):not(.active) { background: #f8fafc; color: #2563eb; border-color: #94a3b8; transform: translateY(-1px); }
.nc-page-btn.active { background: #2563eb; color: #ffffff; border-color: #2563eb; cursor: default; box-shadow: 0 2px 4px rgba(37,99,235,0.25); }
.nc-page-btn.nc-page-disabled { color: #cbd5e1; background: #f8fafc; border-color: #e2e8f0; cursor: not-allowed; box-shadow: none; }
.nc-page-prev, .nc-page-next { font-size: 16px; }

/* Archive Headers */
.nc-archive-header { display: flex; flex-direction: column; gap: 16px; margin: 20px 0 30px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
.nc-author-header { flex-direction: row; align-items: center; gap: 24px; }
.nc-archive-banner img { width: 100%; max-height: 260px; object-fit: cover; border-radius: 8px; display: block; }
.nc-author-avatar-large { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; display: block; border: 3px solid #e2e8f0; }
.nc-archive-title { margin: 0 0 8px; font-size: 28px; color: #0f172a; }
.nc-archive-desc, .nc-author-bio-full { font-size: 15px; color: #475569; line-height: 1.6; }

/* Homepage News Grid (Fixed 3-Column Ratio) */
.nc-home-news-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
@media (max-width: 900px) { .nc-home-news-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .nc-home-news-grid { grid-template-columns: 1fr; } }

/* Home Card */
.nc-home-card { display: flex; flex-direction: column; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: visible; text-align: left; position: relative; }
.nc-home-card-image { width: 100%; height: 200px; overflow: hidden; background: #f1f5f9; border-top-left-radius: 7px; border-top-right-radius: 7px; }
.nc-home-card-image img, .nc-home-news-grid img { width: 100%; height: 200px; object-fit: cover; display: block; }
.nc-home-card-content { padding: 16px; display: flex; flex-direction: column; flex: 1; }
.nc-home-card-meta { font-size: 12px; color: #64748b; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.nc-home-card-title { margin: 0 0 10px 0; font-size: 18px; line-height: 1.35; }
.nc-home-card-title a { color: #0f172a; text-decoration: none; }
.nc-home-card-title a:hover { color: #2563eb; text-decoration: underline; }
.nc-home-card-excerpt { font-size: 13.5px; color: #475569; line-height: 1.55; margin: 0 0 14px 0; flex-grow: 1; }
.nc-home-card-readmore { font-size: 13px; font-weight: 600; color: #2563eb; text-decoration: none; align-self: flex-start; }
.nc-home-card-readmore:hover { text-decoration: underline; }

/* Article Single */
.nc-article-single { margin: 20px 0; }
.nc-article-single .nc-title { font-size: 32px; margin: 0 0 12px 0; }
.nc-article-single .nc-featured-image img { max-width: 100%; border-radius: 8px; }

/* Single Gallery Tray */
.nc-article-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 15px; margin: 30px 0 20px 0; padding-top: 20px; border-top: 1px solid #e5e7eb; }
.nc-gallery-item { margin: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.nc-gallery-item img { width: 100%; height: 125px; object-fit: cover; display: block; }
.nc-gallery-caption { font-size: 12px; color: #4b5563; padding: 6px 10px; text-align: center; background: #f9fafb; }

/* Authors & Categories Cards */
.nc-authors-grid, .nc-categories-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.nc-author-card, .nc-category-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: visible; padding: 18px; display: flex; flex-direction: column; position: relative; }
.nc-author-avatar-wrap { margin-bottom: 12px; }
.nc-author-avatar { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; display: block; border: 2px solid #e2e8f0; }
.nc-category-img-wrap { margin: -18px -18px 14px -18px; border-top-left-radius: 7px; border-top-right-radius: 7px; overflow: hidden; }
.nc-category-thumb { width: 100%; height: 140px; object-fit: cover; display: block; }
.nc-author-name, .nc-category-title { margin: 0 0 6px 0; font-size: 19px; }
.nc-author-count, .nc-category-count { font-size: 12px; color: #64748b; margin-bottom: 10px; display: block; }
.nc-author-bio, .nc-category-desc { font-size: 13.5px; color: #475569; line-height: 1.5; margin: 0 0 14px 0; flex-grow: 1; }
.nc-author-view-articles, .nc-category-view-link { font-size: 13px; color: #2563eb; text-decoration: none; font-weight: 600; }'
            ]
        ];

        $stmt = $db->prepare("INSERT OR REPLACE INTO nc_templates (template_key, title, content, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        foreach ($templates as $k => $tpl) {
            $stmt->execute([$k, $tpl['title'], $tpl['content']]);
        }
    }
}

/**
 * Render a template string by replacing placeholders and parsing basic conditionals.
 * Supports parameterized date placeholders such as {{article.date('F')}} or {{article.updated('j')}}.
 */
if (!function_exists('nc_render_template')) {
    function nc_render_template($template_str, $vars = []) {
        if (empty($template_str)) {
            return '';
        }

        $template_str = preg_replace_callback('/\{\{if:([a-zA-Z0-9_\.]+)\}\}(.*?)\{\{\/if:\1\}\}/s', function($matches) use ($vars) {
            $key = $matches[1];
            $inner = $matches[2];
            return (!empty($vars[$key])) ? $inner : '';
        }, $template_str);

        $template_str = preg_replace_callback('/\{\{ifnot:([a-zA-Z0-9_\.]+)\}\}(.*?)\{\{\/ifnot:\1\}\}/s', function($matches) use ($vars) {
            $key = $matches[1];
            $inner = $matches[2];
            return (empty($vars[$key])) ? $inner : '';
        }, $template_str);

        $template_str = preg_replace_callback('/\{\{article\.(date|updated)\([\'"]([^\'"]+)[\'"]\)\}\}/', function($matches) use ($vars) {
            $field = $matches[1];
            $format = $matches[2];
            $raw_key = ($field === 'updated') ? 'article.updated_raw' : 'article.date_raw';
            $fallback_key = ($field === 'updated') ? 'article.updated' : 'article.date';

            if (!empty($vars[$raw_key])) {
                $ts = strtotime((string)$vars[$raw_key]);
                if ($ts !== false) {
                    return function_exists('nc_date') ? nc_date($format, $ts) : date($format, $ts);
                }
            }
            return isset($vars[$fallback_key]) ? (string)$vars[$fallback_key] : '';
        }, $template_str);

        $template_str = preg_replace_callback('/\{\{([a-zA-Z0-9_\.]+)\}\}/', function($matches) use ($vars) {
            $key = $matches[1];
            return isset($vars[$key]) ? (string)$vars[$key] : '';
        }, $template_str);

        return $template_str;
    }
}