<?php
header("Content-Type: application/rss+xml; charset=UTF-8");
require_once 'includes/config.php';
require_once 'includes/functions.php';

$site_name = get_setting('site_name', 'NewsCast');
$site_tagline = get_setting('site_tagline', 'Digital News Portal');
$meta_desc = get_setting('meta_description', 'Your ultimate destination for the latest news and insights.');

// Fetch latest published posts with at least one active category
$stmt = $pdo->query("SELECT DISTINCT p.*, u.username FROM posts p
                     JOIN users u ON p.user_id = u.id
                     JOIN post_categories pc ON p.id = pc.post_id
                     JOIN categories c ON pc.category_id = c.id
                     WHERE p.status = 'published' AND p.published_at <= NOW() AND c.status = 'active'
                     ORDER BY p.published_at DESC LIMIT 30");
$posts = $stmt->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo htmlspecialchars($site_name); ?></title>
    <link><?php echo BASE_URL; ?></link>
    <description><?php echo htmlspecialchars($meta_desc); ?></description>
    <language>en-us</language>
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>
    <atom:link href="<?php echo BASE_URL; ?>feed.php" rel="self" type="application/rss+xml" />
    
    <?php foreach ($posts as $post): 
        $post_url = ($post['external_type'] != 'none') ? BASE_URL . "click_tracker.php?post_id=" . $post['id'] : BASE_URL . "article/" . $post['slug'];
        $pub_date = date(DATE_RSS, strtotime($post['published_at']));
    ?>
    <item>
      <title><?php echo htmlspecialchars($post['title']); ?></title>
      <link><?php echo $post_url; ?></link>
      <guid isPermaLink="true"><?php echo $post_url; ?></guid>
      <pubDate><?php echo $pub_date; ?></pubDate>
      <dc:creator><?php echo htmlspecialchars($post['username']); ?></dc:creator>
      <description><![CDATA[<?php echo htmlspecialchars($post['excerpt'] ?: get_excerpt($post['content'], 50)); ?>]]></description>
      <content:encoded><![CDATA[<?php echo $post['content']; ?>]]></content:encoded>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
