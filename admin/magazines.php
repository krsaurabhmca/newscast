<?php
$page_title = "Manage Magazines";
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!is_admin()) { redirect('admin/dashboard.php', 'Access denied.', 'danger'); }

$errors = []; $form_values = [];

// ── Add Magazine ─────────────────────────────────────────
if (isset($_POST['add_magazine'])) {
    $form_values = [
        'title'       => clean($_POST['title'] ?? ''),
        'description' => clean($_POST['description'] ?? ''),
        'issue_month' => clean($_POST['issue_month'] ?? ''),
        'status'      => clean($_POST['status'] ?? 'published'),
    ];
    if (empty($form_values['title']))       $errors['title']       = 'Magazine title is required.';
    if (empty($form_values['issue_month'])) $errors['issue_month'] = 'Please select the issue month.';
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== 0) {
        $errors['pdf_file'] = 'A PDF file is required.';
    } elseif (pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION) !== 'pdf') {
        $errors['pdf_file'] = 'Only PDF files are accepted.';
    }

    if (empty($errors)) {
        $upload_dir = '../assets/magazines/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $safe_name = time() . '_' . preg_replace('/[^a-z0-9._-]/', '_', strtolower(basename($_FILES['pdf_file']['name'])));
        $cover_img = null;

        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $safe_name)) {
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
                $cover_ext  = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
                $cover_name = 'cover_' . time() . '.' . $cover_ext;
                move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $cover_name);
                $cover_img = $cover_name;
            }
            // Store issue_month as first day of month
            $issue_date = $form_values['issue_month'] . '-01';
            try {
                $pdo->prepare("INSERT INTO magazines (title,description,issue_month,file_path,cover_image,status)
                               VALUES(?,?,?,?,?,?)")
                    ->execute([$form_values['title'], $form_values['description'], $issue_date, $safe_name, $cover_img, $form_values['status']]);
                redirect('admin/magazines.php', 'Magazine uploaded successfully!');
            } catch (PDOException $e) { $errors['general'] = 'DB Error: ' . $e->getMessage(); }
        } else { $errors['pdf_file'] = 'Upload failed. Check folder permissions.'; }
    }
}

// ── Delete ──────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $pdo->prepare("SELECT file_path, cover_image FROM magazines WHERE id=?");
    $row->execute([$id]); $row = $row->fetch();
    if ($row) {
        @unlink('../assets/magazines/' . $row['file_path']);
        if ($row['cover_image']) @unlink('../assets/magazines/' . $row['cover_image']);
        $pdo->prepare("DELETE FROM magazines WHERE id=?")->execute([$id]);
        redirect('admin/magazines.php', 'Magazine deleted.');
    }
}

// ── Toggle Status ────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE magazines SET status = IF(status='published','draft','published') WHERE id=?")->execute([$id]);
    redirect('admin/magazines.php', 'Status updated.');
}

