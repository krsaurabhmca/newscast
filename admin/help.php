<?php
$page_title = "Help & User Guide";
include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-size: 28px; font-weight: 800; color: #0f172a;">Welcome to the Help Center</h2>
        <p style="color: #64748b; font-size: 16px;">Step-by-step guide to managing your professional news portal.</p>
    </div>

    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px; align-items: start;">
        <!-- Navigation -->
        <div style="position: sticky; top: 100px; background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;"><a href="#getting-started" style="display: flex; align-items: center; gap: 10px; color: var(--primary); font-weight: 700; text-decoration: none; padding: 10px; background: #f5f3ff; border-radius: 8px;"><i data-feather="play" style="width: 16px;"></i> Getting Started</a></li>
                <li style="margin-bottom: 10px;"><a href="#articles" style="display: flex; align-items: center; gap: 10px; color: #475569; font-weight: 600; text-decoration: none; padding: 10px;"><i data-feather="file-text" style="width: 16px;"></i> Managing Articles</a></li>
                <li style="margin-bottom: 10px;"><a href="#live-stream" style="display: flex; align-items: center; gap: 10px; color: #475569; font-weight: 600; text-decoration: none; padding: 10px;"><i data-feather="tv" style="width: 16px;"></i> Live Broadcasting</a></li>
                <li style="margin-bottom: 10px;"><a href="#ads" style="display: flex; align-items: center; gap: 10px; color: #475569; font-weight: 600; text-decoration: none; padding: 10px;"><i data-feather="image" style="width: 16px;"></i> Ads & Monetization</a></li>
                <li style="margin-bottom: 10px;"><a href="#security" style="display: flex; align-items: center; gap: 10px; color: #475569; font-weight: 600; text-decoration: none; padding: 10px;"><i data-feather="lock" style="width: 16px;"></i> Profile & Security</a></li>
            </ul>
        </div>

        <!-- Content -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <section id="getting-started" class="stat-card" style="padding: 30px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="flag" style="color: var(--primary);"></i> Getting Started
                </h3>
                <p style="color: #475569; line-height: 1.7;">The **Dashboard** is your primary command center. Here you can see total views, ad performance, and latest activities. Use the **Admin Header** search to quickly find articles, or the **Quick Actions** sidebar to post new content instantly.</p>
            </section>

            <section id="articles" class="stat-card" style="padding: 30px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="edit-3" style="color: var(--primary);"></i> How to Publish Articles
                </h3>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px;">
                    <li>Go to **Articles > Add New**.</li>
                    <li>Enter a catchy title and structured content.</li>
                    <li>**Lead Story**: Toggle "Featured" to show it in the main hero section of the homepage.</li>
                    <li>**External Links**: Use this to track clicks if the story is hosted on a different subdomain or partner site.</li>
                    <li>**Video Support**: Paste a YouTube URL to automatically show a play icon and embed the video.</li>
                </ul>
            </section>

            <section id="live-stream" class="stat-card" style="padding: 30px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="video" style="color: #ff0000;"></i> Using Live Broadcasting
                </h3>
                <p style="color: #475569; line-height: 1.7;">When you have a live event:</p>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px;">
                    <li>Go to **Settings > Livestream**.</li>
                    <li>Paste your YouTube Live URL and give it a title.</li>
                    <li>Go back to the **Dashboard** and click **"Go Live"**.</li>
                </ul>
                <div style="background: #fff8f8; border-left: 4px solid #ff0000; padding: 15px; margin-top: 15px; border-radius: 4px;">
                    <strong style="color: #991b1b; font-size: 13px;">Pro Tip:</strong> When Live is enabled, it automatically hides your Lead Story and displays the broadcast with a transparent security overlay to block YouTube controls.
                </div>
            </section>

            <section id="ads" class="stat-card" style="padding: 30px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="dollar-sign" style="color: #059669;"></i> Managing Advertisements
                </h3>
                <p style="color: #475569; line-height: 1.7;">Monetize your portal by placing ads in designated slots:</p>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px;">
                    <li>**Header**: Top banner above the logo.</li>
                    <li>**Sidebar**: Best for square/portrait banners.</li>
                    <li>**Content Top/Bottom**: Shown inside every single article view.</li>
                </ul>
                <p style="color: #475569; padding-top: 10px;">You can track performance (CTR) directly from the **Dashboard** or the **Ads Management** page.</p>
            </section>

            <section id="security" class="stat-card" style="padding: 30px;">
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i data-feather="shield" style="color: #6366f1;"></i> Security & Profiles
                </h3>
                <ul style="color: #475569; line-height: 1.8; padding-left: 20px;">
                    <li>**Account Profile**: Update your journalist name, email, and upload a professional profile photo.</li>
                    <li>**Security**: Change your password regularly. The portal uses secure hashing for your safety.</li>
                    <li>**Roles**: Administrators can add more users and assign them as "Editors" who have restricted access.</li>
                </ul>
            </section>

        </div>
    </div>
</div>

<style>
    section { scroll-margin-top: 100px; }
    ul li { margin-bottom: 8px; }
    h3 i { width: 22px; height: 22px; }
</style>

<?php include 'includes/footer.php'; ?>
