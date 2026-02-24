<?php
$page_title = "Dashboard";
include 'includes/header.php';

// ── Core Stats ───────────────────────────────────────────────
$total_posts      = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$published_posts  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$draft_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn();
$total_views      = $pdo->query("SELECT COALESCE(SUM(views),0) FROM posts")->fetchColumn();
$total_users      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$unread_msgs      = $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
$today_posts      = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// ── Top viewed posts ──────────────────────────────────────────
$top_posts = $pdo->query("
    SELECT p.id, p.title, p.views, p.status, p.published_at, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id = pc.post_id
    LEFT JOIN categories c ON pc.category_id = c.id
    WHERE p.status = 'published'
    GROUP BY p.id
    ORDER BY p.views DESC LIMIT 5
")->fetchAll();

// ── Recent Posts ──────────────────────────────────────────────
$recent_posts = $pdo->query("
    SELECT p.id, p.title, p.status, p.created_at, p.views, p.slug,
           GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') as cats,
           GROUP_CONCAT(c.color ORDER BY c.name SEPARATOR ',') as colors
    FROM posts p
    LEFT JOIN post_categories pc ON p.id = pc.post_id
    LEFT JOIN categories c ON pc.category_id = c.id
    GROUP BY p.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetchAll();

// ── Categories with post counts ───────────────────────────────
$cat_stats = $pdo->query("
    SELECT c.name, c.color, c.icon, COUNT(pc.post_id) as cnt
    FROM categories c
    LEFT JOIN post_categories pc ON c.id = pc.category_id
    LEFT JOIN posts p ON pc.post_id = p.id AND p.status = 'published'
    WHERE c.status = 'active'
    GROUP BY c.id ORDER BY cnt DESC LIMIT 6
")->fetchAll();
$max_cnt = max(array_column($cat_stats, 'cnt') ?: [1]);

// ── Recent Feedback ───────────────────────────────────────────
$recent_feedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT 4")->fetchAll();

?>

<!-- ═══════════════════════════ STATS ROW ═══════════════════════════ -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 28px;">

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Published</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($published_posts); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;"><?php echo $draft_posts; ?> drafts pending</p>
            </div>
            <div style="background: rgba(99,102,241,.1); color: var(--primary); width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="file-text" style="width: 24px;"></i>
            </div>
        </div>
        <div style="margin-top: 15px; height: 4px; background: #f1f5f9; border-radius: 4px;">
            <div style="height: 4px; background: var(--primary); border-radius: 4px; width: <?php echo $total_posts > 0 ? round(($published_posts/$total_posts)*100) : 0; ?>%;"></div>
        </div>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Total Reach</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_views); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Cumulative views</p>
            </div>
            <div style="background: rgba(16,185,129,.1); color: #10b981; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="trending-up" style="width: 24px;"></i>
            </div>
        </div>
        <div style="margin-top: 15px; padding: 6px 10px; background: #ecfdf5; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
            <i data-feather="eye" style="width: 12px; color: #10b981;"></i>
            <span style="font-size: 12px; font-weight: 600; color: #059669;"><?php echo $today_posts; ?> new today</span>
        </div>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Categories</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_categories); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Active sections</p>
            </div>
            <div style="background: rgba(245,158,11,.1); color: #f59e0b; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="layers" style="width: 24px;"></i>
            </div>
        </div>
        <a href="categories.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #f59e0b; text-decoration: none;">
            Manage <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Messages</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($unread_msgs); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Unread in inbox</p>
            </div>
            <div style="background: rgba(239,68,68,.1); color: #ef4444; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="inbox" style="width: 24px;"></i>
            </div>
        </div>
        <a href="feedback.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #ef4444; text-decoration: none;">
            View Inbox <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>

    <div class="stat-card">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <p style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;">Contributors</p>
                <div style="font-size: 30px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_users); ?></div>
                <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Journalists & Editors</p>
            </div>
            <div style="background: rgba(139,92,246,.1); color: #8b5cf6; width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">
                <i data-feather="users" style="width: 24px;"></i>
            </div>
        </div>
        <a href="users.php" style="margin-top: 14px; display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: #8b5cf6; text-decoration: none;">
            Manage Team <i data-feather="arrow-right" style="width: 12px;"></i>
        </a>
    </div>
</div>

<!-- ═══════════════════════════ MAIN GRID ═══════════════════════════ -->
<div style="display: grid; grid-template-columns: 1fr 320px; gap: 25px; align-items: start;">

    <!-- LEFT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 25px;">

        <!-- Top Performing Articles -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">🏆 Top Performing Articles</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 3px 0 0;">By total views</p>
                </div>
                <a href="posts.php" class="btn btn-primary" style="font-size: 12px; padding: 7px 15px;">All Posts</a>
            </div>
            <table class="content-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_posts)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">No published posts yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($top_posts as $i => $post):
                        $cats = explode(',', $post['cats'] ?? '');
                        $colors = explode(',', $post['colors'] ?? '');
                    ?>
                    <tr>
                        <td style="font-size: 13px; font-weight: 800; color: <?php echo $i === 0 ? '#f59e0b' : ($i === 1 ? '#94a3b8' : ($i === 2 ? '#92400e' : '#cbd5e1')); ?>;">
                            <?php echo $i + 1; ?>
                        </td>
                        <td style="max-width: 250px;">
                            <a href="<?php echo BASE_URL; ?>article/<?php echo $post['slug']; ?>" target="_blank" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($post['title']); ?></a>
                        </td>
                        <td>
                            <span style="background: <?php echo $colors[0] ?? '#6366f1'; ?>18; color: <?php echo $colors[0] ?? '#6366f1'; ?>; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($cats[0] ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; height: 6px; background: #f1f5f9; border-radius: 10px; max-width: 60px;">
                                    <div style="height: 6px; background: var(--primary); border-radius: 10px; width: <?php echo $top_posts[0]['views'] > 0 ? round(($post['views']/$top_posts[0]['views'])*100) : 0; ?>%;"></div>
                                </div>
                                <span style="font-weight: 700; font-size: 13px; color: #0f172a;"><?php echo number_format($post['views']); ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none;">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Recently Added -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; margin: 0;">🕒 Recently Added</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 3px 0 0;">Latest editorial activity</p>
                </div>
                <a href="post_add.php" class="btn" style="font-size: 12px; padding: 7px 15px; background: #ecfdf5; color: #059669; font-weight: 700;">+ New Post</a>
            </div>
            <div style="padding: 5px 0;">
            <?php foreach ($recent_posts as $post):
                $cols = explode(',', $post['colors'] ?? '#6366f1');
                $cats = explode(',', $post['cats'] ?? 'Uncategorized');
            ?>
            <div style="padding: 14px 25px; display: flex; gap: 14px; align-items: center; border-bottom: 1px solid #f8fafc;">
                <div style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $cols[0]; ?>; flex-shrink: 0;"></div>
                <div style="flex: 1; min-width: 0;">
                    <a href="post_edit.php?id=<?php echo $post['id']; ?>" style="font-size: 14px; font-weight: 600; color: #0f172a; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($post['title']); ?></a>
                    <span style="font-size: 11px; color: <?php echo $cols[0]; ?>; font-weight: 700;"><?php echo htmlspecialchars($cats[0]); ?></span>
                </div>
                <div style="text-align: right; flex-shrink: 0;">
                    <span class="badge badge-<?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;"><?php echo date('d M', strtotime($post['created_at'])); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div style="display: flex; flex-direction: column; gap: 25px;">

        <!-- Quick Actions -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px;">
            <h3 style="font-size: 15px; font-weight: 700; margin-bottom: 15px;">⚡ Quick Actions</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="post_add.php" class="btn btn-primary" style="justify-content: center;">
                    <i data-feather="plus" style="width: 16px;"></i> New Article
                </a>
                <a href="categories.php" class="btn" style="background: #fdf4ff; color: #9333ea; font-weight: 700; justify-content: center; border-color: #f3e8ff;">
                    <i data-feather="layers" style="width: 16px;"></i> Add Category
                </a>
                <a href="<?php echo BASE_URL; ?>" target="_blank" class="btn" style="background: #f0fdf4; color: #059669; font-weight: 700; justify-content: center; border-color: #dcfce7;">
                    <i data-feather="external-link" style="width: 16px;"></i> View Website
                </a>
                <a href="settings.php" class="btn" style="background: #f8fafc; color: #475569; font-weight: 700; justify-content: center;">
                    <i data-feather="settings" style="width: 16px;"></i> Site Settings
                </a>
            </div>
        </div>

        <!-- Category Distribution -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0;">📊 Category Pulse</h3>
                <a href="categories.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">Manage</a>
            </div>
            <?php foreach ($cat_stats as $cat): ?>
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i data-feather="<?php echo $cat['icon']; ?>" style="width: 14px; color: <?php echo $cat['color']; ?>;"></i>
                        <span style="font-size: 13px; font-weight: 600; color: #334155;"><?php echo htmlspecialchars($cat['name']); ?></span>
                    </div>
                    <span style="font-size: 12px; font-weight: 700; color: #64748b;"><?php echo $cat['cnt']; ?></span>
                </div>
                <div style="height: 6px; background: #f1f5f9; border-radius: 10px;">
                    <div style="height: 6px; background: <?php echo $cat['color']; ?>; border-radius: 10px; width: <?php echo $max_cnt > 0 ? round(($cat['cnt']/$max_cnt)*100) : 0; ?>%; transition: width 1s;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Messages -->
        <div style="background: white; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 18px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0;">💬 Recent Messages</h3>
                <a href="feedback.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">Inbox</a>
            </div>
            <?php if (empty($recent_feedback)): ?>
                <p style="padding: 20px; color: #94a3b8; font-size: 13px; text-align: center;">No messages yet.</p>
            <?php else: ?>
            <?php foreach ($recent_feedback as $msg): ?>
            <a href="feedback.php?view=<?php echo $msg['id']; ?>" style="display: flex; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #f8fafc; text-decoration: none; transition: background .15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; flex-shrink: 0;">
                    <?php echo strtoupper(substr($msg['name'], 0, 1)); ?>
                </div>
                <div style="min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($msg['name']); ?></span>
                        <?php if ($msg['status'] === 'new'): ?>
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars(substr($msg['message'], 0, 45)); ?>...</div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
