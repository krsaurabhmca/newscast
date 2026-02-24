<?php
$page_title = "Manage Posts";
include 'includes/header.php';

// ── Delete ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../assets/images/posts/" . $img)) {
        unlink("../assets/images/posts/" . $img);
    }
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    redirect('admin/posts.php', 'Post deleted successfully!');
}

// ── Search / Filter / Sort params ───────────────────────
$search   = trim($_GET['s']   ?? '');
$status   = trim($_GET['st']  ?? '');
$cat_id   = (int)($_GET['cat'] ?? 0);
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$sort_map = [
    'newest'   => 'p.created_at DESC',
    'oldest'   => 'p.created_at ASC',
    'views'    => 'p.views DESC',
    'title'    => 'p.title ASC',
];
$order_by = $sort_map[$sort] ?? 'p.created_at DESC';

// ── Build dynamic WHERE ─────────────────────────────────
$wheres = [];
$params = [];

if ($search !== '') {
    $wheres[] = 'p.title LIKE ?';
    $params[]  = "%$search%";
}
if ($status !== '') {
    $wheres[] = 'p.status = ?';
    $params[]  = $status;
}
if ($cat_id > 0) {
    $wheres[] = 'EXISTS (SELECT 1 FROM post_categories pc2 WHERE pc2.post_id = p.id AND pc2.category_id = ?)';
    $params[]  = $cat_id;
}

$where_sql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

// ── Count total ─────────────────────────────────────────
$count_sql  = "SELECT COUNT(DISTINCT p.id) FROM posts p $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total       = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// ── Fetch paginated rows ────────────────────────────────
$data_sql = "SELECT p.*, GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS cat_names
             FROM posts p
             LEFT JOIN post_categories pc ON p.id = pc.post_id
             LEFT JOIN categories c ON pc.category_id = c.id
             $where_sql
             GROUP BY p.id
             ORDER BY $order_by
             LIMIT $per_page OFFSET $offset";

$data_stmt = $pdo->prepare($data_sql);
$data_stmt->execute($params);
$posts = $data_stmt->fetchAll();

// ── Category list for filter dropdown ──────────────────
$categories = $pdo->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name ASC")->fetchAll();
?>

