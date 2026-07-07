-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 11:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `newsportal_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ads`
--

CREATE TABLE `ads` (
  `id` int(11) NOT NULL,
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
  `clicks` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `icon` varchar(100) DEFAULT 'folder',
  `color` varchar(20) DEFAULT '#6366f1',
  `status` enum('active','disabled') DEFAULT 'active',
  `show_on_homepage` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`, `icon`, `color`, `status`) VALUES
(1, 'Technology', 'technology', '', '2026-02-24 18:05:12', 'globe', '#6366f1', 'active'),
(2, 'Business', 'business', '', '2026-02-24 18:05:12', 'briefcase', '#f59e0b', 'active'),
(3, 'Health', 'health', '', '2026-02-24 18:05:12', 'stop-circle', '#3ef41a', 'active'),
(4, 'Entertainment', 'entertainment', '', '2026-02-24 18:05:12', 'music', '#db2777', 'active'),
(5, 'Sports', 'sports', '', '2026-02-24 18:05:12', 'shield', '#475569', 'active'),
(6, 'Video', 'video', '', '2026-02-24 18:46:06', 'film', '#16a34a', 'active'),
(7, 'Politics', 'politics', 'National and international political news', '2026-02-24 19:45:07', 'flag', '#dc2626', 'active'),
(8, 'Science', 'science', 'Space, discoveries, and innovation', '2026-02-24 19:45:07', 'zap', '#0891b2', 'disabled'),
(9, 'Lifestyle', 'lifestyle', 'Travel, food, and daily living', '2026-02-24 19:45:07', 'coffee', '#f59e0b', 'disabled'),
(10, 'Education', 'education', 'Academic news, careers and school updates', '2026-02-24 19:45:07', 'book', '#7c3aed', 'active'),
(11, 'Environment', 'environment', 'Climate change, nature and green news', '2026-02-24 19:45:07', 'cloud', '#0d9488', 'disabled'),
(12, 'Opinion', 'opinion', 'Expert views, editorials, and commentary', '2026-02-24 19:45:07', 'message-circle', '#475569', 'disabled'),
(13, 'World', 'world', 'International news from across the globe', '2026-02-24 19:45:07', 'globe', '#1d4ed8', 'active'),
(14, 'Local', 'local', 'News from your immediate vicinity', '2026-02-24 19:45:07', 'map-pin', '#ea580c', 'disabled'),
(15, 'Crime', 'crime', 'Legal news, police reports and investigations', '2026-02-24 19:45:07', 'shield', '#1e293b', 'active'),
(16, 'General', 'general', 'General news and latest updates.', '2026-02-26 02:04:59', 'grid', '#64748b', 'active'),
(24, 'State', 'state', '', '2026-03-18 12:46:24', 'zap', '#16a34a', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `epapers`
--

CREATE TABLE `epapers` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `paper_date` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `magazines`
--

CREATE TABLE `magazines` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `issue_month` date NOT NULL COMMENT 'Store as first-day-of-month e.g. 2025-02-01',
  `file_path` varchar(255) NOT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `pages` smallint(6) DEFAULT 0,
  `status` enum('published','draft') DEFAULT 'published',
  `downloads` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
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
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_categories`
--

CREATE TABLE `post_categories` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('about_page_content', '<p><strong>हमारे बारे में…</strong></p><p><br></p><p>“पंचायत वॉयस” का उद्देश्य लोकतांत्रिक मूल्यों की रक्षा करते हुए अपने पाठकों को सही व सटीक जानकारी देना है, जिससे वे लोकतंत्र की मजबूती में एक सचेत और सक्षम नागरिक की भूमिका का निर्वहन कर सकें। हमारी प्राथमिक सोच “तथ्य ही सत्य है” की है, इससे हम समझौता नहीं करते।</p><p>panchayatvoice.in वेब पोर्टल “पंचायत वॉयस” नामक राष्ट्रीय हिन्दी मासिक समाचार पत्रिका का डिजिटल प्लेटफॉर्म है, जो देश के ग्रामीण और शहरी जीवन में व्याप्त चुनौतियों के निराकरण एवं बेहतरी की संभावनाओं पर आधारित जन सरोकार से जुड़ी है। इसका प्रकाशन 28 मार्च 2026 को उत्तर प्रदेश की राजधानी लखनऊ से श्री संवत 2083 शक 1948 चैत्र मासे शुक्ल पक्षे दशंयाम तिथौ शनिवासरे पुष्य नशत्र सकर्मा योग में संपन्न हुआ।&nbsp;</p><p>हमारा कोर वैल्यू “पंचायत से परिवर्तन” का है, जहां गांव की पंचायत से लेकर देश की पंचायत तक की बात होगी। जिसमें समसामयिक, राजनीतिक, ज्ञान-विज्ञान, चिकित्सा, कला, साहित्य, कारोबार व खेल सहित अन्य विषयों का समावेश होगा। हमारी कोशिश होगी कि हमारे द्वारा प्रकाशित व प्रसारित खबरें, ऑडियो और वीडियो जन-जन तक पहुंचें।</p><p><br></p><p>इस मंच पर खबरों के साथ-साथ लेख, स्टोरी, विचार, विश्लेषण, फीचर, संपादकीय तथा अन्य जन सरोकार से जुड़ी सामग्री भी प्रकाशित और प्रसारित की जाएगी, ताकि समाज के विभिन्न पहलुओं को समग्र रूप में पाठकों तक पहुंचाया जा सके। हम खबरों को लेकर एक विशेष दृष्टिकोण रखते हैं, जिसमें प्राथमिक सूचनाओं के आधार पर खबर को सनसनीखेज बनाना शामिल नहीं है, अपितु खबरों से जुड़े विभिन्न पहलुओं को टटोलते हुए तथ्यों को संकलित कर सही, सटीक और पुख्ता समाचार पाठकों तक पहुंचाना हमारा उद्देश्य है।</p><p><br></p><p>हमारी प्राथमिक सोच ही “तथ्य ही सत्य है” की है। इससे हम समझौता नहीं करते, भले ही सही तथ्यों के लिए इंतजार करना पड़े। कभी-कभी हड़बड़ी में ऐसी खबरें वायरल हो जाती हैं, जिनका सत्य और तथ्य से कोई सरोकार नहीं होता, लेकिन वायरल खबरों के इस दौर में हम और हमारी टीम सत्य का शोधन कर तथ्य के साथ आपके सामने सिर्फ सच को लाने में विश्वास रखती है। इससे जुड़ी संपादकीय टीम, विशेषज्ञ, विचारकों की नजर देश, प्रदेश, शहर, नगर, गांव, गलियारे, धर्म, राजनीति, शासन-सत्ता के गलियारों के हर उस खबर पर रहती है, जो आप जानना चाहते हैं।</p><p><br></p><p>खबर प्रकाशित और प्रसारित करने से पहले हम समाज के सभी तबकों का ख्याल रखते हैं और व्यक्ति की निजता का भी ध्यान रखते हैं। लिखते समय हम अदालती आदेशों और प्रेस नियमों का पालन करते हैं। खबर प्रकाशित और प्रसारित करने से पहले हम रिपोर्ट को सही साबित करने के लिए तथ्यों का सहारा लेते हैं और उन्हें स्पष्टता के साथ लिखते हैं। व्यक्तिगत आक्षेप वाली भाषा का प्रयोग नहीं करते हैं। प्रकाशित और प्रसारित सामग्री अथवा तथ्यों का प्रयोग किसी भी समुदाय, संस्था, जाति, धर्म, गुट या विचारधारा को उकसाना नहीं, बल्कि उसे सही दिशा और राह दिखाना है।</p><p><br></p><p>हमसे जुड़ा कोई साथी यदि शर्त और नियमों का उल्लंघन करता है, तो उसकी सामग्री को “पंचायत वॉयस” बिना किसी सूचना के हटा दिया जाएगा। यहां प्रकाशित और प्रसारित सामग्री का इस्तेमाल तभी किया जा सकता है, जब संपादकीय टीम से पहले से अनुमति या सहमति ली गई हो।&nbsp;आप जो सामग्री हमें प्रकाशन और प्रसारित करने के लिए भेजेंगे, उसकी पहली शर्त है कि वह अप्रकाशित हो तथा तथ्यपरक हो।</p><p><br></p><p>हमारी कोशिश समाज में पत्रकारिता को मजबूती के साथ खड़ा करने पर है। हमारा जोर है कि हिन्दी पत्रकारिता में “तथ्य ही सत्य है” का मूल्य स्थापित हो।</p><p>सादर।</p><p><br></p><p><strong>Panchayatvoice.up@gmail.com</strong></p><p><strong>Mobile Number – 9876917688</strong></p>'),
('about_page_title', 'About Panchayat Voice'),
('address', 'UP, Bihar, Delhi'),
('bing_site_verify', ''),
('breaking_news_enabled', 'yes'),
('contact_email', 'report@panchayatvoice.in'),
('contact_phone', '9876917688'),
('copyright_text', ''),
('db_version', '2'),
('email_on_user_create', 'no'),
('facebook_url', 'https://www.nekodylyjo.ca'),
('footer_theme', 'light'),
('google_analytics_id', ''),
('google_map', ''),
('google_site_verify', ''),
('header_style', 'default'),
('instagram_url', 'https://www.fefepugenameto.info'),
('live_stream_sound', '0'),
('live_stream_title', 'Live Stream'),
('live_youtube_enabled', '0'),
('live_youtube_url', 'https://www.youtube.com/watch?v=0i-W_hziPrY'),
('meta_description', ''),
('meta_keywords', ''),
('meta_robots', 'index, follow'),
('og_image_url', 'https://panchayatvoice.in/assets/images/share.jpg'),
('onesignal_app_id', '1d93afcc-cc77-4f99-a2b3-d3568131c2d9'),
('onesignal_safari_web_id', ''),
('posts_per_page', '11'),
('schema_type', 'NewsMediaOrganization'),
('show_date_time', 'no'),
('site_favicon', 'favicon.jpg'),
('site_logo', 'logo.jpg'),
('site_name', 'Panchayat Voice'),
('site_tagline', 'Digital News Portal'),
('tts_pitch', '1.1'),
('tts_rate', '0.90'),
('tts_voice_keyword', 'Google'),
('smtp_host', ''),
('smtp_pass', ''),
('smtp_port', '587'),
('smtp_sender', ''),
('smtp_user', ''),
('theme_color', '#dc2626'),
('translation_enabled', 'no'),
('tts_enabled', 'yes'),
('twitter_handle', ''),
('twitter_url', 'https://www.nalusiwawitozur.mobi'),
('whatsapp_number', '98769 17688'),
('youtube_url', 'https://www.sekalyfagocuk.cm');

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timeline`
--

CREATE TABLE `timeline` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `status_color` varchar(20) DEFAULT '#6366f1',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','editor') DEFAULT 'editor',
  `profile_image` varchar(255) DEFAULT 'default_avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `profile_image`, `created_at`) VALUES
(1, 'admin', 'admin1@panchyatvoice.in', '$2y$10$kVL0MHW7CIzkqlc30U6S9O9uL2EsxBkuEkRSZpIfjNLts5ZY1cc/y', 'admin', 'default_avatar.png', '2026-02-24 18:05:12'),
(3, 'Panchayat Voice', 'admin@panchayatvoice.in', '$2y$10$9lwC0vU.skJ9Gtkf87GOou0u9sWbhWDt46RUEZE6FQ9wrnYcS9ZDy', 'admin', 'user_3_1771976560.jpg', '2026-02-24 23:16:27'),
(4, 'offerplant', 'ask@offerplant.com', '$2y$10$h9AKYZu6byB3mSYqxWUKN.WvcrJOXxCvfyF.NsZCoI4aP2brhGDdq', 'admin', 'default_avatar.png', '2026-02-26 02:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `action_type` enum('view','bookmark','share') DEFAULT 'view',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_post` (`user_id`,`post_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `epapers`
--
ALTER TABLE `epapers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `magazines`
--
ALTER TABLE `magazines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `timeline`
--
ALTER TABLE `timeline`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `epapers`
--
ALTER TABLE `epapers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `magazines`
--
ALTER TABLE `magazines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_categories`
--
ALTER TABLE `post_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_tags`
--
ALTER TABLE `post_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timeline`
--
ALTER TABLE `timeline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--
-- Table structure for table polls
--

CREATE TABLE polls (
  id int(11) NOT NULL,
  question varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  status enum('active','closed') DEFAULT 'active',
  starts_at datetime DEFAULT NULL,
  expires_at datetime DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table poll_options
--

CREATE TABLE poll_options (
  id int(11) NOT NULL,
  poll_id int(11) NOT NULL,
  option_text varchar(255) NOT NULL,
  votes_count int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table poll_votes
--

CREATE TABLE poll_votes (
  id int(11) NOT NULL,
  poll_id int(11) NOT NULL,
  browser_id varchar(100) NOT NULL,
  ip_address varchar(45) NOT NULL,
  voted_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE polls
  ADD PRIMARY KEY (id);

ALTER TABLE poll_options
  ADD PRIMARY KEY (id),
  ADD KEY poll_id (poll_id);

ALTER TABLE poll_votes
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY poll_browser (poll_id, browser_id),
  ADD KEY poll_id (poll_id);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE polls
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE poll_options
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE poll_votes
  MODIFY id int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE poll_options
  ADD CONSTRAINT poll_options_ibfk_1 FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE;

ALTER TABLE poll_votes
  ADD CONSTRAINT poll_votes_ibfk_1 FOREIGN KEY (poll_id) REFERENCES polls (id) ON DELETE CASCADE;

-- --------------------------------------------------------
--
-- Table structure for table `ad_click_logs`
-- Records every individual ad click with IP, datetime, location and event type
--

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
