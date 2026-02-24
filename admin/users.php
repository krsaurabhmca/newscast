<?php
$page_title = "Contributors & Team";
include 'includes/header.php';
if (!is_admin()) { redirect('admin/dashboard.php', 'Access denied.', 'danger'); }

$errors = []; $form_values = [];

// Add User
if (isset($_POST['add_user'])) {
    $form_values = ['username'=>clean($_POST['username']??''), 'email'=>clean($_POST['email']??''), 'role'=>clean($_POST['role']??'editor')];
    $password = $_POST['password'] ?? '';
    if (empty($form_values['username'])) $errors['username'] = 'Username is required.';
    elseif (strlen($form_values['username']) < 3) $errors['username'] = 'Must be at least 3 characters.';
    if (empty($form_values['email'])) $errors['email'] = 'Email is required.';
    elseif (!filter_var($form_values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    if (empty($password)) $errors['password'] = 'Password is required.';
    elseif (strlen($password) < 6) $errors['password'] = 'Must be at least 6 characters.';
    if (empty($errors)) {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
            $check->execute([$form_values['username'], $form_values['email']]);
            if ($check->fetch()) { $errors['general'] = 'Username or email already exists.'; }
            else {
                $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES(?,?,?,?)")
                    ->execute([$form_values['username'],$form_values['email'],password_hash($password,PASSWORD_DEFAULT),$form_values['role']]);
                redirect('admin/users.php', 'Team member added successfully!');
            }
        } catch (PDOException $e) { $errors['general'] = 'Error: '.$e->getMessage(); }
    }
}

// Delete
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    if ($del !== (int)$_SESSION['user_id']) { $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del]); redirect('admin/users.php','User removed.'); }
    else redirect('admin/users.php','Cannot delete your own account.','danger');
}

