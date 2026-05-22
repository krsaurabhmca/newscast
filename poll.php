<?php
$page_title = "Public Poll";
require_once 'includes/config.php';
require_once 'includes/functions.php';

$slug = isset($_GET['slug']) ? clean($_GET['slug']) : '';

if (!$slug) {
    $legacy_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($legacy_id) {
        $stmt = $pdo->prepare("SELECT slug FROM polls WHERE id = ?");
        $stmt->execute([$legacy_id]);
        if ($res = $stmt->fetch()) {
            redirect('poll/' . $res['slug']);
        }
    }
    redirect('index.php', 'Invalid Poll.', 'danger');
}

// Fetch Poll
$stmt = $pdo->prepare("SELECT * FROM polls WHERE slug = ?");
$stmt->execute([$slug]);
$poll = $stmt->fetch();
$poll_id = $poll ? $poll['id'] : 0;

if (!$poll) {
    redirect('index.php', 'Poll not found.', 'danger');
}

$page_title = htmlspecialchars($poll['question']);

// Set Open Graph tags for sharing
$og_title = $page_title;
$og_description = "Vote on this poll and share your opinion!";
$og_url = BASE_URL . "poll/" . $poll['slug'];
$og_image = get_setting('og_image_url') ?: BASE_URL . "assets/images/share.jpg";

include 'includes/public_header.php';

// Fetch Options
$opt_stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id ASC");
$opt_stmt->execute([$poll_id]);
$options = $opt_stmt->fetchAll();

// Check if already voted
$has_voted = false;
$browser_id = $_COOKIE['voter_id'] ?? '';
$ip_address = $_SERVER['REMOTE_ADDR'];

$check = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND (browser_id = ? OR ip_address = ?)");
$check->execute([$poll_id, $browser_id, $ip_address]);
if ($check->rowCount() > 0) {
    $has_voted = true;
}

$total_votes = 0;
foreach ($options as $opt) {
    $total_votes += $opt['votes_count'];
}

$is_closed = ($poll['status'] === 'closed');
$is_upcoming = false;
$is_expired = false;
$now_time = time();
$starts_time = $poll['starts_at'] ? strtotime($poll['starts_at']) : 0;
$expires_time = $poll['expires_at'] ? strtotime($poll['expires_at']) : 0;

if (!$is_closed) {
    if ($starts_time > 0 && $starts_time > $now_time) {
        $is_upcoming = true;
    }
    if ($expires_time > 0 && $expires_time < $now_time) {
        $is_expired = true;
        $is_closed = true;
    }
}

$show_results = $has_voted || $is_closed;

// URL Encoding for Share Links
$share_url = urlencode($og_url);
$share_text = urlencode($poll['question']);
?>

