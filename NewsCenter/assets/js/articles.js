/**
 * NewsCenter - Article Editor & Media Browser Bridge
 * Vanilla ES6 - No jQuery dependency.
 */

document.addEventListener('DOMContentLoaded', () => {
    const rawContentEl = document.getElementById('nc-article-content');
    const form = document.getElementById('nc-article-form');
    const previewContainer = document.getElementById('nc-media-selection-preview');
    let editor = null;
    let savedSelectionRange = null;

    // 1. Initialize Autumnnote
    const AN = window.AutumnNote || window.autumnnote;
    if (AN && rawContentEl) {
        editor = AN.create(rawContentEl, {
            placeholder: 'Write your article content here...',
            minHeight: 380,
            resizable: true
        });
    }

    // Helper to preserve editor selection when opening modals
    function saveEditorSelection() {
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            savedSelectionRange = sel.getRangeAt(0).cloneRange();
        }
    }

    function restoreEditorSelection() {
        const editable = document.querySelector('.an-editable') || rawContentEl;
        if (editable) {
            editable.focus();
        }
        if (savedSelectionRange) {
            const sel = window.getSelection();
            if (sel) {
                sel.removeAllRanges();
                sel.addRange(savedSelectionRange);
            }
        }
    }

    // 2. Synchronize content before form submission
    if (form && editor) {
        form.addEventListener('submit', () => {
            if (typeof editor.getHTML === 'function') {
                rawContentEl.value = editor.getHTML();
            }
        });
    }

    // 3. Automated slug generation from title
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

    // 4. Media Browser Modal Controller
    const modal = document.getElementById('nc-media-modal');
    const openBtn = document.getElementById('nc-open-media-modal');
    const closeBtns = document.querySelectorAll('.nc-modal-close, .nc-modal-close-btn');
    const fileListContainer = document.getElementById('nc-modal-file-list');
    const insertBtn = document.getElementById('nc-insert-gallery-btn');
    const selectionInfo = document.getElementById('nc-selection-info');
    const searchInput = document.getElementById('nc-media-search');

    // Tabs
    const tabBrowse = document.querySelector('.nc-modal-tab[data-tab="browse"]');
    const tabUpload = document.querySelector('.nc-modal-tab[data-tab="upload"]');
    const paneBrowse = document.getElementById('nc-pane-browse');
    const paneUpload = document.getElementById('nc-pane-upload');

    // Upload elements
    const dropzone = document.getElementById('nc-dropzone');
    const fileInput = document.getElementById('nc-file-input');
    const uploadStatus = document.getElementById('nc-upload-status');

    let allMediaFiles = [];
    const selectedImages = new Map(); // url => file object
    const insertedGalleryImages = []; // Array of images displayed in panel preview
    let pickerMode = 'gallery'; // 'gallery' or 'featured'

    function updateSelectionUI() {
        const count = selectedImages.size;
        if (insertBtn) {
            insertBtn.disabled = (count === 0);
            insertBtn.textContent = count > 1 ? `Insert Gallery (${count})` : (count === 1 ? 'Insert Image' : 'Insert Selected');
        }
        if (selectionInfo) {
            selectionInfo.textContent = count === 1 ? '1 image selected' : `${count} images selected`;
        }
    }

    function renderInsertedGalleryPreview() {
        if (!previewContainer) return;
        previewContainer.innerHTML = '';

        if (insertedGalleryImages.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }

        previewContainer.style.display = 'grid';

        insertedGalleryImages.forEach((img, idx) => {
            const card = document.createElement('div');
            card.className = 'nc-preview-card';

            const imageEl = document.createElement('img');
            imageEl.src = img.thumb || img.url;
            imageEl.alt = img.name;

            const nameEl = document.createElement('span');
            nameEl.className = 'nc-preview-card-name';
            nameEl.textContent = img.name;
            nameEl.title = img.name;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'nc-preview-card-remove';
            removeBtn.innerHTML = '&times;';
            removeBtn.title = 'Remove from preview';

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                insertedGalleryImages.splice(idx, 1);
                renderInsertedGalleryPreview();
            });

            card.appendChild(imageEl);
            card.appendChild(nameEl);
            card.appendChild(removeBtn);
            previewContainer.appendChild(card);
        });
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

    // Modal open/close
    if (openBtn && modal) {
        openBtn.addEventListener('click', () => {
            saveEditorSelection();
            pickerMode = 'gallery';
            modal.style.display = 'block';
            setTab('browse');
            fetchMediaFiles();
        });

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    }

    function renderMediaGrid(filesToRender) {
        fileListContainer.innerHTML = '';

        if (!filesToRender || filesToRender.length === 0) {
            fileListContainer.innerHTML = '<p class="nc-empty-state">No images found. Click the "Upload Images" tab to add files.</p>';
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'nc-media-grid';

        filesToRender.forEach(file => {
            const item = document.createElement('div');
            item.className = 'nc-media-thumb';
            if (selectedImages.has(file.url)) {
                item.classList.add('selected');
            }

            const img = document.createElement('img');
            img.src = file.thumb || file.url;
            img.alt = file.name;
            img.loading = 'lazy';
            img.onerror = () => { img.src = file.url; };

            const p = document.createElement('p');
            p.textContent = file.name;
            p.title = file.path || file.name;

            const tick = document.createElement('span');
            tick.className = 'nc-media-tick';
            tick.textContent = '✓';

            item.appendChild(img);
            item.appendChild(p);
            item.appendChild(tick);

            item.addEventListener('click', () => {
                if (pickerMode === 'featured') {
                    const featuredInput = document.getElementById('nc-featured-image-input');
                    if (featuredInput) {
                        featuredInput.value = file.url;
                    }
                    modal.style.display = 'none';
                    return;
                }

                if (selectedImages.has(file.url)) {
                    selectedImages.delete(file.url);
                    item.classList.remove('selected');
                } else {
                    selectedImages.set(file.url, file);
                    item.classList.add('selected');
                }
                updateSelectionUI();
            });

            grid.appendChild(item);
        });

        fileListContainer.appendChild(grid);
    }

    function fetchMediaFiles() {
        fileListContainer.innerHTML = '<p>Scanning CMS uploads directory...</p>';

        fetch('load.php?id=NewsCenter&action=ajax_media', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                allMediaFiles = data.files || [];
                renderMediaGrid(allMediaFiles);
                updateSelectionUI();
            })
            .catch(err => {
                fileListContainer.innerHTML = '<p class="nc-alert-error" style="padding:10px;">Error loading media: ' + err.message + '</p>';
            });
    }

    // Search filter
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            if (!q) {
                renderMediaGrid(allMediaFiles);
                return;
            }
            const filtered = allMediaFiles.filter(f => f.name.toLowerCase().includes(q) || (f.path && f.path.toLowerCase().includes(q)));
            renderMediaGrid(filtered);
        });
    }

    // Upload Handling
    function uploadFiles(files) {
        if (!files || files.length === 0) return;

        uploadStatus.innerHTML = '<p>Uploading ' + files.length + ' file(s)...</p>';
        const fd = new FormData();
        const csrfToken = modal.getAttribute('data-csrf') || '';
        fd.append('nc_csrf', csrfToken);

        for (let i = 0; i < files.length; i++) {
            fd.append('file[]', files[i]);
        }

        fetch('load.php?id=NewsCenter&action=ajax_upload', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(d => {
            if (d.ok) {
                uploadStatus.innerHTML = '<p class="nc-alert-success" style="padding:8px;">✓ Upload complete!</p>';
                if (d.files) {
                    d.files.forEach(f => {
                        allMediaFiles.unshift(f);
                        selectedImages.set(f.url, f);
                    });
                }
                setTimeout(() => {
                    uploadStatus.innerHTML = '';
                    setTab('browse');
                    renderMediaGrid(allMediaFiles);
                    updateSelectionUI();
                }, 800);
            } else {
                uploadStatus.innerHTML = '<p class="nc-alert-error" style="padding:8px;">✕ ' + (d.error || (d.errors ? d.errors.join('<br>') : 'Upload failed')) + '</p>';
            }
        })
        .catch(err => {
            uploadStatus.innerHTML = '<p class="nc-alert-error" style="padding:8px;">✕ Upload error: ' + err.message + '</p>';
        });
    }

    if (dropzone && fileInput) {
        dropzone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            uploadFiles(fileInput.files);
            fileInput.value = '';
        });

        ['dragenter', 'dragover'].forEach(ev => {
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(ev => {
            dropzone.addEventListener(ev, (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer && e.dataTransfer.files) {
                uploadFiles(e.dataTransfer.files);
            }
        });
    }

    // Insert Gallery / Images Button
    if (insertBtn) {
        insertBtn.addEventListener('click', () => {
            if (selectedImages.size === 0) return;

            let insertHtml = '';
            const chosenList = Array.from(selectedImages.values());

            // Add to the visual thumbnail preview below editor
            chosenList.forEach(img => {
                insertedGalleryImages.push(img);
            });
            renderInsertedGalleryPreview();

            if (chosenList.length === 1) {
                const img = chosenList[0];
                insertHtml = `<p><img src="${img.url}" alt="${img.name}" class="an-image"></p>\n`;
            } else {
                insertHtml = '<div class="nc-frontend-gallery">\n';
                chosenList.forEach((img) => {
                    insertHtml += `  <figure class="nc-gallery-item"><img src="${img.url}" alt="${img.name}"></figure>\n`;
                });
                insertHtml += '</div>\n';
            }

            // Restore focus and cursor selection back into Autumnnote canvas
            restoreEditorSelection();

            if (editor && typeof editor.insertHTML === 'function') {
                editor.insertHTML(insertHtml);
            } else {
                const editable = document.querySelector('.an-editable');
                if (editable) {
                    editable.focus();
                    document.execCommand('insertHTML', false, insertHtml);
                } else if (rawContentEl) {
                    rawContentEl.value += insertHtml;
                }
            }

            selectedImages.clear();
            updateSelectionUI();
            modal.style.display = 'none';
        });
    }

    // 5. Featured Image Picker
    const selectFeaturedBtn = document.getElementById('nc-select-featured-btn');
    if (selectFeaturedBtn && modal) {
        selectFeaturedBtn.addEventListener('click', () => {
            pickerMode = 'featured';
            modal.style.display = 'block';
            setTab('browse');
            fetchMediaFiles();
        });
    }
});