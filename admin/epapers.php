<?php
$page_title = "Manage E-Papers";
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!is_admin()) { redirect('admin/dashboard.php', 'Access denied.', 'danger'); }

$errors = []; $form_values = [];

// Add E-Paper
if (isset($_POST['add_epaper'])) {
    $form_values = ['title' => clean($_POST['title'] ?? ''), 'paper_date' => clean($_POST['paper_date'] ?? '')];
    if (empty($form_values['title']))      $errors['title']      = 'Title is required.';
    if (empty($form_values['paper_date'])) $errors['paper_date'] = 'Please select a paper date.';
    if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== 0) {
        $errors['pdf_file'] = 'A PDF file is required.';
    } elseif ($_FILES['pdf_file']['type'] !== 'application/pdf' && pathinfo($_FILES['pdf_file']['name'],PATHINFO_EXTENSION) !== 'pdf') {
        $errors['pdf_file'] = 'Only PDF files are accepted.';
    }
    if (empty($errors)) {
        $file_name = time() . '_' . basename($_FILES['pdf_file']['name']);
        $upload_dir = "../assets/epapers/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_dir . $file_name)) {
            $thumbnail = null;
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
                $thumbnail = 'thumb_' . time() . '_' . basename($_FILES['thumbnail']['name']);
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $thumbnail);
            }
            try {
                $pdo->prepare("INSERT INTO epapers (title,paper_date,file_path,thumbnail) VALUES(?,?,?,?)")
                    ->execute([$form_values['title'], $form_values['paper_date'], $file_name, $thumbnail]);
                redirect('admin/epapers.php', 'E-Paper uploaded successfully!');
            } catch (PDOException $e) { $errors['general'] = 'DB Error: '.$e->getMessage(); }
        } else { $errors['pdf_file'] = 'Upload failed. Check folder permissions.'; }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $pdo->prepare("SELECT file_path,thumbnail FROM epapers WHERE id=?"); $row->execute([$id]); $row=$row->fetch();
    if ($row) {
        @unlink("../assets/epapers/".$row['file_path']);
        if ($row['thumbnail']) @unlink("../assets/epapers/".$row['thumbnail']);
        $pdo->prepare("DELETE FROM epapers WHERE id=?")->execute([$id]);
        redirect('admin/epapers.php', 'E-Paper deleted.');
    }
}

