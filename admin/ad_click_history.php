<?php
$page_title = "Ad Click History";
include 'includes/header.php';

// ── Filters ────────────────────────────────────────────────────────────────
$filter_ad_id    = isset($_GET['ad_id'])     ? (int)$_GET['ad_id']  : 0;
$filter_event    = isset($_GET['event'])     ? $_GET['event']        : '';
$filter_date_from= isset($_GET['date_from']) ? $_GET['date_from']    : '';
$filter_date_to  = isset($_GET['date_to'])   ? $_GET['date_to']      : '';
$filter_ip       = isset($_GET['ip'])        ? trim($_GET['ip'])     : '';

// ── Pagination ─────────────────────────────────────────────────────────────
$per_page     = 25;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// ── Build WHERE clause ─────────────────────────────────────────────────────
$where  = [];
$params = [];
if ($filter_ad_id) { $where[] = "l.ad_id = :ad_id"; $params[':ad_id'] = $filter_ad_id; }
if ($filter_event && in_array($filter_event, ['ad_click','sponsored_post_click'])) {
    $where[] = "l.event_type = :event_type"; $params[':event_type'] = $filter_event;
}
if ($filter_date_from) { $where[] = "DATE(l.clicked_at) >= :date_from"; $params[':date_from'] = $filter_date_from; }
if ($filter_date_to)   { $where[] = "DATE(l.clicked_at) <= :date_to";   $params[':date_to']   = $filter_date_to;   }
if ($filter_ip)        { $where[] = "l.ip_address LIKE :ip";             $params[':ip']        = '%'.$filter_ip.'%'; }
$where_sql = $where ? "WHERE ".implode(" AND ",$where) : "";

// ── Stats ──────────────────────────────────────────────────────────────────
try {
    $stats_today = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE DATE(clicked_at)=CURDATE()")->fetchColumn();
    $stats_week  = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
    $stats_month = $pdo->query("SELECT COUNT(*) FROM ad_click_logs WHERE clicked_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
    $stats_total = $pdo->query("SELECT COUNT(*) FROM ad_click_logs")->fetchColumn();
    // Unique IPs
    $stats_unique_ips = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM ad_click_logs")->fetchColumn();
} catch (Exception $e) {
    $stats_today = $stats_week = $stats_month = $stats_total = $stats_unique_ips = 0;
}

// ── Count for pagination ───────────────────────────────────────────────────
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_click_logs l $where_sql");
    $count_stmt->execute($params);
    $total_rows  = (int)$count_stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_rows / $per_page));
} catch (Exception $e) { $total_rows = $total_pages = 0; }

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
} catch (Exception $e) { $logs = []; }

// ── Fetch ads for filter dropdown ─────────────────────────────────────────
try { $all_ads = $pdo->query("SELECT id, name FROM ads ORDER BY name ASC")->fetchAll(); }
catch (Exception $e) { $all_ads = []; }

// ── IP Geolocation helper (free ip-api.com, no key required) ───────────────
function get_geo_location(string $ip): array {
    // Skip private/localhost IPs
    if (in_array($ip, ['127.0.0.1','::1','0.0.0.0']) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return ['city' => 'Localhost', 'country' => 'Local', 'flag' => '🏠', 'isp' => ''];
    }
    $cache_key = 'geo_' . md5($ip);
    // Use session-level cache to avoid repeated API calls per page load
    if (isset($_SESSION[$cache_key])) return $_SESSION[$cache_key];

    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $raw = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,isp", false, $ctx);
    if ($raw) {
        $data = json_decode($raw, true);
        if (isset($data['status']) && $data['status'] === 'success') {
            $cc   = strtoupper($data['countryCode'] ?? '');
            // Convert country code to flag emoji
            $flag = $cc ? implode('', array_map(fn($c) => mb_chr(0x1F1E6 + ord($c) - ord('A')), str_split($cc))) : '🌍';
            $result = [
                'city'    => $data['city']    ?? '',
                'country' => $data['country'] ?? '',
                'flag'    => $flag,
                'isp'     => $data['isp']     ?? '',
            ];
            $_SESSION[$cache_key] = $result;
            return $result;
        }
    }
    return ['city' => '', 'country' => 'Unknown', 'flag' => '🌍', 'isp' => ''];
}

