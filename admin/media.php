<?php
$page_title = "Media Library";
include 'includes/header.php';
?>

<div class="admin-main-col" style="max-width: 1200px; margin: 0 auto; width: 100%;">
    <div class="stat-card" style="margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 10px;">
                <i data-feather="image" style="color: var(--primary);"></i>
                Media Library
            </h2>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="mediaSearchInput" class="form-control" placeholder="Search by filename..." style="width: 250px; padding: 8px 12px; font-size: 13px;">
                <button id="btnUploadMedia" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
                    <i data-feather="upload-cloud" style="width: 16px;"></i> Upload New
                </button>
            </div>
        </div>

        <!-- Upload Dropzone (Hidden by default) -->
        <div id="mediaUploadZone" style="display: none; border: 2px dashed var(--border); border-radius: 12px; padding: 40px 20px; text-align: center; margin-bottom: 20px; background: #f8fafc; transition: all 0.3s;">
            <i data-feather="upload-cloud" style="width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 15px;"></i>
            <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin: 0 0 5px;">Drag & Drop Images Here</h3>
            <p style="font-size: 13px; color: var(--muted); margin: 0 0 15px;">or click to browse your computer</p>
            <input type="file" id="mediaFileInput" multiple accept="image/*" style="display: none;">
            <button class="btn btn-primary" onclick="document.getElementById('mediaFileInput').click()" style="background: white; color: var(--primary); border: 1px solid var(--primary);">
                Browse Files
            </button>
            <div id="uploadProgressContainer" style="margin-top: 15px; display: none;">
                <div style="width: 100%; max-width: 400px; height: 6px; background: #e2e8f0; border-radius: 3px; margin: 0 auto; overflow: hidden;">
                    <div id="uploadProgressBar" style="width: 0%; height: 100%; background: var(--primary); transition: width 0.3s;"></div>
                </div>
                <p id="uploadProgressText" style="font-size: 12px; color: var(--primary); margin-top: 5px; font-weight: 600;">Uploading 0/0...</p>
            </div>
        </div>

        <!-- Media Grid -->
        <div id="mediaGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px;">
            <!-- Rendered via JS -->
            <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--muted);">
                <i data-feather="loader" style="animation: spin 1s linear infinite; width: 30px; height: 30px; margin-bottom: 10px;"></i>
                <p>Loading media...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div id="mediaPagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 25px;">
            <!-- Rendered via JS -->
        </div>
    </div>
</div>

