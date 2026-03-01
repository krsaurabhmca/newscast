<?php
$page_title = "Reporter ID Card";
include 'includes/header.php';
if (!is_admin()) {
  redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

$reporters = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
$selected_user = null;
$uid = (int)($_GET['uid'] ?? 0);
if ($uid) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$uid]);
  $selected_user = $stmt->fetch();
}

$site_name = get_setting('site_name', 'NewsCast');
$site_logo = get_setting('site_logo', '');
$theme_color = get_setting('theme_color', '#6366f1');
$contact_phone = get_setting('contact_phone', '');
$contact_email = get_setting('contact_email', '');
$address = get_setting('address', '');
$site_tagline = get_setting('site_tagline', 'Truth • Speed • Trust');

?>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.idcard-layout{display:grid;grid-template-columns:320px 1fr;gap:30px;align-items:start;}
.panel{background:#fff;border-radius:24px;box-shadow:0 10px 40px rgba(0,0,0,.04);overflow:hidden;border:1px solid #f1f5f9;}
.panel-hd{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;}
.panel-hd .ico{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,.05);}
.panel-bd{padding:24px;}
.id-card-wrap{display:flex;justify-content:center;flex-direction:column;align-items:center;gap:25px;padding:20px;}

/* Perfect ID Card Size: 54mm x 86mm (CR80) at 300DPI equivalent ratio */
.id-card{width:340px;height:540px;border-radius:20px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.2);position:relative;font-family:'Outfit', 'Segoe UI', sans-serif;background:#fff;display:flex;flex-direction:column;border:1px solid rgba(0,0,0,.05);}

