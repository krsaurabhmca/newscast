<?php
include 'includes/config.php';
$pdo->query("INSERT INTO settings (setting_key, setting_value) VALUES ('epaper_footer_msg', 'Test Footer Msg') ON DUPLICATE KEY UPDATE setting_value='Test Footer Msg'");
echo "Done.";
