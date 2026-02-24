<?php
$page_title = "Site Settings";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Helper: extract YouTube video ID from any YT URL format
function getYoutubeId($url) {
    $id = '';
    $url = trim($url);
    if (preg_match('/(?:v=|youtu\.be\/|embed\/|live\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = $m[1];
    }
    return $id;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $to_save = [
        'site_name'              => clean($_POST['site_name']),
        'site_tagline'           => clean($_POST['site_tagline']),
        'contact_email'          => clean($_POST['contact_email']),
        'contact_phone'          => clean($_POST['contact_phone']),
        'whatsapp_number'        => clean($_POST['whatsapp_number']),
        'address'                => clean($_POST['address']),
        'facebook_url'           => clean($_POST['facebook_url']),
        'twitter_url'            => clean($_POST['twitter_url']),
        'instagram_url'          => clean($_POST['instagram_url']),
        'youtube_url'            => clean($_POST['youtube_url']),
        'google_map'             => $_POST['google_map'],
        'theme_color'            => clean($_POST['theme_color']),
        'footer_theme'           => clean($_POST['footer_theme']),
        'header_style'           => clean($_POST['header_style']),
        'show_date_time'         => clean($_POST['show_date_time']),
        'breaking_news_enabled'  => clean($_POST['breaking_news_enabled']),
        // SEO & Analytics
        'meta_description'       => clean($_POST['meta_description']),
        'meta_keywords'          => clean($_POST['meta_keywords']),
        'meta_robots'            => clean($_POST['meta_robots']),
        'og_image_url'           => clean($_POST['og_image_url']),
        'twitter_handle'         => clean($_POST['twitter_handle']),
        'google_analytics_id'    => clean($_POST['google_analytics_id']),
        'google_site_verify'     => clean($_POST['google_site_verify']),
        'bing_site_verify'       => clean($_POST['bing_site_verify']),
        'schema_type'            => clean($_POST['schema_type']),
        'posts_per_page'         => (int)$_POST['posts_per_page'],
        'copyright_text'         => clean($_POST['copyright_text']),
        // Live Stream
        'live_youtube_url'       => clean($_POST['live_youtube_url'] ?? ''),
        'live_youtube_enabled'   => isset($_POST['live_youtube_enabled']) ? '1' : '0',
        'live_stream_title'      => clean($_POST['live_stream_title'] ?? 'Live Stream'),
    ];

    try {
        $pdo->beginTransaction();
        foreach ($to_save as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
        $images = ['site_logo' => 'logo', 'site_favicon' => 'favicon'];
        foreach ($images as $field => $prefix) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                $img_ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $new_img_name = $prefix . "." . $img_ext;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], "../assets/images/" . $new_img_name)) {
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$field, $new_img_name, $new_img_name]);
                }
            }
        }
        $pdo->commit();
        redirect('admin/settings.php', 'Settings updated successfully!');
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "danger";
    }
}

include 'includes/header.php';
?>

<style>
.settings-nav { display: flex; gap: 5px; margin-bottom: 28px; flex-wrap: wrap; }
.settings-nav button {
    padding: 9px 20px; border-radius: 10px; border: 1px solid #e2e8f0;
    background: white; font-size: 13px; font-weight: 600; color: #64748b;
    cursor: pointer; display: flex; align-items: center; gap: 7px; transition: all .2s;
}
.settings-nav button.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 12px rgba(99,102,241,.25); }
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
</style>

<!-- Tab Navigation -->
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
    <button type="button" onclick="showTab('seo')" id="tab-seo">
        <i data-feather="search" style="width:15px;"></i> SEO &amp; Analytics
    </button>
    <button type="button" onclick="showTab('livestream')" id="tab-livestream">
        <i data-feather="youtube" style="width:15px;"></i> Live Stream
    </button>
