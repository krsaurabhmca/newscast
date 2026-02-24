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

// Track download
$pdo->prepare("UPDATE magazines SET downloads = downloads + 1 WHERE id = ?")->execute([$id]);

$pdf_url    = BASE_URL . "assets/magazines/" . rawurlencode($mag['file_path']);
$page_title = htmlspecialchars($mag['title']) . " — " . date('F Y', strtotime($mag['issue_month']));
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | <?= SITE_NAME ?></title>
    <meta name="description" content="Read <?= htmlspecialchars($mag['title']) ?> — <?= date('F Y', strtotime($mag['issue_month'])) ?> edition online.">
    <meta name="robots" content="noindex">
    <?php if (get_setting('site_favicon')): ?>
    <link rel="icon" href="<?= BASE_URL ?>assets/images/<?= get_setting('site_favicon') ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{--ink:#0f172a;--accent:#6366f1;--bg:#0d0d1a;--surface:rgba(255,255,255,.06);--border:rgba(255,255,255,.1);}
        body{font-family:'Inter',sans-serif;background:var(--bg);color:#fff;min-height:100vh;overflow-x:hidden;}

        /* ── Top Bar ─── */
        .vtb{position:fixed;top:0;left:0;right:0;z-index:100;height:58px;
            background:rgba(13,13,26,.94);backdrop-filter:blur(18px);
            border-bottom:1px solid var(--border);
            display:flex;align-items:center;padding:0 18px;gap:12px;}
        .vtb-logo{font-size:13px;font-weight:800;color:#fff;text-decoration:none;
            padding-right:14px;border-right:1px solid var(--border);white-space:nowrap;
            display:flex;align-items:center;gap:6px;}
        .vtb-logo .mag-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);display:inline-block;}
        .vtb-meta{flex:1;min-width:0;}
        .vtb-meta h1{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .vtb-meta p{font-size:11px;color:#64748b;margin-top:1px;}
        .vtb-actions{display:flex;align-items:center;gap:7px;}
        .vtb-btn{background:var(--surface);border:1px solid var(--border);color:#cbd5e1;
            padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;
            display:flex;align-items:center;gap:5px;transition:.15s;text-decoration:none;white-space:nowrap;}
        .vtb-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
        .vtb-btn.accent{background:linear-gradient(135deg,#6366f1,#8b5cf6);border-color:transparent;color:#fff;}
        .vtb-btn svg{width:13px;height:13px;flex-shrink:0;}

        /* ── Loader ─── */
        #vloader{position:fixed;inset:0;z-index:200;background:var(--bg);
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;}
        .mag-spinner{width:70px;height:90px;position:relative;}
        .mag-page{position:absolute;inset:0;border-radius:2px 10px 10px 2px;
            transform-origin:left center;animation:magFlip 1.6s ease-in-out infinite;}
        .mag-page:nth-child(1){background:linear-gradient(135deg,#6366f1,#8b5cf6);animation-delay:0s;}
        .mag-page:nth-child(2){background:linear-gradient(135deg,#4f46e5,#7c3aed);animation-delay:.25s;}
        .mag-page:nth-child(3){background:linear-gradient(135deg,#3730a3,#5b21b6);animation-delay:.5s;}
        @keyframes magFlip{0%{transform:perspective(350px)rotateY(0)}50%{transform:perspective(350px)rotateY(-100deg)}100%{transform:perspective(350px)rotateY(0)}}
        #vloader h3{font-size:17px;font-weight:700;}
        #vload-bar-wrap{width:240px;height:3px;background:rgba(255,255,255,.08);border-radius:10px;overflow:hidden;}
        #vload-bar{height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#a78bfa);transition:width .3s;}
        #vloader-msg{font-size:12px;color:#475569;}

        /* ── Stage ─── */
        #vstage{min-height:100vh;padding:76px 0 72px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;}

        /* ── Issue nav ─── */
        .issue-nav{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:center;}
        .iss-btn{text-decoration:none;font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px;
            border:1px solid var(--border);background:var(--surface);color:#94a3b8;
            display:flex;align-items:center;gap:4px;transition:.15s;}
        .iss-btn:hover{color:#fff;border-color:rgba(255,255,255,.25);}
        .iss-btn svg{width:12px;}

        /* ── Book Wrapper ─── */
        #vbook-wrapper{position:relative;display:flex;align-items:center;justify-content:center;
            filter:drop-shadow(0 40px 80px rgba(0,0,0,.8));}
        #flipbook{background:#fff;}
        #flipbook .page{background:#fff;overflow:hidden;display:flex;align-items:center;justify-content:center;}
        #flipbook .page canvas{display:block;max-width:100%;max-height:100%;}
        #flipbook .cover-page{
            background:linear-gradient(160deg,#1e1b4b 0%,#312e81 50%,#3730a3 100%);
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:32px 28px;text-align:center;color:#fff;position:relative;overflow:hidden;
        }
        #flipbook .cover-page::before{content:'';position:absolute;top:-40px;right:-40px;
            width:200px;height:200px;border-radius:50%;background:rgba(99,102,241,.2);pointer-events:none;}
        #flipbook .cover-page::after{content:'';position:absolute;bottom:-40px;left:-40px;
            width:150px;height:150px;border-radius:50%;background:rgba(139,92,246,.15);pointer-events:none;}
        .cv-site{font-size:12px;font-weight:800;color:#a5b4fc;letter-spacing:.15em;text-transform:uppercase;margin-bottom:16px;}
        .cv-title{font-size:19px;font-weight:800;line-height:1.3;color:#fff;margin-bottom:10px;}
        .cv-month{display:inline-block;background:rgba(255,255,255,.12);color:#c7d2fe;
            font-size:11px;font-weight:700;padding:4px 14px;border-radius:20px;letter-spacing:.05em;}
        #flipbook .back-cover{background:linear-gradient(160deg,#0d0d1a,#1e1b4b);
            display:flex;align-items:center;justify-content:center;color:#475569;font-size:13px;font-weight:600;}
        .page-num-badge{position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.3);
            color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;}

        /* ── Nav Arrows ─── */
        .nav-arrow{position:absolute;top:50%;transform:translateY(-50%);width:46px;height:46px;border-radius:50%;
            background:rgba(255,255,255,.08);backdrop-filter:blur(8px);border:1px solid var(--border);
            display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s;color:#fff;z-index:10;}
        .nav-arrow:hover{background:rgba(255,255,255,.18);transform:translateY(-50%) scale(1.08);}
        .nav-arrow.prev{left:-62px;} .nav-arrow.next{right:-62px;}
        .nav-arrow svg{width:20px;} .nav-arrow.disabled{opacity:.2;pointer-events:none;}

        /* ── Bottom Bar ─── */
        #vctrl{position:fixed;bottom:0;left:0;right:0;z-index:100;height:54px;
            background:rgba(13,13,26,.94);backdrop-filter:blur(18px);border-top:1px solid var(--border);
            display:flex;align-items:center;justify-content:center;gap:14px;padding:0 16px;flex-wrap:wrap;}
        .vctrl-grp{display:flex;align-items:center;gap:7px;}
        .vctrl-lbl{font-size:10px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.06em;}
        .vc-btn{background:var(--surface);border:1px solid var(--border);color:#94a3b8;
            width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;}
        .vc-btn:hover{background:rgba(255,255,255,.12);color:#fff;}
        .vc-btn svg{width:14px;}
        .pg-wrap{display:flex;align-items:center;gap:5px;background:var(--surface);
            border:1px solid var(--border);border-radius:8px;padding:4px 9px;}
        #vpg-in{width:38px;background:transparent;border:none;color:#fff;font-size:13px;font-weight:700;
            text-align:center;outline:none;-moz-appearance:textfield;}
        #vpg-in::-webkit-outer-spin-button,#vpg-in::-webkit-inner-spin-button{-webkit-appearance:none;}
        .pg-sep,.pg-tot{font-size:11px;color:#475569;font-weight:600;}
        #vzoom-lbl{font-size:12px;color:#64748b;font-weight:600;min-width:34px;text-align:center;}

        /* ── Thumbnail Panel ─── */
        #vthumb{position:fixed;left:0;top:58px;bottom:0;width:0;overflow:hidden;
            background:rgba(8,8,18,.97);backdrop-filter:blur(18px);border-right:1px solid var(--border);
            transition:width .3s cubic-bezier(.4,0,.2,1);z-index:90;}
        #vthumb.open{width:175px;}
        #vthumb-inner{width:175px;height:100%;overflow-y:auto;padding:12px 8px;
            display:flex;flex-direction:column;gap:9px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.08) transparent;}
        .vti{cursor:pointer;border-radius:7px;overflow:hidden;border:2px solid transparent;transition:.15s;flex-shrink:0;}
        .vti canvas{display:block;width:100%;height:auto;}
        .vti-lbl{font-size:10px;font-weight:700;color:#475569;text-align:center;padding:2px 0;}
        .vti.active{border-color:var(--accent);}
        .vti:hover:not(.active){border-color:rgba(255,255,255,.25);}

        @media(max-width:700px){.nav-arrow{display:none;}.vtb-actions .dk{display:none;}}
    </style>
</head>
<body>

<!-- Loader -->
<div id="vloader">
    <div class="mag-spinner">
        <div class="mag-page"></div><div class="mag-page"></div><div class="mag-page"></div>
    </div>
    <h3>Opening Magazine…</h3>
    <div id="vload-bar-wrap"><div id="vload-bar"></div></div>
    <div id="vloader-msg">Loading pages…</div>
</div>

<!-- Top Bar -->
<div class="vtb">
    <a href="<?= BASE_URL ?>magazine" class="vtb-logo">
        <span class="mag-dot"></span> <?= SITE_NAME ?>
    </a>
    <div class="vtb-meta">
        <h1><?= htmlspecialchars($mag['title']) ?></h1>
        <p><?= date('F Y', strtotime($mag['issue_month'])) ?> &nbsp;·&nbsp; Monthly Edition</p>
    </div>
    <div class="vtb-actions">
        <button class="vtb-btn dk" id="vthumb-toggle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Pages
        </button>
        <a href="assets/magazines/<?= rawurlencode($mag['file_path']) ?>" download class="vtb-btn dk">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
        </a>
        <button class="vtb-btn dk" id="vfs-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            Fullscreen
        </button>
        <a href="<?= BASE_URL ?>magazine" class="vtb-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Back
        </a>
    </div>
</div>

<!-- Thumbnail sidebar -->
<div id="vthumb"><div id="vthumb-inner"></div></div>

<!-- Stage -->
<div id="vstage">
    <!-- Issue nav -->
    <div class="issue-nav">
        <?php if ($prev): ?>
        <a href="<?= BASE_URL ?>magazine/view/<?= $prev['id'] ?>" class="iss-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            <?= htmlspecialchars(date('M Y', strtotime($prev['issue_month']))) ?>
        </a>
        <?php endif; ?>
        <span style="font-size:11px;color:#334155;">📔 <?= date('F Y', strtotime($mag['issue_month'])) ?></span>
        <?php if ($next): ?>
        <a href="<?= BASE_URL ?>magazine/view/<?= $next['id'] ?>" class="iss-btn">
            <?= htmlspecialchars(date('M Y', strtotime($next['issue_month']))) ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        <?php endif; ?>
    </div>

    <!-- Book -->
    <div id="vbook-wrapper">
        <button class="nav-arrow prev disabled" id="vbtn-prev">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <div id="flipbook"></div>
        <button class="nav-arrow next" id="vbtn-next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</div>

<!-- Bottom Controls -->
<div id="vctrl">
    <div class="vctrl-grp">
        <span class="vctrl-lbl">Page</span>
        <div class="pg-wrap">
            <input type="number" id="vpg-in" value="1" min="1">
            <span class="pg-sep">/</span>
            <span class="pg-tot" id="vpg-tot">?</span>
        </div>
    </div>
    <div class="vctrl-grp">
        <button class="vc-btn" id="vzoom-out"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="8" y1="11" x2="14" y2="11"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
        <span id="vzoom-lbl">100%</span>
        <button class="vc-btn" id="vzoom-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
    </div>
    <div class="vctrl-grp">
        <button class="vc-btn" id="vbtn-prev2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
        <button class="vc-btn" id="vbtn-next2"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
    </div>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const PDF_URL    = <?= json_encode($pdf_url) ?>;
const SITE_NAME  = <?= json_encode(SITE_NAME) ?>;
const MAG_TITLE  = <?= json_encode($mag['title']) ?>;
const MAG_MONTH  = <?= json_encode(date('F Y', strtotime($mag['issue_month']))) ?>;

let pdfDoc=null, totalPages=0, zoom=1.0, bookReady=false, thumbsBuilt=false, thumbOpen=false;

function bookDims() {
    const vw=window.innerWidth, vh=window.innerHeight-58-54;
    const maxH=Math.min(vh-30,820);
    const pageW=Math.round(Math.min(vw*.43,510)*zoom);
    const pageH=Math.round(pageW*1.414);
    if(pageH>maxH){const s=maxH/pageH;return{w:Math.round(pageW*s)*2,h:Math.round(pageH*s)};}
    return{w:pageW*2,h:pageH};
}

async function renderPage(num,canvas,scale){
    try{
        const p=await pdfDoc.getPage(num), vp=p.getViewport({scale});
        canvas.width=vp.width; canvas.height=vp.height;
        await p.render({canvasContext:canvas.getContext('2d'),viewport:vp}).promise;
    }catch(e){console.warn('render err p.'+num,e);}
}

function showError(msg){
    document.getElementById('vloader').innerHTML=`
        <div style="text-align:center;max-width:400px;padding:30px;">
            <svg style="width:52px;color:#ef4444;margin-bottom:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <h3 style="color:#fff;font-size:17px;margin-bottom:8px;">Cannot load magazine</h3>
            <p style="color:#94a3b8;font-size:13px;margin-bottom:18px;">${msg}</p>
            <a href="<?= BASE_URL ?>assets/magazines/<?= rawurlencode($mag['file_path']) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:7px;background:#6366f1;color:#fff;padding:10px 20px;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px;">Download PDF</a>
            <a href="<?= BASE_URL ?>magazine" style="display:block;margin-top:12px;color:#475569;font-size:13px;text-decoration:none;">← Back to Archive</a>
        </div>`;
}

async function buildBook(){
    const dims=bookDims(), pageW=dims.w/2, pageH=dims.h;
    const fp=await pdfDoc.getPage(1);
    const scale=pageW/fp.getViewport({scale:1}).width;
    const $b=$('#flipbook');
    $b.empty().css({width:dims.w+'px',height:pageH+'px'});

    // Front cover
    $b.append(`<div class="page cover-page" style="width:${pageW}px;height:${pageH}px;">
        <div class="cv-site">${SITE_NAME}</div>
        <div class="cv-title">${MAG_TITLE}</div>
        <div class="cv-month">${MAG_MONTH}</div>
    </div>`);

    // PDF pages
    for(let i=1;i<=totalPages;i++){
        const $p=$(`<div class="page" style="width:${pageW}px;height:${pageH}px;overflow:hidden;"></div>`);
        const $inner=$('<div style="position:relative;width:100%;height:100%;background:#fff;"></div>');
        const c=document.createElement('canvas');
        $inner.append(c);
        $inner.append(`<div class="page-num-badge">p.${i}</div>`);
        $p.append($inner); $b.append($p);
        const pct=Math.round((i/totalPages)*65)+5;
        document.getElementById('vload-bar').style.width=pct+'%';
        document.getElementById('vloader-msg').textContent=`Building page ${i} of ${totalPages}…`;
    }

    // Back cover
    $b.append(`<div class="page back-cover" style="width:${pageW}px;height:${pageH}px;">© ${SITE_NAME} · All Rights Reserved</div>`);

    // Ensure ≥4 pages
    while($b.children('.page').length<4)
        $b.append(`<div class="page" style="width:${pageW}px;height:${pageH}px;background:#0d0d1a;"></div>`);

    await new Promise(r=>setTimeout(r,60));

    try{
        $b.turn({width:dims.w,height:pageH,autoCenter:true,gradients:true,acceleration:true,display:'double',
            when:{
                turning:(e,pg,view)=>{updateUI(pg);if(view&&view.length)ensureRendered(view,scale);},
                turned: (e,pg,view)=>{updateUI(pg);if(thumbOpen)buildThumbs();}
            }});
        bookReady=true;
    }catch(e){console.error('turn init:',e);}

    ensureRendered([1,2],scale);
    updateUI(1);
    document.getElementById('vpg-tot').textContent=$b.turn('pages');
    setTimeout(()=>{const l=document.getElementById('vloader');l.style.transition='opacity .4s';l.style.opacity='0';setTimeout(()=>l.style.display='none',400);},300);
}

const rendered={};
async function ensureRendered(view,scaleHint){
    if(!view||!view.length||!pdfDoc)return;
    const dims=bookDims(), pageW=dims.w/2;
    for(const tp of view){
        const pp=tp-1;
        if(pp<1||pp>totalPages||rendered[pp])continue;
        rendered[pp]=true;
        const $el=$('#flipbook .page').eq(tp-1);
        const canvas=$el.find('canvas')[0];
        if(!canvas)continue;
        try{
            const pg=await pdfDoc.getPage(pp), vp=pg.getViewport({scale:1});
            await renderPage(pp,canvas,scaleHint||(pageW/vp.width));
        }catch(e){console.warn('render err',e);}
    }
}

function updateUI(pg){
    const inp=document.getElementById('vpg-in'); if(inp)inp.value=pg;
    if(!bookReady)return;
    try{const tot=$('#flipbook').turn('pages');
        $('#vbtn-prev,#vbtn-prev2').toggleClass('disabled',pg<=1);
        $('#vbtn-next,#vbtn-next2').toggleClass('disabled',pg>=tot);
    }catch(e){}
    document.querySelectorAll('.vti').forEach((el,i)=>el.classList.toggle('active',i+1===pg));
}

async function buildThumbs(){
    if(thumbsBuilt)return; thumbsBuilt=true;
    const strip=document.getElementById('vthumb-inner'); strip.innerHTML='';
    for(let i=1;i<=totalPages;i++){
        const w=document.createElement('div'); w.className='vti'; w.dataset.page=i+1;
        const c=document.createElement('canvas');
        const p=await pdfDoc.getPage(i), vp=p.getViewport({scale:1});
        await renderPage(i,c,140/vp.width);
        const l=document.createElement('div'); l.className='vti-lbl'; l.textContent='p.'+i;
        w.appendChild(c); w.appendChild(l);
        w.addEventListener('click',()=>{if(bookReady)$('#flipbook').turn('page',parseInt(w.dataset.page));});
        strip.appendChild(w);
        if(i%5===0)await new Promise(r=>setTimeout(r,0));
    }
}

$(document).ready(async function(){
    try{
        const t=pdfjsLib.getDocument({url:PDF_URL,withCredentials:false});
        t.onProgress=d=>{if(d.total){document.getElementById('vload-bar').style.width=(d.loaded/d.total*40)+'%';}};
        pdfDoc=await t.promise; totalPages=pdfDoc.numPages;
        document.getElementById('vloader-msg').textContent=`${totalPages} pages detected — building…`;
        await buildBook();
    }catch(err){console.error(err);showError(err.message||'Could not load PDF.');}
});

// Navigation
$('#vbtn-prev,#vbtn-prev2').on('click',()=>{if(!$('#vbtn-prev').hasClass('disabled')&&bookReady)$('#flipbook').turn('previous');});
$('#vbtn-next,#vbtn-next2').on('click',()=>{if(!$('#vbtn-next').hasClass('disabled')&&bookReady)$('#flipbook').turn('next');});
document.getElementById('vpg-in').addEventListener('keydown',function(e){if(e.key==='Enter'&&bookReady){const p=parseInt(this.value);if(p>=1)$('#flipbook').turn('page',p);}});
document.addEventListener('keydown',e=>{if(e.target.tagName==='INPUT'||!bookReady)return;if(e.key==='ArrowLeft'||e.key==='PageUp'){e.preventDefault();$('#flipbook').turn('previous');}if(e.key==='ArrowRight'||e.key==='PageDown'){e.preventDefault();$('#flipbook').turn('next');}});

// Zoom
const zooms=[.5,.65,.8,1,1.25,1.5,1.75,2]; let zi=3;
function applyZoom(){zoom=zooms[zi];document.getElementById('vzoom-lbl').textContent=Math.round(zoom*100)+'%';const d=bookDims();$('#flipbook').turn('size',d.w,d.h);Object.keys(rendered).forEach(k=>delete rendered[k]);const cp=$('#flipbook').turn('page');ensureRendered([cp,cp+1]);}
document.getElementById('vzoom-in').addEventListener('click',()=>{if(zi<zooms.length-1){zi++;applyZoom();}});
document.getElementById('vzoom-out').addEventListener('click',()=>{if(zi>0){zi--;applyZoom();}});

// Fullscreen
document.getElementById('vfs-btn').addEventListener('click',()=>{if(!document.fullscreenElement)document.documentElement.requestFullscreen();else document.exitFullscreen();});

// Thumbnails
document.getElementById('vthumb-toggle').addEventListener('click',function(){thumbOpen=!thumbOpen;document.getElementById('vthumb').classList.toggle('open',thumbOpen);buildThumbs();});

// Touch swipe
let tx=0;
document.addEventListener('touchstart',e=>{tx=e.touches[0].clientX;},{passive:true});
document.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-tx;if(Math.abs(dx)>60){if(dx<0)$('#flipbook').turn('next');else $('#flipbook').turn('previous');}},{passive:true});

// Resize
let rt;
window.addEventListener('resize',()=>{clearTimeout(rt);rt=setTimeout(()=>{const d=bookDims();if(bookReady)$('#flipbook').turn('size',d.w,d.h);},200);});
</script>
</body>
</html>
