<?php
$page_title = "Reporter Joining Letter";
include 'includes/header.php';
if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}
require_once '../includes/email_helper.php';

$reporters = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
$uid = (int)($_GET['uid'] ?? 0);
$selected = null;
$flash = '';
$flash_type = '';

if ($uid) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $selected = $stmt->fetch();
}

$site_name = get_setting('site_name', 'NewsCast');
$site_addr = get_setting('address', '');
$auth_name = $_SESSION['username'] ?? 'Editor-in-Chief';

function generate_joining_letter_html($user, $designation, $joining_date, $salary, $custom_note, $site_name, $address, $auth_name)
{
    $date_str = date('d F Y', strtotime($joining_date));
    $issue_date = date('d F Y');
    $id_ref = htmlspecialchars($site_name) . '-APP-' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);
    $salary_line = $salary ? "<li>Remuneration: <strong>Rs. " . htmlspecialchars($salary) . " per month</strong>.</li>" : '';
    $custom_line = $custom_note ? "<p>" . nl2br(htmlspecialchars($custom_note)) . "</p>" : '';
    $addr_html = $address ? "<p style='font-size:12px;color:#64748b;'>" . htmlspecialchars($address) . "</p>" : '';

    return '<div style="font-family:Georgia,serif;max-width:680px;margin:0 auto;padding:30px;border:2px solid #334155;border-radius:4px;color:#0f172a;">
      <div style="text-align:center;border-bottom:1px solid #e2e8f0;padding-bottom:20px;margin-bottom:24px;">
        <h2 style="margin:0 0 4px;font-size:22px;">' . htmlspecialchars($site_name) . '</h2>
        ' . $addr_html . '
        <h3 style="margin:16px 0 0;font-size:16px;text-transform:uppercase;letter-spacing:2px;border-top:1px solid #e2e8f0;padding-top:16px;">Appointment / Joining Letter</h3>
      </div>
      <p><strong>Date:</strong> ' . $issue_date . '</p>
      <p><strong>Ref No:</strong> ' . $id_ref . '</p>
      <br>
      <p>To,<br><strong>' . htmlspecialchars($user['username']) . '</strong><br>' . htmlspecialchars($user['email']) . '</p>
      <br>
      <p>Dear <strong>' . htmlspecialchars($user['username']) . '</strong>,</p>
      <p>We are pleased to inform you that you have been selected and appointed as <strong>' . htmlspecialchars($designation) . '</strong> at <strong>' . htmlspecialchars($site_name) . '</strong>, effective from <strong>' . $date_str . '</strong>.</p>
      <p>Your appointment is subject to the following terms and conditions:</p>
      <ol style="line-height:2.2;">
        <li>Your designation will be <strong>' . htmlspecialchars($designation) . '</strong>.</li>
        <li>Your joining date is: <strong>' . $date_str . '</strong>.</li>
        ' . $salary_line . '
        <li>You must adhere to all editorial standards, deadlines, and code of conduct of ' . htmlspecialchars($site_name) . '.</li>
        <li>Confidentiality of all organizational information must be maintained.</li>
        <li>This appointment is initially for a probationary period of 3 months, subject to satisfactory performance.</li>
      </ol>
      ' . $custom_line . '
      <p>Please report to the Editor-in-Chief on or before your joining date. This letter serves as formal confirmation of your appointment.</p>
      <p>We welcome you to our team and look forward to a productive association.</p>
      <br><br>
      <p>Yours sincerely,</p>
      <br><br>
      <p><strong>' . htmlspecialchars($auth_name) . '</strong><br>
      Editor-in-Chief<br>
      <em>' . htmlspecialchars($site_name) . '</em></p>
    </div>';
}

