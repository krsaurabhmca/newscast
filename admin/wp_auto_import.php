<?php
$page_title = "WP Auto Import";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Fetch categories for the dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

include 'includes/header.php';
?>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">WordPress Auto Importer</h2>
            <p style="color: #64748b; font-size: 14px; margin: 5px 0 0;">Fetch, Rewrite via AI, and Import posts from any WordPress site</p>
        </div>
        <div style="background: rgba(99,102,241,0.1); color: var(--primary); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i data-feather="download-cloud"></i>
        </div>
    </div>

    <!-- Step 1: Configuration -->
    <div class="settings-card" id="step-config" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div>
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">WordPress API Endpoint</label>
                <input type="text" id="wp_api_url" class="form-control" value="https://chhapratoday.com/wp-json/wp/v2/posts?per_page=10&_embed=1" placeholder="e.g. https://site.com/wp-json/wp/v2/posts?per_page=10&_embed=1">
                <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Must include `_embed=1` to fetch featured images.</span>
            </div>
            <div>
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">Target Category</label>
                <select id="target_category" class="form-control">
                    <option value="">-- Select Category --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button onclick="fetchPosts()" id="btn-fetch" class="btn btn-primary" style="font-weight: 700; display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px;">
                <i data-feather="search" style="width: 18px;"></i> Fetch Posts Now
            </button>
        </div>
    </div>

    <!-- Step 2: Results & Processing -->
    <div id="step-results" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Found Posts (<span id="post-count">0</span>)</h3>
            <button onclick="startImport()" id="btn-import" class="btn btn-success" style="font-weight: 700; display: inline-flex; align-items: center; gap: 8px; background: #10b981; border: none;">
                <i data-feather="play" style="width: 18px;"></i> Start Import & Rewrite
            </button>
        </div>

        <!-- Progress Bar -->
        <div id="progress-container" style="display: none; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 8px;">
                <span>Import Progress</span>
                <span id="progress-text">0 / 0</span>
            </div>
            <div style="width: 100%; height: 10px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                <div id="progress-bar" style="height: 100%; width: 0%; background: #10b981; transition: width 0.3s;"></div>
            </div>
        </div>

        <div id="posts-list" style="display: flex; flex-direction: column; gap: 15px;">
            <!-- Post items will be injected here via JS -->
        </div>
    </div>
</div>

<script>
    let fetchedPosts = [];
    let isImporting = false;

    async function fetchPosts() {
        const url = document.getElementById('wp_api_url').value;
        const btn = document.getElementById('btn-fetch');
        const resultsContainer = document.getElementById('step-results');
        const list = document.getElementById('posts-list');
        const countSpan = document.getElementById('post-count');
        
        if (!url) {
            alert("Please enter a valid WordPress API URL");
            return;
        }

        btn.innerHTML = '<i data-feather="loader" class="spin" style="width: 18px;"></i> Fetching...';
        btn.disabled = true;
        list.innerHTML = '';
        fetchedPosts = [];

        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error("Failed to fetch data from the provided URL.");
            
            const data = await response.json();
            
            if (data.length === 0) {
                list.innerHTML = '<div style="padding: 20px; text-align: center; color: #64748b;">No posts found.</div>';
            } else {
                fetchedPosts = data;
                countSpan.innerText = data.length;
                
                data.forEach((post, index) => {
                    // Try to extract featured image from _embedded
                    let imageUrl = '';
                    if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
                        imageUrl = post._embedded['wp:featuredmedia'][0].source_url;
                    }

                    const html = `
                        <div id="post-item-${index}" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff;">
                            <input type="checkbox" id="check-${index}" checked style="margin-top: 5px; cursor: pointer; width: 18px; height: 18px;">
                            ${imageUrl ? `<img src="${imageUrl}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">` : `<div style="width: 80px; height: 60px; background: #f1f5f9; border-radius: 6px; display: flex; align-items:center; justify-content:center; color:#94a3b8; font-size:11px;">No Image</div>`}
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px 0; font-size: 15px; color: #1e293b; line-height: 1.4;">${post.title.rendered}</h4>
                                <div style="font-size: 12px; color: #64748b;">Date: ${new Date(post.date).toLocaleString()}</div>
                                <div id="status-${index}" style="font-size: 12px; font-weight: 700; margin-top: 8px; color: #f59e0b;">Waiting...</div>
                            </div>
                        </div>
                    `;
                    list.insertAdjacentHTML('beforeend', html);
                });
            }
            resultsContainer.style.display = 'block';
        } catch (error) {
            alert(error.message);
        } finally {
            btn.innerHTML = '<i data-feather="search" style="width: 18px;"></i> Fetch Posts Now';
            btn.disabled = false;
            feather.replace();
        }
    }

    async function startImport() {
        const categoryId = document.getElementById('target_category').value;
        if (!categoryId) {
            alert("Please select a Target Category first.");
            return;
        }

        const selectedIndexes = [];
        fetchedPosts.forEach((post, index) => {
            const checkbox = document.getElementById(`check-${index}`);
            if (checkbox && checkbox.checked) {
                selectedIndexes.push(index);
            }
        });

        if (selectedIndexes.length === 0) {
            alert("Please select at least one post to import.");
            return;
        }

        isImporting = true;
        document.getElementById('btn-import').disabled = true;
        document.getElementById('progress-container').style.display = 'block';
        
        let completed = 0;
        
        for (const index of selectedIndexes) {
            const post = fetchedPosts[index];
            const statusDiv = document.getElementById(`status-${index}`);
            const itemDiv = document.getElementById(`post-item-${index}`);
            
            statusDiv.innerHTML = '<span style="color: #3b82f6;">Processing (AI Rewriting & Image Fetching)...</span>';
            itemDiv.style.borderColor = '#3b82f6';
            
            // Extract raw HTML content
            const content = post.content.rendered;
            const title = post.title.rendered;
            
            // Extract image
            let imageUrl = '';
            if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
                imageUrl = post._embedded['wp:featuredmedia'][0].source_url;
            }

            try {
                const response = await fetch('ajax_wp_import_single.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        title: title,
                        content: content,
                        image_url: imageUrl,
                        category_id: categoryId,
                        published_at: post.date
                    })
                });

                const result = await response.json();

                if (result.success) {
                    statusDiv.innerHTML = '<span style="color: #10b981;"><i data-feather="check-circle" style="width: 14px;"></i> Imported Successfully!</span>';
                    itemDiv.style.borderColor = '#10b981';
                    itemDiv.style.backgroundColor = '#ecfdf5';
                } else {
                    statusDiv.innerHTML = `<span style="color: #ef4444;"><i data-feather="x-circle" style="width: 14px;"></i> Error: ${result.error}</span>`;
                    itemDiv.style.borderColor = '#ef4444';
                    itemDiv.style.backgroundColor = '#fef2f2';
                }
            } catch (err) {
                statusDiv.innerHTML = `<span style="color: #ef4444;"><i data-feather="x-circle" style="width: 14px;"></i> Network Error</span>`;
                itemDiv.style.borderColor = '#ef4444';
            }
            
            feather.replace();
            completed++;
            const pct = Math.round((completed / selectedIndexes.length) * 100);
            document.getElementById('progress-bar').style.width = pct + '%';
            document.getElementById('progress-text').innerText = `${completed} / ${selectedIndexes.length}`;
        }
        
        isImporting = false;
        document.getElementById('btn-import').innerText = 'Import Complete';
    }
</script>

<style>
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<?php include 'includes/footer.php'; ?>
