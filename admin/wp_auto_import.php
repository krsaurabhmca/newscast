<?php
$page_title = "WP Auto Importer";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

include 'includes/header.php';
?>

<style>
    .spin { animation: spin 1s linear infinite; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0;">WordPress Importer</h2>
            <p style="color: #64748b; font-size: 14px; margin: 5px 0 0;">Import posts seamlessly. Categories will be auto-created if they don't exist.</p>
        </div>
        <div style="background: rgba(16,185,129,0.1); color: #10b981; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
            <i data-feather="download-cloud"></i>
        </div>
    </div>

    <div class="settings-card" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
        <div style="margin-bottom: 20px;">
            <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">WordPress API Endpoint</label>
            <input type="text" id="batch_wp_api_url" class="form-control" placeholder="e.g. https://site.com/wp-json/wp/v2/posts?_embed=1">
            <span style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">Must include `_embed=1` to import categories and images.</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">From Date</label>
                <input type="datetime-local" id="batch_from_date" class="form-control">
            </div>
            <div>
                <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">To Date</label>
                <input type="datetime-local" id="batch_to_date" class="form-control">
            </div>
        </div>

        <div style="margin-top: 25px; margin-bottom: 25px;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; color: #1e293b; cursor: pointer;">
                <input type="checkbox" id="rewrite_with_ai_batch" style="width: 18px; height: 18px; cursor: pointer;">
                Rewrite content with AI (If unchecked, imports directly as is)
            </label>
        </div>
        <div>
            <button onclick="startBatchImport()" id="btn-batch-import" class="btn btn-primary" style="font-weight: 700; display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; width: 100%; justify-content: center;">
                <i data-feather="download" style="width: 18px;"></i> Start Import
            </button>
        </div>
    </div>

    <div id="batch-results" style="display: none;">
        <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-top: 0; margin-bottom: 15px;">Import Progress</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase;">Total Fetched</div>
                    <div id="batch-total-fetched" style="font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 5px;">0</div>
                </div>
                <div style="background: #ecfdf5; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 12px; color: #10b981; font-weight: 700; text-transform: uppercase;">Imported</div>
                    <div id="batch-total-imported" style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 5px;">0</div>
                </div>
                <div style="background: #fef2f2; padding: 15px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 12px; color: #ef4444; font-weight: 700; text-transform: uppercase;">Skipped/Failed</div>
                    <div id="batch-total-skipped" style="font-size: 24px; font-weight: 800; color: #dc2626; margin-top: 5px;">0</div>
                </div>
            </div>

            <div id="batch-log" style="height: 250px; overflow-y: auto; background: #0f172a; color: #e2e8f0; font-family: monospace; font-size: 12px; padding: 15px; border-radius: 8px;">
                <!-- Logs go here -->
            </div>
        </div>
    </div>
</div>

<script>
    // Set default dates to today
    window.addEventListener('load', () => { 
        feather.replace(); 
        
        const now = new Date();
        // Today at 00:00
        const fromDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0);
        // Today at 23:59
        const toDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);

        const pad = (n) => n.toString().padStart(2, '0');
        
        const formatDateTimeLocal = (d) => {
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        };

        document.getElementById('batch_from_date').value = formatDateTimeLocal(fromDate);
        document.getElementById('batch_to_date').value = formatDateTimeLocal(toDate);
    });

    async function startBatchImport() {
        const baseUrl = document.getElementById('batch_wp_api_url').value;
        const fromDate = document.getElementById('batch_from_date').value;
        const toDate = document.getElementById('batch_to_date').value;
        
        if (!baseUrl || !fromDate || !toDate) {
            alert("Please fill all fields: API URL, From Date, and To Date.");
            return;
        }

        const rewriteWithAi = document.getElementById('rewrite_with_ai_batch').checked;

        const btn = document.getElementById('btn-batch-import');
        btn.innerHTML = '<i data-feather="loader" class="spin" style="width: 18px;"></i> Running Import...';
        btn.disabled = true;
        document.getElementById('batch-results').style.display = 'block';
        
        const logContainer = document.getElementById('batch-log');
        logContainer.innerHTML = '<div>Starting import...</div>';
        
        let fetchedCount = 0;
        let importedCount = 0;
        let skippedCount = 0;
        let page = 1;
        let hasMore = true;

        const formatWpDate = (dt) => {
            if (dt.length === 16) return dt + ':00';
            return dt;
        };

        const afterParam = formatWpDate(fromDate);
        const beforeParam = formatWpDate(toDate);

        let apiUrl = baseUrl.trim();
        if (apiUrl && !apiUrl.startsWith('http')) {
            apiUrl = 'https://' + apiUrl;
        }

        if (!apiUrl.includes('wp-json')) {
            apiUrl = apiUrl.replace(/\/+$/, '') + '/wp-json/wp/v2/posts';
        }

        const log = (msg) => {
            const div = document.createElement('div');
            div.style.marginBottom = '5px';
            div.innerText = `[${new Date().toLocaleTimeString()}] ${msg}`;
            logContainer.appendChild(div);
            logContainer.scrollTop = logContainer.scrollHeight;
        };

        let urlObj;
        try {
            urlObj = new URL(apiUrl);
        } catch (e) {
            log(`Invalid API URL: ${apiUrl}`);
            btn.innerHTML = '<i data-feather="alert-circle" style="width: 18px;"></i> URL Error';
            btn.disabled = false;
            feather.replace();
            return;
        }

        // Ensure embed is present
        urlObj.searchParams.set('_embed', '1');

        while (hasMore) {
            log(`Fetching page ${page}...`);
            
            urlObj.searchParams.set('after', afterParam);
            urlObj.searchParams.set('before', beforeParam);
            urlObj.searchParams.set('per_page', '10');
            urlObj.searchParams.set('page', page);
            
            const fetchUrl = urlObj.toString();
            log(`Requesting URL: ${fetchUrl}`);
            
            try {
                const proxyUrl = `ajax_wp_proxy.php?url=${encodeURIComponent(fetchUrl)}`;
                const response = await fetch(proxyUrl);
                if (!response.ok) {
                    log(`No more posts found on page ${page} or API returned error. Finishing.`);
                    hasMore = false;
                    break;
                }
                
                const data = await response.json();
                if (!data || data.length === 0) {
                    log(`Page ${page} is empty. Finishing.`);
                    hasMore = false;
                    break;
                }

                log(`Found ${data.length} posts on page ${page}. Processing...`);
                
                for (const post of data) {
                    fetchedCount++;
                    document.getElementById('batch-total-fetched').innerText = fetchedCount;
                    
                    log(`Processing: ${post.title.rendered}`);
                    
                    let imageUrl = '';
                    let categoryName = 'Uncategorized';
                    
                    if (post._embedded) {
                        if (post._embedded['wp:featuredmedia'] && post._embedded['wp:featuredmedia'][0]) {
                            imageUrl = post._embedded['wp:featuredmedia'][0].source_url;
                        }
                        
                        // Extract category name
                        if (post._embedded['wp:term'] && post._embedded['wp:term'][0]) {
                            const terms = post._embedded['wp:term'][0];
                            if (terms.length > 0 && terms[0].name) {
                                categoryName = terms[0].name;
                            }
                        }
                    }

                    try {
                        const importRes = await fetch('ajax_wp_import_single.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                title: post.title.rendered,
                                content: post.content.rendered,
                                image_url: imageUrl,
                                category_name: categoryName,
                                published_at: post.date,
                                source_url: post.link,
                                rewrite_with_ai: rewriteWithAi,
                                slug: post.slug
                            })
                        });

                        const result = await importRes.json();
                        if (result.success) {
                            if (result.skipped) {
                                skippedCount++;
                                document.getElementById('batch-total-skipped').innerText = skippedCount;
                                log(`  -> Skipped: Duplicate post.`);
                            } else {
                                importedCount++;
                                document.getElementById('batch-total-imported').innerText = importedCount;
                                log(`  -> Success: Imported under category "${categoryName}"!`);
                            }
                        } else {
                            skippedCount++;
                            document.getElementById('batch-total-skipped').innerText = skippedCount;
                            log(`  -> Failed: ${result.error}`);
                        }
                    } catch (err) {
                        skippedCount++;
                        document.getElementById('batch-total-skipped').innerText = skippedCount;
                        log(`  -> Failed: Network/Server error during import request.`);
                    }
                }
                page++;
            } catch (error) {
                log(`Error fetching page ${page}: ${error.message}`);
                hasMore = false;
            }
        }
        
        log(`Import completed!`);
        btn.innerHTML = '<i data-feather="check" style="width: 18px;"></i> Import Complete';
        btn.disabled = false;
        feather.replace();
    }
</script>

<?php include 'includes/footer.php'; ?>