$designation_val = clean($_POST['designation'] ?? 'Reporter / Journalist');
$joining_date_val = $_POST['joining_date'] ?? date('Y-m-d');
$salary_val = clean($_POST['salary'] ?? '');
$custom_note_val = clean($_POST['custom_note'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email_letter']) && $selected) {
    $letter_body = generate_joining_letter_html($selected, $designation_val, $joining_date_val, $salary_val, $custom_note_val, $site_name, $site_addr, $auth_name);
    $result = send_joining_letter_email($selected['email'], $selected['username'], $letter_body, $designation_val);
    if ($result) {
        $flash = "Joining letter sent to " . htmlspecialchars($selected['email']);
        $flash_type = 'success';
    }
    else {
        $flash = "Could not send email. Check SMTP settings in Settings > Email/SMTP.";
        $flash_type = 'danger';
    }
}

$preview_html = '';
if ($selected) {
    $preview_html = generate_joining_letter_html($selected, $designation_val, $joining_date_val, $salary_val, $custom_note_val, $site_name, $site_addr, $auth_name);
}
?>
<style>
.letter-layout{display:grid;grid-template-columns:300px 1fr;gap:22px;align-items:start;}
.panel{background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-bottom:14px;}
.panel-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.panel-hd .ico{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;}
.panel-bd{padding:20px;}
.field-label{font-size:12px;font-weight:700;color:#334155;margin-bottom:6px;display:block;text-transform:uppercase;letter-spacing:.4px;}
.preview-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;overflow:auto;}
.dl-btns{display:flex;gap:10px;flex-wrap:wrap;}
.dl-btns button{padding:10px 18px;border-radius:10px;font-size:13px;font-weight:700;border:none;cursor:pointer;display:flex;align-items:center;gap:7px;transition:.2s;}
@media(max-width:900px){.letter-layout{grid-template-columns:1fr;}}
.spin { animation: fa-spin 2s infinite linear; }
@keyframes fa-spin { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }
</style>

<?php if ($flash): ?>
<div class="alert alert-<?php echo $flash_type; ?>" style="margin-bottom:16px;"><?php echo $flash; ?></div>
<?php
endif; ?>

<div class="letter-layout">
  <div>
    <div class="panel">
      <div class="panel-hd">
        <div class="ico" style="background:#fdf4ff;color:#9333ea;"><i data-feather="file-text" style="width:16px;"></i></div>
        <div><div style="font-size:14px;font-weight:700;">Select Reporter</div></div>
      </div>
      <div class="panel-bd" style="max-height:300px;overflow-y:auto;">
        <?php foreach ($reporters as $r): ?>
        <a href="?uid=<?php echo $r['id']; ?>" style="display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;text-decoration:none;margin-bottom:4px;background:<?php echo $uid == $r['id'] ? '#fdf4ff' : '#f8fafc'; ?>;border:1.5px solid <?php echo $uid == $r['id'] ? '#9333ea' : '#e2e8f0'; ?>;">
          <div style="width:30px;height:30px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:800;color:#9333ea;font-size:13px;flex-shrink:0;"><?php echo strtoupper(substr($r['username'], 0, 1)); ?></div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:#0f172a;"><?php echo htmlspecialchars($r['username']); ?></div>
            <div style="font-size:11px;color:#64748b;"><?php echo htmlspecialchars($r['email']); ?></div>
          </div>
          <?php if ($uid == $r['id']): ?><i data-feather="check-circle" style="width:14px;color:#9333ea;"></i><?php
    endif; ?>
        </a>
        <?php
endforeach; ?>
      </div>
    </div>

    <?php if ($selected): ?>
    <div class="panel">
      <div class="panel-hd">
        <div class="ico" style="background:#ecfdf5;color:#10b981;"><i data-feather="edit-3" style="width:16px;"></i></div>
        <div><div style="font-size:14px;font-weight:700;">Letter Details</div></div>
      </div>
      <div class="panel-bd">
        <form method="POST" action="?uid=<?php echo $uid; ?>">
          <div class="form-group">
            <label class="field-label">Designation</label>
            <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($designation_val); ?>" placeholder="Reporter / Journalist">
          </div>
          <div class="form-group">
            <label class="field-label">Joining Date</label>
            <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($joining_date_val); ?>">
          </div>
          <div class="form-group">
            <label class="field-label">Monthly Salary (optional)</label>
            <input type="text" name="salary" class="form-control" value="<?php echo htmlspecialchars($salary_val); ?>" placeholder="e.g. 15000">
          </div>
          <div class="form-group">
            <label class="field-label">Additional Note (optional)</label>
            <textarea name="custom_note" class="form-control" rows="3" placeholder="Any additional instructions..."><?php echo htmlspecialchars($custom_note_val); ?></textarea>
          </div>
          <div style="display:flex;gap:8px;margin-bottom:10px;">
            <button type="submit" style="flex:1;background:#f1f5f9;color:#475569;padding:10px;border-radius:10px;border:none;cursor:pointer;font-weight:700;font-size:13px;">
              <i data-feather="eye" style="width:14px;"></i> Preview
            </button>
          </div>
          <button type="submit" name="send_email_letter" class="btn btn-primary" style="width:100%;justify-content:center;">
            <i data-feather="send" style="width:14px;"></i> Send via Email
          </button>
        </form>
        <div style="margin-top:10px;" class="dl-btns">
          <button onclick="printLetter()" style="background:#64748b;color:#fff;flex:1;">
            <i data-feather="printer" style="width:14px;"></i> Print
          </button>
          <button onclick="downloadLetterPDF(this)" style="background:#ef4444;color:#fff;flex:1;">
            <i data-feather="file-text" style="width:14px;"></i> PDF
          </button>
          <button onclick="downloadLetter(this)" style="background:#10b981;color:#fff;width:100%;margin-top:8px;">
            <i data-feather="image" style="width:14px;"></i> Download as Image
          </button>
        </div>
      </div>
    </div>
    <?php
endif; ?>
  </div>

  <div class="panel">
    <div class="panel-hd">
      <div class="ico" style="background:#fff7ed;color:#f59e0b;"><i data-feather="file" style="width:16px;"></i></div>
      <div><div style="font-size:14px;font-weight:700;">Letter Preview</div></div>
    </div>
    <div class="panel-bd">
      <?php if ($selected): ?>
        <div id="letter-preview" class="preview-box"><?php echo $preview_html; ?></div>
      <?php
else: ?>
        <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
          <div style="font-size:48px;margin-bottom:14px;">&#128196;</div>
          <div style="font-size:16px;font-weight:700;color:#475569;margin-bottom:8px;">Select a Reporter</div>
          <div style="font-size:13px;">Pick a team member to generate their joining letter.</div>
        </div>
      <?php
endif; ?>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function printLetter() {
    var el = document.getElementById('letter-preview');
    if (!el) return;
    var w = window.open('', '_blank');
    w.document.write('<html><head><title>Joining Letter</title><style>body{font-family:Georgia,serif;padding:40px;}</style></head><body>' + el.innerHTML + '</body></html>');
    w.document.close();
    setTimeout(function(){ w.print(); }, 500);
}

// Reliable download helper
function triggerDownload(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.style.display = 'none';
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
        document.body.removeChild(a);
        if(url.startsWith('blob:')) URL.revokeObjectURL(url);
    }, 200);
}

