<?php
/**
 * Student Portal - Full-Screen Buddy Chat Hub
 */
require_once __DIR__ . '/includes/header.php';

// Fetch buddy details
$buddy_stmt = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1");
$buddy = $buddy_stmt->fetch();
$buddy_name = $buddy['buddy_name'] ?? 'Buddy';
?>
<!-- Hide the floating bubble when inside the full-screen chat workspace -->
<style>
    .buddy-ambient-container {
        display: none !important;
    }
    #chat-bg-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
        opacity: 0.28;
    }
    .chat-hub-body {
        position: relative;
    }
    .chat-hub-body > * {
        position: relative;
        z-index: 2;
    }
    
    .chat-hub-container {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        flex: 1;
        min-height: calc(100vh - 120px);
        align-items: stretch;
        margin-bottom: 24px;
    }

    /* Left Sidebar Menu Active State Glow */
    .menu-item-link[href="buddy.php"] {
        background: var(--glow-primary-alpha) !important;
        border: 1px solid var(--glow-primary) !important;
        color: var(--text-primary) !important;
        box-shadow: 0 0 15px var(--glow-primary-alpha) !important;
    }
    
    /* Center Column: Immersive chat panel */
    .chat-hub-workspace {
        position: relative;
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--border-glass);
        box-shadow: var(--box-shadow);
        background: rgba(13, 18, 35, 0.4);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
    }
    .chat-hub-header {
        position: relative;
        z-index: 2;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255, 255, 255, 0.01);
    }
    .chat-hub-body {
        position: relative;
        z-index: 2;
        flex: 1;
        overflow-y: auto;
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 18px;
        max-height: 520px;
        min-height: 460px;
    }
    .chat-hub-footer {
        position: relative;
        z-index: 2;
        padding: 20px 24px;
        border-top: 1px solid var(--border-light);
        display: flex;
        gap: 12px;
        align-items: center;
        background: rgba(255, 255, 255, 0.01);
    }
    
    /* Message Bubbles styling matching mockup */
    .msg-bubble {
        max-width: 75%;
        padding: 12px 18px;
        border-radius: 18px;
        font-size: 0.92rem;
        line-height: 1.5;
        word-wrap: break-word;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }
    .msg-bubble-buddy {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-glass);
        color: var(--text-primary);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    .msg-bubble-user {
        background: linear-gradient(135deg, #0072ff, #7f00ff);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: var(--text-primary);
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        box-shadow: 0 5px 15px rgba(0, 114, 255, 0.3);
    }
    
    /* Thinking Indicator Animation */
    .thinking-dots {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
    }
    .thinking-dots span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: var(--text-primary);
        opacity: 0.4;
        animation: bounce 1.4s infinite ease-in-out both;
    }
    .thinking-dots span:nth-child(1) { animation-delay: -0.32s; }
    .thinking-dots span:nth-child(2) { animation-delay: -0.16s; }

    @keyframes bounce {
        0%, 80%, 100% { transform: scale(0.2); opacity: 0.2; }
        40% { transform: scale(1.0); opacity: 1.0; }
    }
    
    .chat-input {
        flex: 1;
        padding: 12px 20px;
        font-size: 0.92rem;
        border-radius: 50px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid var(--border-glass);
        color: var(--text-primary);
        transition: all var(--transition-fast);
    }
    .chat-input:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--glow-primary);
        box-shadow: 0 0 10px var(--glow-primary-alpha);
        outline: none;
    }

    /* Right column widgets styling */
    .widgets-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* 1. Buddy profile card widget */
    .buddy-profile-card {
        padding: 24px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 20px;
        border: 1px solid var(--border-glass);
        background: rgba(13, 18, 35, 0.4);
    }
    .avatar-sphere-container {
        position: relative;
        width: 140px;
        height: 140px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-sphere-canvas {
        width: 140px;
        height: 140px;
        background: transparent;
    }
    .profile-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 4px;
    }
    .profile-subtitle {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-bottom: 12px;
    }
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.25);
        color: #10b981;
        font-size: 0.78rem;
        border-radius: 50px;
        margin-bottom: 16px;
    }
    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }
    .quote-card {
        padding: 12px 14px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        font-size: 0.8rem;
        color: var(--text-secondary);
        line-height: 1.4;
        font-style: italic;
    }
    
    /* 2. Suggested Questions */
    .suggested-questions-card {
        padding: 20px;
        border-radius: 20px;
        border: 1px solid var(--border-glass);
        background: rgba(13, 18, 35, 0.4);
    }
    .suggested-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .suggested-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .suggested-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-light);
        cursor: pointer;
        font-size: 0.8rem;
        color: var(--text-secondary);
        text-decoration: none;
        text-align: left;
        transition: all var(--transition-fast);
    }
    .suggested-item:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
        border-color: var(--glow-primary);
        transform: translateX(4px);
    }
    .suggested-item i {
        font-size: 0.75rem;
        color: var(--text-tertiary);
    }
    
    /* 3. Quick Actions */
    .quick-actions-card {
        padding: 20px;
        border-radius: 20px;
        border: 1px solid var(--border-glass);
        background: rgba(13, 18, 35, 0.4);
    }
    .quick-title {
        font-size: 0.88rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .quick-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 10px;
        border-radius: 12px;
        border: 1px solid var(--border-light);
        background: rgba(255, 255, 255, 0.02);
        color: var(--text-secondary);
        text-decoration: none;
        font-size: 0.78rem;
        font-weight: 500;
        transition: all var(--transition-fast);
        cursor: pointer;
    }
    .quick-action-btn:hover {
        transform: translateY(-2px);
        color: var(--text-primary);
    }
    
    /* Action-specific glows */
    .btn-notes:hover { border-color: #a78bfa; background: rgba(167, 139, 250, 0.08); box-shadow: 0 0 15px rgba(167, 139, 250, 0.15); }
    .btn-syllabus:hover { border-color: #60a5fa; background: rgba(96, 165, 250, 0.08); box-shadow: 0 0 15px rgba(96, 165, 250, 0.15); }
    .btn-map:hover { border-color: #2dd4bf; background: rgba(45, 212, 191, 0.08); box-shadow: 0 0 15px rgba(45, 212, 191, 0.15); }
    .btn-helpdesk:hover { border-color: #fbbf24; background: rgba(251, 191, 36, 0.08); box-shadow: 0 0 15px rgba(251, 191, 36, 0.15); }
    
    .btn-notes i { color: #c084fc; font-size: 1.15rem; }
    .btn-syllabus i { color: #60a5fa; font-size: 1.15rem; }
    .btn-map i { color: #2dd4bf; font-size: 1.15rem; }
    .btn-helpdesk i { color: #fbbf24; font-size: 1.15rem; }
    
    /* Light Theme compatibility overrides */
    html[data-theme="Light"] .chat-hub-workspace,
    html[data-theme="Light"] .buddy-profile-card,
    html[data-theme="Light"] .suggested-questions-card,
    html[data-theme="Light"] .quick-actions-card {
        background: rgba(255, 255, 255, 0.6);
        border-color: rgba(0, 0, 0, 0.08);
    }
    html[data-theme="Light"] .msg-bubble-buddy {
        background: rgba(0, 0, 0, 0.02);
        border-color: rgba(0, 0, 0, 0.06);
    }
    html[data-theme="Light"] .msg-bubble-user {
        border-color: rgba(0, 0, 0, 0.05);
        box-shadow: 0 5px 15px rgba(0, 114, 255, 0.15);
    }
    html[data-theme="Light"] .suggested-item,
    html[data-theme="Light"] .quick-action-btn {
        background: rgba(0, 0, 0, 0.02);
        border-color: rgba(0, 0, 0, 0.06);
    }
    html[data-theme="Light"] .suggested-item:hover,
    html[data-theme="Light"] .quick-action-btn:hover {
        background: rgba(0, 0, 0, 0.04);
    }
    html[data-theme="Light"] .quote-card {
        background: rgba(0, 0, 0, 0.03);
    }
</style>

<div class="chat-hub-container">
    
    <!-- 1. Center Panel: Immersive chat panel -->
    <div class="chat-hub-workspace">
        <canvas id="chat-bg-canvas"></canvas>
        <header class="chat-hub-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px #10b981;"></div>
                <div>
                    <h3 style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?php echo sanitize_input($buddy_name); ?> AI senior</h3>
                    <p style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px;">Conversational memory active</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <button id="hub-speaker-btn" onclick="toggleSpeakerOutput()" class="btn-action btn-edit" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid var(--border-glass);" title="Toggle Voice Output"><i class="fa-solid fa-volume-high"></i></button>
                <button onclick="clearChatHistory()" class="btn-action btn-delete" style="width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;" title="Reset Session Memory"><i class="fa-solid fa-trash-can"></i></button>
            </div>
        </header>

        <!-- Conversational Feed Grid -->
        <div class="chat-hub-body" id="hub-conversation-feed">
            <!-- Initial Greeting -->
            <div class="msg-bubble msg-bubble-buddy">
                👋 Hello! I am <?php echo sanitize_input($buddy_name); ?>, your digital senior. You can ask me any questions about our college blocks, exam regulations, syllabus, timetable or cafeteria, in English, Tamil, or Tanglish. I remember our conversation, so ask away!
            </div>
        </div>

        <!-- Chat Input Form Footer -->
        <footer class="chat-hub-footer">
            <?php if ($student_settings['notifications_enabled'] ?? 1): ?>
                <button class="chat-btn-voice" id="hub-mic-btn" onclick="toggleHubVoice()" style="width: 44px; height: 44px; font-size: 1.1rem;"><i class="fa-solid fa-microphone"></i></button>
            <?php endif; ?>
            
            <input type="text" id="hub-user-input" class="form-control chat-input" placeholder="Ask your digital senior..." onkeydown="handleHubKey(event)">
            <button onclick="sendHubMessage()" class="btn-glass btn-primary" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;"><i class="fa-solid fa-paper-plane"></i></button>
        </footer>
    </div>

    <!-- 2. Right Panel: Widgets Column -->
    <aside class="widgets-column">
        <!-- Profile & 3D Sphere -->
        <div class="buddy-profile-card">
            <div class="avatar-sphere-container">
                <canvas id="buddy-profile-canvas" class="avatar-sphere-canvas"></canvas>
            </div>
            <h3 class="profile-name"><?php echo sanitize_input($buddy_name); ?></h3>
            <p class="profile-subtitle">Your Digital Senior</p>
            
            <div class="status-indicator">
                <span class="status-dot"></span> Online • Ready to help
            </div>
            
            <div class="quote-card">
                “Not a human, but understands you better than some humans. 🧑‍🎓”
            </div>
        </div>
        
        <!-- Suggested Questions -->
        <div class="suggested-questions-card">
            <h4 class="suggested-title"><i class="fa-solid fa-lightbulb" style="color: var(--glow-primary);"></i> Suggested Questions</h4>
            <div class="suggested-list">
                <div class="suggested-item" onclick="triggerSuggestion('<?php echo addslashes($buddy['suggest_q1_query'] ?? 'Where is the campus library?'); ?>')">
                    <span><?php echo sanitize_input($buddy['suggest_q1_text'] ?? 'Where is the library?'); ?></span>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="suggested-item" onclick="triggerSuggestion('<?php echo addslashes($buddy['suggest_q2_query'] ?? 'canteen timings?'); ?>')">
                    <span><?php echo sanitize_input($buddy['suggest_q2_text'] ?? 'What is canteen timing?'); ?></span>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="suggested-item" onclick="triggerSuggestion('<?php echo addslashes($buddy['suggest_q3_query'] ?? 'Where is Natarajan maths cabin?'); ?>')">
                    <span><?php echo sanitize_input($buddy['suggest_q3_text'] ?? 'Find Dr. Natarajan\'s cabin'); ?></span>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
                <div class="suggested-item" onclick="triggerSuggestion('<?php echo addslashes($buddy['suggest_q4_query'] ?? 'library yenga iruku details pathu sollu'); ?>')">
                    <span><?php echo sanitize_input($buddy['suggest_q4_text'] ?? 'Library yenga iruku? (Tamil)'); ?></span>
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions-card">
            <h4 class="quick-title"><i class="fa-solid fa-bolt" style="color: var(--glow-primary);"></i> Quick Actions</h4>
            <div class="quick-grid">
                <a href="documents.php" class="quick-action-btn btn-notes">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Notes</span>
                </a>
                <a href="orientation.php" class="quick-action-btn btn-syllabus">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Syllabus</span>
                </a>
                <a href="campus.php" class="quick-action-btn btn-map">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Map</span>
                </a>
                <a href="faculty.php" class="quick-action-btn btn-helpdesk">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Helpdesk</span>
                </a>
            </div>
        </div>
    </aside>

</div>

<script>
    let speakOutput = <?php echo ($student_settings['notifications_enabled'] ?? 1) ? 'true' : 'false'; ?>;

    // Toggle speech synthesis read out
    function toggleSpeakerOutput() {
        speakOutput = !speakOutput;
        const btn = document.getElementById('hub-speaker-btn');
        if (speakOutput) {
            btn.style.borderColor = 'var(--glow-primary)';
            btn.style.color = 'var(--glow-primary)';
            btn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
        } else {
            btn.style.borderColor = 'var(--border-glass)';
            btn.style.color = 'var(--text-secondary)';
            btn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
        }
    }

    // Initialize 3D particles inside the right card on DOM load
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof Buddy3DParticles === 'function') {
            new Buddy3DParticles('buddy-profile-canvas');
        }
        
        toggleSpeakerOutput();
        toggleSpeakerOutput(); // double call triggers default check styling correctly
    });

    let isTyping = false;

    function triggerSuggestion(text) {
        if (isTyping) return;
        document.getElementById('hub-user-input').value = text;
        sendHubMessage();
    }

    function addHubBubble(text, sender) {
        const feed = document.getElementById('hub-conversation-feed');
        const bubble = document.createElement('div');
        bubble.className = `msg-bubble msg-bubble-${sender}`;
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        feed.appendChild(bubble);
        feed.scrollTop = feed.scrollHeight;
    }

    function showThinkingIndicator() {
        const feed = document.getElementById('hub-conversation-feed');
        const indicator = document.createElement('div');
        indicator.className = `msg-bubble msg-bubble-buddy thinking-bubble`;
        indicator.id = "buddy-thinking-indicator";
        indicator.innerHTML = `
            <div class="thinking-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        feed.appendChild(indicator);
        feed.scrollTop = feed.scrollHeight;
    }

    function removeThinkingIndicator() {
        const indicator = document.getElementById('buddy-thinking-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    function updateSuggestionsPanel(suggestions) {
        const container = document.querySelector('.suggested-list');
        if (!container || !suggestions) return;
        container.innerHTML = "";
        suggestions.forEach(item => {
            const btn = document.createElement('a');
            btn.className = "suggested-item";
            btn.innerHTML = `${item} <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem; opacity: 0.5;"></i>`;
            btn.setAttribute('onclick', `triggerSuggestion('${item.replace(/'/g, "\\'")}')`);
            container.appendChild(btn);
        });
    }

    function checkQuickAction(query) {
        const q = query.toLowerCase().trim();
        
        // 3D Campus Navigator redirection checks
        if (q.includes("library")) {
            showQuickActionMessage("Flying to Library on 3D Campus Map...", "campus.php?fly=library");
            return true;
        }
        if (q.includes("canteen")) {
            showQuickActionMessage("Flying to Canteen on 3D Campus Map...", "campus.php?fly=canteen");
            return true;
        }
        if (q.includes("rv block") || q.includes("rv")) {
            showQuickActionMessage("Flying to RV Block on 3D Campus Map...", "campus.php?fly=rv_block");
            return true;
        }
        if (q.includes("js block") || q.includes("js")) {
            showQuickActionMessage("Flying to JS Block on 3D Campus Map...", "campus.php?fly=js_block");
            return true;
        }
        if (q.includes("admin")) {
            showQuickActionMessage("Flying to Admin Block on 3D Campus Map...", "campus.php?fly=admin_block");
            return true;
        }
        if (q.includes("hostel")) {
            showQuickActionMessage("Flying to Campus Hostel on 3D Campus Map...", "campus.php?fly=hostel");
            return true;
        }
        if (q.includes("auditorium") || q.includes("audi")) {
            showQuickActionMessage("Flying to Auditorium on 3D Campus Map...", "campus.php?fly=auditorium");
            return true;
        }
        if (q.includes("parking")) {
            showQuickActionMessage("Flying to Parking Area on 3D Campus Map...", "campus.php?fly=parking");
            return true;
        }
        if (q.includes("main gate") || q.includes("entrance")) {
            showQuickActionMessage("Flying to Main Gate on 3D Campus Map...", "campus.php?fly=main_gate");
            return true;
        }

        // Standard portal shortcuts
        if (q.includes("show bus timing") || q.includes("open bus page") || q.includes("bus timing") || q.includes("bus timings")) {
            showQuickActionMessage("Opening Bus Routes Guide...", "campus.php?fly=bus_stop");
            return true;
        }
        if (q.includes("show events") || q.includes("events page") || q.includes("open events")) {
            showQuickActionMessage("Opening Campus Events Portal...", "events.php");
            return true;
        }
        if (q.includes("faculty") || q.includes("teachers") || q.includes("cabin")) {
            showQuickActionMessage("Opening Faculty Cabin Directory...", "faculty.php");
            return true;
        }
        if (q.includes("timetable") || q.includes("class schedule") || q.includes("schedule")) {
            showQuickActionMessage("Opening Weekly Timetable...", "timetable.php");
            return true;
        }
        return false;
    }

    function showQuickActionMessage(text, targetUrl) {
        addHubBubble(`🤖 Buddy says:<br>${text}`, 'buddy');
        setTimeout(() => {
            window.location.href = targetUrl;
        }, 1500);
    }

    function typeWriterEffect(text, sender, suggestions) {
        isTyping = true;
        if (typeof window.setChatBackgroundState === 'function') {
            window.setChatBackgroundState('scatter');
        }
        const feed = document.getElementById('hub-conversation-feed');
        const bubble = document.createElement('div');
        bubble.className = `msg-bubble msg-bubble-${sender}`;
        feed.appendChild(bubble);

        // Standardized Prefix
        bubble.innerHTML = "<strong>🤖 Buddy says:</strong><br>";

        // Clean out any formatting artifacts from model
        const cleanText = text.replace(/^🤖 Buddy says:?/i, '').trim();

        const words = cleanText.split(" ");
        let wordIndex = 0;

        function typeWord() {
            if (wordIndex < words.length) {
                bubble.innerHTML += (wordIndex === 0 ? "" : " ") + words[wordIndex];
                feed.scrollTop = feed.scrollHeight;
                wordIndex++;
                setTimeout(typeWord, 45); // 45ms per word typing animation
            } else {
                isTyping = false;
                feed.scrollTop = feed.scrollHeight;
                if (speakOutput && cleanText) {
                    speakHubOutput(cleanText);
                } else {
                    if (typeof window.setChatBackgroundState === 'function') {
                        window.setChatBackgroundState('assemble');
                    }
                }
                if (suggestions) {
                    updateSuggestionsPanel(suggestions);
                }
            }
        }
        typeWord();
    }

    function handleHubKey(e) {
        if (e.key === 'Enter') {
            sendHubMessage();
        }
    }

    function sendHubMessage() {
        if (isTyping) return;
        const input = document.getElementById('hub-user-input');
        const query = input.value.trim();
        if (query === "") return;

        addHubBubble(query, 'user');
        input.value = "";

        // Check for quick actions redirects
        if (checkQuickAction(query)) {
            return;
        }

        showThinkingIndicator();

        fetch('../api/buddy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `query=${encodeURIComponent(query)}`
        })
        .then(res => res.json())
        .then(data => {
            removeThinkingIndicator();
            typeWriterEffect(data.answer, 'buddy', data.suggestions);
        })
        .catch(() => {
            removeThinkingIndicator();
            addHubBubble("🤖 Buddy says:<br>Sorry, I had trouble reaching my AI Senior brain servers. Please check your connection.", 'buddy');
        });
    }

    function clearChatHistory() {
        if (isTyping) return;
        if (confirm("Are you sure you want to reset Buddy's session memory?")) {
            fetch('../api/buddy.php?clear=1')
            .then(res => res.json())
            .then(data => {
                document.getElementById('hub-conversation-feed').innerHTML = "";
                addHubBubble("🤖 Buddy says:<br>Conversation memory reset. 👋 How can I help you now?", 'buddy');
            });
        }
    }

    // Global reference to prevent garbage collection lockup in Chrome/Edge
    let activeUtterance = null;

    // Text to Speech
    function speakHubOutput(text) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            if (typeof window.setChatBackgroundState === 'function') {
                window.setChatBackgroundState('assemble'); // Reset state first
            }
            window.speechSynthesis.resume(); // Clear any paused state locks
            
            const clean = text.replace(/<[^>]*>/g, '').replace(/👋|📍|📚|🎉|💡|👨‍🏫|🗓️/g, '');
            activeUtterance = new SpeechSynthesisUtterance(clean);
            
            activeUtterance.onstart = function() {
                if (typeof window.setChatBackgroundState === 'function') {
                    window.setChatBackgroundState('scatter');
                }
            };

            // Prevent garbage collection GC sweeping
            activeUtterance.onend = function() {
                activeUtterance = null;
                if (typeof window.setChatBackgroundState === 'function') {
                    window.setChatBackgroundState('assemble');
                }
            };
            activeUtterance.onerror = function() {
                activeUtterance = null;
                if (typeof window.setChatBackgroundState === 'function') {
                    window.setChatBackgroundState('assemble');
                }
            };

            const voices = window.speechSynthesis.getVoices();
            const engVoice = voices.find(v => v.lang.includes('en-IN') || v.lang.includes('en-GB') || v.lang.includes('en-US'));
            if (engVoice) activeUtterance.voice = engVoice;
            
            window.speechSynthesis.speak(activeUtterance);
        }
    }

    // Speech to Text Web Recognition
    let hubRecognition;
    let hubRecording = false;

    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        hubRecognition = new SpeechRecognition();
        hubRecognition.continuous = false;
        hubRecognition.interimResults = false;
        hubRecognition.lang = 'en-IN'; // Indian English accent parser

        hubRecognition.onstart = function() {
            hubRecording = true;
            document.getElementById('hub-mic-btn').classList.add('recording');
        };

        hubRecognition.onresult = function(event) {
            const result = event.results[0][0].transcript;
            document.getElementById('hub-user-input').value = result;
            sendHubMessage();
        };

        hubRecognition.onerror = function() {
            hubRecording = false;
            document.getElementById('hub-mic-btn').classList.remove('recording');
        };

        hubRecognition.onend = function() {
            hubRecording = false;
            document.getElementById('hub-mic-btn').classList.remove('recording');
        };
    }

    function toggleHubVoice() {
        if (!hubRecognition) {
            alert("Speech recognition is not supported in this browser.");
            return;
        }
        if (hubRecording) {
            hubRecognition.stop();
        } else {
            hubRecognition.start();
        }
    }

    // Live black hole particle generator
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('chat-bg-canvas');
        if (!canvas || typeof THREE === 'undefined') return;

        const container = canvas.parentElement;
        let width = container.clientWidth;
        let height = container.clientHeight;

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, width / height, 0.1, 1000);
        camera.position.z = 15;

        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(width, height);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

        // Create glowing radial particle texture
        const createParticleTexture = () => {
            const canvasTex = document.createElement('canvas');
            canvasTex.width = 32;
            canvasTex.height = 32;
            const ctxTex = canvasTex.getContext('2d');
            const grad = ctxTex.createRadialGradient(16, 16, 0, 16, 16, 16);
            grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
            grad.addColorStop(0.2, 'rgba(255, 255, 255, 0.8)');
            grad.addColorStop(0.5, 'rgba(255, 255, 255, 0.2)');
            grad.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctxTex.fillStyle = grad;
            ctxTex.fillRect(0, 0, 32, 32);
            return new THREE.CanvasTexture(canvasTex);
        };

        const getThemeColors = () => {
            const colorStr = getComputedStyle(document.documentElement).getPropertyValue('--glow-primary').trim();
            const baseColor = colorStr ? new THREE.Color(colorStr) : new THREE.Color(0x00f2fe);
            const secondaryColor = new THREE.Color(0x7f00ff); // dynamic dual-tone secondary
            return { baseColor, secondaryColor };
        };

        const { baseColor, secondaryColor } = getThemeColors();

        // 1. Singularity Core (Black Hole Centre Circle)
        const singularityGeo = new THREE.CircleGeometry(1.6, 64);
        const singularityMat = new THREE.MeshBasicMaterial({
            color: 0x05070e,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.96
        });
        const singularity = new THREE.Mesh(singularityGeo, singularityMat);
        scene.add(singularity);

        // 2. Event Horizon Glowing Rim
        const horizonGeo = new THREE.RingGeometry(1.6, 1.8, 64);
        const horizonMat = new THREE.MeshBasicMaterial({
            color: baseColor.clone(),
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0.85,
            blending: THREE.AdditiveBlending
        });
        const horizon = new THREE.Mesh(horizonGeo, horizonMat);
        scene.add(horizon);

        // 3. Particles Accretion Disk Layout
        const particleCount = 1400;
        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);
        const particles = [];

        for (let i = 0; i < particleCount; i++) {
            const angle = Math.random() * Math.PI * 2;
            // Denser distribution closer to the event horizon (power function)
            const distance = 2.1 + Math.pow(Math.random(), 2) * 11;
            const z = (Math.random() - 0.5) * 0.6; // Thin accretion disk thickness

            const x = Math.cos(angle) * distance;
            const y = Math.sin(angle) * distance;

            positions[i * 3] = x;
            positions[i * 3 + 1] = y;
            positions[i * 3 + 2] = z;

            particles.push({
                x: x,
                y: y,
                z: z,
                distance: distance,
                angle: angle,
                orbitSpeed: (0.012 + Math.random() * 0.015) * (4.0 / Math.sqrt(distance)), // Keplerian speed
                pullSpeed: 0.006 + Math.random() * 0.007,
                origZ: z,
                scatterVx: 0,
                scatterVy: 0
            });

            // Map color gradient: brighter neon near core, fades to indigo outer
            const t = (distance - 2.1) / 11.0;
            const mixedColor = baseColor.clone().lerp(secondaryColor, t);
            colors[i * 3] = mixedColor.r;
            colors[i * 3 + 1] = mixedColor.g;
            colors[i * 3 + 2] = mixedColor.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.32,
            map: createParticleTexture(),
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending,
            depthWrite: false
        });

        const accretionDisk = new THREE.Points(geometry, material);
        scene.add(accretionDisk);

        let currentState = 'assemble'; // 'assemble' (live blackhole swirl) or 'scatter' (AI speaking/pulsating)
        let horizonPulse = 0;

        window.setChatBackgroundState = (state) => {
            currentState = state;
            if (state === 'scatter') {
                particles.forEach(p => {
                    // Set random radial push velocities when starting to speak
                    const pushForce = 0.12 + Math.random() * 0.16;
                    p.scatterVx = Math.cos(p.angle) * pushForce;
                    p.scatterVy = Math.sin(p.angle) * pushForce;
                });
            }
        };

        const observer = new MutationObserver(() => {
            const { baseColor: newBase, secondaryColor: newSec } = getThemeColors();
            horizonMat.color.copy(newBase);
            const colorAttr = geometry.attributes.color;
            const colorsArr = colorAttr.array;
            for (let i = 0; i < particleCount; i++) {
                const t = (particles[i].distance - 2.1) / 11.0;
                const mixed = newBase.clone().lerp(newSec, t);
                colorsArr[i * 3] = mixed.r;
                colorsArr[i * 3 + 1] = mixed.g;
                colorsArr[i * 3 + 2] = mixed.b;
            }
            colorAttr.needsUpdate = true;
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        const resize = () => {
            width = container.clientWidth;
            height = container.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        };
        window.addEventListener('resize', resize);

        setInterval(() => {
            if (container.clientHeight !== height || container.clientWidth !== width) {
                resize();
            }
        }, 1000);

        const animate = () => {
            requestAnimationFrame(animate);

            horizonPulse += 0.08;
            const posAttr = geometry.attributes.position;
            const positionsArr = posAttr.array;

            if (currentState === 'scatter') {
                // AI speaking: black hole event horizon pulsates violently
                const speakScale = 1.0 + Math.sin(Date.now() * 0.03) * 0.18;
                singularity.scale.setScalar(speakScale);
                horizon.scale.setScalar(speakScale);

                for (let i = 0; i < particleCount; i++) {
                    const p = particles[i];
                    
                    // Apply outward scatter velocity
                    p.x += p.scatterVx;
                    p.y += p.scatterVy;

                    // Decelerate scattering over time
                    p.scatterVx *= 0.96;
                    p.scatterVy *= 0.96;

                    // Orbit slightly as they scatter
                    p.angle += p.orbitSpeed * 0.25;
                    p.distance = Math.sqrt(p.x * p.x + p.y * p.y);

                    // Add dynamic floating waves
                    p.z += Math.sin(horizonPulse + i) * 0.015;

                    positionsArr[i * 3] = p.x;
                    positionsArr[i * 3 + 1] = p.y;
                    positionsArr[i * 3 + 2] = p.z;
                }
            } else {
                // Idle: Accretion Disk (particles fall towards the black hole core)
                const idleScale = 1.0 + Math.sin(Date.now() * 0.0025) * 0.05;
                singularity.scale.setScalar(idleScale);
                horizon.scale.setScalar(idleScale);

                for (let i = 0; i < particleCount; i++) {
                    const p = particles[i];

                    // Swirl orbit and spiral inwards
                    p.angle += p.orbitSpeed;
                    p.distance -= p.pullSpeed;
                    p.z += (p.origZ - p.z) * 0.1; // Restabilize vertical disk flatness

                    // Recalculate target positions
                    let targetX = Math.cos(p.angle) * p.distance;
                    let targetY = Math.sin(p.angle) * p.distance;

                    // Smooth transition from scattering back to disk orbit
                    p.x += (targetX - p.x) * 0.08;
                    p.y += (targetY - p.y) * 0.08;

                    // If particles cross event horizon (swallowed by the black hole core), respawn on the outer boundary
                    if (p.distance < 1.7) {
                        p.distance = 12.0 + Math.random() * 2.0;
                        p.angle = Math.random() * Math.PI * 2;
                        p.x = Math.cos(p.angle) * p.distance;
                        p.y = Math.sin(p.angle) * p.distance;
                        p.z = (Math.random() - 0.5) * 0.6;
                    }

                    positionsArr[i * 3] = p.x;
                    positionsArr[i * 3 + 1] = p.y;
                    positionsArr[i * 3 + 2] = p.z;
                }
            }

            posAttr.needsUpdate = true;
            renderer.render(scene, camera);
        };
        animate();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
