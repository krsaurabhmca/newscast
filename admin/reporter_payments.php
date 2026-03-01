<?php
$page_title = "Reporter Payments";
include 'includes/header.php';
if (!is_admin()) { redirect('admin/dashboard.php', 'Access denied.', 'danger'); }

$reporters  = $pdo->query("SELECT * FROM users ORDER BY username ASC")->fetchAll();
$flash = ''; $flash_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $reporter_id = (int)$_POST['reporter_id'];
    $amount      = (float)$_POST['amount'];
    $pay_type    = clean($_POST['pay_type']);
    $pay_date    = clean($_POST['pay_date'] ?? date('Y-m-d'));
    $note        = clean($_POST['note'] ?? '');
    $status      = clean($_POST['status'] ?? 'paid');
    if ($reporter_id && $amount > 0) {
        try {
            $pdo->prepare("INSERT INTO reporter_payments (reporter_id,amount,pay_type,pay_date,note,status) VALUES(?,?,?,?,?,?)")
                ->execute([$reporter_id,$amount,$pay_type,$pay_date,$note,$status]);
            $flash = "Payment recorded!"; $flash_type = 'success';
        } catch (PDOException $e) { $flash = "Error: ".$e->getMessage(); $flash_type = 'danger'; }
    }
}
if (isset($_GET['del_pay'])) {
    try { $pdo->prepare("DELETE FROM reporter_payments WHERE id=?")->execute([(int)$_GET['del_pay']]); $flash = "Deleted."; $flash_type = 'warning'; } catch (Exception $e){}
}

