<?php
$page_title = "Advertisement Management";
include 'includes/header.php';

// Handle Ad Submission (Add or Edit)
if (isset($_POST['save_ad'])) {
    $name = clean($_POST['name']);
    $location = $_POST['location'];
    $type = $_POST['type'];
    $link_url = clean($_POST['link_url']);
    $link_type = $_POST['link_type'];
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $ad_code = $_POST['ad_code'];
    $status = isset($_POST['status']) ? 1 : 0;
    $ad_id = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : null;

    $image_path = isset($_POST['existing_image']) ? $_POST['existing_image'] : '';
    if ($type == 'image' && isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === 0) {
        $img_name = $_FILES['ad_image']['name'];
        $tmp_name = $_FILES['ad_image']['tmp_name'];
        $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
        $new_img_name = uniqid("ad_") . "." . $img_ext;
        $upload_path = "../assets/images/ads/" . $new_img_name;
        
        if (!is_dir("../assets/images/ads/")) {
            mkdir("../assets/images/ads/", 0777, true);
        }
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            // Delete old image if it exists and we're updating
            if ($image_path && file_exists("../assets/images/ads/" . $image_path)) {
                unlink("../assets/images/ads/" . $image_path);
            }
            $image_path = $new_img_name;
        }
    }

    try {
        if ($ad_id) {
            $stmt = $pdo->prepare("UPDATE ads SET name = ?, location = ?, type = ?, image_path = ?, link_url = ?, link_type = ?, ad_code = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $location, $type, $image_path, $link_url, $link_type, $ad_code, $start_date, $end_date, $status, $ad_id]);
            $_SESSION['flash_msg'] = "Advertisement updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO ads (name, location, type, image_path, link_url, link_type, ad_code, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $location, $type, $image_path, $link_url, $link_type, $ad_code, $start_date, $end_date, $status]);
            $_SESSION['flash_msg'] = "Advertisement added successfully!";
        }
        $_SESSION['flash_type'] = "success";
        redirect('admin/ads.php');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $ad_id = $_GET['toggle_status'];
    $current_status = $_GET['current'];
    $new_status = $current_status == 1 ? 0 : 1;
    $pdo->prepare("UPDATE ads SET status = ? WHERE id = ?")->execute([$new_status, $ad_id]);
    redirect('admin/ads.php', 'Ad status updated!');
}

// Handle Delete
if (isset($_GET['delete'])) {
    $ad_id = $_GET['delete'];
    $ad = $pdo->prepare("SELECT image_path FROM ads WHERE id = ?");
    $ad->execute([$ad_id]);
    $img = $ad->fetchColumn();
    if ($img && file_exists("../assets/images/ads/" . $img)) {
        unlink("../assets/images/ads/" . $img);
    }
    $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$ad_id]);
    redirect('admin/ads.php', 'Ad deleted successfully!');
}

// Fetch existing ad for editing
$edit_ad = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_ad = $stmt->fetch();
}

