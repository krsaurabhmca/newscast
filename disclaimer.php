<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Disclaimer";
include 'includes/public_header.php';
?>

<div class="content-container" style="max-width: 900px; padding: 50px 20px;">
    <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 30px; border-bottom: 4px solid var(--primary); display: inline-block; padding-bottom: 5px;">Disclaimer</h1>
        
        <p style="color: #64748b; font-size: 14px; margin-bottom: 30px;">Last Updated: <?php echo date('F d, Y'); ?></p>

        <section style="margin-bottom: 30px;">
            <p style="line-height: 1.8; color: #334155; margin-bottom: 20px;">
                The information provided by <strong><?php echo SITE_NAME_DYNAMIC; ?></strong> on <strong><?php echo BASE_URL; ?></strong> is for general informational purposes only. All information on the site is provided in good faith; however, we make no representation or warranty of any kind, express or implied, regarding the accuracy, adequacy, validity, reliability, availability, or completeness of any information on the site.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">1. Professional Disclaimer</h2>
            <p style="line-height: 1.8; color: #334155;">
                The site cannot and does not contain legal, financial, or medical advice. The information is provided for general informational and educational purposes only and is not a substitute for professional advice. Accordingly, before taking any actions based upon such information, we encourage you to consult with the appropriate professionals.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">2. External Links Disclaimer</h2>
            <p style="line-height: 1.8; color: #334155;">
                The site may contain links to other websites or content belonging to or originating from third parties. Such external links are not investigated, monitored, or checked for accuracy, adequacy, validity, reliability, availability, or completeness by us. We do not warrant, endorse, guarantee, or assume responsibility for the accuracy or reliability of any information offered by third-party websites linked through the site.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">3. Errors and Omissions Disclaimer</h2>
            <p style="line-height: 1.8; color: #334155;">
                While we have made every attempt to ensure that the information contained in this site has been obtained from reliable sources, <?php echo SITE_NAME_DYNAMIC; ?> is not responsible for any errors or omissions, or for the results obtained from the use of this information.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">4. Fair Use Disclaimer</h2>
            <p style="line-height: 1.8; color: #334155;">
                This site may contain copyrighted material the use of which has not always been specifically authorized by the copyright owner. We are making such material available in our efforts to advance understanding of news, politics, and social issues. We believe this constitutes a "fair use" of any such copyrighted material.
            </p>
        </section>

        <section style="margin-bottom: 30px;">
            <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">5. Contact</h2>
            <p style="line-height: 1.8; color: #334155;">
                Should you have any feedback, comments, requests for technical support, or other inquiries, please contact us by email: <strong><?php echo get_setting('contact_email', 'admin@example.com'); ?></strong>.
            </p>
        </section>
    </div>
</div>

<?php include 'includes/public_footer.php'; ?>
