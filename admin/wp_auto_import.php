<?php
$page_title = "WP Auto Importer";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Handle Add Source
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_source'])) {
    $site_name = clean($_POST['site_name']);
    $feed_url = clean($_POST['feed_url']);
    $category_id = (int) $_POST['category_id'];
    
    if (strpos($feed_url, 'wp-json') === false) {
        $feed_url = rtrim($feed_url, '/') . '/wp-json/wp/v2/posts?_embed=1';
    } else {
        if (strpos($feed_url, '_embed=1') === false) {
            $feed_url .= (strpos($feed_url, '?') !== false ? '&' : '?') . '_embed=1';
        }
    }

    if (!empty($site_name) && !empty($feed_url) && $category_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO wp_sources (site_name, feed_url, category_id, status) VALUES (?, ?, ?, 'active')");
        if ($stmt->execute([$site_name, $feed_url, $category_id])) {
            $_SESSION['flash_msg'] = "Source added successfully!";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_msg'] = "Failed to add source.";
            $_SESSION['flash_type'] = "danger";
        }
    } else {
        $_SESSION['flash_msg'] = "Please fill all required fields.";
        $_SESSION['flash_type'] = "danger";
    }
    redirect('admin/wp_auto_import.php');
}

// Handle Delete Source
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM wp_sources WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_msg'] = "Source deleted.";
    $_SESSION['flash_type'] = "success";
    redirect('admin/wp_auto_import.php');
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE wp_sources SET status = IF(status='active', 'inactive', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/wp_auto_import.php');
}

$stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

$sources = $pdo->query("SELECT w.*, c.name as category_name FROM wp_sources w LEFT JOIN categories c ON w.category_id = c.id ORDER BY w.id DESC")->fetchAll();

include 'includes/header.php';
?>