.idc-header{padding:30px 20px 20px;text-align:center;position:relative;color:#fff;clip-path:polygon(0 0, 100% 0, 100% 85%, 0 100%);}
.idc-header h2{margin:0;font-size:19px;font-weight:800;letter-spacing:0.5px;text-transform:uppercase;}
.idc-header p{margin:4px 0 0;font-size:10px;opacity:0.9;font-weight:700;letter-spacing:2px;text-transform:uppercase;}

.idc-logo{width:56px;height:56px;border-radius:15px;background:rgba(255,255,255,0.15);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:20px;color:#fff;margin:0 auto 12px;overflow:hidden;border:1.5px solid rgba(255,255,255,0.3);}
.idc-logo img{width:100%;height:100%;object-fit:contain;padding:5px;}

.idc-photo-area{background:linear-gradient(to bottom, #f8fafc, #ffffff);padding:25px 20px 15px;text-align:center;position:relative;margin-top:-20px;z-index:2;}
.idc-photo{width:110px;height:110px;border-radius:50%;object-fit:cover;border:6px solid #fff;box-shadow:0 10px 25px rgba(0,0,0,.15);margin-bottom:15px;}
.idc-photo-ph{width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);display:flex;align-items:center;justify-content:center;font-size:40px;font-weight:900;color:#94a3b8;border:6px solid #fff;box-shadow:0 10px 25px rgba(0,0,0,.1);margin:0 auto 15px;}

.idc-name{font-size:20px;font-weight:800;color:#0f172a;margin:0 0 4px;letter-spacing:-0.3px;}
.idc-role{font-size:10px;font-weight:800;letter-spacing:1px;text-transform:uppercase;padding:5px 15px;border-radius:30px;display:inline-block;margin-bottom:6px;box-shadow:0 2px 10px rgba(0,0,0,0.05);}
.idc-id{font-size:12px;color:#64748b;font-weight:700;font-family:monospace;}

.idc-body{padding:10px 25px;flex:1;background:#fff;}
.idc-row{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:12px;}
.idc-row:last-child{border-bottom:none;}
.idc-row .lbl{color:#94a3b8;font-weight:700;min-width:75px;text-transform:uppercase;font-size:9px;letter-spacing:0.5px;}
.idc-row .val{color:#334155;font-weight:600;line-height:1.2;word-break:break-all;}

.idc-qr-wrap { position: absolute; bottom: 65px; right: 20px; padding: 5px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid #f1f5f9; }
.idc-qr-wrap img { width: 50px; height: 50px; display: block; }

.idc-footer{padding:15px 20px;text-align:center;font-size:10px;font-weight:700;letter-spacing:1px;color:rgba(255,255,255,1);position:relative;z-index:2;text-transform:uppercase;border-top:1px solid rgba(255,255,255,0.1);}
.dl-btns{display:flex;flex-direction:column;gap:12px;}
.dl-btns button{width:100%;padding:14px;border-radius:14px;font-size:14px;font-weight:700;text-align:center;border:none;cursor:pointer;transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);display:flex;align-items:center;justify-content:center;gap:10px;box-shadow: 0 4px 12px rgba(0,0,0,0.1);}
.dl-btns button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
@media(max-width:800px){.idcard-layout{grid-template-columns:1fr;}}
.spin { animation: fa-spin 2s infinite linear; }
@keyframes fa-spin { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }
</style>

<div class="idcard-layout">
  <div>
    <div class="panel">
      <div class="panel-hd">
        <div class="ico" style="background:#eef2ff;color:var(--primary);"><i data-feather="credit-card" style="width:16px;"></i></div>
        <div><div style="font-size:14px;font-weight:700;">Select Reporter</div><div style="font-size:11px;color:#94a3b8;">Choose to generate ID card</div></div>
      </div>
      <div class="panel-bd">
        <?php foreach ($reporters as $r): ?>
        <a href="?uid=<?php echo $r['id']; ?>" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;text-decoration:none;margin-bottom:4px;transition:.15s;background:<?php echo $uid == $r['id'] ? '#eef2ff' : '#f8fafc'; ?>;border:1.5px solid <?php echo $uid == $r['id'] ? 'var(--primary)' : '#e2e8f0'; ?>;">
          <div style="width:34px;height:34px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:800;color:#6366f1;font-size:14px;flex-shrink:0;">
            <?php echo strtoupper(substr($r['username'], 0, 1)); ?>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($r['username']); ?></div>
            <div style="font-size:11px;color:#64748b;"><?php echo ucfirst($r['role']); ?></div>
          </div>
          <?php if ($uid == $r['id']): ?><i data-feather="check-circle" style="width:15px;color:var(--primary);flex-shrink:0;"></i><?php
  endif; ?>
        </a>
        <?php
endforeach; ?>
      </div>
    </div>

    <?php if ($selected_user): ?>
    <div class="panel" style="margin-top:16px;">
      <div class="panel-bd">
        <div style="font-size:13px;font-weight:700;margin-bottom:12px;color:#0f172a;">Download ID Card</div>
        <div class="dl-btns">
          <button onclick="downloadIDCardImage(this)" style="background:#0ea5e9;color:#fff;">
            <i data-feather="image" style="width:14px;"></i> PNG
          </button>
          <button onclick="downloadIDCardPDF(this)" style="background:#ef4444;color:#fff;">
            <i data-feather="file-text" style="width:14px;"></i> PDF
          </button>
        </div>
        <div style="margin-top:12px;font-size:11px;color:#94a3b8;text-align:center;">High quality - 300 DPI ready</div>
      </div>
    </div>
    <?php
endif; ?>
  </div>

  <div>
    <?php if ($selected_user):
  $issue_date = date('d M Y', strtotime($selected_user['created_at']));
  $expiry_date = date('d M Y', strtotime('+1 year'));
  $id_number = 'NC-' . str_pad($selected_user['id'], 5, '0', STR_PAD_LEFT);
  $has_photo = !empty($selected_user['profile_image']) && file_exists('../assets/images/' . $selected_user['profile_image']);
?>
    <div class="id-card-wrap">
      <div id="id-card-element" class="id-card">
        <div class="idc-header" style="background:linear-gradient(135deg,<?php echo $theme_color; ?> 0%,<?php echo $theme_color; ?>cc 100%);">
          <div class="idc-logo">
            <?php if ($site_logo && file_exists('../assets/images/' . $site_logo)): ?>
              <img src="<?php echo BASE_URL; ?>assets/images/<?php echo $site_logo; ?>" alt="Logo" crossorigin="anonymous">
            <?php
  else: ?><?php echo strtoupper(substr($site_name, 0, 2)); ?><?php
  endif; ?>
          </div>
          <h2><?php echo htmlspecialchars($site_name); ?></h2>
          <p><?php echo strtoupper(htmlspecialchars($site_tagline)); ?></p>
        </div>

        <div class="idc-photo-area">
          <?php if ($has_photo): ?>
            <img class="idc-photo" src="<?php echo BASE_URL; ?>assets/images/<?php echo $selected_user['profile_image']; ?>" alt="Photo" crossorigin="anonymous">
          <?php
  else: ?>
            <div class="idc-photo-ph"><?php echo strtoupper(substr($selected_user['username'], 0, 1)); ?></div>
          <?php
  endif; ?>
          <div class="idc-name"><?php echo htmlspecialchars($selected_user['username']); ?></div>
          <span class="idc-role" style="background:<?php echo $theme_color; ?>20;color:<?php echo $theme_color; ?>;">
            <?php echo $selected_user['role'] === 'admin' ? 'Administrator' : 'Reporter / Editor'; ?>
          </span>
          <div class="idc-id">ID: <?php echo $id_number; ?></div>
        </div>

        <div class="idc-body">
          <div class="idc-row"><span class="lbl">Email</span><span class="val"><?php echo htmlspecialchars($selected_user['email']); ?></span></div>
          <?php if ($contact_phone): ?>
          <div class="idc-row"><span class="lbl">Phone</span><span class="val"><?php echo htmlspecialchars($contact_phone); ?></span></div>
          <?php
  endif; ?>
          <div class="idc-row"><span class="lbl">Issued</span><span class="val"><?php echo $issue_date; ?></span></div>
          <div class="idc-row"><span class="lbl">Expires</span><span class="val"><?php echo $expiry_date; ?></span></div>
          
          <div class="idc-qr-wrap">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode(BASE_URL); ?>" alt="QR" crossorigin="anonymous">
          </div>

          <div style="text-align:center;font-size:10px;color:#94a3b8;letter-spacing:3px;font-weight:800;font-family:monospace;margin-top:25px;padding-bottom:15px;"><?php echo $id_number; ?></div>
        </div>

        <div class="idc-footer" style="background:<?php echo $theme_color; ?>;">
          VERIFIED STAFF &bull; <?php echo strtoupper(htmlspecialchars($site_name)); ?> 
        </div>
      </div>
      <div style="font-size:12px;color:#94a3b8;text-align:center;">
        <i data-feather="info" style="width:13px;vertical-align:middle;"></i>
        Preview &mdash; Download as PNG or PDF
      </div>
    </div>

    <?php
else: ?>
    <div style="background:#fff;border-radius:16px;padding:60px;text-align:center;color:#94a3b8;box-shadow:0 1px 4px rgba(0,0,0,.05);">
      <div style="font-size:48px;margin-bottom:14px;">&#x1FAA7;</div>
      <div style="font-size:16px;font-weight:700;color:#475569;margin-bottom:8px;">Select a Reporter</div>
      <div style="font-size:13px;">Choose a team member from the left to preview and generate their ID card.</div>
    </div>
    <?php
endif; ?>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
async function downloadIDCardImage(btn) {
    const el = document.getElementById('id-card-element');
    if (!el) return alert("Element not found!");
    
    btn.innerHTML = '<i data-feather="loader" class="spin" style="width:14px;"></i> Wait...';
    btn.disabled = true;
    if(window.feather) feather.replace();

    try {
        // Ensure fonts are loaded before capturing
        await document.fonts.ready;
        
        const canvas = await html2canvas(el, { 
            scale: 4, // Higher scale for even better quality
            useCORS: true,
            allowTaint: false, // Changed to false for better CORS handling
            backgroundColor: '#ffffff',
            logging: false
        });
        
        // Convert to data URL
        const dataURL = canvas.toDataURL('image/png');
        
        // Create filename
        const name = "<?php echo isset($selected_user['username']) ? str_replace(' ', '_', $selected_user['username']) : 'reporter'; ?>";
        const filename = `IDCard_${name.replace(/[^a-z0-9_]/gi, '')}_<?php echo isset($id_number) ? str_replace('-', '', $id_number) : '00000'; ?>.png`;
        
        // Download using data URL
        const link = document.createElement('a');
        link.href = dataURL;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        btn.innerHTML = '<i data-feather="check" style="width:14px;"></i> Done!';
        btn.style.background = '#10b981';
        if(window.feather) feather.replace();
        
    } catch (err) {
        console.error(err);
        alert("Error: " + err.message);
        btn.innerHTML = '<i data-feather="x" style="width:14px;"></i> Error';
    }
    
    setTimeout(() => {
        btn.innerHTML = '<i data-feather="image" style="width:14px;"></i> PNG';
        btn.style.background = '#0ea5e9';
        btn.disabled = false;
        if(window.feather) feather.replace();
    }, 2000);
}

async function downloadIDCardPDF(btn) {
    const el = document.getElementById('id-card-element');
    if (!el) return alert("Element not found!");

    btn.innerHTML = '<i data-feather="loader" class="spin" style="width:14px;"></i> Wait...';
    btn.disabled = true;
    if(window.feather) feather.replace();

    try {
        if (!window.jspdf) throw new Error("jsPDF not loaded");
        
        // Ensure fonts are loaded before capturing
        await document.fonts.ready;

        const canvas = await html2canvas(el, { 
            scale: 4,
            useCORS: true,
            allowTaint: false,
            backgroundColor: '#ffffff',
            logging: false
        });
        
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        
        const pdfW = 54; // Standard CR80 Width in mm
        const pdfH = 86; // Standard CR80 Height in mm
        
        const pdf = new jsPDF('p', 'mm', [pdfW, pdfH]);
        pdf.addImage(imgData, 'PNG', 0, 0, pdfW, pdfH);
        
        const name = "<?php echo isset($selected_user['username']) ? str_replace(' ', '_', $selected_user['username']) : 'reporter'; ?>";
        const filename = `IDCard_${name.replace(/[^a-z0-9_]/gi, '')}_<?php echo isset($id_number) ? str_replace('-', '', $id_number) : '00000'; ?>.pdf`;
        
        pdf.save(filename);
        
        btn.innerHTML = '<i data-feather="check" style="width:14px;"></i> Done!';
        btn.style.background = '#10b981';
        if(window.feather) feather.replace();
        
    } catch (err) {
        console.error(err);
        alert("Error: " + err.message);
        btn.innerHTML = '<i data-feather="x" style="width:14px;"></i> Error';
    }
    
    setTimeout(() => {
        btn.innerHTML = '<i data-feather="file-text" style="width:14px;"></i> PDF';
        btn.style.background = '#ef4444';
        btn.disabled = false;
        if(window.feather) feather.replace();
    }, 2000);
}
</script>
<?php include 'includes/footer.php'; ?>
