<?php
$page_title = "Help & User Guide";
include 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-size: 32px; font-weight: 800; color: #0f172a;">Knowledge Center & Features</h2>
        <p style="color: #64748b; font-size: 16px;">Everything you need to know about your advanced NewsCast CMS features.</p>
    </div>

    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: start;">
        <!-- Navigation -->
        <div style="position: sticky; top: 120px; background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 8px;"><a href="#how-to-use" style="display: flex; align-items: center; gap: 12px; color: var(--primary); font-weight: 700; text-decoration: none; padding: 12px; background: color-mix(in srgb, var(--primary), white 90%); border-radius: 12px;"><i data-feather="terminal" style="width: 18px;"></i> How to Use</a></li>
                <li style="margin-bottom: 8px;"><a href="#accessibility" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="headphones" style="width: 18px;"></i> Accessibility (TTS)</a></li>
                <li style="margin-bottom: 8px;"><a href="#translation" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="globe" style="width: 18px;"></i> Multi-Language</a></li>
                <li style="margin-bottom: 8px;"><a href="#epapermag" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="file-text" style="width: 18px;"></i> E-Paper & Magazine</a></li>
                <li style="margin-bottom: 8px;"><a href="#user-engage" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="bookmark" style="width: 18px;"></i> User Engagement</a></li>
                <li style="margin-bottom: 8px;"><a href="#branding" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="palette" style="width: 18px;"></i> Theme & Branding</a></li>
                <li style="margin-bottom: 8px;"><a href="#ads" style="display: flex; align-items: center; gap: 12px; color: #475569; font-weight: 600; text-decoration: none; padding: 12px;"><i data-feather="trending-up" style="width: 18px;"></i> Ads Monetization</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div style="display: flex; flex-direction: column; gap: 35px;">
            
            <!-- How to Use Section -->
            <section id="how-to-use" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: var(--primary); color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="terminal" style="width: 20px;"></i></span>
                    How to Use (Quick Start)
                </h3>
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div style="display: flex; gap: 15px;">
                        <div style="background: #f1f5f9; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #475569; flex-shrink: 0;">1</div>
                        <div>
                            <h4 style="font-weight: 700; color: #1e293b; margin-bottom: 5px;">Managing Content</h4>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">Navigate to <span style="font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Articles > Add New</span> to create a story. Toggle <span style="font-weight: 700; color: #1e293b;">"Featured"</span> to make it the lead story on the homepage.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div style="background: #f1f5f9; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #475569; flex-shrink: 0;">2</div>
                        <div>
                            <h4 style="font-weight: 700; color: #1e293b; margin-bottom: 5px;">Configuring Advanced Tools</h4>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">Go to <span style="font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Site Settings > Appearance</span> to enable/disable TTS and Google Translate. Changes reflect instantly on article pages.</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px;">
                        <div style="background: #f1f5f9; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; color: #475569; flex-shrink: 0;">3</div>
                        <div>
                            <h4 style="font-weight: 700; color: #1e293b; margin-bottom: 5px;">E-Paper Publishing</h4>
                            <p style="color: #64748b; font-size: 14px; line-height: 1.6;">Click <span style="font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Digital Papers</span> in the sidebar. Upload your PDF and set the paper date. It will automatically show up in the public E-Paper archive.</p>
                        </div>
                    </div>
                </div>
            </section>
            <section id="accessibility" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: #f59e0b; color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="volume-2" style="width: 20px;"></i></span>
                    Voice Reader (Text-to-Speech)
                </h3>
                <p style="color: #475569; line-height: 1.8; margin-bottom: 15px;">Your portal features an advanced <span style="font-weight: 700; color: #1e293b;">Female Voice Reader</span> specifically optimized for Hindi news reporting.</p>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px;">
                    <li><strong>Female/Cold Tone:</strong> Uses a professional, secondary-neutral female accent to mimic news broadcasters.</li>
                    <li><strong>Hindi Optimization:</strong> Automatically detects Hindi text and uses `hi-IN` phonetics.</li>
                    <li><strong>Admin Control:</strong> Can be enabled/disabled via <span style="font-weight: 700; color: #1e293b; background: #fef3c7; padding: 2px 6px; border-radius: 4px;">Settings > Appearance</span>.</li>
                </ul>
            </section>

            <!-- Translation Section -->
            <section id="translation" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: #6366f1; color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="globe" style="width: 20px;"></i></span>
                    Multi-Language Translation
                </h3>
                <p style="color: #475569; line-height: 1.8;">Integrated with Google Neural Translation for real-time English ↔ Hindi switching.</p>
                <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-top: 15px; border: 1px dashed #cbd5e1;">
                    <strong style="color: #1e293b; display: block; margin-bottom: 5px;">How to Manage:</strong>
                    Go to <span style="font-weight: 700; color: #1e293b; background: #e0e7ff; padding: 2px 6px; border-radius: 4px;">Settings > Appearance</span> to toggle the "Google Translate" feature. When enabled, a sleek language switcher appears on all article pages.
                </div>
            </section>

            <!-- E-Paper Section -->
            <section id="epapermag" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: #ef4444; color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="book-open" style="width: 20px;"></i></span>
                    Digital E-Papers & Magazines
                </h3>
                <p style="color: #475569; line-height: 1.8;">Upload and manage PDF editions of your physical newspaper.</p>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px; margin-top: 15px;">
                    <li><strong>E-Paper Archive:</strong> Users can filter papers by specific dates.</li>
                    <li><strong>Magazine Center:</strong> High-performance PDF renderer for magazines with download counters.</li>
                    <li><strong>Theme Sync:</strong> All filter buttons and UI elements automatically match your primary portal color.</li>
                </ul>
            </section>

            <!-- User Engagement -->
            <section id="user-engage" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: #059669; color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="user-check" style="width: 20px;"></i></span>
                    User Engagement Tools
                </h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: #f0fdf4; padding: 20px; border-radius: 15px;">
                        <h4 style="font-weight: 700; color: #166534; display: flex; align-items: center; gap: 8px;"><i data-feather="bookmark" style="width: 16px;"></i> Bookmarks</h4>
                        <p style="font-size: 13px; color: #166534; margin-top: 5px;">Logged-in users can save articles to read later from their personal dashboard.</p>
                    </div>
                    <div style="background: #eff6ff; padding: 20px; border-radius: 15px;">
                        <h4 style="font-weight: 700; color: #1e40af; display: flex; align-items: center; gap: 8px;"><i data-feather="clock" style="width: 16px;"></i> Reading History</h4>
                        <p style="font-size: 13px; color: #1e40af; margin-top: 5px;">Tracks and displays recently viewed articles to improve user return rates.</p>
                    </div>
                </div>
            </section>

            <!-- Branding -->
            <section id="branding" style="background: white; padding: 35px; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <h3 style="font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <span style="background: #ec4899; color: white; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;"><i data-feather="palette" style="width: 20px;"></i></span>
                    Unified Theme & Branding
                </h3>
                <p style="color: #475569; line-height: 1.8;">Your admin panel and public portal are now perfectly synchronized.</p>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px; margin-top: 10px;">
                    <li><strong>Global Primary Color:</strong> Update your "Theme Color" in Settings, and the <span style="font-weight: 700; color: #1e293b;">Admin Dashboard</span> will change its primary accent to match!</li>
                    <li><strong>Placeholder System:</strong> Articles without high-quality images automatically generate branded placeholders with your portal name and URL.</li>
                </ul>
            </section>

        </div>
    </div>
</div>

<style>
    section { scroll-margin-top: 100px; margin-bottom: 20px; transition: transform 0.3s ease; }
    section:hover { transform: translateY(-3px); }
    ul li { margin-bottom: 10px; }
    .nav-links a { transition: 0.2s; }
    .nav-links a:hover { background: #f8fafc; color: var(--primary); }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Simple active state for help sidebar
        const links = document.querySelectorAll('aside a, .navigation a');
        links.forEach(link => {
            link.addEventListener('click', () => {
                links.forEach(l => {
                    l.style.background = 'transparent';
                    l.style.color = '#475569';
                    l.style.fontWeight = '600';
                });
                link.style.background = 'color-mix(in srgb, var(--primary), white 90%)';
                link.style.color = 'var(--primary)';
                link.style.fontWeight = '700';
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