$ads = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC")->fetchAll();
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow); margin-bottom: 30px;">
    <h3 style="margin-bottom: 20px;"><?php echo $edit_ad ? 'Edit Advertisement' : 'New Advertisement'; ?></h3>
    <form action="" method="POST" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <?php if($edit_ad): ?>
            <input type="hidden" name="ad_id" value="<?php echo $edit_ad['id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo $edit_ad['image_path']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label>Ad Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g., Summer Sale Banner" required value="<?php echo $edit_ad ? $edit_ad['name'] : ''; ?>">
        </div>
        <div class="form-group">
            <label>Location Slot</label>
            <select name="location" class="form-control" required>
                <option value="header" <?php echo ($edit_ad && $edit_ad['location'] == 'header') ? 'selected' : ''; ?>>Header (Top)</option>
                <option value="sidebar" <?php echo ($edit_ad && $edit_ad['location'] == 'sidebar') ? 'selected' : ''; ?>>Sidebar</option>
                <option value="content_top" <?php echo ($edit_ad && $edit_ad['location'] == 'content_top') ? 'selected' : ''; ?>>Above Post Content</option>
                <option value="content_bottom" <?php echo ($edit_ad && $edit_ad['location'] == 'content_bottom') ? 'selected' : ''; ?>>Below Post Content</option>
            </select>
        </div>
        <div class="form-group" style="grid-column: span 2;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Start Date (Optional)</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $edit_ad ? $edit_ad['start_date'] : ''; ?>">
                </div>
                <div>
                    <label>End Date (Optional)</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $edit_ad ? $edit_ad['end_date'] : ''; ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Ad Type</label>
            <select name="type" class="form-control" id="adType">
                <option value="image" <?php echo ($edit_ad && $edit_ad['type'] == 'image') ? 'selected' : ''; ?>>Image / Banner</option>
                <option value="code" <?php echo ($edit_ad && $edit_ad['type'] == 'code') ? 'selected' : ''; ?>>HTML / Script (e.g., AdSense)</option>
            </select>
        </div>
        
        <div id="imageFields" style="grid-column: 1 / -1; display: <?php echo ($edit_ad && $edit_ad['type'] == 'code') ? 'none' : 'grid'; ?>; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label>Upload Image <?php echo $edit_ad ? '(Leave blank to keep current)' : ''; ?></label>
                <input type="file" name="ad_image" class="form-control" accept="image/*">
                <?php if($edit_ad && $edit_ad['image_path']): ?>
                    <small>Current: <code><?php echo $edit_ad['image_path']; ?></code></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Destination Type</label>
                <select name="link_type" class="form-control" id="linkType">
                    <option value="url" <?php echo ($edit_ad && $edit_ad['link_type'] == 'url') ? 'selected' : ''; ?>>Website URL</option>
                    <option value="whatsapp" <?php echo ($edit_ad && $edit_ad['link_type'] == 'whatsapp') ? 'selected' : ''; ?>>WhatsApp Message</option>
                    <option value="call" <?php echo ($edit_ad && $edit_ad['link_type'] == 'call') ? 'selected' : ''; ?>>Phone Call</option>
                </select>
            </div>
            <div class="form-group">
                <label id="destLabel">Destination URL</label>
                <input type="text" name="link_url" class="form-control" placeholder="https://..." value="<?php echo $edit_ad ? $edit_ad['link_url'] : ''; ?>">
            </div>
        </div>

        <div class="form-group" id="codeInput" style="display: <?php echo ($edit_ad && $edit_ad['type'] == 'code') ? 'block' : 'none'; ?>; grid-column: 1 / -1;">
            <label>Ad Script / HTML Code</label>
            <textarea name="ad_code" class="form-control" rows="4"><?php echo $edit_ad ? $edit_ad['ad_code'] : ''; ?></textarea>
        </div>
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-bottom: 12px;">
                <input type="checkbox" name="status" <?php echo (!$edit_ad || $edit_ad['status']) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                <span>Active</span>
            </label>
        </div>
        <div class="form-group" style="display: flex; align-items: flex-center; gap: 10px;">
            <button type="submit" name="save_ad" class="btn btn-primary" style="flex: 1; justify-content: center;"><?php echo $edit_ad ? 'Update Advertisement' : 'Create Advertisement'; ?></button>
            <?php if($edit_ad): ?>
                <a href="admin/ads.php" class="btn" style="background: #f1f5f9; color: #444; text-decoration: none; padding: 10px 15px; border-radius: 8px;">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #1e293b;">Ad Performance & Management</h3>
        <div style="font-size: 13px; color: #64748b;">
            <span style="display: inline-block; width: 10px; height: 10px; background: #22c55e; border-radius: 50%; margin-right: 5px;"></span> Active 
            <span style="display: inline-block; width: 10px; height: 10px; background: #ef4444; border-radius: 50%; margin-left: 15px; margin-right: 5px;"></span> Paused
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="padding: 15px; border-radius: 8px 0 0 8px; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Ad Content</th>
                    <th style="padding: 15px; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Location</th>
                    <th style="padding: 15px; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Visibility Period</th>
                    <th style="padding: 15px; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Performance</th>
                    <th style="padding: 15px; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Status</th>
                    <th style="padding: 15px; border-radius: 0 8px 8px 0; text-align: right; font-size: 12px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ads as $ad): ?>
                <tr style="background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s;">
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-left: 1px solid #f1f5f9; border-radius: 8px 0 0 8px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <?php if($ad['type'] == 'image'): ?>
                                <div style="width: 80px; height: 50px; background: #f1f5f9; border-radius: 6px; overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                    <img src="../assets/images/ads/<?php echo $ad['image_path']; ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                            <?php else: ?>
                                <div style="width: 80px; height: 50px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 1px solid #e2e8f0;">
                                    <i data-feather="code" style="width: 20px;"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 15px;"><?php echo $ad['name']; ?></div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    <?php echo strtoupper($ad['type']); ?> • <?php echo strtoupper($ad['link_type']); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                        <span style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                            <?php echo str_replace('_', ' ', $ad['location']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #475569; font-size: 13px;">
                            <i data-feather="calendar" style="width: 14px; color: #94a3b8;"></i>
                            <span>
                                <?php 
                                    echo ($ad['start_date'] ? date('j M', strtotime($ad['start_date'])) : 'Any');
                                    echo ' — ';
                                    echo ($ad['end_date'] ? date('j M', strtotime($ad['end_date'])) : '∞');
                                ?>
                            </span>
                        </div>
                    </td>
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div>
                                <div style="font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1;"><?php echo number_format($ad['impressions']); ?></div>
                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; margin-top: 4px; font-weight: 600;">Views</div>
                            </div>
                            <div style="width: 1px; height: 25px; background: #e2e8f0;"></div>
                            <div>
                                <div style="font-size: 16px; font-weight: 800; color: #f97316; line-height: 1;"><?php echo number_format($ad['clicks']); ?></div>
                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; margin-top: 4px; font-weight: 600;">Clicks</div>
                            </div>
                            <div style="width: 1px; height: 25px; background: #e2e8f0;"></div>
                            <div>
                                <div style="font-size: 16px; font-weight: 800; color: #059669; line-height: 1;">
                                    <?php echo $ad['impressions'] > 0 ? round(($ad['clicks']/$ad['impressions'])*100, 1) : 0; ?>%
                                </div>
                                <div style="font-size: 10px; color: #94a3b8; text-transform: uppercase; margin-top: 4px; font-weight: 600;">CTR</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9;">
                        <a href="?toggle_status=<?php echo $ad['id']; ?>&current=<?php echo $ad['status']; ?>" style="text-decoration: none;">
                            <div style="display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 6px; background: <?php echo $ad['status'] ? '#f0fdf4' : '#fef2f2'; ?>; border: 1px solid <?php echo $ad['status'] ? '#bbf7d0' : '#fecaca'; ?>; color: <?php echo $ad['status'] ? '#15803d' : '#991b1b'; ?>; font-size: 12px; font-weight: 700; width: fit-content;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: currentColor;"></span>
                                <?php echo $ad['status'] ? 'Active' : 'Paused'; ?>
                            </div>
                        </a>
                    </td>
                    <td style="padding: 15px; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-radius: 0 8px 8px 0; text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <a href="?edit=<?php echo $ad['id']; ?>" class="action-btn edit" style="background: #fff; border: 1px solid #e2e8f0; color: #6366f1; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                <i data-feather="edit-2" style="width: 16px;"></i>
                            </a>
                            <a href="?delete=<?php echo $ad['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this advertisement completely?')" style="background: #fff; border: 1px solid #fecaca; color: #ef4444; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                <i data-feather="trash-2" style="width: 16px;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($ads)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 60px;">
                            <i data-feather="image" style="width: 48px; height: 48px; color: #e2e8f0; margin-bottom: 15px;"></i>
                            <div style="font-size: 16px; font-weight: 600;">No advertisements found</div>
                            <p style="font-size: 13px; margin-top: 5px;">Create your first ad campaign using the form above.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('adType').addEventListener('change', function() {
    const isImage = this.value === 'image';
    document.getElementById('imageFields').style.display = isImage ? 'grid' : 'none';
    document.getElementById('codeInput').style.display = isImage ? 'none' : 'block';
});

document.getElementById('linkType').addEventListener('change', function() {
    const label = document.getElementById('destLabel');
    const input = document.getElementsByName('link_url')[0];
    if (this.value === 'whatsapp') {
        label.innerText = 'WhatsApp Number';
        input.placeholder = 'e.g., 919876543210';
    } else if (this.value === 'call') {
        label.innerText = 'Phone Number';
        input.placeholder = 'e.g., +919876543210';
    } else {
        label.innerText = 'Destination URL';
        input.placeholder = 'https://...';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
