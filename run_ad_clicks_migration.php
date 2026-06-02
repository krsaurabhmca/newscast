<?php
require_once 'includes/config.php';

// Only allow admin users
if (!isset($_SESSION['user_id'])) {
    die('Access denied. Please login as admin first.');
}

$results = [];

// Create ad_click_logs table
$sql = "
CREATE TABLE IF NOT EXISTS `ad_click_logs` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `ad_id`       INT(11)      DEFAULT NULL,
  `post_id`     INT(11)      DEFAULT NULL,
  `event_type`  ENUM('ad_click','sponsored_post_click') NOT NULL DEFAULT 'ad_click',
  `ad_name`     VARCHAR(255) DEFAULT NULL,
  `ad_location` VARCHAR(50)  DEFAULT NULL,
  `ip_address`  VARCHAR(45)  NOT NULL DEFAULT '',
  `user_agent`  TEXT         DEFAULT NULL,
  `referer_url` VARCHAR(500) DEFAULT NULL,
  `clicked_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ad_id`      (`ad_id`),
  KEY `idx_clicked_at` (`clicked_at`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $pdo->exec($sql);
    $results[] = ['status' => 'success', 'message' => 'Table <code>ad_click_logs</code> created (or already exists).'];
} catch (PDOException $e) {
    $results[] = ['status' => 'error', 'message' => 'Error creating table: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ad Click History Migration</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 700px; margin: 60px auto; padding: 0 20px; background: #f8fafc; }
        h1 { color: #1e293b; }
        .result { padding: 14px 18px; border-radius: 8px; margin: 12px 0; font-size: 15px; }
        .success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        a.btn { display: inline-block; margin-top: 20px; padding: 10px 22px; background: #6366f1; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <h1>🗄️ Ad Click History Migration</h1>
    <?php foreach ($results as $r): ?>
        <div class="result <?php echo $r['status']; ?>"><?php echo $r['message']; ?></div>
    <?php endforeach; ?>
    <a class="btn" href="admin/ads.php">← Back to Ad Campaigns</a>
    <a class="btn" href="admin/ad_click_history.php" style="background:#10b981; margin-left:10px;">View Click History →</a>
</body>
</html>
