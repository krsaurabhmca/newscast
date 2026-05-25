<?php
$page_title = "Categories";
include 'includes/header.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
    $name = clean($_POST['name']);
    $slug = create_slug($name);
    $description = clean($_POST['description']);
    $icon = clean($_POST['icon']);
    $color = clean($_POST['color']);
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;

    if (empty($name)) {
        $_SESSION['flash_msg'] = "Category name is required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, color, status, show_on_homepage) VALUES (?, ?, ?, ?, ?, 'active', ?)");
            $stmt->execute([$name, $slug, $description, $icon, $color, $show_on_homepage]);
            redirect('admin/categories.php', 'Category added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Category
if (isset($_POST['update_category'])) {
    $id = $_POST['id'];
    $name = clean($_POST['name']);
    $slug = create_slug($name);
    $description = clean($_POST['description']);
    $icon = clean($_POST['icon']);
    $color = clean($_POST['color']);
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, color = ?, show_on_homepage = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $icon, $color, $show_on_homepage, $id]);
        redirect('admin/categories.php', 'Category updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Toggle Category Status
if (isset($_GET['toggle'])) {
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot modify data.', 'danger');
        exit;
    }
    $id = $_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE categories SET status = IF(status='active', 'disabled', 'active') WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/categories.php', 'Category status updated!');
}

// Handle Toggle Featured Status
if (isset($_GET['toggle_featured'])) {
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot modify data.', 'danger');
        exit;
    }
    $id = $_GET['toggle_featured'];
    $stmt = $pdo->prepare("UPDATE categories SET show_on_homepage = IF(show_on_homepage=1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/categories.php', 'Featured status updated!');
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot delete data.', 'danger');
        exit;
    }
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/categories.php', 'Category deleted successfully!');
}

// Fetch Category for Editing
$edit_cat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_cat = $stmt->fetch();
}

