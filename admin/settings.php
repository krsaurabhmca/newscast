<?php
$page_title = "Site Settings";
$hide_floating_widgets = true; // Suppress AI Chat + Feedback drawer on this page
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

if (!file_exists(dirname(__DIR__) . '/sitemap.xml')) {
    generate_sitemap($pdo);
}

if (!file_exists(dirname(__DIR__) . '/robots.txt')) {
    generate_robots_txt();
}

// Helper: extract YouTube video ID from any YT URL format
function getYoutubeId($url)
{
    $id = '';
    $url = trim($url);
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = $m[1];
    }
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (is_demo_account()) {
        redirect('admin/settings.php', 'Action restricted: Demo accounts cannot save settings.', 'danger');
        exit;
    }
    $to_save = [
        'site_name' => clean($_POST['site_name']),
        'site_tagline' => clean($_POST['site_tagline']),
        'contact_email' => clean($_POST['contact_email']),
        'contact_phone' => clean($_POST['contact_phone']),
        'whatsapp_number' => clean($_POST['whatsapp_number']),
        'address' => clean($_POST['address']),
        'facebook_url' => clean($_POST['facebook_url']),
        'twitter_url' => clean($_POST['twitter_url']),
        'instagram_url' => clean($_POST['instagram_url']),
        'youtube_url' => clean($_POST['youtube_url']),
        'whatsapp_channel' => clean($_POST['whatsapp_channel']),
        'footer_custom_link_title' => clean($_POST['footer_custom_link_title'] ?? ''),
        'footer_custom_link_url' => clean($_POST['footer_custom_link_url'] ?? ''),
        'google_map' => $_POST['google_map'],
        'theme_color' => clean($_POST['theme_color']),
        'footer_theme' => clean($_POST['footer_theme']),
        'header_style' => clean($_POST['header_style']),
        'show_date_time' => clean($_POST['show_date_time']),
        'breaking_news_enabled' => clean($_POST['breaking_news_enabled']),
        // SEO & Analytics
        'meta_description' => clean($_POST['meta_description']),
        'meta_keywords' => clean($_POST['meta_keywords']),
        'meta_robots' => clean($_POST['meta_robots']),
        'og_image_url' => clean($_POST['og_image_url']),
        'twitter_handle' => clean($_POST['twitter_handle']),
        'google_analytics_id' => clean($_POST['google_analytics_id']),
        'google_adsense_pub_id' => clean($_POST['google_adsense_pub_id'] ?? ''),
        'google_site_verify' => clean($_POST['google_site_verify']),
        'bing_site_verify' => clean($_POST['bing_site_verify']),
        'schema_type' => clean($_POST['schema_type']),
        'posts_per_page' => (int)$_POST['posts_per_page'],
        'copyright_text' => clean($_POST['copyright_text']),
        // Live Stream
        'live_youtube_url' => clean($_POST['live_youtube_url'] ?? ''),
        'live_youtube_enabled' => isset($_POST['live_youtube_enabled']) ? '1' : '0',
        'live_stream_title' => clean($_POST['live_stream_title'] ?? 'Live Stream'),
        'live_stream_sound' => clean($_POST['live_stream_sound'] ?? '0'),
        'translation_enabled' => clean($_POST['translation_enabled'] ?? 'no'),
        'tts_enabled' => clean($_POST['tts_enabled'] ?? 'no'),
        'tts_lang' => clean($_POST['tts_lang'] ?? 'hi-IN'),
        'tts_voice_keyword' => clean($_POST['tts_voice_keyword'] ?? ''),
        'tts_rate' => (float)($_POST['tts_rate'] ?? 0.90),
        'tts_pitch' => (float)($_POST['tts_pitch'] ?? 1.1),
        'smtp_host' => clean($_POST['smtp_host'] ?? ''),
        'smtp_user' => clean($_POST['smtp_user'] ?? ''),
        'smtp_pass' => $_POST['smtp_pass'] ?? '',
        'smtp_port' => clean($_POST['smtp_port'] ?? '587'),
        'smtp_sender' => clean($_POST['smtp_sender'] ?? ''),
        'email_on_user_create' => clean($_POST['email_on_user_create'] ?? 'no'),
        'onesignal_app_id' => clean($_POST['onesignal_app_id'] ?? ''),
        'onesignal_safari_web_id' => clean($_POST['onesignal_safari_web_id'] ?? ''),
        'collapse_sidebar' => clean($_POST['collapse_sidebar'] ?? 'no'),
        'ebook_magazine_enabled' => clean($_POST['ebook_magazine_enabled'] ?? 'no'),
        'groq_api_key' => clean($_POST['groq_api_key'] ?? ''),
        'whatsapp_floating_btn' => clean($_POST['whatsapp_floating_btn'] ?? 'no'),
        'hide_contact_details' => clean($_POST['hide_contact_details'] ?? 'no'),
        'header_brand_display' => clean($_POST['header_brand_display'] ?? 'both'),
        'comments_enabled' => clean($_POST['comments_enabled'] ?? 'no'),
        'comments_moderation_enabled' => clean($_POST['comments_moderation_enabled'] ?? 'yes'),
        'likes_dislikes_enabled' => clean($_POST['likes_dislikes_enabled'] ?? 'no'),
        'homepage_theme' => clean($_POST['homepage_theme'] ?? 'theme1'),
        'apni_baat_label' => clean($_POST['apni_baat_label'] ?? 'Apni Baat'),
        'google_sitemap_ping_enabled' => clean($_POST['google_sitemap_ping_enabled'] ?? 'no'),
    ];

    try {
        $pdo->beginTransaction();
        foreach ($to_save as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $images = ['site_logo' => 'logo', 'site_favicon' => 'favicon', 'default_og_image' => 'default_og'];
        foreach ($images as $field => $prefix) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                $max_w = ($field === 'site_favicon') ? 256 : 1200;
                $uploaded_file = upload_and_optimize_image($_FILES[$field], "../assets/images/", $prefix . "_", $max_w, 90);
                if ($uploaded_file) {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$field, $uploaded_file, $uploaded_file]);
                }
            }
        }

        // Handle Google Site Verification HTML File Upload
        if (isset($_FILES['google_verification_file']) && $_FILES['google_verification_file']['error'] === 0) {
            $file_name = basename($_FILES['google_verification_file']['name']);
            // Google verification files typically look like google123456789.html
            if (preg_match('/^google[a-zA-Z0-9_-]+\.html$/i', $file_name)) {
                $target_path = dirname(__DIR__) . '/' . $file_name;
                if (move_uploaded_file($_FILES['google_verification_file']['tmp_name'], $target_path)) {
                    // Save the filename in settings so we can display/track it
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                        ->execute(['google_verification_filename', $file_name, $file_name]);
                }
            } else {
                throw new Exception("Invalid Google verification filename. It must start with 'google' and end with '.html' (e.g., google1a2b3c4d5e6f7g8h.html).");
            }
        }
        // Auto-update ads.txt when Google AdSense Publisher ID changes
        $ads_txt_path = dirname(__DIR__) . '/ads.txt';
        if (!empty($to_save['google_adsense_pub_id'])) {
            $pub_id = $to_save['google_adsense_pub_id'];
            $clean_pub_id = str_replace(['ca-', 'pub-'], '', $pub_id);
            $clean_pub_id = 'pub-' . preg_replace('/[^0-9]/', '', $clean_pub_id);
            $ads_txt_content = "google.com, " . $clean_pub_id . ", DIRECT, f08c47fec0942fa0\n";
            @file_put_contents($ads_txt_path, $ads_txt_content);
        }

        $pdo->commit();
        trigger_sitemap_update($pdo);
        redirect('admin/settings.php', 'Settings updated successfully!');
    }
    catch (Exception $e) {
        if ($pdo->inTransaction()) {
            try { $pdo->rollBack(); } catch (Exception $rb_e) {}
        }
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'gone away') !== false) {
            $error_msg = "Database connection lost. This usually happens if the content being saved (like a large logo or setting) exceeds the server limit (max_allowed_packet).";
        }
        $_SESSION['flash_msg'] = "Error: " . $error_msg;
        $_SESSION['flash_type'] = "danger";
    }
}

