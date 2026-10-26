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
      <span class="nc-author">By <a href="{{article.author_url}}">{{article.author}}</a></span>
      <span class="nc-categories">{{article.categories}}</span>
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
            'list_item' => [
                'title'   => 'Article List Item Template',
                'content' => '<article class="nc-card">
  {{if:article.has_image}}
  <div class="nc-card-image">
    <a href="{{article.url}}"><img src="{{article.featured_image}}" alt="{{article.title}}"></a>
  </div>
  {{/if:article.has_image}}
  <div class="nc-card-body">
    <h2 class="nc-card-title"><a href="{{article.url}}">{{article.title}}</a></h2>
    <div class="nc-meta">
      <time>{{article.date}}</time>
      <span class="nc-author">By <a href="{{article.author_url}}">{{article.author}}</a></span>
    </div>
    <div class="nc-excerpt">{{article.excerpt}}</div>
    <a class="nc-read-more" href="{{article.url}}">Read More &rarr;</a>
  </div>
</article>'
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
    <a href="{{article.url}}"><img src="{{article.featured_image}}" alt="{{article.title}}" loading="lazy"></a>
  </div>
  {{/if:article.has_image}}
  <div class="nc-home-card-content">
    <div class="nc-home-card-meta">
      <span class="nc-home-card-date">{{article.date}}</span>
      <span class="nc-home-card-author">By <a href="{{article.author_url}}">{{article.author}}</a></span>
    </div>
    <h3 class="nc-home-card-title"><a href="{{article.url}}">{{article.title}}</a></h3>
    <p class="nc-home-card-excerpt">{{article.excerpt}}</p>
    <a href="{{article.url}}" class="nc-home-card-readmore">Read story &rarr;</a>
  </div>
</article>'
            ],
            'frontend_css' => [
                'title'   => 'Frontend CSS Stylesheet',
                'content' => '/* ==========================================================================
   NewsCenter Frontend Core Styles
   ========================================================================== */

/* Feed & Archive Layout */
.nc-news-feed {
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin: 20px 0;
  font-family: inherit;
}

.nc-filter-heading {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 2px solid #e2e8f0;
}
.nc-filter-heading h3 {
  margin: 0;
  font-size: 20px;
  color: #1e293b;
}

/* Feed Cards */
.nc-news-feed .nc-card {
  display: flex;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.nc-news-feed .nc-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
.nc-card-image {
  flex: 0 0 240px;
  background: #f1f5f9;
}
.nc-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.nc-card-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex: 1;
}
.nc-card-title {
  margin: 0 0 8px 0;
  font-size: 20px;
  line-height: 1.35;
}
.nc-card-title a {
  color: #0f172a;
  text-decoration: none;
}
.nc-card-title a:hover {
  color: #2563eb;
}
.nc-meta {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 12px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}
.nc-meta a {
  color: #64748b;
  text-decoration: none;
}
.nc-meta a:hover {
  color: #2563eb;
  text-decoration: underline;
}
.nc-excerpt {
  font-size: 14px;
  line-height: 1.6;
  color: #475569;
  margin-bottom: 15px;
  flex-grow: 1;
}
.nc-read-more {
  font-size: 13px;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
  align-self: flex-start;
}
.nc-read-more:hover {
  text-decoration: underline;
}

/* Single Article */
.nc-article-single {
  margin: 20px 0;
  font-family: inherit;
  color: #1e293b;
  line-height: 1.7;
}
.nc-article-single .nc-title {
  font-size: 32px;
  line-height: 1.25;
  margin: 0 0 12px 0;
  color: #0f172a;
}
.nc-article-single .nc-featured-image {
  margin: 20px 0;
}
.nc-article-single .nc-featured-image img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  display: block;
}
.nc-article-single .nc-content {
  font-size: 16px;
  margin-bottom: 30px;
}
.nc-article-single .nc-footer {
  padding-top: 15px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}
.nc-tags a {
  display: inline-block;
  background: #f1f5f9;
  color: #475569;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 12px;
  text-decoration: none;
  margin-right: 5px;
}
.nc-tags a:hover {
  background: #e2e8f0;
  color: #0f172a;
}
.nc-nav a {
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
  font-size: 14px;
}

/* Single Post Bottom GLightbox Gallery */
.nc-article-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 15px;
  margin: 30px 0 20px 0;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}
