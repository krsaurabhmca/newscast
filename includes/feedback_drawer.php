<!-- Feedback Trigger Button -->
<button id="feedbackTrigger" style="position: fixed; right: 0; top: 50%; transform: translateY(-50%) rotate(-90deg); transform-origin: right bottom; background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px 8px 0 0; cursor: pointer; z-index: 1001; font-weight: 700; box-shadow: -2px 0 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="transform: rotate(90deg);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
    FEEDBACK
</button>

<!-- Feedback Drawer -->
<div id="feedbackDrawer" style="position: fixed; right: -400px; top: 0; width: 380px; height: 100vh; background: white; box-shadow: -5px 0 30px rgba(0,0,0,0.15); z-index: 1002; transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column;">
    <div style="padding: 25px; background: #0f172a; color: white; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Share Your Feedback</h3>
            <p style="margin: 5px 0 0 0; font-size: 12px; opacity: 0.7;">We value your thoughts and suggestions</p>
        </div>
        <button id="closeFeedback" style="background: rgba(255,255,255,0.1); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <div style="flex: 1; padding: 30px; overflow-y: auto;">
        <form id="feedbackForm">
            <input type="hidden" name="submit_feedback" value="1">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Full Name</label>
                <input type="text" name="name" required style="width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="Enter your name">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="Enter your email">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Mobile Number</label>
                <input type="tel" name="phone" style="width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="Optional: Enter mobile number">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Subject</label>
                <select name="subject" required style="width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white;">
                    <option value="General Feedback">General Feedback</option>
                    <option value="Bug Report">Bug Report</option>
                    <option value="Feature Suggestion">Feature Suggestion</option>
                    <option value="Content Issue">Content Issue</option>
                </select>
            </div>
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px;">Message</label>
                <textarea name="message" required style="width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; min-height: 120px; resize: vertical;" placeholder="How can we improve?"></textarea>
            </div>
            <button type="submit" style="width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px;">
                Send Feedback
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </form>
        <div id="feedbackResponse" style="margin-top: 20px; padding: 15px; border-radius: 10px; display: none; font-size: 14px; font-weight: 600; text-align: center;"></div>
    </div>

    <div style="padding: 20px; border-top: 1px solid #f1f5f9; text-align: center;">
        <p style="font-size: 11px; color: #94a3b8; margin: 0;">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME_DYNAMIC; ?> Digital</p>
    </div>
</div>

<!-- Overlay -->
<div id="feedbackOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 1001; display: none; backdrop-filter: blur(2px);"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const trigger = document.getElementById('feedbackTrigger');
    const drawer = document.getElementById('feedbackDrawer');
    const overlay = document.getElementById('feedbackOverlay');
    const closeBtn = document.getElementById('closeFeedback');
    const form = document.getElementById('feedbackForm');
    const responseDiv = document.getElementById('feedbackResponse');

    function openDrawer() {
        drawer.style.right = '0';
        overlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.style.right = '-400px';
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        setTimeout(() => {
            responseDiv.style.display = 'none';
            form.style.display = 'block';
            form.reset();
        }, 400);
    }

    trigger.addEventListener('click', openDrawer);
    closeBtn.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Sending...';

        fetch('<?php echo BASE_URL; ?>includes/feedback_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            form.style.display = 'none';
            responseDiv.style.display = 'block';
            responseDiv.innerText = data.message;
            responseDiv.style.backgroundColor = data.status === 'success' ? '#ecfdf5' : '#fef2f2';
            responseDiv.style.color = data.status === 'success' ? '#059669' : '#dc2626';
            
            if(data.status === 'success') {
                setTimeout(closeDrawer, 3000);
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>
