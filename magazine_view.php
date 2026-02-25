<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
require_once 'includes/config.php';
require_once 'includes/functions.php';

$stmt = $pdo->prepare("SELECT * FROM magazines WHERE id = ? AND status = 'published'");
$stmt->execute([$id]);
$mag = $stmt->fetch();
if (!$mag) { redirect('magazine', 'Magazine not found.', 'danger'); }

// Adjacent issues
$prev = $pdo->prepare("SELECT id, title, issue_month FROM magazines WHERE status='published' AND issue_month < ? ORDER BY issue_month DESC LIMIT 1");
$prev->execute([$mag['issue_month']]); $prev = $prev->fetch();
$next = $pdo->prepare("SELECT id, title, issue_month FROM magazines WHERE status='published' AND issue_month > ? ORDER BY issue_month ASC LIMIT 1");
$next->execute([$mag['issue_month']]); $next = $next->fetch();

// Track download (view count)
$pdo->prepare("UPDATE magazines SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);

$pdf_url    = BASE_URL . "assets/magazines/" . rawurlencode($mag['file_path']);
$page_title = htmlspecialchars($mag['title']) . " — " . date('F Y', strtotime($mag['issue_month']));
$meta_description = "Read " . htmlspecialchars($mag['title']) . " — " . date('F Y', strtotime($mag['issue_month'])) . " digital edition online.";
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $page_title ?> | <?= SITE_NAME ?></title>
    <meta name="description" content="<?= $meta_description ?>">
    <meta name="robots" content="noindex">
    <?php if (get_setting('site_favicon')): ?>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/images/<?= get_setting('site_favicon') ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- Turn.js (jQuery dependency) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>

    <style>
        *{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
        :root{
            --accent: #6366f1; /* Indigo for Magazines */
            --accent-glow: rgba(99, 102, 241, 0.4);
            --bg: #0d0d12;
            --surface: rgba(255, 255, 255, 0.05);
            --surface-hover: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --topbar-h: 64px;
            --ctrlbar-h: 70px;
        }
        
        body{
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Modern Top Bar ─────────────────────────── */
        .v-header {
            height: var(--topbar-h);
            background: rgba(13, 13, 18, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
            position: relative;
        }
        .v-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #fff;
            padding-right: 20px;
            border-right: 1px solid var(--border);
            margin-right: 15px;
        }
        .v-brand-logo {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            box-shadow: 0 0 15px var(--accent-glow);
        }
        .v-brand-name { font-weight: 800; font-size: 15px; letter-spacing: -0.02em; }
        
        .v-meta { flex: 1; min-width: 0; }
        .v-meta-title { 
            font-size: 14px; 
            font-weight: 700; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .v-meta-date { font-size: 11px; color: var(--text-muted); font-weight: 500; }

        .v-header-actions { display: flex; align-items: center; gap: 8px; }
        .btn-icon {
            width: 38px;
            height: 38px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }
        .btn-icon:hover { background: var(--surface-hover); transform: translateY(-1px); border-color: rgba(255,255,255,0.2); }
        .btn-icon svg { width: 18px; height: 18px; }
        
        .btn-text {
            height: 38px;
            padding: 0 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-main);
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-text:hover { background: var(--surface-hover); }
        .btn-text.primary { background: var(--accent); border-color: transparent; color: #fff; }

        /* ── Loading Page ────────────────────────────── */
        #v-loader {
            position: fixed;
            inset: 0;
            background: var(--bg);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 30px;
        }
        .loader-viz {
            position: relative;
            width: 120px;
            height: 120px;
        }
        .loader-circle {
            position: absolute;
            inset: 0;
            border: 3px solid var(--surface);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .book-anim {
            position: absolute;
            inset: 30px;
            display: flex;
            gap: 4px;
            align-items: flex-end;
            justify-content: center;
        }
        .book-bar {
            width: 6px;
            background: var(--accent);
            border-radius: 3px;
            animation: barGrow 1.2s ease-in-out infinite;
        }
        @keyframes barGrow {
            0%, 100% { height: 20%; }
            50% { height: 80%; }
        }
        .book-bar:nth-child(2) { animation-delay: 0.2s; }
        .book-bar:nth-child(3) { animation-delay: 0.4s; }

        .loader-text { text-align: center; }
        .loader-text h2 { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .loader-text p { font-size: 14px; color: var(--text-muted); }
        
        .progress-box {
            width: 280px;
            height: 6px;
            background: var(--surface);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        #v-progress-inner {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--accent), #818cf8);
            transition: width 0.3s ease;
        }

        /* ── Stage & Viewer ─────────────────────────── */
        .v-main {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: radial-gradient(circle at center, #1a1a24 0%, #0d0d12 100%);
        }

        #book-viewport {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            touch-action: none;
        }

        #flipbook {
            position: relative;
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.8);
            background: #fff;
            transition: opacity 0.4s ease;
        }

        .page { background: #fff; position: relative; }
        .page canvas { display: block; width: 100%; height: 100%; }

        /* Cover & Backcover Special Styles */
        .page.hard { 
            background: linear-gradient(135deg, #2e2e3a 0%, #0d0d12 100%);
            color: #fff;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }
        .cover-content h3 { font-size: 24px; font-weight: 800; margin-bottom: 12px; line-height: 1.2; }
        .cover-content span { 
            font-size: 13px; font-weight: 600; 
            color: #fff; background: var(--accent);
            padding: 4px 14px; border-radius: 20px;
        }

        /* Nav Arrows */
        .v-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 50%;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 500;
        }
        .v-arrow:hover { background: rgba(255,255,255,0.08); transform: translateY(-50%) scale(1.1); border-color: rgba(255,255,255,0.2); }
        .v-arrow.disabled { opacity: 0; pointer-events: none; }
        .v-arrow.prev { left: 40px; }
        .v-arrow.next { right: 40px; }
        .v-arrow svg { width: 24px; height: 24px; stroke-width: 2.5; }

        /* ── Control Bar ────────────────────────────── */
        .v-controls {
            height: var(--ctrlbar-h);
            background: rgba(13, 13, 18, 0.85);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            gap: 20px;
            z-index: 1000;
        }

        .pager-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface);
            padding: 6px 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .pager-input {
            width: 40px;
            background: transparent;
            border: none;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            text-align: center;
            outline: none;
        }
        .pager-sep { color: var(--text-muted); font-weight: 500; font-size: 13px; }
        .pager-total { color: var(--text-muted); font-weight: 600; font-size: 13px; }

        .zoom-wrap { display: flex; align-items: center; gap: 4px; }
        .zoom-val { font-size: 13px; font-weight: 700; color: var(--text-main); min-width: 48px; text-align: center; }

        /* ── Thumbnail Panel ────────────────────────── */
        #v-thumbs {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 220px;
            background: rgba(13, 13, 18, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            z-index: 1500;
            transform: translateX(-100%);
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        #v-thumbs.active { transform: translateX(0); }
        
        .thumbs-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .thumbs-header h3 { font-size: 15px; font-weight: 700; }
        
        .thumbs-grid {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            scrollbar-width: thin;
        }
        .thumb-item {
            cursor: pointer;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.2s;
            position: relative;
        }
        .thumb-item:hover { transform: scale(1.02); }
        .thumb-item.active { border-color: var(--accent); box-shadow: 0 0 15px var(--accent-glow); }
        .thumb-item canvas { display: block; width: 100%; height: auto; }
        .thumb-label {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* ── Mobile & Desktop specific ──────────────── */
        @media (max-width: 768px) {
            :root { --topbar-h: 58px; --ctrlbar-h: 64px; }
            .v-arrow { display: none; }
            .desktop-hide { display: none; }
            .v-brand-name { display: none; }
            .v-brand { border-right: none; margin-right: 0; padding-right: 0; }
            .v-meta-title { font-size: 13px; }
            .v-controls { gap: 10px; padding: 0 10px; }
            .pager-wrap { padding: 4px 10px; }
            .btn-text.dt-label { width: 38px; padding: 0; text-indent: -9999px; position: relative; }
            .btn-text.dt-label svg { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); text-indent: 0; }
        }

        /* Fullscreen utilities */
        :fullscreen body { background: #000; }
        :fullscreen .v-header, :fullscreen .v-controls { background: rgba(0,0,0,0.7); }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div id="v-loader">
        <div class="loader-viz">
            <div class="loader-circle"></div>
            <div class="book-anim">
                <div class="book-bar"></div>
                <div class="book-bar"></div>
                <div class="book-bar"></div>
            </div>
        </div>
        <div class="loader-text">
            <h2 id="loader-title">Opening Magazine Edition</h2>
            <p id="loader-status">Fetching PDF document...</p>
            <div class="progress-box">
                <div id="v-progress-inner"></div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="v-header">
        <a href="<?= BASE_URL ?>magazine" class="v-brand">
            <div class="v-brand-logo"><?= substr(SITE_NAME, 0, 1) ?></div>
            <span class="v-brand-name"><?= SITE_NAME ?></span>
        </a>
        <div class="v-meta">
            <h1 class="v-meta-title"><?= htmlspecialchars($mag['title']) ?></h1>
            <div class="v-meta-date"><?= date('F Y', strtotime($mag['issue_month'])) ?></div>
        </div>
        <div class="v-header-actions">
            <button class="btn-text desktop-hide" id="toggle-thumbs">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span>Pages</span>
            </button>
            <button class="btn-icon desktop-hide" id="toggle-fullscreen" title="Toggle Fullscreen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            </button>
            <a href="<?= BASE_URL ?>magazine" class="btn-text primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                <span>Back</span>
            </a>
        </div>
    </header>

    <!-- Main Viewer Stage -->
    <main class="v-main">
        <button class="v-arrow prev disabled" id="prev-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        
        <div id="book-viewport">
            <div id="flipbook"></div>
        </div>

        <button class="v-arrow next" id="next-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="9 18 15 12 9 6"/></svg>
        </button>

        <!-- Thumbnail Panel -->
        <aside id="v-thumbs">
            <div class="thumbs-header">
                <h3>Page Overview</h3>
                <button class="btn-icon" id="close-thumbs" style="width:30px;height:30px;border-radius:6px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="thumbs-grid" id="thumbs-container"></div>
        </aside>
    </main>

    <!-- Control Bar -->
    <footer class="v-controls">
        <div class="v-ctrl-group">
            <div class="pager-wrap">
                <input type="number" class="pager-input" id="page-input" value="1" min="1">
                <span class="pager-sep">/</span>
                <span class="pager-total" id="total-pages">?</span>
            </div>
        </div>

        <div class="v-ctrl-group zoom-wrap desktop-hide">
            <button class="btn-icon" id="zoom-out" style="width:32px;height:32px;border-radius:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            <span class="zoom-val" id="zoom-label">100%</span>
            <button class="btn-icon" id="zoom-in" style="width:32px;height:32px;border-radius:8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
        </div>

        <div class="v-ctrl-group" style="display:flex;gap:6px;">
            <button class="btn-icon" id="prev-page-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="btn-icon" id="next-page-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
        </div>
    </footer>

    <script>
    // Configuration & PDF.js Worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    const CONFIG = {
        pdfUrl: <?= json_encode($pdf_url) ?>,
        siteName: <?= json_encode(SITE_NAME) ?>,
        title: <?= json_encode($mag['title']) ?>,
        date: <?= json_encode(date('F Y', strtotime($mag['issue_month']))) ?>,
        isMobile: window.innerWidth <= 768
    };

    let state = {
        pdf: null,
        totalPages: 0,
        zoom: 1.0,
        bookReady: false,
        renderedPages: new Set()
    };

    // ── Book Sizing Logic ──────────────────────────────
    function getBookSize() {
        const w = window.innerWidth;
        const h = window.innerHeight - 64 - 70; // Topbar + Ctrlbar
        
        let bookW, bookH;
        const padding = CONFIG.isMobile ? 20 : 60;
        const targetH = h - padding;
        const targetW = w - padding;

        if (CONFIG.isMobile) {
            // Single page on mobile
            bookW = Math.min(targetW, 550) * state.zoom;
            bookH = bookW * 1.414;
            if (bookH > targetH) {
                const s = targetH / bookH;
                bookH = targetH;
                bookW = bookH / 1.414;
            }
        } else {
            // Double page on desktop
            const pageW = Math.min(targetW / 2, 550) * state.zoom;
            const pageH = pageW * 1.414;
            if (pageH > targetH) {
                const s = targetH / pageH;
                bookH = targetH;
                bookW = (bookH / 1.414) * 2;
            } else {
                bookW = pageW * 2;
                bookH = pageH;
            }
        }

        return { width: Math.round(bookW), height: Math.round(bookH) };
    }

    // ── PDF Rendering ──────────────────────────────────
    async function renderPageToCanvas(pageNum, canvas, scale) {
        try {
            const page = await state.pdf.getPage(pageNum);
            const viewport = page.getViewport({ scale });
            
            // Set canvas size for high fidelity (devicePixelRatio)
            const dpr = window.devicePixelRatio || 1;
            canvas.width = viewport.width * dpr;
            canvas.height = viewport.height * dpr;
            canvas.style.width = '100%';
            canvas.style.height = '100%';

            const ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);

            await page.render({
                canvasContext: ctx,
                viewport: viewport
            }).promise;
        } catch (e) {
            console.error(`Page ${pageNum} render error:`, e);
        }
    }

    // ── Build Flipbook Structure ───────────────────────
    async function initBook() {
        const size = getBookSize();
        const pageW = CONFIG.isMobile ? size.width : size.width / 2;
        const pageH = size.height;
        
        const $book = $('#flipbook');
        $book.empty().css({ width: size.width, height: size.height });

        // 1. Cover
        $book.append(`
            <div class="page hard" style="width:${pageW}px;height:${pageH}px">
                <div class="cover-content">
                    <div style="font-weight:900;color:var(--accent);font-size:32px;margin-bottom:20px;">${CONFIG.siteName}</div>
                    <h3>${CONFIG.title}</h3>
                    <span>${CONFIG.date}</span>
                </div>
            </div>
        `);

        // 2. Pages
        for (let i = 1; i <= state.totalPages; i++) {
            $book.append(`
                <div class="page" data-pdf-page="${i}" style="width:${pageW}px;height:${pageH}px">
                    <div class="page-loader" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;font-weight:600;">p.${i}</div>
                    <canvas></canvas>
                </div>
            `);
            const p = Math.round((i / state.totalPages) * 100);
            updateLoading(`Processing pages...`, p);
        }

        // 3. Back cover (ensure even pages for turn.js)
        $book.append(`<div class="page hard" style="width:${pageW}px;height:${pageH}px">© ${CONFIG.siteName}</div>`);
        if ($book.children().length % 2 !== 0) {
            $book.append(`<div class="page" style="width:${pageW}px;height:${pageH}px"></div>`);
        }

        // Initialize Turn.js
        $book.turn({
            width: size.width,
            height: size.height,
            display: CONFIG.isMobile ? 'single' : 'double',
            acceleration: true,
            gradients: true,
            elevation: 50,
            when: {
                turning: (e, page, view) => {
                    updatePager(page);
                    lazyLoad(view);
                },
                turned: (e, page) => {
                    updatePager(page);
                    highlightThumb(page);
                }
            }
        });

        state.bookReady = true;
        updatePager(1);
        lazyLoad([1, 2]);
        hideLoader();
    }

    async function lazyLoad(view) {
        if (!state.pdf || !view) return;
        const size = getBookSize();
        const pageW = CONFIG.isMobile ? size.width : size.width / 2;

        for (const turnPage of view) {
            const $page = $('#flipbook .page').eq(turnPage - 1);
            const pdfPage = parseInt($page.attr('data-pdf-page'));
            
            if (pdfPage && !state.renderedPages.has(pdfPage)) {
                state.renderedPages.add(pdfPage);
                const canvas = $page.find('canvas')[0];
                if (!canvas) continue;
                
                try {
                    const page = await state.pdf.getPage(pdfPage);
                    const vp = page.getViewport({ scale: 1 });
                    const scale = (pageW / vp.width) * 1.5; // Render slightly higher for sharpness
                    await renderPageToCanvas(pdfPage, canvas, scale);
                    $page.find('.page-loader').fadeOut();
                } catch(e) {}
            }
        }
    }

    // ── Thumbnails ────────────────────────────────────
    async function buildThumbs() {
        const container = document.getElementById('thumbs-container');
        if (container.children.length > 0) return;

        for (let i = 1; i <= state.totalPages; i++) {
            const item = document.createElement('div');
            item.className = 'thumb-item';
            item.dataset.page = i + 1; // +1 because of cover
            
            const canvas = document.createElement('canvas');
            const page = await state.pdf.getPage(i);
            const vp = page.getViewport({ scale: 0.2 });
            await renderPageToCanvas(i, canvas, 180 / vp.width);
            
            item.appendChild(canvas);
            item.innerHTML += `<div class="thumb-label">p.${i}</div>`;
            item.onclick = () => {
                $('#flipbook').turn('page', parseInt(item.dataset.page));
                if (CONFIG.isMobile) toggleThumbs(false);
            };
            container.appendChild(item);
        }
    }

    // ── Interaction UI ────────────────────────────────
    function updatePager(page) {
        const input = document.getElementById('page-input');
        if(input) input.value = page;
        const total = document.getElementById('total-pages');
        if(total) total.textContent = $('#flipbook').turn('pages');
        
        $('#prev-btn, #prev-page-btn').toggleClass('disabled', page === 1);
        $('#next-btn, #next-page-btn').toggleClass('disabled', page === $('#flipbook').turn('pages'));
    }

    function highlightThumb(page) {
        $('.thumb-item').removeClass('active');
        $(`.thumb-item[data-page="${page}"]`).addClass('active');
    }

    function updateLoading(msg, pct) {
        const status = document.getElementById('loader-status');
        if(status) status.textContent = msg;
        const progress = document.getElementById('v-progress-inner');
        if(progress) progress.style.width = pct + '%';
    }

    function hideLoader() {
        $('#v-loader').fadeOut(500);
    }

    function toggleThumbs(show) {
        if (show === undefined) show = !$('#v-thumbs').hasClass('active');
        $('#v-thumbs').toggleClass('active', show);
        if (show) buildThumbs();
    }

    // ── Main Controller ──────────────────────────────
    $(document).ready(async () => {
        try {
            updateLoading('Fetching document...', 10);
            const loadingTask = pdfjsLib.getDocument(CONFIG.pdfUrl);
            
            loadingTask.onProgress = (progress) => {
                if(progress.total > 0) {
                    const p = Math.round((progress.loaded / progress.total) * 50);
                    updateLoading('Downloading PDF...', p);
                }
            };

            state.pdf = await loadingTask.promise;
            state.totalPages = state.pdf.numPages;
            
            updateLoading('Initializing reader...', 60);
            await initBook();

        } catch (e) {
            console.error(e);
            const title = document.getElementById('loader-title');
            if(title) title.textContent = 'Oops! Failed to load';
            const status = document.getElementById('loader-status');
            if(status) status.innerHTML = `
                Could not open this digital edition.<br>
                <a href="${CONFIG.pdfUrl}" class="btn-text primary" style="margin-top:15px;display:inline-flex;">Download PDF Instead</a>
            `;
            const progress = document.getElementById('v-progress-inner');
            if(progress) progress.style.background = '#ef4444';
        }
    });

    // ── Events ────────────────────────────────────────
    $('#prev-btn, #prev-page-btn').on('click', () => $('#flipbook').turn('previous'));
    $('#next-btn, #next-page-btn').on('click', () => $('#flipbook').turn('next'));

    $('#toggle-thumbs, #close-thumbs').on('click', () => toggleThumbs());
    
    $('#page-input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            const val = parseInt($(this).val());
            if (val > 0 && val <= $('#flipbook').turn('pages')) $('#flipbook').turn('page', val);
        }
    });

    // Zoom Logic
    const zoomLevels = [0.8, 1.0, 1.3, 1.6, 2.0];
    let zoomIdx = 1;
    function applyZoom() {
        state.zoom = zoomLevels[zoomIdx];
        const label = document.getElementById('zoom-label');
        if(label) label.textContent = Math.round(state.zoom * 100) + '%';
        const size = getBookSize();
        $('#flipbook').turn('size', size.width, size.height);
        
        // Clear rendered cache to refresh high-quality versions if zoomed in
        state.renderedPages.clear();
        const currentView = $('#flipbook').turn('view');
        lazyLoad(currentView);
    }

    $('#zoom-in').on('click', () => { if (zoomIdx < zoomLevels.length - 1) { zoomIdx++; applyZoom(); } });
    $('#zoom-out').on('click', () => { if (zoomIdx > 0) { zoomIdx--; applyZoom(); } });

    // Keyboard & Fullscreen
    $(window).on('keydown', e => {
        if (e.key === 'ArrowLeft') $('#flipbook').turn('previous');
        if (e.key === 'ArrowRight') $('#flipbook').turn('next');
        if (e.key === 'f') $('#toggle-fullscreen').click();
    });

    $('#toggle-fullscreen').on('click', () => {
        if (!document.fullscreenElement) document.documentElement.requestFullscreen();
        else document.exitFullscreen();
    });

    // Responsive Handlers
    let resizeTimer;
    $(window).on('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const wasMobile = CONFIG.isMobile;
            CONFIG.isMobile = window.innerWidth <= 768;
            
            if (state.bookReady) {
                if (wasMobile !== CONFIG.isMobile) {
                    location.reload(); 
                } else {
                    const size = getBookSize();
                    $('#flipbook').turn('size', size.width, size.height);
                }
            }
        }, 300);
    });

    // Swipe support
    let touchX = 0;
    $(document).on('touchstart', e => touchX = e.originalEvent.touches[0].clientX);
    $(document).on('touchend', e => {
        const dx = e.originalEvent.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 50) {
            if (dx < 0) $('#flipbook').turn('next');
            else $('#flipbook').turn('previous');
        }
    });
    </script>
</body>
</html>
