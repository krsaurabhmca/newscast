<?php
$page_title = "Comments Moderation";
include 'includes/header.php';

// Handle Action requests (Approve, Delete, Spam)
if (isset($_GET['action']) && isset($_GET['id'])) {
    if (is_demo_account()) {
        redirect('admin/comments.php', 'Action restricted: Demo accounts cannot modify data.', 'danger');
        exit;
    }

    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_msg'] = "Comment approved successfully!";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'spam') {
            $stmt = $pdo->prepare("UPDATE comments SET status = 'spam' WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_msg'] = "Comment marked as spam.";
            $_SESSION['flash_type'] = "warning";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash_msg'] = "Comment deleted permanently.";
            $_SESSION['flash_type'] = "danger";
        }
        redirect('admin/comments.php');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Active Tab Filter (default: pending)
$tab = $_GET['tab'] ?? 'pending';
if (!in_array($tab, ['pending', 'approved', 'spam'])) {
    $tab = 'pending';
}

// Fetch Comments based on tab
try {
    $stmt = $pdo->prepare("SELECT c.*, p.title as post_title, p.slug as post_slug 
                           FROM comments c 
                           JOIN posts p ON c.post_id = p.id 
                           WHERE c.status = ? 
                           ORDER BY c.created_at DESC");
    $stmt->execute([$tab]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    $comments = [];
}

// Fetch counts for badges
$pending_count = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
$approved_count = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
$spam_count = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn();
?>

<!-- Tab Selector Navigation -->
<div style="display: flex; gap: 15px; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
    <a href="?tab=pending" style="text-decoration: none; padding: 8px 16px; font-weight: 700; font-size: 14px; border-radius: 8px; transition: 0.2s; display: flex; align-items: center; gap: 8px; <?php echo ($tab === 'pending') ? 'background: var(--primary); color: white;' : 'color: #64748b; background: #f8fafc;'; ?>">
        <i data-feather="clock" style="width: 16px;"></i>
        Pending Reviews
        <span style="font-size: 11px; padding: 2px 6px; border-radius: 20px; font-weight: 800; <?php echo ($tab === 'pending') ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: #cbd5e1; color: #475569;'; ?>"><?php echo $pending_count; ?></span>
    </a>
    <a href="?tab=approved" style="text-decoration: none; padding: 8px 16px; font-weight: 700; font-size: 14px; border-radius: 8px; transition: 0.2s; display: flex; align-items: center; gap: 8px; <?php echo ($tab === 'approved') ? 'background: #10b981; color: white;' : 'color: #64748b; background: #f8fafc;'; ?>">
        <i data-feather="check-circle" style="width: 16px;"></i>
        Approved Comments
        <span style="font-size: 11px; padding: 2px 6px; border-radius: 20px; font-weight: 800; <?php echo ($tab === 'approved') ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: #cbd5e1; color: #475569;'; ?>"><?php echo $approved_count; ?></span>
    </a>
    <a href="?tab=spam" style="text-decoration: none; padding: 8px 16px; font-weight: 700; font-size: 14px; border-radius: 8px; transition: 0.2s; display: flex; align-items: center; gap: 8px; <?php echo ($tab === 'spam') ? 'background: #ef4444; color: white;' : 'color: #64748b; background: #f8fafc;'; ?>">
        <i data-feather="slash" style="width: 16px;"></i>
        Spam List
        <span style="font-size: 11px; padding: 2px 6px; border-radius: 20px; font-weight: 800; <?php echo ($tab === 'spam') ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: #cbd5e1; color: #475569;'; ?>"><?php echo $spam_count; ?></span>
    </a>
</div>

<!-- Main Comments Container -->
<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <h3 style="margin-top: 0; margin-bottom: 20px;">Reviewing: <?php echo ucfirst($tab); ?> comments</h3>

    <?php if (empty($comments)): ?>
        <div style="text-align: center; padding: 80px 20px; color: #94a3b8;">
            <i data-feather="message-square" style="width: 60px; height: 60px; color: #e2e8f0; margin-bottom: 15px;"></i>
            <h4>No comments found in this section.</h4>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="content-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">User & Email</th>
                        <th>Comment Description</th>
                        <th style="width: 250px;">On Article</th>
                        <th style="width: 150px;">Date Posted</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $cmt): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($cmt['user_name']); ?></strong>
                                <div style="font-size: 12px; color: #64748b; margin-top: 4px; font-family: monospace;"><?php echo htmlspecialchars($cmt['user_email']); ?></div>
                            </td>
                            <td style="max-width: 400px; white-space: normal; line-height: 1.5; font-size:13px; font-weight: 500; color: #475569;">
                                <?php echo nl2br(htmlspecialchars($cmt['comment_text'])); ?>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>article/<?php echo $cmt['post_slug']; ?>" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 700; font-size: 13px;">
                                    <?php echo htmlspecialchars($cmt['post_title']); ?>
                                </a>
                            </td>
                            <td style="font-size: 13px; color: #64748b; font-weight: 600;">
                                <?php echo date('M d, Y h:i A', strtotime($cmt['created_at'])); ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <?php if ($tab !== 'approved'): ?>
                                        <a href="?action=approve&id=<?php echo $cmt['id']; ?>" class="btn" style="background: #ecfdf5; color: #059669; padding: 6px 12px; font-size: 11px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" title="Approve">
                                            <i data-feather="check" style="width: 12px;"></i> Approve
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($tab !== 'spam'): ?>
                                        <a href="?action=spam&id=<?php echo $cmt['id']; ?>" class="btn" style="background: #fffbeb; color: #d97706; padding: 6px; border-radius: 6px; display: inline-flex; align-items: center;" title="Mark as Spam">
                                            <i data-feather="slash" style="width: 14px;"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="?action=delete&id=<?php echo $cmt['id']; ?>" class="btn btn-danger" style="background: #fef2f2; color: #ef4444; border: 1px solid transparent; padding: 6px; border-radius: 6px; display: inline-flex; align-items: center;" onclick="return confirm('Permanently delete this comment?')" title="Delete">
                                        <i data-feather="trash-2" style="width: 14px;"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
