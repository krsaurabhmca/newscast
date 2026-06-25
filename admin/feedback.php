<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
$page_title = htmlspecialchars(get_setting('apni_baat_label', 'Apni Baat')) . " — Submissions";
include 'includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot delete data.', 'danger');
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    redirect('admin/feedback.php', 'Submission deleted permanently.');
}

// Handle approve
if (isset($_GET['approve'])) {
    if (is_demo_account()) {
        redirect('admin/feedback.php', 'Action restricted: Demo accounts cannot modify data.', 'danger');
        exit;
    }
    $id = (int)$_GET['approve'];
    
    // Fetch submission
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
    $sub = $stmt->fetch();
    
    if ($sub && $sub['status'] === 'pending') {
        try {
            $pdo->beginTransaction();
            
            // Create slug
            $slug = create_slug($sub['subject']);
            
            // Resolve uniqueness of slug
            $check_slug = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $check_slug->execute([$slug]);
            if ($check_slug->fetch()) {
                $slug .= '-' . time();
            }
            
            // Insert into posts table (published instantly)
            $post_stmt = $pdo->prepare("INSERT INTO posts (category_id, user_id, title, slug, content, excerpt, featured_image, status, published_at, views) VALUES (?, ?, ?, ?, ?, ?, ?, 'published', NOW(), 0)");
            
            // Resolve category_id (default to General category ID 16 if not set/deleted)
            $cat_id = $sub['category_id'] ?: 16;
            
            // Append author credits to the content block
            $author_name = $sub['name'];
            $author_email = $sub['email'];
            $author_phone = $sub['phone'] ? " (Phone: " . $sub['phone'] . ")" : "";
            $credits_html = "<p><em>Submitted by: <strong>" . htmlspecialchars($author_name) . "</strong> (" . htmlspecialchars($author_email) . "$author_phone)</em></p><hr>\n";
            $full_content = $credits_html . $sub['message'];
            
            // Generate clean excerpt
            $excerpt = get_excerpt(strip_tags($sub['message']), 25);
            
            $post_stmt->execute([
                $cat_id,
                (int)$_SESSION['user_id'], // Attributed to approving admin/dev
                $sub['subject'],
                $slug,
                $full_content,
                $excerpt,
                $sub['featured_image']
            ]);
            
            $new_post_id = $pdo->lastInsertId();
            
            // Link category
            $cat_stmt = $pdo->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            $cat_stmt->execute([$new_post_id, $cat_id]);
            
            // Update submission status to approved
            $update_stmt = $pdo->prepare("UPDATE feedback SET status = 'approved' WHERE id = ?");
            $update_stmt->execute([$id]);
            
            $pdo->commit();
            redirect('admin/posts.php', 'Submission approved and published live!', 'success');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['flash_msg'] = "Error approving article: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
            redirect('admin/feedback.php');
            exit;
        }
    } else {
        redirect('admin/feedback.php', 'Submission already processed or invalid.', 'danger');
        exit;
    }
}

// Handle reject
if (isset($_GET['reject'])) {
    if (is_demo_account()) {
        redirect('admin/feedback.php', 'Action restricted: Demo accounts cannot modify data.', 'danger');
        exit;
    }
    $id = (int)$_GET['reject'];
    $stmt = $pdo->prepare("UPDATE feedback SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/feedback.php', 'Submission marked as rejected.', 'warning');
    exit;
}

// Stats counters
$total_count    = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$pending_count  = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'pending'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'approved'")->fetchColumn();
$rejected_count = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'rejected'")->fetchColumn();

// Filter Tabs
$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'])) {
    $filter = 'pending';
}
$where_clause  = match($filter) {
    'pending'  => "WHERE f.status = 'pending'",
    'approved' => "WHERE f.status = 'approved'",
    'rejected' => "WHERE f.status = 'rejected'",
    default   => ""
};

// Fetch Submissions
$submissions = $pdo->query("SELECT f.*, c.name as category_name 
                            FROM feedback f 
                            LEFT JOIN categories c ON f.category_id = c.id 
                            $where_clause 
                            ORDER BY f.created_at DESC")->fetchAll();

// Viewing detailed single submission
$view_sub = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT f.*, c.name as category_name 
                           FROM feedback f 
                           LEFT JOIN categories c ON f.category_id = c.id 
                           WHERE f.id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $view_sub = $stmt->fetch();
}
?>