<style>
    .nav-tabs-custom {
        display: flex; gap: 10px; border-bottom: 2px solid #e2e8f0; margin-bottom: 25px;
    }
    .nav-tabs-custom .nav-link {
        border: none; background: transparent; padding: 12px 20px; font-weight: 700; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: 0.3s;
    }
    .nav-tabs-custom .nav-link.active {
        color: #0f172a; border-bottom-color: var(--primary);
    }
    .nav-tabs-custom .nav-link:hover:not(.active) {
        color: #334155;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">WordPress Importer</h2>
            <p style="color: #64748b; font-size: 14px; margin: 5px 0 0;">Manage background syncs or manually import posts with live progress.</p>
        </div>
        <div style="background: rgba(16,185,129,0.1); color: #10b981; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i data-feather="download-cloud"></i>
        </div>
    </div>

    <div class="nav-tabs-custom">
        <button class="nav-link active" onclick="switchTab('tab-sync', this)">Auto-Sync Sources (Background)</button>
        <button class="nav-link" onclick="switchTab('tab-manual', this)">Manual Import (Live Progress)</button>
    </div>

    <!-- TAB 1: AUTO SYNC SOURCES -->
    <div id="tab-sync" class="tab-content active">
        <!-- Add Source Form -->
        <div class="settings-card" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <h3 style="font-size: 16px; font-weight: 700; margin-top: 0; margin-bottom: 15px;">Add New Auto-Sync Source</h3>
            <form action="" method="POST">
                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 20px; align-items: end;">
                    <div>
                        <label style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">Site Name</label>
                        <input type="text" name="site_name" class="form-control" placeholder="e.g. Chhapra Today" required>
                    </div>
                    <div>
                        <label style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">WordPress API URL or Domain</label>
                        <input type="url" name="feed_url" class="form-control" placeholder="https://domain.com/wp-json/wp/v2/posts" required>
                    </div>
                    <div>
                        <label style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">Target Category</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" name="add_source" class="btn btn-primary" style="font-weight: 700;">Add Source</button>
                </div>
            </form>
        </div>

        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 15px;">Active Auto-Sync Sources</h3>
        
        <?php if (empty($sources)): ?>
            <div style="text-align: center; padding: 40px; border: 2px dashed #e2e8f0; border-radius: 12px; color: #94a3b8;">
                <i data-feather="cloud-off" style="width: 40px; height: 40px; margin-bottom: 10px;"></i>
                <p style="margin: 0;">No WordPress sources added yet. Auto-sync is currently inactive.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($sources as $source): ?>
                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);" onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.08)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.02)'; this.style.transform='translateY(0)';">
                        
                        <div style="display: flex; align-items: center; gap: 20px; flex: 1;">
                            <div style="width: 50px; height: 50px; background: <?php echo $source['status'] == 'active' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #94a3b8, #64748b)'; ?>; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                <i data-feather="<?php echo $source['status'] == 'active' ? 'activity' : 'power'; ?>" style="width: 24px; height: 24px;"></i>
                            </div>
                            
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                                    <?php echo htmlspecialchars($source['site_name']); ?>
                                    <?php if ($source['status'] == 'active'): ?>
                                        <span style="background: #dcfce7; color: #166534; font-size: 10px; padding: 3px 8px; border-radius: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Active</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #64748b; font-size: 10px; padding: 3px 8px; border-radius: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Paused</span>
                                    <?php endif; ?>
                                </h4>
                                <div style="font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 15px;">
                                    <span style="display: flex; align-items: center; gap: 5px;"><i data-feather="folder" style="width: 12px;"></i> <?php echo htmlspecialchars($source['category_name']); ?></span>
                                    <span style="display: flex; align-items: center; gap: 5px;" title="<?php echo htmlspecialchars($source['feed_url']); ?>">
                                        <i data-feather="link" style="width: 12px;"></i> <?php echo strlen($source['feed_url']) > 40 ? substr(htmlspecialchars($source['feed_url']), 0, 40) . '...' : htmlspecialchars($source['feed_url']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 30px;">
                            <div style="text-align: right;">
                                <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px;">Last Checked</div>
                                <div style="font-size: 13px; font-weight: 600; color: #334155;">
                                    <?php echo $source['last_checked'] ? date('M d, g:i A', strtotime($source['last_checked'])) : '<span style="color:#f59e0b;">Waiting...</span>'; ?>
                                </div>
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <a href="?toggle=<?php echo $source['id']; ?>" style="width: 36px; height: 36px; border-radius: 8px; background: <?php echo $source['status'] == 'active' ? '#fffbeb' : '#ecfdf5'; ?>; color: <?php echo $source['status'] == 'active' ? '#d97706' : '#10b981'; ?>; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: 1px solid <?php echo $source['status'] == 'active' ? '#fde68a' : '#a7f3d0'; ?>;" title="<?php echo $source['status'] == 'active' ? 'Pause Sync' : 'Start Sync'; ?>">
                                    <i data-feather="<?php echo $source['status'] == 'active' ? 'pause' : 'play'; ?>" style="width: 16px;"></i>
                                </a>
                                <a href="?delete=<?php echo $source['id']; ?>" onclick="return confirm('Delete this source?');" style="width: 36px; height: 36px; border-radius: 8px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: 1px solid #fecaca;" title="Delete Source">
                                    <i data-feather="trash-2" style="width: 16px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 25px; padding: 20px; background: linear-gradient(to right, #f0fdf4, #ffffff); border: 1px solid #bbf7d0; border-left: 4px solid #10b981; border-radius: 12px; color: #166534; font-size: 13px; font-weight: 600; display: flex; gap: 15px; align-items: center; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.05);">
                <div style="background: #dcfce7; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-feather="info" style="width: 20px; color: #15803d;"></i> 
                </div>
                <div>The background auto-sync engine runs silently. It checks active sources roughly every 30 minutes to fetch and rewrite up to 5 new articles per source. Duplicates are automatically blocked.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB 2: MANUAL IMPORT (LIVE PROGRESS) -->
    <div id="tab-manual" class="tab-content">
        <div class="settings-card" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">Quick Select Saved Source (Optional)</label>
                <select class="form-control" onchange="if(this.value) { document.getElementById('wp_api_url').value = this.options[this.selectedIndex].dataset.url; document.getElementById('target_category').value = this.options[this.selectedIndex].dataset.cat; }">
                    <option value="">-- Choose from saved Auto-Sync Sources --</option>
                    <?php foreach ($sources as $s): ?>
                        <option value="<?php echo $s['id']; ?>" data-url="<?php echo htmlspecialchars($s['feed_url']); ?>" data-cat="<?php echo $s['category_id']; ?>">
                            <?php echo htmlspecialchars($s['site_name']); ?> (<?php echo htmlspecialchars($s['category_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div>
                    <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">WordPress API Endpoint</label>
                    <input type="text" id="wp_api_url" class="form-control" value="https://chhapratoday.com/wp-json/wp/v2/posts?per_page=5&_embed=1" placeholder="e.g. https://site.com/wp-json/wp/v2/posts?per_page=10&_embed=1">
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

        <div id="step-results" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0;">Found Posts (<span id="post-count">0</span>)</h3>
                <button onclick="startImport()" id="btn-import" class="btn btn-success" style="font-weight: 700; display: inline-flex; align-items: center; gap: 8px; background: #10b981; border: none;">
                    <i data-feather="play" style="width: 18px;"></i> Start Import & Rewrite
                </button>
            </div>

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
</div>

<script>
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

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
                    let imageUrl = '';
                    if (post._embedded && post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
                        imageUrl = post._embedded['wp:featuredmedia'][0].source_url;
                    }

                    const html = `
                        <div id="post-item-${index}" style="display: flex; align-items: flex-start; gap: 15px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; transition: all 0.3s ease;">
                            <input type="checkbox" id="check-${index}" checked style="margin-top: 5px; cursor: pointer; width: 18px; height: 18px;">
                            ${imageUrl ? `<img src="${imageUrl}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">` : `<div style="width: 80px; height: 60px; background: #f1f5f9; border-radius: 6px; display: flex; align-items:center; justify-content:center; color:#94a3b8; font-size:11px;">No Image</div>`}
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 5px 0; font-size: 15px; color: #1e293b; line-height: 1.4;">${post.title.rendered}</h4>
                                <div style="font-size: 12px; color: #64748b;">Date: ${new Date(post.date).toLocaleString()}</div>
                                <div id="status-${index}" style="font-size: 13px; font-weight: 700; margin-top: 8px; color: #f59e0b; display: flex; align-items: center; gap: 5px;">
                                    <i data-feather="clock" style="width: 14px;"></i> Waiting to import...
                                </div>
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
            
            statusDiv.innerHTML = '<span style="color: #3b82f6; display: flex; align-items: center; gap: 5px;"><i data-feather="loader" class="spin" style="width: 14px;"></i> AI is Rewriting...</span>';
            itemDiv.style.borderColor = '#3b82f6';
            feather.replace();
            
            const content = post.content.rendered;
            const title = post.title.rendered;
            
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
                    statusDiv.innerHTML = '<span style="color: #10b981; display: flex; align-items: center; gap: 5px;"><i data-feather="check-circle" style="width: 14px;"></i> Imported & Published!</span>';
                    itemDiv.style.borderColor = '#10b981';
                    itemDiv.style.backgroundColor = '#ecfdf5';
                } else {
                    statusDiv.innerHTML = `<span style="color: #ef4444; display: flex; align-items: center; gap: 5px;"><i data-feather="alert-circle" style="width: 14px;"></i> Failed: ${result.error}</span>`;
                    itemDiv.style.borderColor = '#ef4444';
                    itemDiv.style.backgroundColor = '#fef2f2';
                }
            } catch (err) {
                statusDiv.innerHTML = `<span style="color: #ef4444; display: flex; align-items: center; gap: 5px;"><i data-feather="alert-circle" style="width: 14px;"></i> Network Error</span>`;
                itemDiv.style.borderColor = '#ef4444';
            }
            
            feather.replace();
            completed++;
            const pct = Math.round((completed / selectedIndexes.length) * 100);
            document.getElementById('progress-bar').style.width = pct + '%';
            document.getElementById('progress-text').innerText = `${completed} / ${selectedIndexes.length}`;
        }
        
        isImporting = false;
        document.getElementById('btn-import').innerHTML = '<i data-feather="check" style="width: 18px;"></i> Import Complete';
        feather.replace();
    }

    window.addEventListener('load', () => { feather.replace(); });
</script>

<?php include 'includes/footer.php'; ?>
