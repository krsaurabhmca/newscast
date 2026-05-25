<?php
/*
========================================
 AI CHAT APPLICATION
 Frontend + Backend in One File
 Powered by Groq API
========================================
*/
$page_title = "AI News Generator";
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin()) {
    redirect('admin/dashboard.php', 'Access denied.', 'danger');
}

$API_KEY = get_setting('groq_api_key');
$MODEL   = "llama-3.1-8b-instant";

/*
========================================
 AJAX REQUEST HANDLE
========================================
*/

if(isset($_POST['message']))
{
    header('Content-Type: application/json');

    // Fetch available categories so AI can suggest an exact match
    $stmt = $pdo->query("SELECT name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $category_list = !empty($categories) ? implode(', ', $categories) : 'News, Tech, Sports';

    if(empty($API_KEY)) {
        echo json_encode(["reply" => "Error: Groq API Key is not set. Please add it in the AI Integrations tab under Settings."]);
        exit;
    }

    $message = trim($_POST['message']);

    $data = [
        "model" => $MODEL,
        "messages" => [
            [
                "role" => "system",
                "content" => "
You are a professional Indian News Editor, SEO Content Writer, and Digital Media Journalist.

Your responsibility:
- Rewrite news professionally
- Create engaging headlines
- Generate SEO optimized articles
- Write like real media editors
- Sound natural and human
- Avoid robotic AI style

ARTICLE RULES:

1. Always generate:
   - Powerful headline
   - Short summary
   - Structured article
   - Key highlights
   - Conclusion

2. Writing Style:
   - Professional journalism tone
   - Human sounding
   - Easy readability
   - Medium-length paragraphs
   - Clean grammar

3. SEO Rules:
   - Use search-friendly headings
   - Include keywords naturally
   - Make content Google Discover friendly

4. Language Rules:
   - Write the ENTIRE NEWS CONTENT (Headline, Summary, Body, Highlights, Conclusion) EXCLUSIVELY IN HINDI.
   - ONLY the URL Slug should be generated in English or Hinglish format (e.g. [Slug: naya-niyam-2026-laagu]).
   - Use proper Hindi Unicode for the news.
   - Maintain professional Indian media style writing.

5. Avoid:
   - Fake claims
   - AI robotic language
   - Over dramatic words
   - Repeated content

8. Format Output Like:

# Headline
[Slug: english-url-slug-here]
[Category: EXACT_CATEGORY_NAME_FROM_LIST]
[Excerpt: Short 2-3 line summary here]

## Summary

Main Article Content

## Key Highlights
- Point 1
- Point 2
- Point 3

## Conclusion

AVAILABLE CATEGORIES TO CHOOSE FROM:
{$category_list}
"
            ],
            [
                "role" => "user",
                "content" => $message
            ]
        ],
        "temperature" => 0.9,
        "top_p" => 0.95,
        "max_tokens" => 4096
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer ".$API_KEY,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo json_encode(["reply" => curl_error($ch)]);
        exit;
    }
    curl_close($ch);

    $result = json_decode($response, true);
    $reply = $result['choices'][0]['message']['content'] ?? 'No response';

    echo json_encode(["reply" => $reply]);
    exit;
}

include 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/prismjs/themes/prism-tomorrow.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs/prism.min.js"></script>

<style>
.chat-container {
    display: flex;
    flex-direction: column;
    height: 70vh;
    min-height: 500px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.chat-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.chat-box {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f1f5f9;
}

.message {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}

.user {
    align-items: flex-end;
}

.ai {
    align-items: flex-start;
}

.bubble {
    max-width: 85%;
    padding: 15px;
    border-radius: 15px;
    line-height: 1.6;
    font-size: 14px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.user .bubble {
    background: var(--primary);
    color: #fff;
    border-bottom-right-radius: 4px;
}

.ai .bubble {
    background: #fff;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}

.ai .bubble h1, .ai .bubble h2, .ai .bubble h3 {
    margin-top: 10px;
    margin-bottom: 10px;
    color: #0f172a;
    font-size: 1.2em;
    font-weight: 700;
}

.chat-input {
    padding: 15px 20px;
    border-top: 1px solid #e2e8f0;
    background: #fff;
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.chat-input textarea {
    flex: 1;
    resize: none;
    padding: 12px 15px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    min-height: 50px;
    max-height: 150px;
    font-size: 14px;
    transition: all 0.2s;
}

.chat-input textarea:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.chat-input button {
    padding: 0 20px;
    height: 50px;
    border: none;
    border-radius: 12px;
    background: var(--primary);
    color: #fff;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.chat-input button:hover {
    filter: brightness(1.1);
}

.typing {
    display: flex;
    gap: 5px;
    padding: 5px 0;
}

.typing span {
    width: 8px;
    height: 8px;
    background: #94a3b8;
    border-radius: 50%;
    animation: bounce 1s infinite;
}

.typing span:nth-child(2) { animation-delay: 0.2s; }
.typing span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

pre {
    background: #1e293b !important;
    padding: 15px !important;
    border-radius: 8px !important;
    margin: 10px 0 !important;
    overflow-x: auto;
}

code {
    font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
    font-size: 13px;
}

.draft-btn {
    margin-top: 10px;
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.draft-btn:hover {
    background: #059669;
}
</style>

<div class="admin-grid">
    <div class="admin-main-col" style="flex: 1; max-width: 100%;">
        
        <?php if(empty($API_KEY)): ?>
            <div class="alert alert-warning" style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; padding:15px; border-radius:10px; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                <i data-feather="alert-triangle"></i>
                <div>
                    <strong>Groq API Key Missing!</strong><br>
                    Please configure your Groq API Key in the <a href="settings.php?tab=ai" style="color:#b45309; text-decoration:underline;">Settings > AI Integration</a> tab before using the generator.
                </div>
            </div>
        <?php endif; ?>

        <div class="chat-container">
            <div class="chat-header">
                <h3><i data-feather="cpu" style="width: 18px;"></i> AI News Generator</h3>
                <button class="btn" style="background:#f1f5f9; border:1px solid #e2e8f0; color:#475569; padding:5px 12px; font-size:12px;" onclick="newChat()">
                    <i data-feather="refresh-cw" style="width:14px; margin-right:4px;"></i> New Chat
                </button>
            </div>

            <div class="chat-box" id="chatBox">
                <div class="message ai">
                    <div class="bubble">
                        <div style="font-size:16px; margin-bottom:5px;">Hello! 👋</div>
                        I am your AI News Editor. Give me a raw news update, a press release, or a topic, and I will write a perfectly structured, SEO-friendly article for you.
                    </div>
                </div>
            </div>

            <div class="chat-input">
                <textarea id="message" placeholder="Paste your raw news, facts, or topic here..."></textarea>
                <button onclick="sendMessage()" <?php echo empty($API_KEY) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                    <i data-feather="send" style="width: 16px;"></i> Send
                </button>
            </div>
        </div>

    </div>
</div>

<script>
const textarea = document.getElementById('message');

textarea.addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight < 150 ? this.scrollHeight : 150) + 'px';
});

textarea.addEventListener('keydown', function(e){
    if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

let messageCounter = 0;

function addMessage(type, text, rawMarkdown = '') {
    const chatBox = document.getElementById('chatBox');
    
    const message = document.createElement('div');
    message.className = 'message ' + type;

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    
    bubble.innerHTML = marked.parse(text);

    message.appendChild(bubble);

    // If it's an AI message with substantial content, add a "Draft as Post" button
    if (type === 'ai' && text.length > 50 && text.includes('#')) {
        const btnId = 'draft_btn_' + messageCounter++;
        const draftContainer = document.createElement('div');
        draftContainer.style.marginTop = '10px';
        
        const draftBtn = document.createElement('button');
        draftBtn.className = 'draft-btn';
        draftBtn.id = btnId;
        draftBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Draft as Post';
        
        // Store the raw HTML and Markdown for form submission
        const htmlContent = bubble.innerHTML;
        
        draftBtn.onclick = function() {
            draftPost(text, htmlContent);
        };
        
        draftContainer.appendChild(draftBtn);
        message.appendChild(draftContainer);
    }

    chatBox.appendChild(message);
    Prism.highlightAll();
    chatBox.scrollTop = chatBox.scrollHeight;
}

function draftPost(markdown, html) {
    // Extract Headline, Slug, Category, and Excerpt
    let title = "Auto Generated AI Draft";
    let slug = "";
    let category = "";
    let excerpt = "";
    const lines = markdown.split('\n');
    let cleanLines = [];
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line.startsWith('# ') && title === "Auto Generated AI Draft") {
            title = line.substring(2).trim();
            // Skip adding title to body
        } else if (line.startsWith('[Slug:')) {
            slug = line.replace('[Slug:', '').replace(']', '').trim();
        } else if (line.startsWith('[Category:')) {
            category = line.replace('[Category:', '').replace(']', '').trim();
        } else if (line.startsWith('[Excerpt:')) {
            excerpt = line.replace('[Excerpt:', '').replace(']', '').trim();
        } else {
            if (line.length > 10 && !line.startsWith('#') && !line.startsWith('[') && title === "Auto Generated AI Draft" && cleanLines.length === 0) {
                // fallback to first substantial line if no title found yet
                title = line;
                cleanLines.push(lines[i]);
            } else {
                cleanLines.push(lines[i]);
            }
        }
    }

    const cleanHtml = marked.parse(cleanLines.join('\n').trim());

    // Create a dynamic form to POST data to post_add.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'post_add.php';
    form.target = '_blank'; // Open in new tab

    const titleInput = document.createElement('input');
    titleInput.type = 'hidden';
    titleInput.name = 'prefill_title';
    titleInput.value = title;

    const contentInput = document.createElement('input');
    contentInput.type = 'hidden';
    contentInput.name = 'prefill_content';
    contentInput.value = cleanHtml; // Sending the parsed HTML (without metadata tags) to the Quill Editor

    const slugInput = document.createElement('input');
    slugInput.type = 'hidden';
    slugInput.name = 'prefill_slug';
    slugInput.value = slug;

    const categoryInput = document.createElement('input');
    categoryInput.type = 'hidden';
    categoryInput.name = 'prefill_category';
    categoryInput.value = category;

    const excerptInput = document.createElement('input');
    excerptInput.type = 'hidden';
    excerptInput.name = 'prefill_excerpt';
    excerptInput.value = excerpt;

    form.appendChild(titleInput);
    form.appendChild(contentInput);
    form.appendChild(slugInput);
    form.appendChild(categoryInput);
    form.appendChild(excerptInput);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function typingAnimation() {
    return `
        <div class="message ai" id="typingBox">
            <div class="bubble" style="background:transparent; border:none; box-shadow:none; padding:10px 0;">
                <div class="typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        </div>
    `;
}

async function sendMessage() {
    const textarea = document.getElementById('message');
    let message = textarea.value.trim();

    if(message == '') return;

    addMessage('user', message);
    textarea.value = '';
    textarea.style.height = '50px';

    const chatBox = document.getElementById('chatBox');
    chatBox.innerHTML += typingAnimation();
    chatBox.scrollTop = chatBox.scrollHeight;

    let formData = new FormData();
    formData.append('message', message);

    try {
        let response = await fetch('', {
            method:'POST',
            body:formData
        });
        
        let data = await response.json();
        document.getElementById('typingBox').remove();
        
        if (data.reply) {
            addMessage('ai', data.reply);
        } else {
            addMessage('ai', 'Error: Invalid response from server.');
        }

    } catch(error) {
        document.getElementById('typingBox')?.remove();
        addMessage('ai', 'Error: ' + error);
    }
}

function newChat() {
    document.getElementById('chatBox').innerHTML = `
        <div class="message ai">
            <div class="bubble">
                <div style="font-size:16px; margin-bottom:5px;">Chat Cleared 🚀</div>
                What would you like to write about next?
            </div>
        </div>
    `;
}

// Initialize feather icons since we load them in footer.php, but if called early we might need to manually call it
window.addEventListener('load', function() {
    if(typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
