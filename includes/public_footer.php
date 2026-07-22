<?php 
$is_theme2 = (get_setting('homepage_theme', 'theme1') === 'theme2');
$compact_class = $is_theme2 ? ' compact-footer' : '';
?>
    <footer class="bhaskar-footer <?php echo (get_setting('footer_theme') == 'dark') ? 'theme-dark' : 'theme-light'; ?><?php echo $compact_class; ?>">

        <!-- ── Main Footer Content ───────────────────────────────────── -->
        <!-- ── Main Footer Content ───────────────────────────────────── -->
        <div class="content-container footer-content-wrapper" style="max-width: 1300px; margin: 0 auto; padding: 40px 20px 10px 20px;">
            <div class="footer-grid" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 30px; margin-bottom: 30px;">
                <!-- Column 1: Brand & About -->
                <div class="footer-col brand-col">
                    <div class="footer-brand" style="margin-bottom: 15px;">
                         <?php if (get_setting('site_logo')): ?>
                            <img src="<?php echo BASE_URL . 'assets/images/' . get_setting('site_logo'); ?>" style="height: 35px; margin-bottom: 10px;" alt="<?php echo SITE_NAME_DYNAMIC; ?>">
                        <?php else: ?>
                            <h2 style="font-size: 24px; font-weight: 900; color: var(--primary); margin-bottom: 5px; letter-spacing: -0.5px;"><?php echo SITE_NAME_DYNAMIC; ?></h2>
                        <?php endif; ?>
                        <?php $tagline = get_setting('site_tagline', 'Digital News'); ?>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;"><?php echo htmlspecialchars($tagline); ?></div>
                        <p style="color: #64748b; font-size: 13px; line-height: 1.5; max-width: 320px; margin: 0;">Delivering accurate news, real stories, and comprehensive coverage from around the world. Your trusted digital news platform.</p>
                    </div>
                    <div class="social-icons" style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <?php if(get_setting('facebook_url')): ?>
                            <a href="<?php echo get_setting('facebook_url'); ?>" target="_blank" class="foot-social facebook" aria-label="Facebook"><i data-feather="facebook" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('twitter_url')): ?>
                            <a href="<?php echo get_setting('twitter_url'); ?>" target="_blank" class="foot-social twitter" aria-label="Twitter"><i data-feather="twitter" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('instagram_url')): ?>
                            <a href="<?php echo get_setting('instagram_url'); ?>" target="_blank" class="foot-social instagram" aria-label="Instagram"><i data-feather="instagram" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('youtube_url')): ?>
                            <a href="<?php echo get_setting('youtube_url'); ?>" target="_blank" class="foot-social youtube" aria-label="YouTube"><i data-feather="youtube" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('whatsapp_channel')): ?>
                            <a href="<?php echo get_setting('whatsapp_channel'); ?>" target="_blank" class="foot-social whatsapp" aria-label="WhatsApp"><i data-feather="message-circle" style="width: 18px;"></i></a>
                        <?php endif; ?>
                        <?php if(get_setting('footer_custom_link_url')): ?>
                            <a href="<?php echo htmlspecialchars(get_setting('footer_custom_link_url')); ?>" target="_blank" class="foot-social custom-link" aria-label="<?php echo htmlspecialchars(get_setting('footer_custom_link_title', 'Link')); ?>" title="<?php echo htmlspecialchars(get_setting('footer_custom_link_title', 'Link')); ?>"><i data-feather="external-link" style="width: 18px;"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col links-col">
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Categories</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <?php foreach($nav_categories as $cat): 
                            $cat_url = !empty($cat['custom_url']) ? $cat['custom_url'] : BASE_URL . 'category/' . $cat['slug'];
                            $cat_target = !empty($cat['custom_url']) ? 'target="_blank"' : '';
                        ?>
                            <li><a href="<?php echo $cat_url; ?>" <?php echo $cat_target; ?> class="footer-link"><?php echo $cat['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Column 3: Legal & Info -->
                <div class="footer-col links-col">
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Information</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                        <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
                        <li><a href="<?php echo BASE_URL; ?>magazine" class="footer-link"><i data-feather="book-open" style="width:13px; margin-right:6px;"></i> Magazine</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>about.php" class="footer-link">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>contact.php" class="footer-link">Contact</a></li>
                        <li><a href="<?php echo BASE_URL; ?>privacy-policy.php" class="footer-link">Privacy Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>terms.php" class="footer-link">Terms of Use</a></li>
                    </ul>
                </div>

                <!-- Column 4: Newsletter & Contact -->
                <div class="footer-col contact-col">
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Stay Updated</h4>
                    <form onsubmit="event.preventDefault(); alert('Subscribed successfully!');" style="display: flex; gap: 6px; margin-bottom: 20px;">
                        <input type="email" placeholder="Email address" required style="flex: 1; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; outline: none; background: #f8fafc;" class="footer-input">
                        <button type="submit" style="background: var(--primary); color: #fff; border: none; padding: 0 15px; border-radius: 6px; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: 0 2px 6px rgba(0,0,0,0.1);" class="footer-btn"><i data-feather="send" style="width: 14px;"></i></button>
                    </form>

                    <?php if (get_setting('hide_contact_details', 'no') !== 'yes'): ?>
                    <h4 style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px;">Reach Us</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                        <li style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px;"><i data-feather="mail" style="width: 14px; color: var(--primary);"></i> <?php echo get_setting('contact_email', 'admin@example.com'); ?></li>
                        <li style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13px;"><i data-feather="phone" style="width: 14px; color: var(--primary);"></i> <?php echo get_setting('contact_phone', '+91 000 000 0000'); ?></li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Bottom Bar ── -->
            <div class="footer-bottom" style="border-top: 1px solid #e2e8f0; padding: 20px 0 10px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <p style="color: #64748b; font-size: 13px; margin: 0;">
                    © <?php echo date('Y'); ?> <strong style="color: #0f172a; font-weight: 800;"><?php echo SITE_NAME_DYNAMIC; ?></strong>. All rights reserved. 
                    <span style="margin: 0 8px; opacity: 0.5;">|</span> 
                    Planted by <a href="https://offerplant.com" target="_blank" style="color: var(--primary); font-weight: 800; text-decoration: none; letter-spacing: 0.5px; transition: 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">OfferPlant</a>
                </p>

                <div class="footer-bottom-links" style="display: flex; gap: 20px;">
                    <a href="<?php echo BASE_URL; ?>" class="footer-link">Home</a>
                    <a href="<?php echo BASE_URL; ?>privacy-policy.php" class="footer-link">Privacy</a>
                    <a href="<?php echo BASE_URL; ?>terms.php" class="footer-link">Terms</a>
                </div>
            </div>
        </div>
    </footer>
        </div><!-- .main-wrapper -->
    </div><!-- .app-container -->

    <style>
        /* ── Vector banner ───────────────────────────── */
        .footer-vector-wrap {
            position: relative;
            overflow: hidden;
            height: 130px;
            background: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(0,0,0,.06);
        }
        .theme-dark .footer-vector-wrap { border-bottom-color: rgba(255,255,255,.06); }

        /* Dot-grid overlay */
        .fv-dotgrid {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        .theme-light .fv-dotgrid { color: #94a3b8; }
        .theme-dark  .fv-dotgrid { color: #334155; }

        /* Floating icons */
        .fv-icons { position: absolute; inset: 0; pointer-events: none; }
        .fvi {
            position: absolute;
            opacity: 0;
            animation: fvFloat 9s ease-in-out infinite;
        }
        .theme-light .fvi { color: #64748b; }
        .theme-dark  .fvi { color: #475569; }

        /* varied sizes for visual depth */
        .fvi-1  { width:18px; height:18px; left:1.5%; top:18%; animation-delay:0s;    animation-duration:10s; }
        .fvi-2  { width:24px; height:24px; left:6%;   top:58%; animation-delay:1.2s;  animation-duration:11s; }
        .fvi-3  { width:16px; height:16px; left:11%;  top:22%; animation-delay:0.5s;  animation-duration:8s;  }
        .fvi-4  { width:20px; height:20px; left:16%;  top:68%; animation-delay:2.4s;  animation-duration:12s; }
        .fvi-5  { width:28px; height:28px; left:22%;  top:12%; animation-delay:1.7s;  animation-duration:9s;  }
        .fvi-6  { width:16px; height:16px; left:27%;  top:72%; animation-delay:0.2s;  animation-duration:10s; }
        .fvi-7  { width:22px; height:22px; left:33%;  top:20%; animation-delay:3.0s;  animation-duration:8s;  }
        .fvi-8  { width:18px; height:18px; left:38%;  top:76%; animation-delay:1.5s;  animation-duration:10s; }
        .fvi-9  { width:20px; height:20px; left:60%;  top:15%; animation-delay:2.9s;  animation-duration:11s; }
        .fvi-10 { width:16px; height:16px; left:65%;  top:70%; animation-delay:0.8s;  animation-duration:9s;  }
        .fvi-11 { width:24px; height:24px; left:71%;  top:20%; animation-delay:2.0s;  animation-duration:10s; }
        .fvi-12 { width:18px; height:18px; left:76%;  top:65%; animation-delay:1.3s;  animation-duration:8s;  }
        .fvi-13 { width:16px; height:16px; left:82%;  top:18%; animation-delay:3.4s;  animation-duration:11s; }
        .fvi-14 { width:22px; height:22px; left:86%;  top:62%; animation-delay:0.7s;  animation-duration:9s;  }
        .fvi-15 { width:18px; height:18px; left:91%;  top:22%; animation-delay:2.2s;  animation-duration:10s; }
        .fvi-16 { width:16px; height:16px; left:95%;  top:65%; animation-delay:1.0s;  animation-duration:8s;  }
        .fvi-17 { width:20px; height:20px; left:4%;   top:75%; animation-delay:4.0s;  animation-duration:12s; }
        .fvi-18 { width:14px; height:14px; left:14%;  top:45%; animation-delay:2.7s;  animation-duration:9s;  }
        .fvi-19 { width:16px; height:16px; left:84%;  top:40%; animation-delay:1.9s;  animation-duration:11s; }
        .fvi-20 { width:18px; height:18px; left:97%;  top:35%; animation-delay:3.6s;  animation-duration:10s; }

        @keyframes fvFloat {
            0%   { opacity:0;    transform: translateY(7px)  rotate(-6deg); }
            12%  { opacity:.45;  transform: translateY(0)    rotate(0deg); }
            88%  { opacity:.45;  transform: translateY(-5px)  rotate(4deg); }
            100% { opacity:0;    transform: translateY(-9px)  rotate(7deg); }
        }

        /* Center SVG full width */
        .fv-center {
            width: min(900px, 98%);
            height: 110px;
            position: relative;
            z-index: 1;
        }
        .theme-light .fv-center { color: #0f172a; }
        .theme-dark  .fv-center { color: #e2e8f0; }

        /* ── Footer grid ─────────────────────────────── */
        .footer-link {
            color: #64748b;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        .footer-link:hover { color: var(--primary); transform: translateX(3px); }
        .theme-dark .footer-link { color: #94a3b8; }
        .theme-dark .footer-link:hover { color: #fff; }

        .foot-social {
            width: 32px; height: 32px; border-radius: 8px;
            background: #f1f5f9; display: flex; align-items: center;
            justify-content: center; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none;
            color: #475569;
        }
        .foot-social:hover { transform: translateY(-5px); color: #fff !important; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .foot-social.facebook:hover { background: #1877f2; }
        .foot-social.twitter:hover { background: #0f1419; }
        .foot-social.instagram:hover { background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); }
        .foot-social.youtube:hover { background: #ff0000; }
        .foot-social.whatsapp:hover { background: #25d366; }

        .theme-dark .foot-social { background: rgba(255,255,255,.05); color: #cbd5e1; }
        .theme-dark h4 { color: #f8fafc !important; }
        .theme-dark p, .theme-dark li { color: #94a3b8 !important; }
        .theme-dark .footer-bottom p strong { color: #f8fafc !important; }

        .footer-input:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(var(--primary-rgb, 37, 99, 235), 0.2); }
        .footer-btn:hover { background: #0f172a !important; transform: translateY(-2px); }

        /* ── Content protection ──────────────────────── */
        body { -webkit-user-select: none; -moz-user-select: none; user-select: none; }
        @media print { body { display: none !important; } }

        @media (max-width: 1024px) { 
            div[style*="grid-template-columns: 2fr 1fr 1fr 1.5fr"] { grid-template-columns: 1fr 1fr !important; } 
        }
        @media (max-width: 640px)  {
            div[style*="grid-template-columns: 2fr 1fr 1fr 1.5fr"] { grid-template-columns: 1fr !important; }
            .footer-vector-wrap { height: 70px; }
            .fv-center { height: 70px; }
            .footer-bottom { flex-direction: column; text-align: center; }
            .footer-bottom-links { justify-content: center; flex-wrap: wrap; }
        }

        /* ── Compact Footer (Theme 2) ───────────────────────────── */
        .compact-footer .footer-content-wrapper { padding: 40px 20px 20px 20px !important; }
        .compact-footer .footer-grid { margin-bottom: 25px !important; gap: 40px 30px !important; grid-template-columns: 1.2fr 1.5fr 1fr 1.2fr !important; }
        .compact-footer .footer-col.brand-col p { display: block; font-size: 12px; margin-bottom: 15px; }
        .compact-footer .footer-col.brand-col .social-icons { margin-top: 15px; }
        .compact-footer .footer-col h4 { margin-bottom: 15px !important; font-size: 13px !important; }
        .compact-footer .footer-link { font-size: 13px !important; }
        .compact-footer .links-col ul { gap: 12px 20px !important; }
        .compact-footer .foot-social { width: 32px; height: 32px; }
        .compact-footer .foot-social svg { width: 16px !important; }
        .compact-footer .footer-bottom { padding: 20px 0 10px 0; border-top: 1px solid rgba(255,255,255,0.05); }
        .theme-light.compact-footer .footer-bottom { border-top: 1px solid #e2e8f0; }
        @media (max-width: 1024px) {
            .compact-footer .footer-grid { grid-template-columns: 1fr 1fr !important; }
        }
        @media (max-width: 640px) {
            .compact-footer .footer-grid { grid-template-columns: 1fr !important; }
        }
    </style>
    <script>
        feather.replace();
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.onkeydown = function(e) {
            if (e.ctrlKey && (e.keyCode===67||e.keyCode===86||e.keyCode===85||e.keyCode===80)) return false;
            if (e.keyCode===123) return false;
        };
    </script>
    
    <?php if (get_setting('whatsapp_floating_btn', 'no') == 'yes' && get_setting('whatsapp_number')): ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', get_setting('whatsapp_number')); ?>?text=Hello" target="_blank" class="floating-wa" style="position: fixed; bottom: 85px; right: 20px; background: #25D366; color: white; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 999; transition: transform 0.3s; padding: 0; margin: 0;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" viewBox="0 0 16 16">
          <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
        </svg>
    </a>
    <?php endif; ?>

    <?php // include 'includes/feedback_drawer.php'; // Apni Baat floating button disabled ?>

    <?php 
    // Automated WP Sync Trigger
    $last_wp_cron = get_setting('last_wp_cron_run');
    if (!$last_wp_cron || (time() - strtotime($last_wp_cron)) > 1800): ?>
    <script>
        // Asynchronously trigger WP sync in the background
        fetch('<?php echo BASE_URL; ?>api/cron_wp_sync.php').catch(()=>{});
    </script>
    <?php endif; ?>

    <?php if (get_setting('homepage_theme', 'theme1') === 'theme2'): ?>
    <!-- Theme 2: Mobile App-like Bottom Navigation Bar -->
    <nav class="t2-mobile-bottom-nav" id="t2BottomNav">
        <a href="<?php echo BASE_URL; ?>" class="t2-bottom-nav-item <?php echo ($current_file == 'index.php') ? 'active' : ''; ?>" id="t2-bn-home">
            <i data-feather="home"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo BASE_URL; ?>search.php" class="t2-bottom-nav-item <?php echo ($current_file == 'search.php') ? 'active' : ''; ?>" id="t2-bn-search">
            <i data-feather="search"></i>
            <span>Search</span>
        </a>
        <?php if (get_setting('ebook_magazine_enabled', 'yes') == 'yes'): ?>
        <a href="<?php echo BASE_URL; ?>magazine" class="t2-bottom-nav-item <?php echo ($current_file == 'magazine.php' || $current_file == 'magazine_view.php') ? 'active' : ''; ?>" id="t2-bn-magazine">
            <i data-feather="book-open"></i>
            <span>Magazine</span>
        </a>
        <?php endif; ?>
        <?php if ($latest_poll_header): ?>
        <a href="<?php echo $poll_header_url; ?>" class="t2-bottom-nav-item <?php echo ($current_file == 'poll.php') ? 'active' : ''; ?>" id="t2-bn-poll">
            <i data-feather="pie-chart"></i>
            <span>Poll</span>
        </a>
        <?php endif; ?>
        <button class="t2-bottom-nav-item" onclick="toggleMobileMenu()" id="t2-bn-menu" style="background:none;border:none;cursor:pointer;">
            <i data-feather="menu"></i>
            <span>Menu</span>
        </button>
    </nav>
    <style>
        /* ── Theme 2 Mobile Bottom Navigation ───────────────────────── */
        .t2-mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
            z-index: 1500;
            align-items: stretch;
            justify-content: space-around;
            padding: 0;
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
        @media (max-width: 1024px) {
            .t2-mobile-bottom-nav { display: flex; }
        }
        .t2-bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            flex: 1;
            padding: 8px 5px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
            transition: all 0.2s ease;
            position: relative;
            font-family: 'Mukta', 'Outfit', sans-serif;
        }
        .t2-bottom-nav-item svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 2;
        }
        .t2-bottom-nav-item.active {
            color: var(--primary);
        }
        .t2-bottom-nav-item.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 3px;
            background: var(--primary);
            border-radius: 0 0 3px 3px;
        }
        .t2-bottom-nav-item:hover {
            color: var(--primary);
            background: rgba(0,0,0,0.02);
        }
    </style>
    <?php endif; ?>

</body>
</html>
