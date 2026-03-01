<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$site_name = get_setting('site_name', 'NewsCast');
$theme_color = get_setting('theme_color', '#6366f1');
$site_tagline = get_setting('site_tagline', 'Truth • Speed • Trust');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NewsCast CMS | अपना डिजिटल न्यूज़ चैनल शुरू करें</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        :root {
            --primary: <?php echo $theme_color; ?>;
            --primary-rgb: <?php
list($r, $g, $b) = sscanf($theme_color, "#%02x%02x%02x");
echo "$r, $g, $b";
?>;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Outfit', sans-serif; 
            background: var(--bg); 
            color: var(--text-main); 
            line-height: 1.6;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        .ambient-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: radial-gradient(circle at 10% 20%, rgba(var(--primary-rgb), 0.15) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(var(--primary-rgb), 0.1) 0%, transparent 40%);
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 25px; }

        header { 
            padding: 25px 0; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
        }
        .logo { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 22px; color: #fff; text-decoration: none; }
        .logo-icon { width: 40px; height: 40px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(var(--primary-rgb), 0.3); }

        .btn-demo { 
            background: var(--primary); color: #fff; padding: 12px 28px; border-radius: 30px; 
            font-weight: 700; text-decoration: none; box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.2); transition: .3s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-demo:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(var(--primary-rgb), 0.4); }

        .hero { padding: 80px 0 60px; text-align: center; }
        .badge { display: inline-block; padding: 6px 16px; background: rgba(var(--primary-rgb), 0.15); border: 1px solid rgba(var(--primary-rgb), 0.3); border-radius: 30px; color: var(--primary); font-size: 13px; font-weight: 800; margin-bottom: 25px; letter-spacing: 1px; }
        .hero h1 { font-size: 56px; font-weight: 900; line-height: 1.2; margin-bottom: 25px; }
        .hero h1 span { background: linear-gradient(135deg, #fff 30%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { max-width: 700px; margin: 0 auto 40px; font-size: 20px; color: var(--text-muted); }
        
        .mockup-wrap { position: relative; margin-top: 50px; }
        .main-mockup { width: 100%; max-width: 900px; height: 450px; margin: 0 auto; border-radius: 24px; border: 1px solid var(--border); background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); box-shadow: 0 40px 80px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; position: relative; z-index: 5; }

        .features { padding: 80px 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .feat-card { padding: 40px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 24px; transition: .4s; }
        .feat-card:hover { transform: translateY(-10px); border-color: var(--primary); background: rgba(var(--primary-rgb), 0.05); }
        .feat-card i { width: 40px; height: 40px; color: var(--primary); margin-bottom: 20px; }
        .feat-card h3 { font-size: 20px; font-weight: 700; margin-bottom: 12px; }

        /* Pricing Table */
        .pricing-section { padding: 100px 0; background: rgba(255,255,255,0.02); border-radius: 50px; margin: 50px 0; text-align: center; }
        .pricing-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 60px; }
        .price-card { background: var(--card-bg); border: 1px solid var(--border); padding: 50px 30px; border-radius: 30px; transition: .4s; position: relative; }
        .price-card.featured { border: 2.5px solid var(--primary); transform: scale(1.05); z-index: 10; background: rgba(var(--primary-rgb), 0.05); }
        .price-card h4 { font-size: 20px; font-weight: 800; margin-bottom: 15px; color: var(--text-muted); }
        .price-card .cost { font-size: 48px; font-weight: 900; color: #fff; margin-bottom: 10px; }
        .price-card .old-cost { text-decoration: line-through; color: #ef4444; font-size: 18px; font-weight: 700; }
        .price-card ul { list-style: none; margin: 30px 0; text-align: left; }
        .price-card li { margin-bottom: 15px; font-size: 15px; display: flex; align-items: center; gap: 10px; }
        .price-card li i { width: 16px; color: #10b981; }
        .price-card li.no { color: #64748b; }
        .price-card li.no i { color: #ef4444; }
        .tag-popular { position: absolute; top: -15px; left: 50%; transform: translateX(-50%); background: var(--primary); color: #fff; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.3); }

        footer { padding: 60px 0; border-top: 1px solid var(--border); text-align: center; }

        @media (max-width: 992px) {
            .hero h1 { font-size: 42px; }
            .features, .pricing-grid { grid-template-columns: 1fr; }
            .price-card.featured { transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="ambient-bg"></div>

<div class="container">
    <header>
        <a href="#" class="logo">
            <div class="logo-icon"><i data-feather="zap" style="fill: #fff;"></i></div>
            NEWSCAST
        </a>
        <div style="display: flex; gap: 15px;">
            <a href="https://wa.me/919431426600" class="btn-demo" style="background: #25d366;"><i data-feather="message-circle"></i> व्हाट्सएप</a>
            <a href="admin/login.php" class="btn-demo" style="background: rgba(255,255,255,0.05); border: 1px solid var(--border);">एडमिन</a>
        </div>
    </header>

    <section class="hero">
        <?php $expiry_date = date('d M Y', strtotime('+7 days')); ?>
        <div class="badge">🔥 खास धमाका ऑफर - सिर्फ <?php echo $expiry_date; ?> तक!</div>
        <h1>अपना प्रोफेशनल डिजिटल न्यूज़ पोर्टल <span>आज ही शुरू करें!</span></h1>
        <p>बिना किसी कोडिंग के अपना न्यूज़ चैनल चलाएं। रिपोर्टर मैनेजमेंट, लाइव ब्रॉडकास्ट, और स्मार्ट डिज़ाइन - सब कुछ एक जगह।</p>
        
        <div style="display: flex; flex-direction: column; align-items: center; gap: 15px;">
            <div style="background: rgba(var(--primary-rgb), 0.1); border: 1px solid var(--primary); padding: 25px 50px; border-radius: 40px;">
                <span style="font-size: 18px; opacity: 0.6; text-decoration: line-through; margin-right: 15px;">MRP: ₹10,000</span>
                <span style="font-size: 38px; font-weight: 900; color: #fff;">सिर्फ ₹6,666/-</span>
            </div>
            <p style="font-weight: 700; color: var(--primary);">🚀 सिर्फ एक दिन में सेटअप और ट्रेनिंग!</p>
        </div>

        <div class="mockup-wrap">
            <div class="main-mockup">
                <div style="text-align: center;">
                    <i data-feather="layout" style="width: 80px; height: 80px; color: var(--primary); opacity: 0.4; margin-bottom: 20px;"></i>
                    <h2 style="font-size: 24px; font-weight: 800; color: #fff;">न्यूज़ वेबसाइट का आधुनिक चेहरा</h2>
                    <p style="color: var(--text-muted);">बड़ी स्क्रीन और मोबाइल दोनों के लिए बेहतरीन डिज़ाइन</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Simple Benefits Grid -->
    <section id="features" class="features">
        <div class="feat-card">
            <i data-feather="smartphone"></i>
            <h3>चलाने में एकदम आसान</h3>
            <p>फेसबुक यूज करने जैसा आसान! आप खुद अपनी मोबाइल से न्यूज़ डाल सकते हैं और फोटो बदल सकते हैं।</p>
        </div>
        <div class="feat-card">
            <i data-feather="credit-card"></i>
            <h3>रिपोर्टर आईडी कार्ड</h3>
            <p>अपने रिपोर्टर्स को असली प्रेस आईडी कार्ड दें। कार्ड पर QR कोड होगा जिससे उनकी तुरंत पहचान होगी।</p>
        </div>
        <div class="feat-card">
            <i data-feather="play"></i>
            <h3>लाइव न्यूज़ वीडियो</h3>
            <p>अपने यूट्यूब लाइव को सीधे अपनी वेबसाइट पर दिखाएं। "LIVE" बटन अपने आप आ जाएगा।</p>
        </div>
        <div class="feat-card">
            <i data-feather="book-open"></i>
            <h3>डिजिटल मैगज़ीन और E-Paper</h3>
            <p>अपने पाठकों को डिजिटल अखबार (E-Paper) और मैगज़ीन पढ़ने की सुविधा दें। लोग इसे मोबाइल पर आराम से पढ़ पाएंगे।</p>
        </div>
        <div class="feat-card">
            <i data-feather="volume-2"></i>
            <h3>खबरें सुनने की सुविधा (AI)</h3>
            <p>आपकी वेबसाइट खुद न्यूज़ बोलकर सुनाएगी। जो लोग पढ़ना नहीं चाहते, वो न्यूज़ को रेडियो की तरह सुन पाएंगे।</p>
        </div>
        <div class="feat-card">
            <i data-feather="share-2"></i>
            <h3>स्मार्ट वॉट्सऐप शेयरिंग</h3>
            <p>जब भी आप न्यूज़ शेयर करेंगे, सुंदर फोटो और हेडिंग के साथ जाएगी। इससे आपकी खबरें ज्यादा वायरल होंगी।</p>
        </div>
        <div class="feat-card">
            <i data-feather="search"></i>
            <h3>गूगल (SEO) में सबसे आगे</h3>
            <p>आपकी खबरें गूगल सर्च में सबसे ऊपर आएंगी। हमने इसमें खास तकनीक लगायी है जो आपकी वेबसाइट को फेमस करेगी।</p>
        </div>
        <div class="feat-card">
            <i data-feather="shield"></i>
            <h3>फुली सुरक्षित और सुपर फ़ास्ट</h3>
            <p>कम नेटवर्क और पुराने फोन में भी आपकी वेबसाइट बहुत तेज़ खुलेगी। आपकी न्यूज़ कभी नहीं रुकेगी।</p>
        </div>
        <div class="feat-card">
            <i data-feather="bar-chart-2"></i>
            <h3>न्यूज़ व्यू काउंट</h3>
            <p>देखें कि आपकी खबर को कितने लोगों ने पढ़ा। रियल-टाइम में पता चलेगा कि कौन सी न्यूज़ हिट हो रही है।</p>
        </div>
    </section>
    </section>

    <!-- Comparative Study / Plans -->
    <section class="pricing-section">
        <div class="badge">तुलना करें और चुनें</div>
        <h2 style="font-size: 42px; font-weight: 900; margin-bottom: 50px;">बेहतरीन प्लान्स आपके लिए</h2>
        
        <div class="pricing-grid">
            <!-- Starter -->
            <div class="price-card">
                <h4>STARTER</h4>
                <div class="cost">₹3,999</div>
                <p style="font-size: 12px; color: var(--text-muted);">सिर्फ वेबसाइट की शुरुआत के लिए</p>
                <ul>
                    <li><i data-feather="check"></i> प्रोफेशनल न्यूज़ वेबसाइट</li>
                    <li><i data-feather="check"></i> न्यूज़ कैटेगरीज व टैग्स</li>
                    <li><i data-feather="check"></i> तेज़ लोडिंग स्पीड</li>
                    <li class="no"><i data-feather="x"></i> रिपोर्टर आईडी कार्ड सिस्टम</li>
                    <li class="no"><i data-feather="x"></i> लाइव वीडियो इंटीग्रेशन</li>
                    <li class="no"><i data-feather="x"></i> मोबाइल ऐप (Android)</li>
                </ul>
                <a href="https://wa.me/919431426600" class="btn-demo" style="width: 100%; justify-content: center; background: rgba(255,255,255,0.05);">स्टार्ट करें</a>
            </div>

            <!-- Professional -->
            <div class="price-card featured">
                <div class="tag-popular">सबसे लोकप्रिय</div>
                <h4>PROFESSIONAL</h4>
                <div class="old-cost">₹10,000</div>
                <div class="cost">₹6,666</div>
                <p style="font-size: 12px; color: var(--text-muted);">एक न्यूज़ चैनल का पूरा समाधान</p>
                <ul>
                    <li><i data-feather="check"></i> सब कुछ जो 'Starter' में है</li>
                    <li><i data-feather="check"></i> <strong>रिपोर्टर आईडी कार्ड (QR)</strong></li>
                    <li><i data-feather="check"></i> <strong>यूट्यूब लाइव स्ट्रीम</strong></li>
                    <li><i data-feather="check"></i> एडमिन कंट्रोल पैनल (Web)</li>
                    <li><i data-feather="check"></i> न्यूज़ बोलकर सुनाने का सिस्टम</li>
                    <li class="no"><i data-feather="x"></i> मोबाइल ऐप (Android)</li>
                </ul>
                <a href="https://wa.me/919431426600" class="btn-demo" style="width: 100%; justify-content: center;">अभी ऑफर लें</a>
            </div>

            <!-- Enterprise -->
            <div class="price-card">
                <h4>ENTERPRISE</h4>
                <div class="cost">₹14,999</div>
                <p style="font-size: 12px; color: var(--text-muted);">बड़े न्यूज़ नेटवर्क के लिए</p>
                <ul>
                    <li><i data-feather="check"></i> सब कुछ जो 'Professional' में है</li>
                    <li><i data-feather="check"></i> <strong>Native Android App (.apk)</strong></li>
                    <li><i data-feather="check"></i> कस्टम ब्रांडिंग और लोगो</li>
                    <li><i data-feather="check"></i> डिजिटल मैगज़ीन मॉड्यूल</li>
                    <li><i data-feather="check"></i> प्राइवेट डोमेन और ईमेल</li>
                    <li><i data-feather="check"></i> 24/7 वीआईपी सपोर्ट</li>
                </ul>
                <a href="https://wa.me/919431426600" class="btn-demo" style="width: 100%; justify-content: center; background: rgba(255,255,255,0.05);">संपर्क करें</a>
            </div>
        </div>
    </section>

    <!-- Why Us Section -->
    <section style="padding: 80px 0; text-align: center;">
        <h2 style="font-size: 32px; font-weight: 900; margin-bottom: 30px;">NewsCast क्यों है सबसे अलग?</h2>
        <div style="max-width: 800px; margin: 0 auto; color: var(--text-muted); font-size: 18px; line-height: 1.8;">
            हम सिर्फ वेबसाइट नहीं बनाते, हम तकनीक को आसान बनाते हैं। आज के ज़माने में जब आपके पास न्यूज़ पढ़ने का समय कम है, हमारी वेबसाइट <strong>बोलकर न्यूज़ सुनाती है</strong>। जब आपके रिपोर्टर्स फील्ड पर जाते हैं, तो उनके पास <strong>असली आईडी कार्ड</strong> होते हैं। 
            <br><br>
            यह सब कुछ सिर्फ ₹6,666 में! बाज़ार में इसके लिए आपको लाखों खर्च करने पड़ सकते हैं। हमारा मिशन है - हर छोटा न्यूज़ रिपोर्टर बने एक बड़ा न्यूज़ चैनल!
        </div>
    </section>

    <footer>
        <div class="logo" style="justify-content: center; margin-bottom: 20px;">
            <div class="logo-icon" style="width: 32px; height: 32px;"><i data-feather="zap" style="width: 14px; fill: #fff;"></i></div>
            NEWSCAST
        </div>
        <p style="color: var(--text-muted); font-weight: 600;"><?php echo $site_tagline; ?></p>
        <div style="margin-top: 20px; color: var(--text-muted); font-size: 13px;">
            &copy; <?php echo date('Y'); ?> Digital Seal & NewsCast. Call: 9431426600
        </div>
    </footer>
</div>

<script>
    feather.replace();
</script>

</body>
</html>
