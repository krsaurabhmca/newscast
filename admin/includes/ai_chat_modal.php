<!-- AI Chat Floating Button -->
<button class="ai-fab" onclick="toggleAIChat()" title="AI News Generator">
    <i data-feather="cpu"></i>
</button>

<!-- AI Chat Modal Overlay -->
<div class="ai-chat-overlay" id="aiChatOverlay" onclick="closeAIChat(event)">
    <div class="ai-chat-modal" onclick="event.stopPropagation()">
        <div class="ai-chat-header">
            <h3><i data-feather="cpu" style="width: 18px;"></i> AI News Generator</h3>
            <div>
                <button class="btn" style="background:transparent; border:1px solid rgba(255,255,255,0.3); color:#fff; padding:4px 10px; font-size:12px; margin-right: 10px;" onclick="newAIChat()">
                    <i data-feather="refresh-cw" style="width:12px; margin-right:4px;"></i> Clear
                </button>
                <button style="background: transparent; border: none; color: #fff; cursor: pointer;" onclick="toggleAIChat()">
                    <i data-feather="x"></i>
                </button>
            </div>
        </div>

        <div class="ai-chat-box" id="aiChatBox">
            <div class="ai-message ai">
                <div class="ai-bubble">
                    <div style="font-size:15px; margin-bottom:5px;">Hello! 👋</div>
                    I am your AI News Editor. Give me a raw news update or topic, and I'll generate a perfectly structured article for you.
                </div>
            </div>
        </div>

        <div class="ai-chat-input">
            <textarea id="aiMessageInput" placeholder="Paste your raw news, facts, or topic here..."></textarea>
            <button onclick="sendAIMessage()">
                <i data-feather="send" style="width: 16px;"></i> Send
            </button>
        </div>
    </div>
</div>

<style>
/* Floating Action Button */
.ai-fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #9333ea;
    color: #fff;
    border: none;
    box-shadow: 0 10px 25px rgba(147, 51, 234, 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.ai-fab:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 15px 35px rgba(147, 51, 234, 0.5);
}
.ai-fab i { width: 24px; height: 24px; }

/* Modal Overlay */
.ai-chat-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}
.ai-chat-overlay.show {
    display: flex;
    opacity: 1;
}

/* Modal Content */
.ai-chat-modal {
    width: 90%;
    max-width: 800px;
    height: 85vh;
    background: #fff;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    transform: scale(0.95) translateY(20px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.ai-chat-overlay.show .ai-chat-modal {
    transform: scale(1) translateY(0);
}

