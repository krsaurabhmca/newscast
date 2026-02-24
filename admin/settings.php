<?php
$page_title = "Site Settings";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $to_save = [
        'site_name' => clean($_POST['site_name']),
        'contact_email' => clean($_POST['contact_email']),
        'contact_phone' => clean($_POST['contact_phone']),
        'whatsapp_number' => clean($_POST['whatsapp_number']),
        'address' => clean($_POST['address']),
        'facebook_url' => clean($_POST['facebook_url']),
        'twitter_url' => clean($_POST['twitter_url']),
        'instagram_url' => clean($_POST['instagram_url']),
        'youtube_url' => clean($_POST['youtube_url']),
        'google_map' => $_POST['google_map'], 
        'theme_color' => clean($_POST['theme_color']),
        'footer_theme' => clean($_POST['footer_theme']),
        'header_style' => clean($_POST['header_style']),
        'show_date_time' => clean($_POST['show_date_time']),
        'breaking_news_enabled' => clean($_POST['breaking_news_enabled']),
    ];

    try {
        $pdo->beginTransaction();
        foreach ($to_save as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        // Logo & Favicon Upload
        $images = ['site_logo' => 'logo', 'site_favicon' => 'favicon'];
        foreach ($images as $field => $prefix) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
                $img_name = $_FILES[$field]['name'];
                $tmp_name = $_FILES[$field]['tmp_name'];
                $img_ext = pathinfo($img_name, PATHINFO_EXTENSION);
                $new_img_name = $prefix . "." . $img_ext;
                $upload_path = "../assets/images/" . $new_img_name;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$field, $new_img_name, $new_img_name]);
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

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow);">
    <form action="" method="POST" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- General Info -->
            <div class="settings-section">
                <h3 style="margin-bottom: 20px; border-bottom: 2px solid var(--primary); display: inline-block;">General Information</h3>
                <div class="form-group">
                    <label>Channel / Site Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo get_setting('site_name', SITE_NAME); ?>">
                </div>
                <div class="form-group">
                    <label>Site Logo</label>
                    <?php if (get_setting('site_logo')): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/images/<?php echo get_setting('site_logo'); ?>" style="height: 50px; background: #eee; padding: 5px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_logo" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo get_setting('contact_email'); ?>">
                </div>
                <div class="form-group">
                    <label>Site Favicon</label>
                    <?php if (get_setting('site_favicon')): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../assets/images/<?php echo get_setting('site_favicon'); ?>" style="height: 32px; background: #eee; padding: 5px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="site_favicon" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?php echo get_setting('contact_phone'); ?>">
                </div>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?php echo get_setting('whatsapp_number'); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo get_setting('address'); ?></textarea>
                </div>
            </div>

            <!-- Social & Maps -->
            <div class="settings-section">
                <h3 style="margin-bottom: 20px; border-bottom: 2px solid var(--primary); display: inline-block;">Social Links & Maps</h3>
                <div class="form-group">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?php echo get_setting('facebook_url'); ?>">
                </div>
                <div class="form-group">
                    <label>Twitter URL</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?php echo get_setting('twitter_url'); ?>">
                </div>
                <div class="form-group">
                    <label>Instagram URL</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?php echo get_setting('instagram_url'); ?>">
                </div>
                <div class="form-group">
                    <label>YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?php echo get_setting('youtube_url'); ?>">
                </div>
                <div class="form-group">
                    <label>Google Map Embed Code (Iframe)</label>
                    <textarea name="google_map" class="form-control" rows="5" placeholder="Paste <iframe> code here..."><?php echo get_setting('google_map'); ?></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; text-align: right;">
            <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;">
                Save All Settings
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