// Fetch All Categories
$search = trim($_GET['s'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE name LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%"]);
    $categories = $stmt->fetchAll();
} else {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll();
}
?>

<!-- Categories List -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">All Categories</h3>
        <div style="display: flex; gap: 15px; align-items: center;">
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="s" class="form-control" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; padding: 8px 12px; font-size: 13px;">
                <button type="submit" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px;">Search</button>
                <?php if ($search): ?>
                    <a href="categories.php" class="btn" style="padding: 8px 15px; font-size: 13px; background: #f1f5f9; color: #475569;">Clear</a>
                <?php endif; ?>
            </form>
            <button onclick="openCategoryModal()" class="btn btn-primary"><i data-feather="plus" style="width: 16px;"></i> Add New Category</button>
        </div>
    </div>
        <div class="table-responsive"><table class="content-table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Status & Visibility</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): 
                    $post_count = $pdo->prepare("SELECT COUNT(*) FROM post_categories WHERE category_id = ?");
                    $post_count->execute([$cat['id']]);
                    $count = $post_count->fetchColumn();
                ?>
                <tr>
                    <td>
                        <div style="color: <?php echo $cat['color']; ?>;">
                             <i data-feather="<?php echo $cat['icon']; ?>"></i>
                        </div>
                    </td>
                    <td><strong><?php echo $cat['name']; ?></strong></td>
                    <td>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <a href="?toggle=<?php echo $cat['id']; ?>" style="text-decoration: none; display: flex; align-items: center; gap: 6px;" title="Toggle Active Status">
                                <label class="switch" style="display: flex; align-items: center; cursor: pointer; pointer-events: none; margin: 0;">
                                    <div style="position: relative; width: 36px; height: 20px;">
                                        <input type="checkbox" <?php echo $cat['status'] == 'active' ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0; position: absolute;">
                                        <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 20px;"></span>
                                    </div>
                                </label>
                                <span style="font-size: 11px; font-weight: 700; color: #64748b;">Active</span>
                            </a>
                            
                            <a href="?toggle_featured=<?php echo $cat['id']; ?>" style="text-decoration: none; display: flex; align-items: center; gap: 6px;" title="Toggle Homepage Featured">
                                <label class="switch" style="display: flex; align-items: center; cursor: pointer; pointer-events: none; margin: 0;">
                                    <div style="position: relative; width: 36px; height: 20px;">
                                        <input type="checkbox" <?php echo !empty($cat['show_on_homepage']) ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0; position: absolute;">
                                        <span class="slider slider-featured" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 20px;"></span>
                                    </div>
                                </label>
                                <span style="font-size: 11px; font-weight: 700; color: #64748b;">Featured</span>
                            </a>
                        </div>
                    </td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn" style="padding: 6px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f1f5f9; color: #475569;" title="Edit">
                                <i data-feather="edit-2" style="width: 14px; margin: 0;"></i>
                            </a>
                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" style="padding: 6px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fef2f2; color: #ef4444; border: 1px solid transparent;" onclick="return confirm('Delete this category?')" title="Delete">
                                <i data-feather="trash-2" style="width: 14px; margin: 0;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>

<!-- Category Add/Edit Modal -->
<div class="modal-overlay" id="categoryModal" style="<?php echo $edit_cat ? 'display: flex;' : ''; ?>">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?></h3>
            <button type="button" onclick="closeCategoryModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center;"><i data-feather="x"></i></button>
        </div>
        <div style="padding: 25px; overflow-y: auto;">
            <form action="categories.php" method="POST">
                <?php if ($edit_cat): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Technology" value="<?php echo $edit_cat ? $edit_cat['name'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Category Icon</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div id="icon-preview" style="padding: 10px; background: #f1f5f9; border-radius: 8px; color: var(--primary);">
                            <i data-feather="<?php echo $edit_cat ? $edit_cat['icon'] : 'folder'; ?>"></i>
                        </div>
                        <input type="text" name="icon" id="icon-input" class="form-control" placeholder="Icon name" value="<?php echo $edit_cat ? $edit_cat['icon'] : 'folder'; ?>">
                        <button type="button" class="btn" onclick="openIconModal()" style="background: #f1f5f9; color: #444;">Choose</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Theme Color</label>
                    <div class="color-list" style="max-height: 80px; overflow-y: auto; padding: 5px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px;">
                        <?php 
                        $std_colors = ['#dc2626', '#2563eb', '#6366f1', '#db2777', '#16a34a', '#e11d48', '#0891b2', '#f59e0b', '#7c3aed', '#0d9488', '#475569', '#1d4ed8', '#ea580c', '#1e293b'];
                        foreach($std_colors as $color): ?>
                            <div class="color-item" style="background: <?php echo $color; ?>;" onclick="selectColor('<?php echo $color; ?>')"></div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="color" id="color-input" class="form-control" style="width: 60px; height: 45px; padding: 5px;" value="<?php echo $edit_cat ? $edit_cat['color'] : '#6366f1'; ?>">
                        <input type="text" id="color-hex" class="form-control" value="<?php echo $edit_cat ? $edit_cat['color'] : '#6366f1'; ?>" oninput="syncColorInput(this.value)">
                    </div>
                </div>



                <div class="form-group">
                    <label>Featured Category</label>
                    <div style="margin-top: 5px;">
                        <label class="switch" style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <div style="position: relative; width: 44px; height: 24px;">
                                <input type="checkbox" name="show_on_homepage" value="1" <?php echo ($edit_cat && !empty($edit_cat['show_on_homepage'])) ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0; position: absolute;">
                                <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px;"></span>
                            </div>
                            <span style="font-size: 14px; font-weight: 600; color: #475569;">Show as Featured Section on Homepage (Max 3 recommended)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $edit_cat ? $edit_cat['description'] : ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" name="<?php echo $edit_cat ? 'update_category' : 'add_category'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center;">
                        <?php echo $edit_cat ? 'Update Category' : 'Save Category'; ?>
                    </button>
                    <button type="button" onclick="closeCategoryModal()" class="btn" style="background: #f1f5f9; color: #444;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<!-- Icon Selector Modal -->
<div id="iconModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Choose Category Icon</h3>
            <button onclick="closeIconModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div class="icon-grid">
            <?php 
            $icons = ['flag', 'briefcase', 'cpu', 'film', 'activity', 'heart', 'zap', 'coffee', 'book', 'cloud', 'message-circle', 'globe', 'map-pin', 'shield', 'trending-up', 'camera', 'music', 'shopping-bag', 'award', 'anchor', 'bell', 'battery', 'bluetooth'];
            foreach($icons as $icon): ?>
                <div class="icon-item" onclick="selectIcon('<?php echo $icon; ?>')">
                    <i data-feather="<?php echo $icon; ?>"></i>
                    <small><?php echo $icon; ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function openCategoryModal() {
        document.getElementById('categoryModal').style.display = 'flex';
    }
    
    function closeCategoryModal() {
        <?php if ($edit_cat): ?>
            window.location.href = 'categories.php';
        <?php else: ?>
            document.getElementById('categoryModal').style.display = 'none';
        <?php endif; ?>
    }

    function openIconModal() {
        document.getElementById('iconModal').style.display = 'flex';
        // Ensure icon modal is on top of category modal
        document.getElementById('iconModal').style.zIndex = '10000';
    }
    
    function closeIconModal() {
        document.getElementById('iconModal').style.display = 'none';
    }
    
    function selectIcon(iconName) {
        document.getElementById('icon-input').value = iconName;
        document.getElementById('icon-preview').innerHTML = `<i data-feather="${iconName}"></i>`;
        feather.replace();
        closeIconModal();
    }
    
    function selectColor(hex) {
        document.getElementById('color-input').value = hex;
        document.getElementById('color-hex').value = hex;
        updateIconColor(hex);
    }
    
    function syncColorInput(hex) {
        if(/^#[0-9A-F]{6}$/i.test(hex)) {
            document.getElementById('color-input').value = hex;
            updateIconColor(hex);
        }
    }
    
    function updateIconColor(hex) {
        document.getElementById('icon-preview').style.color = hex;
    }

    document.getElementById('color-input').addEventListener('input', function() {
        document.getElementById('color-hex').value = this.value;
        updateIconColor(this.value);
    });
</script>

<style>
    /* CSS for Toggle Switches */
    .switch input:checked + .slider {
        background-color: #10b981 !important;
    }
    .switch input:checked + .slider-featured {
        background-color: #6366f1 !important;
    }
    .switch .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .switch input:checked + .slider:before {
        transform: translateX(16px);
    }
</style>

<?php include 'includes/footer.php'; ?>
