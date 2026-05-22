<?php
$page_title = "Timeline Management";
include 'includes/header.php';

// Handle Add Item
if (isset($_POST['add_timeline'])) {
    $event_name = clean($_POST['event_name']);
    $event_date = clean($_POST['event_date']);
    $event_time = clean($_POST['event_time']);
    $description = clean($_POST['description']);
    $status_color = clean($_POST['status_color']);

    if (empty($event_name) || empty($event_date) || empty($event_time) || empty($description)) {
        $_SESSION['flash_msg'] = "All fields are required.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO timeline (event_name, event_date, event_time, description, status_color) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$event_name, $event_date, $event_time, $description, $status_color]);
            redirect('admin/timeline.php', 'Event added successfully!');
        } catch (PDOException $e) {
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Item
if (isset($_POST['update_timeline'])) {
    $id = $_POST['id'];
    $event_name = clean($_POST['event_name']);
    $event_date = clean($_POST['event_date']);
    $event_time = clean($_POST['event_time']);
    $description = clean($_POST['description']);
    $status_color = clean($_POST['status_color']);

    try {
        $stmt = $pdo->prepare("UPDATE timeline SET event_name = ?, event_date = ?, event_time = ?, description = ?, status_color = ? WHERE id = ?");
        $stmt->execute([$event_name, $event_date, $event_time, $description, $status_color, $id]);
        redirect('admin/timeline.php', 'Event updated successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete Item
if (isset($_GET['delete'])) {
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot delete data.', 'danger');
        exit;
    }
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

<div style="display:block;">
    <!-- List -->
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow); width: 100%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Manage Events</h3>
            <button onclick="openTimelineModal()" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px; font-weight: 600;"><i data-feather="plus" style="width: 16px;"></i> Add New Event</button>
        </div>
        <div class="table-responsive"><table class="content-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Date & Time</th>
                    <th>Event Info</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $now_date = date('Y-m-d');
                $now_time = date('H:i');
                foreach ($timeline as $item): 
                    // Automatic Status Logic
                    $status_text = 'Upcoming';
                    $status_color = '#f59e0b';
                    
                    if ($item['event_date'] < $now_date || ($item['event_date'] == $now_date && $item['event_time'] < $now_time)) {
                        $status_text = 'Completed';
                        $status_color = '#10b981';
                    } elseif ($item['event_date'] == $now_date && $item['event_time'] == $now_time) {
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
                    <td style="width: 150px;">
                        <strong><?php echo date("M d, Y", strtotime($item['event_date'])); ?></strong><br>
                        <span style="font-size: 12px; color: #64748b;"><?php echo date("h:i A", strtotime($item['event_time'])); ?></span>
                    </td>
                    <td style="font-size: 14px; line-height: 1.6; color: #475569;">
                        <strong style="color: #0f172a; display: block; margin-bottom: 3px;"><?php echo htmlspecialchars($item['event_name']); ?></strong>
                        <?php echo htmlspecialchars($item['description']); ?>
                    </td>
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
        </table></div>
    </div>
</div>


<!-- Add/Edit Event Modal -->
<div class="modal-overlay" id="timelineModal" style="<?php echo (isset($_POST['add_timeline']) && isset($_SESSION['flash_type']) && $_SESSION['flash_type'] == 'danger') || $edit_item ? 'display:flex;' : 'display:none;'; ?>">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><?php echo $edit_item ? 'Edit Event' : 'Add New Event'; ?></h3>
            <?php if ($edit_item): ?>
                <a href="timeline.php" style="background:none;border:none;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;"><i data-feather="x"></i></a>
            <?php else: ?>
                <button onclick="closeTimelineModal()" style="background:none;border:none;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;"><i data-feather="x"></i></button>
            <?php endif; ?>
        </div>
        <div style="padding: 25px;">
            <form action="timeline.php" method="POST">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Event Name</label>
                    <input type="text" name="event_name" class="form-control" required value="<?php echo $edit_item ? htmlspecialchars($edit_item['event_name']) : ''; ?>" placeholder="E.g. Election Results">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Event Date</label>
                    <input type="date" name="event_date" class="form-control" required value="<?php echo $edit_item ? $edit_item['event_date'] : date('Y-m-d'); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Event Time</label>
                    <input type="time" name="event_time" class="form-control" required value="<?php echo $edit_item ? $edit_item['event_time'] : date('H:i'); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom: 5px; font-weight: 700; font-size: 13px;">Description</label>
                    <textarea name="description" class="form-control" rows="4" required placeholder="What happened?"><?php echo $edit_item ? htmlspecialchars($edit_item['description']) : ''; ?></textarea>
                </div>
                
                <input type="hidden" name="status_color" value="#6366f1">

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="<?php echo $edit_item ? 'update_timeline' : 'add_timeline'; ?>" class="btn btn-primary" style="flex: 1; justify-content: center; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="upload-cloud" style="width:16px;"></i>
                        <?php echo $edit_item ? 'Update Event' : 'Post Event'; ?>
                    </button>
                    <?php if ($edit_item): ?>
                        <a href="timeline.php" class="btn" style="background: #f1f5f9; color: #475569; display: flex; align-items: center; justify-content: center; text-decoration: none;">Cancel</a>
                    <?php else: ?>
                        <button type="button" onclick="closeTimelineModal()" class="btn" style="background: #f1f5f9; color: #475569;">Cancel</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTimelineModal() {
    document.getElementById('timelineModal').style.display = 'flex';
}
function closeTimelineModal() {
    document.getElementById('timelineModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
