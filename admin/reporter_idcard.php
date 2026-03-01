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
?>
<style>
.idcard-layout{display:grid;grid-template-columns:280px 1fr;gap:22px;align-items:start;}
.panel{background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;}
.panel-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.panel-hd .ico{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;}
.panel-bd{padding:20px;}
.id-card-wrap{display:flex;justify-content:center;flex-direction:column;align-items:center;gap:18px;}
.id-card{width:340px;min-height:530px;border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.18);position:relative;font-family:'Segoe UI',Arial,sans-serif;background:#fff;display:flex;flex-direction:column;}
.idc-header{padding:22px 20px 18px;text-align:center;position:relative;color:#fff;}
.idc-header h2{margin:0;font-size:17px;font-weight:900;letter-spacing:.5px;}
.idc-header p{margin:3px 0 0;font-size:10px;opacity:.85;font-weight:600;letter-spacing:1px;}
.idc-logo{width:52px;height:52px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:18px;color:#fff;margin:0 auto 10px;overflow:hidden;}
.idc-logo img{width:100%;height:100%;object-fit:contain;}
.idc-photo-area{background:#f8fafc;padding:24px 20px;text-align:center;}
.idc-photo{width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.15);margin-bottom:14px;}
.idc-photo-ph{width:100px;height:100px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:900;color:#94a3b8;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,.1);margin:0 auto 14px;}
.idc-name{font-size:17px;font-weight:800;color:#0f172a;margin:0 0 2px;}
.idc-role{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:3px 12px;border-radius:20px;display:inline-block;margin-bottom:4px;}
.idc-id{font-size:11px;color:#64748b;font-weight:600;}
.idc-body{padding:16px 20px;flex:1;}
.idc-row{display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px;}
.idc-row:last-child{border-bottom:none;}
.idc-row .lbl{color:#94a3b8;font-weight:700;min-width:68px;line-height:1.4;}
.idc-row .val{color:#0f172a;font-weight:600;line-height:1.4;word-break:break-word;}
.idc-footer{padding:12px 20px;text-align:center;font-size:9.5px;font-weight:700;letter-spacing:.5px;color:rgba(255,255,255,.9);}
.idc-barcode{display:flex;justify-content:center;margin:8px 0;}
.dl-btns{display:flex;gap:10px;}
.dl-btns button{flex:1;padding:11px;border-radius:10px;font-size:13px;font-weight:700;text-align:center;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:6px;}
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
          <p>PRESS &middot; MEDIA &middot; OFFICIAL</p>
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
          <div class="idc-row"><span class="lbl">Office</span><span class="val"><?php echo htmlspecialchars($contact_phone); ?></span></div>
          <?php
    endif; ?>
          <div class="idc-row"><span class="lbl">Issued</span><span class="val"><?php echo $issue_date; ?></span></div>
          <div class="idc-row"><span class="lbl">Valid Till</span><span class="val"><?php echo $expiry_date; ?></span></div>
          <?php if ($address): ?>
          <div class="idc-row"><span class="lbl">Address</span><span class="val"><?php echo htmlspecialchars(substr($address, 0, 50)); ?>...</span></div>
          <?php
    endif; ?>
          <div class="idc-barcode">
            <svg width="180" height="32" viewBox="0 0 180 32">
              <?php
    $bars = str_split(md5($id_number));
    $x = 5;
    foreach ($bars as $b) {
        $w = (hexdec($b) % 3) + 1;
        $hb = 14 + (hexdec($b) % 14);
        $y = (32 - $hb) / 2;
        echo "<rect x='{$x}' y='{$y}' width='{$w}' height='{$hb}' fill='#334155' opacity='0.8'/>";
        $x += $w + ((hexdec($b) % 2) + 1);
    }
?>
            </svg>
          </div>
          <div style="text-align:center;font-size:9px;color:#94a3b8;letter-spacing:1px;font-weight:600;"><?php echo $id_number; ?></div>
        </div>

        <div class="idc-footer" style="background:<?php echo $theme_color; ?>;">
          <?php if ($contact_email):
        echo htmlspecialchars($contact_email) . ' &nbsp;|&nbsp; ';
    endif; ?>
          <?php echo htmlspecialchars($site_name); ?> &mdash; Official Press ID
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
        const canvas = await html2canvas(el, { 
            scale: 3,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
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
        
        const canvas = await html2canvas(el, { 
            scale: 3,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff'
        });
        
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        
        const ratio = canvas.height / canvas.width;
        const pdfW = 85;
        const pdfH = pdfW * ratio;
        
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