<style>
.poll-container {
    max-width: 600px;
    margin: 40px auto;
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.poll-question {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 20px;
    line-height: 1.3;
}
.poll-meta {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 25px;
    display: flex;
    gap: 15px;
    align-items: center;
}
.poll-option-label {
    display: block;
    padding: 15px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 15px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
    font-weight: 600;
    color: #334155;
    position: relative;
    overflow: hidden;
}
.poll-option-label:hover {
    border-color: var(--primary);
    background: #f8fafc;
}
.poll-option-radio {
    margin-right: 10px;
}
.poll-btn {
    width: 100%;
    padding: 15px;
    background: var(--primary);
    color: white;
    font-size: 16px;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.2s;
}
.poll-btn:hover {
    background: #4f46e5;
}
.poll-btn:disabled {
    background: #94a3b8;
    cursor: not-allowed;
}

/* Results Styles */
.result-item {
    margin-bottom: 20px;
}
.result-text {
    display: flex;
    justify-content: space-between;
    font-size: 15px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}
.result-bar-bg {
    background: #e2e8f0;
    height: 12px;
    border-radius: 6px;
    overflow: hidden;
}
.result-bar-fill {
    background: var(--primary);
    height: 100%;
    border-radius: 6px;
    width: 0%;
    transition: width 1s ease-in-out;
}
.share-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}
.share-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}
.share-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: transform 0.2s;
}
.share-btn:hover {
    transform: scale(1.1);
}
.btn-whatsapp { background: #25d366; }
.btn-facebook { background: #1877f2; }
.btn-twitter { background: #1da1f2; }
.btn-copy { background: #64748b; cursor: pointer; }
</script>
</style>

<div class="container" style="padding: 0 15px;">
    <div class="poll-container">
        
        <h1 class="poll-question"><?php echo htmlspecialchars($poll['question']); ?></h1>
        
        <div class="poll-meta">
            <span><i data-feather="calendar" style="width:14px; vertical-align:text-bottom;"></i> <?php echo date('M d, Y', strtotime($poll['created_at'])); ?></span>
            <span id="total-votes"><i data-feather="users" style="width:14px; vertical-align:text-bottom;"></i> <?php echo $total_votes; ?> Votes</span>
            <?php if($is_closed): ?>
                <span style="color:#ef4444; font-weight:700;"><i data-feather="lock" style="width:14px; vertical-align:text-bottom;"></i> Closed</span>
            <?php elseif($is_upcoming): 
                $remaining = $starts_time - $now_time;
            ?>
                <span style="color:#d97706; font-weight:700;" id="countdown-timer" data-remaining="<?php echo $remaining; ?>" data-type="starts"><i data-feather="clock" style="width:14px; vertical-align:text-bottom;"></i> Starts in: ...</span>
            <?php elseif($expires_time > 0): 
                $remaining = $expires_time - $now_time;
            ?>
                <span style="color:#10b981; font-weight:700;" id="countdown-timer" data-remaining="<?php echo $remaining; ?>" data-type="expires"><i data-feather="clock" style="width:14px; vertical-align:text-bottom;"></i> Closes in: ...</span>
            <?php endif; ?>
        </div>

        <div id="poll-content">
            <?php if ($is_upcoming): ?>
                <!-- Show Upcoming Message -->
                <div style="text-align: center; padding: 40px; background: #f8fafc; border-radius: 12px; border: 2px dashed #e2e8f0;">
                    <i data-feather="clock" style="width: 48px; height: 48px; color: #d97706; margin-bottom: 15px;"></i>
                    <h3 style="margin: 0 0 10px 0; color: #0f172a;">Voting Hasn't Started Yet</h3>
                    <p style="margin: 0; color: #64748b;">Please come back when the countdown finishes to cast your vote.</p>
                </div>
            <?php elseif ($show_results): ?>
                <!-- Show Results -->
                <div class="poll-results">
                    <?php foreach ($options as $opt): 
                        $percent = $total_votes > 0 ? round(($opt['votes_count'] / $total_votes) * 100) : 0;
                    ?>
                    <div class="result-item">
                        <div class="result-text">
                            <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                            <span><?php echo $opt['votes_count']; ?> (<?php echo $percent; ?>%)</span>
                        </div>
                        <div class="result-bar-bg">
                            <div class="result-bar-fill" style="width: <?php echo $percent; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 10px; text-align: center; color: #64748b; font-size: 14px;">
                        <?php if($has_voted): ?>
                            <i data-feather="check-circle" style="color: #10b981; width: 18px; vertical-align: text-bottom;"></i> You have voted on this poll.
                        <?php else: ?>
                            This poll is closed for voting.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show Voting Form -->
                <form id="pollForm">
                    <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                    <?php foreach ($options as $opt): ?>
                        <label class="poll-option-label">
                            <input type="radio" name="option_id" value="<?php echo $opt['id']; ?>" class="poll-option-radio" required>
                            <?php echo htmlspecialchars($opt['option_text']); ?>
                        </label>
                    <?php endforeach; ?>
                    
                    <button type="submit" class="poll-btn" id="voteBtn">Submit Vote</button>
                    <div id="poll-msg" style="margin-top: 15px; text-align: center; font-size: 14px; font-weight: 600; display: none;"></div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Share Section -->
        <div class="share-section">
            <h4 style="margin:0 0 10px 0; font-size:16px; color:#334155;">Share this Poll</h4>
            <div class="share-buttons">
                <a href="https://api.whatsapp.com/send?text=<?php echo $share_text . '%0A' . $share_url; ?>" target="_blank" class="share-btn btn-whatsapp" title="Share on WhatsApp">
                    <i data-feather="message-circle" style="width:20px; height:20px;"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" class="share-btn btn-facebook" title="Share on Facebook">
                    <i data-feather="facebook" style="width:20px; height:20px;"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo $share_text; ?>&url=<?php echo $share_url; ?>" target="_blank" class="share-btn btn-twitter" title="Share on Twitter">
                    <i data-feather="twitter" style="width:20px; height:20px;"></i>
                </a>
                <div class="share-btn btn-copy" onclick="copyToClipboard('<?php echo urldecode($share_url); ?>')" title="Copy Link">
                    <i data-feather="link" style="width:20px; height:20px;"></i>
                </div>
            </div>
            <div id="copy-msg" style="color:#10b981; font-size:12px; margin-top:10px; font-weight:600; display:none;">Link copied to clipboard!</div>
        </div>

    </div>

    <!-- Advertisement -->
    <div style="margin-top: 30px;">
        <?php echo display_ad('article_bottom', $pdo); ?>
    </div>

    <!-- Other Polls Section -->
    <?php
    $other_polls_stmt = $pdo->prepare("SELECT * FROM polls WHERE status = 'active' AND id != ? ORDER BY created_at DESC LIMIT 3");
    $other_polls_stmt->execute([$poll_id]);
    $other_polls = $other_polls_stmt->fetchAll();
    
    if (count($other_polls) > 0):
    ?>
    <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #e2e8f0;">
        <h3 style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 20px;">Vote on Other Polls</h3>
        <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
            <?php foreach ($other_polls as $op): ?>
                <a href="<?php echo BASE_URL . 'poll/' . $op['slug']; ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: inherit; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';" onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0';">
                    <span style="font-weight: 700; color: #334155; font-size: 15px; flex: 1;"><?php echo htmlspecialchars($op['question']); ?></span>
                    <i data-feather="chevron-right" style="color: #94a3b8; width: 18px;"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pollForm = document.getElementById('pollForm');
    if (pollForm) {
        pollForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('voteBtn');
            const msg = document.getElementById('poll-msg');
            const formData = new FormData(pollForm);
            
            if(!formData.get('option_id')) {
                msg.style.display = 'block';
                msg.style.color = '#ef4444';
                msg.innerText = 'Please select an option to vote.';
                return;
            }
            
            btn.disabled = true;
            btn.innerText = 'Voting...';
            
            fetch('<?php echo rtrim(BASE_URL, "/"); ?>/api/api_poll_vote.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    msg.style.display = 'block';
                    msg.style.color = '#10b981';
                    msg.innerText = data.message;
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    msg.style.display = 'block';
                    msg.style.color = '#ef4444';
                    msg.innerText = data.message;
                    btn.disabled = false;
                    btn.innerText = 'Submit Vote';
                }
            })
            .catch(err => {
                msg.style.display = 'block';
                msg.style.color = '#ef4444';
                msg.innerText = 'An error occurred. Please try again.';
                btn.disabled = false;
                btn.innerText = 'Submit Vote';
            });
        });
    }

    // Countdown Timer Logic
    const timerEl = document.getElementById('countdown-timer');
    if (timerEl) {
        let remaining = parseInt(timerEl.getAttribute('data-remaining'), 10);
        const type = timerEl.getAttribute('data-type');
        
        const interval = setInterval(() => {
            remaining--;
            
            if (remaining < 0) {
                clearInterval(interval);
                timerEl.innerHTML = `<i data-feather="${type === 'starts' ? 'play-circle' : 'lock'}" style="width:14px; vertical-align:text-bottom;"></i> ${type === 'starts' ? 'Just Started! Refreshing...' : 'Closed'}`;
                if(typeof feather !== 'undefined') feather.replace();
                setTimeout(() => window.location.reload(), 2000);
                return;
            }
            
            const days = Math.floor(remaining / (60 * 60 * 24));
            const hours = Math.floor((remaining % (60 * 60 * 24)) / (60 * 60));
            const minutes = Math.floor((remaining % (60 * 60)) / 60);
            const seconds = Math.floor(remaining % 60);
            
            let timeStr = "";
            if (days > 0) timeStr += days + "d ";
            timeStr += String(hours).padStart(2, '0') + "h ";
            timeStr += String(minutes).padStart(2, '0') + "m ";
            timeStr += String(seconds).padStart(2, '0') + "s";
            
            timerEl.innerHTML = `<i data-feather="clock" style="width:14px; vertical-align:text-bottom;"></i> ${type === 'starts' ? 'Starts in' : 'Closes in'}: ${timeStr}`;
            if(typeof feather !== 'undefined') feather.replace();
        }, 1000);
    }
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        const msg = document.getElementById('copy-msg');
        msg.style.display = 'block';
        setTimeout(() => { msg.style.display = 'none'; }, 3000);
    });
}
</script>

<?php include 'includes/public_footer.php'; ?>