// ── CSV Export ─────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $export_stmt = $pdo->prepare("SELECT l.id,l.event_type,l.ad_name,l.ad_location,l.ip_address,l.user_agent,l.referer_url,l.clicked_at FROM ad_click_logs l $where_sql ORDER BY l.clicked_at DESC");
        $export_stmt->execute($params);
        $export_rows = $export_stmt->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ad_click_history_'.date('Ymd_His').'.csv"');
        $out = fopen('php://output','w');
        fputcsv($out,['#','Event Type','Ad / Post Name','Ad Slot','IP Address','City','Country','ISP','User Agent','Referer URL','Clicked At']);
        foreach ($export_rows as $row) {
            $geo = get_geo_location($row['ip_address']);
            fputcsv($out,[$row['id'],$row['event_type'],$row['ad_name'],$row['ad_location'],$row['ip_address'],$geo['city'],$geo['country'],$geo['isp'],$row['user_agent'],$row['referer_url'],$row['clicked_at']]);
        }
        fclose($out); exit();
    } catch (Exception $e) {}
}

// ── Query string for pagination ────────────────────────────────────────────
$qs_base = http_build_query(array_filter(['ad_id'=>$filter_ad_id?:'','event'=>$filter_event,'date_from'=>$filter_date_from,'date_to'=>$filter_date_to,'ip'=>$filter_ip]));

