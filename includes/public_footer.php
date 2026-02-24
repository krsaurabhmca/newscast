    <footer class="bhaskar-footer <?php echo (get_setting('footer_theme') == 'dark') ? 'theme-dark' : 'theme-light'; ?>">
        <div class="content-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-brand" style="margin-bottom: 25px;">
                         <?php if (get_setting('site_logo')): ?>
                            <img src="<?php echo BASE_URL . 'assets/images/' . get_setting('site_logo'); ?>" style="height: 45px; margin-bottom: 15px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                        <?php else: ?>
                            <h2 style="font-weight: 900; color: var(--primary); margin-bottom: 10px;"><?php echo SITE_NAME_DYNAMIC; ?></h2>
                        <?php endif; ?>
                        <p style="color: #64748b; font-size: 14px; line-height: 1.6;">Stay informed with the most accurate news and real stories from around the world. Your trusted source for daily digital news.</p>
                    </div>
                    <div class="social-icons" style="display: flex; gap: 12px;">
                        <?php if(get_setting('facebook_url')): ?>
                            <a href="<?php echo get_setting('facebook_url'); ?>" target="_blank" style="width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #1877f2;"><i data-feather="facebook" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('twitter_url')): ?>
                            <a href="<?php echo get_setting('twitter_url'); ?>" target="_blank" style="width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #000;"><i data-feather="twitter" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('instagram_url')): ?>
                            <a href="<?php echo get_setting('instagram_url'); ?>" target="_blank" style="width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #e4405f;"><i data-feather="instagram" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('youtube_url')): ?>
                            <a href="<?php echo get_setting('youtube_url'); ?>" target="_blank" style="width: 38px; height: 38px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #ff0000;"><i data-feather="youtube" style="width: 18px;"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer-col">
                    <h5>Categories</h5>
                    <ul>
                        <?php foreach($nav_categories as $cat): ?>
                            <li><a href="<?php echo BASE_URL; ?>category/<?php echo $cat['slug']; ?>"><?php echo $cat['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h5>Support</h5>
                    <ul>
                        <li><a href="<?php echo BASE_URL; ?>about.php">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php">Contact Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>terms.php">Terms of Use</a></li>
                        <li><a href="<?php echo BASE_URL; ?>disclaimer.php">Disclaimer</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h5>Connect</h5>
                    <ul>
                        <li><i data-feather="mail" style="width: 16px;"></i> <?php echo get_setting('contact_email', 'admin@example.com'); ?></li>
                        <li><i data-feather="phone" style="width: 16px;"></i> <?php echo get_setting('contact_phone', '+91 000 000 0000'); ?></li>
                        <?php if(get_setting('whatsapp_number')): ?>
                        <li><i data-feather="message-circle" style="width: 16px;"></i> WhatsApp Support</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div style="border-top: 1px solid #f1f5f9; padding-top: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <p style="color: #64748b; font-size: 13px;">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME_DYNAMIC; ?>. Digital Presence by DevTeam.</p>
                <div style="display: flex; gap: 20px;">
                    <a href="#" style="font-size: 12px; color: #94a3b8;">Home</a>
                    <a href="#" style="font-size: 12px; color: #94a3b8;">Sitemap</a>
                    <a href="#" style="font-size: 12px; color: #94a3b8;">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>
        </div><!-- .main-wrapper -->
    </div><!-- .app-container -->

    <style>
        /* Disable Text Selection */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        /* Disable Printing */
        @media print {
            body { display: none !important; }
        }

        /* Ensure footer stays within main wrapper bounds */
        .bhaskar-footer .content-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        @media (max-width: 1024px) {
            .footer-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        @media (max-width: 640px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <script>
        feather.replace();

        // Disable Right Click
        document.addEventListener('contextmenu', event => event.preventDefault());

        // Disable Keyboard Shortcuts (Ctrl+C, Ctrl+V, Ctrl+U, Ctrl+P, F12)
        document.onkeydown = function(e) {
            if (e.ctrlKey && 
                (e.keyCode === 67 || 
                 e.keyCode === 86 || 
                 e.keyCode === 85 || 
                 e.keyCode === 80)) {
                return false;
            }
            if (e.keyCode === 123) { // F12
                return false;
            }
        };
    </script>
    <?php include 'includes/feedback_drawer.php'; ?>
</body>
</html>
