<?php
$_SERVER['HTTP_HOST'] = 'localhost'; // To trick config.php
include 'includes/config.php';

$posts = [
    [
        'title' => 'Revolutionize Your Newsroom with NewsCast Auto-Share',
        'content' => 'In the fast-paced world of digital journalism, speed is everything. NewsCast introduces a powerful Auto-Share feature that lets you push articles to Facebook and Instagram the moment you hit publish. No more manual copying, no more wasted time. Just instant reach and professional delivery.',
        'slug' => 'revolutionize-newsroom-newscast-auto-share'
    ],
    [
        'title' => 'Beat the Clock: Save Hours Every Week on Social Media',
        'content' => 'Journalists should focus on stories, not social media management. Our automated system handles the synchronization of your content across major platforms, saving you hours of repetitive work. Focus on the scoop, and let NewsCast handle the audience engagement.',
        'slug' => 'save-hours-social-media-automation'
    ],
    [
        'title' => 'The Power of Multi-Platform News Distribution',
        'content' => 'Your readers are on Facebook, Instagram, and beyond. NewsCast allows you to maintain a consistent presence across all connected nodes with a single click. Broadcast your message globally and ensure your headlines are seen everywhere simultaneously.',
        'slug' => 'power-multi-platform-news-distribution'
    ],
    [
        'title' => 'Professional Branding Made Easy for Digital Publishers',
        'content' => 'Consistency is key to a premium news brand. Our Social Share module ensures that every post sent to Facebook and Instagram is formatted professionally, carrying your brand identity and driving traffic directly back to your domain with clean, reliable links.',
        'slug' => 'professional-branding-digital-publishers'
    ],
    [
        'title' => 'Manual Dispatch: Total Control over Your Breaking News',
        'content' => 'While automation is powerful, sometimes you need surgical precision. Our "Manual Broadcast" center gives you the ability to select the exact content architecture and dispatch it to specific nodes—Facebook page or Instagram edge—whenever you need that extra push.',
        'slug' => 'manual-dispatch-breaking-news-control'
    ],
    [
        'title' => 'How Social Signals Boost Your News Site SEO',
        'content' => 'Engagement on social media isn\'t just about views—it\'s about authority. By automating your social shares with NewsCast, you create a steady stream of social signals that search engines love, helping your articles rank higher and reach a wider organic audience.',
        'slug' => 'social-signals-boost-news-seo'
    ],
    [
        'title' => 'Seamless Setup: From Newsroom to Social Feed in Minutes',
        'content' => 'Don\'t let technical jargon slow you down. Our new "Full Setup Guide" and "Configuration Grid" make it easy for any administrative user to link their Meta App, exchange permanent tokens, and start broadcasting. Professional news distribution has never been this simple.',
        'slug' => 'seamless-setup-social-news-feed'
    ],
    [
        'title' => 'The Science of the Social Share: Engagement Analytics',
        'content' => 'Every post shared through NewsCast is optimized for engagement. By leveraging the Meta Graph API, we ensure your articles are displayed with high-quality featured images and crisp meta-descriptions, capturing the reader\'s attention and maximizing click-through rates.',
        'slug' => 'science-social-share-engagement'
    ],
    [
        'title' => 'Digital Seal: Empowering Independent Media Everywhere',
        'content' => 'NewsCast is built by Digital Seal to provide enterprise-grade tools to independent newsrooms. Our Social Auto-Share system is just one part of a comprehensive ecosystem designed to make professional media publishing accessible and efficient for everyone.',
        'slug' => 'digital-seal-empowering-independent-media'
    ],
    [
        'title' => 'Stay Connected: Real-time System Diagnostics for Your Feed',
        'content' => 'Never worry about a broken link again. With our built-in connection badges and diagnostic tools, you can instantly verify if your Facebook and Instagram nodes are active. Reliability is at the heart of NewsCast, ensuring your news always reaches your followers.',
        'slug' => 'stay-connected-real-time-diagnostics'
    ]
];

// Fetch valid IDs from database
$u = $pdo->query('SELECT id FROM users LIMIT 1')->fetch();
$c = $pdo->query('SELECT id FROM categories LIMIT 1')->fetch();

$user_id = $u['id'] ?? 1;
$category_id = $c['id'] ?? 1;

foreach ($posts as $p) {
    try {
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, user_id, category_id, status, published_at) VALUES (?, ?, ?, ?, ?, 'published', NOW())");
        $stmt->execute([$p['title'], $p['slug'], $p['content'], $user_id, $category_id]);
        echo "Inserted: " . $p['title'] . "\n";
    }
    catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
