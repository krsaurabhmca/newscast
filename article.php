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

// Log activity (view)
$user_id = $_SESSION['user_id'] ?? null;
log_activity($pdo, $user_id, $post['id'], 'view');

// Check if bookmarked
$is_bookmarked = false;
if ($user_id) {
    $stmt_book = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt_book->execute([$user_id, $post['id']]);
    $is_bookmarked = $stmt_book->fetch() ? true : false;
}

// Calculate Read Time
$read_time = calculate_reading_time($post['content']);
$page_title = $post['title'];
$meta_description = $post['meta_description'] ?: $post['excerpt'];
$page_image = $post['featured_image'] ? BASE_URL . "assets/images/posts/" . $post['featured_image'] : "";

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
$stmt = $pdo->prepare("SELECT DISTINCT p.* FROM posts p 
                       JOIN post_categories pc ON p.id = pc.post_id 
                       WHERE pc.category_id IN ($placeholders) AND p.id != ? AND p.status = 'published' AND p.published_at <= NOW()
                       LIMIT 3");
$stmt->execute(array_merge($cat_ids, [$post['id']]));
$related = $stmt->fetchAll();
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
<?php endif; ?>

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

                        <a href="javascript:void(0)" id="bookmark-btn" onclick="toggleBookmark(<?php echo $post['id']; ?>)" style="color: <?php echo $is_bookmarked ? '#f59e0b' : '#94a3b8'; ?>;" title="<?php echo $is_bookmarked ? 'Saved' : 'Save for later'; ?>">
                            <i data-feather="bookmark" style="width: 20px; <?php echo $is_bookmarked ? 'fill: #f59e0b;' : ''; ?>"></i>
                        </a>
                    </div>
                </div>

                <!-- Accessibility & Utility Tools -->
                <?php if (get_setting('tts_enabled', 'yes') == 'yes' || get_setting('translation_enabled', 'no') == 'yes'): ?>
                <div style="margin-top: 15px; display: flex; align-items: center; gap: 15px; background: #f8fafc; padding: 10px 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <?php if (get_setting('tts_enabled', 'yes') == 'yes'): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button id="tts-btn" onclick="toggleVoice()" class="btn" style="padding: 5px 12px; font-size: 12px; background: #fff; border: 1px solid #cbd5e1; display: flex; align-items: center; gap: 6px; font-weight: 700; color: #1e293b;">
                            <i data-feather="play" id="tts-icon" style="width: 14px;"></i> <span id="tts-text">Listen</span>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (get_setting('tts_enabled', 'yes') == 'yes' && get_setting('translation_enabled', 'no') == 'yes'): ?>
                        <div style="border-left: 1px solid #cbd5e1; height: 15px;"></div>
                    <?php endif; ?>

                    <?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
                        <div id="google_translate_element"></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
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

<script>
    // 🎤 Text-to-Speech (Voice Reader)
    let synth = window.speechSynthesis;
    let utterance = null;
    let isSpeaking = false;

    function toggleVoice() {
        if (!isSpeaking) {
            const bodyText = document.querySelector('.article-body').innerText;
            utterance = new SpeechSynthesisUtterance(bodyText);
            
            // Priority: Search for Hindi (India) voice
            let voices = synth.getVoices();
            let hindiVoice = voices.find(v => v.lang === 'hi-IN' || v.name.includes('Hindi'));
            
            if (hindiVoice) {
                utterance.voice = hindiVoice;
            }
            utterance.lang = 'hi-IN'; // Essential for proper pronunciation
            utterance.rate = 1.0;
            
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

    // 🔖 Bookmark Logic
    async function toggleBookmark(postId) {
        <?php if (!$user_id): ?>
            alert('Please login to save articles.');
            window.location.href = '<?php echo BASE_URL; ?>login.php';
            return;
        <?php endif; ?>

        try {
            const response = await fetch('<?php echo BASE_URL; ?>api_bookmark.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `post_id=${postId}`
            });
            const res = await response.json();
            
            const btn = document.getElementById('bookmark-btn');
            const icon = btn.querySelector('i');
            
            if (res.status === 'added') {
                btn.style.color = '#f59e0b';
                icon.style.fill = '#f59e0b';
                btn.title = 'Saved';
            } else {
                btn.style.color = '#94a3b8';
                icon.style.fill = 'none';
                btn.title = 'Save for later';
            }
            feather.replace();
        } catch (e) {
            console.error('Bookmark error:', e);
        }
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
        <?php endif; ?>
    }
</script>
<?php if (get_setting('translation_enabled', 'no') == 'yes'): ?>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<?php endif; ?>
