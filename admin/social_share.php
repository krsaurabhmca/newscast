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

$recent_posts = $pdo->query("SELECT id,title,featured_image,published_at FROM posts WHERE status='published' ORDER BY published_at DESC LIMIT 20")->fetchAll();
?>
<style>
/* High-End Design System */
:root {
    --primary: #6366f1;
    --primary-light: #818cf8;
    --primary-glow: rgba(99, 102, 241, 0.25);
    --fb-brand: #1877f2;
    --ig-brand: #e1306c;
    --success: #10b981;
    --danger: #ef4444;
    --bg-aura: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 50%), 
               radial-gradient(at 100% 0%, rgba(225, 48, 108, 0.04) 0px, transparent 50%);
    --surface: #ffffff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --radius-lg: 28px;
    --radius-md: 20px;
}

body { background: #fdfdfe var(--bg-aura); background-attachment: fixed; }

/* Global Container Polish */
.social-app { 
    font-family: 'Outfit', 'Inter', sans-serif; 
    color: var(--text-main); 
    max-width: 1280px; margin: 0 auto; padding: 30px;
    animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.page-header { margin-bottom: 50px; display: flex; justify-content: space-between; align-items: center; }
.page-header h1 { 
    font-size: 36px; font-weight: 900; letter-spacing: -1.5px; 
    background: linear-gradient(135deg, #1e1b4b, #6366f1); 
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
    margin-bottom: 5px;
}

/* Polished Tab Navigation */
.tab-nav { 
    background: rgba(241, 245, 249, 0.8); backdrop-filter: blur(8px); padding: 6px; border-radius: 22px; 
    display: inline-flex; gap: 4px; border: 1px solid rgba(226, 232, 240, 0.8);
}
.tab-btn { 
    padding: 14px 30px; border-radius: 18px; border: none; background: transparent; 
    font-size: 14px; font-weight: 700; color: var(--text-muted); cursor: pointer; 
    transition: all 0.4s cubic-bezier(0.2, 1, 0.3, 1); display: flex; align-items: center; gap: 10px;
    position: relative;
}
.tab-btn.active { 
    background: #fff; color: var(--primary); 
    box-shadow: 0 12px 20px -8px rgba(99, 102, 241, 0.15);
    transform: translateY(-2px);
}
.tab-btn::after {
    content: ''; position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%) scaleX(0);
    width: 20px; height: 3px; background: var(--primary); border-radius: 10px; transition: 0.3s;
}
.tab-btn.active::after { transform: translateX(-50%) scaleX(1); }
.tab-btn:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.7); transform: translateY(-1px); }

