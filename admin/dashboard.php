<?php
$page_title = "Dashboard";
include 'includes/header.php';

// Fetch Stats
$total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_views = $pdo->query("SELECT SUM(views) FROM posts")->fetchColumn() ?: 0;
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent Posts
$stmt = $pdo->query("SELECT p.*, c.name as cat_name FROM posts p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 5");
$recent_posts = $stmt->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Posts</h3>
        <div class="value"><?php echo $total_posts; ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Views</h3>
        <div class="value"><?php echo $total_views; ?></div>
    </div>
    <div class="stat-card">
        <h3>Categories</h3>
        <div class="value"><?php echo $total_categories; ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Users</h3>
        <div class="value"><?php echo $total_users; ?></div>
    </div>
</div>

<div class="section-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 18px; font-weight: 600;">Recently Published</h3>
        <a href="posts.php" class="btn btn-primary" style="font-size: 13px;">View All</a>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th>Views</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_posts)): ?>
                <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No posts found yet.</td></tr>
            <?php else: ?>
                <?php foreach ($recent_posts as $post): ?>
                <tr>
                    <td><strong><?php echo $post['title']; ?></strong></td>
                    <td><span class="badge" style="background: #eef2ff; color: #4338ca;"><?php echo $post['cat_name']; ?></span></td>
                    <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span></td>
                    <td><?php echo format_date($post['created_at']); ?></td>
                    <td><?php echo $post['views']; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
