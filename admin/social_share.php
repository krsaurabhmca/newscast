<?php
$page_title = "Social Media Auto-Share";
include 'includes/header.php';
if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_social_share'])) {
    $keys = ['fb_app_id', 'fb_app_secret', 'fb_page_id', 'fb_page_access_token', 'ig_access_token', 'ig_business_account_id', 'auto_share_facebook', 'auto_share_instagram', 'auto_share_on_publish'];
    try {
        $pdo->beginTransaction();
        foreach ($keys as $key) {
            $val = clean($_POST[$key] ?? '');
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$key, $val, $val]);
        }
        $pdo->commit();
        $_SESSION['flash_msg'] = 'Social share settings saved!';
        $_SESSION['flash_type'] = 'success';
        header("Location: social_share.php");
        exit();
    }
    catch (Exception $e) {
        $pdo->rollBack();
    }
}

// Manual share
$share_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_share'])) {
    $post_id = (int)$_POST['share_post_id'];
    $platforms = $_POST['platforms'] ?? [];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $art = $stmt->fetch();
    if ($art) {
        $art_url = BASE_URL . 'article.php?slug=' . urlencode($art['slug']);
        $art_title = $art['title'];
        $art_image = !empty($art['featured_image']) ? BASE_URL . 'assets/images/posts/' . $art['featured_image'] : '';
        foreach ($platforms as $platform) {
            if ($platform === 'facebook') {
                $r = share_to_facebook($art_url, $art_title);
                $ok = $r['ok'] ? 'ok' : 'err';
                $share_result .= "<div class='shr-result " . $ok . "'>Facebook: " . htmlspecialchars($r['msg']) . "</div>";
            }
            if ($platform === 'instagram') {
                $r = share_to_instagram($art_url, $art_title, $art_image);
                $ok = $r['ok'] ? 'ok' : 'err';
                $share_result .= "<div class='shr-result " . $ok . "'>Instagram: " . htmlspecialchars($r['msg']) . "</div>";
            }
        }
    }
}

function share_to_facebook($url, $title)
{
    $page_id = get_setting('fb_page_id', '');
    $access_token = get_setting('fb_page_access_token', '');
    if (!$page_id || !$access_token)
        return ['ok' => false, 'msg' => 'Not configured. Add Page ID and Access Token in Configuration tab.'];
    $api_url = "https://graph.facebook.com/v22.0/{$page_id}/feed";
    $data = ['message' => $title . "\n\nRead more: " . $url, 'link' => $url, 'access_token' => $access_token];
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($resp, true);
    if (isset($json['id']))
        return ['ok' => true, 'msg' => 'Posted! Post ID: ' . $json['id']];
    return ['ok' => false, 'msg' => $json['error']['message'] ?? 'Unknown error'];
}

function share_to_instagram($url, $title, $image_url = '')
{
    $ig_id = get_setting('ig_business_account_id', '');
    $access_token = get_setting('ig_access_token', '');
    if (!$ig_id || !$access_token)
        return ['ok' => false, 'msg' => 'Instagram not configured. Add Business Account ID and Access Token.'];
    if (!$image_url)
        return ['ok' => false, 'msg' => 'Instagram requires a featured image on the article.'];
    $caption = $title . "\n\n" . $url . "\n\n#news #breakingnews #media";
    $ch = curl_init("https://graph.facebook.com/v22.0/{$ig_id}/media");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['image_url' => $image_url, 'caption' => $caption, 'access_token' => $access_token]), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
    $r = json_decode(curl_exec($ch), true);
    curl_close($ch);
    if (!isset($r['id']))
        return ['ok' => false, 'msg' => 'Container failed: ' . ($r['error']['message'] ?? 'Unknown')];
    $ch2 = curl_init("https://graph.facebook.com/v22.0/{$ig_id}/media_publish");
    curl_setopt_array($ch2, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['creation_id' => $r['id'], 'access_token' => $access_token]), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
    $r2 = json_decode(curl_exec($ch2), true);
    curl_close($ch2);
    if (isset($r2['id']))
        return ['ok' => true, 'msg' => 'Posted to Instagram! ID: ' . $r2['id']];
    return ['ok' => false, 'msg' => $r2['error']['message'] ?? 'Publish failed'];
}

$recent_posts = $pdo->query("SELECT id,title,slug,featured_image,published_at FROM posts WHERE status='published' ORDER BY published_at DESC LIMIT 20")->fetchAll();
?>
<style>
:root {
    --primary: #3b82f6;
    --primary-hover: #2563eb;
    --bg-main: #f8fafc;
    --border-color: #e2e8f0;
    --text-dark: #1e293b;
    --text-muted: #64748b;
    --white: #ffffff;
}