<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
.media-item {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    background: white;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}
.media-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08);
}
.media-thumb-container {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.media-thumb-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-info {
    padding: 10px;
    font-size: 11px;
}
.media-filename {
    font-weight: 700;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 3px;
}
.media-meta {
    color: var(--muted);
    display: flex;
    justify-content: space-between;
}
.media-actions {
    position: absolute;
    top: 5px;
    right: 5px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s;
}
.media-item:hover .media-actions {
    opacity: 1;
}
.media-btn {
    background: rgba(255,255,255,0.9);
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-main);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.2s;
}
.media-btn:hover {
    background: white;
    color: var(--primary);
}
.media-btn.delete-btn:hover {
    color: #ef4444;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();

    const mediaGrid = document.getElementById('mediaGrid');
    const mediaPagination = document.getElementById('mediaPagination');
    const searchInput = document.getElementById('mediaSearchInput');
    const btnUpload = document.getElementById('btnUploadMedia');
    const uploadZone = document.getElementById('mediaUploadZone');
    const fileInput = document.getElementById('mediaFileInput');
    
    const isAdmin = <?php echo is_admin() ? 'true' : 'false'; ?>;
    let currentPage = 1;
    let currentSearch = '';

    // Load Media
    async function loadMedia(page = 1, search = '') {
        mediaGrid.innerHTML = `<div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--muted);">
                <i data-feather="loader" style="animation: spin 1s linear infinite; width: 30px; height: 30px; margin-bottom: 10px;"></i>
                <p>Loading media...</p>
            </div>`;
        feather.replace();

        try {
            const response = await fetch(`ajax_media_list.php?page=${page}&search=${encodeURIComponent(search)}`);
            const data = await response.json();
            
            if (data.success) {
                renderMediaGrid(data.data);
                renderPagination(data.pagination);
            } else {
                mediaGrid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error loading media:', error);
            mediaGrid.innerHTML = `<p style="grid-column:1/-1; text-align:center; color:red;">Failed to load media.</p>`;
        }
    }

    function formatBytes(bytes, decimals = 1) {
        if (!+bytes) return '0 Bytes';
        const k = 1024, dm = decimals < 0 ? 0 : decimals, sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    }

    function renderMediaGrid(items) {
        if (items.length === 0) {
            mediaGrid.innerHTML = `<div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--muted);">
                <i data-feather="image" style="width: 40px; height: 40px; margin-bottom: 10px; opacity:0.5;"></i>
                <p>No media found.</p>
            </div>`;
            feather.replace();
            return;
        }

        mediaGrid.innerHTML = items.map(item => {
            const url = `../assets/images/media/${item.filename}`;
            return `
                <div class="media-item">
                    <div class="media-thumb-container">
                        <img src="${url}" alt="${item.original_name}" loading="lazy">
                    </div>
                    <div class="media-info">
                        <div class="media-filename" title="${item.original_name}">${item.original_name}</div>
                        <div class="media-meta">
                            <span>${formatBytes(item.file_size)}</span>
                            <span>${item.width}x${item.height}</span>
                        </div>
                    </div>
                    <div class="media-actions">
                        <button class="media-btn" title="Copy URL" onclick="copyToClipboard('${url}')">
                            <i data-feather="copy" style="width: 14px;"></i>
                        </button>
                        ${isAdmin ? `<button class="media-btn delete-btn" title="Delete" onclick="deleteMedia(${item.id})">
                            <i data-feather="trash-2" style="width: 14px;"></i>
                        </button>` : ''}
                    </div>
                </div>
            `;
        }).join('');
        feather.replace();
    }

    function renderPagination(pg) {
        if (pg.total_pages <= 1) {
            mediaPagination.innerHTML = '';
            return;
        }

        let html = '';
        if (pg.current_page > 1) {
            html += `<button class="btn btn-sm" style="background:#fff;border:1px solid var(--border);" onclick="changePage(${pg.current_page - 1})">Prev</button>`;
        }
        
        for (let i = 1; i <= pg.total_pages; i++) {
            if (i === 1 || i === pg.total_pages || (i >= pg.current_page - 2 && i <= pg.current_page + 2)) {
                if (i === pg.current_page) {
                    html += `<button class="btn btn-sm btn-primary">${i}</button>`;
                } else {
                    html += `<button class="btn btn-sm" style="background:#fff;border:1px solid var(--border);" onclick="changePage(${i})">${i}</button>`;
                }
            } else if (i === pg.current_page - 3 || i === pg.current_page + 3) {
                html += `<span style="padding: 5px 10px; color: var(--muted);">...</span>`;
            }
        }

        if (pg.current_page < pg.total_pages) {
            html += `<button class="btn btn-sm" style="background:#fff;border:1px solid var(--border);" onclick="changePage(${pg.current_page + 1})">Next</button>`;
        }

        mediaPagination.innerHTML = html;
    }

    window.changePage = function(page) {
        currentPage = page;
        loadMedia(currentPage, currentSearch);
    };

    window.copyToClipboard = function(text) {
        // Build an absolute URL assuming this is in /admin/
        const baseUrl = window.location.href.split('/admin/')[0];
        const fullUrl = baseUrl + '/assets/images/media/' + text.split('/').pop();
        
        navigator.clipboard.writeText(fullUrl).then(() => {
            alert('URL copied to clipboard!');
        });
    };

    window.deleteMedia = async function(id) {
        if (!confirm('Are you sure you want to delete this image? If it is used in any articles, it will appear broken.')) return;

        try {
            const formData = new FormData();
            formData.append('id', id);
            
            const response = await fetch('ajax_media_delete.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                loadMedia(currentPage, currentSearch);
            } else {
                alert(data.message || 'Failed to delete media.');
            }
        } catch (error) {
            console.error(error);
            alert('An error occurred.');
        }
    };

    // Search
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value;
            currentPage = 1;
            loadMedia(currentPage, currentSearch);
        }, 500);
    });

    // Upload Toggle
    btnUpload.addEventListener('click', () => {
        uploadZone.style.display = uploadZone.style.display === 'none' ? 'block' : 'none';
    });

    // Drag and Drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--primary)';
        uploadZone.style.background = '#eff6ff';
    });
    uploadZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--border)';
        uploadZone.style.background = '#f8fafc';
    });
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--border)';
        uploadZone.style.background = '#f8fafc';
        
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFiles(this.files);
        }
    });

    async function handleFiles(files) {
        const progressContainer = document.getElementById('uploadProgressContainer');
        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        
        progressContainer.style.display = 'block';
        
        let successCount = 0;
        let failCount = 0;
        const total = files.length;

        for (let i = 0; i < total; i++) {
            const file = files[i];
            // Only process images
            if (!file.type.startsWith('image/')) {
                failCount++;
                continue;
            }

            progressText.innerText = `Uploading ${i+1}/${total}...`;
            
            const formData = new FormData();
            formData.append('media_file', file);
            
            try {
                const response = await fetch('ajax_media_upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    successCount++;
                } else {
                    failCount++;
                    console.error("Upload failed for", file.name, data.message);
                }
            } catch (err) {
                failCount++;
                console.error("Network error for", file.name, err);
            }
            
            progressBar.style.width = `${((i+1)/total)*100}%`;
        }

        progressText.innerText = `Complete! ${successCount} successful, ${failCount} failed.`;
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressBar.style.width = '0%';
            fileInput.value = ''; // reset
            loadMedia(1, currentSearch); // reload grid
        }, 2000);
    }

    // Initial Load
    loadMedia();
});
</script>

<?php include 'includes/footer.php'; ?>
