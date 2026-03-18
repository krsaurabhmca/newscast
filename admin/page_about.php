<?php
$page_title = "Edit About Page";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_about'])) {
    $about_title = clean($_POST['about_title']);
    // RTF content shouldn't use clean() as it strips some HTML
    $about_content = $_POST['about_content']; // Or sanitize using HTMLPurifier if available, here we assume admin is trusted

    // Save title
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('about_page_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$about_title, $about_title]);

    // Save content
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('about_page_content', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    // we use $_POST direct for rich text
    $stmt->execute([$_POST['about_content'], $_POST['about_content']]);

    $_SESSION['flash_msg'] = "About page updated successfully!";
    $_SESSION['flash_type'] = "success";
    header("Location: page_about.php");
    exit();
}

$site_name = get_setting('site_name', 'NewsCast');
$default_content = '
<section>
    <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">Our Mission</h2>
    <p style="line-height: 1.8; color: #334155; font-size: 16px;">
        Welcome to <strong>' . htmlspecialchars($site_name) . '</strong>, your number one source for all things digital news. We\'re dedicated to giving you the very best of journalism, with a focus on reliability, real-time updates, and local impact.
    </p>
</section>
<section>
    <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">Our Story</h2>
    <p style="line-height: 1.8; color: #334155;">
        Founded in ' . date('Y') . ', ' . htmlspecialchars($site_name) . ' has come a long way from its beginnings. When we first started out, our passion for "Truth in Digital" drove us to start our own news portal.
    </p>
</section>
<section style="background: #f8fafc; padding: 30px; border-radius: 12px; border-left: 5px solid var(--primary);">
    <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 10px;">Why Choose Us?</h2>
    <ul style="line-height: 1.8; color: #475569; padding-left: 20px;">
        <li>Unbiased and independent reporting.</li>
        <li>24/7 breaking news alerts.</li>
        <li>Deep-dive investigations into local issues.</li>
        <li>A user-friendly digital experience.</li>
    </ul>
</section>
<p style="line-height: 1.8; color: #334155; font-size: 16px; text-align: center; margin-top: 20px;">
    We hope you enjoy our news coverage as much as we enjoy offering it to you. If you have any questions or comments, please don\'t hesitate to contact us.
</p>
<p style="text-align: center; font-weight: 700; color: #0f172a; font-size: 18px;">
    Sincerely,<br>
    <span style="color: var(--primary);">The ' . htmlspecialchars($site_name) . ' Team</span>
</p>
';

$about_title_val = get_setting('about_page_title', 'About ' . $site_name);
// We don't clean content from db here to retain formatting in editor
$stmt_get = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'about_page_content'");
$stmt_get->execute();
$about_content_val = $stmt_get->fetchColumn();
if($about_content_val === false) {
    $about_content_val = $default_content;
}

include 'includes/header.php';
?>

<form action="" method="POST" id="aboutForm" style="margin-bottom: 50px;">
    <div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; width: 100%; max-width: 900px;">
        
        <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 25px; display:flex; align-items:center; gap: 8px;">
            <i data-feather="file-text" style="width: 20px;"></i> Editable About Page
        </h3>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="font-weight: 700; color: #1e293b; font-size: 14px; margin-bottom: 8px; display: block;">Page Heading Title (H1) <span style="color:red;">*</span></label>
            <input type="text" name="about_title" class="form-control" style="font-size: 16px; font-weight: 600; padding: 12px;" value="<?php echo htmlspecialchars($about_title_val); ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label style="font-weight: 700; color: #1e293b; font-size: 14px; margin-bottom: 8px; display: block;">Body Content (RTF) <span style="color:red;">*</span></label>
            <div id="editor-container" style="background: white;">
                <div id="editor" style="height: 500px; font-size: 15px;"><?php echo $about_content_val; ?></div>
            </div>
            <input type="hidden" name="about_content" id="about_content">
        </div>

        <!-- Add Title Option: The Quill toolbar has multiple heading options -->
        
        <button type="submit" name="save_about" class="btn btn-primary" style="padding: 12px 30px; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
            <i data-feather="save" style="width: 18px;"></i> Save About Page
        </button>
    </div>
</form>

<script>
    window.addEventListener('load', function() {
        if (typeof Quill !== 'undefined') {
            const quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Write the about us content here...',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }], // Added multiple title/heading options
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image', 'video'],
                        [{ 'align': [] }],
                        ['clean']
                    ]
                }
            });

            const form = document.getElementById('aboutForm');
            const hiddenContent = document.getElementById('about_content');

            form.onsubmit = function() {
                const html = quill.root.innerHTML;
                if (html === '<p><br></p>') {
                    alert('Please write some content.');
                    return false;
                }
                hiddenContent.value = html;
            };
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
