<?php
$page_title = "Manage Posts";
include 'includes/header.php';

// ── Delete ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    if (!is_admin()) {
        redirect('admin/posts.php', 'Access denied. Only Admins can delete posts.', 'danger');
    }
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot delete data.', 'danger');
        exit;
    }
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists("../assets/images/posts/" . $img)) {
        unlink("../assets/images/posts/" . $img);
    }
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    redirect('admin/posts.php', 'Post deleted successfully!');
}

// ── Bulk Delete ─────────────────────────────────────────────
if (isset($_POST['bulk_delete']) && !empty($_POST['post_ids'])) {
    if (!is_admin()) {
        redirect('admin/posts.php', 'Access denied. Only Admins can delete posts.', 'danger');
    }
    if (is_demo_account()) {
        redirect('admin/' . basename($_SERVER['PHP_SELF']), 'Action restricted: Demo accounts cannot delete data.', 'danger');
        exit;
    }
    $ids = array_map('intval', $_POST['post_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($images as $img) {
        if ($img && file_exists("../assets/images/posts/" . $img)) {
            unlink("../assets/images/posts/" . $img);
        }
    }

    $pdo->prepare("DELETE FROM posts WHERE id IN ($placeholders)")->execute($ids);
    redirect('admin/posts.php', count($ids) . ' posts deleted successfully!');
}

// ── Search / Filter / Sort params ───────────────────────
$search = trim($_GET['s'] ?? '');
$status = trim($_GET['st'] ?? '');
$cat_id = (int) ($_GET['cat'] ?? 0);
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 15;

$sort_map = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'views' => 'p.views DESC',
    'title' => 'p.title ASC',
];
$order_by = $sort_map[$sort] ?? 'p.created_at DESC';

// ── Build dynamic WHERE ─────────────────────────────────
$wheres = [];
$params = [];

if ($search !== '') {
    $wheres[] = 'p.title LIKE ?';
    $params[] = "%$search%";
}
if ($status !== '') {
    $wheres[] = 'p.status = ?';
    $params[] = $status;
}
if ($cat_id > 0) {
    $wheres[] = 'EXISTS (SELECT 1 FROM post_categories pc2 WHERE pc2.post_id = p.id AND pc2.category_id = ?)';
    $params[] = $cat_id;
}

$where_sql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

// ── Count total ─────────────────────────────────────────
$count_sql = "SELECT COUNT(DISTINCT p.id) FROM posts p $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total = (int) $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

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

// Build URL helper
function paginate_url(array $overrides = []): string
{
    $base = array_merge($_GET, $overrides);
    return '?' . http_build_query(array_filter($base, fn($v) => $v !== '' && $v !== '0' && $v !== 0));
}
?>