<!-- Stats Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(99,102,241,0.1); color: var(--primary); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="inbox" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Submissions</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $total_count; ?></div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(245,158,11,0.1); color: #f59e0b; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="clock" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Pending Review</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $pending_count; ?></div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(16,185,129,0.1); color: #10b981; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="check-circle" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Approved</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $approved_count; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Layout structure -->
<div style="display: grid; grid-template-columns: 1fr <?php echo $view_sub ? '1fr' : ''; ?>; gap: 25px; align-items: start;">

    <!-- List block -->
    <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0;">Submissions Inbox</h3>
            <div style="display: flex; gap: 8px;">
                <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label): ?>
                    <a href="?filter=<?php echo $key; ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; <?php echo $filter === $key ? 'background: var(--primary); color: white;' : 'background: #f1f5f9; color: #475569;'; ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($submissions)): ?>
            <div style="padding: 60px; text-align: center; color: #94a3b8;">
                <i data-feather="upload-cloud" style="width: 40px; height: 40px; margin-bottom: 15px; display: block; margin: 0 auto 15px;"></i>
                <p style="font-weight: 600;">No articles in this section</p>
            </div>
        <?php else: ?>
            <?php foreach ($submissions as $sub): ?>
                <?php $is_pending = $sub['status'] === 'pending'; ?>
                <div style="padding: 18px 25px; border-bottom: 1px solid #f8fafc; display: flex; gap: 15px; align-items: start; <?php echo $is_pending ? 'background: #fafbff;' : ''; ?> <?php echo (isset($_GET['view']) && $_GET['view'] == $sub['id']) ? 'background: #eef2ff; border-left: 3px solid var(--primary);' : ''; ?>">
                    <!-- Avatar fallback -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $is_pending ? 'var(--primary)' : '#cbd5e1'; ?>; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 16px; flex-shrink: 0;">
                        <?php echo strtoupper(substr($sub['name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-weight: <?php echo $is_pending ? '800' : '600'; ?>; color: #0f172a; font-size: 14px;"><?php echo htmlspecialchars($sub['name']); ?></span>
                            <span style="font-size: 11px; color: #94a3b8; white-space: nowrap;"><?php echo date('d M, g:i A', strtotime($sub['created_at'])); ?></span>
                        </div>
                        <div style="font-size: 13px; color: #1e293b; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($sub['subject']); ?></div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; display: flex; gap: 8px; align-items: center;">
                            <span style="background:#eef2ff; color:#6366f1; padding: 1px 6px; border-radius: 4px; font-weight:700;"><?php echo htmlspecialchars($sub['category_name'] ?: 'General'); ?></span>
                            <?php if ($sub['featured_image']): ?>
                                <span style="color:#059669; font-weight:700;"><i data-feather="image" style="width:11px; height:11px; vertical-align:middle;"></i> Photo attached</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 5px; flex-shrink: 0;">
                        <a href="?view=<?php echo $sub['id']; ?>&filter=<?php echo $filter; ?>" style="background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none;">Review</a>
                        <a href="?delete=<?php echo $sub['id']; ?>" onclick="return confirm('Permanently delete this submission?')" style="background: #fef2f2; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #ef4444; text-decoration: none;">
                            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Details block -->
    <?php if ($view_sub): ?>
    <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: sticky; top: 20px;">
        <div style="padding: 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Submission Review</h3>
            <a href="?filter=<?php echo $filter; ?>" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 600;">
                <i data-feather="x" style="width: 16px;"></i> Close
            </a>
        </div>
        <div style="padding: 25px;">
            <!-- Sender Details -->
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800;">
                    <?php echo strtoupper(substr($view_sub['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($view_sub['name']); ?></div>
                    <a href="mailto:<?php echo htmlspecialchars($view_sub['email']); ?>" style="color: var(--primary); font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($view_sub['email']); ?></a>
                    <?php if ($view_sub['phone']): ?>
                        <div style="font-size: 13px; color: #64748b; margin-top: 3px;">📞 <?php echo htmlspecialchars($view_sub['phone']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Subject/Title -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;">Submitted Article Title</label>
                <div style="font-size: 16px; font-weight: 700; color: #0f172a; line-height:1.4;"><?php echo htmlspecialchars($view_sub['subject']); ?></div>
            </div>

            <!-- Category -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;">Category Choice</label>
                <span style="background: #eef2ff; color: #6366f1; padding: 5px 14px; border-radius: 8px; font-size: 13px; font-weight: 700;"><?php echo htmlspecialchars($view_sub['category_name'] ?: 'General'); ?></span>
            </div>

            <!-- Photo Preview -->
            <?php if ($view_sub['featured_image']): ?>
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 8px;">Uploaded Featured Photo</label>
                    <div style="width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1;">
                        <img src="<?php echo BASE_URL . 'assets/images/posts/' . $view_sub['featured_image']; ?>" style="width: 100%; max-height: 240px; object-fit: cover; display:block;">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Article Content -->
            <div style="margin-bottom: 25px;">
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Article Content</label>
                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; font-size: 15px; line-height: 1.8; color: #334155; border: 1px solid #f1f5f9; white-space: pre-line;">
                    <?php echo htmlspecialchars($view_sub['message']); ?>
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display: flex; gap: 10px;">
                <?php if ($view_sub['status'] === 'pending'): ?>
                    <a href="?approve=<?php echo $view_sub['id']; ?>" class="btn" style="flex: 1; padding: 12px; background: #10b981; color: white; border-radius: 10px; text-align: center; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i data-feather="check" style="width: 16px;"></i> Approve &amp; Publish
                    </a>
                    <a href="?reject=<?php echo $view_sub['id']; ?>" class="btn" style="padding: 12px 18px; background: #f59e0b; color: white; border-radius: 10px; text-align: center; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="slash" style="width: 16px;"></i> Reject
                    </a>
                <?php else: ?>
                    <div style="flex: 1; text-align: center; padding: 10px; border-radius: 10px; font-weight: 700; <?php echo $view_sub['status'] === 'approved' ? 'background: #ecfdf5; color: #059669;' : 'background: #fffbeb; color: #d97706;'; ?>">
                        Status: <?php echo ucfirst($view_sub['status']); ?>
                    </div>
                <?php endif; ?>
                <a href="?delete=<?php echo $view_sub['id']; ?>" onclick="return confirm('Permanently delete this submission?')" style="padding: 12px 18px; background: #fef2f2; color: #ef4444; border-radius: 10px; font-weight: 700; text-decoration: none; display: flex; align-items: center;">
                    <i data-feather="trash-2" style="width: 16px;"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
