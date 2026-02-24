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
    $status = clean($_POST['status']);

    if (empty($name)) {
        $_SESSION['flash_msg'] = "Category name is required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, color, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $icon, $color, $status]);
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
    $status = clean($_POST['status']);

    try {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, color = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $icon, $color, $status, $id]);
        redirect('admin/categories.php', 'Category updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
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
$categories = $pdo->query("SELECT * FROM categories ORDER BY created_at DESC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
    <!-- Category Form (Add/Edit) -->
    <div style="background: white; padding: 25px; border-radius: 12px; height: fit-content; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;"><?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?></h3>
        <form action="" method="POST">
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
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active" <?php echo ($edit_cat && $edit_cat['status'] == 'active') ? 'selected' : ''; ?>>Active (Visible)</option>
                    <option value="disabled" <?php echo ($edit_cat && $edit_cat['status'] == 'disabled') ? 'selected' : ''; ?>>Disabled (Hidden)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea name="description" class="form-control" rows="3"><?php echo $edit_cat ? $edit_cat['description'] : ''; ?></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="<?php echo $edit_cat ? 'update_category' : 'add_category'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center;">
                    <?php echo $edit_cat ? 'Update Category' : 'Save Category'; ?>
                </button>
                <?php if ($edit_cat): ?>
                    <a href="categories.php" class="btn" style="background: #f1f5f9; color: #444;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">All Categories</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Status</th>
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
                        <span class="badge" style="background: <?php echo $cat['status'] == 'active' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $cat['status'] == 'active' ? '#065f46' : '#991b1b'; ?>;">
                            <?php echo ucfirst($cat['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #6366f1;">Edit</a>
                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Delete this category?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
    function openIconModal() {
        document.getElementById('iconModal').style.display = 'flex';
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

<?php include 'includes/footer.php'; ?>