</div>

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
                        <?php else: ?>
                        <div class="logo-preview" style="justify-content:center; flex-direction: column;">
                            <i data-feather="image" style="width: 30px; color: #cbd5e1;"></i>
                            <span style="font-size: 12px; color: #94a3b8; margin-top: 5px;">No logo uploaded</span>
                        </div>
                        <?php endif; ?>
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
                        <?php else: ?>
                        <div class="logo-preview" style="justify-content:center; flex-direction: column;">
                            <i data-feather="bookmark" style="width: 30px; color: #cbd5e1;"></i>
                            <span style="font-size: 12px; color: #94a3b8; margin-top: 5px;">No favicon uploaded</span>
                        </div>
                        <?php endif; ?>
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
                        ['name'=>'facebook_url',  'label'=>'Facebook',  'icon'=>'facebook',  'color'=>'#1877f2', 'placeholder'=>'https://facebook.com/yourpage'],
                        ['name'=>'twitter_url',   'label'=>'X / Twitter','icon'=>'twitter',   'color'=>'#000',    'placeholder'=>'https://twitter.com/yourhandle'],
                        ['name'=>'instagram_url', 'label'=>'Instagram', 'icon'=>'instagram', 'color'=>'#e1306c', 'placeholder'=>'https://instagram.com/yourprofile'],
                        ['name'=>'youtube_url',   'label'=>'YouTube',   'icon'=>'youtube',   'color'=>'#ff0000', 'placeholder'=>'https://youtube.com/yourchannel'],
                    ];
                    foreach ($socials as $s): ?>
                    <div>
                        <label class="field-label" style="display:flex; align-items: center; gap: 8px;">
                            <i data-feather="<?php echo $s['icon']; ?>" style="width:15px; color: <?php echo $s['color']; ?>;"></i>
                            <?php echo $s['label']; ?> URL
                        </label>
                        <input type="url" name="<?php echo $s['name']; ?>" class="form-control" placeholder="<?php echo $s['placeholder']; ?>" value="<?php echo get_setting($s['name']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════ APPEARANCE ══════════ -->
    <div class="settings-panel" id="panel-appearance">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fff7ed; color: #f59e0b;">
                    <i data-feather="droplet" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Theme Color</h3>
                    <p>Controls the primary accent color across the entire site</p>
                </div>
            </div>
            <div class="settings-card-body">
                <label class="field-label">Pick Primary Color</label>
                <div class="color-preview-row" style="margin-bottom:15px;">
                    <input type="color" name="theme_color" id="theme_color_pick" value="<?php echo get_setting('theme_color', '#ff3c00'); ?>">
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a;">Selected:</div>
                        <div id="color_label" style="font-size: 13px; color: #64748b; font-weight: 600;"><?php echo get_setting('theme_color', '#ff3c00'); ?></div>
                    </div>
                </div>
                <label class="field-label">Quick Presets</label>
                <div class="color-swatches">
                    <?php foreach (['#ff3c00','#6366f1','#0ea5e9','#10b981','#f59e0b','#ec4899','#8b5cf6','#14b8a6','#dc2626','#1d4ed8','#0f172a','#7c3aed'] as $c): ?>
                        <button type="button" class="swatch-btn" style="background:<?php echo $c;?>;" onclick="setColor('<?php echo $c;?>')"></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#f0fdf4; color: #22c55e;">
                    <i data-feather="layout" style="width:18px;"></i>
                </div>
                <div>
                    <h3>Layout & Display Options</h3>
                    <p>Control how your site header, footer, and features look</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <div>
                        <label class="field-label">Footer Theme</label>
                        <div class="toggle-group">
                            <div class="toggle-opt">
                                <input type="radio" name="footer_theme" id="ft_light" value="light" <?php echo get_setting('footer_theme','light') == 'light' ? 'checked' : ''; ?>>
                                <label for="ft_light"><i data-feather="sun" style="width:14px;"></i> Light</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="footer_theme" id="ft_dark" value="dark" <?php echo get_setting('footer_theme') == 'dark' ? 'checked' : ''; ?>>
                                <label for="ft_dark"><i data-feather="moon" style="width:14px;"></i> Dark</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Header Style</label>
                        <div class="toggle-group">
                            <div class="toggle-opt">
                                <input type="radio" name="header_style" id="hs_default" value="default" <?php echo get_setting('header_style','default') == 'default' ? 'checked' : ''; ?>>
                                <label for="hs_default"><i data-feather="minus" style="width:14px;"></i> Standard</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="header_style" id="hs_sticky" value="sticky" <?php echo get_setting('header_style') == 'sticky' ? 'checked' : ''; ?>>
                                <label for="hs_sticky"><i data-feather="anchor" style="width:14px;"></i> Sticky</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Live Date & Time Bar</label>
                        <div class="toggle-group">
                            <div class="toggle-opt">
                                <input type="radio" name="show_date_time" id="dt_yes" value="yes" <?php echo get_setting('show_date_time','yes') == 'yes' ? 'checked' : ''; ?>>
                                <label for="dt_yes"><i data-feather="clock" style="width:14px;"></i> Show</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="show_date_time" id="dt_no" value="no" <?php echo get_setting('show_date_time') == 'no' ? 'checked' : ''; ?>>
                                <label for="dt_no"><i data-feather="eye-off" style="width:14px;"></i> Hide</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Breaking News Banner</label>
                        <div class="toggle-group">
                            <div class="toggle-opt">
                                <input type="radio" name="breaking_news_enabled" id="bn_yes" value="yes" <?php echo get_setting('breaking_news_enabled') == 'yes' ? 'checked' : ''; ?>>
                                <label for="bn_yes"><i data-feather="zap" style="width:14px;"></i> Enable</label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="breaking_news_enabled" id="bn_no" value="no" <?php echo get_setting('breaking_news_enabled','no') == 'no' ? 'checked' : ''; ?>>
                                <label for="bn_no"><i data-feather="zap-off" style="width:14px;"></i> Disable</label>
                            </div>
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
                        <label class="field-label">Google Search Console Verify</label>
                        <div class="social-input-group">
                            <i data-feather="check-circle" class="social-icon" style="width:16px;"></i>
                            <input type="text" name="google_site_verify" class="form-control"
                                placeholder="Verification meta content value" value="<?php echo get_setting('google_site_verify'); ?>">
                        </div>
                        <span class="field-hint">Paste only the content value from the meta tag Google provides.</span>
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
    </div>

    <!-- ══════════ LIVE STREAM ══════════ -->
    <div class="settings-panel" id="panel-livestream">
        <div class="settings-card">
            <div class="settings-card-header">
                <div class="icon" style="background:#fef2f2; color:#dc2626;">
                    <i data-feather="youtube" style="width:18px;"></i>
                </div>
                <div>
                    <h3>YouTube Live Stream</h3>
                    <p>Embed a live YouTube stream on the homepage with an animated LIVE badge</p>
                </div>
            </div>
            <div class="settings-card-body">
                <div class="settings-grid">
                    <!-- Enable Toggle -->
                    <div style="grid-column:1/-1;">
                        <label class="field-label">Show Live Stream on Homepage</label>
                        <div class="toggle-group">
                            <div class="toggle-opt">
                                <input type="radio" name="live_youtube_enabled" id="live_on" value="1" <?php echo get_setting('live_youtube_enabled') === '1' ? 'checked' : ''; ?>>
                                <label for="live_on" style="color:#16a34a; border-color:#bbf7d0;">
                                    <i data-feather="radio" style="width:14px;"></i> Enabled — Show on Homepage
                                </label>
                            </div>
                            <div class="toggle-opt">
                                <input type="radio" name="live_youtube_enabled" id="live_off" value="0" <?php echo get_setting('live_youtube_enabled') !== '1' ? 'checked' : ''; ?>>
                                <label for="live_off">
                                    <i data-feather="eye-off" style="width:14px;"></i> Disabled — Hidden
                                </label>
                            </div>
                        </div>
                        <span class="field-hint">When enabled, the live player appears prominently on the homepage.</span>
                    </div>

                    <!-- YouTube URL -->
                    <div>
                        <label class="field-label">YouTube Live Video URL</label>
                        <div class="social-input-group">
                            <i data-feather="youtube" class="social-icon" style="width:16px; color:#dc2626;"></i>
                            <input type="url" name="live_youtube_url" id="live_youtube_url_input" class="form-control"
                                placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX or Live URL"
                                value="<?php echo htmlspecialchars(get_setting('live_youtube_url')); ?>"
                                oninput="updateLivePreview(this.value)">
                        </div>
                        <span class="field-hint">Paste the YouTube video or live stream URL. Works with both regular videos and live streams.</span>
                    </div>

                    <!-- Stream Title -->
                    <div>
                        <label class="field-label">Stream Section Title</label>
                        <input type="text" name="live_stream_title" class="form-control"
                            placeholder="e.g. Watch Live | News Live" value="<?php echo htmlspecialchars(get_setting('live_stream_title', 'Watch Live')); ?>">
                        <span class="field-hint">Appears as the heading above the embedded player.</span>
                    </div>

                    <!-- Live Preview -->
                    <div style="grid-column:1/-1;" id="live_preview_wrap" <?php echo get_setting('live_youtube_url') ? '' : 'style="display:none;"'; ?>>
                        <label class="field-label">Preview</label>
                        <div style="position:relative; background:#000; border-radius:12px; overflow:hidden; aspect-ratio:16/9; max-width:560px;">
                            <iframe id="live_preview_iframe"
                                src="<?php echo get_setting('live_youtube_url') ? 'https://www.youtube.com/embed/' . getYoutubeId(get_setting('live_youtube_url')) . '?autoplay=0&mute=1' : ''; ?>"
                                style="width:100%;height:100%;border:none;" allowfullscreen></iframe>
                        </div>
                        <span class="field-hint">Save &amp; refresh the homepage to see autoplay in action.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips callout -->
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:16px; display:flex; gap:12px; align-items:start; margin-bottom:20px;">
            <i data-feather="info" style="width:16px; color:#dc2626; flex-shrink:0; margin-top:2px;"></i>
            <div style="font-size:13px; color:#7f1d1d; line-height:1.7;">
                <strong>Tips:</strong> For a YouTube <em>Live stream</em>, use the share URL from "Go Live" or studio. For a regular video, paste the normal watch URL.
                The homepage will show an animated <span style="background:#dc2626;color:#fff;padding:1px 6px;border-radius:4px;font-size:11px;font-weight:700;">● LIVE</span> badge and the player will autoplay muted.
                Autoplay requires the browser to have autoplay enabled (usually works on most modern browsers when muted).
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
</script>

<?php include 'includes/footer.php'; ?>
