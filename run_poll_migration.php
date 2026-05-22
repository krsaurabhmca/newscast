<?php
require_once 'includes/config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `polls` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `question` varchar(255) NOT NULL,
        `status` enum('active','closed') DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `poll_options` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `poll_id` int(11) NOT NULL,
        `option_text` varchar(255) NOT NULL,
        `votes_count` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `poll_id` (`poll_id`),
        CONSTRAINT `poll_options_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `poll_votes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `poll_id` int(11) NOT NULL,
        `browser_id` varchar(100) NOT NULL,
        `ip_address` varchar(45) NOT NULL,
        `voted_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `poll_browser` (`poll_id`,`browser_id`),
        CONSTRAINT `poll_votes_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    echo "Migration successful.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