// ── Search / Sort / Paginate ─────────────────────────────
$search   = trim($_GET['s'] ?? '');
$sort     = $_GET['sort'] ?? 'newest';
$status_f = $_GET['st'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$sort_map = ['newest'=>'issue_month DESC','oldest'=>'issue_month ASC','title'=>'title ASC'];
$order_by = $sort_map[$sort] ?? 'issue_month DESC';

$wheres = []; $params = [];
if ($search !== '')   { $wheres[] = 'title LIKE ?';   $params[] = "%$search%"; }
if ($status_f !== '') { $wheres[] = 'status = ?';     $params[] = $status_f;   }
$where_sql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

$cs = $pdo->prepare("SELECT COUNT(*) FROM magazines $where_sql"); $cs->execute($params);
$total       = (int)$cs->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$ds = $pdo->prepare("SELECT * FROM magazines $where_sql ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$ds->execute($params); $magazines = $ds->fetchAll();

function mag_url(array $ov = []): string {
    $b = array_merge($_GET, $ov);
    return '?' . http_build_query(array_filter($b, fn($v) => $v !== ''));
}

$months_php = ['January','February','March','April','May','June','July','August','September','October','November','December'];

include 'includes/header.php';
?>
<style>
.two-col{display:grid;grid-template-columns:330px 1fr;gap:22px;align-items:start;}
.form-card{background:white;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;}
.form-card-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.form-card-bd{padding:20px;}
.field-err{font-size:12px;color:#dc2626;margin-top:4px;display:flex;align-items:center;gap:4px;}
.is-error{border-color:#ef4444!important;background:#fef9f9!important;}
.err-banner{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:10px;padding:12px 15px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:600;}

.table-toolbar{display:flex;gap:9px;flex-wrap:wrap;align-items:center;margin-bottom:16px;}
.sb{position:relative;flex:1;min-width:180px;}
.sb input{padding-left:36px!important;}
.sb .sbi{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;width:15px;height:15px;pointer-events:none;}
.fsel{padding:9px 13px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;color:#475569;background:white;cursor:pointer;}
.table-card{background:white;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;}
.tc-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.empty-st{text-align:center;padding:50px 20px;color:#94a3b8;}
.pagi{display:flex;gap:5px;justify-content:center;padding:16px;flex-wrap:wrap;}
.pb{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;color:#475569;background:white;border:1px solid #e2e8f0;transition:.15s;}
.pb.active{background:var(--primary);color:white;border-color:var(--primary);}
.pb:hover:not(.active){background:#f8fafc;} .pb.dis{opacity:.4;pointer-events:none;}
.cover-thumb{width:48px;height:65px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;display:block;}
.cover-ph{width:48px;height:65px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;}
.issue-badge{display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;}
.pub-badge{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
.dra-badge{background:#fef9c3;color:#854d0e;border:1px solid #fde68a;}
@media(max-width:900px){.two-col{grid-template-columns:1fr;}}
</style>

<div class="two-col">
  <!-- FORM -->
  <div class="form-card">
    <div class="form-card-hd">
      <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center;">
        <i data-feather="book-open" style="width:17px;"></i></div>
      <div>
        <div style="font-size:14px;font-weight:700;">Upload Magazine</div>
        <div style="font-size:11px;color:#94a3b8;">Monthly editions archive</div>
      </div>
    </div>
    <div class="form-card-bd">
      <?php if(isset($errors['general'])): ?>
      <div class="err-banner"><?= htmlspecialchars($errors['general']) ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data" novalidate>

        <div class="form-group">
          <label class="field-label">Magazine Title <span style="color:#ef4444">*</span></label>
          <input type="text" name="title" class="form-control <?= isset($errors['title'])?'is-error':'' ?>"
                 value="<?= htmlspecialchars($form_values['title'] ?? '') ?>" placeholder="e.g. March 2025 Monthly Edition">
          <?php if(isset($errors['title'])): ?>
          <div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?= $errors['title'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="field-label">Description <span style="font-size:11px;color:#94a3b8;">(optional)</span></label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="Brief summary of this month's edition…"><?= htmlspecialchars($form_values['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label class="field-label">Issue Month <span style="color:#ef4444">*</span></label>
          <input type="month" name="issue_month" class="form-control <?= isset($errors['issue_month'])?'is-error':'' ?>"
                 value="<?= htmlspecialchars($form_values['issue_month'] ?? date('Y-m')) ?>">
          <?php if(isset($errors['issue_month'])): ?>
          <div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?= $errors['issue_month'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="field-label">Status</label>
          <select name="status" class="form-control">
            <option value="published" <?= ($form_values['status']??'published')==='published'?'selected':'' ?>>Published</option>
            <option value="draft"     <?= ($form_values['status']??'')==='draft'?'selected':''     ?>>Draft</option>
          </select>
        </div>

        <div class="form-group">
          <label class="field-label">PDF File <span style="color:#ef4444">*</span></label>
          <input type="file" name="pdf_file" class="form-control <?= isset($errors['pdf_file'])?'is-error':'' ?>" accept="application/pdf">
          <?php if(isset($errors['pdf_file'])): ?>
          <div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?= $errors['pdf_file'] ?></div>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label class="field-label">Cover Image <span style="font-size:11px;color:#94a3b8;">(optional)</span></label>
          <input type="file" name="cover_image" class="form-control" accept="image/*">
          <span style="font-size:11px;color:#94a3b8;margin-top:4px;display:block;">Recommended: 600×800 px portrait</span>
        </div>

        <button type="submit" name="add_magazine" class="btn btn-primary" style="width:100%;justify-content:center;">
          <i data-feather="upload" style="width:15px;"></i> Upload Magazine
        </button>
      </form>
    </div>
  </div>

  <!-- TABLE -->
  <div>
    <form method="GET">
      <div class="table-toolbar">
        <div class="sb">
          <svg class="sbi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="s" class="form-control" placeholder="Search by title…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="st" class="fsel" onchange="this.form.submit()">
          <option value="">All Status</option>
          <option value="published" <?= $status_f==='published'?'selected':'' ?>>Published</option>
          <option value="draft"     <?= $status_f==='draft'?'selected':''     ?>>Draft</option>
        </select>
        <select name="sort" class="fsel" onchange="this.form.submit()">
          <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
          <option value="oldest" <?= $sort==='oldest'?'selected':'' ?>>Oldest First</option>
          <option value="title"  <?= $sort==='title'?'selected':''  ?>>Title A–Z</option>
        </select>
        <button class="btn btn-primary" style="padding:9px 14px;font-size:13px;">
          <i data-feather="search" style="width:14px;"></i>
        </button>
        <?php if($search || $status_f): ?>
        <a href="magazines.php" class="btn" style="padding:9px 14px;font-size:13px;background:#f1f5f9;color:#64748b;">
          <i data-feather="x" style="width:14px;"></i>
        </a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-card">
      <div class="tc-hd">
        <div>
          <div style="font-size:15px;font-weight:700;">Magazine Archive</div>
          <div style="font-size:13px;color:#64748b;">
            <?= number_format($total) ?> issue<?= $total != 1 ? 's' : '' ?>
            <?php if($search): ?> — "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
          </div>
        </div>
        <div style="font-size:11px;color:#94a3b8;font-weight:600;">Monthly Digital</div>
      </div>

      <table class="content-table">
        <thead>
          <tr>
            <th width="60">Cover</th>
            <th>Title</th>
            <th>Issue</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($magazines)): ?>
          <tr><td colspan="5">
            <div class="empty-st">
              <i data-feather="book" style="width:38px;color:#e2e8f0;display:block;margin:0 auto 10px;"></i>
              <strong>No magazines found</strong>
              <p style="font-size:13px;margin-top:5px;"><?= $search ? 'Try a different search.' : 'Upload your first monthly edition.' ?></p>
            </div>
          </td></tr>
        <?php else: foreach ($magazines as $mg): ?>
          <tr>
            <td>
              <?php if ($mg['cover_image']): ?>
                <img src="../assets/magazines/<?= htmlspecialchars($mg['cover_image']) ?>"
                     class="cover-thumb" onerror="this.src='../assets/images/default-post.jpg'">
              <?php else: ?>
                <div class="cover-ph"><i data-feather="image" style="width:16px;color:#cbd5e1;"></i></div>
              <?php endif; ?>
            </td>
            <td>
              <strong style="font-size:13px;"><?= htmlspecialchars(mb_strimwidth($mg['title'], 0, 55, '…')) ?></strong>
              <?php if ($mg['description']): ?>
              <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars(mb_strimwidth($mg['description'],0,60,'…')) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <span class="issue-badge">
                <i data-feather="calendar" style="width:10px;"></i>
                <?= date('M Y', strtotime($mg['issue_month'])) ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $mg['status']==='published' ? 'pub-badge' : 'dra-badge' ?>">
                <?= $mg['status']==='published' ? '● Published' : '○ Draft' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if ($mg['status']==='published'): ?>
                <a href="<?= BASE_URL ?>magazine/view/<?= $mg['id'] ?>" target="_blank"
                   class="btn" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:5px 10px;font-size:11px;font-weight:700;">
                  <i data-feather="external-link" style="width:12px;"></i> View
                </a>
                <?php endif; ?>
                <a href="?toggle=<?= $mg['id'] ?>&<?= http_build_query(array_filter(['s'=>$search,'st'=>$status_f,'sort'=>$sort,'page'=>$page])) ?>"
                   class="btn" style="background:#f1f5f9;color:#475569;padding:5px 10px;font-size:11px;font-weight:700;">
                  <i data-feather="<?= $mg['status']==='published'?'eye-off':'eye' ?>" style="width:12px;"></i>
                </a>
                <a href="?delete=<?= $mg['id'] ?>&<?= http_build_query(array_filter(['s'=>$search,'st'=>$status_f,'sort'=>$sort,'page'=>$page])) ?>"
                   class="btn btn-danger" style="padding:5px 10px;font-size:11px;font-weight:700;"
                   onclick="return confirm('Delete this magazine permanently?')">
                  <i data-feather="trash-2" style="width:12px;"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>

      <?php if ($total_pages > 1): ?>
      <div class="pagi">
        <a href="<?= mag_url(['page'=>max(1,$page-1)]) ?>" class="pb <?= $page<=1?'dis':'' ?>">
          <i data-feather="chevron-left" style="width:13px;"></i>
        </a>
        <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
        <a href="<?= mag_url(['page'=>$i]) ?>" class="pb <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a href="<?= mag_url(['page'=>min($total_pages,$page+1)]) ?>" class="pb <?= $page>=$total_pages?'dis':'' ?>">
          <i data-feather="chevron-right" style="width:13px;"></i>
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