body { background: var(--bg-main); font-family: 'Inter', system-ui, sans-serif; }

.social-app { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }

.page-header { margin-bottom: 25px; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end; }
.page-header h1 { font-size: 24px; font-weight: 700; color: var(--text-dark); margin: 0; }
.page-header p { color: var(--text-muted); font-size: 14px; margin: 5px 0 0; }

.tab-nav { display: flex; gap: 10px; margin-bottom: 25px; }
.tab-btn { 
    background: transparent; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px;
    font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: 0.2s;
    display: flex; align-items: center; gap: 8px; position: relative;
}
.tab-btn:hover { background: #f1f5f9; color: var(--text-dark); }
.tab-btn.active { background: var(--white); border-color: var(--primary); color: var(--primary); box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

.card { background: var(--white); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 20px; }
.card-title { font-size: 16px; font-weight: 700; margin: 0 0 20px; display: flex; align-items: center; gap: 10px; color: var(--text-dark); }
.card-title svg { width: 18px; color: var(--primary); }

.config-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
.form-control { 
    width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px;
    font-size: 14px; transition: 0.2s; background: #fff;
}
.form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.utility-bar { background: #f1f5f9; border-radius: 10px; padding: 15px 20px; margin-bottom: 25px; display: flex; gap: 30px; align-items: center; border: 1px solid var(--border-color); }
.u-item { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.u-label { font-weight: 700; color: var(--text-muted); }
.u-value { font-family: monospace; color: var(--text-dark); background: #fff; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color); }
.copy-link { color: var(--primary); cursor: pointer; font-size: 12px; text-decoration: underline; margin-left: 5px; }

.btn-primary { background: var(--primary); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
.btn-primary:hover { background: var(--primary-hover); }

.broadcast-box { background: #1e293b; border-radius: 12px; padding: 30px; color: #fff; }
.broadcast-box h2 { font-size: 20px; margin: 0 0 10px; }
.broadcast-box p { font-size: 14px; opacity: 0.7; margin-bottom: 20px; }

.toggle-group { display: flex; gap: 15px; margin: 15px 0; }
.toggle-btn { 
    flex: 1; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); padding: 15px; border-radius: 10px;
    display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s; color: #fff;
}
.toggle-btn.active { border-color: #60a5fa; background: rgba(96, 165, 250, 0.1); }

.guide-step { display: flex; gap: 15px; margin-bottom: 20px; }
.step-icon { width: 30px; height: 30px; background: #eff6ff; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
.step-body h4 { margin: 0 0 5px; font-size: 15px; }
.step-body p { margin: 0; font-size: 14px; color: var(--text-muted); line-height: 1.5; }

.s-panel { display: none; }
.s-panel.active { display: block; }

.badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
.badge-success { background: #dcfce7; color: #166534; }
.badge-warning { background: #fef9c3; color: #854d0e; }
</style>

<div class="social-app">
    <div class="page-header">
        <div>
            <h1>Social Share</h1>
            <p>Automate your social reach with ease.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <?php $fb_ok = get_setting('fb_page_id') && get_setting('fb_page_access_token'); ?>
            <span class="badge <?php echo $fb_ok ? 'badge-success' : 'badge-warning'; ?>">
                <i data-feather="<?php echo $fb_ok ? 'check' : 'alert-circle'; ?>" style="width:14px;"></i> Facebook
            </span>
            <?php $ig_ok = get_setting('ig_business_account_id') && get_setting('ig_access_token'); ?>
            <span class="badge <?php echo $ig_ok ? 'badge-success' : 'badge-warning'; ?>">
                <i data-feather="<?php echo $ig_ok ? 'check' : 'alert-circle'; ?>" style="width:14px;"></i> Instagram
            </span>
        </div>
    </div>

    <div class="tab-nav">
        <button class="tab-btn active" onclick="showSTab(this,'config')"><i data-feather="settings" style="width:16px;"></i> Configuration</button>
        <button class="tab-btn" onclick="showSTab(this,'guide-fb')"><i data-feather="book-open" style="width:16px;"></i> Setup Guide</button>
        <button class="tab-btn" onclick="showSTab(this,'manual')"><i data-feather="send" style="width:16px;"></i> Manual Post</button>
    </div>

    <!-- Configuration -->
    <div class="s-panel active" id="spanel-config">
        <div class="utility-bar">
            <div class="u-item">
                <span class="u-label">Domain:</span>
                <span class="u-value" id="val-dom"><?php echo parse_url(BASE_URL, PHP_URL_HOST); ?></span>
                <span class="copy-link" onclick="copyText('val-dom', this)">Copy</span>
            </div>
            <div class="u-item">
                <span class="u-label">Privacy URL:</span>
                <span class="u-value" id="val-pp"><?php echo BASE_URL . 'privacy-policy.php'; ?></span>
                <span class="copy-link" onclick="copyText('val-pp', this)">Copy</span>
            </div>
        </div>

        <form method="POST">
            <div class="config-grid">
                <div class="card">
                    <div class="card-title"> Facebook App Settings</div>
                    <div class="form-group">
                        <label>App ID</label>
                        <input type="text" name="fb_app_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('fb_app_id')); ?>">
                    </div>
                    <div class="form-group">
                        <label>App Secret</label>
                        <input type="password" name="fb_app_secret" class="form-control" value="<?php echo htmlspecialchars(get_setting('fb_app_secret')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Page ID</label>
                        <input type="text" name="fb_page_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('fb_page_id')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Access Token</label>
                        <input type="password" name="fb_page_access_token" class="form-control" value="<?php echo htmlspecialchars(get_setting('fb_page_access_token')); ?>">
                    </div>
                </div>

                <div class="card">
                    <div class="card-title"> Instagram & Automation</div>
                    <div class="form-group">
                        <label>Instagram Business ID</label>
                        <input type="text" name="ig_business_account_id" class="form-control" value="<?php echo htmlspecialchars(get_setting('ig_business_account_id')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Instagram Access Token</label>
                        <input type="password" name="ig_access_token" class="form-control" value="<?php echo htmlspecialchars(get_setting('ig_access_token')); ?>">
                    </div>
                    
                    <div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div class="form-group">
                                <label>Auto Facebook</label>
                                <select name="auto_share_facebook" class="form-control">
                                    <option value="no" <?php echo get_setting('auto_share_facebook') === 'no' ? 'selected' : ''; ?>>Off</option>
                                    <option value="yes" <?php echo get_setting('auto_share_facebook') === 'yes' ? 'selected' : ''; ?>>On</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Auto Instagram</label>
                                <select name="auto_share_instagram" class="form-control">
                                    <option value="no" <?php echo get_setting('auto_share_instagram') === 'no' ? 'selected' : ''; ?>>Off</option>
                                    <option value="yes" <?php echo get_setting('auto_share_instagram') === 'yes' ? 'selected' : ''; ?>>On</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Execution Trigger</label>
                            <select name="auto_share_on_publish" class="form-control">
                                <option value="yes" <?php echo get_setting('auto_share_on_publish', 'yes') === 'yes' ? 'selected' : ''; ?>>Instantly on Publish</option>
                                <option value="no" <?php echo get_setting('auto_share_on_publish') === 'no' ? 'selected' : ''; ?>>Manual Execution Only</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align:right;">
                <button type="submit" name="save_social_share" class="btn btn-primary"><i data-feather="save" style="width:16px;"></i> Save Configurations</button>
            </div>
        </form>
    </div>

    <!-- Guide -->
    <div class="s-panel" id="spanel-guide-fb">
        <div class="card">
            <div class="card-title"><i data-feather="book-open"></i> Full Setup Guide</div>
            
            <div class="guide-step">
                <div class="step-icon">1</div>
                <div class="step-body">
                    <h4>Meta App Creation</h4>
                    <p>Create a <b>Business</b> or <b>Others</b> type app at the Meta portal. This acts as your secure gateway.</p>
                    <a href="https://developers.facebook.com/apps/" target="_blank" class="copy-link" style="display:inline-block; margin-top:5px;">Meta App Dashboard &rarr;</a>
                </div>
            </div>

            <div class="guide-step">
                <div class="step-icon">2</div>
                <div class="step-body">
                    <h4>Required Permissions</h4>
                    <p>Use Graph Explorer to grant these permissions: <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, and <code>instagram_content_publish</code>.</p>
                    <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="copy-link" style="display:inline-block; margin-top:5px;">Open Graph Explorer &rarr;</a>
                </div>
            </div>

            <div class="guide-step">
                <div class="step-icon">3</div>
                <div class="step-body">
                    <h4>Permanent Token Exchange</h4>
                    <p>Take your temporary token to the Token Debugger and exchange it for a <b>Long-lived (60 days)</b> or <b>Never-expire</b> token.</p>
                    <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" class="copy-link" style="display:inline-block; margin-top:5px;">Access Token Debugger &rarr;</a>
                </div>
            </div>

            <div class="guide-step">
                <div class="step-icon">4</div>
                <div class="step-body">
                    <h4>Instagram Handshake</h4>
                    <p>Ensure your Instagram is a <b>Business Account</b> linked to your Facebook Page. Get your IG Business ID using <code>me/accounts?fields=instagram_business_account</code>.</p>
                </div>
            </div>

            <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:12px; padding:15px; margin-top:10px;">
                <p style="color:#991b1b; font-size:13px; margin:0;">
                    <i data-feather="alert-circle" style="width:14px; vertical-align:middle;"></i> <b>Error #200:</b> This usually means your App is in "Development Mode" or your Token lacks <code>pages_manage_posts</code>.
                </p>
            </div>
        </div>
    </div>

    <!-- Manual Share -->
    <div class="s-panel" id="spanel-manual">
        <?php if ($share_result)
    echo '<div class="card" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534; padding:15px; margin-bottom:20px;">' . $share_result . '</div>'; ?>
        
        <div class="card" style="border-top: 4px solid var(--primary);">
            <div class="card-title"><i data-feather="send"></i> Dispatch Payload</div>
            <p style="color:var(--text-muted); font-size:14px; margin-top:-15px; margin-bottom:25px;">Force-trigger a broadcast event to your connected nodes.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label>Select Content Architecture</label>
                    <select name="share_post_id" class="form-control" style="font-weight:600;">
                        <option value="">-- Select Post --</option>
                        <?php foreach ($recent_posts as $ap): ?>
                            <option value="<?php echo $ap['id']; ?>" data-slug="<?php echo htmlspecialchars($ap['slug']); ?>">
                                <?php echo htmlspecialchars(substr($ap['title'], 0, 90)); ?>
                            </option>
                        <?php
endforeach; ?>
                    </select>
                </div>

                <div class="toggle-group">
                    <label class="toggle-btn active" id="m-fb" style="background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8;">
                        <input type="checkbox" name="platforms[]" value="facebook" checked style="display:none;" onchange="updateToggle(this, 'fb')">
                        <i data-feather="facebook"></i> Facebook Node
                    </label>
                    <label class="toggle-btn" id="m-ig" style="color:var(--text-muted);">
                        <input type="checkbox" name="platforms[]" value="instagram" style="display:none;" onchange="updateToggle(this, 'ig')">
                        <i data-feather="instagram"></i> Instagram Edge
                    </label>
                </div>

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:15px; margin-top:30px; border-top:1px solid #f1f5f9; padding-top:20px;">
                    <button type="submit" name="manual_share" class="btn-primary" style="justify-content:center; padding:15px;">
                        <i data-feather="zap"></i> EXECUTE BROADCAST
                    </button>
                    <button type="button" onclick="shareViaDialog()" class="btn-primary" style="background:#fff; color:var(--text-dark); border:1px solid var(--border-color); justify-content:center;">
                        EXTERNAL OVERLAY
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateToggle(input, type) {
    const parent = input.parentElement;
    if (input.checked) {
        parent.classList.add('active');
        parent.style.background = '#eff6ff';
        parent.style.borderColor = '#bfdbfe';
        parent.style.color = '#1d4ed8';
    } else {
        parent.classList.remove('active');
        parent.style.background = 'rgba(0,0,0,0.02)';
        parent.style.borderColor = 'var(--border-color)';
        parent.style.color = 'var(--text-muted)';
    }
}
function showSTab(btn, tab) {
    document.querySelectorAll('.s-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('spanel-' + tab).classList.add('active');
    btn.classList.add('active');
}

function shareViaDialog() {
    const sel = document.querySelector('select[name="share_post_id"]');
    if(!sel.value) { alert("Select article."); return; }
    const url = '<?php echo BASE_URL; ?>article.php?slug=' + sel.options[sel.selectedIndex].getAttribute('data-slug');
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), 'fb', 'width=600,height=400');
}

function copyText(id, btn) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(() => {
        const old = btn.innerText;
        btn.innerText = 'Copied!';
        setTimeout(() => btn.innerText = old, 1500);
    });
}
document.addEventListener('DOMContentLoaded', () => { if(window.feather) feather.replace(); });
</script>
<?php include 'includes/footer.php'; ?>