<style>
    /* Premium Table Redesign */
    .content-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .content-header h2 {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 5px 0;
        letter-spacing: -0.5px;
    }

    .content-header p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
        font-weight: 500;
    }

    .modern-toolbar {
        background: white;
        padding: 15px 20px;
        border-radius: 16px 16px 0 0;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }

    .toolbar-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        flex: 1;
    }

    .search-wrapper {
        position: relative;
        min-width: 260px;
        max-width: 100%;
    }

    .search-wrapper input {
        width: 100%;
        padding: 10px 15px 10px 40px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        transition: 0.2s;
        background: #f8fafc;
    }

    .search-wrapper input:focus {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .search-wrapper i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        width: 16px;
        pointer-events: none;
    }

    .custom-select {
        appearance: none;
        padding: 10px 35px 10px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        background: #f8fafc url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%2394a3b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>') no-repeat right 12px center;
        cursor: pointer;
        transition: 0.2s;
    }

    .custom-select:hover {
        background-color: white;
        border-color: #cbd5e1;
    }

    .custom-select:focus {
        outline: none;
        border-color: var(--primary);
        background-color: white;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .modern-table-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        overflow: visible;
        border: 1px solid #f1f5f9;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: transparent;
        padding: 16px 20px;
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #f1f5f9;
        text-align: left;
    }

    .modern-table td {
        padding: 12px 20px;
        border-bottom: 1px solid #f8fafc;
        font-size: 13px;
        vertical-align: middle;
        transition: background 0.2s;
    }

    .modern-table tbody tr:hover td {
        background: #f8fafc;
    }

    .modern-table tbody tr:last-child td {
        border-bottom: none;
    }

    .post-title-cell {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .post-thumb {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        flex-shrink: 0;
        background: #f1f5f9;
    }

    .post-thumb-fallback {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        flex-shrink: 0;
    }

    .post-title-link {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        text-decoration: none;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color 0.2s;
        line-height: 1.4;
    }

    .post-title-link:hover {
        color: var(--primary);
    }

    .pill {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .pill-published {
        background: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .pill-draft {
        background: #fffbeb;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .pill-category {
        background: #f1f5f9;
        color: #475569;
        font-weight: 600;
        border: 1px solid #e2e8f0;
    }

    .pill-featured {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 2px 8px;
        font-size: 9px;
        margin-top: 4px;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: transparent;
        transition: 0.2s;
        text-decoration: none;
        border: 1px solid transparent;
    }

    .action-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #e2e8f0;
    }

    .action-btn.delete:hover {
        background: #fef2f2;
        color: #ef4444;
        border-color: #fecaca;
    }

    .action-btn.view:hover {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .pagination-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: white;
        border-top: 1px solid #f1f5f9;
        border-radius: 0 0 16px 16px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .page-numbers {
        display: flex;
        gap: 5px;
    }

    .page-btn {
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        color: #475569;
        transition: 0.2s;
        border: 1px solid transparent;
    }

    .page-btn:hover:not(.active) {
        background: #f1f5f9;
    }

    .page-btn.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);
    }

    .page-btn.disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    .sort-link {
        color: inherit;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color 0.2s;
    }

    .sort-link:hover {
        color: var(--primary);
    }

    .sort-link.active { color: var(--primary); font-weight: 800; }

    /* Mobile View Toggle Switcher Styles */
    .mobile-view-toggle {
        display: none !important;
    }
    .view-toggle-btn {
        color: #64748b;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 6px 10px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .view-toggle-btn:hover {
        color: #0f172a;
    }
    .view-toggle-btn.active {
        background: #fff !important;
        color: var(--primary) !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
        .mobile-view-toggle {
            display: inline-flex !important;
        }

        /* If list view is active on mobile, override the default block/card styles from admin_responsive.css */
        .table-view-wrapper.view-list {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin: 0 -10px;
            width: calc(100% + 20px);
        }
        
        .table-view-wrapper.view-list .modern-table {
            display: table !important;
            width: 100% !important;
            min-width: 700px !important;
        }
        
        .table-view-wrapper.view-list .modern-table thead {
            display: table-header-group !important;
        }
        
        .table-view-wrapper.view-list .modern-table tbody {
            display: table-row-group !important;
        }
        
        .table-view-wrapper.view-list .modern-table tr {
            display: table-row !important;
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
            margin-bottom: 0 !important;
        }
        
        .table-view-wrapper.view-list .modern-table td {
            display: table-cell !important;
            width: auto !important;
            padding: 12px 10px !important;
            text-align: left !important;
            border-bottom: 1px solid #f1f5f9 !important;
            min-height: auto !important;
            justify-content: unset !important;
            align-items: unset !important;
        }
        
        .table-view-wrapper.view-list .modern-table th {
            display: table-cell !important;
            background: #f8fafc !important;
            border-bottom: 2px solid #f1f5f9 !important;
            padding: 12px 10px !important;
            font-size: 11px !important;
            color: #94a3b8 !important;
        }
        
        /* Disable pseudo-labels */
        .table-view-wrapper.view-list .modern-table td::before {
            display: none !important;
        }
        
        /* Reset checkbox and title positioning */
        .table-view-wrapper.view-list .modern-table td:nth-child(1) {
            position: static !important;
            width: 40px !important;
            padding-right: 0 !important;
            display: table-cell !important;
        }
        
        .table-view-wrapper.view-list .modern-table td:nth-child(2) {
            padding: 12px 10px !important;
            border-bottom: 1px solid #f1f5f9 !important;
            margin-bottom: 0 !important;
            display: table-cell !important;
        }
        
        .table-view-wrapper.view-list .post-title-link {
            padding-right: 0 !important;
        }
    }
</style>



<div class="content-header">
    <div>
        <h2>Manage Articles</h2>
        <p>You have published <strong><?= number_format($total) ?></strong> articles in total</p>
    </div>
    <div style="display: flex; gap: 10px;">

        <a href="post_add.php" class="btn btn-primary">
            <i data-feather="edit-3" style="width: 16px;"></i> Write Article
        </a>
    </div>
</div>

<div class="modern-table-container">
    <!-- Toolbar -->
    <form method="GET" action="">
        <div class="modern-toolbar">
            <div class="toolbar-filters">
                <div class="search-wrapper">
                    <!-- <i data-feather="search"></i> -->
                    <input type="text" name="s" placeholder="Search articles by title..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <select name="st" class="custom-select" onchange="this.form.submit()">
                    <option value="">Status: All</option>
                    <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Drafts</option>
                </select>

                <select name="cat" class="custom-select" onchange="this.form.submit()">
                    <option value="">Category: All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat_id == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <button type="submit" style="display:none;"></button> <!-- For enter key submission -->
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <!-- Mobile View Toggle Switcher -->
                <div class="mobile-view-toggle" style="display: none; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                    <button type="button" id="btn-view-card" class="view-toggle-btn active" title="Card View">
                        <i data-feather="grid" style="width: 14px; height: 14px;"></i>
                    </button>
                    <button type="button" id="btn-view-list" class="view-toggle-btn" title="List View">
                        <i data-feather="list" style="width: 14px; height: 14px;"></i>
                    </button>
                </div>

                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Sort
                    by:</span>
                <select name="sort" class="custom-select"
                    style="background-color: transparent; border: none; font-weight: 700; color: var(--primary);"
                    onchange="this.form.submit()">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="views" <?= $sort == 'views' ? 'selected' : '' ?>>Most Views</option>
                    <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>Title (A-Z)</option>
                </select>

                <?php if ($search || $status || $cat_id): ?>
                    <a href="posts.php" class="btn"
                        style="background: #f1f5f9; color: #64748b; padding: 8px 12px; font-size: 12px; border-radius: 8px;">
                        <i data-feather="x" style="width: 14px;"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <form id="bulkForm" method="POST" action="">
        <!-- Bulk Actions Toolbar -->
        <div id="bulkActions"
            style="display: none; padding: 12px 20px; background: #fff1f2; border-bottom: 1px solid #fee2e2; align-items: center; justify-content: space-between;">
            <span style="font-size: 13px; font-weight: 600; color: #991b1b;"><span id="selectedCount">0</span> items
                selected</span>
            <button type="submit" name="bulk_delete" class="btn btn-sm"
                style="background: #ef4444; color: white; padding: 6px 12px; font-size: 12px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px;"
                onclick="return confirm('Are you sure you want to delete these posts permanently?');">
                <i data-feather="trash-2" style="width: 14px;"></i> Bulk Delete
            </button>
        </div>

        <div class="table-view-wrapper" style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th width="40px" style="padding-right: 0;">
                            <?php if (is_admin()): ?>
                                <input type="checkbox" id="selectAll" style="cursor: pointer;">
                            <?php endif; ?>
                        </th>
                        <th width="40%">Article Details</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th><a href="<?= paginate_url(['sort' => 'views', 'page' => 1]) ?>"
                                class="sort-link <?= $sort == 'views' ? 'active' : '' ?>">Views <i data-feather="bar-chart-2"
                                    style="width:12px;"></i></a></th>
                        <th>Date Published</th>
                        <th style="text-align: right;">Manage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i data-feather="inbox"
                                        style="width: 48px; height: 48px; color: #cbd5e1; margin: 0 auto 15px;"></i>
                                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0;">No
                                        articles found</h3>
                                    <p style="color: #64748b; margin: 0 0 20px 0;">
                                        <?= $search || $status || $cat_id ? 'Try adjusting your filters to find what you are looking for.' : 'Get started by publishing your first piece of news.' ?>
                                    </p>
                                    <?php if ($search || $status || $cat_id): ?>
                                        <a href="posts.php" class="btn" style="background: #f1f5f9; color: #475569;">Clear All
                                            Filters</a>
                                    <?php else: ?>
                                        <a href="post_add.php" class="btn btn-primary"><i data-feather="edit-3"
                                                style="width:16px;"></i> Write Article</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td style="padding-right: 0;" data-label="">
                                    <?php if (is_admin()): ?>
                                        <input type="checkbox" name="post_ids[]" value="<?= $post['id'] ?>" class="post-checkbox"
                                            style="cursor: pointer;">
                                    <?php endif; ?>
                                </td>
                                <td data-label="">
                                    <div class="post-title-cell">
                                        <?php if ($post['featured_image']): ?>
                                            <img src="<?= htmlspecialchars(get_post_thumbnail($post['featured_image'])) ?>"
                                                class="post-thumb">
                                        <?php else: ?>
                                            <div class="post-thumb-fallback">
                                                <i data-feather="image" style="width: 20px;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="post_edit.php?id=<?= $post['id'] ?>" class="post-title-link">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </a>
                                            <?php if ($post['is_featured']): ?>
                                                <span class="pill pill-featured"><i data-feather="star"
                                                        style="width:10px; fill:currentColor;"></i> Featured</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Category">
                                    <?php if ($post['cat_names']): ?>
                                        <span class="pill pill-category"><?= htmlspecialchars($post['cat_names']) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="pill pill-<?= $post['status'] ?>">
                                        <?= $post['status'] === 'published' ? '<i data-feather="check-circle" style="width:12px;"></i> ' : '<i data-feather="edit" style="width:12px;"></i> ' ?>        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td data-label="Views">
                                    <span
                                        style="font-weight: 700; color: #334155; font-size: 13px;"><?= number_format($post['views']) ?></span>
                                </td>
                                <td data-label="Date Published">
                                    <span
                                        style="color: #64748b; font-size: 13px;"><?= format_date($post['created_at']) ?></span>
                                </td>
                                <td style="text-align: right;" data-label="Manage">
                                    <div style="display:flex; gap:4px; justify-content: flex-end;">
                                        <?php if ($post['status'] === 'published' && !empty($post['slug'])): ?>
                                            <a href="<?= BASE_URL ?>article/<?= htmlspecialchars($post['slug']) ?>" target="_blank"
                                                class="action-btn view" title="View Live">
                                                <i data-feather="external-link" style="width:15px;"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (can_edit_post($post)): ?>
                                            <a href="post_edit.php?id=<?= $post['id'] ?>" class="action-btn" title="Edit Article">
                                                <i data-feather="edit-2" style="width:15px;"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (is_admin()): ?>
                                            <a href="?delete=<?= $post['id'] ?>&<?= http_build_query(array_filter(['s' => $search, 'st' => $status, 'cat' => $cat_id, 'sort' => $sort, 'page' => $page])) ?>"
                                                class="action-btn delete" title="Delete Article"
                                                onclick="return confirm('Delete this article permanently? This action cannot be undone.')">
                                                <i data-feather="trash-2" style="width:15px;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-modern">
                <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                    Showing <span
                        style="color: #0f172a;"><?= number_format(min($offset + 1, $total)) ?>-<?= number_format(min($offset + $per_page, $total)) ?></span>
                    of <span style="color: #0f172a;"><?= number_format($total) ?></span>
                </div>

                <div class="page-numbers">
                    <a href="<?= paginate_url(['page' => max(1, $page - 1)]) ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i data-feather="chevron-left" style="width:16px;"></i>
                    </a>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    if ($start > 1)
                        echo '<span class="page-btn disabled">…</span>';
                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="<?= paginate_url(['page' => $i]) ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    if ($end < $total_pages)
                        echo '<span class="page-btn disabled">…</span>';
                    ?>
                    <a href="<?= paginate_url(['page' => min($total_pages, $page + 1)]) ?>"
                        class="page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <i data-feather="chevron-right" style="width:16px;"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.post-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        function updateBulkActions() {
            const count = document.querySelectorAll('.post-checkbox:checked').length;
            if (selectedCount) selectedCount.textContent = count;
            if (bulkActions) bulkActions.style.display = count > 0 ? 'flex' : 'none';
            if (selectAll) {
                selectAll.checked = count === checkboxes.length && checkboxes.length > 0;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateBulkActions();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkActions);
        });

        // ── View Mode Toggle (Mobile) ──
        const btnViewCard = document.getElementById('btn-view-card');
        const btnViewList = document.getElementById('btn-view-list');
        const tableWrapper = document.querySelector('.table-view-wrapper');

        if (btnViewCard && btnViewList && tableWrapper) {
            // Load saved preference or default to card
            const savedView = localStorage.getItem('posts_mobile_view') || 'card';
            setViewMode(savedView);

            btnViewCard.addEventListener('click', function() {
                setViewMode('card');
            });
            btnViewList.addEventListener('click', function() {
                setViewMode('list');
            });
        }

        function setViewMode(mode) {
            localStorage.setItem('posts_mobile_view', mode);
            if (mode === 'list') {
                tableWrapper.classList.add('view-list');
                tableWrapper.classList.remove('view-card');
                btnViewList.classList.add('active');
                btnViewCard.classList.remove('active');
            } else {
                tableWrapper.classList.add('view-card');
                tableWrapper.classList.remove('view-list');
                btnViewCard.classList.add('active');
                btnViewList.classList.remove('active');
            }
            if (typeof feather !== 'undefined') feather.replace();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
