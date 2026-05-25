<?php
$page_title = "WP Sources Manager";
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
    
    // Auto-append wp-json/wp/v2/posts if they just enter the domain
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

// Fetch categories for the dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch sources
$sources = $pdo->query("SELECT w.*, c.name as category_name FROM wp_sources w LEFT JOIN categories c ON w.category_id = c.id ORDER BY w.id DESC")->fetchAll();

include 'includes/header.php';
?>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">Automated WP Sources</h2>
            <p style="color: #64748b; font-size: 14px; margin: 5px 0 0;">Manage external WordPress sites. Active sources are auto-synced every 30 minutes in the background.</p>
        </div>
        <div style="background: rgba(16,185,129,0.1); color: #10b981; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i data-feather="refresh-cw"></i>
        </div>
    </div>

    <!-- Add Source Form -->
    <div class="settings-card" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
        <h3 style="font-size: 16px; font-weight: 700; margin-top: 0; margin-bottom: 15px;">Add New Source</h3>
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

    <!-- Sources List -->
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
                            <a href="?toggle=<?php echo $source['id']; ?>" style="width: 36px; height: 36px; border-radius: 8px; background: <?php echo $source['status'] == 'active' ? '#fffbeb' : '#ecfdf5'; ?>; color: <?php echo $source['status'] == 'active' ? '#d97706' : '#10b981'; ?>; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: 1px solid <?php echo $source['status'] == 'active' ? '#fde68a' : '#a7f3d0'; ?>;" title="<?php echo $source['status'] == 'active' ? 'Pause Sync' : 'Start Sync'; ?>" onmouseover="this.style.background='<?php echo $source['status'] == 'active' ? '#fef3c7' : '#d1fae5'; ?>'" onmouseout="this.style.background='<?php echo $source['status'] == 'active' ? '#fffbeb' : '#ecfdf5'; ?>'">
                                <i data-feather="<?php echo $source['status'] == 'active' ? 'pause' : 'play'; ?>" style="width: 16px;"></i>
                            </a>
                            <a href="?delete=<?php echo $source['id']; ?>" onclick="return confirm('Delete this source?');" style="width: 36px; height: 36px; border-radius: 8px; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; border: 1px solid #fecaca;" title="Delete Source" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
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

<script>
    window.addEventListener('load', () => { feather.replace(); });
</script>

<?php include 'includes/footer.php'; ?>
