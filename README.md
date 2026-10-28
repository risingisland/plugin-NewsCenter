# NewsCenter

A news and article management plugin for [GetSimple CMS CE](https://getsimple-ce.ovh/). Articles, categories, tags and authors are stored in SQLite, written in a WYSIWYG editor, and rendered on the front end through editable templates.

---

## Features

- **Article management** with draft / published status and scheduled publishing (a published article with a future date stays hidden until that date)
- **WYSIWYG editor** powered by Autumn-Note, with a media picker modal and drag-and-drop image upload
- **Featured image** and a **sortable image gallery** per article, shown on the front end with GLightbox
- **Categories** (with description, banner image and sort order), **tags** and **multiple authors per article** (with bio and avatar)
- **SEO fields**: custom meta title and meta description, canonical URL and `og:url` tags, and automatic `sitemap.xml` generation
- **Editable front-end templates** with CodeMirror (HTML and CSS), using `{{placeholder}}` tags and `{{if:...}}` conditionals
- **Homepage and sidebar widgets** via shortcodes or PHP functions: recent posts, authors directory, categories directory, recent posts list, monthly archives
- **Fancy URL support** with generated `.htaccess` rules, plus an optional trailing-slash setting
- **Built-in documentation** inside the admin (News → Docs)

---

## Requirements

- GetSimple CMS CE
- PHP 7.4 or newer
- PHP `pdo_sqlite` extension enabled

If `pdo_sqlite` is missing, the plugin stops with an error message instead of loading.

---

## Installation

1. Unzip the package into your GetSimple `plugins/` directory so that you end up with:

   ```
   plugins/
   ├── NewsCenter.php
   └── NewsCenter/
       ├── admin/
       ├── assets/
       ├── inc/
       └── lang/
   ```

2. In the GetSimple admin, open the **Plugins** tab and enable **NewsCenter**.
3. A new **News** tab appears in the top navigation.

On first load the plugin creates its database, a default author (taken from the logged-in username), default settings and the default templates. No manual setup is needed.

### Data location

| What | Where |
| --- | --- |
| SQLite database | `data/other/newscenter/newscenter.db` |
| Uploaded images | `data/uploads/` (the standard GetSimple uploads folder) |
| Generated sitemap | `sitemap.xml` in the site root |

The data folder is created with an `.htaccess` file that denies direct web access. Include `data/other/newscenter/` in your backups.

---

## Configuration

Go to **News → Settings**.

| Setting | Default | Notes |
| --- | --- | --- |
| News page slug | `news` | Slug of an existing GetSimple page that will display the news section. Pick it from the dropdown or type it in. |
| Trailing slash | On | Adds a trailing slash to generated URLs when fancy URLs are enabled. |
| Posts per page | `10` | 1 to 100. |
| Excerpt length | `250` | 50 to 1000 characters. |
| Date format | `F j, Y` | Any PHP `date()` format. |
| Sitemap priority | `0.8` | Priority given to article URLs (1.0, 0.8, 0.6 or 0.4). |

### Setting up the news page

1. Create a normal page in GetSimple (for example with the slug `news`). Its own content is replaced by the plugin output.
2. Set that slug under **News → Settings → News page slug**.

### Fancy URLs

With GetSimple fancy URLs enabled, copy the rewrite rules shown in **Settings → .htaccess Rules** into your site's `.htaccess`. They update live as you change the slug. With slug `news`, they look like this:

```apache
RewriteRule ^news/post/([A-Za-z0-9_-]+)/?$ index.php?id=news&post=$1 [QSA,L]
RewriteRule ^news/category/([A-Za-z0-9_-]+)/?$ index.php?id=news&category=$1 [QSA,L]
RewriteRule ^news/tag/([A-Za-z0-9_-]+)/?$ index.php?id=news&tag=$1 [QSA,L]
RewriteRule ^news/author/([A-Za-z0-9_-]+)/?$ index.php?id=news&author=$1 [QSA,L]
RewriteRule ^news/archive/([0-9]{4}-[0-9]{2})/?$ index.php?id=news&archive=$1 [QSA,L]
RewriteRule ^news/page/([0-9]+)/?$ index.php?id=news&page=$1 [QSA,L]
```

Without fancy URLs the plugin falls back to query strings, such as `index.php?id=news&post=my-article`.

### Sitemap

`sitemap.xml` is regenerated whenever GetSimple saves its sitemap, and includes both CMS pages and published articles. Use **Settings → Rebuild sitemap now** to regenerate it manually.

---

## Admin sections

| Section | Purpose |
| --- | --- |
| Dashboard | Overview of your content |
| Articles | List, edit and delete articles |
| New Article | Create an article |
| Categories | Manage categories (name, slug, description, banner image, order) |
| Tags | Manage tags |
| Authors | Manage author profiles (name, slug, bio, avatar) |
| Templates | Edit front-end HTML templates and the front-end stylesheet |
| Settings | Slug, pagination, date format, sitemap, rewrite rules |
| Docs | Shortcode, PHP and placeholder reference |

---

## Displaying content on your site

### Shortcodes

Place these in any page's content:

```
[get-newscenter-recent limit=3]
[get-newscenter-recent limit=3 category="featured" author="john-doe"]
[get-newscenter-authors]
[get-newscenter-categories]
```

`limit` defaults to 3. `category` and `author` take slugs. If you use both filters, keep them in the order `limit`, `category`, `author`.

### PHP functions for themes

```php
<?php if (function_exists('nc_show_recent_posts')) { nc_show_recent_posts(3); } ?>

<?php if (function_exists('nc_show_recent_posts')) {
    nc_show_recent_posts(3, 'announcements', 'Company Updates', 'john-doe');
} ?>

<?php if (function_exists('nc_show_authors')) { nc_show_authors(); } ?>
<?php if (function_exists('nc_show_categories')) { nc_show_categories(); } ?>
<?php if (function_exists('nc_show_recent_posts_list')) { nc_show_recent_posts_list(5); } ?>
<?php if (function_exists('nc_show_archives')) { nc_show_archives(); } ?>
```

| Function | Arguments | Output |
| --- | --- | --- |
| `nc_show_recent_posts()` | `$limit = 3`, `$category_slug = ''`, `$title = 'Latest News'`, `$author_slug = ''` | Card grid of recent posts |
| `nc_show_authors()` | none | Authors directory |
| `nc_show_categories()` | none | Categories directory |
| `nc_show_recent_posts_list()` | `$limit = 5` | Simple linked list, suited to sidebars |
| `nc_show_archives()` | none | Monthly archive list with post counts |

### SEO tags

The plugin hooks into `theme-header` to print canonical and `og:url` tags on news pages. Make sure your theme calls `get_header()` so they are output.

### Search

Appending `?nc_query=term` to the news page URL displays search results.

---

## Templates

Front-end markup is stored in the database and edited under **News → Templates**. Each template can be reset to its default.

| Template key | Used for |
| --- | --- |
| `single` | Single article view |
| `list_item` | Article card in lists and archives |
| `category_archive` | Category archive header |
| `author_archive` | Author archive header |
| `home_posts` | Container for the recent-posts grid |
| `home_item` | Card in the recent-posts grid |
| `authors_list` / `author_item` | Authors directory |
| `categories_list` / `category_item` | Categories directory |
| `frontend_css` | Front-end stylesheet |

### Placeholders

| Tag | Context | Description |
| --- | --- | --- |
| `{{article.title}}` | Single, list, home | Article title |
| `{{article.url}}` | List, home | Link to the article |
| `{{article.slug}}` | Single | Article slug |
| `{{article.authors}}` | Single, list, home | Linked names of all assigned authors |
| `{{article.author}}` | Single, list, home | Author name(s) as plain text |
| `{{article.author_url}}` | Single, list, home | Primary author's archive URL |
| `{{article.author_image}}` | Single, list, home | Primary author's avatar URL |
| `{{article.featured_image}}` | Single, list, home | Featured image URL |
| `{{article.excerpt}}` | Single, list, home | Trimmed summary |
| `{{article.content}}` | Single | Full body, including the gallery tray |
| `{{article.date}}` | Single, list, home | Date in your configured format |
| `{{article.date_raw}}` | Single | ISO datetime, for `<time datetime="">` |
| `{{article.categories}}` | Single | Linked categories |
| `{{article.tags}}` | Single | Linked tags |
| `{{author.name}}`, `{{author.slug}}`, `{{author.image}}`, `{{author.bio}}`, `{{author.url}}`, `{{author.count}}` | Author item | Author profile data and published article count |
| `{{authors.items}}` | Authors list | All rendered author cards |
| `{{category.name}}`, `{{category.slug}}`, `{{category.image}}`, `{{category.description}}`, `{{category.url}}`, `{{category.count}}` | Category item | Category data and published article count |
| `{{categories.items}}` | Categories list | All rendered category cards |
| `{{section.title}}` | Home posts container | Heading passed to the grid |
| `{{posts.items}}` | Home posts container | All rendered `home_item` cards |
| `{{news.url}}` | All templates | Base URL of the news section |

### Conditionals

Wrap optional markup so it only renders when the value exists:

```html
{{if:article.has_image}}
  <img src="{{article.featured_image}}" alt="{{article.title}}">
{{/if:article.has_image}}
```

Available conditions: `article.has_image`, `article.has_author_image`, `author.has_image`, `author.has_bio`, `category.has_image`.

### Custom admin styling

If `assets/css/custom.css` exists, it is loaded after `admin.css` on NewsCenter admin screens.

---

## Security

- All admin actions require a valid GetSimple admin session.
- Forms and delete links are protected with GetSimple nonces (CSRF tokens).
- Article HTML is sanitized before saving and rendering: `<script>` and `<iframe>` tags, inline `on*` event handlers, and `javascript:`, `vbscript:` and `data:` URLs in `href` / `src` are removed.
- Image uploads are validated by content (not just extension). Only JPEG, PNG, GIF and WebP are accepted, and filenames are cleaned and de-duplicated.
- Database access uses prepared statements.
- The SQLite folder is protected from direct web access via `.htaccess`. On servers that ignore `.htaccess` (Nginx, for example), block `data/other/newscenter/` in your server config.

---

## Bundled libraries

| Library | Purpose |
| --- | --- |
| [Autumn-Note](https://github.com/autumnnote) 2.7.1 | WYSIWYG article editor |
| [CodeMirror](https://codemirror.net/) | Template and CSS editing |
| [GLightbox](https://github.com/biati-digital/glightbox) | Front-end image gallery lightbox |

All assets are served locally from `assets/vendor/`, with no CDN dependencies.

---

## Translating

Language files live in `NewsCenter/lang/`. To add a language, copy `en_US.php` to a new file named after the locale (for example `fr_FR.php`) and translate the values. Keep the array keys unchanged.

---

## Uninstalling

Disable the plugin from the Plugins tab, then delete `plugins/NewsCenter.php` and `plugins/NewsCenter/`. Your articles remain in `data/other/newscenter/newscenter.db` until you delete that folder yourself.

---

## Credits

Built for [GetSimple CMS CE](https://getsimple-ce.ovh/) by *🏝️ risingisland*.