.nc-gallery-item {
  margin: 0;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.nc-gallery-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
.nc-gallery-item a {
  display: block;
  position: relative;
  text-decoration: none;
}
.nc-gallery-item img {
  width: 100%;
  height: 125px;
  object-fit: cover;
  display: block;
}
.nc-gallery-caption {
  font-size: 12px;
  color: #4b5563;
  padding: 8px 10px;
  margin: 0;
  text-align: center;
  background: #f9fafb;
  border-top: 1px solid #f3f4f6;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Home Grid & Shortcode */
.nc-home-news-section {
  margin: 30px 0;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}
.nc-home-news-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 20px;
  border-bottom: 2px solid #f1f5f9;
  padding-bottom: 10px;
}
.nc-home-news-title {
  margin: 0;
  font-size: 22px;
  color: #1e293b;
  font-weight: 700;
}
.nc-home-view-all {
  font-size: 14px;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}
.nc-home-view-all:hover {
  text-decoration: underline;
}
.nc-home-news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}
.nc-home-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  max-width: 380px;
  width: 100%;
  box-sizing: border-box;
}
.nc-home-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}
.nc-home-card-image img {
  width: 100%;
  height: 180px;
  object-fit: cover;
  display: block;
}
.nc-home-card-content {
  padding: 16px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}
.nc-home-card-meta {
  font-size: 12px;
  color: #64748b;
  margin-bottom: 6px;
  font-weight: 500;
  display: flex;
  gap: 8px;
}
.nc-home-card-author a {
  color: #64748b;
  text-decoration: none;
}
.nc-home-card-author a:hover {
  color: #2563eb;
  text-decoration: underline;
}
.nc-home-card-title {
  margin: 0 0 10px 0;
  font-size: 18px;
  line-height: 1.35;
}
.nc-home-card-title a {
  color: #0f172a;
  text-decoration: none;
  font-weight: 600;
}
.nc-home-card-title a:hover {
  color: #2563eb;
}
.nc-home-card-excerpt {
  font-size: 13.5px;
  color: #475569;
  line-height: 1.5;
  margin: 0 0 14px 0;
  flex-grow: 1;
}
.nc-home-card-readmore {
  font-size: 13px;
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
  align-self: flex-start;
}
.nc-home-card-readmore:hover {
  text-decoration: underline;
}

/* Pagination */
.nc-frontend-pagination {
  display: flex;
  gap: 6px;
  margin-top: 25px;
}
.nc-frontend-pagination a {
  padding: 6px 12px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #1e293b;
  text-decoration: none;
  border-radius: 4px;
  font-size: 13px;
}
.nc-frontend-pagination a.active {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

@media (max-width: 680px) {
  .nc-news-feed .nc-card {
    flex-direction: column;
  }
  .nc-card-image {
    flex: none;
    height: 180px;
  }
  .nc-home-card {
    max-width: 100%;
  }
}'
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
 */
if (!function_exists('nc_render_template')) {
    function nc_render_template($template_str, $vars = []) {
        if (empty($template_str)) {
            return '';
        }

        // 1. Process Conditionals: {{if:key}}...{{/if:key}}
        $template_str = preg_replace_callback('/\{\{if:([a-zA-Z0-9_\.]+)\}\}(.*?)\{\{\/if:\1\}\}/s', function($matches) use ($vars) {
            $key = $matches[1];
            $inner = $matches[2];
            return (!empty($vars[$key])) ? $inner : '';
        }, $template_str);

        // 2. Process Negated Conditionals: {{ifnot:key}}...{{/ifnot:key}}
        $template_str = preg_replace_callback('/\{\{ifnot:([a-zA-Z0-9_\.]+)\}\}(.*?)\{\{\/ifnot:\1\}\}/s', function($matches) use ($vars) {
            $key = $matches[1];
            $inner = $matches[2];
            return (empty($vars[$key])) ? $inner : '';
        }, $template_str);

        // 3. Process Value Placeholders: {{key}}
        $template_str = preg_replace_callback('/\{\{([a-zA-Z0-9_\.]+)\}\}/', function($matches) use ($vars) {
            $key = $matches[1];
            return isset($vars[$key]) ? (string)$vars[$key] : '';
        }, $template_str);

        return $template_str;
    }
}