<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Terms of Use";
include 'includes/public_header.php';
?>

<div class="content-container" style="max-width: 900px; padding: 50px 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 30px; border-bottom: 4px solid var(--primary); display: inline-block; padding-bottom: 5px;">Terms of Use</h1>
        
        <p style="color: #64748b; font-size: 14px; margin-bottom: 30px;">Effective Date: <?php echo date('F d, Y'); ?></p>

        <section style="margin-bottom: 30px;">
            <p style="line-height: 1.8; color: #334155; margin-bottom: 20px;">
                Welcome to <strong><?php echo SITE_NAME_DYNAMIC; ?></strong>. These Terms of Use govern your access to and use of <strong><?php echo BASE_URL; ?></strong> and any content, functionality, and services offered on or through the website.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">1. Acceptance of Terms</h2>
            <p style="line-height: 1.8; color: #334155;">
                By using our website, you accept and agree to be bound and abide by these Terms of Use and our Privacy Policy. If you do not want to agree to these Terms of Use or the Privacy Policy, you must not access or use the website.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">2. Intellectual Property Rights</h2>
            <p style="line-height: 1.8; color: #334155;">
                The website and its entire contents, features, and functionality (including but not limited to all information, software, text, displays, images, video, and audio) are owned by <?php echo SITE_NAME_DYNAMIC; ?>, its licensors, or other providers of such material and are protected by copyright, trademark, and other intellectual property or proprietary rights laws.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">3. User Conduct</h2>
            <p style="line-height: 1.8; color: #334155; margin-bottom: 15px;">You agree not to use the website:</p>
            <ul style="line-height: 1.8; color: #334155; padding-left: 20px;">
                <li>In any way that violates any applicable local or international law.</li>
                <li>To transmit, or procure the sending of, any advertising or promotional material, including "junk mail" or "spam".</li>
                <li>To impersonate or attempt to impersonate <?php echo SITE_NAME_DYNAMIC; ?>, an employee, or any other person.</li>
                <li>To engage in any other conduct that restricts or inhibits anyone's use or enjoyment of the website.</li>
            </ul>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">4. Disclaimer of Warranties</h2>
            <p style="line-height: 1.8; color: #334155;">
                Your use of the website, its content, and any services or items obtained through the website is at your own risk. The website is provided on an "as is" and "as available" basis, without any warranties of any kind, either express or implied.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">5. Changes to Terms</h2>
            <p style="line-height: 1.8; color: #334155;">
                We may revise and update these Terms of Use from time to time in our sole discretion. All changes are effective immediately when we post them.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">6. Contact</h2>
            <p style="line-height: 1.8; color: #334155;">
                To ask questions or comment about these Terms of Use, contact us at: <strong><?php echo get_setting('contact_email', 'admin@example.com'); ?></strong>.
            </p>
        </section>
    </div>
</div>

<?php include 'includes/public_footer.php'; ?>
