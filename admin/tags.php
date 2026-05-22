<?php
$page_title = "Tags Management";
include 'includes/header.php';

// Handle Add Tag
if (isset($_POST['add_tag'])) {
    $name = clean($_POST['name']);
    $slug = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($name);

    if (empty($name)) {
        $_SESSION['flash_msg'] = "Tag name is required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);
            redirect('admin/tags.php', 'Tag added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: Tag with this slug may already exist.";
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Tag
if (isset($_POST['update_tag'])) {
    $id = $_POST['id'];
    $name = clean($_POST['name']);
    $slug = !empty($_POST['slug']) ? create_slug($_POST['slug']) : create_slug($name);

    try {
        $stmt = $pdo->prepare("UPDATE tags SET name = ?, slug = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $id]);
        redirect('admin/tags.php', 'Tag updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: Tag with this slug may already exist.";
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete Tag
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/tags.php', 'Tag deleted successfully!');
}

// Fetch Tag for Editing
$edit_tag = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_tag = $stmt->fetch();
}

// Fetch All Tags
$tags = $pdo->query("SELECT * FROM tags ORDER BY created_at DESC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
    <!-- Tag Form (Add/Edit) -->
    <div style="background: white; padding: 25px; border-radius: 12px; height: fit-content; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;"><?php echo $edit_tag ? 'Edit Tag' : 'Add New Tag'; ?></h3>
        <form action="" method="POST">
            <?php if ($edit_tag): ?>
                <input type="hidden" name="id" value="<?php echo $edit_tag['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Tag Name</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Breaking News" value="<?php echo $edit_tag ? $edit_tag['name'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Slug (Optional)</label>
                <input type="text" name="slug" class="form-control" placeholder="e.g. breaking-news" value="<?php echo $edit_tag ? $edit_tag['slug'] : ''; ?>">
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="<?php echo $edit_tag ? 'update_tag' : 'add_tag'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center;">
                    <?php echo $edit_tag ? 'Update Tag' : 'Save Tag'; ?>
                </button>
                <?php if ($edit_tag): ?>
                    <a href="tags.php" class="btn" style="background: #f1f5f9; color: #444;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tags List -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">All Tags</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Posts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tags as $tag): 
                    $post_count = $pdo->prepare("SELECT COUNT(*) FROM post_tags WHERE tag_id = ?");
                    $post_count->execute([$tag['id']]);
                    $count = $post_count->fetchColumn();
                ?>
                <tr>
                    <td><strong><?php echo $tag['name']; ?></strong></td>
                    <td><code><?php echo $tag['slug']; ?></code></td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="../tag/<?php echo $tag['slug']; ?>" target="_blank" title="View Tag" style="background: #fff; border: 1px solid #e2e8f0; color: #10b981; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#f0fdf4'; this.style.borderColor='#10b981';" onmouseout="this.style.background='#fff'; this.style.borderColor='#e2e8f0';"><i data-feather="external-link" style="width: 16px;"></i></a>
                            <a href="?edit=<?php echo $tag['id']; ?>" title="Edit Tag" style="background: #fff; border: 1px solid #e2e8f0; color: #6366f1; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#eef2ff'; this.style.borderColor='#6366f1';" onmouseout="this.style.background='#fff'; this.style.borderColor='#e2e8f0';"><i data-feather="edit-2" style="width: 16px;"></i></a>
                            <a href="?delete=<?php echo $tag['id']; ?>" onclick="return confirm('Delete this tag?')" title="Delete Tag" style="background: #fff; border: 1px solid #fecaca; color: #ef4444; width: 34px; height: 34px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='#fef2f2'; this.style.borderColor='#ef4444';" onmouseout="this.style.background='#fff'; this.style.borderColor='#fecaca';"><i data-feather="trash-2" style="width: 16px;"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tags)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b;">No tags found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
