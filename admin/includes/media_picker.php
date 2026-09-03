<!-- Media Picker Modal -->
<div id="mediaPickerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; width: 90%; max-width: 1000px; height: 80vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i data-feather="image" style="color: var(--primary);"></i> Select Media
            </h3>
            <button onclick="closeMediaPicker()" style="background: none; border: none; cursor: pointer; color: var(--muted);"><i data-feather="x"></i></button>
        </div>
        <div style="padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <input type="text" id="mediaPickerSearch" class="form-control" placeholder="Search by filename..." style="width: 250px; font-size: 13px;">
            
            <div id="mediaPickerAltContainer" style="display: none; align-items: center; gap: 10px;">
                <input type="text" id="mediaPickerAlt" class="form-control" placeholder="Alt tag (optional)" style="width: 200px; font-size: 13px;" title="Alt text for the image when added in post">
            </div>

            <button onclick="document.getElementById('mediaPickerUpload').click()" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 5px;">
                <i data-feather="upload-cloud" style="width: 14px;"></i> Upload
            </button>
            <input type="file" id="mediaPickerUpload" accept="image/*" style="display: none;">
        </div>
        <div id="mediaPickerGrid" style="flex: 1; overflow-y: auto; padding: 20px; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; align-content: start;">
            <!-- Rendered via JS -->
        </div>
        <div id="mediaPickerPagination" style="padding: 15px; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 5px;">
            <!-- Rendered via JS -->
        </div>
    </div>
</div>

<style>
.picker-item {
    border: 2px solid transparent;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    background: #f1f5f9;
}
.picker-item:hover {
    border-color: #cbd5e1;
    transform: translateY(-2px);
}
.picker-item.selected {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
}
</style>

<script>
const MEDIA_BASE_URL = '<?php echo BASE_URL; ?>';
let currentPickerTarget = null;
let pickerPage = 1;
let pickerSearch = '';

window.openMediaPicker = function(target) {
    currentPickerTarget = target;
    
    // Show alt tag input only when targeting quill
    if (target === 'quill') {
        document.getElementById('mediaPickerAltContainer').style.display = 'flex';
        document.getElementById('mediaPickerAlt').value = ''; // Reset
    } else {
        document.getElementById('mediaPickerAltContainer').style.display = 'none';
    }

    document.getElementById('mediaPickerModal').style.display = 'flex';
    loadMediaPicker();
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
};

window.closeMediaPicker = function() {
    document.getElementById('mediaPickerModal').style.display = 'none';
};

async function loadMediaPicker(page = 1, search = '') {
    const grid = document.getElementById('mediaPickerGrid');
    grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 40px;"><i data-feather="loader" style="animation: spin 1s linear infinite;"></i></div>';
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    try {
        const response = await fetch(`ajax_media_list.php?page=${page}&search=${encodeURIComponent(search)}`);
        const data = await response.json();
        
        if (data.success) {
            renderMediaPickerGrid(data.data);
            renderMediaPickerPagination(data.pagination);
        } else {
            grid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">${data.message}</p>`;
        }
    } catch (e) {
        grid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">Error loading media.</p>`;
    }
}

function renderMediaPickerGrid(items) {
    const grid = document.getElementById('mediaPickerGrid');
    if (items.length === 0) {
        grid.innerHTML = `<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--muted);">No media found.</div>`;
        return;
    }

    grid.innerHTML = items.map(item => {
        const url = `../assets/images/media/${item.filename}`;
        return `
            <div class="picker-item" onclick="selectMedia('${url}', '${item.filename}')">
                <div style="position: relative; width: 100%; padding-top: 100%; background: #f1f5f9; overflow: hidden;">
                    <img src="${url}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                </div>
                <div style="padding: 8px; font-size: 10px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: center;" title="${item.original_name}">
                    ${item.original_name}
                </div>
            </div>
        `;
    }).join('');
}

