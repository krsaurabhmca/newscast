<?php
$page_title = "Manage Posts";
include 'includes/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Delete featured image file
    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../assets/images/posts/" . $img)) {
        unlink("../assets/images/posts/" . $img);
    }

    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/posts.php', 'Post deleted successfully!');
}

// Fetch Posts
$stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(c.name) as cat_names 
                     FROM posts p 
                     LEFT JOIN post_categories pc ON p.id = pc.post_id 
                     LEFT JOIN categories c ON pc.category_id = c.id 
                     GROUP BY p.id 
                     ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();
?>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="font-size: 18px; font-weight: 600;">All News Posts</h3>
        <a href="post_add.php" class="btn btn-primary">Add New Post</a>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th width="80">Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No posts found. Start by creating one!</td></tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <?php if ($post['featured_image']): ?>
                            <img src="../assets/images/posts/<?php echo $post['featured_image']; ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #94a3b8;">No Img</div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?php echo $post['title']; ?></strong>
                        <?php if ($post['is_featured']): ?>
                            <span class="badge" style="background: #fdf2f2; color: #991b1b; font-size: 9px; margin-left: 5px;">Featured</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge" style="background: #eef2ff; color: #4338ca;"><?php echo $post['cat_names'] ?: 'Uncategorized'; ?></span></td>
                    <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span></td>
                    <td><?php echo $post['views']; ?></td>
                    <td><?php echo format_date($post['created_at']); ?></td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="post_edit.php?id=<?php echo $post['id']; ?>" class="btn" style="background: #f1f5f9; color: #475569; padding: 5px 10px; font-size: 12px;">Edit</a>
                            <a href="?delete=<?php echo $post['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="return confirm('Archive this post?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
