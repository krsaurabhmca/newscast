<?php
$apni_baat_label = htmlspecialchars(get_setting('apni_baat_label', 'Apni Baat'));
?>
<!-- Apni Baat Trigger Button -->
<button id="feedbackTrigger" style="position: fixed; right: 0; top: 50%; transform: translateY(-50%) rotate(-90deg); transform-origin: right bottom; background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px 8px 0 0; cursor: pointer; z-index: 1001; font-weight: 700; box-shadow: -2px 0 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px; font-size: 13px; letter-spacing: 0.5px; white-space: nowrap;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(90deg); flex-shrink:0;"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
    <?php echo $apni_baat_label; ?>
</button>

<!-- Apni Baat Drawer -->
<div id="feedbackDrawer" style="position: fixed; right: -420px; top: 0; width: 400px; height: 100vh; background: white; box-shadow: -5px 0 40px rgba(0,0,0,0.18); z-index: 1002; transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;">

    <!-- Drawer Header -->
    <div style="padding: 22px 25px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <div style="background: var(--primary); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <h3 style="margin: 0; font-size: 17px; font-weight: 800; letter-spacing: -0.3px;"><?php echo $apni_baat_label; ?></h3>
            </div>
            <p style="margin: 0; font-size: 12px; opacity: 0.6; padding-left: 42px;">Submit your article for review &amp; publication</p>
        </div>
        <button id="closeFeedback" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; flex-shrink: 0;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <!-- Info strip -->
    <div style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0; padding: 10px 20px; display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="margin: 0; font-size: 12px; color: #166534; font-weight: 600; line-height: 1.4;">Your article will be reviewed by our editors before publishing.</p>
    </div>

    <!-- Form Area -->
    <div style="flex: 1; padding: 24px 22px; overflow-y: auto;">
        <form id="feedbackForm" enctype="multipart/form-data">
            <input type="hidden" name="submit_feedback" value="1">

            <!-- Name -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Full Name <span style="color: var(--primary);">*</span></label>
                <input type="text" name="name" required placeholder="Your full name" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Email -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Email Address <span style="color: var(--primary);">*</span></label>
                <input type="email" name="email" required placeholder="your@email.com" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Phone -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Mobile Number</label>
                <input type="tel" name="phone" placeholder="Optional — for follow-up" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Article Title -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Article Title <span style="color: var(--primary);">*</span></label>
                <input type="text" name="subject" required placeholder="Enter a compelling headline..." style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
            </div>

            <!-- Category -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Category</label>
                <select name="category_id" style="width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white; outline: none; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'">
                    <option value="">— Select Category (Optional) —</option>
                    <?php
                    // Fetch active top-level categories for selection
                    $drawer_cats = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' AND (parent_id IS NULL OR parent_id = 0) ORDER BY name ASC")->fetchAll();
                    foreach ($drawer_cats as $dc):
                    ?>
                    <option value="<?php echo $dc['id']; ?>"><?php echo htmlspecialchars($dc['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Featured Photo -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Featured Photo</label>
                <label for="ab_photo_input" style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1.5px dashed #cbd5e1; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: #f8fafc;" onmouseover="this.style.borderColor='var(--primary)';this.style.background='#f5f3ff'" onmouseout="this.style.borderColor='#cbd5e1';this.style.background='#f8fafc'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span id="ab_photo_label" style="font-size: 13px; color: #64748b; font-weight: 600;">Click to upload a photo (optional)</span>
                    <input type="file" id="ab_photo_input" name="featured_image" accept="image/*" style="display:none;" onchange="document.getElementById('ab_photo_label').textContent = this.files[0]?.name || 'Click to upload a photo (optional)'">
                </label>
            </div>

            <!-- Article Content -->
            <div style="margin-bottom: 22px;">
                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px;">Article Content <span style="color: var(--primary);">*</span></label>
                <textarea name="message" required placeholder="Write your article here. Include all important details, facts, and context..." style="width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; min-height: 140px; resize: vertical; outline: none; line-height: 1.6; transition: border-color 0.2s; box-sizing: border-box;" onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="abSubmitBtn" style="width: 100%; padding: 13px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 14px; letter-spacing: 0.3px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Submit Article
            </button>
        </form>

        <div id="feedbackResponse" style="margin-top: 20px; padding: 18px 20px; border-radius: 12px; display: none; font-size: 14px; font-weight: 700; text-align: center; line-height: 1.6;"></div>
    </div>

    <div style="padding: 14px 20px; border-top: 1px solid #f1f5f9; text-align: center; flex-shrink: 0;">
        <p style="font-size: 11px; color: #94a3b8; margin: 0;">© <?php echo date('Y'); ?> <?php echo SITE_NAME_DYNAMIC; ?> — Articles are published after editorial review</p>
    </div>
</div>

<!-- Overlay -->
<div id="feedbackOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); z-index: 1001; display: none; backdrop-filter: blur(3px);"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trigger = document.getElementById('feedbackTrigger');
    const drawer = document.getElementById('feedbackDrawer');
    const overlay = document.getElementById('feedbackOverlay');
    const closeBtn = document.getElementById('closeFeedback');
    const form = document.getElementById('feedbackForm');
    const responseDiv = document.getElementById('feedbackResponse');
    const submitBtn = document.getElementById('abSubmitBtn');

    function openDrawer() {
        drawer.style.right = '0';
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.style.right = '-420px';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        setTimeout(() => {
            responseDiv.style.display = 'none';
            form.style.display = 'block';
            form.reset();
            document.getElementById('ab_photo_label').textContent = 'Click to upload a photo (optional)';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit Article';
        }, 420);
    }

    trigger.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Submitting...';

        fetch('<?php echo BASE_URL; ?>includes/feedback_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            form.style.display = 'none';
            responseDiv.style.display = 'block';
            if (data.status === 'success') {
                responseDiv.innerHTML = '<div style="font-size:40px;margin-bottom:12px;">🎉</div>' +
                    '<div style="font-size:16px;font-weight:800;color:#059669;margin-bottom:8px;">Article Submitted!</div>' +
                    '<div style="font-size:13px;color:#475569;font-weight:500;">' + data.message + '</div>';
                responseDiv.style.backgroundColor = '#ecfdf5';
                responseDiv.style.border = '1.5px solid #a7f3d0';
                setTimeout(closeDrawer, 4000);
            } else {
                responseDiv.innerHTML = '<div style="font-size:13px;font-weight:700;color:#dc2626;">' + data.message + '</div>';
                responseDiv.style.backgroundColor = '#fef2f2';
                responseDiv.style.border = '1.5px solid #fecaca';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit Article';
                form.style.display = 'block';
                setTimeout(() => { responseDiv.style.display = 'none'; }, 5000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Submit Article';
        });
    });
});
</script>
