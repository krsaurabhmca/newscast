<?php
$page_title = "Ad Click History";
include 'includes/header.php';

// ── Filters ────────────────────────────────────────────────────────────────
$filter_ad_id    = isset($_GET['ad_id'])     ? (int)$_GET['ad_id']           : 0;
$filter_event    = isset($_GET['event'])     ? $_GET['event']                 : '';
$filter_date_from= isset($_GET['date_from']) ? $_GET['date_from']             : '';
$filter_date_to  = isset($_GET['date_to'])   ? $_GET['date_to']               : '';
$filter_ip       = isset($_GET['ip'])        ? trim($_GET['ip'])               : '';

// ── Pagination ─────────────────────────────────────────────────────────────
$per_page    = 25;
$current_page= max(1, (int)($_GET['page'] ?? 1));
$offset      = ($current_page - 1) * $per_page;

// ── Build WHERE clause ─────────────────────────────────────────────────────
$where  = [];
$params = [];

if ($filter_ad_id) {
    $where[]  = "l.ad_id = :ad_id";
    $params[':ad_id'] = $filter_ad_id;
}
if ($filter_event && in_array($filter_event, ['ad_click', 'sponsored_post_click'])) {
    $where[]  = "l.event_type = :event_type";
    $params[':event_type'] = $filter_event;
}
if ($filter_date_from) {
    $where[]  = "DATE(l.clicked_at) >= :date_from";
    $params[':date_from'] = $filter_date_from;
}
if ($filter_date_to) {
    $where[]  = "DATE(l.clicked_at) <= :date_to";
    $params[':date_to'] = $filter_date_to;
}
if ($filter_ip) {
    $where[]  = "l.ip_address LIKE :ip";
    $params[':ip'] = '%' . $filter_ip . '%';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// ── Stats ──────────────────────────────────────────────────────────────────
try {
    $stats_today = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
    $stats_week  = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $stats_month = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $stats_total = $pdo->query("SELECT COUNT(*) FROM ad_click_logs")->fetchColumn();
} catch (Exception $e) {
    $stats_today = $stats_week = $stats_month = $stats_total = 0;
}

// ── Count total rows for pagination ───────────────────────────────────────
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_click_logs l $where_sql");
    $count_stmt->execute($params);
    $total_rows  = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $per_page);
} catch (Exception $e) {
    $total_rows = $total_pages = 0;
}

// ── Fetch logs ─────────────────────────────────────────────────────────────
try {
    $logs_stmt = $pdo->prepare("
        SELECT l.*, a.name AS current_ad_name
        FROM ad_click_logs l
        LEFT JOIN ads a ON l.ad_id = a.id
        $where_sql
        ORDER BY l.clicked_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $logs_stmt->bindValue($k, $v);
    $logs_stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
    $logs_stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
    $logs_stmt->execute();
    $logs = $logs_stmt->fetchAll();
} catch (Exception $e) {
    $logs = [];
}

// ── Fetch all ads for filter dropdown ─────────────────────────────────────
try {
    $all_ads = $pdo->query("SELECT id, name FROM ads ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    $all_ads = [];
}

// ── CSV Export ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $export_stmt = $pdo->prepare("
            SELECT l.id, l.event_type, l.ad_name, l.ad_location, l.ip_address,
                   l.user_agent, l.referer_url, l.clicked_at
            FROM ad_click_logs l
            $where_sql
            ORDER BY l.clicked_at DESC
        ");
        $export_stmt->execute($params);
        $export_rows = $export_stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ad_click_history_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['#', 'Event Type', 'Ad / Post Name', 'Location', 'IP Address', 'User Agent', 'Referer URL', 'Clicked At']);
        foreach ($export_rows as $row) {
            fputcsv($out, [
                $row['id'],
                $row['event_type'],
                $row['ad_name'],
                $row['ad_location'],
                $row['ip_address'],
                $row['user_agent'],
                $row['referer_url'],
                $row['clicked_at'],
            ]);
        }
        fclose($out);
        exit();
    } catch (Exception $e) {
        // fall through to page render
    }
}

// ── Build query string for pagination links ────────────────────────────────
$qs_base = http_build_query(array_filter([
    'ad_id'     => $filter_ad_id ?: '',
    'event'     => $filter_event,
    'date_from' => $filter_date_from,
    'date_to'   => $filter_date_to,
    'ip'        => $filter_ip,
]));
?>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
@media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 500px) { .stats-grid { grid-template-columns: 1fr; } }

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    border-left: 4px solid var(--accent-color);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.stat-card .stat-value {
    font-size: 32px;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
}
.stat-card .stat-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #94a3b8;
}
.stat-card .stat-icon {
    font-size: 22px;
    margin-bottom: 4px;
}

.filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 20px 22px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    margin-bottom: 22px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    align-items: flex-end;
}
.filter-bar .form-group { margin: 0; }
.filter-bar label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 5px; display: block; }
.filter-bar .form-control { font-size: 13px; padding: 8px 12px; }

.history-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 6px;
    font-size: 13px;
}
.history-table thead th {
    padding: 12px 14px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    font-weight: 700;
    background: #f8fafc;
}
.history-table thead th:first-child { border-radius: 8px 0 0 8px; }
.history-table thead th:last-child  { border-radius: 0 8px 8px 0; }
.history-table tbody tr {
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s;
}
.history-table tbody tr:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
.history-table tbody td {
    padding: 12px 14px;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.history-table tbody td:first-child { border-left: 1px solid #f1f5f9; border-radius: 8px 0 0 8px; }
.history-table tbody td:last-child  { border-right: 1px solid #f1f5f9; border-radius: 0 8px 8px 0; }

.badge-event {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.badge-ad      { background: #eff6ff; color: #3b82f6; }
.badge-sponsor { background: #fef3c7; color: #d97706; }

.badge-loc {
    display: inline-block;
    padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.4px;
    background: #f1f5f9; color: #475569;
}

.pagination { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }
.pagination a, .pagination span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; border: 1px solid #e2e8f0; color: #475569;
    transition: all 0.15s;
}
.pagination a:hover { background: #6366f1; color: #fff; border-color: #6366f1; }
.pagination .active-page { background: #6366f1; color: #fff; border-color: #6366f1; }

.ip-tag {
    font-family: monospace; font-size: 12px;
    background: #f8fafc; border: 1px solid #e2e8f0;
    padding: 2px 8px; border-radius: 6px; color: #334155;
}
</style>

<!-- Page Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="margin: 0; font-size: 22px; font-weight: 800; color: #0f172a;">
            <i data-feather="mouse-pointer" style="width:20px; vertical-align: middle; margin-right: 6px; color: #6366f1;"></i>
            Ad Click History
        </h2>
        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">Detailed log of every ad click — date, time, IP address, location & event type.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="ads.php" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; background: #f1f5f9; color: #475569; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600;">
            <i data-feather="arrow-left" style="width:15px;"></i> Ad Campaigns
        </a>
        <a href="?<?php echo $qs_base; ?>&export=csv" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; background: #059669; color: #fff; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600;">
            <i data-feather="download" style="width:15px;"></i> Export CSV
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card" style="--accent-color: #6366f1;">
        <div class="stat-icon">🖱️</div>
        <div class="stat-value"><?php echo number_format($stats_today); ?></div>
        <div class="stat-label">Clicks Today</div>
    </div>
    <div class="stat-card" style="--accent-color: #f59e0b;">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?php echo number_format($stats_week); ?></div>
        <div class="stat-label">Last 7 Days</div>
    </div>
    <div class="stat-card" style="--accent-color: #10b981;">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?php echo number_format($stats_month); ?></div>
        <div class="stat-label">Last 30 Days</div>
    </div>
    <div class="stat-card" style="--accent-color: #ef4444;">
        <div class="stat-icon">∑</div>
        <div class="stat-value"><?php echo number_format($stats_total); ?></div>
        <div class="stat-label">All-Time Total</div>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
    <div class="form-group">
        <label>Ad / Campaign</label>
        <select name="ad_id" class="form-control">
            <option value="">All Ads</option>
            <?php foreach ($all_ads as $a): ?>
                <option value="<?php echo $a['id']; ?>" <?php echo $filter_ad_id == $a['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($a['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Event Type</label>
        <select name="event" class="form-control">
            <option value="">All Events</option>
            <option value="ad_click"             <?php echo $filter_event === 'ad_click'             ? 'selected' : ''; ?>>Ad Click</option>
            <option value="sponsored_post_click" <?php echo $filter_event === 'sponsored_post_click' ? 'selected' : ''; ?>>Sponsored Post Click</option>
        </select>
    </div>
    <div class="form-group">
        <label>From Date</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
    </div>
    <div class="form-group">
        <label>To Date</label>
        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
    </div>
    <div class="form-group">
        <label>IP Address</label>
        <input type="text" name="ip" class="form-control" placeholder="e.g. 192.168" value="<?php echo htmlspecialchars($filter_ip); ?>">
    </div>
    <div class="form-group" style="display: flex; gap: 8px;">
        <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; font-size: 13px; padding: 8px 14px;">
            <i data-feather="filter" style="width:14px;"></i> Filter
        </button>
        <a href="ad_click_history.php" class="btn" style="background: #f1f5f9; color: #475569; text-decoration: none; padding: 8px 12px; font-size: 13px; border-radius: 8px; display: flex; align-items: center;">
            <i data-feather="x" style="width:14px;"></i>
        </a>
    </div>
</form>

<!-- Results Table -->
<div style="background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 22px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
            Click Log
            <span style="font-size: 13px; font-weight: 500; color: #94a3b8; margin-left: 6px;">(<?php echo number_format($total_rows); ?> records)</span>
        </h3>
        <span style="font-size: 12px; color: #94a3b8;">Page <?php echo $current_page; ?> of <?php echo max(1, $total_pages); ?></span>
    </div>

    <div class="table-responsive">
        <table class="history-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Ad / Post Name</th>
                    <th>Location</th>
                    <th>IP Address</th>
                    <th>Date &amp; Time</th>
                    <th>Referer</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 60px; color: #94a3b8;">
                        <i data-feather="mouse-pointer" style="width: 40px; height: 40px; color: #e2e8f0; display: block; margin: 0 auto 12px;"></i>
                        <div style="font-size: 15px; font-weight: 600; margin-bottom: 5px;">No click records found</div>
                        <div style="font-size: 13px;">
                            <?php if ($where): ?>
                                No records match your current filters. <a href="ad_click_history.php" style="color: #6366f1;">Clear filters</a>
                            <?php else: ?>
                                Clicks will appear here as visitors interact with your ads.
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $idx => $log): ?>
                <tr>
                    <td style="color: #94a3b8; font-size: 12px;"><?php echo $offset + $idx + 1; ?></td>
                    <td>
                        <?php if ($log['event_type'] === 'ad_click'): ?>
                            <span class="badge-event badge-ad">
                                <i data-feather="target" style="width:11px;"></i> Ad Click
                            </span>
                        <?php else: ?>
                            <span class="badge-event badge-sponsor">
                                <i data-feather="star" style="width:11px;"></i> Sponsored Post
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: #0f172a; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo htmlspecialchars($log['ad_name'] ?? '—'); ?>
                        </div>
                        <?php if ($log['ad_id']): ?>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Ad #<?php echo $log['ad_id']; ?></div>
                        <?php elseif ($log['post_id']): ?>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Post #<?php echo $log['post_id']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-loc"><?php echo htmlspecialchars(str_replace('_', ' ', $log['ad_location'] ?? '—')); ?></span>
                    </td>
                    <td>
                        <span class="ip-tag"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                    </td>
                    <td style="white-space: nowrap;">
                        <div style="font-weight: 600; color: #334155; font-size: 13px;"><?php echo date('j M Y', strtotime($log['clicked_at'])); ?></div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;"><?php echo date('h:i:s A', strtotime($log['clicked_at'])); ?></div>
                    </td>
                    <td style="max-width: 180px;">
                        <?php if ($log['referer_url']): ?>
                            <a href="<?php echo htmlspecialchars($log['referer_url']); ?>" target="_blank" rel="noopener"
                               title="<?php echo htmlspecialchars($log['referer_url']); ?>"
                               style="color: #6366f1; text-decoration: none; font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px;">
                                <?php
                                $parsed = parse_url($log['referer_url']);
                                echo htmlspecialchars(($parsed['host'] ?? '') . ($parsed['path'] ?? ''));
                                ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #cbd5e1; font-size: 12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($current_page > 1): ?>
            <a href="?<?php echo $qs_base; ?>&page=<?php echo $current_page - 1; ?>">‹</a>
        <?php endif; ?>

        <?php
        $start_p = max(1, $current_page - 3);
        $end_p   = min($total_pages, $current_page + 3);
        if ($start_p > 1) echo '<span>…</span>';
        for ($p = $start_p; $p <= $end_p; $p++):
        ?>
            <?php if ($p === $current_page): ?>
                <span class="active-page"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="?<?php echo $qs_base; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($end_p < $total_pages) echo '<span>…</span>'; ?>

        <?php if ($current_page < $total_pages): ?>
            <a href="?<?php echo $qs_base; ?>&page=<?php echo $current_page + 1; ?>">›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