.ai-chat-header {
    background: linear-gradient(135deg, #9333ea, #7e22ce);
    color: #fff;
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.ai-chat-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; font-size: 16px; }

.ai-chat-box {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
}

.ai-message {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}
.ai-message.user { align-items: flex-end; }
.ai-message.ai { align-items: flex-start; }

.ai-bubble {
    max-width: 85%;
    padding: 15px 20px;
    border-radius: 15px;
    line-height: 1.6;
    font-size: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.ai-message.user .ai-bubble {
    background: #9333ea;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ai-message.ai .ai-bubble {
    background: #fff;
    color: #334155;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}
.ai-message.ai .ai-bubble h1, .ai-message.ai .ai-bubble h2, .ai-message.ai .ai-bubble h3 { margin: 10px 0; color: #0f172a; font-size: 1.1em; }
.ai-message.ai .ai-bubble pre { background: #1e293b; padding: 15px; border-radius: 8px; color: #f8fafc; overflow-x: auto; }
.ai-message.ai .ai-bubble code { font-family: monospace; }

.ai-chat-input {
    padding: 20px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 15px;
    align-items: flex-end;
}
.ai-chat-input textarea {
    flex: 1;
    resize: none;
    padding: 15px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #f1f5f9;
    font-size: 14px;
    outline: none;
    min-height: 52px;
    max-height: 150px;
    transition: 0.2s;
}
.ai-chat-input textarea:focus { border-color: #9333ea; background: #fff; box-shadow: 0 0 0 3px rgba(147,51,234,0.1); }
.ai-chat-input button {
    height: 52px;
    padding: 0 25px;
    background: #9333ea;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s;
}
.ai-chat-input button:hover { background: #7e22ce; }

.ai-typing { display: flex; gap: 5px; padding: 10px; }
.ai-typing span { width: 8px; height: 8px; background: #94a3b8; border-radius: 50%; animation: ai-bounce 1s infinite; }
.ai-typing span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes ai-bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

.draft-btn {
    margin-top: 12px; background: #10b981; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
}
.draft-btn:hover { background: #059669; }
</style>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function toggleAIChat() {
    const overlay = document.getElementById('aiChatOverlay');
    if(overlay.classList.contains('show')) {
        overlay.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    } else {
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);
        document.getElementById('aiMessageInput').focus();
    }
}

function closeAIChat(e) {
    if(e.target === document.getElementById('aiChatOverlay')) toggleAIChat();
}

const aiTextarea = document.getElementById('aiMessageInput');
aiTextarea.addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight < 150 ? this.scrollHeight : 150) + 'px';
});
aiTextarea.addEventListener('keydown', function(e){
    if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAIMessage(); }
});

function addAIMessage(type, text) {
    const chatBox = document.getElementById('aiChatBox');
    const message = document.createElement('div');
    message.className = 'ai-message ' + type;
    const bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    
    bubble.innerHTML = marked.parse(text);
    message.appendChild(bubble);

    if (type === 'ai' && text.length > 50 && text.includes('#')) {
        const draftContainer = document.createElement('div');
        const draftBtn = document.createElement('button');
        draftBtn.className = 'draft-btn';
        draftBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Draft as Post';
        const htmlContent = bubble.innerHTML;
        draftBtn.onclick = () => draftAIPost(text, htmlContent);
        draftContainer.appendChild(draftBtn);
        message.appendChild(draftContainer);
    }

    chatBox.appendChild(message);
    chatBox.scrollTop = chatBox.scrollHeight;
}

function draftAIPost(markdown, html) {
    let title = "Auto Generated AI Draft", slug = "", category = "", excerpt = "";
    const lines = markdown.split('\n');
    let cleanLines = [];
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line.startsWith('# ') && title === "Auto Generated AI Draft") title = line.substring(2).trim();
        else if (line.startsWith('[Slug:')) slug = line.replace('[Slug:', '').replace(']', '').trim();
        else if (line.startsWith('[Category:')) category = line.replace('[Category:', '').replace(']', '').trim();
        else if (line.startsWith('[Excerpt:')) excerpt = line.replace('[Excerpt:', '').replace(']', '').trim();
        else {
            if (line.length > 10 && !line.startsWith('#') && !line.startsWith('[') && title === "Auto Generated AI Draft" && cleanLines.length === 0) {
                title = line; cleanLines.push(lines[i]);
            } else { cleanLines.push(lines[i]); }
        }
    }

    const cleanHtml = marked.parse(cleanLines.join('\n').trim());
    const form = document.createElement('form');
    form.method = 'POST'; form.action = 'post_add.php'; form.target = '_blank';
    
    ['title', 'content', 'slug', 'category', 'excerpt'].forEach(f => {
        const inp = document.createElement('input'); inp.type = 'hidden'; inp.name = 'prefill_' + f;
        inp.value = eval(f === 'content' ? 'cleanHtml' : f);
        form.appendChild(inp);
    });
    
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}

async function sendAIMessage() {
    let message = aiTextarea.value.trim();
    if(message == '') return;
    
    addAIMessage('user', message);
    aiTextarea.value = ''; aiTextarea.style.height = '52px';
    
    const chatBox = document.getElementById('aiChatBox');
    const typingId = 'typing_' + Date.now();
    chatBox.innerHTML += `<div class="ai-message ai" id="${typingId}"><div class="ai-bubble" style="background:transparent; border:none; box-shadow:none;"><div class="ai-typing"><span></span><span></span><span></span></div></div></div>`;
    chatBox.scrollTop = chatBox.scrollHeight;

    let formData = new FormData(); formData.append('message', message);
    try {
        let response = await fetch('ajax_ai_chat.php', { method:'POST', body:formData });
        let data = await response.json();
        document.getElementById(typingId).remove();
        addAIMessage('ai', data.reply || 'Error: Invalid response.');
        if(typeof feather !== 'undefined') feather.replace();
    } catch(err) {
        document.getElementById(typingId)?.remove();
        addAIMessage('ai', 'Error: ' + err);
    }
}

function newAIChat() {
    document.getElementById('aiChatBox').innerHTML = `
        <div class="ai-message ai">
            <div class="ai-bubble">
                <div style="font-size:15px; margin-bottom:5px;">Chat Cleared 🚀</div>
                What would you like to write about next?
            </div>
        </div>
    `;
    if(typeof feather !== 'undefined') feather.replace();
}

document.addEventListener("DOMContentLoaded", function() {
    if(typeof feather !== 'undefined') feather.replace();
});
</script>