async function downloadLetterPDF(btn) {
    var el = document.getElementById('letter-preview');
    if (!el || !btn) return;

    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i data-feather="loader" class="spin" style="width:14px;"></i> Wait...';
    btn.disabled = true;
    if(window.feather) setTimeout(() => feather.replace(), 10);

    try {
        var name = "<?php echo addslashes($selected['username'] ?? 'reporter'); ?>";
        var reporterName = (name.replace(/[^a-z0-9]/gi, '_').toLowerCase()) || 'letter';
        var filename = 'joining_letter_' + reporterName + '.pdf';

        var jspdf   = window.jspdf;
        var jsPDF   = jspdf.jsPDF;
        
        var canvas  = await html2canvas(el, { 
            scale: 2, 
            useCORS: true, 
            backgroundColor: '#ffffff',
            logging: false
        });
        
        var imgData = canvas.toDataURL('image/png', 1.0);
        var pdf     = new jsPDF('p', 'mm', 'a4');
        var imgProps= pdf.getImageProperties(imgData);
        var pdfWidth = pdf.internal.pageSize.getWidth();
        var pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
        
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save(filename);
    } catch (err) {
        console.error("PDF Gen Error:", err);
        alert("Could not generate PDF. Please try again.");
    } finally {
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            if(window.feather) setTimeout(() => feather.replace(), 10);
        }, 1000);
    }
}

async function downloadLetter(btn) {
    var el = document.getElementById('letter-preview');
    if (!el || !btn) return;

    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i data-feather="loader" class="spin" style="width:14px;"></i> Wait...';
    btn.disabled = true;
    if(window.feather) setTimeout(() => feather.replace(), 10);

    try {
        var name = "<?php echo addslashes($selected['username'] ?? 'reporter'); ?>";
        var reporterName = (name.replace(/[^a-z0-9]/gi, '_').toLowerCase()) || 'letter';
        var filename = 'joining_letter_' + reporterName + '.png';

        var canvas = await html2canvas(el, { 
            scale: 2, 
            useCORS: true, 
            backgroundColor: '#ffffff',
            logging: false
        });
        
        canvas.toBlob(function(blob) {
            if(!blob) throw new Error("Canvas to Blob failed");
            var url = URL.createObjectURL(blob);
            triggerDownload(url, filename);
        }, 'image/png');

    } catch (err) {
        console.error("Image Gen Error:", err);
        alert("Could not generate image. Please try again.");
    } finally {
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            if(window.feather) setTimeout(() => feather.replace(), 10);
        }, 1000);
    }
}
</script>
<?php include 'includes/footer.php'; ?>
