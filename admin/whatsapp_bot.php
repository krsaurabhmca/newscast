<?php
// admin/whatsapp_bot.php
$page_title = "WhatsApp Chatbot Integration";
require_once 'includes/header.php';

// Detect the endpoint URL
$endpoint_url = BASE_URL . "api/v1/whatsapp_bot.php";
?>

<div class="container-fluid" style="padding: 0 15px;">
    <!-- Top Stats/Overview Card -->
    <div style="background: white; border-radius: 16px; padding: 25px; margin-bottom: 30px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
        <div style="background: rgba(37,211,102,0.1); width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #25d366; flex-shrink: 0;">
            <i data-feather="message-circle" style="width: 32px; height: 32px;"></i>
        </div>
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 5px 0;">WhatsApp News Chatbot</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0; line-height: 1.5;">Automate news delivery directly to your subscribers' WhatsApp. Let users request today's top stories, last 3 updates, category-wise listings, summaries, and search by keywords using a simulation of a smart chatbot.</p>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; margin-bottom: 50px;" class="wa-bot-layout">
        
        <!-- Left: Integration Guide -->
        <div style="display: flex; flex-direction: column; gap: 30px;">
            
            <!-- Webhook URL Card -->
            <div style="background: white; border-radius: 16px; border: 1px solid var(--border); padding: 25px; box-shadow: var(--shadow);">
                <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="link" style="color: var(--primary); width: 20px;"></i> Webhook Endpoint URL
                </h3>
                <p style="color: #64748b; font-size: 14px; line-height: 1.5; margin-bottom: 15px;">Use this URL as your webhook in Twilio, DoubleTick, Gupshup, or any custom WhatsApp Business gateway to connect the chatbot.</p>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                    <code id="endpointUrl" style="font-family: monospace; font-size: 13.5px; color: #0f172a; font-weight: 600; word-break: break-all;"><?php echo htmlspecialchars($endpoint_url); ?></code>
                    <button onclick="copyEndpoint()" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.2s; white-space: nowrap; display: flex; align-items: center; gap: 6px;" onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i data-feather="copy" style="width: 14px; height: 14px;"></i> Copy URL
                    </button>
                </div>
            </div>

            <!-- Command Guide Card -->
            <div style="background: white; border-radius: 16px; border: 1px solid var(--border); padding: 25px; box-shadow: var(--shadow);">
                <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="command" style="color: var(--primary); width: 20px;"></i> Supported Chatbot Keywords
                </h3>
                
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; color: #475569; font-weight: 700;">
                                <th style="padding: 12px 8px;">Trigger Keyword</th>
                                <th style="padding: 12px 8px;">Chatbot Action</th>
                                <th style="padding: 12px 8px;">Example Output</th>
                            </tr>
                        </thead>
                        <tbody style="color: #475569;">
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">HELLO / MENU / START</span></td>
                                <td style="padding: 15px 8px;">Sends the greeting guide message and lists available keywords.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">Welcome message & command list...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">TODAY</span></td>
                                <td style="padding: 15px 8px;">Returns today's top news stories with direct links.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">1. Headline 1 (Link) ...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">LATEST / LAST 3</span></td>
                                <td style="padding: 15px 8px;">Returns the last 3 published news stories with links.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">1. Headline 1 (Link) ...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">DATE YYYY-MM-DD</span></td>
                                <td style="padding: 15px 8px;">Fetches top news published on that specific date.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">News for July 09, 2026...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">SUMMARY</span></td>
                                <td style="padding: 15px 8px;">Fetches the top 10 articles along with brief summaries (excerpts).</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">1. Headline (Summary: Text...) ...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">CATEGORIES</span></td>
                                <td style="padding: 15px 8px;">Lists all active news categories on the portal.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">Politics, Sports, Business...</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">CAT [Category]</span></td>
                                <td style="padding: 15px 8px;">Returns the latest 5 news stories in that specific category.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">Top news in Politics...</td>
                            </tr>
                            <tr>
                                <td style="padding: 15px 8px;"><span style="background: #e2e8f0; padding: 2px 8px; border-radius: 6px; font-family: monospace; font-weight: 700;">[Any other text]</span></td>
                                <td style="padding: 15px 8px;">Automatically searches all news titles matching the text query.</td>
                                <td style="padding: 15px 8px; color: #94a3b8; font-style: italic;">Search results for "Keyword"...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- JustReply App Integration Card -->
            <div style="background: white; border-radius: 16px; border: 1px solid var(--border); padding: 25px; box-shadow: var(--shadow);">
                <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="smartphone" style="color: #25d366; width: 20px;"></i> Auto-respond using JustReply App (Android)
                </h3>
                
                <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 15px;">
                    You can easily convert your standard WhatsApp Business or Personal account into a News Chatbot using the <strong>JustReply</strong> auto-responder application on Android.
                </p>

                <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-start; flex-wrap: wrap;" class="jr-flex-container">
                    <div style="flex: 1; min-width: 250px;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 10px 0;">Setup Steps:</h4>
                        <ol style="font-size: 13.5px; line-height: 1.6; color: #475569; padding-left: 18px; margin: 0;">
                            <li style="margin-bottom: 8px;"><strong>Download the App:</strong> Install <a href="https://play.google.com/store/apps/details?id=com.offerplant.justreply" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: underline;">JustReply from the Google Play Store</a>.</li>
                            <li style="margin-bottom: 8px;"><strong>Select Global API:</strong> Open the app, and from the reply actions, select <strong>"Global API"</strong>.</li>
                            <li style="margin-bottom: 8px;"><strong>Configure Webhook Link:</strong> In the configuration settings, paste the Webhook URL:
                                <br><code style="font-family: monospace; font-size: 11.5px; background: #f1f5f9; padding: 2px 5px; border-radius: 4px; display: inline-block; margin-top: 4px; word-break: break-all;"><?php echo htmlspecialchars($endpoint_url); ?></code>
                            </li>
                            <li style="margin-bottom: 8px;"><strong>Enable:</strong> Turn on the auto-responder toggle to activate. Your phone will now respond automatically to WhatsApp messages using your News Portal API!</li>
                        </ol>
                    </div>
                    <?php if (file_exists('../assets/images/news.jpeg')): ?>
                    <div style="flex-shrink: 0; width: 140px; text-align: center;" class="jr-img-container">
                        <a href="../assets/images/news.jpeg" target="_blank" title="View configuration screenshot">
                            <img src="../assets/images/news.jpeg" alt="JustReply Config Guide" style="width: 100%; height: auto; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        </a>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 6px; font-weight: 600;">Config Screenshot</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Right: Smartphone Chatbot Simulator -->
        <div>
            <div style="position: sticky; top: 100px; display: flex; flex-direction: column; align-items: center;">
                <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 20px; align-self: flex-start; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="play-circle" style="color: #25d366; width: 20px;"></i> Live Chatbot Simulator
                </h3>
                
                <!-- Quick Trigger Pills -->
                <div style="width: 100%; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-bottom: 20px;">
                    <button onclick="triggerPill('hello')" class="pill-btn">Hello</button>
                    <button onclick="triggerPill('today')" class="pill-btn">Today</button>
                    <button onclick="triggerPill('latest')" class="pill-btn">Latest 3</button>
                    <button onclick="triggerPill('summary')" class="pill-btn">Summary</button>
                    <button onclick="triggerPill('categories')" class="pill-btn">Categories</button>
                </div>

                <!-- CSS Smart Phone Mockup -->
                <div class="phone-frame">
                    <div class="phone-notch"></div>
                    <div class="phone-speaker"></div>
                    
                    <!-- Screen Container -->
                    <div class="phone-screen">
                        
                        <!-- WhatsApp Header -->
                        <div class="wa-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i data-feather="chevron-left" style="width: 20px; height: 20px; color: white;"></i>
                                <div class="wa-avatar">
                                    <i data-feather="user" style="width: 16px; height: 16px; color: #7f8c8d;"></i>
                                </div>
                                <div>
                                    <div class="wa-chat-name"><?php echo htmlspecialchars(get_setting('site_name', 'NewsCast')); ?> Bot</div>
                                    <div class="wa-status">online</div>
                                </div>
                            </div>
                            <div style="display: flex; gap: 12px;">
                                <i data-feather="video" style="width: 18px; color: white;"></i>
                                <i data-feather="phone" style="width: 18px; color: white;"></i>
                                <i data-feather="more-vertical" style="width: 18px; color: white;"></i>
                            </div>
                        </div>
                        
                        <!-- WhatsApp Chat Body -->
                        <div class="wa-chat-body" id="chatBody">
                            
                            <!-- Initial Bot Message -->
                            <div class="wa-msg wa-received">
                                <div class="wa-bubble">
                                    👋 *Welcome to <?php echo htmlspecialchars(get_setting('site_name', 'NewsCast')); ?> Bot!* <br><br>
                                    ताज़ा ख़बरें पाने के लिए ये कीवर्ड्स टाइप करें:<br><br>
                                    📰 *TODAY* - आज की मुख्य ख़बरें<br>
                                    🔥 *LATEST* - आख़िरी 3 ख़बरें<br>
                                    📋 *SUMMARY* - टॉप 10 ख़बरों का सारांश<br>
                                    🗂️ *CATEGORIES* - सभी कैटेगरी लिस्ट<br>
                                    📂 *CAT [कैटेगरी]* - कैटेगरी की ख़बरें (उदा. *CAT Politics*)<br>
                                    🔍 *SEARCH [शब्द]* - ख़बरें खोजें (उदा. *SEARCH चुनाव*)
                                    <div class="wa-time"><?php echo date('h:i A'); ?></div>
                                </div>
                            </div>

                        </div>
                        
                        <!-- WhatsApp Message Input Footer -->
                        <form id="chatForm" onsubmit="sendChatMessage(event)" class="wa-input-area">
                            <div class="wa-input-container">
                                <i data-feather="smile" style="color: #8596a0; cursor: pointer;"></i>
                                <input type="text" id="userInput" placeholder="Type a keyword..." autocomplete="off">
                                <i data-feather="paperclip" style="color: #8596a0; cursor: pointer; margin-right: 5px;"></i>
                                <i data-feather="camera" style="color: #8596a0; cursor: pointer;"></i>
                            </div>
                            <button type="submit" class="wa-send-btn">
                                <i data-feather="send" style="width: 16px; height: 16px; color: white;"></i>
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Styles Specific to WhatsApp Simulation -->
<style>
    /* Two column scaling */
    @media (max-width: 1024px) {
        .wa-bot-layout {
            grid-template-columns: 1fr !important;
        }
    }

    /* Pill styling */
    .pill-btn {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13.5px;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .pill-btn:hover {
        background: #25d366;
        color: white;
        border-color: #25d366;
        box-shadow: 0 4px 10px rgba(37,211,102,0.25);
    }

    /* Phone casing styles */
    .phone-frame {
        width: 330px;
        height: 600px;
        background: #111;
        border-radius: 36px;
        padding: 10px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15), inset 0 0 3px 2px rgba(255,255,255,0.2);
        position: relative;
        border: 4px solid #2c3e50;
    }
    .phone-notch {
        width: 120px;
        height: 18px;
        background: #111;
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        z-index: 10;
    }
    .phone-speaker {
        width: 40px;
        height: 4px;
        background: #555;
        position: absolute;
        top: 6px;
        left: 50%;
        transform: translateX(-50%);
        border-radius: 2px;
        z-index: 10;
    }

    /* Screen layout */
    .phone-screen {
        width: 100%;
        height: 100%;
        background: #efeae2;
        border-radius: 28px;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    /* WhatsApp header style */
    .wa-header {
        background: #008069;
        padding: 30px 10px 10px 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        z-index: 5;
    }
    .wa-avatar {
        width: 32px;
        height: 32px;
        background: #e1e8ed;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .wa-chat-name {
        color: white;
        font-weight: 700;
        font-size: 13.5px;
        line-height: 1.2;
    }
    .wa-status {
        color: rgba(255,255,255,0.85);
        font-size: 10px;
    }

    /* Chat body doodle bg */
    .wa-chat-body {
        flex: 1;
        padding: 12px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background-color: #efeae2;
        background-image: radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 0), radial-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 0);
        background-size: 24px 24px;
        background-position: 0 0, 12px 12px;
    }

    /* Chat bubbles */
    .wa-msg {
        display: flex;
        width: 100%;
    }
    .wa-received {
        justify-content: flex-start;
    }
    .wa-sent {
        justify-content: flex-end;
    }
    .wa-bubble {
        max-width: 85%;
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 12.5px;
        line-height: 1.45;
        position: relative;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        word-break: break-word;
    }
    .wa-received .wa-bubble {
        background: #ffffff;
        color: #111b21;
        border-top-left-radius: 0;
    }
    .wa-sent .wa-bubble {
        background: #d9fdd3;
        color: #111b21;
        border-top-right-radius: 0;
    }
    .wa-time {
        font-size: 9px;
        color: #667781;
        text-align: right;
        margin-top: 4px;
    }

    /* WhatsApp input area style */
    .wa-input-area {
        padding: 10px;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .wa-input-container {
        flex: 1;
        background: white;
        border-radius: 20px;
        padding: 5px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .wa-input-container input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 13px;
        padding: 5px 0;
        background: transparent;
    }
    .wa-send-btn {
        background: #00a884;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s;
    }
    .wa-send-btn:hover {
        background: #008f72;
    }
</style>

<!-- Copy URL JS -->
<script>
    function copyEndpoint() {
        const text = document.getElementById('endpointUrl').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert('Webhook URL copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    }

    // Smartphone chat handler
    function appendMessage(text, isSent) {
        const chatBody = document.getElementById('chatBody');
        const msgDiv = document.createElement('div');
        msgDiv.className = 'wa-msg ' + (isSent ? 'wa-sent' : 'wa-received');
        
        // WhatsApp markdown parser
        let formattedText = text;
        if (!isSent) {
            // Replace *bold* with <strong>
            formattedText = formattedText.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
            // Replace _italic_ with <em>
            formattedText = formattedText.replace(/_(.*?)_/g, '<em>$1</em>');
            // Replace line breaks with <br>
            formattedText = formattedText.replace(/\n/g, '<br>');
            // Convert URLs into <a> links
            formattedText = formattedText.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" style="color: #039be5; text-decoration: underline;">$1</a>');
        } else {
            // Escape user text to prevent XSS
            const temp = document.createElement('div');
            temp.textContent = formattedText;
            formattedText = temp.innerHTML;
        }

        const now = new Date();
        const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        msgDiv.innerHTML = `
            <div class="wa-bubble">
                ${formattedText}
                <div class="wa-time">${timeStr}</div>
            </div>
        `;
        
        chatBody.appendChild(msgDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function triggerPill(command) {
        appendMessage(command, true);
        fetchBotResponse(command);
    }

    function sendChatMessage(event) {
        event.preventDefault();
        const userInput = document.getElementById('userInput');
        const text = userInput.value.trim();
        if (!text) return;
        
        appendMessage(text, true);
        userInput.value = '';
        fetchBotResponse(text);
    }

    function fetchBotResponse(msgText) {
        // Append a typing placeholder
        const chatBody = document.getElementById('chatBody');
        const typingDiv = document.createElement('div');
        typingDiv.className = 'wa-msg wa-received';
        typingDiv.id = 'typingPlaceholder';
        typingDiv.innerHTML = `
            <div class="wa-bubble" style="color: #667781; font-style: italic;">
                typing...
            </div>
        `;
        chatBody.appendChild(typingDiv);
        chatBody.scrollTop = chatBody.scrollHeight;

        // AJAX request to chatbot endpoint
        fetch(`../api/v1/whatsapp_bot.php?message=${encodeURIComponent(msgText)}&format=text`)
            .then(response => response.text())
            .then(data => {
                // Remove placeholder
                const ph = document.getElementById('typingPlaceholder');
                if (ph) ph.remove();
                
                // Print chatbot response
                appendMessage(data, false);
            })
            .catch(error => {
                const ph = document.getElementById('typingPlaceholder');
                if (ph) ph.remove();
                
                appendMessage("❌ Error connecting to the chatbot API. Please check configuration.", false);
                console.error('Error:', error);
            });
    }
    
    // Automatically make sure feather icons are initialized on dynamically added elements as well
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }, 500);
    });
</script>

<?php
require_once 'includes/footer.php';
?>
