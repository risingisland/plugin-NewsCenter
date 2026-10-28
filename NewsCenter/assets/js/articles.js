/**
 * NewsCenter - Article Editor, Sortable Gallery & Featured Thumbnail
 * Vanilla ES6 - Self-contained
 */

document.addEventListener('DOMContentLoaded', () => {
    // Admin-language data injected by edit-article.php (window.NC_I18N)
    const I18N = window.NC_I18N || {};
    const t = (key, fallback) => (I18N.strings && I18N.strings[key]) || fallback;
    const tAttr = (key, fallback) => t(key, fallback).replace(/"/g, '&quot;');

    const rawContentEl = document.getElementById('nc-article-content');
    const form = document.getElementById('nc-article-form');
    const galleryDataInput = document.getElementById('nc-gallery-data');
    const galleryCardsContainer = document.getElementById('nc-gallery-cards');

    // 1. Initialize Autumnnote WYSIWYG v2.7.1 umd
    function setParagraphSeparator() {
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) { /* ignore */ }
    }

    const BLOCK_TAGS = ['ADDRESS', 'ARTICLE', 'ASIDE', 'BLOCKQUOTE', 'DIV', 'DL', 'FIELDSET', 'FIGURE',
        'FOOTER', 'FORM', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'HEADER', 'HR', 'OL', 'P', 'PRE',
        'SECTION', 'TABLE', 'UL'];

    function isPlainDiv(node) {
        return node.tagName === 'DIV'
            && !node.className
            && !Array.from(node.children).some((c) => BLOCK_TAGS.indexOf(c.tagName) !== -1);
    }

    function divToParagraph(div) {
        const p = document.createElement('p');
        Array.from(div.attributes).forEach((a) => p.setAttribute(a.name, a.value));
        while (div.firstChild) p.appendChild(div.firstChild);
        return p;
    }

    function normalizeBlocks(html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = html || '';
        Array.from(tpl.content.children).forEach((node) => {
            if (isPlainDiv(node)) node.replaceWith(divToParagraph(node));
        });
        return tpl.innerHTML;
    }

    function bindLiveParagraphs() {
        const box = editor && editor.layoutInfo && editor.layoutInfo.container;
        const scope = box || (rawContentEl && rawContentEl.parentElement) || document;
        const editable = scope.querySelector('[contenteditable="true"]');
        if (!editable) {
            console.warn('NewsCenter: editable area not found, live <p> conversion disabled');
            return;
        }

        const convert = () => {
            const sel = window.getSelection();
            const inside = sel && sel.rangeCount && editable.contains(sel.anchorNode) && editable.contains(sel.focusNode);
            const saved = inside ? {
                aN: sel.anchorNode, aO: sel.anchorOffset, fN: sel.focusNode, fO: sel.focusOffset
            } : null;
            const map = new Map();

            Array.from(editable.children).forEach((node) => {
                if (!isPlainDiv(node)) return;
                const p = divToParagraph(node);
                map.set(node, p);
                editable.replaceChild(p, node);
            });

            if (saved && map.size) {
                const aN = map.get(saved.aN) || saved.aN;
                const fN = map.get(saved.fN) || saved.fN;
                try { sel.setBaseAndExtent(aN, saved.aO, fN, saved.fO); } catch (e) { /* ignore */ }
            }
        };

        const observer = new MutationObserver(() => {
            observer.disconnect();
            convert();
            observer.observe(editable, { childList: true });
        });

        convert(); 
        observer.observe(editable, { childList: true });
    }

    // Custom toolbar layout (one array per button group)
    const TOOLBAR_LAYOUT = [
        ['undo', 'redo'],
        ['paragraphStyle'],
        ['bold', 'italic', 'underline', 'strikethrough'],
		['superscriptBtn', 'subscriptBtn'],
        ['foreColor', 'backColor'],
        ['alignLeft', 'alignCenter', 'alignRight', 'alignJustify'],
        ['ul', 'ol', 'indent', 'outdent'],
        ['link', 'video', 'table', 'hr'],
        ['removeFormat'],
        ['codeview', 'fullscreen']
    ];

    function resolveToolbar(AN) {
        const lib = AN.buttons || {};
        const exportName = { strikethrough: 'strikeBtn' };
        return TOOLBAR_LAYOUT.map((group) => group.map((name) => {
            const key = exportName[name] || (name + 'Btn');
            return lib[key] || AN[key] || lib[name] || name;
        }));
    }

    let editor = null;
    const AN = window.AutumnNote || window.autumnnote;

    const baseOptions = {
        lang: I18N.lang || 'en',   // Autumn-Note UMD build has en, vi, ja, zh, fr, de, es, ko built in
        placeholder: t('EDITOR_PLACEHOLDER', 'Write your article content here...'),
        minHeight: 380,
        resizable: true,

        // Block formats in the dropdown - "Paragraph" is a <p>
        paragraphStyles: [
            { value: 'p', label: 'Paragraph' },
            { value: 'h2', label: 'Heading 2' },
            { value: 'h3', label: 'Heading 3' },
            { value: 'h4', label: 'Heading 4' },
            { value: 'blockquote', label: 'Quote' },
            { value: 'pre', label: 'Code' }
        ]
    };

    function afterCreate() {
        if (typeof editor.isEmpty === 'function' && editor.isEmpty() && typeof editor.setHTML === 'function') {
            editor.setHTML('<p><br></p>');
        }
        
        if (typeof editor.on === 'function') {
            editor.on('focus', setParagraphSeparator);
        }
        bindLiveParagraphs();
    }

    if (AN && rawContentEl) {
        setParagraphSeparator();

        try {
            editor = AN.create(rawContentEl, Object.assign({}, baseOptions, { toolbar: resolveToolbar(AN) }));

            const box = editor.layoutInfo && editor.layoutInfo.container;
            if (box && !box.querySelector('button, select')) {
                console.warn('NewsCenter: custom toolbar did not render, falling back to default toolbar');
                Promise.resolve(editor.destroy()).then(() => {
                    editor = AN.create(rawContentEl, baseOptions);
                    afterCreate();
                });
            } else {
                afterCreate();
            }
        } catch (err) {
            console.error('NewsCenter: editor failed to initialize', err);
        }
    }

    // 2. Featured Image Thumbnail Function
    const featuredInput = document.getElementById('nc-featured-image-input');
    const featuredThumbWrap = document.getElementById('nc-featured-thumb-wrap');
    const featuredThumbImg = document.getElementById('nc-featured-thumb-img');
    const featuredPlaceholder = document.getElementById('nc-featured-placeholder');
    const featuredRemoveBtn = document.getElementById('nc-featured-remove-btn');
    const selectFeaturedBtn = document.getElementById('nc-select-featured-btn');

    window.ncSetFeaturedThumb = function(url, thumbUrl) {
        const cleanUrl = (url || '').trim();
        const displaySrc = (thumbUrl || cleanUrl || '').trim();

        if (cleanUrl !== '') {
            featuredInput.value = cleanUrl;
            featuredThumbImg.src = displaySrc;
            featuredThumbImg.style.display = 'block';
            if (featuredPlaceholder) featuredPlaceholder.style.display = 'none';
            if (featuredRemoveBtn) featuredRemoveBtn.style.display = 'block';
            if (selectFeaturedBtn) selectFeaturedBtn.textContent = t('CHANGE_IMAGE', 'Change Image');
        } else {
            featuredInput.value = '';
            featuredThumbImg.src = '';
            featuredThumbImg.style.display = 'none';
            if (featuredPlaceholder) featuredPlaceholder.style.display = 'flex';
            if (featuredRemoveBtn) featuredRemoveBtn.style.display = 'none';
            if (selectFeaturedBtn) selectFeaturedBtn.textContent = t('CHOOSE_IMAGE', 'Choose Image');
        }
    };

    if (featuredRemoveBtn) {
        featuredRemoveBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.ncSetFeaturedThumb('');
        });
    }

    // 3. Load & Render Sortable Gallery
    let galleryItems = [];
    if (galleryDataInput && galleryDataInput.value) {
        try {
            galleryItems = JSON.parse(galleryDataInput.value) || [];
        } catch (e) {
            galleryItems = [];
        }
    }

    function syncGalleryInput() {
        if (galleryDataInput) {
            galleryDataInput.value = JSON.stringify(galleryItems);
        }
    }

    function renderGalleryCards() {
        if (!galleryCardsContainer) return;
        galleryCardsContainer.innerHTML = '';

        if (galleryItems.length === 0) {
            galleryCardsContainer.style.display = 'none';
            return;
        }

        galleryCardsContainer.style.display = 'grid';

        galleryItems.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'nc-gallery-card';
            card.dataset.index = String(index);
            card._ncItem = item;

            card.innerHTML = `
              <div class="nc-gallery-card-thumb">
                <img src="${item.thumb || item.url}" alt="${item.name || ''}" draggable="false">
                <span class="nc-drag-badge" title="${tAttr('DRAG_TO_REORDER', 'Drag to reorder')}">&#9776;</span>
                <button type="button" class="nc-gallery-card-close" title="${tAttr('REMOVE_IMAGE', 'Remove image')}">&times;</button>
              </div>
              <input type="text" class="nc-gallery-card-caption" placeholder="Caption (optional)" value="${item.caption ? String(item.caption).replace(/"/g, '&quot;') : ''}">
            `;

            const closeBtn = card.querySelector('.nc-gallery-card-close');
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const pos = galleryItems.indexOf(item);
                if (pos > -1) galleryItems.splice(pos, 1);
                syncGalleryInput();
                renderGalleryCards();
            });

            const captionInput = card.querySelector('.nc-gallery-card-caption');
            captionInput.addEventListener('input', () => {
                item.caption = captionInput.value;
                syncGalleryInput();
            });

            galleryCardsContainer.appendChild(card);
        });

        syncGalleryInput();
    }

    renderGalleryCards();

    // Gallery drag-sorting using PointerEvents
    function initGallerySort() {
        if (!galleryCardsContainer) return;
        const DRAG_THRESHOLD = 5;
        let drag = null;

        galleryCardsContainer.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (e.target.closest('input, textarea, button')) return;
            const card = e.target.closest('.nc-gallery-card');
            if (!card) return;
            drag = { card, startX: e.clientX, startY: e.clientY, active: false, ghost: null, offX: 0, offY: 0 };
        });

        function startDrag(e) {
            const rect = drag.card.getBoundingClientRect();
            drag.offX = drag.startX - rect.left;
            drag.offY = drag.startY - rect.top;

            const ghost = drag.card.cloneNode(true);
            const srcInput = drag.card.querySelector('input');
            const ghostInput = ghost.querySelector('input');
            if (srcInput && ghostInput) ghostInput.value = srcInput.value;
            ghost.classList.add('nc-gallery-ghost');
            ghost.style.width = rect.width + 'px';
            ghost.style.height = rect.height + 'px';
            ghost.style.left = (e.clientX - drag.offX) + 'px';
            ghost.style.top = (e.clientY - drag.offY) + 'px';
            document.body.appendChild(ghost);

            drag.ghost = ghost;
            drag.original = Array.from(galleryCardsContainer.children);
            drag.active = true;
            drag.card.classList.add('nc-dragging');
            document.body.classList.add('nc-sorting');
        }

        function moveGhost(e) {
            if (drag.ghost) {
                drag.ghost.style.left = (e.clientX - drag.offX) + 'px';
                drag.ghost.style.top = (e.clientY - drag.offY) + 'px';
            }
        }

        function cardAtPoint(x, y) {
            const cards = Array.from(galleryCardsContainer.children);
            for (const c of cards) {
                const r = c.getBoundingClientRect();
                if (x >= r.left && x <= r.left + r.width && y >= r.top && y <= r.top + r.height) return c;
            }
            return null;
        }

        function reorderLive(e) {
            const target = cardAtPoint(e.clientX, e.clientY);
            if (!target || target === drag.card) return;

            const cards = Array.from(galleryCardsContainer.children);
            if (cards.indexOf(drag.card) < cards.indexOf(target)) {
                galleryCardsContainer.insertBefore(drag.card, target.nextSibling);
            } else {
                galleryCardsContainer.insertBefore(drag.card, target);
            }
        }

        document.addEventListener('pointermove', (e) => {
            if (!drag) return;
            if (!drag.active) {
                if (Math.hypot(e.clientX - drag.startX, e.clientY - drag.startY) < DRAG_THRESHOLD) return;
                startDrag(e);
            }
            e.preventDefault();
            moveGhost(e);
            reorderLive(e);
        });

        function endDrag(commit) {
            if (!drag) return;
            const d = drag;
            drag = null;
            if (!d.active) return;

            if (d.ghost) d.ghost.remove();
            d.card.classList.remove('nc-dragging');
            document.body.classList.remove('nc-sorting');

            if (commit) {
                const cards = Array.from(galleryCardsContainer.children);
                const reordered = cards.map(c => c._ncItem);
                if (reordered.length === galleryItems.length && reordered.every(Boolean)) {
                    galleryItems = reordered;
                    cards.forEach((c, i) => { c.dataset.index = String(i); });
                    syncGalleryInput();
                } else {
                    d.original.forEach(c => galleryCardsContainer.appendChild(c));
                }
            } else {
                d.original.forEach(c => galleryCardsContainer.appendChild(c));
            }
        }

        document.addEventListener('pointerup', () => endDrag(true));
        document.addEventListener('pointercancel', () => endDrag(false));
    }

    initGallerySort();

    // 4. Form Submit Sync
    if (form) {
        form.addEventListener('submit', () => {
            if (editor && typeof editor.getHTML === 'function') {
                rawContentEl.value = normalizeBlocks(editor.getHTML());
            }
            syncGalleryInput();
        });
    }

    // 5. Title-to-Slug Generator
    const titleInput = document.getElementById('nc-title-input');
    const slugInput = document.getElementById('nc-slug-input');
    if (titleInput && slugInput && !slugInput.value) {
        titleInput.addEventListener('input', () => {
            if (!slugInput.dataset.touched) {
                slugInput.value = titleInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9 -]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            }
        });
        slugInput.addEventListener('change', () => {
            slugInput.dataset.touched = 'true';
        });
    }

    // 6. Sticky Status Sync (With Scheduled Detection)
    const statusSelect = document.getElementById('nc-status-select');
    const pubDateInput = document.getElementById('nc-published-at-input');
    const stickyStatusText = document.getElementById('nc-bar-status-text');

    const statusPrefix = t('STATUS', 'Status') + ': ';

    function refreshStickyStatus() {
        if (!statusSelect || !stickyStatusText) return;
        const currentVal = statusSelect.value;

        if (currentVal === 'published') {
            const pubTime = pubDateInput && pubDateInput.value ? new Date(pubDateInput.value).getTime() : 0;
            const nowTime = Date.now();
            if (pubTime > nowTime) {
                stickyStatusText.textContent = statusPrefix + t('SCHEDULED', 'Scheduled');
                return;
            }
            stickyStatusText.textContent = statusPrefix + t('PUBLISHED', 'Published');
        } else {
            stickyStatusText.textContent = statusPrefix + t('DRAFT', 'Draft');
        }
    }

    if (statusSelect) statusSelect.addEventListener('change', refreshStickyStatus);
    if (pubDateInput) pubDateInput.addEventListener('input', refreshStickyStatus);
    refreshStickyStatus();

    // 7. Media Picker & Modal Engine
    const modal = document.getElementById('nc-media-modal');
    const openGalleryBtn = document.getElementById('nc-open-media-modal');
    const closeBtns = document.querySelectorAll('.nc-modal-close, .nc-modal-close-btn, #nc-modal-x-close');
    const fileListContainer = document.getElementById('nc-modal-file-list');
    const insertBtn = document.getElementById('nc-insert-gallery-btn');
    const selectionInfo = document.getElementById('nc-selection-info');
    const searchInput = document.getElementById('nc-media-search');

    const tabBrowse = document.querySelector('.nc-modal-tab[data-tab="browse"]');
    const tabUpload = document.querySelector('.nc-modal-tab[data-tab="upload"]');
    const paneBrowse = document.getElementById('nc-pane-browse');
    const paneUpload = document.getElementById('nc-pane-upload');

    let allMediaFiles = [];
    const selectedImages = new Map();
    let currentMode = 'gallery';

    function updateSelectionUI() {
        const count = selectedImages.size;
        if (insertBtn) {
            insertBtn.disabled = (count === 0);
            insertBtn.textContent = count > 1 ? `${t('ADD_TO_GALLERY', 'Add to Gallery')} (${count})` : t('ADD_TO_GALLERY', 'Add to Gallery');
        }
        if (selectionInfo) {
            selectionInfo.textContent = count === 1
                ? t('IMAGE_SELECTED_ONE', '1 image selected')
                : t('IMAGES_SELECTED', '{count} images selected').replace('{count}', count);
        }
    }

    function setTab(name) {
        if (name === 'browse') {
            paneBrowse.style.display = 'block';
            paneUpload.style.display = 'none';
            tabBrowse.classList.add('active');
            tabUpload.classList.remove('active');
        } else {
            paneBrowse.style.display = 'none';
            paneUpload.style.display = 'block';
            tabBrowse.classList.remove('active');
            tabUpload.classList.add('active');
        }
    }

    if (tabBrowse && tabUpload) {
        tabBrowse.addEventListener('click', () => setTab('browse'));
        tabUpload.addEventListener('click', () => setTab('upload'));
    }

    if (openGalleryBtn) {
        openGalleryBtn.addEventListener('click', () => {
            currentMode = 'gallery';
            selectedImages.clear();
            updateSelectionUI();
            modal.style.display = 'block';
            setTab('browse');
            loadMedia();
        });
    }

    if (selectFeaturedBtn) {
        selectFeaturedBtn.addEventListener('click', () => {
            currentMode = 'featured';
            selectedImages.clear();
            updateSelectionUI();
            modal.style.display = 'block';
            setTab('browse');
            loadMedia();
        });
    }

    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => { modal.style.display = 'none'; });
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    function renderGrid(files) {
        fileListContainer.innerHTML = '';
        if (!files || files.length === 0) {
            fileListContainer.innerHTML = '<p style="text-align:center; padding:30px; color:#64748b;">' + t('NO_IMAGES_FOUND', 'No images found in uploads.') + '</p>';
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'nc-media-grid';

        files.forEach(file => {
            const card = document.createElement('div');
            card.className = 'nc-media-thumb';
            if (selectedImages.has(file.url)) {
                card.classList.add('selected');
            }

            card.innerHTML = `
              <img src="${file.thumb || file.url}" alt="${file.name}" loading="lazy">
              <p title="${file.name}">${file.name}</p>
            `;

            card.addEventListener('click', () => {
                if (currentMode === 'featured') {
                    window.ncSetFeaturedThumb(file.url, file.thumb);
                    modal.style.display = 'none';
                    return;
                }

                if (selectedImages.has(file.url)) {
                    selectedImages.delete(file.url);
                    card.classList.remove('selected');
                } else {
                    selectedImages.set(file.url, file);
                    card.classList.add('selected');
                }
                updateSelectionUI();
            });

            grid.appendChild(card);
        });

        fileListContainer.appendChild(grid);
    }

    function loadMedia() {
        fileListContainer.innerHTML = '<p style="text-align:center; padding:30px;">' + t('LOADING_MEDIA', 'Loading uploads...') + '</p>';
        fetch('load.php?id=NewsCenter&action=ajax_media', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                allMediaFiles = data.files || [];
                renderGrid(allMediaFiles);
            })
            .catch(err => {
                fileListContainer.innerHTML = '<p style="color:red; padding:20px;">' + t('LOAD_IMAGES_FAILED', 'Failed to load images:') + ' ' + err.message + '</p>';
            });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            if (!q) {
                renderGrid(allMediaFiles);
                return;
            }
            const filtered = allMediaFiles.filter(f => f.name.toLowerCase().includes(q));
            renderGrid(filtered);
        });
    }

    // Direct Upload Handler
    const dropzone = document.getElementById('nc-dropzone');
    const fileInput = document.getElementById('nc-file-input');
    const uploadStatus = document.getElementById('nc-upload-status');

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const fd = new FormData();
                fd.append('nc_csrf', modal.getAttribute('data-csrf') || '');
                for (let i = 0; i < fileInput.files.length; i++) {
                    fd.append('file[]', fileInput.files[i]);
                }

                uploadStatus.innerHTML = '<p>' + t('UPLOADING', 'Uploading...') + '</p>';
                fetch('load.php?id=NewsCenter&action=ajax_upload', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.ok && res.files && res.files.length > 0) {
                            if (currentMode === 'featured') {
                                window.ncSetFeaturedThumb(res.files[0].url, res.files[0].thumb);
                                modal.style.display = 'none';
                            } else {
                                res.files.forEach(f => selectedImages.set(f.url, f));
                                setTab('browse');
                                loadMedia();
                                updateSelectionUI();
                            }
                            uploadStatus.innerHTML = '';
                        } else {
                            uploadStatus.innerHTML = '<p style="color:red;">' + t('UPLOAD_FAILED', 'Upload failed.') + '</p>';
                        }
                    });
            }
        });
    }

    // Insert multiple into gallery
    if (insertBtn) {
        insertBtn.addEventListener('click', () => {
            selectedImages.forEach(file => {
                if (!galleryItems.some(i => i.url === file.url)) {
                    galleryItems.push({
                        url: file.url,
                        thumb: file.thumb || file.url,
                        name: file.name,
                        caption: ''
                    });
                }
            });
            renderGalleryCards();
            modal.style.display = 'none';
        });
    }
});