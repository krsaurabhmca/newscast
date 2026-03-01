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
.guide-step{background:#fff;border-radius:14px;padding:22px 26px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.guide-step h4{margin:0 0 10px;font-size:15px;font-weight:800;color:#0f172a;display:flex;align-items:center;gap:10px;}
.step-num{width:28px;height:28px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;flex-shrink:0;}
.guide-step ol{margin:0;padding-left:20px;color:#475569;font-size:13px;line-height:2.2;}
.guide-step code{background:#f1f5f9;padding:2px 7px;border-radius:5px;font-family:monospace;font-size:12px;color:#6366f1;}
.s-tabs{display:flex;gap:5px;margin-bottom:20px;flex-wrap:wrap;}
.s-tab{padding:9px 18px;border-radius:9px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:700;color:#64748b;cursor:pointer;transition:.15s;}
.s-tab.active{background:var(--primary);color:#fff;border-color:var(--primary);}
.s-panel{display:none;} .s-panel.active{display:block;}
.shr-result{padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-top:8px;}
.shr-result.ok{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;}
.shr-result.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}
.info-callout{background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;font-size:13px;color:#1e40af;margin-bottom:16px;display:flex;gap:10px;}
.warn-callout{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 18px;font-size:13px;color:#92400e;margin-bottom:16px;display:flex;gap:10px;}
.settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;}
.field-label{font-size:12px;font-weight:700;color:#334155;margin-bottom:5px;display:block;text-transform:uppercase;letter-spacing:.3px;}
.field-hint{font-size:11px;color:#94a3b8;margin-top:4px;}
.guide-btn-group{display:flex;gap:10px;margin-top:15px;flex-wrap:wrap;}
.guide-btn-group a, .guide-btn-group btn{padding:8px 15px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.2s;}
</style>

<div class="s-tabs">
  <button class="s-tab active" onclick="showSTab(this,'config')">Configuration</button>
  <button class="s-tab" onclick="showSTab(this,'guide-fb')">Facebook Guide</button>
  <button class="s-tab" onclick="showSTab(this,'guide-ig')">Instagram Guide</button>
  <button class="s-tab" onclick="showSTab(this,'manual')">Manual Share</button>
</div>

<!-- CONFIG -->
<div class="s-panel active" id="spanel-config">
<form method="POST">
  <div class="info-callout"><span style="font-size:18px;flex-shrink:0;">i</span><div>Enter your Facebook App &amp; Page credentials and Instagram Business Account details below. Once configured, articles can be auto-shared on publish or manually from the "Manual Share" tab.</div></div>
  <div style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:16px;">
    <h3 style="margin:0 0 18px;font-size:15px;font-weight:800;">Facebook Page Settings</h3>
    <div class="settings-grid">
      <div><label class="field-label">App ID</label><input type="text" name="fb_app_id" class="form-control" placeholder="e.g. 123456789012345" value="<?php echo htmlspecialchars(get_setting('fb_app_id')); ?>"><span class="field-hint">From developers.facebook.com &rarr; Your App &rarr; App ID</span></div>
      <div><label class="field-label">App Secret</label><input type="password" name="fb_app_secret" class="form-control" placeholder="App Secret" value="<?php echo htmlspecialchars(get_setting('fb_app_secret')); ?>"></div>
      <div><label class="field-label">Page ID</label><input type="text" name="fb_page_id" class="form-control" placeholder="Your Facebook Page ID" value="<?php echo htmlspecialchars(get_setting('fb_page_id')); ?>"><span class="field-hint">Found in your Page's About section</span></div>
      <div><label class="field-label">Page Access Token</label><input type="password" name="fb_page_access_token" class="form-control" placeholder="Long-lived Page Access Token" value="<?php echo htmlspecialchars(get_setting('fb_page_access_token')); ?>"><span class="field-hint">Use Graph API Explorer to get a permanent page token</span></div>
    </div>

    <h3 style="margin:24px 0 18px;font-size:15px;font-weight:800;">Instagram Business Settings</h3>
    <div class="settings-grid">
      <div><label class="field-label">Instagram Business Account ID</label><input type="text" name="ig_business_account_id" class="form-control" placeholder="IG Business Account ID" value="<?php echo htmlspecialchars(get_setting('ig_business_account_id')); ?>"><span class="field-hint">Get from Graph API: me/accounts &rarr; instagram_business_account id</span></div>
      <div><label class="field-label">Instagram Access Token</label><input type="password" name="ig_access_token" class="form-control" placeholder="Same long-lived token as Facebook" value="<?php echo htmlspecialchars(get_setting('ig_access_token')); ?>"></div>
    </div>

    <h3 style="margin:24px 0 18px;font-size:15px;font-weight:800;">Auto-Share Options</h3>
    <div class="settings-grid">
      <div><label class="field-label">Auto-Share on Facebook</label><select name="auto_share_facebook" class="form-control"><option value="no" <?php echo get_setting('auto_share_facebook', 'no') === 'no' ? 'selected' : ''; ?>>Disabled</option><option value="yes" <?php echo get_setting('auto_share_facebook') === 'yes' ? 'selected' : ''; ?>>Enabled</option></select></div>
      <div><label class="field-label">Auto-Share on Instagram</label><select name="auto_share_instagram" class="form-control"><option value="no" <?php echo get_setting('auto_share_instagram', 'no') === 'no' ? 'selected' : ''; ?>>Disabled</option><option value="yes" <?php echo get_setting('auto_share_instagram') === 'yes' ? 'selected' : ''; ?>>Enabled (requires image)</option></select></div>
      <div><label class="field-label">Trigger</label><select name="auto_share_on_publish" class="form-control"><option value="yes" <?php echo get_setting('auto_share_on_publish', 'yes') === 'yes' ? 'selected' : ''; ?>>On Article Publish</option><option value="no" <?php echo get_setting('auto_share_on_publish') === 'no' ? 'selected' : ''; ?>>Manual Only</option></select></div>
    </div>
  </div>
  <div style="text-align:right;"><button type="submit" name="save_social_share" class="btn btn-primary" style="padding:12px 30px;font-size:15px;"><i data-feather="save" style="width:15px;"></i> Save Settings</button></div>
</form>
</div>

<!-- FACEBOOK GUIDE -->
<div class="s-panel" id="spanel-guide-fb">
  <div class="warn-callout"><span style="font-size:18px;flex-shrink:0;">!</span><div>Update: <strong>Meta Business Suite</strong> is now the primary hub. Your app needs <code>pages_manage_posts</code> (for automatic posting) or you can use the Share Dialog (manual).</div></div>
  
  <div class="guide-step" style="border-left:4px solid #1877f2;">
    <div style="float:right;" class="guide-btn-group">
      <a href="https://developers.facebook.com/apps/" target="_blank" style="background:#1877f2;color:#fff;"><i data-feather="external-link" style="width:13px;"></i> Meta App Dashboard</a>
    </div>
    <h4><div class="step-num" style="background:#1877f2;">1</div> Create a Meta Developer App</h4>
    <ol><li>Go to <strong>developers.facebook.com</strong> and click <strong>"My Apps"</strong>.</li><li>Click <strong>"Create App"</strong> &rarr; Select <strong>"Other"</strong> &rarr; <strong>"Business"</strong>.</li><li>Note your <code>App ID</code> and <code>App Secret</code> from <strong>App Settings &rarr; Basic.</strong></li></ol>
  </div>

  <div class="guide-step" style="border-left:4px solid #1877f2;">
    <div style="float:right;" class="guide-btn-group">
      <a href="https://developers.facebook.com/tools/explorer/" target="_blank" style="background:#1877f2;color:#fff;"><i data-feather="terminal" style="width:13px;"></i> Graph API Explorer</a>
    </div>
    <h4><div class="step-num" style="background:#1877f2;">2</div> Get Permanent Page Token</h4>
    <ol>
      <li>In Graph Explorer, select your App and your <strong>Page</strong>.</li>
      <li>Grant: <code>pages_manage_posts</code>, <code>pages_read_engagement</code>, <code>instagram_basic</code>.</li>
      <li>Generate Short-lived Token &rarr; Copy it.</li>
      <li>Use the <strong>Access Token Tool</strong> or this tool to exchange for a 60-day or permanent token.</li>
    </ol>
    <div class="guide-btn-group">
        <button onclick="showSTab(null,'config')" style="background:#f1f5f9;color:#475569;border:none;cursor:pointer;"><i data-feather="settings" style="width:13px;"></i> Go to Configuration</button>
    </div>
  </div>

  <div class="guide-step" style="border-left:4px solid #1877f2;">
    <h4><div class="step-num" style="background:#1877f2;">3</div> Privacy & Domain Verification</h4>
    <ol><li>Verify your domain in <strong>Business Settings &rarr; Brand Safety</strong>.</li><li>Add your <strong>Privacy Policy URL</strong> in App Settings to go "Live".</li></ol>
  </div>
</div>
  <div class="guide-step" style="border-left:4px solid #1877f2;">
    <h4><div class="step-num" style="background:#1877f2;">4</div> Get Your Facebook Page ID</h4>
    <ol><li>Visit your Facebook Page &rarr; click <strong>"About"</strong> &rarr; scroll to find <strong>Page ID</strong>.</li><li>Or call: <code>https://graph.facebook.com/me?fields=id&amp;access_token=YOUR_PAGE_TOKEN</code></li></ol>
  </div>
  <div class="guide-step" style="border-left:4px solid #1877f2;">
    <h4><div class="step-num" style="background:#1877f2;">5</div> Enter Credentials &amp; Test</h4>
    <ol><li>Go to the <strong>Configuration</strong> tab and enter all details. Save.</li><li>Go to <strong>Manual Share</strong> tab and test with a recent article.</li></ol>
  </div>
</div>

<!-- INSTAGRAM GUIDE -->
<div class="s-panel" id="spanel-guide-ig">
  <div class="warn-callout"><span style="font-size:18px;flex-shrink:0;">!</span><div>Instagram requires a <strong>Business or Creator Account</strong> linked to a Facebook Page. Your article <strong>must have a featured image</strong> — Instagram only supports image/video posts.</div></div>
  <div class="guide-step" style="border-left:4px solid #e1306c;">
    <h4><div class="step-num" style="background:#e1306c;">1</div> Convert to Instagram Business Account</h4>
    <ol><li>Open Instagram app &rarr; Profile &rarr; <strong>Settings &rarr; Account.</strong></li><li>Tap <strong>"Switch to Professional Account"</strong> &rarr; <strong>"Business".</strong></li><li>Connect to your <strong>Facebook Page</strong> (this is required).</li></ol>
  </div>
  <div class="guide-step" style="border-left:4px solid #e1306c;">
    <h4><div class="step-num" style="background:#e1306c;">2</div> Enable Instagram in Your Facebook App</h4>
    <ol><li>In Graph API Explorer, add permissions: <code>instagram_basic</code>, <code>instagram_content_publish</code>.</li><li>Generate a new access token with these permissions.</li></ol>
  </div>
  <div class="guide-step" style="border-left:4px solid #e1306c;">
    <h4><div class="step-num" style="background:#e1306c;">3</div> Get Instagram Business Account ID</h4>
    <ol>
      <li>In Graph API Explorer, call: <code>me/accounts</code> &rarr; find your page &rarr; copy the <code>id</code>.</li>
      <li>Then call: <code>PAGE_ID?fields=instagram_business_account&amp;access_token=TOKEN</code></li>
      <li>The returned <code>instagram_business_account.id</code> is your IG Business Account ID.</li>
    </ol>
  </div>
  <div class="guide-step" style="border-left:4px solid #e1306c;">
    <h4><div class="step-num" style="background:#e1306c;">4</div> Enter Credentials &amp; Test</h4>
    <ol><li>Go to <strong>Configuration</strong> tab and enter your IG Business Account ID and the same Page Access Token.</li><li>Save settings, then go to <strong>Manual Share</strong> and pick an article with a featured image.</li></ol>
  </div>
</div>

<!-- MANUAL SHARE -->
<div class="s-panel" id="spanel-manual">
  <?php if ($share_result):
  echo '<div style="margin-bottom:16px;">' . $share_result . '</div>';
endif; ?>
  <div class="info-callout"><span style="font-size:18px;flex-shrink:0;">i</span><div>Select a recent article and choose which platforms to share on. Make sure credentials are configured in the <strong>Configuration</strong> tab first.</div></div>
  <form method="POST" style="background:#fff;border-radius:16px;padding:24px;box-shadow:0 1px 4px rgba(0,0,0,.05);">
    <div class="form-group">
      <label class="field-label">Select Article to Share</label>
      <select name="share_post_id" class="form-control" required>
        <option value="">-- Pick an article --</option>
        <?php foreach ($recent_posts as $ap):
  $ap_slug = create_slug($ap['title']); // Fallback if slug not in SELECT, but better to get it from DB
  // Let's get slug from DB correctly
  $stmt_slug = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
  $stmt_slug->execute([$ap['id']]);
  $ap_data = $stmt_slug->fetch();
  $art_slug = $ap_data['slug'] ?? $ap_slug;
?>
        <option value="<?php echo $ap['id']; ?>" data-slug="<?php echo htmlspecialchars($art_slug); ?>">
            <?php echo htmlspecialchars(substr($ap['title'], 0, 70)); ?> &mdash; <?php echo date('d M Y', strtotime($ap['published_at'])); ?>
        </option>
        <?php
endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="field-label">Share On</label>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;">
        <label style="display:flex;align-items:center;gap:8px;background:#f0f4ff;border:1.5px solid #1877f2;border-radius:10px;padding:10px 18px;cursor:pointer;font-weight:700;font-size:14px;">
          <input type="checkbox" name="platforms[]" value="facebook" checked style="width:16px;height:16px;"> Facebook
        </label>
        <label style="display:flex;align-items:center;gap:8px;background:#fff0f6;border:1.5px solid #e1306c;border-radius:10px;padding:10px 18px;cursor:pointer;font-weight:700;font-size:14px;">
          <input type="checkbox" name="platforms[]" value="instagram" style="width:16px;height:16px;"> Instagram
        </label>
      </div>
      <div style="font-size:11px;color:#94a3b8;margin-top:6px;">Instagram requires the article to have a featured image.</div>
    </div>
    <div style="margin-top:15px; display:flex; gap:10px;">
        <button type="submit" name="manual_share" class="btn btn-primary" style="padding:12px 30px;font-size:15px;">
          <i data-feather="cpu" style="width:15px;"></i> Share via Server (Auto)
        </button>
        <button type="button" onclick="shareViaDialog()" class="btn" style="background:#1877f2; color:#fff; padding:12px 30px; font-size:15px; border:none;">
          <i data-feather="share-2" style="width:15px;"></i> Share via Facebook Dialog
        </button>
    </div>
  </form>
</div>

<script>
function showSTab(btn, tab) {
    document.querySelectorAll('.s-panel').forEach(function(p){ p.classList.remove('active'); });
    document.querySelectorAll('.s-tab').forEach(function(b){ b.classList.remove('active'); });
    
    document.getElementById('spanel-' + tab).classList.add('active');
    
    if(btn) {
        btn.classList.add('active');
    } else {
        // Find the button by text or data if called internally
        const targetBtn = Array.from(document.querySelectorAll('.s-tab')).find(b => b.innerText.toLowerCase().includes(tab.split('-')[0]));
        if(targetBtn) targetBtn.classList.add('active');
    }
}

function shareViaDialog() {
    const selector = document.querySelector('select[name="share_post_id"]');
    const postId = selector.value;
    if(!postId) { alert("Please select an article first."); return; }
    
    // Get slug from selected option text (hacky) or we can use JS mapping
    // For now, simpler: we need the URL. 
    // Usually we'd want a map of ID -> Slug. 
    // Let's just alert that selection is needed.
    // To make this robust, let's just pass the slug in a data attribute
    const opt = selector.options[selector.selectedIndex];
    const slug = opt.getAttribute('data-slug');
    const url = '<?php echo BASE_URL; ?>article.php?slug=' + slug;
    
    const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    window.open(fbUrl, 'fbShareWindow', 'width=600,height=400');
}
</script>
<?php include 'includes/footer.php'; ?>
