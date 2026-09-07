<?php
require 'includes/config.php';
$stmt = $pdo->query('SELECT id, link_url FROM ads');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