// Search / Sort / Paginate
$search = trim($_GET['s'] ?? ''); $sort = $_GET['sort'] ?? 'newest';
$page = max(1,(int)($_GET['page']??1)); $per_page = 10;
$sort_map = ['newest'=>'paper_date DESC','oldest'=>'paper_date ASC','title'=>'title ASC'];
$order_by = $sort_map[$sort] ?? 'paper_date DESC';
$params = [];
$where_sql = '';
if ($search !== '') { $where_sql = 'WHERE title LIKE ?'; $params[] = "%$search%"; }
$cs=$pdo->prepare("SELECT COUNT(*) FROM epapers $where_sql"); $cs->execute($params);
$total=(int)$cs->fetchColumn(); $total_pages=max(1,ceil($total/$per_page));
$page=min($page,$total_pages); $offset=($page-1)*$per_page;
$ds=$pdo->prepare("SELECT * FROM epapers $where_sql ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$ds->execute($params); $epapers=$ds->fetchAll();
function ep_url(array $ov=[]): string { $b=array_merge($_GET,$ov); return '?'.http_build_query(array_filter($b,fn($v)=>$v!=='')); }

include 'includes/header.php';
?>
<style>
.two-col{display:grid;grid-template-columns:320px 1fr;gap:22px;align-items:start;}
.form-card{background:white;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;}
.form-card-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.form-card-bd{padding:20px;}
.field-err{font-size:12px;color:#dc2626;margin-top:4px;display:flex;align-items:center;gap:4px;}
.is-error{border-color:#ef4444!important;background:#fef9f9!important;}
.err-banner{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #ef4444;border-radius:10px;padding:12px 15px;margin-bottom:16px;font-size:13px;color:#991b1b;font-weight:600;}
.table-toolbar{display:flex;gap:9px;flex-wrap:wrap;align-items:center;margin-bottom:16px;}
.sb{position:relative;flex:1;min-width:180px;} .sb input{padding-left:36px;} .sb i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;width:15px;}
.fsel{padding:9px 13px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;color:#475569;background:white;cursor:pointer;}
.table-card{background:white;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;}
.tc-hd{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;}
.empty-st{text-align:center;padding:45px 20px;color:#94a3b8;}
.pagi{display:flex;gap:5px;justify-content:center;padding:16px;flex-wrap:wrap;}
.pb{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;color:#475569;background:white;border:1px solid #e2e8f0;transition:.15s;}
.pb.active{background:var(--primary);color:white;border-color:var(--primary);}
.pb:hover:not(.active){background:#f8fafc;} .pb.dis{opacity:.4;pointer-events:none;}
@media(max-width:900px){.two-col{grid-template-columns:1fr;}}
</style>

<div class="two-col">
  <!-- FORM -->
  <div class="form-card">
    <div class="form-card-hd">
      <div style="width:35px;height:35px;border-radius:9px;background:#fff7ed;color:#f59e0b;display:flex;align-items:center;justify-content:center;">
        <i data-feather="upload" style="width:16px;"></i></div>
      <div><div style="font-size:14px;font-weight:700;">Upload E-Paper</div><div style="font-size:11px;color:#94a3b8;">PDF editions archive</div></div>
    </div>
    <div class="form-card-bd">
      <?php if(isset($errors['general'])): ?>
      <div class="err-banner"><?=htmlspecialchars($errors['general'])?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data" novalidate>
        <div class="form-group">
          <label class="field-label">Edition Title <span style="color:#ef4444">*</span></label>
          <input type="text" name="title" class="form-control <?=isset($errors['title'])?'is-error':''?>" value="<?=htmlspecialchars($form_values['title']??'')?>" placeholder="e.g. Morning Edition – 25 Feb">
          <?php if(isset($errors['title'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['title']?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="field-label">Publication Date <span style="color:#ef4444">*</span></label>
          <input type="date" name="paper_date" class="form-control <?=isset($errors['paper_date'])?'is-error':''?>" value="<?=htmlspecialchars($form_values['paper_date'] ?? date('Y-m-d'))?>">
          <?php if(isset($errors['paper_date'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['paper_date']?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="field-label">PDF File <span style="color:#ef4444">*</span></label>
          <input type="file" name="pdf_file" class="form-control <?=isset($errors['pdf_file'])?'is-error':''?>" accept="application/pdf">
          <?php if(isset($errors['pdf_file'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['pdf_file']?></div><?php endif; ?>
          <span style="font-size:11px;color:#94a3b8;margin-top:4px;display:block;">Max size depends on your server's upload_max_filesize.</span>
        </div>
        <div class="form-group">
          <label class="field-label">Cover Thumbnail <span style="font-size:11px;color:#94a3b8;">(optional)</span></label>
          <input type="file" name="thumbnail" class="form-control" accept="image/*">
        </div>
        <button type="submit" name="add_epaper" class="btn btn-primary" style="width:100%;justify-content:center;">
          <i data-feather="upload" style="width:15px;"></i> Upload E-Paper
        </button>
      </form>
    </div>
  </div>

  <!-- TABLE -->
  <div>
    <form method="GET"><div class="table-toolbar">
      <div class="sb"><i data-feather="search"></i><input type="text" name="s" class="form-control" placeholder="Search by title…" value="<?=htmlspecialchars($search)?>"></div>
      <select name="sort" class="fsel" onchange="this.form.submit()">
        <option value="newest" <?=$sort=='newest'?'selected':''?>>Newest First</option>
        <option value="oldest" <?=$sort=='oldest'?'selected':''?>>Oldest First</option>
        <option value="title"  <?=$sort=='title'?'selected':''?>>Title A–Z</option>
      </select>
      <button class="btn btn-primary" style="padding:9px 14px;font-size:13px;"><i data-feather="search" style="width:14px;"></i></button>
      <?php if($search): ?><a href="epapers.php" class="btn" style="padding:9px 14px;font-size:13px;background:#f1f5f9;color:#64748b;"><i data-feather="x" style="width:14px;"></i></a><?php endif; ?>
    </div></form>

    <div class="table-card">
      <div class="tc-hd">
        <div><div style="font-size:15px;font-weight:700;">E-Paper Archive</div>
          <div style="font-size:13px;color:#64748b;"><?=number_format($total)?> edition<?=$total!=1?'s':''?>
          <?php if($search): ?> matching "<strong><?=htmlspecialchars($search)?></strong>"<?php endif;?></div>
        </div>
      </div>
      <table class="content-table">
        <thead><tr><th width="70">Cover</th><th>Title</th><th>Date</th><th>File</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($epapers)): ?>
          <tr><td colspan="5"><div class="empty-st">
            <i data-feather="file" style="width:36px;color:#e2e8f0;display:block;margin:0 auto 10px;"></i>
            <strong>No e-papers found</strong>
            <p><?=$search?'Try a different search term.':'Upload your first edition using the form.'?></p>
          </div></td></tr>
        <?php else: foreach($epapers as $ep): ?>
          <tr>
            <td>
              <img src="../assets/epapers/<?=htmlspecialchars($ep['thumbnail']??'')?>" onerror="this.src='../assets/images/default-post.jpg'"
                   style="width:48px;height:65px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
            </td>
            <td><strong style="font-size:14px;"><?=htmlspecialchars($ep['title'])?></strong></td>
            <td style="font-size:13px;color:#64748b;white-space:nowrap;"><?=format_date($ep['paper_date'])?></td>
            <td>
              <span style="font-size:11px;color:#94a3b8;background:#f8fafc;padding:3px 8px;border-radius:6px;border:1px solid #e2e8f0;">PDF</span>
            </td>
            <td>
              <div style="display:flex;gap:7px;">
                <a href="../assets/epapers/<?=htmlspecialchars($ep['file_path'])?>" target="_blank"
                   class="btn" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:5px 11px;font-size:12px;font-weight:600;">
                  <i data-feather="eye" style="width:13px;"></i> View
                </a>
                <a href="?delete=<?=$ep['id']?>&<?=http_build_query(array_filter(['s'=>$search,'sort'=>$sort,'page'=>$page]))?>"
                   class="btn btn-danger" style="padding:5px 11px;font-size:12px;"
                   onclick="return confirm('Delete this e-paper permanently?')">
                  <i data-feather="trash-2" style="width:13px;"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if($total_pages>1): ?><div class="pagi">
        <a href="<?=ep_url(['page'=>max(1,$page-1)])?>" class="pb <?=$page<=1?'dis':''?>"><i data-feather="chevron-left" style="width:13px;"></i></a>
        <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
        <a href="<?=ep_url(['page'=>$i])?>" class="pb <?=$i==$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
        <a href="<?=ep_url(['page'=>min($total_pages,$page+1)])?>" class="pb <?=$page>=$total_pages?'dis':''?>"><i data-feather="chevron-right" style="width:13px;"></i></a>
      </div><?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
