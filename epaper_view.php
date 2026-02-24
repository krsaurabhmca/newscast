<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
require_once 'includes/config.php';
require_once 'includes/functions.php';

$stmt = $pdo->prepare("SELECT * FROM epapers WHERE id = ?");
$stmt->execute([$id]);
$paper = $stmt->fetch();
if (!$paper) { redirect('digital-paper', 'E-Paper not found.', 'danger'); }

// Adjacent papers
$prev = $pdo->prepare("SELECT id, title FROM epapers WHERE paper_date < ? ORDER BY paper_date DESC LIMIT 1");
$prev->execute([$paper['paper_date']]); $prev = $prev->fetch();
$next = $pdo->prepare("SELECT id, title FROM epapers WHERE paper_date > ? ORDER BY paper_date ASC LIMIT 1");
$next->execute([$paper['paper_date']]); $next = $next->fetch();

$pdf_url = BASE_URL . "assets/epapers/" . rawurlencode($paper['file_path']);
$page_title = htmlspecialchars($paper['title']) . " – Digital Edition";
$meta_description = "Read " . htmlspecialchars($paper['title']) . " — " . format_date($paper['paper_date']) . " digital edition online.";
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | <?= SITE_NAME ?></title>
    <meta name="description" content="<?= $meta_description ?>">
    <meta name="robots" content="noindex">
    <?php if (get_setting('site_favicon')): ?>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/images/<?= get_setting('site_favicon') ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- Turn.js (jQuery dependency) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>

    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{
            --ink:#0f172a; --accent:#e63946; --bg:#1a1a2e;
            --surface:rgba(255,255,255,.06); --border:rgba(255,255,255,.12);
        }
        body{font-family:'Inter',sans-serif; background:var(--bg); color:#fff; min-height:100vh; overflow-x:hidden;}

        /* ── Top Bar ───────────────────────────────────── */
        .viewer-topbar{
            position:fixed;top:0;left:0;right:0;z-index:100;
            background:rgba(15,15,30,.92); backdrop-filter:blur(16px);
            border-bottom:1px solid var(--border);
            padding:10px 20px; display:flex; align-items:center; gap:12px;
        }
        .tb-logo{font-size:14px;font-weight:800;color:#fff;text-decoration:none;
            border-right:1px solid var(--border);padding-right:14px;margin-right:2px;white-space:nowrap;}
        .tb-title{flex:1;min-width:0;}
        .tb-title h1{font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .tb-title p{font-size:11px;color:#94a3b8;margin-top:1px;}
        .tb-actions{display:flex;align-items:center;gap:8px;}
        .tb-btn{background:var(--surface);border:1px solid var(--border);color:#cbd5e1;padding:7px 13px;
            border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;
            gap:5px;transition:.15s;white-space:nowrap;text-decoration:none;}
        .tb-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
        .tb-btn.accent{background:var(--accent);border-color:transparent;color:#fff;}
        .tb-btn svg{width:14px;height:14px;flex-shrink:0;}

        /* ── Loading Screen ────────────────────────────── */
        #loader{
            position:fixed;inset:0;z-index:200;background:var(--bg);
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;
        }
        .loader-book{width:80px;height:100px;position:relative;}
        .loader-page{
            position:absolute;width:100%;height:100%;background:linear-gradient(135deg,#fff 0%,#e2e8f0 100%);
            border-radius:2px 8px 8px 2px;
            transform-origin:left center;
            animation:flipLoader 1.4s ease-in-out infinite;
        }
        .loader-page:nth-child(1){animation-delay:0s;}
        .loader-page:nth-child(2){animation-delay:.2s; background:linear-gradient(135deg,#f1f5f9,#cbd5e1);}
        .loader-page:nth-child(3){animation-delay:.4s; background:linear-gradient(135deg,#e2e8f0,#94a3b8);}
        @keyframes flipLoader{
            0%{transform:perspective(400px) rotateY(0deg);}
            50%{transform:perspective(400px) rotateY(-90deg);}
            100%{transform:perspective(400px) rotateY(0deg);}
        }
        #loader h3{font-size:18px;font-weight:700;color:#fff;}
        .progress-ring{margin-top:10px;}
        #loader-msg{font-size:13px;color:#64748b;margin-top:-8px;}
        #load-progress{
            width:260px;height:4px;background:rgba(255,255,255,.1);border-radius:10px;overflow:hidden;
        }
        #load-bar{height:100%;width:0%;background:linear-gradient(90deg,var(--accent),#f472b6);border-radius:10px;transition:width .3s;}

        /* ── Stage (main viewer area) ──────────────────── */
        #stage{
            min-height:100vh;padding-top:64px;padding-bottom:80px;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:24px;
        }

        /* ── Flip Book Wrapper ─────────────────────────── */
        #book-wrapper{
            position:relative;
            display:flex;align-items:center;justify-content:center;
            filter:drop-shadow(0 40px 80px rgba(0,0,0,.7));
        }

        #flipbook{
            background:#fff;
            /* dimensions set by JS */
        }

        /* Turn.js page styles */
        #flipbook .page{
            background:#fff;overflow:hidden;
            display:flex;align-items:center;justify-content:center;
        }
        #flipbook .page canvas{display:block;max-width:100%;max-height:100%;}

        /* Hard cover pages */
        #flipbook .page.cover{
            background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:30px;text-align:center;color:#fff;
        }
        #flipbook .page.cover .cover-title{
            font-size:20px;font-weight:800;line-height:1.3;margin-bottom:10px;color:#fff;
        }
        #flipbook .page.cover .cover-date{
            font-size:12px;font-weight:600;color:#94a3b8;
            background:rgba(255,255,255,.1);padding:4px 12px;border-radius:20px;
        }
        #flipbook .page.cover .cover-logo{
            font-size:28px;font-weight:900;color:var(--accent);margin-bottom:20px;
            letter-spacing:-1px;
        }
        #flipbook .back-cover{
            background:linear-gradient(135deg,#0f172a,#1e293b);
            display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:14px;
        }

        /* Page curl shadow (Turn.js adds .gradient automatically) */
        #flipbook .even .gradient{
            background:linear-gradient(to right,rgba(0,0,0,.15) 0%,transparent 100%) !important;
        }
        #flipbook .odd .gradient{
            background:linear-gradient(to left,rgba(0,0,0,.15) 0%,transparent 100%) !important;
        }

        /* Navigation arrows beside book */
        .nav-arrow{
            position:absolute;top:50%;transform:translateY(-50%);
            width:48px;height:48px;border-radius:50%;
            background:rgba(255,255,255,.1);backdrop-filter:blur(8px);
            border:1px solid var(--border);
            display:flex;align-items:center;justify-content:center;
            cursor:pointer;transition:.2s;z-index:10;color:#fff;
        }
        .nav-arrow:hover{background:rgba(255,255,255,.2);transform:translateY(-50%) scale(1.08);}
        .nav-arrow.prev{left:-64px;}
        .nav-arrow.next{right:-64px;}
        .nav-arrow svg{width:22px;height:22px;}
        .nav-arrow.disabled{opacity:.25;pointer-events:none;}

        /* ── Bottom Control Bar ────────────────────────── */
        #ctrl-bar{
            position:fixed;bottom:0;left:0;right:0;z-index:100;
            background:rgba(15,15,30,.92);backdrop-filter:blur(16px);
            border-top:1px solid var(--border);
            padding:10px 20px;display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;
        }
        .ctrl-group{display:flex;align-items:center;gap:8px;}
        .ctrl-label{font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;}

        .page-input-wrap{display:flex;align-items:center;gap:6px;background:var(--surface);
            border:1px solid var(--border); border-radius:8px;padding:4px 10px;}
        #page-input{width:42px;background:transparent;border:none;color:#fff;font-size:13px;
            font-weight:700;text-align:center;outline:none;-moz-appearance:textfield;}
        #page-input::-webkit-outer-spin-button,
        #page-input::-webkit-inner-spin-button{-webkit-appearance:none;}
        .page-sep{color:#475569;font-size:12px;}
        #page-total{font-size:12px;color:#64748b;font-weight:600;}

        .zoom-btn,.ctrl-btn{
            background:var(--surface);border:1px solid var(--border);color:#cbd5e1;
            width:34px;height:34px;border-radius:8px;display:flex;align-items:center;
            justify-content:center;cursor:pointer;transition:.15s;flex-shrink:0;
        }
        .zoom-btn:hover,.ctrl-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
        .zoom-btn svg,.ctrl-btn svg{width:15px;height:15px;}
        #zoom-level{font-size:12px;color:#94a3b8;font-weight:600;min-width:36px;text-align:center;}

        /* ── Thumbnail Strip ───────────────────────────── */
        #thumb-strip{
            position:fixed;left:0;top:64px;bottom:0;width:0;overflow:hidden;
            background:rgba(10,10,20,.96);backdrop-filter:blur(16px);
            border-right:1px solid var(--border);
            transition:width .3s cubic-bezier(.4,0,.2,1);z-index:90;
        }
        #thumb-strip.open{width:180px;}
        #thumb-inner{width:180px;height:100%;overflow-y:auto;padding:14px 10px;display:flex;flex-direction:column;gap:10px;
            scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.1) transparent;}
        .thumb-item{cursor:pointer;border-radius:6px;overflow:hidden;border:2px solid transparent;
            transition:.15s;flex-shrink:0;}
        .thumb-item canvas{display:block;width:100%;height:auto;}
        .thumb-item.active{border-color:var(--accent);}
        .thumb-item:hover:not(.active){border-color:rgba(255,255,255,.3);}
        .thumb-label{font-size:10px;font-weight:700;color:#64748b;text-align:center;padding:3px 0;}

        /* ── Edition nav ───────────────────────────────── */
        .edition-nav{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:center;}
        .ed-btn{
            text-decoration:none;font-size:12px;font-weight:600;
            padding:7px 14px;border-radius:8px;border:1px solid var(--border);
            background:var(--surface);color:#94a3b8;display:flex;align-items:center;gap:5px;transition:.15s;
        }
        .ed-btn:hover{color:#fff;border-color:rgba(255,255,255,.25);}
        .ed-btn svg{width:13px;height:13px;}

        /* ── Page loading placeholder ──────────────────── */
        .page-placeholder{
            width:100%;height:100%;background:linear-gradient(135deg,#f8fafc,#e2e8f0);
            display:flex;align-items:center;justify-content:center;font-size:13px;
            color:#94a3b8;font-weight:600;
        }
        .page-num-badge{
            position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.35);
            color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;
        }

        /* ── Responsive ────────────────────────────────── */
        @media(max-width:768px){
            .nav-arrow{display:none;}
            .tb-actions .desktop-only{display:none;}
            #ctrl-bar{gap:10px;}
        }
    </style>
</head>
<body>

<!-- Loading Screen -->
<div id="loader">
    <div class="loader-book">
        <div class="loader-page"></div>
        <div class="loader-page"></div>
        <div class="loader-page"></div>
    </div>
    <h3>Loading Digital Edition…</h3>
    <div id="load-progress"><div id="load-bar"></div></div>
    <div id="loader-msg">Preparing pages 0 / ?</div>
</div>

<!-- Top Bar -->
<div class="viewer-topbar">
    <a href="<?= BASE_URL ?>" class="tb-logo"><?= SITE_NAME ?></a>
    <div class="tb-title">
        <h1><?= htmlspecialchars($paper['title']) ?></h1>
        <p><?= format_date($paper['paper_date']) ?> &nbsp;·&nbsp; Digital Edition</p>
    </div>
    <div class="tb-actions">
        <button class="tb-btn desktop-only" id="thumb-toggle" title="Toggle page thumbnails">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Pages
        </button>
        <button class="tb-btn desktop-only" id="fullscreen-btn" title="Toggle fullscreen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            Fullscreen
        </button>
        <a href="<?= BASE_URL ?>digital-paper" class="tb-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Archive
        </a>
    </div>
</div>

<!-- Thumbnail sidebar -->
<div id="thumb-strip"><div id="thumb-inner"></div></div>

<!-- Stage -->
<div id="stage">
    <!-- Edition nav -->
    <div class="edition-nav">
        <?php if ($prev): ?>
        <a href="<?= BASE_URL ?>digital-paper/view/<?= $prev['id'] ?>" class="ed-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            <?= htmlspecialchars(mb_strimwidth($prev['title'],0,28,'…')) ?>
        </a>
        <?php endif; ?>
        <span style="font-size:12px;color:#475569;">·</span>
        <?php if ($next): ?>
        <a href="<?= BASE_URL ?>digital-paper/view/<?= $next['id'] ?>" class="ed-btn">
            <?= htmlspecialchars(mb_strimwidth($next['title'],0,28,'…')) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
    </div>

    <!-- Book + arrows -->
    <div id="book-wrapper">
        <button class="nav-arrow prev disabled" id="btn-prev" title="Previous spread (←)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div id="flipbook"></div>

        <button class="nav-arrow next" id="btn-next" title="Next spread (→)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<!-- Bottom Control Bar -->
<div id="ctrl-bar">
    <div class="ctrl-group">
        <span class="ctrl-label">Page</span>
        <div class="page-input-wrap">
            <input type="number" id="page-input" value="1" min="1">
            <span class="page-sep">/</span>
            <span id="page-total">?</span>
        </div>
    </div>

    <div class="ctrl-group">
        <button class="zoom-btn" id="zoom-out" title="Zoom out">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
        </button>
        <span id="zoom-level">100%</span>
        <button class="zoom-btn" id="zoom-in" title="Zoom in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
        </button>
    </div>

    <div class="ctrl-group">
        <button class="ctrl-btn" id="btn-prev2" title="Previous (←)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <button class="ctrl-btn" id="btn-next2" title="Next (→)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const PDF_URL    = <?= json_encode($pdf_url) ?>;
const SITE_NAME  = <?= json_encode(SITE_NAME) ?>;
const PAPER_TITLE= <?= json_encode($paper['title']) ?>;
const PAPER_DATE = <?= json_encode(format_date($paper['paper_date'])) ?>;

let pdfDoc      = null;
let totalPages  = 0;
let zoom        = 1.0;
let thumbsBuilt = false;
let thumbOpen   = false;
let bookReady   = false;

// ── Compute book dimensions based on viewport ──────────
function bookDims() {
    const vw = window.innerWidth;
    const vh = window.innerHeight - 64 - 80;
    const maxH = Math.min(vh - 40, 820);
    const pageW = Math.round(Math.min((vw * .44), 520) * zoom);
    const pageH = Math.round(pageW * 1.414);
    if (pageH > maxH) {
        const scale = maxH / pageH;
        return { w: Math.round(pageW * scale) * 2, h: Math.round(pageH * scale) };
    }
    return { w: pageW * 2, h: pageH };
}

// ── Render a single PDF page into a canvas ─────────────
async function renderPage(pageNum, canvas, scale) {
    try {
        const page  = await pdfDoc.getPage(pageNum);
        const vp    = page.getViewport({ scale });
        canvas.width  = vp.width;
        canvas.height = vp.height;
        await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
    } catch(e) { console.warn('Render error p.' + pageNum, e); }
}

// ── Show error to user ─────────────────────────────────
function showError(msg) {
    document.getElementById('loader').innerHTML = `
        <div style="text-align:center; max-width:420px; padding:30px;">
            <svg style="width:56px;height:56px;color:#ef4444;margin-bottom:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <h3 style="color:#fff;font-size:18px;margin-bottom:10px;">Could not load PDF</h3>
            <p style="color:#94a3b8;font-size:14px;margin-bottom:20px;">${msg}</p>
            <a href="<?= BASE_URL ?>assets/epapers/<?= rawurlencode($paper['file_path']) ?>" 
               target="_blank"
               style="display:inline-flex;align-items:center;gap:8px;background:#e63946;color:#fff;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;">
                <svg style="width:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download PDF Instead
            </a>
            <a href="<?= BASE_URL ?>digital-paper" style="display:block;margin-top:14px;color:#64748b;font-size:13px;text-decoration:none;">← Back to Archive</a>
        </div>`;
}

// ── Build the flip book once PDF is loaded ─────────────
async function buildBook() {
    const dims  = bookDims();
    const pageW = dims.w / 2;
    const pageH = dims.h;
    const firstPage = await pdfDoc.getPage(1);
    const scale = pageW / firstPage.getViewport({ scale: 1 }).width;

    const $book = $('#flipbook');
    $book.empty().css({ width: dims.w + 'px', height: pageH + 'px' });

    // Front Cover
    $book.append(`
        <div class="page cover" style="width:${pageW}px;height:${pageH}px;">
            <div class="cover-logo">${SITE_NAME}</div>
            <div class="cover-title">${PAPER_TITLE}</div>
            <div class="cover-date" style="margin-top:10px;">${PAPER_DATE}</div>
        </div>`);

    // PDF pages
    for (let i = 1; i <= totalPages; i++) {
        const $page = $(`<div class="page" style="width:${pageW}px;height:${pageH}px;overflow:hidden;"></div>`);
        const $inner = $('<div style="position:relative;width:100%;height:100%;background:#fff;"></div>');
        const canvas  = document.createElement('canvas');
        $inner.append(canvas);
        $inner.append(`<div class="page-num-badge">p.${i}</div>`);
        $page.append($inner);
        $book.append($page);

        const pct = Math.round((i / totalPages) * 100);
        document.getElementById('load-bar').style.width = pct + '%';
        document.getElementById('loader-msg').textContent = `Preparing pages ${i} / ${totalPages}`;
    }

    // Back Cover (turn.js needs even page count)
    $book.append(`<div class="page back-cover" style="width:${pageW}px;height:${pageH}px;"><span>© ${SITE_NAME} · All Rights Reserved</span></div>`);

    // Ensure minimum 4 pages for turn.js
    while ($book.children('.page').length < 4) {
        $book.append(`<div class="page" style="width:${pageW}px;height:${pageH}px;background:#f8fafc;"></div>`);
    }

    // Wait for DOM to settle before init
    await new Promise(r => setTimeout(r, 50));

    // Init Turn.js
    try {
        $book.turn({
            width:       dims.w,
            height:      pageH,
            autoCenter:  true,
            gradients:   true,
            acceleration:true,
            display:     'double',
            when: {
                turning: function(e, page, view) {
                    updateUI(page);
                    if (view && view.length) ensureRendered(view, scale);
                },
                turned: function(e, page, view) {
                    updateUI(page);
                    if (thumbOpen) buildThumbs();
                }
            }
        });
        bookReady = true;
    } catch(e) {
        console.error('Turn.js init error:', e);
    }

    // Render first two visible pages
    ensureRendered([1, 2], scale);
    updateUI(1);
    document.getElementById('page-total').textContent = $book.turn('pages');

    // Hide loader
    setTimeout(() => {
        const loader = document.getElementById('loader');
        loader.style.transition = 'opacity .4s';
        loader.style.opacity = '0';
        setTimeout(() => loader.style.display = 'none', 400);
    }, 300);
}

// ── Lazy-render pages as they become visible ───────────
const rendered = {};
async function ensureRendered(view, scaleHint) {
    if (!view || !view.length || !pdfDoc) return;
    const dims  = bookDims();
    const pageW = dims.w / 2;
    for (const turnPage of view) {
        const pdfPage = turnPage - 1; // account for cover page
        if (pdfPage < 1 || pdfPage > totalPages) continue;
        if (rendered[pdfPage]) continue;
        rendered[pdfPage] = true;
        // .page is 0-indexed: cover=0, page1=1, page2=2 …
        const $pageEl = $('#flipbook .page').eq(turnPage - 1);
        const canvas  = $pageEl.find('canvas')[0];
        if (!canvas) continue;
        try {
            const pg   = await pdfDoc.getPage(pdfPage);
            const vp   = pg.getViewport({ scale: 1 });
            const sc   = scaleHint || (pageW / vp.width);
            await renderPage(pdfPage, canvas, sc);
        } catch(e) { console.warn('ensureRendered err p.' + pdfPage, e); }
    }
}

// ── Update page counter & arrow states ────────────────
function updateUI(page) {
    const input = document.getElementById('page-input');
    if (input) input.value = page;
    if (!bookReady) return;
    try {
        const total = $('#flipbook').turn('pages');
        $('#btn-prev, #btn-prev2').toggleClass('disabled', page <= 1);
        $('#btn-next, #btn-next2').toggleClass('disabled', page >= total);
    } catch(e) {}
    document.querySelectorAll('.thumb-item').forEach((el, i) => {
        el.classList.toggle('active', i + 1 === page);
    });
}

// ── Build thumbnail strip ──────────────────────────────
async function buildThumbs() {
    if (thumbsBuilt) return;
    thumbsBuilt = true;
    const strip = document.getElementById('thumb-inner');
    strip.innerHTML = '';
    const THUMB_W = 140;

    for (let i = 1; i <= totalPages; i++) {
        const wrap = document.createElement('div');
        wrap.className = 'thumb-item';
        wrap.dataset.page = i + 1;
        const c = document.createElement('canvas');
        const p = await pdfDoc.getPage(i);
        const vp = p.getViewport({scale:1});
        await renderPage(i, c, THUMB_W / vp.width);
        const lbl = document.createElement('div');
        lbl.className = 'thumb-label';
        lbl.textContent = 'p.' + i;
        wrap.appendChild(c);
        wrap.appendChild(lbl);
        wrap.addEventListener('click', () => {
            $('#flipbook').turn('page', parseInt(wrap.dataset.page));
        });
        strip.appendChild(wrap);
        if (i % 5 === 0) await new Promise(r => setTimeout(r, 0));
    }
}

// ── Load PDF ───────────────────────────────────────────
$(document).ready(async function() {
    try {
        const loadTask = pdfjsLib.getDocument({ url: PDF_URL, withCredentials: false });
        loadTask.onProgress = function(data) {
            if (data.total) {
                const pct = (data.loaded / data.total * 50);
                document.getElementById('load-bar').style.width = pct + '%';
            }
        };
        pdfDoc     = await loadTask.promise;
        totalPages = pdfDoc.numPages;
        document.getElementById('loader-msg').textContent = `${totalPages} pages found. Rendering…`;
        await buildBook();
    } catch(err) {
        console.error('PDF load error:', err);
        showError(err.message || 'The PDF file could not be loaded. It may be missing or corrupted.');
    }
});

// Navigation
$('#btn-prev,  #btn-prev2').on('click', () => { if (!$('#btn-prev').hasClass('disabled') && bookReady) $('#flipbook').turn('previous'); });
$('#btn-next,  #btn-next2').on('click', () => { if (!$('#btn-next').hasClass('disabled') && bookReady) $('#flipbook').turn('next'); });

// Page jump
document.getElementById('page-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && bookReady) {
        const p = parseInt(this.value);
        if (p >= 1) $('#flipbook').turn('page', p);
    }
});