function renderMediaPickerPagination(pg) {
    const pag = document.getElementById('mediaPickerPagination');
    if (pg.total_pages <= 1) { pag.innerHTML = ''; return; }
    
    let html = '';
    for(let i=1; i<=pg.total_pages; i++) {
        const style = i === pg.current_page ? 'btn-primary' : '';
        html += `<button class="btn btn-sm ${style}" style="${!style?'background:#fff;border:1px solid var(--border);':''}" onclick="changePickerPage(${i})">${i}</button>`;
    }
    pag.innerHTML = html;
}

window.changePickerPage = function(page) {
    pickerPage = page;
    loadMediaPicker(pickerPage, pickerSearch);
}

document.getElementById('mediaPickerSearch').addEventListener('input', function(e) {
    setTimeout(() => {
        pickerSearch = e.target.value;
        pickerPage = 1;
        loadMediaPicker(1, pickerSearch);
    }, 500);
});

// Upload from within picker
document.getElementById('mediaPickerUpload').addEventListener('change', async function() {
    if (this.files.length === 0) return;
    
    const file = this.files[0];
    const formData = new FormData();
    formData.append('media_file', file);
    
    try {
        const response = await fetch('ajax_media_upload.php', { method: 'POST', body: formData });
        const data = await response.json();
        if(data.success) {
            if (currentPickerTarget === 'og_image') {
                const fullUrl = (data.data && data.data.url) ? data.data.url : (MEDIA_BASE_URL + 'assets/images/media/' + data.data.filename);
                selectMedia(fullUrl, data.data.filename);
            } else {
                loadMediaPicker(1, pickerSearch);
            }
        } else {
            alert('Upload failed: ' + data.message);
        }
    } catch(e) {
        alert('Network error during upload.');
    }
    this.value = '';
});

window.selectMedia = function(url, filename) {
    if (currentPickerTarget === 'quill') {
        const range = window.quill ? window.quill.getSelection() : null;
        const index = range ? range.index : (window.quill ? window.quill.getLength() : 0);
        
        if (window.quill) {
            window.quill.insertEmbed(index, 'image', url);
            
            const altText = document.getElementById('mediaPickerAlt').value.trim();
            if (altText) {
                // Quill image blot may not support native formatting by default, so we format text or append alt text manually using DOM
                setTimeout(() => {
                    const editorContainer = document.querySelector('#editor');
                    if (editorContainer) {
                        const imgs = editorContainer.querySelectorAll(`img[src="${url}"]`);
                        if (imgs.length > 0) {
                            imgs[imgs.length - 1].setAttribute('alt', altText);
                            // Also update Quill content directly to persist
                            window.quill.root.innerHTML = window.quill.root.innerHTML; 
                        }
                    }
                }, 50);
            }
            window.quill.setSelection(index + 1);
        }
    } else if (currentPickerTarget === 'featured') {
        document.getElementById('imgPreview').src = url;
        document.getElementById('imgPreview').style.display = 'block';
        const placeholder = document.getElementById('imgPlaceholder');
        if (placeholder) placeholder.style.display = 'none';
        document.getElementById('previewBox').style.borderStyle = 'solid';
        
        // Save the library filename
        document.getElementById('library_image_filename').value = filename;
        // Clear normal file input
        document.getElementById('imgInput').value = '';
    } else if (currentPickerTarget === 'og_image') {
        const fullUrl = (url.startsWith('http://') || url.startsWith('https://')) 
            ? url 
            : (MEDIA_BASE_URL + 'assets/images/media/' + filename);
        const ogInput = document.getElementById('og_image_url');
        if (ogInput) {
            ogInput.value = fullUrl;
            if (typeof updateOgPreview === 'function') {
                updateOgPreview(fullUrl);
            }
        }
    } else if (typeof window.onMediaSelected === 'function') {
        window.onMediaSelected(url, filename);
    }
    closeMediaPicker();
};
</script>
