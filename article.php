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
                       WHERE p.slug = ? AND p.status = 'published' AND p.published_at <= NOW()");
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

// SEO Meta Data
$page_title = $post['title'];
$meta_description = $post['meta_description'] ?: $post['excerpt'];
$page_image = $post['featured_image'] ? BASE_URL . "assets/images/posts/" . $post['featured_image'] : "";

include 'includes/public_header.php';

// Fetch Related Posts (sharing any category with current post)
$cat_ids = array_column($post_categories, 'id');
$placeholders = count($cat_ids) > 0 ? str_repeat('?,', count($cat_ids) - 1) . '?' : '0';
$stmt = $pdo->prepare("SELECT DISTINCT p.* FROM posts p 
                       JOIN post_categories pc ON p.id = pc.post_id 
                       WHERE pc.category_id IN ($placeholders) AND p.id != ? AND p.status = 'published' AND p.published_at <= NOW()
                       LIMIT 3");
$stmt->execute(array_merge($cat_ids, [$post['id']]));
$related = $stmt->fetchAll();
?>

<main class="content-container">
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px;">
        <article class="article-page">
            <div style="margin-bottom: 25px;">
                <?php if($post['external_label'] != 'none'): ?>
                    <span style="background: #000; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: inline-block;"><?php echo $post['external_label']; ?></span>
                <?php endif; ?>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                    <?php foreach ($post_categories as $cat): ?>
                        <a href="<?php echo BASE_URL; ?>category/<?php echo $cat['slug']; ?>" style="color: <?php echo $cat['color']; ?>; font-weight: 700; font-size: 14px; text-transform: uppercase; background: <?php echo $cat['color']; ?>15; padding: 2px 8px; border-radius: 4px;"><?php echo $cat['name']; ?></a>
                    <?php endforeach; ?>
                </div>
                
                <h1 style="margin-top: 10px; font-size: 38px; line-height: 1.2; font-weight: 800;"><?php echo $post['title']; ?></h1>
                
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-top: 20px; border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 15px 0;">
                    <div style="font-size: 14px; color: #555;">
                        By <strong style="color: #000;"><?php echo $post['username']; ?></strong> | 
                        <span><?php echo format_date($post['created_at']); ?></span>
                    </div>
                    
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <span style="font-size: 13px; color: #888; margin-right: 15px;"><?php echo $post['views']; ?> views</span>
                        
                        <?php 
                            $current_url = urlencode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
                            $share_title = urlencode($post['title']);
                        ?>
                        
                        <!-- Share Buttons -->
                        <a href="https://api.whatsapp.com/send?text=<?php echo $share_title; ?>%20<?php echo $current_url; ?>" target="_blank" style="color: #25d366;" title="Share on WhatsApp">
                            <i data-feather="message-circle" style="width: 20px;"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $current_url; ?>" target="_blank" style="color: #1877f2;" title="Share on Facebook">
                            <i data-feather="facebook" style="width: 20px;"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?php echo $share_title; ?>&url=<?php echo $current_url; ?>" target="_blank" style="color: #000;" title="Share on X">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                            </svg>
                        </a>
                        <a href="javascript:void(0)" onclick="navigator.share({title: '<?php echo addslashes($post['title']); ?>', url: window.location.href})" style="color: #6366f1;" title="More Share Options">
                            <i data-feather="share-2" style="width: 20px;"></i>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($post['video_url']): ?>
                <div style="margin-bottom: 25px; aspect-ratio: 16/9; width: 100%; background: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <?php 
                        $video_id = extract_youtube_id($post['video_url']);
                        if ($video_id): 
                    ?>
                        <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo $video_id; ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    <?php else: ?>
                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: white;">
                            <p>Invalid Video URL</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($post['featured_image']): ?>
                <img src="<?php echo get_post_thumbnail($post['featured_image']); ?>" alt="<?php echo $post['title']; ?>" class="article-main-img">
            <?php endif; ?>

            <?php echo display_ad('content_top', $pdo); ?>

            <div class="article-body">
                <?php echo $post['content']; ?>
            </div>

            <?php echo display_ad('content_bottom', $pdo); ?>
            
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
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <!-- Sidebar Ads/Trending -->
        <aside class="article-sidebar">
            <div style="position: sticky; top: 20px;">
                <h4 style="border-bottom: 2px solid #ff3c00; padding-bottom: 5px; margin-bottom: 15px; font-size: 16px; font-weight: 800;">ADVERTISEMENT</h4>
                <?php echo display_ad('sidebar', $pdo); ?>
                
                <div style="margin-top: 40px;">
                    <h4 style="border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 15px; font-size: 16px; font-weight: 800;">TRENDING</h4>
                    <?php 
                        $trending = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY views DESC LIMIT 5")->fetchAll();
                        foreach($trending as $tp):
                    ?>
                    <a href="<?php echo BASE_URL; ?>article/<?php echo $tp['slug']; ?>" style="display: flex; gap: 10px; text-decoration: none; color: inherit; margin-bottom: 15px;">
                        <img src="<?php echo get_post_thumbnail($tp['featured_image']); ?>" style="width: 80px; height: 50px; border-radius: 4px; object-fit: cover;">
                        <h5 style="font-size: 13px; margin: 0; line-height: 1.3;"><?php echo $tp['title']; ?></h5>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 40px;">
                    <?php echo display_ad('sidebar', $pdo); ?>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include 'includes/public_footer.php'; ?>
