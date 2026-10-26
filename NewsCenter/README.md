# NewsCenter for GetSimple CMS CE

Modern news and article management plugin powered by PDO SQLite, Autumnnote, and CodeMirror.

## Features
- **Modern Architecture**: Relational SQLite storage (`newscenter.db`), protected by `.htaccess`.
- **Zero jQuery**: NewsCenter admin interactions run purely on Vanilla JavaScript (ES6).
- **Zero Bootstrap**: Clean, responsive, self-contained CSS scoped with `.nc-` classes.
- **Rich Text Editor**: Integrated with Autumnnote rich text editing.
- **Media Inserter**: Direct image selection from CMS uploads directly below the editor.
- **Template Management**: CodeMirror template editor with SQLite persistence and restore-defaults feature.
- **Taxonomies**: Multi-category and tag management with article counters.
- **SEO & Sitemap**: Metadata generation, friendly URLs, and XML sitemap integration.

## Installation
1. Upload `NewsCenter.php` to your `plugins/` directory.
2. Upload the `NewsCenter/` directory to `plugins/NewsCenter/`.
3. Go to the CMS Admin -> Plugins, and verify **NewsCenter** is enabled.
4. Access the **News** tab from the top navigation bar.
