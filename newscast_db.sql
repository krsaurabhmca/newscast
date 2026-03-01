-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: newsportal_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` enum('header','sidebar','content_top','content_bottom') NOT NULL,
  `type` enum('image','code') DEFAULT 'image',
  `image_path` varchar(255) DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `link_type` enum('url','call','whatsapp') DEFAULT 'url',
  `ad_code` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ads`
--

LOCK TABLES `ads` WRITE;
/*!40000 ALTER TABLE `ads` DISABLE KEYS */;
INSERT INTO `ads` VALUES (1,'Matthew Rowe','content_bottom','image','ad_699df7bf3dba8.png','9431426600','call','',1,'2026-02-01','2026-02-28','2026-02-24 19:10:55',57,0),(3,'Matthew Rowe','sidebar','image','ad_699df83112521.png','9431426600','call','',1,'2026-02-01','2026-02-28','2026-02-24 19:12:49',199,0);
/*!40000 ALTER TABLE `ads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookmarks`
--

DROP TABLE IF EXISTS `bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_post` (`user_id`,`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookmarks`
--

LOCK TABLES `bookmarks` WRITE;
/*!40000 ALTER TABLE `bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `icon` varchar(100) DEFAULT 'folder',
  `color` varchar(20) DEFAULT '#6366f1',
  `status` enum('active','disabled') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Technology','technology','','2026-02-24 18:05:12','globe','#6366f1','active'),(2,'Business','business','','2026-02-24 18:05:12','map-pin','#f59e0b','active'),(3,'Health','health','','2026-02-24 18:05:12','stop-circle','#3ef41a','active'),(4,'Entertainment','entertainment','','2026-02-24 18:05:12','music','#db2777','active'),(5,'Sports','sports','','2026-02-24 18:05:12','shield','#475569','active'),(6,'Video','video','','2026-02-24 18:46:06','film','#16a34a','active'),(7,'Politics','politics','National and international political news','2026-02-24 19:45:07','flag','#dc2626','active'),(8,'Science','science','Space, discoveries, and innovation','2026-02-24 19:45:07','zap','#0891b2','disabled'),(9,'Lifestyle','lifestyle','Travel, food, and daily living','2026-02-24 19:45:07','coffee','#f59e0b','disabled'),(10,'Education','education','Academic news, careers and school updates','2026-02-24 19:45:07','book','#7c3aed','active'),(11,'Environment','environment','Climate change, nature and green news','2026-02-24 19:45:07','cloud','#0d9488','disabled'),(12,'Opinion','opinion','Expert views, editorials, and commentary','2026-02-24 19:45:07','message-circle','#475569','disabled'),(13,'World','world','International news from across the globe','2026-02-24 19:45:07','globe','#1d4ed8','active'),(14,'Local','local','News from your immediate vicinity','2026-02-24 19:45:07','map-pin','#ea580c','disabled'),(15,'Crime','crime','Legal news, police reports and investigations','2026-02-24 19:45:07','shield','#1e293b','active'),(16,'General','general','General news and latest updates.','2026-03-01 07:44:45','grid','#64748b','active');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epapers`
--

DROP TABLE IF EXISTS `epapers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `epapers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `paper_date` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `epapers`
--

LOCK TABLES `epapers` WRITE;
/*!40000 ALTER TABLE `epapers` DISABLE KEYS */;
INSERT INTO `epapers` VALUES (1,'Computer in Research','2026-02-24','1771958438_Computers in Research.pdf','thumb_1771958438_ChatGPT Image Feb 24, 2026, 12_13_39 PM.png','2026-02-24 18:40:38'),(2,'New Book Sing','2026-02-24','1771967790_2026-02-13T11-58 Payment receipt #25816214128062285-25816214184728946.pdf','thumb_1771967790_261690262_1977717589068174_7245295120682884408_n.jpg','2026-02-24 21:16:30');
/*!40000 ALTER TABLE `epapers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
INSERT INTO `feedback` VALUES (1,'Ray Elliott','bydawagik@mailinator.com','+1 (538) 855-3075','Content Issue','Fugit reprehenderit','read','2026-02-24 20:33:41'),(2,'Chester Mullins','fizoxyhy@mailinator.com','+1 (962) 357-2933','Web Contact Form','Incidunt et dolorem','read','2026-02-24 20:34:06');
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `magazines`
--

DROP TABLE IF EXISTS `magazines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `magazines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issue_month` date NOT NULL COMMENT 'Store as first-day-of-month e.g. 2025-02-01',
  `file_path` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `pages` smallint(6) DEFAULT 0,
  `status` enum('published','draft') DEFAULT 'published',
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `magazines`
--

LOCK TABLES `magazines` WRITE;
/*!40000 ALTER TABLE `magazines` DISABLE KEYS */;
/*!40000 ALTER TABLE `magazines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_categories`
--

DROP TABLE IF EXISTS `post_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_categories`
--

LOCK TABLES `post_categories` WRITE;
/*!40000 ALTER TABLE `post_categories` DISABLE KEYS */;
INSERT INTO `post_categories` VALUES (1,9,1),(2,10,1),(3,1,2),(4,3,2),(5,12,2),(6,5,3),(8,6,5),(9,7,5),(10,8,5),(11,11,5),(12,13,5),(18,14,3),(19,14,6),(22,4,4),(23,16,1),(24,17,1),(25,18,1),(26,19,1),(27,20,1),(28,21,1),(29,22,1),(30,23,1),(31,24,1),(32,25,1);
/*!40000 ALTER TABLE `post_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tags`
--

DROP TABLE IF EXISTS `post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `post_tags` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tags`
--

LOCK TABLES `post_tags` WRITE;
/*!40000 ALTER TABLE `post_tags` DISABLE KEYS */;
INSERT INTO `post_tags` VALUES (4,2),(4,3),(4,6);
/*!40000 ALTER TABLE `post_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `external_link` varchar(255) DEFAULT NULL,
  `external_type` enum('none','url','whatsapp','call') DEFAULT 'none',
  `external_label` enum('none','Ad','Promoted','Sponsored') DEFAULT 'none',
  `status` enum('draft','published') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `meta_description` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,2,1,'Test Poste','test-poste','<p>Test PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest PosteTest Poste</p>','','post_699de99cbc65c.png',NULL,NULL,'none','none','published',4,1,'','2026-02-24 18:09:17','2026-02-24 19:33:14','2026-02-24 23:39:17'),(3,2,1,'Excepteur qui qui et','officia-molestiae-et','<p>Amet, nihil voluptas. Amet, nihil voluptas.Et eiusmod rerum tem.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.Amet, nihil voluptas.</p>','Cillum veniam fugit','',NULL,NULL,'none','none','published',5,0,'Eos vel atque qui et repellendus Consequuntur quo','2026-02-24 18:21:10','2026-02-25 11:04:09','2026-02-24 23:51:10'),(10,1,1,'Economy Trends for the Next Decade','economy-trends-for-the-next-decade-731','<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, q...','post_699dec75e2e76.jpg',NULL,NULL,'none','none','published',239,1,'','2026-02-24 18:22:06','2026-02-24 20:58:50','2026-02-24 23:52:06'),(12,2,1,'Mastering the Art of Modern Cooking','mastering-the-art-of-modern-cooking-426','<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, q...','post_699dec8ab7717.png',NULL,NULL,'none','none','published',367,1,'','2026-02-24 18:22:06','2026-02-25 06:35:05','2026-02-24 23:52:06'),(13,5,1,'Cybersecurity Tips for Everyone','cybersecurity-tips-for-everyone-110','<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>','Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, q...',NULL,NULL,NULL,'none','none','published',369,1,NULL,'2026-02-24 18:22:06','2026-02-24 19:33:14','2026-02-24 23:52:06'),(14,6,2,'Pariatur Sint aut e','impedit-officiis-ve','<p>Tempora sit, eveniet. Tempora sit, eveniet.Omnis qui aut distin.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.Tempora sit, eveniet.</p>','Rerum consectetur ve','','https://www.youtube.com/watch?v=CT_WEGUKejQ&amp;t=2175s','','none','none','draft',11,1,'Odio asperiores enim alias et facere iusto at et suscipit ipsa','2026-02-24 19:01:28','2026-02-24 20:37:38','2026-02-25 00:31:00'),(16,2,1,'Revolutionize Your Newsroom with NewsCast Auto-Share','revolutionize-newsroom-newscast-auto-share','In the fast-paced world of digital journalism, speed is everything. NewsCast introduces a powerful Auto-Share feature that lets you push articles to Facebook and Instagram the moment you hit publish. No more manual copying, no more wasted time. Just instant reach and professional delivery.','In the fast-paced world of digital journalism, speed is everything. NewsCast introduces a powerful Auto-Share feature that lets you push articles to F...','share.png',NULL,NULL,'none','none','published',10239,1,NULL,'2026-03-01 11:25:36','2026-03-01 11:38:00','2026-03-01 17:06:44'),(17,2,1,'Beat the Clock: Save Hours Every Week on Social Media','save-hours-social-media-automation','Journalists should focus on stories, not social media management. Our automated system handles the synchronization of your content across major platforms, saving you hours of repetitive work. Focus on the scoop, and let NewsCast handle the audience engagement.','Journalists should focus on stories, not social media management. Our automated system handles the synchronization of your content across major platfo...','share.png',NULL,NULL,'none','none','published',5381,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:38:10','2026-03-01 17:06:44'),(18,2,1,'The Power of Multi-Platform News Distribution','power-multi-platform-news-distribution','Your readers are on Facebook, Instagram, and beyond. NewsCast allows you to maintain a consistent presence across all connected nodes with a single click. Broadcast your message globally and ensure your headlines are seen everywhere simultaneously.','Your readers are on Facebook, Instagram, and beyond. NewsCast allows you to maintain a consistent presence across all connected nodes with a single cl...','share.png',NULL,NULL,'none','none','published',11617,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:36:44','2026-03-01 17:06:44'),(19,2,1,'Professional Branding Made Easy for Digital Publishers','professional-branding-digital-publishers','Consistency is key to a premium news brand. Our Social Share module ensures that every post sent to Facebook and Instagram is formatted professionally, carrying your brand identity and driving traffic directly back to your domain with clean, reliable links.','Consistency is key to a premium news brand. Our Social Share module ensures that every post sent to Facebook and Instagram is formatted professionally...','share.png',NULL,NULL,'none','none','published',7168,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:37:50','2026-03-01 17:06:44'),(20,2,1,'Manual Dispatch: Total Control over Your Breaking News','manual-dispatch-breaking-news-control','While automation is powerful, sometimes you need surgical precision. Our \"Manual Broadcast\" center gives you the ability to select the exact content architecture and dispatch it to specific nodes—Facebook page or Instagram edge—whenever you need that extra push.','While automation is powerful, sometimes you need surgical precision. Our \"Manual Broadcast\" center gives you the ability to select the exact content a...','share.png',NULL,NULL,'none','none','published',11184,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:37:55','2026-03-01 17:06:44'),(21,2,1,'How Social Signals Boost Your News Site SEO','social-signals-boost-news-seo','Engagement on social media isn\'t just about views—it\'s about authority. By automating your social shares with NewsCast, you create a steady stream of social signals that search engines love, helping your articles rank higher and reach a wider organic audience.','Engagement on social media isn\'t just about views—it\'s about authority. By automating your social shares with NewsCast, you create a steady stream o...','share.png',NULL,NULL,'none','none','published',6140,0,NULL,'2026-03-01 11:25:36','2026-03-01 15:30:47','2026-03-01 17:06:44'),(22,2,1,'Seamless Setup: From Newsroom to Social Feed in Minutes','seamless-setup-social-news-feed','Don\'t let technical jargon slow you down. Our new \"Full Setup Guide\" and \"Configuration Grid\" make it easy for any administrative user to link their Meta App, exchange permanent tokens, and start broadcasting. Professional news distribution has never been this simple.','Don\'t let technical jargon slow you down. Our new \"Full Setup Guide\" and \"Configuration Grid\" make it easy for any administrative user to link their M...','share.png',NULL,NULL,'none','none','published',9339,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:36:44','2026-03-01 17:06:44'),(23,2,1,'The Science of the Social Share: Engagement Analytics','science-social-share-engagement','Every post shared through NewsCast is optimized for engagement. By leveraging the Meta Graph API, we ensure your articles are displayed with high-quality featured images and crisp meta-descriptions, capturing the reader\'s attention and maximizing click-through rates.','Every post shared through NewsCast is optimized for engagement. By leveraging the Meta Graph API, we ensure your articles are displayed with high-qual...','share.png',NULL,NULL,'none','none','published',13005,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:36:44','2026-03-01 17:06:44'),(24,2,1,'Digital Seal: Empowering Independent Media Everywhere','digital-seal-empowering-independent-media','NewsCast is built by Digital Seal to provide enterprise-grade tools to independent newsrooms. Our Social Auto-Share system is just one part of a comprehensive ecosystem designed to make professional media publishing accessible and efficient for everyone.','NewsCast is built by Digital Seal to provide enterprise-grade tools to independent newsrooms. Our Social Auto-Share system is just one part of a compr...','share.png',NULL,NULL,'none','none','published',9521,0,NULL,'2026-03-01 11:25:36','2026-03-01 11:36:44','2026-03-01 17:06:44'),(25,2,1,'Stay Connected: Real-time System Diagnostics for Your Feed','stay-connected-real-time-diagnostics','Never worry about a broken link again. With our built-in connection badges and diagnostic tools, you can instantly verify if your Facebook and Instagram nodes are active. Reliability is at the heart of NewsCast, ensuring your news always reaches your followers.','Never worry about a broken link again. With our built-in connection badges and diagnostic tools, you can instantly verify if your Facebook and Instagr...','share.png',NULL,NULL,'none','none','published',11902,0,NULL,'2026-03-01 11:25:36','2026-03-01 15:30:52','2026-03-01 17:06:44');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reporter_payments`
--

DROP TABLE IF EXISTS `reporter_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reporter_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reporter_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pay_type` enum('salary','bonus','article_fee','expense','advance','other') DEFAULT 'salary',
  `pay_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reporter_payments`
--

LOCK TABLES `reporter_payments` WRITE;
/*!40000 ALTER TABLE `reporter_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `reporter_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('address','Laborum qui quaerat'),('auto_share_facebook','no'),('auto_share_instagram','no'),('auto_share_on_publish','yes'),('bing_site_verify',''),('breaking_news_enabled','yes'),('contact_email','nivu@mailinator.com'),('contact_phone','+1 (726) 211-4134'),('copyright_text',''),('facebook_url','https://www.nekodylyjo.ca'),('fb_app_id','2115421249207805'),('fb_app_secret','0da9921ba712d8285debc9621264c843'),('fb_page_access_token','EAAeD9uQbZCf0BQZB08ZAW8A3lX4BUOxtdw2uBVAkwqygPL1UnmhfQpRDM27QvlSFvzHVY5WKMAUZCNGY9kcJIZCMjr3UIdZAOuZCCxkEJFkdAmkHcHqnTMIFTIHlnpASXeWPXFShY97ODJ4LUEcaXTaS1ve6ZAtaLguj0AqVk8Y4N4Q0sZBZBMyrxyJ9brCNE7eIG3AG7dCyIHzeXjfKnewrPY3mNVa5MjlPaw0DdksMlZBPeeyhoYcSiM9RRgSs7D2GM0EJNOlFoLY5RifBIG7ycrG'),('fb_page_id',''),('footer_theme','light'),('google_analytics_id',''),('google_map',''),('google_site_verify',''),('header_style','sticky'),('ig_access_token',''),('ig_business_account_id',''),('instagram_url','https://www.fefepugenameto.info'),('live_stream_sound','0'),('live_stream_title','Live Stream'),('live_youtube_enabled','1'),('live_youtube_url','https://www.youtube.com/watch?v=0i-W_hziPrY'),('meta_description',''),('meta_keywords',''),('meta_robots','index, follow'),('og_image_url',''),('posts_per_page','10'),('schema_type','NewsMediaOrganization'),('show_date_time','no'),('site_favicon','favicon.jpeg'),('site_logo','logo.jpeg'),('site_name','NewsCast'),('site_tagline','Digital News Portal'),('theme_color','#ff3c00'),('translation_enabled','no'),('tts_enabled','yes'),('twitter_handle',''),('twitter_url','https://www.nalusiwawitozur.mobi'),('whatsapp_number','645'),('youtube_url','https://www.sekalyfagocuk.cm');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_shares`
--

DROP TABLE IF EXISTS `social_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `platform` enum('facebook','instagram','twitter') NOT NULL,
  `status` enum('success','failed') DEFAULT 'success',
  `response` text DEFAULT NULL,
  `shared_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_shares`
--

LOCK TABLES `social_shares` WRITE;
/*!40000 ALTER TABLE `social_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (2,'breaking','breaking','2026-02-25 06:18:43'),(3,'news','news','2026-02-25 06:18:43'),(6,'top 10','top-10','2026-02-25 06:22:55');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timeline`
--

DROP TABLE IF EXISTS `timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_time` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `status_color` varchar(20) DEFAULT '#6366f1',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timeline`
--

LOCK TABLES `timeline` WRITE;
/*!40000 ALTER TABLE `timeline` DISABLE KEYS */;
INSERT INTO `timeline` VALUES (1,'6:30 PM','Gajal Sandhya at Param Complex With Sufi Najeer Ahamad','#f59e0b','2026-02-25 10:51:41');
/*!40000 ALTER TABLE `timeline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `action_type` enum('view','bookmark','share') DEFAULT 'view',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity`
--

LOCK TABLES `user_activity` WRITE;
/*!40000 ALTER TABLE `user_activity` DISABLE KEYS */;
INSERT INTO `user_activity` VALUES (1,1,7,'view','2026-02-25 06:29:19'),(2,1,7,'view','2026-02-25 06:29:55'),(3,1,7,'view','2026-02-25 06:30:05'),(4,1,7,'view','2026-02-25 06:30:08'),(5,1,7,'view','2026-02-25 06:30:20'),(6,1,7,'view','2026-02-25 06:30:37'),(7,1,7,'view','2026-02-25 06:32:11'),(8,1,12,'view','2026-02-25 06:32:15'),(9,1,12,'view','2026-02-25 06:32:47'),(10,1,12,'view','2026-02-25 06:33:00'),(11,1,12,'view','2026-02-25 06:33:00'),(12,1,12,'view','2026-02-25 06:33:00'),(13,1,12,'view','2026-02-25 06:33:01'),(14,1,12,'view','2026-02-25 06:33:01'),(15,1,12,'view','2026-02-25 06:33:01'),(16,1,12,'view','2026-02-25 06:33:01'),(17,1,3,'view','2026-02-25 06:33:26'),(18,1,9,'view','2026-02-25 06:34:48'),(19,1,12,'view','2026-02-25 06:35:05'),(20,1,4,'view','2026-02-25 10:10:01'),(21,1,3,'view','2026-02-25 11:04:09'),(22,1,5,'view','2026-03-01 11:25:57'),(23,1,7,'view','2026-03-01 11:26:06'),(24,1,6,'view','2026-03-01 11:27:10'),(25,1,8,'view','2026-03-01 11:27:19'),(26,1,24,'view','2026-03-01 11:28:24'),(27,1,19,'view','2026-03-01 11:37:50'),(28,1,20,'view','2026-03-01 11:37:55'),(29,1,16,'view','2026-03-01 11:38:01'),(30,1,17,'view','2026-03-01 11:38:10'),(31,1,21,'view','2026-03-01 11:44:24'),(32,1,21,'view','2026-03-01 15:30:47'),(33,1,25,'view','2026-03-01 15:30:52');
/*!40000 ALTER TABLE `user_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','editor') DEFAULT 'editor',
  `profile_image` varchar(255) DEFAULT 'default_avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@newscast.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','default_avatar.png','2026-02-24 18:05:12'),(2,'Saurabh','krsaurabhbca@gmail.com','$2y$10$vYghMlwV7KBhjpuHcGvlH.3bFBbAuXt0n1VNk3i/cwPKkmHm3/Nra','editor','default_avatar.png','2026-02-24 18:59:59');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-01 21:10:18