// Search / Filter / Sort / Paginate
$search = trim($_GET['s'] ?? ''); $role_f = trim($_GET['role'] ?? ''); $sort = $_GET['sort'] ?? 'newest';
$page = max(1,(int)($_GET['page']??1)); $per_page = 15;
$sort_map = ['newest'=>'u.created_at DESC','oldest'=>'u.created_at ASC','name'=>'u.username ASC'];
$order_by = $sort_map[$sort] ?? 'u.created_at DESC';
$wheres=[]; $params=[];
if ($search!=='') { $wheres[]='(u.username LIKE ? OR u.email LIKE ?)'; $params[]="%$search%"; $params[]="%$search%"; }
if ($role_f!=='')  { $wheres[]='u.role=?'; $params[]=$role_f; }
$where_sql = $wheres ? 'WHERE '.implode(' AND ',$wheres) : '';
$cs=$pdo->prepare("SELECT COUNT(*) FROM users u $where_sql"); $cs->execute($params);
$total=(int)$cs->fetchColumn(); $total_pages=max(1,ceil($total/$per_page));
$page=min($page,$total_pages); $offset=($page-1)*$per_page;
$ds=$pdo->prepare("SELECT * FROM users u $where_sql ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$ds->execute($params); $users=$ds->fetchAll();
function u_url(array $ov=[]): string { $b=array_merge($_GET,$ov); return '?'.http_build_query(array_filter($b,fn($v)=>$v!=='')); }
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
.sb{position:relative;flex:1;min-width:180px;} .sb input{padding-left:36px;} .sb .sb-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;width:15px;height:15px;pointer-events:none;z-index:1;}
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
      <div style="width:35px;height:35px;border-radius:9px;background:#eef2ff;color:var(--primary);display:flex;align-items:center;justify-content:center;">
        <i data-feather="user-plus" style="width:16px;"></i></div>
      <div><div style="font-size:14px;font-weight:700;">Add Contributor</div><div style="font-size:11px;color:#94a3b8;">Editors &amp; Admins</div></div>
    </div>
    <div class="form-card-bd">
      <?php if (isset($errors['general'])): ?>
      <div class="err-banner"><i data-feather="alert-circle" style="width:15px;margin-right:5px;"></i><?= htmlspecialchars($errors['general']) ?></div>
      <?php endif; ?>
      <form method="POST" novalidate>
        <div class="form-group">
          <label class="field-label">Username <span style="color:#ef4444">*</span></label>
          <input type="text" name="username" class="form-control <?=isset($errors['username'])?'is-error':''?>" value="<?=htmlspecialchars($form_values['username']??'')?>" placeholder="e.g. rajesh_editor">
          <?php if(isset($errors['username'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['username']?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="field-label">Email <span style="color:#ef4444">*</span></label>
          <input type="email" name="email" class="form-control <?=isset($errors['email'])?'is-error':''?>" value="<?=htmlspecialchars($form_values['email']??'')?>" placeholder="editor@newscast.com">
          <?php if(isset($errors['email'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['email']?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="field-label">Password <span style="color:#ef4444">*</span></label>
          <div style="position:relative;">
            <input type="password" name="password" id="pwdF" class="form-control <?=isset($errors['password'])?'is-error':''?>" placeholder="Min 6 chars" style="padding-right:42px;">
            <button type="button" onclick="var f=document.getElementById('pwdF');f.type=f.type=='password'?'text':'password';" style="position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;"><i data-feather="eye" style="width:15px;"></i></button>
          </div>
          <?php if(isset($errors['password'])): ?><div class="field-err"><i data-feather="alert-circle" style="width:12px;"></i><?=$errors['password']?></div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="field-label">Role</label>
          <select name="role" class="form-control">
            <option value="editor" <?=($form_values['role']??'editor')==='editor'?'selected':''?>>✏️ Editor / Journalist</option>
            <option value="admin"  <?=($form_values['role']??'')==='admin'?'selected':''?>>🛡️ Administrator</option>
          </select>
        </div>
        <button type="submit" name="add_user" class="btn btn-primary" style="width:100%;justify-content:center;">
          <i data-feather="user-plus" style="width:15px;"></i> Add to Team
        </button>
      </form>
    </div>
  </div>

  <!-- TABLE -->
  <div>
    <form method="GET"><div class="table-toolbar">
      <div class="sb"><i data-feather="search"></i><input type="text" name="s" class="form-control" placeholder="Search name or email…" value="<?=htmlspecialchars($search)?>"></div>
      <select name="role" class="fsel" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <option value="admin"  <?=$role_f=='admin'?'selected':''?>>Admin</option>
        <option value="editor" <?=$role_f=='editor'?'selected':''?>>Editor</option>
      </select>
      <select name="sort" class="fsel" onchange="this.form.submit()">
        <option value="newest" <?=$sort=='newest'?'selected':''?>>Newest</option>
        <option value="oldest" <?=$sort=='oldest'?'selected':''?>>Oldest</option>
        <option value="name"   <?=$sort=='name'?'selected':''?>>Name A–Z</option>
      </select>
      <button class="btn btn-primary" style="padding:9px 14px;font-size:13px;"><i data-feather="search" style="width:14px;"></i></button>
      <?php if($search||$role_f): ?><a href="users.php" class="btn" style="padding:9px 14px;font-size:13px;background:#f1f5f9;color:#64748b;"><i data-feather="x" style="width:14px;"></i></a><?php endif; ?>
    </div></form>

    <div class="table-card">
      <div class="tc-hd">
        <div><div style="font-size:15px;font-weight:700;">Team Members</div><div style="font-size:13px;color:#64748b;"><?=number_format($total)?> member<?=$total!=1?'s':''?></div></div>
      </div>
      <table class="content-table">
        <thead><tr><th>Member</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
        <tbody>
        <?php if(empty($users)): ?>
          <tr><td colspan="5"><div class="empty-st"><strong>No members found</strong><p>Adjust search or add someone above.</p></div></td></tr>
        <?php else: foreach($users as $u): ?>
          <tr>
            <td><div style="display:flex;align-items:center;gap:9px;">
              <?php
                $u_img = !empty($u['profile_image']) ? get_profile_image($u['profile_image']) : null;
              ?>
              <?php if ($u_img): ?>
                <img src="<?php echo $u_img; ?>"
                     onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';"
                     style="width:34px;height:34px;border-radius:9px;object-fit:cover;border:1.5px solid #e2e8f0;flex-shrink:0;"
                     alt="<?php echo htmlspecialchars($u['username']); ?>">
                <div style="width:34px;height:34px;border-radius:9px;background:<?=$u['role']==='admin'?'#fee2e2':'#eff6ff'?>;display:none;align-items:center;justify-content:center;font-weight:800;color:<?=$u['role']==='admin'?'#dc2626':'#2563eb'?>;flex-shrink:0;"><?=strtoupper(substr($u['username'],0,1))?></div>
              <?php else: ?>
                <div style="width:34px;height:34px;border-radius:9px;background:<?=$u['role']==='admin'?'#fee2e2':'#eff6ff'?>;display:flex;align-items:center;justify-content:center;font-weight:800;color:<?=$u['role']==='admin'?'#dc2626':'#2563eb'?>;flex-shrink:0;"><?=strtoupper(substr($u['username'],0,1))?></div>
              <?php endif; ?>
              <strong><?=htmlspecialchars($u['username'])?></strong>
              <?php if((int)$u['id']===(int)$_SESSION['user_id']): ?><span class="badge" style="background:#ecfdf5;color:#065f46;font-size:9px;">You</span><?php endif; ?>
            </div></td>
            <td style="font-size:13px;color:#475569;"><?=htmlspecialchars($u['email'])?></td>
            <td><span class="badge" style="background:<?=$u['role']==='admin'?'#fee2e2':'#eff6ff'?>;color:<?=$u['role']==='admin'?'#991b1b':'#1e40af'?>;"><?=$u['role']==='admin'?'🛡️ Admin':'✏️ Editor'?></span></td>
            <td style="font-size:13px;color:#64748b;white-space:nowrap;"><?=format_date($u['created_at'])?></td>
            <td><?php if((int)$u['id']!==(int)$_SESSION['user_id']): ?>
              <a href="?delete=<?=$u['id']?>&<?=http_build_query(array_filter(['s'=>$search,'role'=>$role_f,'sort'=>$sort,'page'=>$page]))?>" class="btn btn-danger" style="padding:5px 11px;font-size:12px;" onclick="return confirm('Remove this member?')"><i data-feather="trash-2" style="width:13px;"></i></a>
            <?php else: ?><span style="font-size:12px;color:#cbd5e1;">—</span><?php endif; ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      <?php if($total_pages>1): ?><div class="pagi">
        <a href="<?=u_url(['page'=>max(1,$page-1)])?>" class="pb dis<?=$page<=1?' dis':''?>"><i data-feather="chevron-left" style="width:13px;"></i></a>
        <?php for($i=max(1,$page-2);$i<=min($total_pages,$page+2);$i++): ?>
        <a href="<?=u_url(['page'=>$i])?>" class="pb <?=$i==$page?'active':''?>"><?=$i?></a>
        <?php endfor; ?>
        <a href="<?=u_url(['page'=>min($total_pages,$page+1)])?>" class="pb <?=$page>=$total_pages?'dis':''?>"><i data-feather="chevron-right" style="width:13px;"></i></a>
      </div><?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
