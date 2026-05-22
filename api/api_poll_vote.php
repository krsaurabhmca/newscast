<?php
ini_set('display_errors', 0);
error_reporting(0);
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

function sendJson($data) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'message' => 'Invalid request method.']);
}

$poll_id = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : 0;
$option_id = isset($_POST['option_id']) ? (int)$_POST['option_id'] : 0;

if (!$poll_id || !$option_id) {
    sendJson(['success' => false, 'message' => 'Invalid parameters.']);
}

// Ensure the poll is active
$stmt = $pdo->prepare("SELECT status, starts_at, expires_at FROM polls WHERE id = ?");
$stmt->execute([$poll_id]);
$poll = $stmt->fetch();

if (!$poll || $poll['status'] !== 'active') {
    sendJson(['success' => false, 'message' => 'This poll is closed or does not exist.']);
}

$now = date('Y-m-d H:i:s');
if ($poll['starts_at'] && $poll['starts_at'] > $now) {
    sendJson(['success' => false, 'message' => 'This poll has not started yet.']);
}
if ($poll['expires_at'] && $poll['expires_at'] < $now) {
    sendJson(['success' => false, 'message' => 'This poll has expired.']);
}

// Stop fake voting: Browser cookie check
if (!isset($_COOKIE['voter_id'])) {
    // Generate a unique identifier for this browser
    $browser_id = bin2hex(random_bytes(16));
    // Set cookie for 1 year
    setcookie('voter_id', $browser_id, time() + (365 * 24 * 60 * 60), "/");
} else {
    $browser_id = clean($_COOKIE['voter_id']);
}

$ip_address = $_SERVER['REMOTE_ADDR'];

// Check if this browser OR IP has already voted on this poll
$check = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND (browser_id = ? OR ip_address = ?)");
$check->execute([$poll_id, $browser_id, $ip_address]);
if ($check->rowCount() > 0) {
    sendJson(['success' => false, 'message' => 'You have already voted on this poll.']);
}

try {
    $pdo->beginTransaction();
    
    // Increment vote count
    $upd = $pdo->prepare("UPDATE poll_options SET votes_count = votes_count + 1 WHERE id = ? AND poll_id = ?");
    $upd->execute([$option_id, $poll_id]);
    
    // Record the vote
    $ins = $pdo->prepare("INSERT INTO poll_votes (poll_id, browser_id, ip_address) VALUES (?, ?, ?)");
    $ins->execute([$poll_id, $browser_id, $ip_address]);
    
    $pdo->commit();
    sendJson(['success' => true, 'message' => 'Your vote has been recorded.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    sendJson(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
