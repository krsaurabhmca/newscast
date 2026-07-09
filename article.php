<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['slug'])) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$slug = $_GET['slug'];
$stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= NOW()
                       AND EXISTS (
                           SELECT 1 FROM post_categories pc
                           JOIN categories c ON pc.category_id = c.id
                           WHERE pc.post_id = p.id AND c.status = 'active'
                       )");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Fetch Categories for this post
$post_categories = get_post_categories($pdo, $post['id']);
$primary_cat = !empty($post_categories) ? $post_categories[0] : null;

// Update views
$update = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$update->execute([$post['id']]);

// Log activity (view)
$user_id = $_SESSION['user_id'] ?? null;
log_activity($pdo, $user_id, $post['id'], 'view');

// Log view to post_views_logs
try {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $pdo->prepare("INSERT INTO post_views_logs (post_id, ip_address, user_agent) VALUES (?, ?, ?)")->execute([$post['id'], $ip, $ua]);
} catch (Exception $e) {}

// Check if bookmarked
$is_bookmarked = false;
if ($user_id) {
    $stmt_book = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt_book->execute([$user_id, $post['id']]);
    $is_bookmarked = $stmt_book->fetch() ? true : false;
}

// Calculate Read Time
$read_time = calculate_reading_time($post['content']);

// Main Page SEO
$page_title = $post['title'];
$meta_description = $post['meta_description'] ?: ($post['excerpt'] ?: get_excerpt($post['content'], 30));
$page_image = get_post_thumbnail($post['featured_image']);

// Generate Schema JSON-LD
$schema = [
    "@context" => "https://schema.org",
    "@type" => "NewsArticle",
    "headline" => $post['title'],
    "image" => [$page_image],
    "datePublished" => date('c', strtotime($post['published_at'])),
    "dateModified" => date('c', strtotime($post['created_at'])),
    "author" => [
        "@type" => "Person",
        "name" => $post['username'],
        "url" => BASE_URL
    ],
    "publisher" => [
        "@type" => "Organization",
        "name" => SITE_NAME,
        "logo" => [
            "@type" => "ImageObject",
            "url" => BASE_URL . "assets/images/logo.png"
        ]
    ],
    "description" => $meta_description
];

include 'includes/public_header.php';
?>
<script type="application/ld+json">
<?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
</script>

