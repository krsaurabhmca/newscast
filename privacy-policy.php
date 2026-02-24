<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Privacy Policy";
include 'includes/public_header.php';
?>

<div class="content-container" style="max-width: 900px; padding: 50px 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 30px; border-bottom: 4px solid var(--primary); display: inline-block; padding-bottom: 5px;">Privacy Policy</h1>
        
        <p style="color: #64748b; font-size: 14px; margin-bottom: 30px;">Last Updated: <?php echo date('F d, Y'); ?></p>

        <section style="margin-bottom: 30px;">
            <p style="line-height: 1.8; color: #334155; margin-bottom: 20px;">
                At <strong><?php echo SITE_NAME_DYNAMIC; ?></strong>, accessible from <strong><?php echo BASE_URL; ?></strong>, one of our main priorities is the privacy of our visitors. This Privacy Policy document contains types of information that is collected and recorded by <?php echo SITE_NAME_DYNAMIC; ?> and how we use it.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">1. Information We Collect</h2>
            <p style="line-height: 1.8; color: #334155; margin-bottom: 15px;">
                The personal information that you are asked to provide, and the reasons why you are asked to provide it, will be made clear to you at the point we ask you to provide your personal information.
            </p>
            <ul style="line-height: 1.8; color: #334155; padding-left: 20px;">
                <li>Contact information (such as name and email address) when you subscribe to our newsletter or submit feedback.</li>
                <li>Device information and log data (such as IP address, browser type, and pages visited).</li>
                <li>Cookies and similar tracking technologies to improve user experience.</li>
            </ul>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">2. How We Use Your Information</h2>
            <p style="line-height: 1.8; color: #334155; margin-bottom: 15px;">We use the information we collect in various ways, including to:</p>
            <ul style="line-height: 1.8; color: #334155; padding-left: 20px;">
                <li>Provide, operate, and maintain our website.</li>
                <li>Improve, personalize, and expand our website.</li>
                <li>Understand and analyze how you use our website.</li>
                <li>Develop new products, services, features, and functionality.</li>
                <li>Communicate with you, either directly or through one of our partners.</li>
                <li>Send you emails and newsletters.</li>
            </ul>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">3. Log Files</h2>
            <p style="line-height: 1.8; color: #334155;">
                <?php echo SITE_NAME_DYNAMIC; ?> follows a standard procedure of using log files. These files log visitors when they visit websites. All hosting companies do this and a part of hosting services' analytics. The information collected by log files include internet protocol (IP) addresses, browser type, Internet Service Provider (ISP), date and time stamp, referring/exit pages, and possibly the number of clicks.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">4. Advertising Partners</h2>
            <p style="line-height: 1.8; color: #334155;">
                Some of our advertisers on our site may use cookies and web beacons. Our advertising partners each have their own Privacy Policy for their policies on user data. For easier access, we hyperlinked to their Privacy Policies below where possible (e.g., Google AdSense).
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">5. Contact Us</h2>
            <p style="line-height: 1.8; color: #334155;">
                If you have additional questions or require more information about our Privacy Policy, do not hesitate to contact us at <strong><?php echo get_setting('contact_email', 'admin@example.com'); ?></strong>.
            </p>
        </section>
    </div>
</div>

<?php include 'includes/public_footer.php'; ?>