<style>
.table-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
.table-toolbar .search-box { position:relative; flex:1; min-width:220px; }
.table-toolbar .search-box input { padding-left:38px; }
.table-toolbar .search-box .sb-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; width:15px; height:15px; pointer-events:none; z-index:1; }
.filter-select { padding:9px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:600; color:#475569; background:white; cursor:pointer; }
.filter-select:focus { outline:none; border-color:var(--primary); }
.sort-btn { padding:9px 14px; border:1px solid #e2e8f0; border-radius:10px; font-size:12px; font-weight:700; color:#475569; background:white; text-decoration:none; display:flex; align-items:center; gap:5px; transition:.15s; white-space:nowrap; }
.sort-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
.sort-btn:hover:not(.active) { background:#f1f5f9; }

.table-card { background:white; border-radius:16px; box-shadow:0 1px 4px rgba(0,0,0,.05); overflow:hidden; }
.table-card-header { padding:20px 25px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.table-meta { font-size:13px; color:#64748b; }
.table-meta strong { color:#0f172a; }

.empty-state { text-align:center; padding:60px 20px; color:#94a3b8; }
.empty-state i { display:block; margin:0 auto 12px; width:40px; color:#e2e8f0; }
.empty-state p { font-size:14px; margin-top:5px; }

.pagination { display:flex; gap:5px; justify-content:center; padding:20px; flex-wrap:wrap; }
.page-btn { width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; color:#475569; background:white; border:1px solid #e2e8f0; transition:.15s; }
.page-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
.page-btn:hover:not(.active) { background:#f8fafc; }
.page-btn.disabled { opacity:.4; pointer-events:none; }
</style>

<?php
// Build URL helper
function paginate_url(array $overrides = []): string {
    $base = array_merge($_GET, $overrides);
    return '?' . http_build_query(array_filter($base, fn($v) => $v !== '' && $v !== '0' && $v !== 0));
}
?>

<!-- Toolbar -->
<form method="GET" action="">
    <div class="table-toolbar">
        <div class="search-box">
            <svg class="sb-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="s" class="form-control" placeholder="Search posts by title…" value="<?= htmlspecialchars($search) ?>">
        </div>

        <select name="st" class="filter-select" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="published" <?= $status=='published'?'selected':'' ?>>Published</option>
            <option value="draft"     <?= $status=='draft'?'selected':'' ?>>Draft</option>
        </select>

        <select name="cat" class="filter-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $cat_id==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
        <button type="submit" class="btn btn-primary" style="padding:9px 18px; font-size:13px;">
            <i data-feather="search" style="width:15px;"></i> Search
        </button>
        <?php if ($search || $status || $cat_id): ?>
        <a href="posts.php" class="btn" style="padding:9px 18px; font-size:13px; background:#f1f5f9; color:#475569;">
            <i data-feather="x" style="width:14px;"></i> Clear
        </a>
        <?php endif; ?>
    </div>
</form>

<!-- Sort Chips -->
<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px;">
    <span style="font-size:12px; color:#94a3b8; font-weight:700; align-self:center;">SORT:</span>
    <?php foreach (['newest'=>'Newest First','oldest'=>'Oldest First','views'=>'Most Views','title'=>'Title A–Z'] as $k=>$label): ?>
    <a href="<?= paginate_url(['sort'=>$k,'page'=>1]) ?>" class="sort-btn <?= $sort==$k?'active':'' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Table Card -->
<div class="table-card">
    <div class="table-card-header">
        <div>
            <h3 style="font-size:16px; font-weight:700; margin:0;">All News Posts</h3>
            <div class="table-meta">
                Showing <strong><?= number_format(min($offset+1,$total)) ?>–<?= number_format(min($offset+$per_page,$total)) ?></strong>
                of <strong><?= number_format($total) ?></strong> post<?= $total!=1?'s':'' ?>
                <?php if ($search): ?> matching "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
            </div>
        </div>
        <a href="post_add.php" class="btn btn-primary" style="font-size:13px;">
            <i data-feather="plus" style="width:15px;"></i> New Post
        </a>
    </div>

    <table class="content-table">
        <thead>
            <tr>
                <th width="65">Image</th>
                <th>
                    <a href="<?= paginate_url(['sort'=>'title','page'=>1]) ?>" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:5px;">
                        Title <?php if($sort=='title'):?><i data-feather="chevron-up" style="width:12px;"></i><?php else:?><i data-feather="chevrons-up-down" style="width:12px; opacity:.4;"></i><?php endif;?>
                    </a>
                </th>
                <th>Category</th>
                <th>Status</th>
                <th>Views</th>
                <th>
                    <a href="<?= paginate_url(['sort'=>$sort=='newest'?'oldest':'newest','page'=>1]) ?>" style="color:inherit; text-decoration:none; display:flex; align-items:center; gap:5px;">
                        Date <?php if(in_array($sort,['newest','oldest'])):?><i data-feather="<?=$sort=='newest'?'chevron-down':'chevron-up'?>" style="width:12px;"></i><?php else:?><i data-feather="chevrons-up-down" style="width:12px; opacity:.4;"></i><?php endif;?>
                    </a>
                </th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($posts)): ?>
            <tr>
                <td colspan="7">
                    <div class="empty-state">
                        <i data-feather="file-text"></i>
                        <strong>No posts found</strong>
                        <p><?= $search||$status||$cat_id ? 'Try changing your search or filters.' : 'Get started by creating your first post.' ?></p>
                        <?php if ($search||$status||$cat_id): ?>
                        <a href="posts.php" style="font-size:13px; color:var(--primary);">Clear filters</a>
                        <?php else: ?>
                        <a href="post_add.php" class="btn btn-primary" style="margin-top:12px; font-size:13px;">Create First Post</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td>
                    <?php if ($post['featured_image']): ?>
                        <img src="../assets/images/posts/<?= htmlspecialchars($post['featured_image']) ?>" style="width:52px;height:52px;border-radius:10px;object-fit:cover;">
                    <?php else: ?>
                        <div style="width:52px;height:52px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                            <i data-feather="image" style="width:18px;color:#cbd5e1;"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong style="font-size:14px;"><?= htmlspecialchars(mb_strimwidth($post['title'],0,70,'…')) ?></strong>
                    <?php if ($post['is_featured']): ?>
                        <span class="badge" style="background:#fff7ed;color:#c2410c;font-size:9px;margin-left:5px;">⭐ Featured</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($post['cat_names']): ?>
                        <span class="badge" style="background:#eef2ff;color:#4338ca;"><?= htmlspecialchars($post['cat_names']) ?></span>
                    <?php else: ?>
                        <span style="font-size:12px;color:#94a3b8;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge badge-<?= $post['status'] ?>">
                        <?= $post['status'] === 'published' ? '● ' : '○ ' ?><?= ucfirst($post['status']) ?>
                    </span>
                </td>
                <td style="font-size:13px;font-weight:600;color:#334155;">
                    <?= number_format($post['views']) ?>
                </td>
                <td style="font-size:13px;color:#64748b;white-space:nowrap;">
                    <?= format_date($post['created_at']) ?>
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <?php if ($post['status'] === 'published' && !empty($post['slug'])): ?>
                        <a href="<?= BASE_URL ?>article/<?= htmlspecialchars($post['slug']) ?>" target="_blank"
                           class="btn" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;padding:5px 10px;font-size:11px;font-weight:700;" title="View live article">
                            <i data-feather="external-link" style="width:12px;"></i> View
                        </a>
                        <?php endif; ?>
                        <a href="post_edit.php?id=<?= $post['id'] ?>" class="btn" style="background:#f1f5f9;color:#475569;padding:5px 10px;font-size:11px;font-weight:700;">
                            <i data-feather="edit-2" style="width:12px;"></i> Edit
                        </a>
                        <a href="?delete=<?= $post['id'] ?>&<?= http_build_query(array_filter(['s'=>$search,'st'=>$status,'cat'=>$cat_id,'sort'=>$sort,'page'=>$page])) ?>"
                           class="btn btn-danger" style="padding:5px 10px;font-size:11px;font-weight:700;"
                           onclick="return confirm('Delete this post permanently?')">
                            <i data-feather="trash-2" style="width:12px;"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <a href="<?= paginate_url(['page'=>max(1,$page-1)]) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">
            <i data-feather="chevron-left" style="width:14px;"></i>
        </a>
        <?php
        $start = max(1, $page - 2);
        $end   = min($total_pages, $page + 2);
        if ($start > 1) echo '<span class="page-btn" style="pointer-events:none;">…</span>';
        for ($i = $start; $i <= $end; $i++):
        ?>
        <a href="<?= paginate_url(['page'=>$i]) ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor;
        if ($end < $total_pages) echo '<span class="page-btn" style="pointer-events:none;">…</span>';
        ?>
        <a href="<?= paginate_url(['page'=>min($total_pages,$page+1)]) ?>" class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">
            <i data-feather="chevron-right" style="width:14px;"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
