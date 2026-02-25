<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = "User Dashboard";
include 'includes/public_header.php';

// Fetch Bookmarks
$stmt = $pdo->prepare("SELECT p.* FROM posts p JOIN bookmarks b ON p.id = b.post_id WHERE b.user_id = ? ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookmarks = $stmt->fetchAll();

// Fetch Recent Activity (History)
$stmt = $pdo->prepare("SELECT p.*, ua.created_at as viewed_at FROM posts p JOIN user_activity ua ON p.id = ua.post_id WHERE ua.user_id = ? AND ua.action_type = 'view' ORDER BY ua.created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>

<main class="content-container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; background: white; padding: 30px; border-radius: 15px; border: 1px solid #eef2f6; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 80px; height: 80px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800;">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
            <div>
                <h1 style="font-size: 28px; margin: 0;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p style="color: #64748b; margin-top: 5px;">Manage your saved stories and reading history.</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="logout.php" class="btn" style="background: #fee2e2; color: #ef4444; border: 1px solid #fecaca;">Logout</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
        <!-- Left Column: Bookmarks -->
        <section>
            <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i data-feather="bookmark" style="color: var(--primary);"></i>
                Saved Articles (Bookmarks)
            </h3>
            
            <?php if (empty($bookmarks)): ?>
                <div style="background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 50px; text-align: center;">
                    <i data-feather="bookmark" style="width: 48px; height: 48px; color: #cbd5e1; margin-bottom: 15px;"></i>
                    <p style="color: #64748b;">You haven't saved any articles yet.</p>
                    <a href="index.php" style="color: var(--primary); font-weight: 700; margin-top: 10px; display: inline-block;">Start Exploring</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($bookmarks as $post): ?>
                        <div style="background: white; padding: 15px; border-radius: 12px; border: 1px solid #eef2f6; display: flex; gap: 20px; align-items: center;">
                            <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" style="width: 120px; height: 80px; object-fit: cover; border-radius: 8px;">
                            <div style="flex: 1;">
                                <h4 style="font-size: 16px; margin: 0 0 5px 0;"><a href="article/<?php echo $post['slug']; ?>" style="text-decoration: none; color: #1e293b;"><?php echo $post['title']; ?></a></h4>
                                <span style="font-size: 12px; color: #94a3b8;"><?php echo format_date($post['created_at']); ?></span>
                            </div>
                            <button onclick="toggleBookmark(<?php echo $post['id']; ?>)" style="background: none; border: none; cursor: pointer; color: #f59e0b;">
                                <i data-feather="bookmark" style="fill: #f59e0b;"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Right Column: Reading History -->
        <aside>
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                <i data-feather="clock" style="color: #6366f1;"></i>
                Recently Viewed
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($history as $h): ?>
                    <a href="article/<?php echo $h['slug']; ?>" style="display: block; text-decoration: none; group">
                        <h5 style="font-size: 14px; margin: 0; color: #1e293b; line-height: 1.4;"><?php echo $h['title']; ?></h5>
                        <p style="font-size: 11px; color: #94a3b8; margin-top: 5px;"><?php echo format_date($h['viewed_at']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 40px; background: #1e293b; color: white; padding: 20px; border-radius: 12px;">
                <h4 style="font-size: 15px; margin-bottom: 10px;">Notifications</h4>
                <p style="font-size: 12px; color: #94a3b8; line-height: 1.6;">Get real-time updates on breaking news.</p>
                <button id="push-btn" class="btn" style="width: 100%; margin-top: 15px; background: var(--primary); border: none;">Enable Notifications</button>
            </div>
        </aside>
    </div>
</main>

<script>
    async function toggleBookmark(postId) {
        try {
            const response = await fetch('api_bookmark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            });
            const res = await response.json();
            if (res.status === 'removed') {
                location.reload(); // Refresh to update list
            }
        } catch (e) {
            console.error('Bookmark error:', e);
        }
    }

    // Push Notification Helper
    document.getElementById('push-btn').onclick = function() {
        if (!("Notification" in window)) {
            alert("This browser does not support desktop notification");
        } else if (Notification.permission === "granted") {
            new Notification("Notifications already enabled!");
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    new Notification("Welcome to " + <?php echo json_encode(SITE_NAME); ?> + "! You will now receive breaking news.");
                }
            });
        }
    };
</script>

<?php include 'includes/public_footer.php'; ?>
