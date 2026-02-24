<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Contact Us";
include 'includes/public_header.php';
?>

<div class="content-container" style="max-width: 1000px; padding: 50px 20px;">
    <h1 style="font-size: 36px; font-weight: 800; color: #0f172a; text-align: center; margin-bottom: 50px;">Get in Touch</h1>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
        <!-- Contact Info -->
        <div>
            <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%;">
                <h3 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 25px;">Contact Information</h3>
                <p style="color: #64748b; margin-bottom: 30px; line-height: 1.6;">Have a news tip or want to advertise with us? Reach out through any of these channels.</p>

                <div style="display: flex; gap: 20px; align-items: start; margin-bottom: 25px;">
                    <div style="background: rgba(99, 102, 241, 0.1); color: var(--primary); padding: 12px; border-radius: 12px;">
                        <i data-feather="mail"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 16px; color: #1e293b;">Email Us</h4>
                        <p style="margin: 5px 0 0 0; color: #475569; font-weight: 600;"><?php echo get_setting('contact_email', 'admin@example.com'); ?></p>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; align-items: start; margin-bottom: 25px;">
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 12px; border-radius: 12px;">
                        <i data-feather="phone"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 16px; color: #1e293b;">Call Us</h4>
                        <p style="margin: 5px 0 0 0; color: #475569; font-weight: 600;"><?php echo get_setting('contact_phone', '+91 000 000 0000'); ?></p>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; align-items: start;">
                    <div style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 12px; border-radius: 12px;">
                        <i data-feather="map-pin"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 16px; color: #1e293b;">Headquarters</h4>
                        <p style="margin: 5px 0 0 0; color: #475569; font-weight: 600;">Digital News Tower, Main Street, India</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form (Linked to Feedback Handler) -->
        <div>
            <div style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <h3 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 25px;">Send a Message</h3>
                
                <form id="contactPageForm">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Your Name</label>
                        <input type="text" name="name" required style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="Full Name">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Email Address</label>
                        <input type="email" name="email" required style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="Email@example.com">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Mobile Number</label>
                        <input type="tel" name="phone" style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px;" placeholder="+91 00000 00000">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 8px;">Your Message</label>
                        <textarea name="message" required style="width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; min-height: 120px; resize: vertical;" placeholder="Tell us more..."></textarea>
                    </div>
                    <button type="submit" style="width: 100%; padding: 14px; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.2s;">
                        Send Message
                    </button>
                </form>
                <div id="contactResponse" style="margin-top: 20px; padding: 15px; border-radius: 10px; display: none; text-align: center; font-weight: 600;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('contactPageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const responseDiv = document.getElementById('contactResponse');
    const formData = new FormData(form);
    formData.append('subject', 'Web Contact Form');

    fetch('<?php echo BASE_URL; ?>includes/feedback_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        responseDiv.innerText = data.message;
        responseDiv.style.display = 'block';
        responseDiv.style.backgroundColor = data.status === 'success' ? '#ecfdf5' : '#fef2f2';
        responseDiv.style.color = data.status === 'success' ? '#059669' : '#dc2626';
        if(data.status === 'success') form.reset();
    });
});
</script>

<?php include 'includes/public_footer.php'; ?>