// Keyboard nav
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || !bookReady) return;
    if (e.key === 'ArrowLeft'  || e.key === 'PageUp')   { e.preventDefault(); $('#flipbook').turn('previous'); }
    if (e.key === 'ArrowRight' || e.key === 'PageDown')  { e.preventDefault(); $('#flipbook').turn('next'); }
});

// Zoom
let zoomLevels = [0.5, 0.65, 0.8, 1.0, 1.25, 1.5, 1.75, 2.0];
let zoomIdx = 3;
function applyZoom() {
    zoom = zoomLevels[zoomIdx];
    document.getElementById('zoom-level').textContent = Math.round(zoom * 100) + '%';
    // Rebuild book with fresh size
    const dims = bookDims();
    $('#flipbook').turn('size', dims.w, dims.h);
    Object.keys(rendered).forEach(k => delete rendered[k]);
    const cur = $('#flipbook').turn('page');
    ensureRendered([cur, cur + 1]);
}
document.getElementById('zoom-in').addEventListener('click', () => { if (zoomIdx < zoomLevels.length - 1) { zoomIdx++; applyZoom(); }});
document.getElementById('zoom-out').addEventListener('click', () => { if (zoomIdx > 0) { zoomIdx--; applyZoom(); }});

// Fullscreen
document.getElementById('fullscreen-btn').addEventListener('click', () => {
    if (!document.fullscreenElement) document.documentElement.requestFullscreen();
    else document.exitFullscreen();
});
document.addEventListener('fullscreenchange', () => {
    const btn = document.getElementById('fullscreen-btn');
    if (document.fullscreenElement) btn.innerHTML = btn.innerHTML.replace('Fullscreen','Exit Full');
    else btn.innerHTML = btn.innerHTML.replace('Exit Full','Fullscreen');
});

// Thumbnail toggle
document.getElementById('thumb-toggle').addEventListener('click', function() {
    thumbOpen = !thumbOpen;
    document.getElementById('thumb-strip').classList.toggle('open', thumbOpen);
    buildThumbs();
});

// Touch swipe support
let touchStartX = 0;
document.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, {passive:true});
document.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(dx) > 60) {
        if (dx < 0) $('#flipbook').turn('next');
        else         $('#flipbook').turn('previous');
    }
}, {passive:true});

// Resize reflow
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        const dims = bookDims();
        $('#flipbook').turn('size', dims.w, dims.h);
    }, 200);
});
</script>
</body>
</html>
