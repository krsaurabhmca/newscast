<?php
$page_title = "Categories";
include 'includes/header.php';

// Handle Add Category
if (isset($_POST['add_category'])) {
    if (!is_admin()) {
        redirect('admin/categories.php', 'Access denied. Only Admins can add categories.', 'danger');
    }
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
            $parent_id = empty($_POST['parent_id']) ? null : (int)$_POST['parent_id'];
            $custom_url = clean($_POST['custom_url'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, color, status, show_on_homepage, parent_id, custom_url) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $icon, $color, $show_on_homepage, $parent_id, $custom_url]);
            redirect('admin/categories.php', 'Category added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Category
if (isset($_POST['update_category'])) {
    if (!is_admin()) {
        redirect('admin/categories.php', 'Access denied. Only Admins can edit categories.', 'danger');
    }
    $id = $_POST['id'];
    $name = clean($_POST['name']);
    $slug = create_slug($name);
    $description = clean($_POST['description']);
    $icon = clean($_POST['icon']);
    $color = clean($_POST['color']);
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;

    try {
        $parent_id = empty($_POST['parent_id']) ? null : (int)$_POST['parent_id'];
        $custom_url = clean($_POST['custom_url'] ?? '');
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, color = ?, show_on_homepage = ?, parent_id = ?, custom_url = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $description, $icon, $color, $show_on_homepage, $parent_id, $custom_url, $id]);
        redirect('admin/categories.php', 'Category updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Toggle Category Status
if (isset($_GET['toggle'])) {
    if (!is_admin()) {
        redirect('admin/categories.php', 'Access denied.', 'danger');
    }
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
    if (!is_admin()) {
        redirect('admin/categories.php', 'Access denied.', 'danger');
    }
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
    if (!is_admin()) {
        redirect('admin/categories.php', 'Access denied. Only Admins can delete categories.', 'danger');
    }
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
    $stmt = $pdo->prepare("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id WHERE c.name LIKE ? ORDER BY c.created_at DESC");
    $stmt->execute(["%$search%"]);
    $categories = $stmt->fetchAll();
} else {
    $categories = $pdo->query("SELECT c.*, p.name as parent_name FROM categories c LEFT JOIN categories p ON c.parent_id = p.id ORDER BY c.created_at DESC")->fetchAll();
}

// Fetch Parent Category options (only top-level active categories, except the edited one itself)
$parent_opt_query = "SELECT id, name FROM categories WHERE parent_id IS NULL AND status = 'active'";
if ($edit_cat) {
    $parent_opt_query .= " AND id != " . (int)$edit_cat['id'];
}
$parent_categories = $pdo->query($parent_opt_query . " ORDER BY name ASC")->fetchAll();
?>

<!-- Categories List Card -->
<div class="premium-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <i data-feather="layers" style="color: var(--primary); width: 20px; height: 20px;"></i>
                All Categories
            </h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Manage news sections, parent hierarchy, direct links, and visibility</p>
        </div>
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <input type="text" name="s" class="form-control" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>" style="width: 220px; padding: 8px 14px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                <button type="submit" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; font-weight: 700;">Search</button>
                <?php if ($search): ?>
                    <a href="categories.php" class="btn" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; text-decoration: none; display: inline-flex; align-items: center; font-weight: 700;">Clear</a>
                <?php endif; ?>
            </form>
            <?php if (is_admin()): ?>
                <button onclick="openCategoryModal()" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"><i data-feather="plus" style="width: 15px;"></i> Add Category</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive table-container">
        <table class="premium-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Icon</th>
                    <th>Name</th>
                    <th>Parent Category</th>
                    <th>Direct Link</th>
                    <th style="width: 180px;">Status & Visibility</th>
                    <th style="width: 100px;">Posts</th>
                    <?php if (is_admin()): ?>
                        <th style="width: 110px; text-align: center;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): 
                    $post_count = $pdo->prepare("SELECT COUNT(*) FROM post_categories WHERE category_id = ?");
                    $post_count->execute([$cat['id']]);
                    $count = $post_count->fetchColumn();
                    $cat_color = !empty($cat['color']) ? $cat['color'] : '#6366f1';
                ?>
                <tr>
                    <td>
                        <div class="icon-badge" style="background: <?php echo $cat_color; ?>12; color: <?php echo $cat_color; ?>;">
                             <i data-feather="<?php echo !empty($cat['icon']) ? $cat['icon'] : 'folder'; ?>" style="width: 18px; height: 18px;"></i>
                        </div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $cat_color; ?>; display: inline-block;"></span>
                            <strong style="color: #1e293b; font-size: 14.5px;"><?php echo $cat['name']; ?></strong>
                        </div>
                    </td>
                    <td>
                        <?php echo !empty($cat['parent_name']) ? '<span style="font-weight:600; color:#475569;">' . htmlspecialchars($cat['parent_name']) . '</span>' : '<span style="color:#94a3b8; font-style:italic;">Top Level</span>'; ?>
                    </td>
                    <td>
                        <?php echo !empty($cat['custom_url']) ? '<a href="' . htmlspecialchars($cat['custom_url']) . '" target="_blank" class="link-badge"><i data-feather="external-link" style="width:12px;"></i> Link</a>' : '<span style="color:#94a3b8;">-</span>'; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 20px; align-items: center;">
                            <!-- Active Status Switch -->
                            <div class="switch-wrapper" title="<?php echo $cat['status'] == 'active' ? 'Status: Active' : 'Status: Disabled'; ?>">
                                <?php if (is_admin()): ?>
                                    <a href="?toggle=<?php echo $cat['id']; ?>" style="display: flex; align-items: center;">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center;">
                                <?php endif; ?>
                                    <label class="modern-switch" style="margin: 0; pointer-events: none;">
                                        <input type="checkbox" <?php echo $cat['status'] == 'active' ? 'checked' : ''; ?>>
                                        <span class="modern-slider"></span>
                                    </label>
                                <?php if (is_admin()): ?>
                                    </a>
                                <?php else: ?>
                                    </div>
                                <?php endif; ?>
                                <span>Active</span>
                            </div>
                            
                            <!-- Featured Status Switch -->
                            <div class="switch-wrapper" title="<?php echo !empty($cat['show_on_homepage']) ? 'Featured on Homepage' : 'Not Featured'; ?>">
                                <?php if (is_admin()): ?>
                                    <a href="?toggle_featured=<?php echo $cat['id']; ?>" style="display: flex; align-items: center;">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center;">
                                <?php endif; ?>
                                    <label class="modern-switch" style="margin: 0; pointer-events: none;">
                                        <input type="checkbox" <?php echo !empty($cat['show_on_homepage']) ? 'checked' : ''; ?>>
                                        <span class="modern-slider modern-slider-featured"></span>
                                    </label>
                                <?php if (is_admin()): ?>
                                    </a>
                                <?php else: ?>
                                    </div>
                                <?php endif; ?>
                                <span>Featured</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="post-counter"><?php echo $count; ?> posts</span>
                    </td>
                    <?php if (is_admin()): ?>
                        <td>
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-action-edit" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #e2e8f0; text-decoration: none;" title="Edit Category">
                                    <i data-feather="edit-3" style="width: 14px; height: 14px;"></i>
                                </a>
                                <a href="?delete=<?php echo $cat['id']; ?>" class="btn btn-action-delete" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid transparent; text-decoration: none;" onclick="return confirm('Delete this category? All subcategories will become orphan.')" title="Delete Category">
                                    <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                </a>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Add/Edit Modal -->
<div class="modal-overlay" id="categoryModal" style="<?php echo $edit_cat ? 'display: flex;' : 'display: none;'; ?> justify-content: center; align-items: center; position: fixed; inset: 0; z-index: 9999;">
    <div class="modal-content" style="max-width: 500px; width: 90%; background: #ffffff; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header" style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <i data-feather="<?php echo $edit_cat ? 'edit' : 'plus-circle'; ?>" style="color: var(--primary); width: 18px; height: 18px;"></i>
                <?php echo $edit_cat ? 'Edit Category' : 'Add New Category'; ?>
            </h3>
            <button type="button" onclick="closeCategoryModal()" style="background: none; border: none; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; transition: color 0.2s;" onmouseover="this.style.color='#475569'" onmouseout="this.style.color='#94a3b8'"><i data-feather="x" style="width: 20px; height: 20px;"></i></button>
        </div>
        <div style="padding: 25px; max-height: 75vh; overflow-y: auto;">
            <form action="categories.php" method="POST">
                <?php if ($edit_cat): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Category Name <span style="color: red;">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Technology" value="<?php echo $edit_cat ? $edit_cat['name'] : ''; ?>" style="border-radius: 8px; padding: 10px 14px; font-size: 13.5px; border: 1px solid #cbd5e1; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Parent Category (Optional)</label>
                    <select name="parent_id" class="form-control" style="border: 1px solid #cbd5e1; height: 42px; outline: none; background: #fff; padding: 0 10px; border-radius: 8px; font-size: 13.5px; cursor: pointer; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                        <option value="">-- None (Top Level) --</option>
                        <?php foreach ($parent_categories as $pcat): ?>
                            <option value="<?php echo $pcat['id']; ?>" <?php echo ($edit_cat && $edit_cat['parent_id'] == $pcat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($pcat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Direct Link/URL (Optional)</label>
                    <input type="url" name="custom_url" class="form-control" placeholder="https://example.com/custom-page" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['custom_url']) : ''; ?>" style="border-radius: 8px; padding: 10px 14px; font-size: 13.5px; border: 1px solid #cbd5e1; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                    <span class="field-hint" style="font-size: 11px; color: #94a3b8; display: block; margin-top: 4px;">If set, clicking this category will open this URL directly.</span>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Category Icon</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div id="icon-preview" style="width: 42px; height: 42px; background: #f1f5f9; border-radius: 8px; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #e2e8f0; transition: all 0.2s;">
                            <i data-feather="<?php echo $edit_cat ? $edit_cat['icon'] : 'folder'; ?>" style="width: 18px; height: 18px;"></i>
                        </div>
                        <input type="text" name="icon" id="icon-input" class="form-control" placeholder="Icon name" value="<?php echo $edit_cat ? $edit_cat['icon'] : 'folder'; ?>" style="border-radius: 8px; padding: 10px 14px; font-size: 13.5px; border: 1px solid #cbd5e1; outline: none; flex: 1; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                        <button type="button" class="btn" onclick="openIconModal()" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 15px; font-size: 13px; font-weight: 700; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Choose</button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Theme Color</label>
                    <div class="color-list">
                        <?php 
                        $std_colors = ['#dc2626', '#2563eb', '#6366f1', '#db2777', '#16a34a', '#e11d48', '#0891b2', '#f59e0b', '#7c3aed', '#0d9488', '#475569', '#1d4ed8', '#ea580c', '#1e293b'];
                        foreach($std_colors as $color): 
                            $isSelected = ($edit_cat && $edit_cat['color'] == $color) || (!$edit_cat && $color == '#6366f1');
                        ?>
                            <div class="color-item <?php echo $isSelected ? 'selected' : ''; ?>" style="background: <?php echo $color; ?>;" onclick="selectColor('<?php echo $color; ?>', this)"></div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="color" id="color-input" class="form-control" style="width: 55px; height: 42px; padding: 4px; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;" value="<?php echo $edit_cat ? $edit_cat['color'] : '#6366f1'; ?>">
                        <input type="text" id="color-hex" class="form-control" value="<?php echo $edit_cat ? $edit_cat['color'] : '#6366f1'; ?>" oninput="syncColorInput(this.value)" style="border-radius: 8px; padding: 10px 14px; font-size: 13.5px; border: 1px solid #cbd5e1; outline: none; flex: 1; transition: border-color 0.2s;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Featured Section</label>
                    <div style="margin-top: 8px; display: flex; align-items: center;">
                        <label class="switch-wrapper" style="cursor: pointer; display: flex; align-items: center; gap: 10px;">
                            <label class="modern-switch" style="margin: 0;">
                                <input type="checkbox" name="show_on_homepage" value="1" <?php echo ($edit_cat && !empty($edit_cat['show_on_homepage'])) ? 'checked' : ''; ?>>
                                <span class="modern-slider modern-slider-featured"></span>
                            </label>
                            <span style="font-size: 13.5px; font-weight: 600; color: #475569;">Show as featured section on homepage</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="font-size: 12.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief details about category content..." style="border-radius: 8px; padding: 10px 14px; font-size: 13.5px; border: 1px solid #cbd5e1; outline: none; transition: border-color 0.2s; resize: vertical;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'"><?php echo $edit_cat ? $edit_cat['description'] : ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 12px; margin-top: 25px;">
                    <button type="submit" name="<?php echo $edit_cat ? 'update_category' : 'add_category'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center; border-radius: 8px; padding: 12px; font-weight: 700; font-size: 13.5px; cursor: pointer;">
                        <?php echo $edit_cat ? 'Update Category' : 'Save Category'; ?>
                    </button>
                    <button type="button" onclick="closeCategoryModal()" class="btn" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 20px; font-weight: 700; font-size: 13.5px; cursor: pointer;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Icon Selector Modal -->
<div id="iconModal" class="modal-overlay" style="display: none; justify-content: center; align-items: center; position: fixed; inset: 0; z-index: 10000;">
    <div class="modal-content" style="max-width: 500px; width: 90%; background: #ffffff; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header" style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #1e293b;">Choose Category Icon</h3>
            <button onclick="closeIconModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center; transition: color 0.2s;" onmouseover="this.style.color='#475569'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        <div class="icon-grid">
            <?php 
            $icons = ['flag', 'briefcase', 'cpu', 'film', 'activity', 'heart', 'zap', 'coffee', 'book', 'book-open', 'cloud', 'message-square', 'message-circle', 'globe', 'map', 'map-pin', 'shield', 'trending-up', 'camera', 'music', 'shopping-bag', 'shopping-cart', 'award', 'anchor', 'bell', 'battery', 'bluetooth', 'tv', 'users', 'home', 'calendar', 'file-text', 'thumbs-up', 'video', 'image', 'dollar-sign', 'info', 'help-circle', 'mail'];
            foreach($icons as $icon): ?>
                <div class="icon-item" onclick="selectIcon('<?php echo $icon; ?>')">
                    <i data-feather="<?php echo $icon; ?>"></i>
                    <small style="font-size: 10px; font-weight: 600; margin-top: 4px;"><?php echo $icon; ?></small>
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
    }
    
    function closeIconModal() {
        document.getElementById('iconModal').style.display = 'none';
    }
    
    function selectIcon(iconName) {
        document.getElementById('icon-input').value = iconName;
        document.getElementById('icon-preview').innerHTML = `<i data-feather="${iconName}" style="width: 18px; height: 18px;"></i>`;
        feather.replace();
        closeIconModal();
    }
    
    function selectColor(hex, element) {
        document.getElementById('color-input').value = hex;
        document.getElementById('color-hex').value = hex;
        
        // Remove selected class from all items
        document.querySelectorAll('.color-item').forEach(item => {
            item.classList.remove('selected');
        });
        if(element) {
            element.classList.add('selected');
        }
        updateIconColor(hex);
    }
    
    function syncColorInput(hex) {
        if(/^#[0-9A-F]{6}$/i.test(hex)) {
            document.getElementById('color-input').value = hex;
            updateIconColor(hex);
            
            // Highlight matching color item if any
            document.querySelectorAll('.color-item').forEach(item => {
                const rgb = item.style.backgroundColor;
                // convert rgb to hex to compare
                const hexColor = rgb2hex(rgb);
                if(hexColor.toLowerCase() === hex.toLowerCase()) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        }
    }

    function rgb2hex(rgb) {
        if (rgb.search("rgb") == -1) {
            return rgb;
        } else {
            rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
            function hex(x) {
                return ("0" + parseInt(x).toString(16)).slice(-2);
            }
            return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
        }
    }
    
    function updateIconColor(hex) {
        document.getElementById('icon-preview').style.color = hex;
        document.getElementById('icon-preview').style.borderColor = hex;
        document.getElementById('icon-preview').style.background = hex + '12';
    }

    document.getElementById('color-input').addEventListener('input', function() {
        document.getElementById('color-hex').value = this.value;
        updateIconColor(this.value);
    });
</script>

<style>
    /* Premium Table and Form Styles */
    .premium-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
        border: 1px solid #e2e8f0;
        padding: 30px;
        transition: all 0.3s ease;
    }
    .premium-card:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
    }
    
    .table-container {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        margin-top: 15px;
    }
    .premium-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }
    .premium-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 16px 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    .premium-table td {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: middle;
        font-size: 13.5px;
        transition: all 0.2s;
    }
    .premium-table tr:hover td {
        background-color: #f8fafc;
    }
    .premium-table tr:last-child td {
        border-bottom: none;
    }
    
    .icon-badge {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    .icon-badge:hover {
        transform: scale(1.08);
    }
    
    .link-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
        padding: 5px 12px;
        background: rgba(99, 102, 241, 0.08);
        border-radius: 6px;
        transition: all 0.2s;
    }
    .link-badge:hover {
        background: var(--primary);
        color: #ffffff;
    }
    
    .post-counter {
        display: inline-block;
        padding: 4px 10px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 20px;
        font-size: 11.5px;
        font-weight: 700;
    }
    
    /* Toggle Switches styling */
    .switch-wrapper {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }
    .modern-switch {
        position: relative;
        display: inline-block;
        width: 42px;
        height: 22px;
    }
    .modern-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .modern-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 34px;
    }
    .modern-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .modern-switch input:checked + .modern-slider {
        background-color: #10b981;
    }
    .modern-switch input:checked + .modern-slider-featured {
        background-color: #6366f1;
    }
    .modern-switch input:checked + .modern-slider:before {
        transform: translateX(20px);
    }
    
    /* Modals Glassmorphism */
    .modal-overlay {
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(10px);
        transition: opacity 0.3s ease;
    }
    .modal-content {
        border-radius: 20px;
        border: 1px solid rgba(255,255,255,0.7);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);
        overflow: hidden;
        animation: modalScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes modalScale {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    
    .color-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .color-item {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .color-item:hover {
        transform: scale(1.2);
    }
    .color-item.selected {
        border-color: #ffffff;
        box-shadow: 0 0 0 2px #475569;
    }
    
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(65px, 1fr));
        gap: 10px;
        padding: 20px;
        max-height: 350px;
        overflow-y: auto;
    }
    .icon-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        color: #475569;
    }
    .icon-item:hover {
        background: #f1f5f9;
        color: var(--primary);
        transform: translateY(-2px);
    }
    .icon-item i {
        width: 20px;
        height: 20px;
    }
    
    /* Buttons */
    .btn-action-edit {
        background: #f1f5f9;
        color: #475569;
        transition: all 0.2s;
    }
    .btn-action-edit:hover {
        background: #e2e8f0;
        color: var(--primary);
        transform: translateY(-1px);
    }
    .btn-action-delete {
        background: #fef2f2;
        color: #ef4444;
        transition: all 0.2s;
    }
    .btn-action-delete:hover {
        background: #fee2e2;
        transform: translateY(-1px);
    }
</style>

<?php include 'includes/footer.php'; ?>
