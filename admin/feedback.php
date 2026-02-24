<?php
$page_title = "Feedback & Messages";
include 'includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    redirect('admin/feedback.php', 'Message deleted.');
}

// Handle mark as read
if (isset($_GET['read'])) {
    $stmt = $pdo->prepare("UPDATE feedback SET status = 'read' WHERE id = ?");
    $stmt->execute([(int)$_GET['read']]);
    // redirect back to same page (possibly with a modal open)
    header("Location: feedback.php");
    exit;
}

// Stats
$total   = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$unread  = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
$contact = $pdo->query("SELECT COUNT(*) FROM feedback WHERE subject = 'Web Contact Form'")->fetchColumn();

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = match($filter) {
    'unread'  => "WHERE status = 'new'",
    'contact' => "WHERE subject = 'Web Contact Form'",
    'feedback'=> "WHERE subject != 'Web Contact Form'",
    default   => ""
};

$messages = $pdo->query("SELECT * FROM feedback $where ORDER BY created_at DESC")->fetchAll();

// Viewing a single message
$view_msg = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM feedback WHERE id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $view_msg = $stmt->fetch();
    if ($view_msg && $view_msg['status'] === 'new') {
        $pdo->prepare("UPDATE feedback SET status = 'read' WHERE id = ?")->execute([$view_msg['id']]);
    }
}
?>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(99,102,241,0.1); color: var(--primary); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="inbox" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Messages</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $total; ?></div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(239,68,68,0.1); color: #ef4444; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="mail" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Unread</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $unread; ?></div>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="background: rgba(16,185,129,0.1); color: #10b981; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="phone" style="width: 22px;"></i>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Contact Form</div>
                <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?php echo $contact; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs + Message Viewer Layout -->
<div style="display: grid; grid-template-columns: 1fr <?php echo $view_msg ? '1fr' : ''; ?>; gap: 25px; align-items: start;">

    <!-- Inbox list -->
    <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0;">Inbox</h3>
            <div style="display: flex; gap: 8px;">
                <?php foreach (['all' => 'All', 'unread' => 'Unread', 'contact' => 'Contact Form', 'feedback' => 'Feedback'] as $key => $label): ?>
                    <a href="?filter=<?php echo $key; ?>" style="padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; <?php echo $filter === $key ? 'background: var(--primary); color: white;' : 'background: #f1f5f9; color: #475569;'; ?>"><?php echo $label; ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($messages)): ?>
            <div style="padding: 60px; text-align: center; color: #94a3b8;">
                <i data-feather="inbox" style="width: 40px; height: 40px; margin-bottom: 15px; display: block; margin: 0 auto 15px;"></i>
                <p style="font-weight: 600;">No messages here</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php $is_unread = $msg['status'] === 'new'; ?>
                <div style="padding: 18px 25px; border-bottom: 1px solid #f8fafc; display: flex; gap: 15px; align-items: start; <?php echo $is_unread ? 'background: #fafbff;' : ''; ?> <?php echo (isset($_GET['view']) && $_GET['view'] == $msg['id']) ? 'background: #eef2ff; border-left: 3px solid var(--primary);' : ''; ?>">
                    <!-- Avatar -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $is_unread ? 'var(--primary)' : '#e2e8f0'; ?>; display: flex; align-items: center; justify-content: center; color: <?php echo $is_unread ? '#fff' : '#64748b'; ?>; font-weight: 800; font-size: 16px; flex-shrink: 0;">
                        <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-weight: <?php echo $is_unread ? '800' : '600'; ?>; color: #0f172a; font-size: 14px;"><?php echo htmlspecialchars($msg['name']); ?></span>
                            <span style="font-size: 11px; color: #94a3b8; white-space: nowrap;"><?php echo date('d M, g:i A', strtotime($msg['created_at'])); ?></span>
                        </div>
                        <div style="font-size: 12px; color: #6366f1; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($msg['subject']); ?></div>
                        <div style="font-size: 13px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?>...</div>
                    </div>
                    <div style="display: flex; gap: 5px; flex-shrink: 0;">
                        <a href="?view=<?php echo $msg['id']; ?>&filter=<?php echo $filter; ?>" style="background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #475569; text-decoration: none;">View</a>
                        <a href="?delete=<?php echo $msg['id']; ?>" onclick="return confirm('Delete this message?')" style="background: #fef2f2; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #ef4444; text-decoration: none;">
                            <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Message Detail Panel -->
    <?php if ($view_msg): ?>
    <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: sticky; top: 20px;">
        <div style="padding: 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">Message Details</h3>
            <a href="?filter=<?php echo $filter; ?>" style="color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 600;">
                <i data-feather="x" style="width: 16px;"></i> Close
            </a>
        </div>
        <div style="padding: 25px;">
            <!-- Sender Info -->
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 55px; height: 55px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800;">
                    <?php echo strtoupper(substr($view_msg['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-size: 18px; font-weight: 800; color: #0f172a;"><?php echo htmlspecialchars($view_msg['name']); ?></div>
                    <a href="mailto:<?php echo htmlspecialchars($view_msg['email']); ?>" style="color: var(--primary); font-size: 14px; font-weight: 600;"><?php echo htmlspecialchars($view_msg['email']); ?></a>
                    <?php if ($view_msg['phone']): ?>
                        <div style="font-size: 13px; color: #64748b; margin-top: 3px;">📞 <?php echo htmlspecialchars($view_msg['phone']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Subject -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;">Subject</label>
                <span style="background: #eef2ff; color: #6366f1; padding: 5px 14px; border-radius: 8px; font-size: 13px; font-weight: 700;"><?php echo htmlspecialchars($view_msg['subject']); ?></span>
            </div>

            <!-- When -->
            <div style="margin-bottom: 20px;">
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;">Received</label>
                <span style="font-size: 14px; color: #475569; font-weight: 600;"><?php echo date('D, d M Y \a\t g:i A', strtotime($view_msg['created_at'])); ?></span>
            </div>

            <!-- Message Body -->
            <div>
                <label style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 10px;">Message</label>
                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; font-size: 15px; line-height: 1.8; color: #334155; border: 1px solid #f1f5f9;">
                    <?php echo nl2br(htmlspecialchars($view_msg['message'])); ?>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <a href="mailto:<?php echo htmlspecialchars($view_msg['email']); ?>?subject=Re: <?php echo urlencode($view_msg['subject']); ?>" style="flex: 1; padding: 12px; background: var(--primary); color: white; border-radius: 10px; text-align: center; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-feather="send" style="width: 16px;"></i> Reply via Email
                </a>
                <a href="?delete=<?php echo $view_msg['id']; ?>" onclick="return confirm('Delete this message?')" style="padding: 12px 18px; background: #fef2f2; color: #ef4444; border-radius: 10px; font-weight: 700; text-decoration: none; display: flex; align-items: center;">
                    <i data-feather="trash-2" style="width: 16px;"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