$filter_uid   = (int)($_GET['uid']   ?? 0);
$filter_month = clean($_GET['month'] ?? '');
$filter_type  = clean($_GET['ptype'] ?? '');
$where = []; $params = [];
if ($filter_uid)   { $where[] = 'p.reporter_id=?'; $params[] = $filter_uid; }
if ($filter_month) { $where[] = 'DATE_FORMAT(p.pay_date,"%Y-%m")=?'; $params[] = $filter_month; }
if ($filter_type)  { $where[] = 'p.pay_type=?'; $params[] = $filter_type; }
$wsql = $where ? 'WHERE '.implode(' AND ',$where) : '';
$pstmt = $pdo->prepare("SELECT p.*,u.username FROM reporter_payments p JOIN users u ON u.id=p.reporter_id $wsql ORDER BY p.pay_date DESC LIMIT 200");
$pstmt->execute($params); $payments = $pstmt->fetchAll();
$total_paid=0; $total_pending=0;
foreach($payments as $p){ if($p['status']==='paid') $total_paid+=$p['amount']; if($p['status']==='pending') $total_pending+=$p['amount']; }
$rsummary=[];
foreach($payments as $p){ $rid=$p['reporter_id']; if(!isset($rsummary[$rid])) $rsummary[$rid]=['name'=>$p['username'],'paid'=>0,'pending'=>0,'total'=>0]; $rsummary[$rid]['total']+=$p['amount']; if($p['status']==='paid') $rsummary[$rid]['paid']+=$p['amount']; if($p['status']==='pending') $rsummary[$rid]['pending']+=$p['amount']; }
$ac=[];
try { foreach($pdo->query("SELECT author_id,COUNT(*) as cnt FROM posts WHERE author_id IS NOT NULL GROUP BY author_id")->fetchAll() as $a) $ac[$a['author_id']]=$a['cnt']; } catch(Exception $e){}
?>
<style>
.pay-layout{display:grid;grid-template-columns:310px 1fr;gap:22px;}
.panel{background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;margin-bottom:18px;}
.panel-hd{padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.panel-hd .ico{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;}
.panel-bd{padding:18px;}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:18px;}
.stat-card{background:#fff;border-radius:12px;padding:14px;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.stat-val{font-size:19px;font-weight:800;}
.stat-lbl{font-size:11px;color:#64748b;font-weight:600;}
.toolbar-bar{display:flex;gap:8px;flex-wrap:wrap;background:#fff;padding:14px;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:16px;}
.fsel{padding:8px 12px;border:1px solid #e2e8f0;border-radius:9px;font-size:13px;font-weight:600;color:#475569;background:#fff;}
.badge-paid{background:#dcfce7;color:#16a34a;font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;}
.badge-pending{background:#fef3c7;color:#d97706;font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;}
.badge-cancelled{background:#fee2e2;color:#dc2626;font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px;}
.rbar{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid #e2e8f0;margin-bottom:6px;}
.pbar-track{height:5px;background:#f1f5f9;border-radius:3px;margin-top:4px;flex:1;}
.pbar-fill{height:100%;border-radius:3px;background:var(--primary);}
@media(max-width:900px){.pay-layout{grid-template-columns:1fr;}}
</style>
<?php if($flash): ?><div class="alert alert-<?php echo $flash_type; ?>" style="margin-bottom:16px;"><?php echo $flash; ?></div><?php endif; ?>
<div class="stat-cards">
  <div class="stat-card" style="border-left:3px solid #10b981;"><div class="stat-val" style="color:#10b981;">Rs.<?php echo number_format($total_paid,0); ?></div><div class="stat-lbl">Total Paid</div></div>
  <div class="stat-card" style="border-left:3px solid #f59e0b;"><div class="stat-val" style="color:#f59e0b;">Rs.<?php echo number_format($total_pending,0); ?></div><div class="stat-lbl">Pending</div></div>
  <div class="stat-card" style="border-left:3px solid var(--primary);"><div class="stat-val"><?php echo count($payments); ?></div><div class="stat-lbl">Transactions</div></div>
  <div class="stat-card" style="border-left:3px solid #8b5cf6;"><div class="stat-val"><?php echo count($reporters); ?></div><div class="stat-lbl">Team Members</div></div>
</div>
<div class="pay-layout">
<div>
  <div class="panel">
    <div class="panel-hd"><div class="ico" style="background:#ecfdf5;color:#10b981;"><i data-feather="dollar-sign" style="width:15px;"></i></div><div><div style="font-size:14px;font-weight:700;">Record Payment</div></div></div>
    <div class="panel-bd">
      <form method="POST">
        <div class="form-group"><label class="field-label">Reporter *</label>
          <select name="reporter_id" class="form-control" required><option value="">-- Select --</option><?php foreach($reporters as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['username']); ?> (<?php echo $r['role']; ?>)</option><?php endforeach; ?></select>
        </div>
        <div class="form-group"><label class="field-label">Amount (Rs.) *</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required></div>
        <div class="form-group"><label class="field-label">Type</label>
          <select name="pay_type" class="form-control"><option value="salary">Salary</option><option value="bonus">Bonus</option><option value="article_fee">Article Fee</option><option value="expense">Expense</option><option value="advance">Advance</option><option value="other">Other</option></select>
        </div>
        <div class="form-group"><label class="field-label">Date</label><input type="date" name="pay_date" class="form-control" value="<?php echo date('Y-m-d'); ?>"></div>
        <div class="form-group"><label class="field-label">Status</label>
          <select name="status" class="form-control"><option value="paid">Paid</option><option value="pending">Pending</option><option value="cancelled">Cancelled</option></select>
        </div>
        <div class="form-group"><label class="field-label">Note</label><textarea name="note" class="form-control" rows="2"></textarea></div>
        <button type="submit" name="add_payment" class="btn btn-primary" style="width:100%;justify-content:center;"><i data-feather="plus-circle" style="width:14px;"></i> Record</button>
      </form>
    </div>
  </div>
  <div class="panel">
    <div class="panel-hd"><div class="ico" style="background:#fdf4ff;color:#9333ea;"><i data-feather="bar-chart-2" style="width:15px;"></i></div><div><div style="font-size:14px;font-weight:700;">Earnings by Reporter</div></div></div>
    <div class="panel-bd">
      <?php if(empty($rsummary)): ?><div style="text-align:center;color:#94a3b8;padding:20px;">No data yet.</div>
      <?php else: $max=max(array_column($rsummary,'total'))?:1; foreach($rsummary as $rid=>$rs): ?>
      <div class="rbar">
        <div style="width:30px;height:30px;border-radius:50%;background:#eef2ff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:var(--primary);"><?php echo strtoupper(substr($rs['name'],0,1)); ?></div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;"><?php echo htmlspecialchars($rs['name']); ?></div>
          <div class="pbar-track"><div class="pbar-fill" style="width:<?php echo round($rs['total']/$max*100); ?>%;"></div></div>
          <div style="font-size:11px;color:#64748b;">Paid:Rs.<?php echo number_format($rs['paid'],0); ?> | Pending:Rs.<?php echo number_format($rs['pending'],0); ?> | Articles:<?php echo $ac[$rid]??0; ?></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<div>
  <form method="GET" class="toolbar-bar">
    <select name="uid" class="fsel" onchange="this.form.submit()"><option value="">All Reporters</option><?php foreach($reporters as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $filter_uid==$r['id']?'selected':''; ?>><?php echo htmlspecialchars($r['username']); ?></option><?php endforeach; ?></select>
    <select name="ptype" class="fsel" onchange="this.form.submit()"><option value="">All Types</option><option value="salary" <?php echo $filter_type=='salary'?'selected':''; ?>>Salary</option><option value="bonus" <?php echo $filter_type=='bonus'?'selected':''; ?>>Bonus</option><option value="article_fee" <?php echo $filter_type=='article_fee'?'selected':''; ?>>Article Fee</option></select>
    <input type="month" name="month" class="fsel" value="<?php echo htmlspecialchars($filter_month); ?>" onchange="this.form.submit()" style="padding:8px 12px;">
    <?php if($filter_uid||$filter_month||$filter_type): ?><a href="reporter_payments.php" class="btn" style="padding:8px 12px;font-size:13px;background:#f1f5f9;color:#64748b;">Clear</a><?php endif; ?>
  </form>
  <div class="panel">
    <div class="panel-hd"><div class="ico" style="background:#eff6ff;color:#2563eb;"><i data-feather="list" style="width:15px;"></i></div><div><div style="font-size:14px;font-weight:700;">Transactions</div><div style="font-size:11px;color:#94a3b8;"><?php echo count($payments); ?> records</div></div></div>
    <table class="content-table">
      <thead><tr><th>Reporter</th><th>Amount</th><th>Type</th><th>Date</th><th>Status</th><th>Note</th><th></th></tr></thead>
      <tbody>
      <?php if(empty($payments)): ?><tr><td colspan="7"><div style="text-align:center;padding:40px;color:#94a3b8;">No records yet.</div></td></tr>
      <?php else: foreach($payments as $p): ?>
        <tr>
          <td style="font-weight:700;font-size:13px;"><?php echo htmlspecialchars($p['username']); ?></td>
          <td style="font-weight:800;color:<?php echo $p['status']==='paid'?'#16a34a':($p['status']==='pending'?'#d97706':'#dc2626'); ?>;">Rs.<?php echo number_format($p['amount'],2); ?></td>
          <td style="font-size:12px;"><?php echo ucfirst(str_replace('_',' ',$p['pay_type'])); ?></td>
          <td style="font-size:12px;color:#64748b;"><?php echo date('d M Y',strtotime($p['pay_date'])); ?></td>
          <td><span class="badge-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
          <td style="font-size:12px;color:#64748b;"><?php echo htmlspecialchars(substr($p['note'],0,40)); ?></td>
          <td><a href="?del_pay=<?php echo $p['id']; ?>" class="btn btn-danger" style="padding:4px 10px;font-size:11px;" onclick="return confirm('Delete?')"><i data-feather="trash-2" style="width:12px;"></i></a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