/* Utility Compliance Console */
.utility-console { 
    background: #fff; border: 1px solid #f1f5f9; border-radius: var(--radius-lg); padding: 30px 40px; 
    display: flex; gap: 60px; margin-bottom: 40px; align-items: center;
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.03);
    position: relative;
}
.utility-console::after {
    content: ''; position: absolute; left: 0; top: 0; right: 0; height: 1px; 
    background: linear-gradient(to right, transparent, #e2e8f0, transparent);
}
.u-item { display: flex; flex-direction: column; gap: 6px; }
.u-label { font-size: 11px; font-weight: 900; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px; }
.u-value-box { display: flex; align-items: center; gap: 15px; }
.u-value { font-family: 'JetBrains Mono', monospace; font-size: 15px; color: var(--text-main); font-weight: 600; padding: 2px 0; }

.btn-copy { 
    border: none; background: #f8fafc; color: #64748b; padding: 7px 16px; border-radius: 12px;
    font-size: 11px; font-weight: 800; cursor: pointer; border: 1.5px solid #e2e8f0;
    transition: 0.3s; display: flex; align-items: center; gap: 8px;
}
.btn-copy:hover { color: var(--primary); border-color: var(--primary); background: #fff; transform: scale(1.05); }
.btn-copy.copied { background: var(--success); color: white; border-color: var(--success); }

/* Step-by-Step Wizard Polish */
.wizard-grid { display: grid; gap: 35px; }
.step-row { display: flex; gap: 30px; align-items: start; }
.step-num { 
    width: 52px; height: 52px; border-radius: 18px; background: #fff; border: 2.5px solid #f1f5f9;
    display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 20px; color: #cbd5e1;
    flex-shrink: 0; transition: 0.5s; position: relative;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
}
.step-num::after { 
    content: ''; position: absolute; width: 3px; height: 80px; background: #f1f5f9; top: 52px; left: 50%; transform: translateX(-50%);
}
.step-row:last-child .step-num::after { display: none; }
.step-row:hover .step-num { border-color: var(--primary); color: var(--primary); box-shadow: 0 15px 30px var(--primary-glow); transform: scale(1.1); }

.step-content-card { 
    background: #fff; border-radius: var(--radius-md); padding: 40px; flex: 1; border: 1px solid #f1f5f9;
    box-shadow: 0 10px 50px -15px rgba(0,0,0,0.04); transition: 0.4s;
    position: relative; overflow: hidden;
}
.step-content-card:hover { transform: translateX(10px); box-shadow: 0 20px 60px -20px rgba(0,0,0,0.06); }
.step-content-card::before {
    content: ''; position: absolute; left: 0; top: 0; width: 4px; height: 100%; background: var(--primary); opacity: 0; transition: 0.3s;
}
.step-row:hover .step-content-card::before { opacity: 1; }

/* Control Center Polish */
.broadcast-center {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); border-radius: 40px; 
    padding: 60px; color: #fff; margin-top: 20px; position: relative; overflow: hidden;
    box-shadow: 0 30px 60px -15px rgba(30, 27, 75, 0.5);
}
.broadcast-center::before {
    content: ''; position: absolute; width: 600px; height: 600px; top: -300px; right: -200px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%); border-radius: 50%;
    z-index: 1; pointer-events: none;
}

.launch-btn {
    background: #fff; color: #1e1b4b; padding: 24px 45px; border-radius: 22px; border: none;
    font-size: 18px; font-weight: 900; cursor: pointer; transition: 0.4s cubic-bezier(0.2, 1, 0.3, 1);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; gap: 12px;
}
.launch-btn:hover { background: #f8fafc; transform: translateY(-5px) scale(1.02); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
.launch-btn:active { transform: translateY(0) scale(0.98); }

.platform-toggle {
    display: flex; align-items: center; gap: 15px; padding: 22px 30px; border-radius: 24px;
    background: rgba(255,255,255,0.04); border: 2px solid rgba(255,255,255,0.08);
    cursor: pointer; transition: 0.4s; flex: 1; min-width: 220px;
}
.platform-toggle:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.15); }
.platform-toggle.active-fb { background: rgba(24, 119, 242, 0.2); border-color: var(--fb-brand); box-shadow: 0 0 25px rgba(24, 119, 242, 0.3); }
.platform-toggle.active-ig { background: rgba(225, 48, 108, 0.2); border-color: var(--ig-brand); box-shadow: 0 0 25px rgba(225, 48, 108, 0.3); }

/* Badge Refinement */
.badge { 
    padding: 8px 16px; border-radius: 12px; font-size: 11px; font-weight: 800; 
    display: inline-flex; align-items: center; gap: 8px; letter-spacing: 0.5px;
}

/* Animation Overlays */
@keyframes slideInUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
.s-panel { display: none; animation: slideInUp 0.6s cubic-bezier(0.22, 1, 0.36, 1) forwards; }
.s-panel.active { display: block; }

