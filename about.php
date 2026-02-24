<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "About Us";
include 'includes/public_header.php';
?>

<div class="content-container" style="max-width: 900px; padding: 50px 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 30px; border-bottom: 4px solid var(--primary); display: inline-block; padding-bottom: 5px;">About <?php echo SITE_NAME_DYNAMIC; ?></h1>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 40px;">
            <section>
                <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">Our Mission</h2>
                <p style="line-height: 1.8; color: #334155; font-size: 16px;">
                    Welcome to <strong><?php echo SITE_NAME_DYNAMIC; ?></strong>, your number one source for all things digital news. We're dedicated to giving you the very best of journalism, with a focus on reliability, real-time updates, and local impact.
                </p>
            </section>

            <section>
                <h2 style="font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">Our Story</h2>
                <p style="line-height: 1.8; color: #334155;">
                    Founded in <?php echo date('Y'); ?>, <?php echo SITE_NAME_DYNAMIC; ?> has come a long way from its beginnings. When we first started out, our passion for "Truth in Digital" drove us to start our own news portal so that <?php echo SITE_NAME_DYNAMIC; ?> can offer you the most credible information. We now serve readers all over the region and are thrilled that we're able to turn our passion into our own website.
                </p>
            </section>

            <section style="background: #f8fafc; padding: 30px; border-radius: 12px; border-left: 5px solid var(--primary);">
                <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 10px;">Why Choose Us?</h2>
                <ul style="line-height: 1.8; color: #475569; padding-left: 20px;">
                    <li>Unbiased and independent reporting.</li>
                    <li>24/7 breaking news alerts.</li>
                    <li>Deep-dive investigations into local issues.</li>
                    <li>A user-friendly digital experience.</li>
                </ul>
            </section>

            <p style="line-height: 1.8; color: #334155; font-size: 16px; text-align: center; margin-top: 20px;">
                We hope you enjoy our news coverage as much as we enjoy offering it to you. If you have any questions or comments, please don't hesitate to contact us.
            </p>
            
            <p style="text-align: center; font-weight: 700; color: #0f172a; font-size: 18px;">
                Sincerely,<br>
                <span style="color: var(--primary);">The <?php echo SITE_NAME_DYNAMIC; ?> Team</span>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/public_footer.php'; ?>
