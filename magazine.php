<?php
include 'includes/public_header.php';

// Year filter
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get magazines for selected year
$stmt = $pdo->prepare("SELECT * FROM magazines WHERE status='published' AND YEAR(issue_month) = ? ORDER BY issue_month DESC");
$stmt->execute([$year]);
$magazines = $stmt->fetchAll();

// Get available years
$yrs = $pdo->query("SELECT DISTINCT YEAR(issue_month) AS y FROM magazines WHERE status='published' ORDER BY y DESC");
$available_years = $yrs->fetchAll(PDO::FETCH_COLUMN);

// Latest issue for hero
$latest = $pdo->query("SELECT * FROM magazines WHERE status='published' ORDER BY issue_month DESC LIMIT 1")->fetch();

$page_title = "Monthly Magazine";
$meta_description = "Browse monthly digital magazine editions from " . SITE_NAME . ".";
?>

<main class="content-container">
    <style>
        .mag-hero{
            background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
            border-radius:20px; padding:40px; margin-top:20px; margin-bottom:30px;
            display:flex; gap:32px; align-items:center; overflow:hidden; position:relative;
        }
        .mag-hero::before{content:'';position:absolute;right:-60px;top:-60px;width:300px;height:300px;
            background:radial-gradient(circle,rgba(99,102,241,.25) 0%,transparent 70%);pointer-events:none;}
        .mag-hero-cover{flex-shrink:0;width:140px;height:196px;object-fit:cover;border-radius:12px;
            box-shadow:0 20px 50px rgba(0,0,0,.5);border:3px solid rgba(255,255,255,.1);}
        .mag-hero-ph{flex-shrink:0;width:140px;height:196px;border-radius:12px;background:rgba(255,255,255,.07);
            border:2px dashed rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;}
        .mag-hero-info{flex:1;min-width:0;}
        .mag-hero-tag{font-size:11px;font-weight:700;color:#818cf8;text-transform:uppercase;letter-spacing:.1em;margin-bottom:8px;}
        .mag-hero-title{font-size:26px;font-weight:800;color:#fff;line-height:1.25;margin-bottom:10px;}
        .mag-hero-meta{font-size:13px;color:#94a3b8;margin-bottom:20px;}
        .mag-hero-actions{display:flex;gap:10px;flex-wrap:wrap;}
        .mag-btn-read{display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:10px;
            background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:700;font-size:13px;
            text-decoration:none;transition:.2s;box-shadow:0 4px 15px rgba(99,102,241,.4);}
        .mag-btn-read:hover{transform:translateY(-1px);box-shadow:0 8px 25px rgba(99,102,241,.5);}
        .mag-btn-dl{display:inline-flex;align-items:center;gap:7px;padding:11px 18px;border-radius:10px;
            background:rgba(255,255,255,.08);color:#cbd5e1;font-weight:600;font-size:13px;
            text-decoration:none;border:1px solid rgba(255,255,255,.15);transition:.2s;}
        .mag-btn-dl:hover{background:rgba(255,255,255,.14);color:#fff;}

        .year-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;}
        .year-tab{padding:8px 18px;border-radius:25px;font-size:13px;font-weight:700;
            text-decoration:none;border:1px solid #e2e8f0;color:#64748b;background:white;transition:.15s;}
        .year-tab.active{background:#0f172a;color:#fff;border-color:#0f172a;}
        .year-tab:hover:not(.active){background:#f8fafc;}

        .mag-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:24px;}
        .mag-card{background:white;border-radius:16px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.06);
            transition:.25s;border:1px solid #f1f5f9;}
        .mag-card:hover{transform:translateY(-5px);box-shadow:0 12px 30px rgba(0,0,0,.12);}
        .mag-card-img{position:relative;height:240px;background:#f8fafc;overflow:hidden;}
        .mag-card-img img{width:100%;height:100%;object-fit:cover;transition:.3s;}
        .mag-card:hover .mag-card-img img{transform:scale(1.04);}
        .mag-card-ph{width:100%;height:240px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;}
        .mag-month-ribbon{position:absolute;top:10px;left:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);
            color:#fff;font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.05em;}
        .mag-card-body{padding:14px;}
        .mag-card-title{font-size:13px;font-weight:700;color:#1e293b;line-height:1.4;
            display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .mag-card-meta{font-size:11px;color:#94a3b8;margin-top:4px;}
        .mag-card-actions{display:flex;gap:6px;margin-top:12px;}
        .ma-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:4px;
            padding:7px 8px;border-radius:8px;font-size:11px;font-weight:700;text-decoration:none;transition:.15s;}
        .ma-btn-read{background:#eef2ff;color:#4338ca;}
        .ma-btn-read:hover{background:#e0e7ff;}
        .ma-btn-dl{background:#f0fdf4;color:#15803d;}
        .ma-btn-dl:hover{background:#dcfce7;}

        .empty-state{text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:2px dashed #e2e8f0;}
        .section-title{font-size:20px;font-weight:800;color:#1e293b;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
        .section-title span{font-size:14px;font-weight:600;color:#94a3b8;}
    </style>

    <!-- Hero: Latest Issue -->
    <?php if ($latest): ?>
    <div class="mag-hero">
        <?php if ($latest['cover_image']): ?>
        <img src="assets/magazines/<?= htmlspecialchars($latest['cover_image']) ?>"
             class="mag-hero-cover" onerror="this.style.display='none'">
        <?php else: ?>
        <div class="mag-hero-ph">
            <i data-feather="book-open" style="width:40px;color:rgba(255,255,255,.25);"></i>
        </div>
        <?php endif; ?>
        <div class="mag-hero-info">
            <div class="mag-hero-tag">✦ Latest Edition</div>
            <h1 class="mag-hero-title"><?= htmlspecialchars($latest['title']) ?></h1>
            <div class="mag-hero-meta">
                📅 <?= date('F Y', strtotime($latest['issue_month'])) ?>
                <?php if ($latest['description']): ?>
                &nbsp;·&nbsp; <?= htmlspecialchars(mb_strimwidth($latest['description'],0,80,'…')) ?>
                <?php endif; ?>
            </div>
            <div class="mag-hero-actions">
                <a href="<?= BASE_URL ?>magazine/view/<?= $latest['id'] ?>" class="mag-btn-read">
                    <i data-feather="book-open" style="width:15px;"></i> Read Now
                </a>
                <a href="assets/magazines/<?= htmlspecialchars($latest['file_path']) ?>" download class="mag-btn-dl">
                    <i data-feather="download" style="width:15px;"></i> Download PDF
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Year Tabs -->
    <?php if (!empty($available_years)): ?>
    <div class="year-tabs">
        <?php foreach ($available_years as $y): ?>
        <a href="?year=<?= $y ?>" class="year-tab <?= $y == $year ? 'active':'' ?>"><?= $y ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Grid -->
    <div class="section-title">
        <?= $year ?> Issues <span><?= count($magazines) ?> edition<?= count($magazines)!=1?'s':'' ?></span>
    </div>

    <?php if (empty($magazines)): ?>
    <div class="empty-state">
        <i data-feather="book" style="width:50px;color:#e2e8f0;display:block;margin:0 auto 12px;"></i>
        <h3 style="color:#64748b;">No magazines for <?= $year ?></h3>
        <p style="color:#94a3b8;font-size:14px;margin-top:8px;">Select another year above.</p>
    </div>
    <?php else: ?>
    <div class="mag-grid">
        <?php foreach ($magazines as $mg): ?>
        <div class="mag-card">
            <div class="mag-card-img">
                <?php if ($mg['cover_image']): ?>
                <img src="assets/magazines/<?= htmlspecialchars($mg['cover_image']) ?>"
                     alt="<?= htmlspecialchars($mg['title']) ?>" onerror="this.src='assets/images/default-post.jpg'">
                <?php else: ?>
                <div class="mag-card-ph">
                    <i data-feather="book" style="width:36px;color:#cbd5e1;"></i>
                    <span style="font-size:11px;color:#94a3b8;">No Cover</span>
                </div>
                <?php endif; ?>
                <div class="mag-month-ribbon"><?= date('M Y', strtotime($mg['issue_month'])) ?></div>
            </div>
            <div class="mag-card-body">
                <div class="mag-card-title"><?= htmlspecialchars($mg['title']) ?></div>
                <?php if ($mg['description']): ?>
                <div class="mag-card-meta"><?= htmlspecialchars(mb_strimwidth($mg['description'],0,55,'…')) ?></div>
                <?php endif; ?>
                <div class="mag-card-actions">
                    <a href="<?= BASE_URL ?>magazine/view/<?= $mg['id'] ?>" class="ma-btn ma-btn-read">
                        <i data-feather="book-open" style="width:12px;"></i> Read
                    </a>
                    <a href="assets/magazines/<?= htmlspecialchars($mg['file_path']) ?>" download class="ma-btn ma-btn-dl">
                        <i data-feather="download" style="width:12px;"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/public_footer.php'; ?>
