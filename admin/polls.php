<?php
$page_title = "Manage Polls";
include 'includes/header.php';

// Handle Add Poll
if (isset($_POST['add_poll'])) {
    $question = clean($_POST['question']);
    $status = clean($_POST['status']);
    $options = $_POST['options'] ?? [];

    // Filter empty options
    $valid_options = array_filter(array_map('clean', $options), function($val) {
        return !empty($val);
    });

    $starts_at = !empty($_POST['starts_at']) ? $_POST['starts_at'] : null;
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;

    if (empty($question)) {
        $_SESSION['flash_msg'] = "Poll question is required.";
        $_SESSION['flash_type'] = "danger";
    } elseif (count($valid_options) < 2) {
        $_SESSION['flash_msg'] = "Please provide at least 2 options.";
        $_SESSION['flash_type'] = "danger";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO polls (question, status, starts_at, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$question, $status, $starts_at, $expires_at]);
            $poll_id = $pdo->lastInsertId();

            $opt_stmt = $pdo->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
            foreach ($valid_options as $opt) {
                $opt_stmt->execute([$poll_id, $opt]);
            }
            $pdo->commit();
            redirect('admin/polls.php', 'Poll created successfully!');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
            $_SESSION['flash_type'] = "danger";
        }
    }
}

// Handle Update Poll Status
if (isset($_POST['update_status'])) {
    $id = $_POST['poll_id'];
    $status = clean($_POST['status']);
    try {
        $stmt = $pdo->prepare("UPDATE polls SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        redirect('admin/polls.php', 'Poll status updated!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Handle Delete Poll
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM polls WHERE id = ?");
        $stmt->execute([$id]);
        redirect('admin/polls.php', 'Poll deleted successfully!');
    } catch (PDOException $e) {
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

// Fetch All Polls
$polls = $pdo->query("SELECT * FROM polls ORDER BY created_at DESC")->fetchAll();
?>

<!-- Modal Overlay -->
<div id="createPollModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <!-- Add Poll Form inside Modal -->
    <div style="background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Create New Poll</h3>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn" style="background: #9333ea; color: white; font-size: 12px; padding: 6px 12px; display: flex; align-items: center; gap: 5px;" onclick="suggestAIPoll(this)">
                    <i data-feather="cpu" style="width: 14px;"></i> AI Suggest (Hindi)
                </button>
                <button type="button" onclick="document.getElementById('createPollModal').style.display='none'" style="background: none; border: none; cursor: pointer; color: #64748b;">
                    <i data-feather="x"></i>
                </button>
            </div>
        </div>
        <form action="" method="POST">
            <div class="form-group">
                <label>Poll Question</label>
                <textarea name="question" class="form-control" rows="3" required placeholder="What is your question?"></textarea>
            </div>

            <div class="form-group">
                <label>Options (Minimum 2)</label>
                <div id="options-container">
                    <input type="text" name="options[]" class="form-control" style="margin-bottom: 8px;" placeholder="Option 1" required>
                    <input type="text" name="options[]" class="form-control" style="margin-bottom: 8px;" placeholder="Option 2" required>
                    <input type="text" name="options[]" class="form-control" style="margin-bottom: 8px;" placeholder="Option 3">
                    <input type="text" name="options[]" class="form-control" style="margin-bottom: 8px;" placeholder="Option 4">
                </div>
                <button type="button" class="btn" style="background: #f1f5f9; color: #475569; font-size: 12px; padding: 5px 10px; margin-top: 5px;" onclick="addOption()">+ Add Another Option</button>
            </div>

            <div class="form-group">
                <label>Start Time (Optional)</label>
                <input type="datetime-local" name="starts_at" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Expiry Time (Optional)</label>
                <input type="datetime-local" name="expires_at" class="form-control">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active (Voting Open)</option>
                    <option value="closed">Closed (Voting Closed)</option>
                </select>
            </div>
            
            <button type="submit" name="add_poll" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Save Poll
            </button>
        </form>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: var(--shadow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0;">Manage Polls</h3>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('createPollModal').style.display='flex'" style="display: flex; align-items: center; gap: 5px;">
            <i data-feather="plus-circle" style="width: 16px;"></i> Create Poll
        </button>
    </div>
        
        <?php if(empty($polls)): ?>
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i data-feather="pie-chart" style="width: 48px; height: 48px; opacity: 0.5; margin-bottom: 10px;"></i>
                <p>No polls created yet.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($polls as $poll): 
                    $options = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id ASC");
                    $options->execute([$poll['id']]);
                    $opts = $options->fetchAll();
                    
                    $total_votes = 0;
                    foreach ($opts as $o) $total_votes += $o['votes_count'];
                ?>
                <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: white;">
                    <!-- Accordion Header -->
                    <div style="padding: 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background 0.2s;" onclick="toggleAccordion('poll-<?php echo $poll['id']; ?>')" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: #0f172a;"><?php echo htmlspecialchars($poll['question']); ?></h4>
                            <div style="font-size: 12px; color: #64748b; display: flex; gap: 15px; align-items: center;">
                                <span><i data-feather="calendar" style="width: 12px; vertical-align: middle;"></i> <?php echo date('M d, Y', strtotime($poll['created_at'])); ?></span>
                                <span><i data-feather="users" style="width: 12px; vertical-align: middle;"></i> <?php echo $total_votes; ?> Total Votes</span>
                                <span class="badge" style="background: <?php echo $poll['status'] == 'active' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $poll['status'] == 'active' ? '#065f46' : '#991b1b'; ?>; font-size: 10px;">
                                    <?php echo ucfirst($poll['status']); ?>
                                </span>
                            </div>
                            <?php if($poll['starts_at'] || $poll['expires_at']): ?>
                            <div style="font-size: 11px; color: #64748b; display: flex; gap: 15px; align-items: center; margin-top: 8px;">
                                <?php if($poll['starts_at']): ?>
                                    <span style="color:#d97706; background:#fef3c7; padding:2px 6px; border-radius:4px;"><i data-feather="clock" style="width: 10px; vertical-align: middle;"></i> Starts: <?php echo date('M d, Y H:i', strtotime($poll['starts_at'])); ?></span>
                                <?php endif; ?>
                                <?php if($poll['expires_at']): ?>
                                    <span style="color:#dc2626; background:#fee2e2; padding:2px 6px; border-radius:4px;"><i data-feather="clock" style="width: 10px; vertical-align: middle;"></i> Expires: <?php echo date('M d, Y H:i', strtotime($poll['expires_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <i data-feather="chevron-down" id="icon-poll-<?php echo $poll['id']; ?>" style="color: #94a3b8; transition: transform 0.3s;"></i>
                    </div>
                    
                    <!-- Accordion Body -->
                    <div id="poll-<?php echo $poll['id']; ?>" style="display: none; padding: 20px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px; justify-content: flex-end;">
                            <form action="" method="POST" style="margin: 0; display: flex; gap: 5px;">
                                <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                                <select name="status" class="form-control" style="padding: 4px 8px; font-size: 12px; height: auto;" onchange="this.form.submit()">
                                    <option value="active" <?php echo $poll['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="closed" <?php echo $poll['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            <a href="../poll.php?id=<?php echo $poll['id']; ?>" target="_blank" class="btn" style="padding: 6px; background: #eff6ff; color: #3b82f6; border-radius: 8px;" title="View Poll">
                                <i data-feather="external-link" style="width: 14px; margin: 0;"></i>
                            </a>
                            <a href="?delete=<?php echo $poll['id']; ?>" class="btn btn-danger" style="padding: 6px; background: #fef2f2; color: #ef4444; border: 1px solid transparent; border-radius: 8px;" onclick="return confirm('Are you sure you want to delete this poll? All votes will be lost.')" title="Delete Poll">
                                <i data-feather="trash-2" style="width: 14px; margin: 0;"></i>
                            </a>
                        </div>
                        
                        <div style="background: white; border-radius: 8px; padding: 15px; border: 1px solid #e2e8f0;">
                            <?php foreach($opts as $opt): 
                                $percent = $total_votes > 0 ? round(($opt['votes_count'] / $total_votes) * 100) : 0;
                            ?>
                            <div style="margin-bottom: 10px; last-child { margin-bottom: 0; }">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px; color: #334155;">
                                    <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                    <span style="font-weight: 600;"><?php echo $opt['votes_count']; ?> votes (<?php echo $percent; ?>%)</span>
                                </div>
                                <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="background: var(--primary); width: <?php echo $percent; ?>%; height: 100%; border-radius: 4px; transition: width 0.5s ease;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
</div>

<script>
function toggleAccordion(id) {
    const content = document.getElementById(id);
    const icon = document.getElementById('icon-' + id);
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

let optCount = 4;
function addOption() {
    optCount++;
    const container = document.getElementById('options-container');
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'options[]';
    input.className = 'form-control';
    input.style.marginBottom = '8px';
    input.placeholder = 'Option ' + optCount;
    container.appendChild(input);
}

async function suggestAIPoll(btn) {
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i data-feather="loader" style="width: 14px;"></i> Wait...';
    btn.disabled = true;

    try {
        const response = await fetch('../api/api_ai_poll.php');
        const data = await response.json();
        
        if (data.success && data.data) {
            document.querySelector('textarea[name="question"]').value = data.data.question;
            
            const container = document.getElementById('options-container');
            container.innerHTML = '';
            optCount = 0;
            
            data.data.options.forEach(opt => {
                optCount++;
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'options[]';
                input.className = 'form-control';
                input.style.marginBottom = '8px';
                input.value = opt;
                container.appendChild(input);
            });
        } else {
            alert(data.message || 'Failed to generate poll.');
        }
    } catch (e) {
        alert('An error occurred. Check if Groq API is reachable.');
    }
    
    btn.innerHTML = originalText;
    btn.disabled = false;
    feather.replace();
}
</script>

<?php include 'includes/footer.php'; ?>