include 'includes/header.php';
?>

<style>
.settings-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 30px;
    align-items: start;
}

.settings-nav { 
    display: flex; 
    flex-direction: column; 
    gap: 8px; 
    background: white;
    padding: 15px;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    position: sticky;
    top: 90px;
}
.settings-nav button {
    padding: 12px 15px; border-radius: 10px; border: 1px solid transparent;
    background: transparent; font-size: 14px; font-weight: 600; color: #64748b;
    cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all .2s;
    text-align: left;
}
.settings-nav button.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99,102,241,.25); }
.settings-nav button:hover:not(.active) { background: #f8fafc; color: #0f172a; }

.settings-panel { 
    position: absolute;
    visibility: hidden;
    height: 0;
    overflow: hidden;
    pointer-events: none;
    width: 100%;
}
.settings-panel.active { 
    position: relative;
    visibility: visible;
    height: auto;
    overflow: visible;
    pointer-events: auto;
}

.settings-card {
    background: white; border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
    overflow: hidden; margin-bottom: 20px;
}
.settings-card-header {
    padding: 20px 25px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 12px;
}
.settings-card-header .icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
}
.settings-card-header h3 { font-size: 15px; font-weight: 700; margin: 0; color: #0f172a; }
.settings-card-header p  { font-size: 12px; color: #94a3b8; margin: 2px 0 0; }
.settings-card-body { padding: 25px; }

.settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 22px; }

.field-label { font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 7px; display: block; }
.field-hint  { font-size: 11px; color: #94a3b8; margin-top: 5px; }

.toggle-group {
    display: flex; gap: 8px;
}
.toggle-opt { flex: 1; }
.toggle-opt input[type="radio"] { display: none; }
.toggle-opt label {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 10px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    cursor: pointer; font-size: 13px; font-weight: 600; color: #64748b;
    transition: all .2s; width: 100%; text-align: center;
}
.toggle-opt input:checked + label {
    border-color: var(--primary); background: rgba(99,102,241,.07); color: var(--primary);
}

.color-preview-row { display: flex; align-items: center; gap: 15px; }
.color-preview-row input[type="color"] {
    width: 52px; height: 52px; border-radius: 12px;
    border: 2px solid #e2e8f0; padding: 3px; cursor: pointer;
}
.color-swatches { display: flex; gap: 8px; flex-wrap: wrap; }
.swatch-btn { width: 28px; height: 28px; border-radius: 8px; border: 2px solid transparent; cursor: pointer; transition: transform .15s; }
.swatch-btn:hover { transform: scale(1.2); outline: 2px solid #94a3b8; }

.logo-preview {
    background: #f8fafc; border: 1.5px dashed #e2e8f0; border-radius: 12px;
    padding: 15px; display: flex; align-items: center; gap: 15px; margin-bottom: 12px;
}
.social-input-group { position: relative; }
.social-input-group .social-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;
}
.social-input-group .form-control { padding-left: 42px; }

.save-bar {
    background: white; border-top: 1px solid #f1f5f9;
    padding: 18px 25px; display: flex; justify-content: space-between; align-items: center;
    margin-top: 20px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.06);
    position: sticky; bottom: 20px; z-index: 10;
}

/* Alert / Flash Messages */
.alert {
    padding: 14px 20px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-danger, .alert-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-warning {
    background: #fffbeb;
    color: #92400e;
    border: 1px solid #fde68a;
}

.alert-info {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

@media (max-width: 768px) {
    .settings-layout {
        grid-template-columns: 1fr;
    }
    .settings-nav {
        position: static;
        flex-direction: row;
        overflow-x: auto;
        padding: 10px;
    }
    .settings-nav button {
        white-space: nowrap;
    }
}
</style>

<div class="settings-layout">
    <!-- Sidebar Navigation -->
    <div class="settings-nav">
        <button type="button" onclick="showTab('general')" id="tab-general" class="active">
            <i data-feather="info" style="width:15px;"></i> General
        </button>
        <button type="button" onclick="showTab('media')" id="tab-media">
            <i data-feather="image" style="width:15px;"></i> Branding & Media
        </button>
        <button type="button" onclick="showTab('social')" id="tab-social">
            <i data-feather="share-2" style="width:15px;"></i> Social Links
        </button>
        <button type="button" onclick="showTab('appearance')" id="tab-appearance">
            <i data-feather="sliders" style="width:15px;"></i> Appearance
        </button>
        <button type="button" onclick="showTab('accessibility')" id="tab-accessibility">
            <i data-feather="headphones" style="width:15px;"></i> Voice &amp; Translate
        </button>
        <button type="button" onclick="showTab('seo')" id="tab-seo">
            <i data-feather="search" style="width:15px;"></i> SEO &amp; Analytics
        </button>
        <button type="button" onclick="showTab('livestream')" id="tab-livestream">
            <i data-feather="youtube" style="width:15px;"></i> Live Stream
        </button>
        <button type="button" onclick="showTab('email')" id="tab-email">
            <i data-feather="mail" style="width:15px;"></i> Email / SMTP
        </button>
        <button type="button" onclick="showTab('webpush')" id="tab-webpush">
            <i data-feather="bell" style="width:15px;"></i> Web Push
        </button>
        <button type="button" onclick="showTab('ai')" id="tab-ai">
            <i data-feather="cpu" style="width:15px;"></i> AI Integration
        </button>
        <button type="button" onclick="showTab('interactions')" id="tab-interactions">
            <i data-feather="message-square" style="width:15px;"></i> Interactions
        </button>
    </div>

    <!-- Content Area -->
    <div class="settings-content">
        <form action="" method="POST" enctype="multipart/form-data">

    <!-- ══════════ GENERAL ══════════ -->
    <div class="settings-panel active" id="panel-general">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#eef2ff; color: var(--primary);">
                    <i data-feather="globe" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Publication Identity</h3>
                    <p>Core information about your news channel</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">Channel / Site Name</label>
                        <input type="text" name="site_name" class="form-control" placeholder="e.g. NewsCast" value="<?php echo get_setting('site_name', SITE_NAME); ?>">
                        <span class="field-hint">Appears in the header, browser tab, and SEO.</span>
                    </div>
                    <div>
                        <label class="field-label">Site Tagline / Slogan</label>
                        <input type="text" name="site_tagline" class="form-control" placeholder="e.g. Truth. Speed. Trust." value="<?php echo get_setting('site_tagline'); ?>">
                        <span class="field-hint">Short line that appears below the logo or in SEO.</span>
                    </div>
                    <div>
                        <label class="field-label">Contact Email</label>
                        <div class="social-input-group">
                            <i data-feather="mail" class="social-icon" style="width:16px;"></i>
                            <input type="email" name="contact_email" class="form-control" placeholder="editor@newscast.com" value="<?php echo get_setting('contact_email'); ?>">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Contact Phone</label>
                        <div class="social-input-group">
                            <i data-feather="phone" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="contact_phone" class="form-control" placeholder="+91 00000 00000" value="<?php echo get_setting('contact_phone'); ?>">
                        </div>
                    </div>
                    <div>
                        <label class="field-label">WhatsApp Number</label>
                        <div class="social-input-group">
                            <i data-feather="message-circle" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="whatsapp_number" class="form-control" placeholder="919XXXXXXXXX" value="<?php echo get_setting('whatsapp_number'); ?>">
                        </div>
                        <span class="field-hint">Include country code without +. e.g. 919431426600</span>
                    </div>
                    <div style="grid-column: 1/-1;">
                        <label class="field-label">Office / Headquarters Address</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Full address..."><?php echo get_setting('address'); ?></textarea>
                    </div>
                    <div style="grid-column: 1/-1;">
                        <label class="field-label">Google Map Embed</label>
                        <textarea name="google_map" class="form-control" rows="4" placeholder="Paste <iframe> code from Google Maps..."><?php echo get_setting('google_map'); ?></textarea>
                        <span class="field-hint">Go to maps.google.com → Share → Embed a map → Copy HTML</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ BRANDING ══════════ -->
    <div class="settings-panel" id="panel-media">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fdf4ff; color: #9333ea;">
                    <i data-feather="image" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Logo & Favicon</h3>
                    <p>Your brand visuals — used in header and browser tabs</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">Site Logo</label>
                        <?php if (get_setting('site_logo')): ?>
                        <div class="logo-preview">
                            <img src="../assets/images/<?php echo get_setting('site_logo'); ?>" style="height: 45px; object-fit: contain;" alt="Logo">
                            <div>
                                <div style="font-size: 13px; font-weight: 600; color: #334155;">Current Logo</div>
                                <div style="font-size: 11px; color: #94a3b8;">Upload below to replace</div>
                            </div>
                        </div>
                        <?php
else: ?>
                        <div class="logo-preview" style="justify-content:center; flex-direction: column;">
                            <i data-feather="image" style="width: 30px; color: #cbd5e1;"></i>
                            <span style="font-size: 12px; color: #94a3b8; margin-top: 5px;">No logo uploaded</span>
                        </div>
                        <?php
endif; ?>
                        <input type="file" name="site_logo" class="form-control" accept="image/*">
                        <span class="field-hint">Recommended: PNG with transparent background, min. 200px height.</span>
                    </div>
                    <div>
                        <label class="field-label">Site Favicon</label>
                        <?php if (get_setting('site_favicon')): ?>
                        <div class="logo-preview">
                            <img src="../assets/images/<?php echo get_setting('site_favicon'); ?>" style="height: 32px; object-fit: contain;" alt="Favicon">
                            <div>
                                <div style="font-size: 13px; font-weight: 600; color: #334155;">Current Favicon</div>
                                <div style="font-size: 11px; color: #94a3b8;">Upload below to replace</div>
                            </div>
                        </div>
                        <?php
else: ?>
                        <div class="logo-preview" style="justify-content:center; flex-direction: column;">
                            <i data-feather="bookmark" style="width: 30px; color: #cbd5e1;"></i>
                            <span style="font-size: 12px; color: #94a3b8; margin-top: 5px;">No favicon uploaded</span>
                        </div>
                        <?php
endif; ?>
                        <input type="file" name="site_favicon" class="form-control" accept="image/*">
                        <span class="field-hint">Recommended: Square PNG or ICO, 32×32 or 64×64 px.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ SOCIAL ══════════ -->
    <div class="settings-panel" id="panel-social">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#ecfdf5; color: #10b981;">
                    <i data-feather="share-2" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Social Media Links</h3>
                    <p>Used in the footer and social share features</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <?php
$socials = [
    ['name' => 'facebook_url', 'label' => 'Facebook', 'icon' => 'facebook', 'color' => '#1877f2', 'placeholder' => 'https://facebook.com/yourpage'],
    ['name' => 'twitter_url', 'label' => 'X / Twitter', 'icon' => 'twitter', 'color' => '#000', 'placeholder' => 'https://twitter.com/yourhandle'],
    ['name' => 'instagram_url', 'label' => 'Instagram', 'icon' => 'instagram', 'color' => '#e1306c', 'placeholder' => 'https://instagram.com/yourprofile'],
    ['name' => 'youtube_url', 'label' => 'YouTube', 'icon' => 'youtube', 'color' => '#ff0000', 'placeholder' => 'https://youtube.com/yourchannel'],
    ['name' => 'whatsapp_channel', 'label' => 'WhatsApp Channel', 'icon' => 'message-circle', 'color' => '#25D366', 'placeholder' => 'https://whatsapp.com/channel/yourchannel'],
];
foreach ($socials as $s): ?>
                    <div>
                        <label class="field-label" style="display:flex; align-items: center; gap: 8px;">
                            <i data-feather="<?php echo $s['icon']; ?>" style="width:15px; color: <?php echo $s['color']; ?>;"></i>
                            <?php echo $s['label']; ?> URL
                        </label>
                        <input type="url" name="<?php echo $s['name']; ?>" class="form-control" placeholder="<?php echo $s['placeholder']; ?>" value="<?php echo get_setting($s['name']); ?>">
                    </div>
                    <?php
endforeach; ?>
                    <div style="grid-column: 1/-1; padding-top: 15px; border-top: 1px solid #f1f5f9; margin-top: 10px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 15px;">Additional Footer Link (Optional)</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label class="field-label">Link Title (e.g., Mail Login)</label>
                                <input type="text" name="footer_custom_link_title" class="form-control" placeholder="Mail Login" value="<?php echo htmlspecialchars(get_setting('footer_custom_link_title')); ?>">
                            </div>
                            <div>
                                <label class="field-label">Link URL</label>
                                <input type="url" name="footer_custom_link_url" class="form-control" placeholder="https://webmail.example.com" value="<?php echo htmlspecialchars(get_setting('footer_custom_link_url')); ?>">
                            </div>
                        </div>
                    </div>
                    <div style="grid-column: 1/-1; padding-top: 15px; border-top: 1px solid #f1f5f9; margin-top: 10px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 15px;">Default Social Share Image (OG Image)</h4>
                        <div style="display: flex; gap: 20px; align-items: flex-start;">
                            <?php if ($def_og = get_setting('default_og_image')): ?>
                            <img src="../assets/images/<?php echo $def_og; ?>" style="width: 120px; height: 63px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <?php endif; ?>
                            <div style="flex: 1;">
                                <input type="file" name="default_og_image" class="form-control" accept="image/*">
                                <span class="field-hint">Fallback image for links shared on social media when an article doesn't have a featured photo. Recommended: 1200x630px.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ APPEARANCE ══════════ -->
    <!-- ══════════ APPEARANCE ══════════ -->
    <div class="settings-panel" id="panel-appearance">
        <!-- Unified Appearance Card -->
        <div class="settings-card">
            <div class="settings-card-header" style="padding: 20px 25px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon" style="background:#fff7ed; color: #f59e0b;">
                        <i data-feather="droplet" style="width:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Appearance & Theme</h3>
                        <p style="margin: 3px 0 0; font-size: 13px; color: #64748b;">Customize colors and layout features</p>
                    </div>
                </div>
            </div>
            
            <div class="settings-card-body" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
                
                <!-- Color Settings -->
                <div style="background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <input type="color" name="theme_color" id="theme_color_pick" value="<?php echo get_setting('theme_color', '#ff3c00'); ?>" style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid #e2e8f0; padding: 0; cursor: pointer; overflow: hidden; appearance: none; background: transparent;">
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Primary Brand Color</div>
                            <div id="color_label" style="font-size: 12px; color: #64748b; font-weight: 600; font-family: monospace; text-transform: uppercase;"><?php echo get_setting('theme_color', '#ff3c00'); ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 8px;">Quick Presets</div>
                        <div class="color-swatches" style="gap: 6px;">
                            <?php foreach (['#ff3c00', '#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#dc2626', '#1d4ed8', '#0f172a', '#7c3aed'] as $c): ?>
                                <button type="button" class="swatch-btn" style="background:<?php echo $c; ?>; width: 24px; height: 24px; border-radius: 6px;" onclick="setColor('<?php echo $c; ?>')"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Features Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                    
                    <!-- Box 1 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Footer Theme</div>
                            <div style="font-size: 12px; color: #94a3b8;">Light or Dark background</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="footer_theme" id="ft_light" value="light" <?php echo get_setting('footer_theme', 'light') == 'light' ? 'checked' : ''; ?>>
                                <label for="ft_light" style="padding: 6px; font-size: 12px;">Light</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="footer_theme" id="ft_dark" value="dark" <?php echo get_setting('footer_theme') == 'dark' ? 'checked' : ''; ?>>
                                <label for="ft_dark" style="padding: 6px; font-size: 12px;">Dark</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 2 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Header Style</div>
                            <div style="font-size: 12px; color: #94a3b8;">Fixed at top or scrollable</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="header_style" id="hs_default" value="default" <?php echo get_setting('header_style', 'default') == 'default' ? 'checked' : ''; ?>>
                                <label for="hs_default" style="padding: 6px; font-size: 12px;">Scroll</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="header_style" id="hs_sticky" value="sticky" <?php echo get_setting('header_style') == 'sticky' ? 'checked' : ''; ?>>
                                <label for="hs_sticky" style="padding: 6px; font-size: 12px;">Sticky</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 3 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Date & Time Bar</div>
                            <div style="font-size: 12px; color: #94a3b8;">Show live clock in header</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="show_date_time" id="dt_yes" value="yes" <?php echo get_setting('show_date_time', 'yes') == 'yes' ? 'checked' : ''; ?>>
                                <label for="dt_yes" style="padding: 6px; font-size: 12px;">Show</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="show_date_time" id="dt_no" value="no" <?php echo get_setting('show_date_time') == 'no' ? 'checked' : ''; ?>>
                                <label for="dt_no" style="padding: 6px; font-size: 12px;">Hide</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 4 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Breaking News</div>
                            <div style="font-size: 12px; color: #94a3b8;">Red alert banner in header</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="breaking_news_enabled" id="bn_yes" value="yes" <?php echo get_setting('breaking_news_enabled') == 'yes' ? 'checked' : ''; ?>>
                                <label for="bn_yes" style="padding: 6px; font-size: 12px;">On</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="breaking_news_enabled" id="bn_no" value="no" <?php echo get_setting('breaking_news_enabled', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="bn_no" style="padding: 6px; font-size: 12px;">Off</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box Theme Selection -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Homepage Layout Theme</div>
                            <div style="font-size: 12px; color: #94a3b8;">Choose Theme 1 (sidebar list) or Theme 2 (100% width, top horizontal menu)</div>
                        </div>
                        <div class="toggle-group" style="width: 160px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="homepage_theme" id="theme_opt_1" value="theme1" <?php echo get_setting('homepage_theme', 'theme1') == 'theme1' ? 'checked' : ''; ?>>
                                <label for="theme_opt_1" style="padding: 6px; font-size: 12px;">Theme 1</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="homepage_theme" id="theme_opt_2" value="theme2" <?php echo get_setting('homepage_theme') == 'theme2' ? 'checked' : ''; ?>>
                                <label for="theme_opt_2" style="padding: 6px; font-size: 12px;">Theme 2</label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Box 7 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Admin Menu</div>
                            <div style="font-size: 12px; color: #94a3b8;">Sidebar default state</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="collapse_sidebar" id="sb_expanded" value="no" <?php echo get_setting('collapse_sidebar', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="sb_expanded" style="padding: 6px; font-size: 12px;">Show</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="collapse_sidebar" id="sb_collapsed" value="yes" <?php echo get_setting('collapse_sidebar') == 'yes' ? 'checked' : ''; ?>>
                                <label for="sb_collapsed" style="padding: 6px; font-size: 12px;">Hide</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 8 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">E-Paper Module</div>
                            <div style="font-size: 12px; color: #94a3b8;">Show digital editions</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="ebook_magazine_enabled" id="em_yes" value="yes" <?php echo get_setting('ebook_magazine_enabled', 'yes') == 'yes' ? 'checked' : ''; ?>>
                                <label for="em_yes" style="padding: 6px; font-size: 12px;">Show</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="ebook_magazine_enabled" id="em_no" value="no" <?php echo get_setting('ebook_magazine_enabled') == 'no' ? 'checked' : ''; ?>>
                                <label for="em_no" style="padding: 6px; font-size: 12px;">Hide</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 9 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">WhatsApp Widget</div>
                            <div style="font-size: 12px; color: #94a3b8;">Floating WhatsApp button</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="whatsapp_floating_btn" id="wa_yes" value="yes" <?php echo get_setting('whatsapp_floating_btn', 'no') == 'yes' ? 'checked' : ''; ?>>
                                <label for="wa_yes" style="padding: 6px; font-size: 12px;">Show</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="whatsapp_floating_btn" id="wa_no" value="no" <?php echo get_setting('whatsapp_floating_btn', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="wa_no" style="padding: 6px; font-size: 12px;">Hide</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 10 -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Hide Contact Info</div>
                            <div style="font-size: 12px; color: #94a3b8;">Hide email/phone in header</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="hide_contact_details" id="hc_yes" value="yes" <?php echo get_setting('hide_contact_details', 'no') == 'yes' ? 'checked' : ''; ?>>
                                <label for="hc_yes" style="padding: 6px; font-size: 12px;">Hide</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="hide_contact_details" id="hc_no" value="no" <?php echo get_setting('hide_contact_details', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="hc_no" style="padding: 6px; font-size: 12px;">Show</label>
                            </div>
                        </div>
                    </div>

                    <!-- Box 11: Header Brand Display -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Header Brand Display</div>
                            <div style="font-size: 12px; color: #94a3b8;">Choose logo/name display style</div>
                        </div>
                        <div style="width: 130px; display: flex; gap: 5px;">
                            <select name="header_brand_display" class="form-control" style="padding: 6px 10px; font-size: 12px; border-radius: 8px; width: 100%; border: 1px solid #cbd5e1; height: 35px; outline: none; background: #fff;">
                                <option value="both" <?php echo get_setting('header_brand_display', 'both') == 'both' ? 'selected' : ''; ?>>Both</option>
                                <option value="logo" <?php echo get_setting('header_brand_display') == 'logo' ? 'selected' : ''; ?>>Logo Only</option>
                                <option value="name" <?php echo get_setting('header_brand_display') == 'name' ? 'selected' : ''; ?>>Name Only</option>
                            </select>
                        </div>
                    </div>

                    <!-- Apni Baat / Public Submissions Label -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1.5px solid #6366f115; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(to right, #f8fafc, #fff);">
                        <div>
                            <div style="font-weight: 700; color: #1e293b; font-size: 14px; display:flex; align-items:center; gap:8px;">
                                <span style="background:#eef2ff; color:#6366f1; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:800; letter-spacing:.5px;">DYNAMIC</span>
                                Public Submissions Button Label
                            </div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top:3px;">Label shown on the floating article submission button (e.g. "Apni Baat", "अपनी बात", "Share Story")</div>
                        </div>
                        <div style="width: 180px;">
                            <input type="text" name="apni_baat_label" class="form-control" value="<?php echo htmlspecialchars(get_setting('apni_baat_label', 'Apni Baat')); ?>" placeholder="Apni Baat" style="padding: 8px 12px; font-size: 13px; border-radius: 8px; width: 100%; border: 2px solid #6366f130; outline: none; text-align: center; font-weight: 700; color: #4f46e5; background: #f5f3ff;">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- ══════════ VOICE & TRANSLATE ══════════ -->
    <div class="settings-panel" id="panel-accessibility">
        <div class="settings-card">
            <div class="settings-card-header" style="padding: 20px 25px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon" style="background:#eef2ff; color: var(--primary);">
                        <i data-feather="headphones" style="width:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Voice &amp; Translate</h3>
                        <p style="margin: 3px 0 0; font-size: 13px; color: #64748b;">Configure automatic translation and text-to-speech audio reader</p>
                    </div>
                </div>
            </div>
            
            <div class="settings-card-body" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
                <!-- Features Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-bottom: 25px;">
                    
                    <!-- Google Translate toggle -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Google Translate</div>
                            <div style="font-size: 12px; color: #94a3b8;">Language switch button</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="translation_enabled" id="tr_yes" value="yes" <?php echo get_setting('translation_enabled') == 'yes' ? 'checked' : ''; ?>>
                                <label for="tr_yes" style="padding: 6px; font-size: 12px;">On</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="translation_enabled" id="tr_no" value="no" <?php echo get_setting('translation_enabled', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="tr_no" style="padding: 6px; font-size: 12px;">Off</label>
                            </div>
                        </div>
                    </div>

                    <!-- Voice Reader toggle -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Voice Reader</div>
                            <div style="font-size: 12px; color: #94a3b8;">Text-to-speech option</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="tts_enabled" id="tts_yes" value="yes" <?php echo get_setting('tts_enabled', 'yes') == 'yes' ? 'checked' : ''; ?>>
                                <label for="tts_yes" style="padding: 6px; font-size: 12px;">On</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="tts_enabled" id="tts_no" value="no" <?php echo get_setting('tts_enabled') == 'no' ? 'checked' : ''; ?>>
                                <label for="tts_no" style="padding: 6px; font-size: 12px;">Off</label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Voice Reader (TTS) Settings -->
                <div style="padding: 25px; background: white; border: 1px solid #e2e8f0; border-radius: 12px;" id="tts-settings-container">
                    <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="headphones" style="width: 18px; color: var(--primary);"></i>
                        Voice Reader (TTS) Settings
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                        <div>
                            <label class="field-label" style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">TTS Voice Language</label>
                            <input type="text" name="tts_lang" class="form-control" placeholder="e.g. hi-IN" value="<?php echo get_setting('tts_lang', 'hi-IN'); ?>">
                            <span class="field-hint" style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Language code (e.g., hi-IN for Hindi, en-US for English).</span>
                        </div>
                        <div>
                            <label class="field-label" style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">Preferred Voice Keyword</label>
                            <input type="text" name="tts_voice_keyword" class="form-control" placeholder="e.g. female, Google, Natural" value="<?php echo get_setting('tts_voice_keyword', 'Google'); ?>">
                            <span class="field-hint" style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Filter voice name (e.g., Kalpana, Heera, Female). Leave blank for default.</span>
                        </div>
                        <div>
                            <label class="field-label" style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">Speech Speed (Rate)</label>
                            <input type="number" name="tts_rate" class="form-control" step="0.05" min="0.5" max="2.0" value="<?php echo get_setting('tts_rate', '0.90'); ?>">
                            <span class="field-hint" style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Speed multiplier (Default: 0.95, range: 0.5 to 2.0).</span>
                        </div>
                        <div>
                            <label class="field-label" style="font-weight: 700; font-size: 13px; color: #1e293b; display: block; margin-bottom: 8px;">Speech Pitch</label>
                            <input type="number" name="tts_pitch" class="form-control" step="0.1" min="0.5" max="2.0" value="<?php echo get_setting('tts_pitch', '1.1'); ?>">
                            <span class="field-hint" style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Voice pitch tone (Default: 1.0, range: 0.5 to 2.0).</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Save Bar -->
    <!-- ══════════ SEO & ANALYTICS ══════════ -->
    <div class="settings-panel" id="panel-seo">

        <!-- Meta Tags -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#f0fdf4; color: #16a34a;">
                    <i data-feather="search" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Meta Tags &amp; SEO Identity</h3>
                    <p>Used in Google search results, browser tabs, and link previews</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div style="grid-column:1/-1;">
                        <label class="field-label">Site-Wide Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="3" maxlength="160" id="meta_desc_field"
                            placeholder="A brief summary of your news portal for search engines..."><?php echo get_setting('meta_description'); ?></textarea>
                        <div style="display:flex; justify-content:space-between;">
                            <span class="field-hint">Used on the homepage and pages without a specific description. Max 160 chars.</span>
                            <span id="meta_desc_count" style="font-size:11px; color:#94a3b8; margin-top:5px;">0/160</span>
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control"
                            placeholder="news, breaking news, india, latest" value="<?php echo get_setting('meta_keywords'); ?>">
                        <span class="field-hint">Comma-separated. Not a strong ranking factor, but used by some search engines.</span>
                    </div>
                    <div>
                        <label class="field-label">Robots Meta Directive</label>
                        <select name="meta_robots" class="form-control">
                            <?php $robots = get_setting('meta_robots', 'index, follow'); ?>
                            <option value="index, follow" <?php echo $robots == 'index, follow' ? 'selected' : ''; ?>>index, follow &mdash; (Recommended)</option>
                            <option value="noindex, follow" <?php echo $robots == 'noindex, follow' ? 'selected' : ''; ?>>noindex, follow</option>
                            <option value="index, nofollow" <?php echo $robots == 'index, nofollow' ? 'selected' : ''; ?>>index, nofollow</option>
                            <option value="noindex, nofollow" <?php echo $robots == 'noindex, nofollow' ? 'selected' : ''; ?>>noindex, nofollow &mdash; (Hide from Google)</option>
                        </select>
                        <span class="field-hint">Controls how search engines crawl and index your site.</span>
                    </div>
                    <div>
                        <label class="field-label">Schema / Site Type</label>
                        <select name="schema_type" class="form-control">
                            <?php $schema = get_setting('schema_type', 'NewsMediaOrganization'); ?>
                            <option value="NewsMediaOrganization" <?php echo $schema == 'NewsMediaOrganization' ? 'selected' : ''; ?>>News Media Organization</option>
                            <option value="Blog" <?php echo $schema == 'Blog' ? 'selected' : ''; ?>>Blog</option>
                            <option value="Organization" <?php echo $schema == 'Organization' ? 'selected' : ''; ?>>Organization</option>
                            <option value="LocalBusiness" <?php echo $schema == 'LocalBusiness' ? 'selected' : ''; ?>>Local Business</option>
                        </select>
                        <span class="field-hint">Tells Google what type of entity your site is (Schema.org structured data).</span>
                    </div>
                    <div>
                        <label class="field-label">Copyright / Footer Text</label>
                        <input type="text" name="copyright_text" class="form-control"
                            placeholder="&copy; 2025 NewsCast. All Rights Reserved." value="<?php echo get_setting('copyright_text'); ?>">
                        <span class="field-hint">Displayed in the footer. Leave blank to use auto-generated text.</span>
                    </div>
                    <div>
                        <label class="field-label">Posts Per Page</label>
                        <input type="number" name="posts_per_page" class="form-control" min="1" max="50"
                            value="<?php echo get_setting('posts_per_page', 12); ?>">
                        <span class="field-hint">How many articles to show per page on listing pages.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Open Graph & Social Sharing -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fdf4ff; color:#9333ea;">
                    <i data-feather="share" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Open Graph &amp; Social Sharing</h3>
                    <p>Controls how links look when shared on Facebook, WhatsApp, Twitter, etc.</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">Default OG / Share Image URL</label>
                        <input type="url" name="og_image_url" class="form-control"
                            placeholder="https://yourdomain.com/assets/images/share.jpg" value="<?php echo get_setting('og_image_url'); ?>">
                        <span class="field-hint">Shown when article has no featured image. Min 1200×630px for best results.</span>
                    </div>
                    <div>
                        <label class="field-label">Twitter / X Handle</label>
                        <div class="social-input-group">
                            <i data-feather="twitter" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="twitter_handle" class="form-control"
                                placeholder="@newscast" value="<?php echo get_setting('twitter_handle'); ?>">
                        </div>
                        <span class="field-hint">Used in Twitter Card meta tags for attribution.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics & Verification -->
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fff7ed; color:#f59e0b;">
                    <i data-feather="bar-chart-2" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Analytics &amp; Search Console</h3>
                    <p>Connect your site to Google Analytics and webmaster tools</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">Google Analytics 4 Measurement ID</label>
                        <div class="social-input-group">
                            <i data-feather="activity" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="google_analytics_id" class="form-control"
                                placeholder="G-XXXXXXXXXX" value="<?php echo get_setting('google_analytics_id'); ?>">
                        </div>
                        <span class="field-hint">Found in Google Analytics &rarr; Admin &rarr; Data Streams. Starts with G-.</span>
                    </div>
                    <div>
                        <label class="field-label">Google AdSense Publisher ID</label>
                        <div class="social-input-group">
                            <i data-feather="dollar-sign" class="social-icon" style="width:16px; color: #16a34a;"></i>
                            <input type="text" name="google_adsense_pub_id" class="form-control"
                                placeholder="ca-pub-XXXXXXXXXXXXXXXX" value="<?php echo htmlspecialchars(get_setting('google_adsense_pub_id')); ?>">
                        </div>
                        <span class="field-hint">Your AdSense ID. Enables Auto Ads on your site.</span>
                    </div>
                    <div>
                        <label class="field-label">Google Search Console Verify (Meta Tag)</label>
                        <div class="social-input-group">
                            <i data-feather="check-circle" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="google_site_verify" class="form-control"
                                placeholder="Verification meta content value" value="<?php echo get_setting('google_site_verify'); ?>">
                        </div>
                        <span class="field-hint">Option A: Paste only the content value from the meta tag Google provides.</span>
                    </div>
                    <div>
                        <label class="field-label">Google Site Verification File (.html)</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="file" name="google_verification_file" class="form-control" style="font-size: 11px;" accept=".html">
                            <?php 
                            $existing_file = get_setting('google_verification_filename');
                            if ($existing_file && file_exists(dirname(__DIR__) . '/' . $existing_file)): ?>
                                <span style="font-size: 11.5px; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 4px 8px; border-radius: 6px; color: #16a34a; white-space: nowrap; font-weight: 700;">
                                    ✓ <?php echo htmlspecialchars($existing_file); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="field-hint">Option B: Upload the Google HTML verification file directly to root directory.</span>
                    </div>
                    <div>
                        <label class="field-label">Bing Webmaster Verify</label>
                        <div class="social-input-group">
                            <i data-feather="compass" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="bing_site_verify" class="form-control"
                                placeholder="Bing verification code" value="<?php echo get_setting('bing_site_verify'); ?>">
                        </div>
                        <span class="field-hint">From Bing Webmaster Tools &rarr; Settings &rarr; Site Verification.</span>
                    </div>
                </div>

                <!-- Info callout -->
                <div style="margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:15px; display:flex; gap:12px; align-items:start;">
                    <i data-feather="info" style="width:16px; color:#64748b; flex-shrink:0; margin-top:2px;"></i>
                    <div style="font-size:13px; color:#475569; line-height:1.7;">
                        <strong>How it works:</strong> After saving, the Google Analytics tracking code is automatically injected into every page's <code>&lt;head&gt;</code>.
                        The verification meta tags are also auto-added so you don't need to edit any code files manually.
                    </div>
                </div>
            </div>
        </div>

        <!-- XML Sitemap Auto Submit settings -->
        <div class="settings-card" style="margin-top: 20px;">
            <div class="settings-card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon" style="background:#eff6ff; color:#3b82f6;">
                        <i data-feather="map" style="width:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin:0; font-size:16px;">XML Sitemap &amp; Google Auto Update</h3>
                        <p style="margin:3px 0 0; font-size:13px; color:#64748b;">Submit sitemap once, then let the system update and ping Google automatically</p>
                    </div>
                </div>
                <!-- Master Toggle -->
                <div class="toggle-group" style="width: 140px;">
                    <div class="toggle-opt">
                        <input type="radio" name="google_sitemap_ping_enabled" id="ping_on" value="yes" <?php echo get_setting('google_sitemap_ping_enabled', 'yes') === 'yes' ? 'checked' : ''; ?>>
                        <label for="ping_on" style="padding: 8px;">Active</label>
                    </div>
                    <div class="toggle-opt">
                        <input type="radio" name="google_sitemap_ping_enabled" id="ping_off" value="no" <?php echo get_setting('google_sitemap_ping_enabled', 'yes') !== 'yes' ? 'checked' : ''; ?>>
                        <label for="ping_off" style="padding: 8px;">Disabled</label>
                    </div>
                </div>
            </div>
            <div class="settings-card-body">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <label class="field-label">Your Sitemap URL</label>
                        <input type="text" class="form-control" style="background: #f8fafc; font-family: monospace; font-weight: bold; color: #475569;" value="<?php echo BASE_URL; ?>sitemap.xml" readonly>
                        <span class="field-hint">Copy this sitemap link to submit in Google Search Console manually the first time.</span>
                    </div>

                    <!-- Setup Help Guide -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; font-family: 'Inter', sans-serif;">
                        <h4 style="margin: 0 0 10px 0; color: #16a34a; font-size: 14px; font-weight: 800; display: flex; align-items: center; gap: 6px;">
                            <i data-feather="help-circle" style="width: 16px;"></i> Easy Setup Guide
                        </h4>
                        <ol style="margin: 0; padding-left: 20px; font-size: 12.5px; color: #1e293b; line-height: 1.8;">
                            <li>Log in to your <a href="https://search.google.com/search-console" target="_blank" style="color: #3b82f6; font-weight: 700; text-decoration: underline;">Google Search Console</a> dashboard.</li>
                            <li>Select your website property from the top-left dropdown list.</li>
                            <li>Go to the **Sitemaps** option in the left-hand sidebar menu.</li>
                            <li>Under **Add a new sitemap**, type <code>sitemap.xml</code> and click **Submit**.</li>
                            <li><strong>That's it!</strong> Google will fetch the sitemap and discover all your published articles.</li>
                            <li>After this initial setup, every time you publish, edit, or delete any article:
                                <ul style="margin: 5px 0 0; padding-left: 15px; list-style-type: circle;">
                                    <li>The system will automatically rewrite/re-generate your <code>sitemap.xml</code> instantly.</li>
                                    <li>It will automatically ping Google's crawler to notify them of updates.</li>
                                    <li>Google will immediately re-crawl your sitemap to discover and index your new post.</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ LIVE STREAM ══════════ -->
    <div class="settings-panel" id="panel-livestream">
        <div class="settings-card">
            <div class="settings-card-header" style="display: flex; justify-content: space-between; align-items: center; padding: 20px 25px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon" style="background:#fef2f2; color:#dc2626;">
                        <i data-feather="youtube" style="width:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">YouTube Live Stream</h3>
                        <p style="margin: 3px 0 0; font-size: 13px; color: #64748b;">Broadcast live events directly on your homepage</p>
                    </div>
                </div>
                
                <!-- Master Toggle -->
                <div class="toggle-group" style="width: 160px;">
                    <div class="toggle-opt">
                        <input type="radio" name="live_youtube_enabled" id="live_on" value="1" <?php echo get_setting('live_youtube_enabled') === '1' ? 'checked' : ''; ?>>
                        <label for="live_on" style="padding: 8px;"><i data-feather="check" style="width:14px;"></i> On</label>
                    </div>
                    <div class="toggle-opt">
                        <input type="radio" name="live_youtube_enabled" id="live_off" value="0" <?php echo get_setting('live_youtube_enabled') !== '1' ? 'checked' : ''; ?>>
                        <label for="live_off" style="padding: 8px;"><i data-feather="x" style="width:14px;"></i> Off</label>
                    </div>
                </div>
            </div>
            
            <div class="settings-card-body" style="background: #f8fafc; border-top: 1px solid #f1f5f9;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start;">
                    
                    <!-- Left: Configuration -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div>
                            <label class="field-label" style="font-size: 14px;">YouTube URL</label>
                            <div class="social-input-group">
                                <i data-feather="youtube" class="social-icon" style="width:18px; color:#dc2626; left: 16px;"></i>
                                <input type="url" name="live_youtube_url" id="live_youtube_url_input" class="form-control"
                                    style="padding-left: 48px; border-color: #cbd5e1; height: 46px; border-radius: 12px; font-size: 15px;"
                                    placeholder="Paste video or live stream URL..."
                                    value="<?php echo htmlspecialchars(get_setting('live_youtube_url')); ?>"
                                    oninput="updateLivePreview(this.value)">
                            </div>
                        </div>

                        <div>
                            <label class="field-label" style="font-size: 14px;">Section Title</label>
                            <input type="text" name="live_stream_title" class="form-control"
                                style="border-color: #cbd5e1; height: 46px; border-radius: 12px; font-size: 15px;"
                                placeholder="e.g. Watch Live Now" value="<?php echo htmlspecialchars(get_setting('live_stream_title', 'Watch Live')); ?>">
                        </div>

                        <div>
                            <label class="field-label" style="font-size: 14px;">Autoplay Sound</label>
                            <div class="toggle-group" style="max-width: 200px;">
                                <div class="toggle-opt">
                                    <input type="radio" name="live_stream_sound" id="sound_on" value="1" <?php echo get_setting('live_stream_sound', '0') === '1' ? 'checked' : ''; ?>>
                                    <label for="sound_on" style="background: white;"><i data-feather="volume-2" style="width:14px;"></i> On</label>
                                </div>
                                <div class="toggle-opt">
                                    <input type="radio" name="live_stream_sound" id="sound_off" value="0" <?php echo get_setting('live_stream_sound', '0') !== '1' ? 'checked' : ''; ?>>
                                    <label for="sound_off" style="background: white;"><i data-feather="volume-x" style="width:14px;"></i> Muted</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Live Preview -->
                    <div id="live_preview_wrap" <?php echo get_setting('live_youtube_url') ? '' : 'style="display:none;"'; ?>>
                        <div style="background: #000; border-radius: 16px; overflow: hidden; aspect-ratio: 16/9; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 6px solid white;">
                            <iframe id="live_preview_iframe"
                                src="<?php echo get_setting('live_youtube_url') ? 'https://www.youtube.com/embed/' . getYoutubeId(get_setting('live_youtube_url')) . '?autoplay=0&mute=1' : ''; ?>"
                                style="width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
                        </div>
                        <p style="text-align: center; color: #64748b; font-size: 13px; margin-top: 15px; font-weight: 600;"><i data-feather="monitor" style="width:14px; margin-right:5px; vertical-align: middle;"></i> Homepage Preview</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ══════════ EMAIL / SMTP ══════════ -->
    <div class="settings-panel" id="panel-email">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fefce8; color: #ca8a04;">
                    <i data-feather="mail" style="width:18px;"></i>
                </div>
                <div>
                    <h4>Email & SMTP Configuration</h4>
                    <p>Setup your outgoing mail server for password resets and notifications.</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_host')); ?>" placeholder="e.g. smtp.gmail.com">
                    </div>
                    <div>
                        <label class="field-label">SMTP Port</label>
                        <input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_port', '587')); ?>" placeholder="587">
                    </div>
                    <div>
                        <label class="field-label">SMTP Username</label>
                        <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_user')); ?>" placeholder="e.g. info@yourdomain.com">
                    </div>
                    <div>
                        <label class="field-label">SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_pass')); ?>" placeholder="••••••••">
                    </div>
                    <div>
                        <label class="field-label">Sender Name</label>
                        <input type="text" name="smtp_sender" class="form-control" value="<?php echo htmlspecialchars(get_setting('smtp_sender')); ?>" placeholder="e.g. NewsCast Team">
                    </div>
                    <div>
                        <label class="field-label">System Emails</label>
                        <div style="display:flex; align-items:center; gap:10px; margin-top:10px;">
                            <label class="sw-check">
                                <input type="checkbox" name="email_on_user_create" value="yes" <?php echo get_setting('email_on_user_create', 'no') === 'yes' ? 'checked' : ''; ?>>
                                <span class="sw-slider"></span>
                            </label>
                            <span style="font-size:13px; color:#475569;">Send welcome email to new users/reporters</span>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top:24px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0;">
                    <h5 style="margin:0 0 8px; font-size:14px; font-weight:700;">Connection Details</h5>
                    <ul style="margin:0; padding-left:20px; font-size:13px; color:#64748b; line-height:1.6;">
                        <li>Use Port <code>465</code> for SSL or <code>587</code> for TLS (Recommended).</li>
                        <li>For Gmail, you must use an <strong>App Password</strong> if 2FA is enabled.</li>
                        <li>If SMTP is not configured, the system will attempt to use PHP <code>mail()</code> which may go to spam.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ WEB PUSH (ONESIGNAL) ══════════ -->
    <div class="settings-panel" id="panel-webpush">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:rgba(99,102,241,0.1); color: var(--primary);">
                    <i data-feather="bell" style="width:18px;"></i>
                </div>
                <div>
                    <h3>OneSignal Web Push</h3>
                    <p>Configure real-time browser notifications for your readers</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div style="grid-column: 1/-1;">
                        <label class="field-label">OneSignal App ID</label>
                        <input type="text" name="onesignal_app_id" class="form-control" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" value="<?php echo get_setting('onesignal_app_id'); ?>">
                        <span class="field-hint">Your unique OneSignal Application ID. Found in OneSignal Dashboard / Keys & IDs.</span>
                    </div>
                    <div style="grid-column: 1/-1;">
                        <label class="field-label">OneSignal Safari Web ID (Optional)</label>
                        <input type="text" name="onesignal_safari_web_id" class="form-control" placeholder="web.onesignal.auto.xxxxxxxxxxxx" value="<?php echo get_setting('onesignal_safari_web_id'); ?>">
                        <span class="field-hint">Required only if you want to support push notifications on Safari for macOS.</span>
                    </div>
                </div>

                <!-- Setup Guide Callout -->
                <div style="margin-top:25px; background:rgba(99,102,241,0.05); border:1.5px dashed rgba(99,102,241,0.2); border-radius:12px; padding:20px;">
                    <h4 style="font-size:14px; font-weight:800; color:var(--primary); margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                        <i data-feather="help-circle" style="width:16px;"></i> Quick Setup Guide
                    </h4>
                    <ol style="font-size:13px; color:#475569; line-height:1.7; padding-left:20px;">
                        <li>Create a free account at <a href="https://onesignal.com" target="_blank" style="color:var(--primary); font-weight:700;">OneSignal.com</a></li>
                        <li>Add a new app and choose <strong>"Web Push"</strong> as your platform.</li>
                        <li>Configure your site URL and upload your icon.</li>
                        <li>Copy the <strong>App ID</strong> from the "Keys & IDs" section and paste it above.</li>
                        <li>Ensure you have uploaded the SDK files (OneSignalSDKWorker.js) to your root directory.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ AI & AUTOMATION ══════════ -->
    <div class="settings-panel" id="panel-ai">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#f3e8ff; color: #9333ea;">
                    <i data-feather="cpu" style="width:18px;"></i>
                </div>
                <div>
                    <h3>AI Integrations</h3>
                    <p>Connect to powerful AI models for auto-news generation</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div style="grid-column: 1/-1;">
                        <label class="field-label">Groq API Key</label>
                        <div class="social-input-group">
                            <i data-feather="key" class="social-icon" style="width:16px;"></i>
                            <input type="password" name="groq_api_key" class="form-control" placeholder="gsk_xxxxxxxxxxxxxxxx" value="<?php echo htmlspecialchars(get_setting('groq_api_key')); ?>">
                        </div>
                        <span class="field-hint">Required for the AI News module. Get your free key at <a href="https://console.groq.com/" target="_blank" style="color:var(--primary); font-weight: 600;">console.groq.com</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ══════════ INTERACTIONS & COMMENTS ══════════ -->
    <div class="settings-panel" id="panel-interactions">
        <div class="settings-card">
            <div class="settings-card-header" style="padding: 20px 25px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="icon" style="background:#ecfeff; color: #0891b2;">
                        <i data-feather="message-square" style="width:18px;"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Interactions & Comments</h3>
                        <p style="margin: 3px 0 0; font-size: 13px; color: #64748b;">Manage article commenting, moderation, and user reactions</p>
                    </div>
                </div>
            </div>
            
            <div class="settings-card-body" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                    
                    <!-- Option 1: Comments Enabled -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Enable Comments</div>
                            <div style="font-size: 12px; color: #94a3b8;">Allow users to comment on articles</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="comments_enabled" id="ce_yes" value="yes" <?php echo get_setting('comments_enabled', 'no') == 'yes' ? 'checked' : ''; ?>>
                                <label for="ce_yes" style="padding: 6px; font-size: 12px;">Yes</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="comments_enabled" id="ce_no" value="no" <?php echo get_setting('comments_enabled', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="ce_no" style="padding: 6px; font-size: 12px;">No</label>
                            </div>
                        </div>
                    </div>

                    <!-- Option 2: Comments Moderation -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Comment Moderation</div>
                            <div style="font-size: 12px; color: #94a3b8;">Must be approved by admin</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="comments_moderation_enabled" id="cm_yes" value="yes" <?php echo get_setting('comments_moderation_enabled', 'yes') == 'yes' ? 'checked' : ''; ?>>
                                <label for="cm_yes" style="padding: 6px; font-size: 12px;">Yes</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="comments_moderation_enabled" id="cm_no" value="no" <?php echo get_setting('comments_moderation_enabled') == 'no' ? 'checked' : ''; ?>>
                                <label for="cm_no" style="padding: 6px; font-size: 12px;">No</label>
                            </div>
                        </div>
                    </div>

                    <!-- Option 3: Likes/Dislikes -->
                    <div style="background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">Article Ratings</div>
                            <div style="font-size: 12px; color: #94a3b8;">Show Like and Dislike options</div>
                        </div>
                        <div class="toggle-group" style="width: 130px; margin: 0;">
                            <div class="toggle-opt">
                                <input type="radio" name="likes_dislikes_enabled" id="ld_yes" value="yes" <?php echo get_setting('likes_dislikes_enabled', 'no') == 'yes' ? 'checked' : ''; ?>>
                                <label for="ld_yes" style="padding: 6px; font-size: 12px;">Yes</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="likes_dislikes_enabled" id="ld_no" value="no" <?php echo get_setting('likes_dislikes_enabled', 'no') == 'no' ? 'checked' : ''; ?>>
                                <label for="ld_no" style="padding: 6px; font-size: 12px;">No</label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <div class="save-bar">
        <span style="font-size: 13px; color: #64748b;">All changes apply across the entire site instantly.</span>
        <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 35px; font-size: 15px; gap: 10px;">
            <i data-feather="save" style="width: 17px;"></i> Save All Settings
        </button>
    </div>

        </form>
    </div> <!-- /.settings-content -->
</div> <!-- /.settings-layout -->

<script>
function showTab(tab) {
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-nav button').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

const colorPick = document.getElementById('theme_color_pick');
const colorLabel = document.getElementById('color_label');
colorPick.addEventListener('input', () => { colorLabel.textContent = colorPick.value; });

function setColor(hex) {
    colorPick.value = hex;
    colorLabel.textContent = hex;
}

// Live meta desc counter
const metaField = document.getElementById('meta_desc_field');
const metaCount = document.getElementById('meta_desc_count');
if (metaField && metaCount) {
    const update = () => {
        const n = metaField.value.length;
        metaCount.textContent = n + '/160';
        metaCount.style.color = n > 155 ? '#ef4444' : n > 130 ? '#f59e0b' : '#94a3b8';
    };
    metaField.addEventListener('input', update);
    update();
}

// Live preview for YouTube URL
function getYouTubeEmbedId(url) {
    const m = url.match(/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/);
    return m ? m[1] : null;
}
function updateLivePreview(url) {
    const wrap  = document.getElementById('live_preview_wrap');
    const iframe = document.getElementById('live_preview_iframe');
    const id = getYouTubeEmbedId(url);
    if (id) {
        iframe.src = 'https://www.youtube.com/embed/' + id + '?autoplay=0&mute=1';
        wrap.style.display = '';
    } else {
        wrap.style.display = 'none';
        iframe.src = '';
    }
}
// URL Tab activation
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab && document.getElementById('panel-' + tab)) {
        showTab(tab);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