// Location colour map
$loc_colors = [
    'header'          => ['bg'=>'#ede9fe','color'=>'#7c3aed'],
    'sidebar'         => ['bg'=>'#dbeafe','color'=>'#1d4ed8'],
    'content_top'     => ['bg'=>'#dcfce7','color'=>'#15803d'],
    'content_bottom'  => ['bg'=>'#fef9c3','color'=>'#a16207'],
    'sponsored_post'  => ['bg'=>'#ffedd5','color'=>'#c2410c'],
];
?>
<style>
/* ── Google Font ── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.ach-wrap { font-family: 'Inter', system-ui, sans-serif; }

/* ── Page Header ── */
.ach-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 28px; flex-wrap: wrap; gap: 14px;
}
.ach-header-left h2 {
    margin: 0; font-size: 24px; font-weight: 900; color: #0f172a; letter-spacing: -0.5px;
    display: flex; align-items: center; gap: 10px;
}
.ach-header-left h2 .h-icon {
    width: 42px; height: 42px; background: linear-gradient(135deg,#6366f1,#8b5cf6);
    border-radius: 12px; display: flex; align-items: center; justify-content: center;
    color: #fff; box-shadow: 0 6px 16px rgba(99,102,241,.3);
}
.ach-header-left p { margin: 6px 0 0; font-size: 13px; color: #64748b; }
.ach-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-ghost { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:#f1f5f9;color:#475569;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;transition:.2s; }
.btn-ghost:hover { background:#e2e8f0; }
.btn-export { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border-radius:10px;text-decoration:none;font-size:13px;font-weight:700;box-shadow:0 4px 12px rgba(16,185,129,.3);transition:.2s; }
.btn-export:hover { transform:translateY(-1px);box-shadow:0 6px 16px rgba(16,185,129,.4); }

/* ── Stats Grid ── */
.ach-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:26px; }
@media(max-width:1100px){.ach-stats{grid-template-columns:repeat(3,1fr);}}
@media(max-width:680px){.ach-stats{grid-template-columns:repeat(2,1fr);}}

.ach-stat {
    background:#fff; border-radius:16px; padding:20px 18px;
    box-shadow:0 2px 12px rgba(0,0,0,.05);
    display:flex; flex-direction:column; gap:8px;
    border-top:3px solid var(--sc);
    transition:.25s;
}
.ach-stat:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.08); }
.ach-stat .sc-icon {
    width:38px;height:38px;border-radius:10px;
    background:var(--sc-bg);color:var(--sc);
    display:flex;align-items:center;justify-content:center;
}
.ach-stat .sc-val { font-size:28px;font-weight:900;color:#0f172a;line-height:1; }
.ach-stat .sc-lbl { font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8; }

/* ── Filter Bar ── */
.ach-filters {
    background:#fff; border-radius:14px; padding:20px 22px;
    box-shadow:0 2px 10px rgba(0,0,0,.04); margin-bottom:22px;
    display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px; align-items:flex-end;
}
.ach-filters .fg { margin:0; }
.ach-filters label { font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;color:#64748b;margin-bottom:5px;display:block; }
.ach-filters .form-control { font-size:13px;padding:9px 12px;border-radius:9px; }
.ach-filter-btns { display:flex;gap:8px; }
.btn-filter { display:inline-flex;align-items:center;gap:6px;padding:9px 18px;background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;flex:1;justify-content:center; }
.btn-filter:hover { transform:translateY(-1px); }
.btn-clear { display:inline-flex;align-items:center;justify-content:center;padding:9px 12px;background:#f1f5f9;color:#64748b;border-radius:9px;text-decoration:none;transition:.2s; }
.btn-clear:hover { background:#e2e8f0; }

/* ── Table Card ── */
.ach-table-card { background:#fff;border-radius:16px;box-shadow:0 2px 14px rgba(0,0,0,.05);overflow:hidden; }
.ach-table-top { display:flex;justify-content:space-between;align-items:center;padding:20px 22px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;gap:10px; }
.ach-table-top h3 { margin:0;font-size:16px;font-weight:800;color:#0f172a; }
.ach-table-top .rc { font-size:12px;color:#94a3b8; }

table.ach-tbl { width:100%;border-collapse:collapse; }
table.ach-tbl thead tr { background:#f8fafc; }
table.ach-tbl thead th { padding:12px 16px;font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:800;text-align:left;white-space:nowrap; }
table.ach-tbl tbody tr { border-bottom:1px solid #f8fafc;transition:.15s; }
table.ach-tbl tbody tr:hover { background:#fafbff; }
table.ach-tbl tbody tr:last-child { border-bottom:none; }
table.ach-tbl tbody td { padding:13px 16px;vertical-align:middle;font-size:13px; }

/* Badges */
.ev-badge { display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap; }
.ev-ad      { background:#eff6ff;color:#3b82f6; }
.ev-sponsor { background:#fef3c7;color:#d97706; }

.loc-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px; }

/* IP + Geo Cell */
.ip-cell { display:flex;flex-direction:column;gap:3px; }
.ip-mono { font-family:monospace;font-size:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:2px 8px;border-radius:6px;color:#334155;display:inline-block;width:fit-content; }
.geo-line { display:flex;align-items:center;gap:4px;font-size:11px;color:#64748b;font-weight:500; }
.geo-flag { font-size:14px; }
.geo-isp { font-size:10px;color:#94a3b8;margin-top:1px; }
.geo-loading { font-size:11px;color:#94a3b8;font-style:italic; }

/* Date Cell */
.date-cell { white-space:nowrap; }
.date-main { font-weight:700;color:#334155;font-size:13px; }
.date-time { font-size:11px;color:#94a3b8;margin-top:2px; }

/* Referer */
.ref-link { color:#6366f1;text-decoration:none;font-size:12px;display:block;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.ref-link:hover { text-decoration:underline; }

/* Pagination */
.ach-pagination { display:flex;gap:6px;justify-content:center;flex-wrap:wrap;padding:22px; }
.ach-pagination a, .ach-pagination span {
    display:inline-flex;align-items:center;justify-content:center;
    width:36px;height:36px;border-radius:9px;font-size:13px;font-weight:700;
    text-decoration:none;border:1px solid #e2e8f0;color:#475569;transition:.15s;
}
.ach-pagination a:hover { background:#6366f1;color:#fff;border-color:#6366f1; }
.ach-pagination .pg-active { background:#6366f1;color:#fff;border-color:#6366f1; }

/* Empty state */
.ach-empty { text-align:center;padding:70px 20px;color:#94a3b8; }
.ach-empty svg { width:52px;height:52px;margin:0 auto 16px;display:block;color:#e2e8f0; }
.ach-empty h4 { margin:0 0 6px;font-size:16px;font-weight:700;color:#cbd5e1; }
.ach-empty p  { margin:0;font-size:13px; }
</style>

<div class="ach-wrap">

<!-- ── Page Header ── -->
<div class="ach-header">
    <div class="ach-header-left">
        <h2>
            <span class="h-icon"><i data-feather="mouse-pointer" style="width:20px;height:20px;"></i></span>
            Ad Click History
        </h2>
        <p>Real-time log of every ad interaction — IP address, geolocation, date, time &amp; event type.</p>
    </div>
    <div class="ach-header-actions">
        <a href="ads.php" class="btn-ghost">
            <i data-feather="arrow-left" style="width:15px;"></i> Ad Campaigns
        </a>
        <a href="?<?php echo $qs_base; ?>&export=csv" class="btn-export">
            <i data-feather="download" style="width:15px;"></i> Export CSV
        </a>
    </div>
</div>

<!-- ── Stats ── -->
<div class="ach-stats">
    <div class="ach-stat" style="--sc:#6366f1;--sc-bg:#eff6ff;">
        <div class="sc-icon"><i data-feather="mouse-pointer" style="width:18px;"></i></div>
        <div class="sc-val"><?php echo number_format($stats_today); ?></div>
        <div class="sc-lbl">Clicks Today</div>
    </div>
    <div class="ach-stat" style="--sc:#f59e0b;--sc-bg:#fef3c7;">
        <div class="sc-icon"><i data-feather="calendar" style="width:18px;"></i></div>
        <div class="sc-val"><?php echo number_format($stats_week); ?></div>
        <div class="sc-lbl">Last 7 Days</div>
    </div>
    <div class="ach-stat" style="--sc:#10b981;--sc-bg:#d1fae5;">
        <div class="sc-icon"><i data-feather="trending-up" style="width:18px;"></i></div>
        <div class="sc-val"><?php echo number_format($stats_month); ?></div>
        <div class="sc-lbl">Last 30 Days</div>
    </div>
    <div class="ach-stat" style="--sc:#ef4444;--sc-bg:#fee2e2;">
        <div class="sc-icon"><i data-feather="bar-chart-2" style="width:18px;"></i></div>
        <div class="sc-val"><?php echo number_format($stats_total); ?></div>
        <div class="sc-lbl">All-Time Total</div>
    </div>
    <div class="ach-stat" style="--sc:#8b5cf6;--sc-bg:#ede9fe;">
        <div class="sc-icon"><i data-feather="globe" style="width:18px;"></i></div>
        <div class="sc-val"><?php echo number_format($stats_unique_ips); ?></div>
        <div class="sc-lbl">Unique Visitors</div>
    </div>
</div>

<!-- ── Filters ── -->
<form method="GET" class="ach-filters">
    <div class="fg">
        <label>Ad / Campaign</label>
        <select name="ad_id" class="form-control">
            <option value="">All Ads</option>
            <?php foreach ($all_ads as $a): ?>
                <option value="<?php echo $a['id']; ?>" <?php echo $filter_ad_id==$a['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($a['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="fg">
        <label>Event Type</label>
        <select name="event" class="form-control">
            <option value="">All Events</option>
            <option value="ad_click"             <?php echo $filter_event==='ad_click'?'selected':''; ?>>Ad Click</option>
            <option value="sponsored_post_click" <?php echo $filter_event==='sponsored_post_click'?'selected':''; ?>>Sponsored Post</option>
        </select>
    </div>
    <div class="fg">
        <label>From Date</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
    </div>
    <div class="fg">
        <label>To Date</label>
        <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
    </div>
    <div class="fg">
        <label>IP Address</label>
        <input type="text" name="ip" class="form-control" placeholder="e.g. 192.168" value="<?php echo htmlspecialchars($filter_ip); ?>">
    </div>
    <div class="fg">
        <label>&nbsp;</label>
        <div class="ach-filter-btns">
            <button type="submit" class="btn-filter">
                <i data-feather="filter" style="width:13px;"></i> Filter
            </button>
            <a href="ad_click_history.php" class="btn-clear" title="Clear filters">
                <i data-feather="x" style="width:15px;"></i>
            </a>
        </div>
    </div>
</form>

<!-- ── Table ── -->
<div class="ach-table-card">
    <div class="ach-table-top">
        <h3>
            Click Log &nbsp;
            <span style="font-size:13px;font-weight:500;color:#94a3b8;"><?php echo number_format($total_rows); ?> records</span>
        </h3>
        <span class="rc">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
    </div>

    <div class="table-responsive">
        <table class="ach-tbl">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Ad / Post</th>
                    <th>Ad Slot</th>
                    <th>IP &amp; Location</th>
                    <th>Date &amp; Time</th>
                    <th>Referer</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7">
                        <div class="ach-empty">
                            <i data-feather="mouse-pointer" style="width:52px;height:52px;color:#e2e8f0;display:block;margin:0 auto 16px;"></i>
                            <h4>No click records found</h4>
                            <p>
                                <?php if ($where): ?>
                                    No records match your filters. <a href="ad_click_history.php" style="color:#6366f1;">Clear filters</a>
                                <?php else: ?>
                                    Clicks will appear here as visitors interact with your ads.
                                <?php endif; ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
            <?php foreach ($logs as $idx => $log): ?>
            <?php
                $geo     = get_geo_location($log['ip_address']);
                $lc      = $loc_colors[$log['ad_location']] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
            ?>
                <tr>
                    <td style="color:#cbd5e1;font-size:12px;font-weight:600;"><?php echo $offset+$idx+1; ?></td>

                    <td>
                        <?php if ($log['event_type']==='ad_click'): ?>
                            <span class="ev-badge ev-ad">
                                <i data-feather="target" style="width:11px;"></i> Ad Click
                            </span>
                        <?php else: ?>
                            <span class="ev-badge ev-sponsor">
                                <i data-feather="star" style="width:11px;"></i> Sponsored
                            </span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div style="font-weight:700;color:#0f172a;max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?php echo htmlspecialchars($log['ad_name'] ?? '—'); ?>
                        </div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                            <?php echo $log['ad_id'] ? 'Ad #'.$log['ad_id'] : ($log['post_id'] ? 'Post #'.$log['post_id'] : ''); ?>
                        </div>
                    </td>

                    <td>
                        <span class="loc-badge" style="background:<?php echo $lc['bg']; ?>;color:<?php echo $lc['color']; ?>;">
                            <?php echo htmlspecialchars(str_replace('_',' ',$log['ad_location'] ?? '—')); ?>
                        </span>
                    </td>

                    <td>
                        <div class="ip-cell">
                            <span class="ip-mono"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                            <?php if ($geo['country'] !== 'Unknown' && $geo['country'] !== ''): ?>
                            <div class="geo-line">
                                <span class="geo-flag"><?php echo $geo['flag']; ?></span>
                                <span><?php echo htmlspecialchars(($geo['city'] ? $geo['city'].', ' : '').$geo['country']); ?></span>
                            </div>
                            <?php if ($geo['isp']): ?>
                            <div class="geo-isp"><?php echo htmlspecialchars(mb_strimwidth($geo['isp'],0,40,'…')); ?></div>
                            <?php endif; ?>
                            <?php elseif ($geo['country'] === 'Local'): ?>
                            <div class="geo-line"><span class="geo-flag">🏠</span><span>Localhost</span></div>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td class="date-cell">
                        <div class="date-main"><?php echo date('j M Y', strtotime($log['clicked_at'])); ?></div>
                        <div class="date-time"><?php echo date('h:i:s A', strtotime($log['clicked_at'])); ?></div>
                    </td>

                    <td>
                        <?php if ($log['referer_url']): ?>
                            <?php $parsed=parse_url($log['referer_url']); ?>
                            <a href="<?php echo htmlspecialchars($log['referer_url']); ?>" target="_blank" rel="noopener"
                               title="<?php echo htmlspecialchars($log['referer_url']); ?>"
                               class="ref-link">
                                <?php echo htmlspecialchars(($parsed['host']??'').($parsed['path']??'')); ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#e2e8f0;font-size:12px;">—</span>
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
    <div class="ach-pagination">
        <?php if ($current_page > 1): ?>
            <a href="?<?php echo $qs_base; ?>&page=<?php echo $current_page-1; ?>">‹</a>
        <?php endif; ?>
        <?php
        $sp = max(1,$current_page-3); $ep = min($total_pages,$current_page+3);
        if ($sp>1) echo '<span>…</span>';
        for ($p=$sp;$p<=$ep;$p++): ?>
            <?php if ($p===$current_page): ?>
                <span class="pg-active"><?php echo $p; ?></span>
            <?php else: ?>
                <a href="?<?php echo $qs_base; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
            <?php endif; ?>
        <?php endfor;
        if ($ep<$total_pages) echo '<span>…</span>'; ?>
        <?php if ($current_page<$total_pages): ?>
            <a href="?<?php echo $qs_base; ?>&page=<?php echo $current_page+1; ?>">›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- .ach-wrap -->

<?php include 'includes/footer.php'; ?>