.action-loader { display: none; }
.launching .action-text { display: none; }
.launching .action-loader { display: inline-block; animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="social-app">
    <div class="page-header">
        <div class="page-title">
            <h1>Broadcast Console</h1>
            <p>Master your social presence with autonomous publishing logic.</p>
        </div>
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showSTab(this,'config')"><i data-feather="cpu"></i> Config</button>
            <button class="tab-btn" onclick="showSTab(this,'guide-fb')"><i data-feather="facebook"></i> Facebook</button>
            <button class="tab-btn" onclick="showSTab(this,'guide-ig')"><i data-feather="instagram"></i> Instagram</button>
            <button class="tab-btn" onclick="showSTab(this,'manual')"><i data-feather="zap"></i> Broadcast</button>
        </div>
    </div>

    <!-- Utility Bar -->
    <div class="utility-console">
        <div class="u-item" style="flex: 2;">
            <div class="u-label">App Domain</div>
            <div class="u-value-box">
                <span class="u-value" id="dom-val"><?php echo parse_url(BASE_URL, PHP_URL_HOST); ?></span>
                <button class="btn-copy" onclick="copyText('dom-val', this)"><i data-feather="copy" style="width:12px;"></i> Copy</button>
            </div>
        </div>
        <div class="u-item" style="flex: 3;">
            <div class="u-label">Privacy Policy URL</div>
            <div class="u-value-box">
                <span class="u-value" id="pp-val"><?php echo BASE_URL . 'privacy-policy.php'; ?></span>
                <button class="btn-copy" onclick="copyText('pp-val', this)"><i data-feather="copy" style="width:12px;"></i> Copy</button>
            </div>
        </div>
        <div style="flex: 1.5; display:flex; flex-direction:column; gap:8px;">
            <?php $fb_ok = get_setting('fb_page_id') && get_setting('fb_page_access_token'); ?>
            <div class="badge <?php echo $fb_ok ? 'badge-success' : 'badge-warning'; ?>">
                <i data-feather="<?php echo $fb_ok ? 'check' : 'alert-circle'; ?>" style="width:12px;"></i> Facebook <?php echo $fb_ok ? 'Link OK' : 'No Link'; ?>
            </div>
            <?php $ig_ok = get_setting('ig_business_account_id') && get_setting('ig_access_token'); ?>
            <div class="badge <?php echo $ig_ok ? 'badge-success' : 'badge-warning'; ?>">
                <i data-feather="<?php echo $ig_ok ? 'check' : 'alert-circle'; ?>" style="width:12px;"></i> Instagram <?php echo $ig_ok ? 'Link OK' : 'No Link'; ?>
            </div>
        </div>
    </div>

    <!-- CONFIGURATION PANEL -->
    <div class="s-panel active" id="spanel-config">
        <form method="POST">
            <div class="config-grid">
                <!-- Facebook Card -->
                <div class="glass-card">
                    <h3 style="color: var(--fb-brand);"><i data-feather="facebook"></i> Facebook Stack</h3>
                    <div class="input-field">
                        <label>Meta App ID</label>
                        <input type="text" name="fb_app_id" class="input-control" placeholder="Dashboard &rarr; App ID" value="<?php echo htmlspecialchars(get_setting('fb_app_id')); ?>">
                    </div>
                    <div class="input-field">
                        <label>App Secret</label>
                        <input type="password" name="fb_app_secret" class="input-control" placeholder="••••••••" value="<?php echo htmlspecialchars(get_setting('fb_app_secret')); ?>">
                    </div>
                    <div class="input-field">
                        <label>Page ID</label>
                        <input type="text" name="fb_page_id" class="input-control" placeholder="About &rarr; Page ID" value="<?php echo htmlspecialchars(get_setting('fb_page_id')); ?>">
                    </div>
                    <div class="input-field">
                        <label>System Access Token</label>
                        <input type="password" name="fb_page_access_token" class="input-control" placeholder="EAA..." value="<?php echo htmlspecialchars(get_setting('fb_page_access_token')); ?>">
                    </div>
                </div>

                <!-- Instagram Card -->
                <div class="glass-card">
                    <h3 style="color: var(--ig-brand);"><i data-feather="instagram"></i> Instagram Stack</h3>
                    <div class="input-field">
                        <label>IG Business ID</label>
                        <input type="text" name="ig_business_account_id" class="input-control" placeholder="178..." value="<?php echo htmlspecialchars(get_setting('ig_business_account_id')); ?>">
                    </div>
                    <div class="input-field" style="margin-bottom:45px;">
                        <label>IG Access Token</label>
                        <input type="password" name="ig_access_token" class="input-control" placeholder="Same as Facebook Page Token" value="<?php echo htmlspecialchars(get_setting('ig_access_token')); ?>">
                    </div>

                    <h3><i data-feather="settings"></i> Automation</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div class="input-field">
                            <label>FB Sync</label>
                            <select name="auto_share_facebook" class="input-control">
                                <option value="no" <?php echo get_setting('auto_share_facebook', 'no') === 'no' ? 'selected' : ''; ?>>OFF</option>
                                <option value="yes" <?php echo get_setting('auto_share_facebook') === 'yes' ? 'selected' : ''; ?>>ON</option>
                            </select>
                        </div>
                        <div class="input-field">
                            <label>IG Sync</label>
                            <select name="auto_share_instagram" class="input-control">
                                <option value="no" <?php echo get_setting('auto_share_instagram', 'no') === 'no' ? 'selected' : ''; ?>>Disabled</option>
                                <option value="yes" <?php echo get_setting('auto_share_instagram') === 'yes' ? 'selected' : ''; ?>>Enabled (Requires Image)</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-field">
                        <label>Trigger Behavior</label>
                        <select name="auto_share_on_publish" class="input-control">
                            <option value="yes" <?php echo get_setting('auto_share_on_publish', 'yes') === 'yes' ? 'selected' : ''; ?>>Instantly on Publish</option>
                            <option value="no" <?php echo get_setting('auto_share_on_publish') === 'no' ? 'selected' : ''; ?>>Manual Execution Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Diagnostic Console -->
            <div style="margin-top:30px; background:#0f172a; border-radius:24px; padding:30px; border:1px solid #1e293b; box-shadow:0 20px 50px rgba(0,0,0,0.15);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:10px; height:10px; background:#10b981; border-radius:50%; box-shadow:0 0 10px #10b981;"></div>
                        <span style="color:#94a3b8; font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:1px;">System Health Diagnostics</span>
                    </div>
                    <span style="color:#475569; font-size:11px; font-family:monospace;">v2.4.0-STABLE</span>
                </div>
                <div style="font-family:'JetBrains Mono',monospace; font-size:13px; color:#e2e8f0; line-height:1.8;">
                    <div style="color:#6366f1;">> Initializing platform handshake...</div>
                    <div style="color:<?php echo $fb_ok ? '#10b981' : '#f59e0b'; ?>">[FB] Node Connection: <?php echo $fb_ok ? 'ACTIVE' : 'IDLE'; ?></div>
                    <div style="color:<?php echo $ig_ok ? '#10b981' : '#f59e0b'; ?>">[IG] Edge Bridge: <?php echo $ig_ok ? 'ACTIVE' : 'IDLE'; ?></div>
                    <div style="color:#475569;">> Listening for publishing triggers on port 443...</div>
                </div>
            </div>

            <div style="text-align:right; margin-top:30px;">
                <button type="submit" name="save_social_share" class="btn btn-primary" style="padding:16px 60px; border-radius:18px; font-weight:900; box-shadow:0 10px 40px rgba(99,102,241,0.3);">
                    Save Environment Variables
                </button>
            </div>
        </form>
    </div>

    <!-- FACEBOOK GUIDE -->
    <div class="s-panel" id="spanel-guide-fb">
        <div class="wizard-grid">
            <div class="step-row">
                <div class="step-num">1</div>
                <div class="step-content-card">
                    <h4>Create Meta App</h4>
                    <p>Initialize a new "Business" or "Others" application in the Meta Developer portal. This is your primary bridge to Meta's servers.</p>
                    <a href="https://developers.facebook.com/apps/" target="_blank" class="btn btn-sm btn-outline-primary" style="font-weight:800; border-radius:10px;">Visit App Portal &rarr;</a>
                </div>
            </div>
            <div class="step-row">
                <div class="step-num">2</div>
                <div class="step-content-card">
                    <h4>Acquire Permissions</h4>
                    <p>Open the Graph Explorer and generate a token that specifically includes these required permissions:</p>
                    <div style="background:#f8fafc; padding:18px; border-radius:16px; border:1px solid #e2e8f0; margin-bottom:15px; font-family:'JetBrains Mono',monospace; font-size:12px; color:var(--primary); line-height:1.6;">
                        pages_manage_posts, pages_read_engagement, instagram_basic, instagram_content_publish
                    </div>
                    <a href="https://developers.facebook.com/tools/explorer/" target="_blank" class="btn btn-sm btn-outline-primary" style="font-weight:800; border-radius:10px;">Open Graph Explorer &rarr;</a>
                </div>
            </div>
            <div class="step-row">
                <div class="step-num">3</div>
                <div class="step-content-card">
                    <h4>Eternalize Token</h4>
                    <p>Exchange your temporary 2-hour token for a permanent key using the Access Token Debugger. This ensures the automation never stops.</p>
                    <a href="https://developers.facebook.com/tools/debug/accesstoken/" target="_blank" class="btn btn-sm btn-outline-primary" style="font-weight:800; border-radius:10px;">Token Debugger &rarr;</a>
                </div>
            </div>
        </div>
    </div>
        
        <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:24px; padding:24px; color:#b91c1c; display:flex; gap:16px; margin-top:30px;">
            <i data-feather="alert-octagon" style="width:24px; flex-shrink:0;"></i>
            <div>
                <strong style="display:block; margin-bottom:4px;">Technical Warning (#200)</strong>
                <span style="font-size:13px; opacity:0.8;">If your server returns "Insufficient Permission", it means you missed step 2. You must regenerate the token WITH <code>pages_manage_posts</code> checked.</span>
            </div>
        </div>
    </div>

    <!-- INSTAGRAM GUIDE -->
    <div class="s-panel" id="spanel-guide-ig">
        <div class="timeline-guide">
            <div class="wizard-step">
                <div class="step-indicator">1</div>
                <div class="step-card">
                    <h4>Business Account Transformation</h4>
                    <p style="color:var(--text-muted); font-size:14px;">Mobile App &rarr; Settings &rarr; Professional Account &rarr; **Business**. You must link the IG account to your Facebook Page in this step.</p>
                </div>
            </div>
            <div class="wizard-step">
                <div class="step-indicator">2</div>
                <div class="step-card">
                    <h4>Fetch Business Handle</h4>
                    <p style="color:var(--text-muted); font-size:14px;">In Graph Explorer, execute: <code>me/accounts?fields=instagram_business_account</code></p>
                    <p style="font-size:12px; color:var(--text-muted); margin-top:10px;">The numeric ID starting with **178...** is your goal.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- COMMAND CENTER -->
    <div class="s-panel" id="spanel-manual">
        <?php if ($share_result): ?>
            <div style="margin-bottom:35px;"><?php echo $share_result; ?></div>
        <?php
endif; ?>

        <div class="broadcast-center">
            <div style="position:relative; z-index:2;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:40px;">
                    <div>
                        <h2 style="margin:0 0 8px; font-weight:900; font-size:32px; letter-spacing:-1px;">Execute Broadcast</h2>
                        <p style="opacity:0.75; font-size:16px; font-weight:500;">Deploy content intelligence to your ecosystem.</p>
                    </div>
                    <div style="background:rgba(255,255,255,0.1); padding:12px 20px; border-radius:18px; border:1px solid rgba(255,255,255,0.15);">
                        <i data-feather="cpu" style="width:16px; margin-right:8px; opacity:0.8;"></i>
                        <span style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Server Node Alpha</span>
                    </div>
                </div>
                
                <form method="POST" id="broadcastForm">
                    <div class="input-field">
                        <label style="color:rgba(255,255,255,0.7); font-size:11px; font-weight:900; letter-spacing:1px;">Target Payload (Article)</label>
                        <select name="share_post_id" class="input-control post-selector" required style="background:rgba(255,255,255,0.04); border-color:rgba(255,255,255,0.1); color:#fff; font-weight:600; backdrop-filter:blur(10px);">
                            <option value="" style="color:#000;">Select content architecture...</option>
                            <?php foreach ($recent_posts as $ap):
    $stmt_slug = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
    $stmt_slug->execute([$ap['id']]);
    $slug = $stmt_slug->fetchColumn();
?>
                                <option value="<?php echo $ap['id']; ?>" data-slug="<?php echo htmlspecialchars($slug); ?>" style="color:#000;">
                                    <?php echo htmlspecialchars(substr($ap['title'], 0, 100)); ?>
                                </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:25px; margin:40px 0;">
                        <label class="platform-toggle" id="fb-toggle">
                            <input type="checkbox" name="platforms[]" value="facebook" checked style="display:none;" onchange="this.parentElement.classList.toggle('active-fb', this.checked)">
                            <div style="width:40px; height:40px; background:rgba(255,255,255,0.1); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-right:15px;">
                                <i data-feather="facebook" style="width:20px;"></i>
                            </div>
                            <span style="font-weight:900; font-size:18px;">Facebook</span>
                        </label>
                        <label class="platform-toggle" id="ig-toggle">
                            <input type="checkbox" name="platforms[]" value="instagram" style="display:none;" onchange="this.parentElement.classList.toggle('active-ig', this.checked)">
                            <div style="width:40px; height:40px; background:rgba(255,255,255,0.1); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-right:15px;">
                                <i data-feather="instagram" style="width:20px;"></i>
                            </div>
                            <span style="font-weight:900; font-size:18px;">Instagram</span>
                        </label>
                    </div>

                    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:25px;">
                        <button type="submit" name="manual_share" class="launch-btn" onclick="startLoading(this)">
                            <i data-feather="loader" class="action-loader"></i>
                            <span class="action-text">Launch Broadcast</span>
                        </button>
                        <button type="button" onclick="shareViaDialog()" class="btn btn-outline-light" style="padding:22px; border-radius:24px; font-weight:900; font-size:16px; border-color:rgba(255,255,255,0.2); transition:0.3s; hover:background:rgba(255,255,255,0.05);">
                            External Overlay
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Polished Actions
function startLoading(btn) {
    if(!document.querySelector('select[name="share_post_id"]').value) return;
    btn.classList.add('launching');
    btn.disabled = true;
    document.getElementById('broadcastForm').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    // Platform Toggle Initialization
    const fbCheck = document.querySelector('input[value="facebook"]');
    const igCheck = document.querySelector('input[value="instagram"]');
    if(fbCheck && fbCheck.checked) document.getElementById('fb-toggle').classList.add('active-fb');
    if(igCheck && igCheck.checked) document.getElementById('ig-toggle').classList.add('active-ig');
    
    // Smooth Feather Replacement
    if(window.feather) feather.replace();
});
function showSTab(btn, tab) {
    document.querySelectorAll('.s-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.tab-btn').forEach(function(b){ b.classList.remove('active'); });
    
    document.getElementById('spanel-' + tab).classList.add('active');
    
    if(btn) {
        btn.classList.add('active');
    } else {
        const targetBtn = Array.from(document.querySelectorAll('.tab-btn')).find(b => b.innerText.toLowerCase().includes(tab.split('-')[0]));
        if(targetBtn) targetBtn.classList.add('active');
    }
}

function shareViaDialog() {
    const selector = document.querySelector('select[name="share_post_id"]');
    const postId = selector.value;
    if(!postId) { alert("Please select an article first."); return; }
    
    const opt = selector.options[selector.selectedIndex];
    const slug = opt.getAttribute('data-slug');
    const url = '<?php echo BASE_URL; ?>article.php?slug=' + slug;
    
    const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    window.open(fbUrl, 'fbShareWindow', 'width=600,height=400');
}

function copyText(id, btn) {
    const text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i data-feather="check" style="width:12px;"></i> Copied!';
        btn.classList.add('copied');
        if(window.feather) feather.replace();
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('copied');
            if(window.feather) feather.replace();
        }, 2000);
    });
}
</script>
<?php include 'includes/footer.php'; ?>