<?php
// Fetch Related Posts (sharing any category with current post)
$cat_ids = array_column($post_categories, 'id');
$placeholders = count($cat_ids) > 0 ? str_repeat('?,', count($cat_ids) - 1) . '?' : '0';
$stmt = $pdo->prepare("SELECT p.* FROM posts p 
                       WHERE p.id != ? AND p.status = 'published' AND p.published_at <= NOW()
                       AND EXISTS (
                           SELECT 1 FROM post_categories pc 
                           WHERE pc.post_id = p.id AND pc.category_id IN ($placeholders)
                       )
                       LIMIT 3");
$stmt->execute(array_merge([$post['id']], $cat_ids));
$related = $stmt->fetchAll();

// Fetch Latest Active Poll
$poll_stmt = $pdo->query("SELECT * FROM polls WHERE status = 'active' AND (starts_at IS NULL OR starts_at <= NOW()) AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY created_at DESC LIMIT 1");
$active_poll = $poll_stmt->fetch();
$poll_has_voted = false;
$poll_options = [];
$poll_total_votes = 0;
if ($active_poll) {
    $voter_id = $_COOKIE['voter_id'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $check_vote = $pdo->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND (browser_id = ? OR ip_address = ?)");
    $check_vote->execute([$active_poll['id'], $voter_id, $ip_address]);
    if ($check_vote->fetch()) {
        $poll_has_voted = true;
    }
    $opt_stmt = $pdo->prepare("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY id ASC");
    $opt_stmt->execute([$active_poll['id']]);
    $poll_options = $opt_stmt->fetchAll();
    foreach ($poll_options as $opt) {
        $poll_total_votes += $opt['votes_count'];
    }
}
?>

<?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
<style>
    /* Hide Google Translate Toolbar */
    .goog-te-banner-frame.skiptranslate, .goog-te-gadget-icon { display: none !important; }
    body { top: 0px !important; }
    .goog-te-gadget-simple {
        background-color: #fff !important;
        border: 1px solid #e2e8f0 !important;
        padding: 6px 12px !important;
        border-radius: 8px !important;
        font-family: inherit !important;
        font-size: 13px !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
    }
    .goog-te-gadget-simple:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;
    }
    .goog-te-menu-value {
        color: #1e293b !important;
        font-weight: 700 !important;
        display: flex !important;
        align-items: center !important;
        gap: 5px !important;
    }
    .goog-te-menu-value span { color: #1e293b !important; }
    .goog-te-menu-value img { display: none !important; }
    .goog-te-menu-value:after {
        content: "\e92e"; /* Feather chevron-down */
        font-family: 'feather' !important;
        font-size: 12px;
        color: #64748b;
    }
    .skiptranslate.goog-te-gadget > div { display: inline-block; }
</style>
<?php
endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<main class="content-container">
    <div class="article-layout">
        <article class="article-page">
            <div style="margin-bottom: 25px;">
                <?php if ($post['external_label'] != 'none'): ?>
                    <span style="background: #000; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: inline-block;"><?php echo $post['external_label']; ?></span>
                <?php
endif; ?>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                    <?php foreach ($post_categories as $cat): ?>
                        <a href="<?php echo BASE_URL; ?>category/<?php echo $cat['slug']; ?>" style="color: <?php echo $cat['color']; ?>; font-weight: 700; font-size: 14px; text-transform: uppercase; background: <?php echo $cat['color']; ?>15; padding: 2px 8px; border-radius: 4px;"><?php echo $cat['name']; ?></a>
                    <?php
endforeach; ?>
                </div>
                
                <h1 style="margin-top: 10px; font-size: 38px; line-height: 1.2; font-weight: 800;"><?php echo $post['title']; ?></h1>
                
                <div class="article-meta-bar" style="display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-top: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 15px 0; flex-wrap: wrap;">
                    <div style="font-size: 14px; color: #555; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="display: flex; align-items: center; gap: 5px;">
                            By <strong style="color: #000;"><?php echo $post['username']; ?></strong>
                        </span>
                        <span style="color: #cbd5e1;">|</span>
                        <span><?php echo format_date($post['created_at']); ?></span>
                        <span style="color: #cbd5e1;">|</span>
                        <span style="display: flex; align-items: center; gap: 4px; color: #64748b; font-weight: 600;">
                            <i data-feather="clock" style="width: 14px;"></i> <?php echo $read_time; ?> min read
                        </span>
                        <?php if (is_logged_in() && is_editor() && can_edit_post($post)): ?>
                            <span style="color: #cbd5e1;">|</span>
                            <a href="<?php echo BASE_URL; ?>admin/post_edit.php?id=<?php echo $post['id']; ?>" class="btn-edit-post-inline" style="display: inline-flex; align-items: center; gap: 5px; background: rgba(248, 153, 29, 0.1); color: var(--primary); padding: 3px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; transition: all 0.2s; text-decoration: none;" onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(248, 153, 29, 0.1)'; this.style.color='var(--primary)';">
                                <i data-feather="edit-2" style="width: 13px; height: 13px;"></i> Edit Post
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="share-buttons-container" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span style="font-size: 13px; font-weight: 700; color: #64748b; margin-right: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Share:</span>
                        
                        <?php
$current_url = urlencode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$share_title = urlencode($post['title']);
?>
                        
                        <!-- Share Buttons -->
                        <a href="https://api.whatsapp.com/send?text=<?php echo $share_title; ?>%20<?php echo $current_url; ?>" target="_blank" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(37, 211, 102, 0.1); color: #25d366; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.background='#25d366'; this.style.color='#fff'; this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(37,211,102,0.3)';" onmouseout="this.style.background='rgba(37, 211, 102, 0.1)'; this.style.color='#25d366'; this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='none';" title="Share on WhatsApp">
                            <i data-feather="message-circle" style="width: 16px; height: 16px;"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(24, 119, 242, 0.1); color: #1877f2; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.background='#1877f2'; this.style.color='#fff'; this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(24,119,242,0.3)';" onmouseout="this.style.background='rgba(24, 119, 242, 0.1)'; this.style.color='#1877f2'; this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='none';" title="Share on Facebook">
                            <i data-feather="facebook" style="width: 16px; height: 16px;"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $current_url; ?>" target="_blank" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(15, 20, 25, 0.1); color: #0f1419; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.background='#0f1419'; this.style.color='#fff'; this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(15,20,25,0.3)';" onmouseout="this.style.background='rgba(15, 20, 25, 0.1)'; this.style.color='#0f1419'; this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='none';" title="Share on X">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                            </svg>
                        </a>
                        <a href="javascript:void(0)" onclick="navigator.share({title: '<?php echo addslashes($post['title']); ?>', url: window.location.href})" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(99, 102, 241, 0.1); color: #6366f1; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.background='#6366f1'; this.style.color='#fff'; this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(99,102,241,0.3)';" onmouseout="this.style.background='rgba(99, 102, 241, 0.1)'; this.style.color='#6366f1'; this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='none';" title="More Share Options">
                            <i data-feather="share-2" style="width: 16px; height: 16px;"></i>
                        </a>

                        <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 5px;"></div>

                        <a href="javascript:void(0)" onclick="downloadEPaper()" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(15, 23, 42, 0.1); color: #0f172a; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.background='#0f172a'; this.style.color='#fff'; this.style.transform='scale(1.1) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(15,23,42,0.3)';" onmouseout="this.style.background='rgba(15, 23, 42, 0.1)'; this.style.color='#0f172a'; this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='none';" title="Download as E-Paper (Image)">
                            <i data-feather="download-cloud" style="width: 16px; height: 16px;"></i>
                        </a>
                    </div>
                </div>

                <!-- Accessibility & Utility Tools -->
                <div class="utility-card" style="margin-top: 15px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; background: var(--primary); padding: 15px 20px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 25px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <?php if (get_setting('tts_enabled', 'yes') == 'yes'): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button id="tts-btn" onclick="toggleVoice()" class="btn" style="padding: 6px 14px; font-size: 13px; background: #ffffff; border: none; display: flex; align-items: center; gap: 8px; font-weight: 800; color: var(--primary); border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">
                                <i data-feather="play" id="tts-icon" style="width: 14px;"></i> <span id="tts-text">Listen Article</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (get_setting('tts_enabled', 'yes') == 'yes' && get_setting('translation_enabled', 'no') == 'yes'): ?>
                            <div style="border-left: 2px solid rgba(255,255,255,0.3); height: 20px; border-radius: 2px;"></div>
                        <?php endif; ?>

                        <?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
                            <div id="google_translate_element"></div>
                            <button onclick="resetLanguage()" title="Reset to Original Language" style="padding: 6px 12px; font-size: 13px; font-weight: 800; background: #ffffff; border: none; border-radius: 8px; cursor: pointer; color: var(--primary); display: flex; align-items: center; gap: 6px; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';">
                                <i data-feather="refresh-ccw" style="width: 13px;"></i> Reset
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Better Follow Section -->
                    <?php 
                        $fb_url = get_setting('facebook_url');
                        $tw_url = get_setting('twitter_url');
                        $ig_url = get_setting('instagram_url');
                        $yt_url = get_setting('youtube_url');
                        if ($fb_url || $tw_url || $ig_url || $yt_url): 
                    ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 13px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 0.5px;">Follow:</span>
                        <?php if ($fb_url): ?>
                        <a href="<?php echo htmlspecialchars($fb_url); ?>" target="_blank" style="color: #1877F2; background: #ffffff; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.transform='scale(1.15) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';" title="Follow on Facebook">
                            <i data-feather="facebook" style="width: 16px; height: 16px;"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($tw_url): ?>
                        <a href="<?php echo htmlspecialchars($tw_url); ?>" target="_blank" style="color: #0f1419; background: #ffffff; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.transform='scale(1.15) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';" title="Follow on X">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if ($ig_url): ?>
                        <a href="<?php echo htmlspecialchars($ig_url); ?>" target="_blank" style="color: #E1306C; background: #ffffff; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.transform='scale(1.15) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';" title="Follow on Instagram">
                            <i data-feather="instagram" style="width: 16px; height: 16px;"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($yt_url): ?>
                        <a href="<?php echo htmlspecialchars($yt_url); ?>" target="_blank" style="color: #FF0000; background: #ffffff; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);" onmouseover="this.style.transform='scale(1.15) translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.2)';" onmouseout="this.style.transform='scale(1) translateY(0)'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)';" title="Subscribe on YouTube">
                            <i data-feather="youtube" style="width: 16px; height: 16px;"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($post['video_url']): ?>
                <div style="margin-bottom: 25px; aspect-ratio: 16/9; width: 100%; background: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <?php
    $video_id = extract_youtube_id($post['video_url']);
    if ($video_id):
?>
                        <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo $video_id; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    <?php
    else: ?>
                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                            <p>Invalid Video URL</p>
                        </div>
                    <?php
    endif; ?>
                </div>
            <?php
elseif ($post['featured_image']): ?>
                <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="<?php echo $post['title']; ?>" class="article-main-img">
            <?php
endif; ?>

            <?php echo display_ad('content_top', $pdo); ?>

            <div class="article-body">
                <?php echo $post['content']; ?>
            </div>

             <?php if (get_setting('likes_dislikes_enabled', 'no') == 'yes'): ?>
                <!-- Like / Dislike Section -->
                <div class="ratings-container" style="display: flex; align-items: center; gap: 20px; margin-top: 30px; padding: 15px 25px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; max-width: max-content;">
                    <span style="font-size: 14px; font-weight: 700; color: #475569;">Rate this article:</span>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="rating-btn like-btn" onclick="rateArticle(<?php echo $post['id']; ?>, 'like')" style="display: flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; background: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 700; color: #1e293b; cursor: pointer; transition: 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='#cbd5e1'">
                            <i data-feather="thumbs-up" style="width: 16px; height: 16px; color: #10b981;"></i>
                            <span id="like-count"><?php echo $post['likes_count'] ?? 0; ?></span>
                        </button>
                        <button type="button" class="rating-btn dislike-btn" onclick="rateArticle(<?php echo $post['id']; ?>, 'dislike')" style="display: flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; background: white; padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 700; color: #1e293b; cursor: pointer; transition: 0.2s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='#cbd5e1'">
                            <i data-feather="thumbs-down" style="width: 16px; height: 16px; color: #ef4444;"></i>
                            <span id="dislike-count"><?php echo $post['dislikes_count'] ?? 0; ?></span>
                        </button>
                    </div>
                    <span id="rating-status" style="font-size: 13px; font-weight: 600; color: #64748b; display: none;"></span>
                </div>
            <?php endif; ?>

            <?php echo display_ad('content_bottom', $pdo); ?>

            <?php if (get_setting('comments_enabled', 'no') == 'yes'): 
                // Fetch approved comments for this post
                $comment_stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
                $comment_stmt->execute([$post['id']]);
                $post_comments = $comment_stmt->fetchAll();
            ?>
                <!-- Comments Section -->
                <div class="comments-section" style="margin-top: 50px; border-top: 2px solid #e2e8f0; padding-top: 40px;">
                    <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 25px; color: var(--primary); text-transform: uppercase; letter-spacing: .06em; display:flex; align-items:center; gap:8px;">
                        <span style="display:inline-block;width:3px;height:18px;background:var(--primary);border-radius:2px;"></span>
                        Comments (<?php echo count($post_comments); ?>)
                    </h3>

                    <!-- Comment Submission Form -->
                    <div style="background: white; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 35px;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top:0; margin-bottom:15px;">Leave a Comment</h4>
                        <form id="comment-form" onsubmit="submitComment(event, <?php echo $post['id']; ?>)">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Your Name</label>
                                    <input type="text" name="name" required placeholder="John Doe" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; border-color:#cbd5e1;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                                </div>
                                <div>
                                    <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Email Address</label>
                                    <input type="email" name="email" required placeholder="john@example.com" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; border-color:#cbd5e1;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'">
                                </div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px;">Comment</label>
                                <textarea name="comment" rows="4" required placeholder="Share your thoughts..." style="width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; font-family:inherit; resize: vertical; border-color:#cbd5e1;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#cbd5e1'"></textarea>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Post Comment</button>
                                <span id="comment-status" style="font-size: 13px; font-weight: 600; color: #10b981; display:none;"></span>
                            </div>
                        </form>
                    </div>

                    <!-- Comments List -->
                    <div class="comments-list" style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (empty($post_comments)): ?>
                            <div style="text-align: center; padding: 30px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; color: #64748b; font-weight: 500;">
                                No comments yet. Be the first to share your thoughts!
                            </div>
                        <?php else: ?>
                            <?php foreach ($post_comments as $cmt): ?>
                                <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display:flex; gap:15px;">
                                    <div style="background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0;">
                                        <?php echo strtoupper(substr($cmt['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                            <span style="font-weight: 700; color: #1e293b; font-size: 14px;"><?php echo htmlspecialchars($cmt['user_name']); ?></span>
                                            <span style="font-size: 12px; color: #94a3b8; font-weight: 500;"><?php echo format_date($cmt['created_at']); ?></span>
                                        </div>
                                        <p style="margin: 0; color: #475569; font-size: 14px; line-height: 1.6; font-weight: 500;"><?php echo nl2br(htmlspecialchars($cmt['comment_text'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 60px; padding-top: 40px; border-top: 2px solid #e2e8f0;">
                <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 20px; color: var(--primary); text-transform: uppercase; letter-spacing: .06em; display:flex; align-items:center; gap:8px;">
                    <span style="display:inline-block;width:3px;height:18px;background:var(--primary);border-radius:2px;"></span>
                    Related Stories
                </h3>
                <div class="news-grid">
                    <?php foreach ($related as $r): ?>
                    <article class="news-card">
                        <a href="<?php echo BASE_URL; ?>article/<?php echo $r['slug']; ?>">
                            <img src="<?php echo get_post_thumbnail($r['featured_image']); ?>" alt="" style="height: 140px;">
                            <h4 style="font-size: 16px;"><?php echo $r['title']; ?></h4>
                        </a>
                    </article>
                    <?php
endforeach; ?>
                </div>
            </div>
        </article>

        <!-- Hidden E-Paper Template for PDF Generation -->
        <div id="epaper-template" style="display: none; padding: 30px; background: #fff; width: 800px; box-sizing: border-box; color: #000; font-family: 'Times New Roman', Times, serif; margin: 0; position: relative; left: 0; top: 0;">
            <!-- Header -->
            <div style="border-bottom: 4px solid #000; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <?php if (get_setting('site_logo')): ?>
                            <img src="<?php echo BASE_URL . 'assets/images/' . get_setting('site_logo'); ?>" style="height: 60px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                        <?php endif; ?>
                        <h1 style="margin: 0; font-size: 36px; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; line-height: 1;"><?php echo SITE_NAME_DYNAMIC; ?></h1>
                    </div>
                    <p style="margin: 5px 0 0; font-size: 14px; font-weight: bold; text-transform: uppercase;">E-Paper Edition</p>
                    <?php if($primary_cat): ?>
                        <p style="margin: 5px 0 0; font-size: 13px; font-weight: bold; color: #ff3c00; text-transform: uppercase; letter-spacing: 1px;"><?php echo htmlspecialchars($primary_cat['name']); ?></p>
                    <?php endif; ?>
                </div>
                <div style="text-align: right; font-size: 14px;">
                    <p style="margin: 0;"><strong>Published:</strong> <?php echo format_date($post['created_at']); ?></p>
                    <p style="margin: 5px 0 0;"><strong>Reporter:</strong> <?php echo $post['username']; ?></p>
                </div>
            </div>
            
            <!-- Article Title -->
            <h1 style="font-size: 42px; line-height: 1.1; margin: 0 0 20px 0; font-weight: bold; text-align: center;"><?php echo $post['title']; ?></h1>
            
            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" style="max-width: 100%; max-height: 400px; border: 1px solid #ccc; padding: 5px;">
                </div>
            <?php endif; ?>
            
            <!-- Content -->
            <div style="font-size: 16px; line-height: 1.6; text-align: justify; columns: 2; column-gap: 40px;">
                <?php echo strip_tags($post['content'], '<p><br><b><strong><i><em>'); ?>
            </div>
            
            <!-- E-Paper Ad (Article Bottom) -->
            <?php 
                $epaper_ad = display_ad('article_bottom', $pdo); 
                if ($epaper_ad): 
            ?>
                <div style="margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 20px; page-break-inside: avoid;">
                    <div style="text-align: center; max-width: 100%;">
                        <h5 style="margin: 0 0 10px 0; font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 2px;">Advertisement</h5>
                        <?php echo $epaper_ad; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer & QR Code -->
            <div style="margin-top: 50px; border-top: 2px solid #000; padding-top: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 14px; color: #555;">
                    <p style="margin: 0;"><strong><?php echo SITE_NAME_DYNAMIC; ?></strong> - Digital News Platform</p>
                    <p style="margin: 5px 0 0;">URL: <?php echo rtrim(BASE_URL, '/'); ?></p>
                </div>
                <div style="text-align: center;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&margin=0" alt="QR Code" style="border: 5px solid #fff; outline: 1px solid #ccc;">
                    <p style="margin: 5px 0 0; font-size: 10px; font-weight: bold;">SCAN TO READ ONLINE</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Ads/Trending -->
        <aside class="article-sidebar">
            <div style="position: sticky; top: 20px; display: flex; flex-direction: column; gap: 30px;">
                
                <!-- Poll Section -->
                <?php if ($active_poll): ?>
                <div class="poll-widget" style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h4 style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                        <div style="background: rgba(99, 102, 241, 0.1); padding: 6px; border-radius: 6px; color: #6366f1;">
                            <i data-feather="pie-chart" style="width: 16px;"></i> 
                        </div>
                        Poll of the Day
                    </h4>
                    <div style="font-size: 17px; font-weight: 700; color: #1e293b; margin-bottom: 20px; line-height: 1.4;">
                        <?php echo htmlspecialchars($active_poll['question']); ?>
                    </div>

                    <div id="poll-container-<?php echo $active_poll['id']; ?>">
                        <?php if ($poll_has_voted): ?>
                            <!-- Results View -->
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($poll_options as $opt): 
                                    $pct = $poll_total_votes > 0 ? round(($opt['votes_count'] / $poll_total_votes) * 100) : 0;
                                ?>
                                <div style="margin-bottom: 5px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 600; color: #475569; margin-bottom: 5px;">
                                        <span><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                        <span><?php echo $pct; ?>%</span>
                                    </div>
                                    <div style="background: #f1f5f9; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: #6366f1; border-radius: 4px; transition: width 1s ease-out;"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top: 20px; font-size: 12px; color: #94a3b8; font-weight: 600; text-align: center;">
                                Total Votes: <?php echo number_format($poll_total_votes); ?>
                            </div>
                        <?php else: ?>
                            <!-- Voting View -->
                            <form id="poll-form-<?php echo $active_poll['id']; ?>" onsubmit="submitPoll(event, <?php echo $active_poll['id']; ?>)">
                                <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
                                    <?php foreach ($poll_options as $opt): ?>
                                    <label style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                                        <input type="radio" name="poll_option" value="<?php echo $opt['id']; ?>" required style="accent-color: #6366f1; width: 16px; height: 16px; cursor: pointer;">
                                        <span style="font-size: 14px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($opt['option_text']); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" style="width: 100%; background: #6366f1; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: background 0.2s;">
                                    Vote Now
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Today's Activity & Events Section -->
                <div style="background: white; border-radius: 16px; padding: 25px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                    <h4 style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; font-size: 16px; font-weight: 800; text-transform: uppercase; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                        <div style="background: #f8fafc; padding: 6px; border-radius: 6px; color: var(--primary);">
                            <i data-feather="calendar" style="width: 16px;"></i> 
                        </div>
                        EVENTS OF THE DAY
                    </h4>
                    
                    <div style="position: relative; padding-left: 20px;">
                        <!-- Vertical Line -->
                        <div style="position: absolute; left: 4px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>

                        <?php
                        $timeline_stmt = $pdo->query("SELECT * FROM timeline WHERE event_date = CURDATE() ORDER BY event_time ASC");
                        $timeline_items = $timeline_stmt->fetchAll();
                        $now = date('H:i');

                        if ($timeline_items):
                            foreach ($timeline_items as $item):
                                $color = '#f59e0b'; // Upcoming
                                if ($item['event_time'] < $now) {
                                    $color = '#10b981'; // Completed
                                }
                                elseif ($item['event_time'] == $now) {
                                    $color = '#ef4444'; // Ongoing / Live
                                }
                        ?>
                        <!-- Timeline Item -->
                        <div style="position: relative; margin-bottom: 25px; padding-left: 10px;">
                            <span style="position: absolute; left: -26px; top: 4px; width: 14px; height: 14px; background: <?php echo $color; ?>; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 0 2px <?php echo $color; ?>40; z-index: 1; <?php echo($item['event_time'] == $now) ? 'animation: pulse 1s infinite;' : ''; ?>"></span>
                            <div style="font-size: 12px; font-weight: 800; color: <?php echo $color; ?>; text-transform: uppercase; margin-bottom: 4px;"><?php echo date("h:i A", strtotime($item['event_time'])); ?></div>
                            <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px;"><?php echo htmlspecialchars($item['event_name']); ?></div>
                            <div style="font-size: 13px; font-weight: 500; color: #64748b; line-height: 1.5;"><?php echo htmlspecialchars($item['description']); ?></div>
                        </div>
                        <?php
                            endforeach;
                        else: ?>
                        <div style="text-align: center; padding: 20px 0;">
                            <i data-feather="clock" style="width: 32px; color: #e2e8f0; margin-bottom: 15px;"></i>
                            <p style="font-size: 14px; color: #94a3b8; font-weight: 500;">No events scheduled for today.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <h4 style="border-bottom: 2px solid #ff3c00; padding-bottom: 5px; margin-bottom: 15px; font-size: 16px; font-weight: 800;">ADVERTISEMENT</h4>
                    <?php echo display_ad('sidebar', $pdo); ?>
                </div>
                
                <div>
                    <h4 style="border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 15px; font-size: 16px; font-weight: 800;">TRENDING</h4>
                    <?php
$trending = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
foreach ($trending as $tp):
?>
                    <a href="<?php echo BASE_URL; ?>article/<?php echo $tp['slug']; ?>" style="display: flex; gap: 10px; text-decoration: none; color: inherit; margin-bottom: 15px;">
                        <img src="<?php echo get_post_thumbnail($tp['featured_image']); ?>" style="width: 80px; height: 50px; border-radius: 4px; object-fit: cover;">
                        <h5 style="font-size: 13px; margin: 0; line-height: 1.3;"><?php echo $tp['title']; ?></h5>
                    </a>
                    <?php
endforeach; ?>
                </div>

                <div style="margin-top: 40px;">
                    <?php echo display_ad('sidebar', $pdo); ?>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include 'includes/public_footer.php'; ?>

<script>
    // 🎤 Text-to-Speech (Voice Reader) Settings
    const ttsLang = <?php echo json_encode(get_setting('tts_lang', 'hi-IN')); ?>;
    const ttsVoiceKeyword = <?php echo json_encode(get_setting('tts_voice_keyword', 'Google')); ?>;
    const ttsRate = parseFloat(<?php echo json_encode(get_setting('tts_rate', '0.90')); ?>);
    const ttsPitch = parseFloat(<?php echo json_encode(get_setting('tts_pitch', '1.1')); ?>);

    let synth = window.speechSynthesis;
    let utterance = null;
    let isSpeaking = false;

    function toggleVoice() {
        if (!synth) {
            alert("⚠️ आपके इस ब्राउज़र में आवाज़ (TTS) फीचर समर्थित नहीं है। कृपया लेख सुनने के लिए इसे Chrome या Safari ब्राउज़र में खोलें।");
            return;
        }

        // Detect in-app browsers (Facebook, Instagram, WebViews)
        const isInAppBrowser = /FBAN|FBAV|Instagram|TikTok|Line|Twitter|WebView/i.test(navigator.userAgent);
        if (isInAppBrowser && !localStorage.getItem('inapp_tts_warned')) {
            alert("💡 सूचना: फेसबुक/इंस्टाग्राम के इन-ऐप ब्राउज़र में मीडिया प्लेबैक प्रतिबंधों के कारण ऑडियो सुनने में समस्या आ सकती है। यदि आवाज़ न आए, तो कृपया ऊपर दाईं तरफ तीन बिंदुओं (...) पर क्लिक करके 'Open in Browser' या 'Chrome/Safari में खोलें' चुनें।");
            localStorage.setItem('inapp_tts_warned', 'true');
        }

        if (!isSpeaking) {
            const bodyText = document.querySelector('.article-body').innerText;
            utterance = new SpeechSynthesisUtterance(bodyText);
            
            // Search for voice based on settings
            let voices = synth.getVoices();
            let preferredVoice = null;
            if (ttsVoiceKeyword.trim() !== "") {
                const keyword = ttsVoiceKeyword.toLowerCase();
                preferredVoice = voices.find(v => 
                    (v.lang === ttsLang || v.lang.startsWith(ttsLang.split('-')[0])) && 
                    v.name.toLowerCase().includes(keyword)
                );
            }
            
            // Fallback to language matching voice
            let fallbackVoice = voices.find(v => v.lang === ttsLang || v.lang.startsWith(ttsLang.split('-')[0]));
            
            let selectedVoice = preferredVoice || fallbackVoice;
            if (selectedVoice) {
                utterance.voice = selectedVoice;
            }
            
            utterance.lang = ttsLang;
            utterance.rate = ttsRate;
            utterance.pitch = ttsPitch;
            
            utterance.onend = () => {
                isSpeaking = false;
                updateTTSUI();
            };

            synth.speak(utterance);
            isSpeaking = true;
        } else {
            synth.cancel();
            isSpeaking = false;
        }
        updateTTSUI();
    }

    function updateTTSUI() {
        const icon = document.getElementById('tts-icon');
        const text = document.getElementById('tts-text');
        if (isSpeaking) {
            icon.setAttribute('data-feather', 'pause');
            text.innerText = 'Stop Reading';
            icon.style.color = '#ef4444';
        } else {
            icon.setAttribute('data-feather', 'play');
            text.innerText = 'Listen';
            icon.style.color = 'inherit';
        }
        feather.replace();
    }


    // 🌐 Google Translate Integration
    function googleTranslateElementInit() {
        <?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: 'hi,en',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
        <?php
endif; ?>
    }

    function resetLanguage() {
        const domain = window.location.hostname;
        // Aggressively clear the googtrans cookie on all possible path/domain combinations
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + domain + ';';
        document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + domain + ';';
        // Also clear for parent domain if subdomain
        const parts = domain.split('.');
        if (parts.length > 2) {
            const parentDomain = parts.slice(1).join('.');
            document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=.' + parentDomain + ';';
        }
        window.location.reload();
    }

    // 📄 E-Paper Image Generation
    function downloadEPaper() {
        const btn = document.querySelector('[title="Download as E-Paper (Image)"]');
        const icon = btn.innerHTML;
        btn.innerHTML = '<i data-feather="loader" style="width: 20px; animation: spin 1s linear infinite;"></i>';
        feather.replace();

        const element = document.getElementById('epaper-template');
        element.style.display = 'block'; // Make it temporarily visible for html2canvas
        
        html2canvas(element, { scale: 2, useCORS: true, scrollX: 0, scrollY: 0 }).then(canvas => {
            const link = document.createElement('a');
            link.download = '<?php echo create_slug(SITE_NAME_DYNAMIC); ?>-<?php echo date("Y-m-d", strtotime($post["published_at"])); ?>-<?php echo $post["id"]; ?>.jpg';
            link.href = canvas.toDataURL('image/jpeg', 0.98);
            link.click();
            
            element.style.display = 'none';
            btn.innerHTML = icon;
            feather.replace();
        }).catch(err => {
            console.error('Image Generation Error:', err);
            element.style.display = 'none';
            btn.innerHTML = icon;
            feather.replace();
            alert('Failed to generate Image. Please try again.');
        });
    }
</script>
<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
<script>
    function submitPoll(e, pollId) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const optionId = formData.get('poll_option');
        if (!optionId) return;

        const btn = form.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = 'Voting...';

        fetch('<?php echo BASE_URL; ?>api/api_poll_vote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'poll_id=' + pollId + '&option_id=' + optionId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.reload(); 
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = 'Vote Now';
            }
        })
        .catch(err => {
            alert('An error occurred. Please try again.');
            btn.disabled = false;
            btn.innerHTML = 'Vote Now';
        });
    }

    function rateArticle(postId, ratingType) {
        const statusEl = document.getElementById('rating-status');
        const likeCountEl = document.getElementById('like-count');
        const dislikeCountEl = document.getElementById('dislike-count');
        
        statusEl.style.display = 'inline';
        statusEl.style.color = '#64748b';
        statusEl.innerText = 'Submitting...';
        
        fetch('<?php echo BASE_URL; ?>ajax_interactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&action=rate&type=${ratingType}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                statusEl.style.color = '#10b981';
                statusEl.innerText = 'Rating saved!';
                if (ratingType === 'like') {
                    likeCountEl.innerText = data.count;
                } else {
                    dislikeCountEl.innerText = data.count;
                }
            } else {
                statusEl.style.color = '#ef4444';
                statusEl.innerText = data.message || 'Error occurred.';
            }
            setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
        })
        .catch(err => {
            statusEl.style.color = '#ef4444';
            statusEl.innerText = 'Failed to save rating.';
            setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
        });
    }

    function submitComment(e, postId) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const statusEl = document.getElementById('comment-status');
        
        const name = form.name.value;
        const email = form.email.value;
        const comment = form.comment.value;
        
        submitBtn.disabled = true;
        statusEl.style.display = 'inline';
        statusEl.style.color = '#64748b';
        statusEl.innerText = 'Submitting comment...';
        
        fetch('<?php echo BASE_URL; ?>ajax_interactions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `post_id=${postId}&action=comment&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&comment=${encodeURIComponent(comment)}`
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            if (data.success) {
                statusEl.style.color = '#10b981';
                statusEl.innerText = data.message;
                form.reset();
                if (data.auto_approved) {
                    setTimeout(() => { location.reload(); }, 1500);
                }
            } else {
                statusEl.style.color = '#ef4444';
                statusEl.innerText = data.message || 'Error submitting comment.';
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            statusEl.style.color = '#ef4444';
            statusEl.innerText = 'Failed to submit comment.';
        });
    }
</script>
<?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<style>
    /* Hide Google Translate top banner & tooltips aggressively */
    iframe.goog-te-banner-frame { display: none !important; }
    .goog-te-banner-frame { display: none !important; }
    .VIpgJd-ZVi9od-ORHb-OEVmcd { display: none !important; }
    body { top: 0 !important; position: static !important; }
    html { top: 0 !important; position: static !important; height: auto !important; }
    .goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
    #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
    .goog-te-gadget { display: none !important; }
</style>
<script>
    setInterval(function() {
        if (document.body.style.top !== '0px') {
            document.body.style.top = '0px';
        }
        document.querySelectorAll('.goog-te-banner-frame, .VIpgJd-ZVi9od-ORHb-OEVmcd').forEach(el => el.style.display = 'none');
    }, 100);
</script>
<?php
endif; ?>
