<?php
$page_title = "Timeline Management";
include 'includes/header.php';

// Handle Add Item
if (isset($_POST['add_timeline'])) {
    $event_time = clean($_POST['event_time']);
    $description = clean($_POST['description']);
    $status_color = clean($_POST['status_color']);

    if (empty($event_time) || empty($description)) {
        $_SESSION['flash_msg'] = "Time and description are required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO timeline (event_time, description, status_color) VALUES (?, ?, ?)");
            $stmt->execute([$event_time, $description, $status_color]);
            redirect('admin/timeline.php', 'Timeline item added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Item
if (isset($_POST['update_timeline'])) {
    $id = $_POST['id'];
    $event_time = clean($_POST['event_time']);
    $description = clean($_POST['description']);
    $status_color = clean($_POST['status_color']);

    try {
        $stmt = $pdo->prepare("UPDATE timeline SET event_time = ?, description = ?, status_color = ? WHERE id = ?");
        $stmt->execute([$event_time, $description, $status_color, $id]);
        redirect('admin/timeline.php', 'Timeline item updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM timeline WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/timeline.php', 'Item deleted successfully!');
}

// Fetch Item for Editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM timeline WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}

// Fetch All Items
$timeline = $pdo->query("SELECT * FROM timeline ORDER BY created_at DESC")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 30px;">
    <!-- Form (Add/Edit) -->
    <div style="background: white; padding: 25px; border-radius: 12px; height: fit-content; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;"><?php echo $edit_item ? 'Edit Timeline Item' : 'Add New Timeline Item'; ?></h3>
        <form action="" method="POST">
            <?php if ($edit_item): ?>
                <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
            <?php endif; ?>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Event Time</label>
                <input type="time" name="event_time" class="form-control" required value="<?php echo $edit_item ? $edit_item['event_time'] : date('H:i'); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Update Description</label>
                <textarea name="description" class="form-control" rows="4" required placeholder="What happened?"><?php echo $edit_item ? $edit_item['description'] : ''; ?></textarea>
            </div>
            
            <input type="hidden" name="status_color" value="#6366f1"> <!-- Hidden default, logic moves to frontend -->

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="<?php echo $edit_item ? 'update_timeline' : 'add_timeline'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="upload-cloud" style="width:16px;"></i>
                    <?php echo $edit_item ? 'Update Item' : 'Post to Timeline'; ?>
                </button>
                <?php if ($edit_item): ?>
                    <a href="timeline.php" class="btn" style="background: #f1f5f9; color: #444; display: flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- List -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
        <h3 style="margin-bottom: 20px;">Today's Live Timeline</h3>
        <table class="content-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $now = date('H:i');
                foreach ($timeline as $item): 
                    // Automatic Status Logic for Admin List
                    $status_text = 'Upcoming';
                    $status_color = '#f59e0b';
                    
                    if ($item['event_time'] < $now) {
                        $status_text = 'Completed';
                        $status_color = '#10b981';
                    } elseif ($item['event_time'] == $now) {
                        $status_text = 'Ongoing';
                        $status_color = '#ef4444';
                    }
                ?>
                <tr>
                    <td style="width: 100px;">
                        <span class="badge" style="background: <?php echo $status_color; ?>15; color: <?php echo $status_color; ?>; border: 1px solid <?php echo $status_color; ?>44;">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td style="width: 100px;"><strong><?php echo date("h:i A", strtotime($item['event_time'])); ?></strong></td>
                    <td style="font-size: 14px; line-height: 1.6; color: #475569;"><?php echo $item['description']; ?></td>
                    <td style="width: 140px;">
                        <div style="display: flex; gap: 8px;">
                            <a href="?edit=<?php echo $item['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px; background: #f1f5f9; color: #444; display: flex; align-items: center; gap: 5px;">
                                <i data-feather="edit-2" style="width: 12px;"></i> Edit
                            </a>
                            <a href="?delete=<?php echo $item['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; display: flex; align-items: center; gap: 5px;" onclick="return confirm('Delete this timeline update?')">
                                <i data-feather="trash-2" style="width: 12px;"></i> Del
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($timeline)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 60px; color: #94a3b8;">
                        <i data-feather="clock" style="width: 48px; height: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p>No timeline updates yet today. Start by adding one!</p>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
