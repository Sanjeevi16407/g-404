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
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255, 255, 255, 0.01);
    }
    .chat-hub-body {
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
            <canvas id="chat-bg-canvas"></canvas>
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

    function triggerSuggestion(text) {
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

    function handleHubKey(e) {
        if (e.key === 'Enter') {
            sendHubMessage();
        }
    }

    function sendHubMessage() {
        const input = document.getElementById('hub-user-input');
        const query = input.value.trim();
        if (query === "") return;

        addHubBubble(query, 'user');
        input.value = "";

        fetch('../api/buddy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `query=${encodeURIComponent(query)}`
        })
        .then(res => res.json())
        .then(data => {
            addHubBubble(data.answer, 'buddy');
            if (speakOutput && data.answer) {
                speakHubOutput(data.answer);
            }
        })
        .catch(() => {
            addHubBubble("Sorry, I had trouble reaching my AI Senior brain servers. Please check your connection.", 'buddy');
        });
    }

    function clearChatHistory() {
        if (confirm("Are you sure you want to reset Buddy's session memory?")) {
            fetch('../api/buddy.php?clear=1')
            .then(res => res.json())
            .then(data => {
                document.getElementById('hub-conversation-feed').innerHTML = "";
                addHubBubble("Conversation memory reset. 👋 How can I help you now?", 'buddy');
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

    // 3D background particle sphere generator
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

        // Create glowing radial circle texture programmatically
        const createParticleTexture = () => {
            const canvasTex = document.createElement('canvas');
            canvasTex.width = 32;
            canvasTex.height = 32;
            const ctxTex = canvasTex.getContext('2d');
            const grad = ctxTex.createRadialGradient(16, 16, 0, 16, 16, 16);
            grad.addColorStop(0, 'rgba(255, 255, 255, 1)');
            grad.addColorStop(0.2, 'rgba(255, 255, 255, 0.85)');
            grad.addColorStop(0.5, 'rgba(255, 255, 255, 0.25)');
            grad.addColorStop(1, 'rgba(255, 255, 255, 0)');
            ctxTex.fillStyle = grad;
            ctxTex.fillRect(0, 0, 32, 32);
            return new THREE.CanvasTexture(canvasTex);
        };

        const particleCount = 1600; // High-density count
        let currentRadius = 8.5;
        let targetRadius = 8.5;
        let targetNoise = 0.2;
        let currentNoise = 0.2;
        let currentState = 'assemble';
        let sphereAngle = 0;

        const geometry = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const colors = new Float32Array(particleCount * 3);
        const particles = [];

        // Parse theme color values
        const getThemeColors = () => {
            const colorStr = getComputedStyle(document.documentElement).getPropertyValue('--glow-primary').trim();
            const baseColor = colorStr ? new THREE.Color(colorStr) : new THREE.Color(0x00f2fe);
            // Dynamic purple-indigo offset for dual-tone premium gradient shift
            const secondaryColor = new THREE.Color(0x7f00ff);
            return { baseColor, secondaryColor };
        };

        const { baseColor, secondaryColor } = getThemeColors();

        for (let i = 0; i < particleCount; i++) {
            const theta = Math.acos(Math.random() * 2 - 1);
            const phi = Math.random() * Math.PI * 2;
            
            const dx = Math.sin(theta) * Math.cos(phi);
            const dy = Math.sin(theta) * Math.sin(phi);
            const dz = Math.cos(theta);

            positions[i * 3] = dx * currentRadius;
            positions[i * 3 + 1] = dy * currentRadius;
            positions[i * 3 + 2] = dz * currentRadius;

            particles.push({
                x: dx * currentRadius,
                y: dy * currentRadius,
                z: dz * currentRadius,
                vx: 0,
                vy: 0,
                vz: 0,
                dx: dx,
                dy: dy,
                dz: dz,
                randSpeed: 0.3 + Math.random() * 0.7
            });

            // Gradient mixed color mapping based on vertical sphere height
            const t = (dy + 1.0) / 2.0;
            const mixedColor = baseColor.clone().lerp(secondaryColor, t);
            colors[i * 3] = mixedColor.r;
            colors[i * 3 + 1] = mixedColor.g;
            colors[i * 3 + 2] = mixedColor.b;
        }

        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        const material = new THREE.PointsMaterial({
            size: 0.35,
            map: createParticleTexture(),
            vertexColors: true,
            transparent: true,
            opacity: 0.85,
            blending: THREE.AdditiveBlending,
            depthWrite: false
        });

        const sphere = new THREE.Points(geometry, material);
        scene.add(sphere);

        // Global callback to control particle alignment vs boundary roaming
        window.setChatBackgroundState = (state) => {
            currentState = state;
            if (state === 'scatter') {
                targetRadius = 15.0;     // Expand boundary
                targetNoise = 1.2;       // Waviness active
            } else {
                targetRadius = 8.5;      // Tight sphere coordinates
                targetNoise = 0.2;       // Subtle breathing noise
            }
        };

        // Update colors dynamically on theme switch
        const observer = new MutationObserver(() => {
            const { baseColor: newBase, secondaryColor: newSec } = getThemeColors();
            const colorAttr = geometry.attributes.color;
            const colorsArr = colorAttr.array;
            for (let i = 0; i < particleCount; i++) {
                const t = (particles[i].dy + 1.0) / 2.0;
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

            // Compute dynamic viewport boundaries for boxing collision (at z=0)
            const halfH = camera.position.z * Math.tan((camera.fov * Math.PI) / 360);
            const halfW = halfH * camera.aspect;

            // Revolve angle over time
            sphereAngle += 0.004;

            // Smooth state values interpolation
            currentRadius += (targetRadius - currentRadius) * 0.08;
            currentNoise += (targetNoise - currentNoise) * 0.08;

            const posAttr = geometry.attributes.position;
            const positionsArr = posAttr.array;
            const time = Date.now() * 0.0015;

            for (let i = 0; i < particleCount; i++) {
                const p = particles[i];

                if (currentState === 'scatter') {
                    // Set random velocities on initial trigger
                    if (p.vx === 0 && p.vy === 0 && p.vz === 0) {
                        const speedVal = 0.22;
                        p.vx = (Math.random() - 0.5) * speedVal;
                        p.vy = (Math.random() - 0.5) * speedVal;
                        p.vz = (Math.random() - 0.5) * speedVal;
                    }

                    // Gentle drift force for roaming path
                    p.vx += (Math.random() - 0.5) * 0.006;
                    p.vy += (Math.random() - 0.5) * 0.006;
                    p.vz += (Math.random() - 0.5) * 0.006;

                    // Bound maximum speed to keep it inside screen nicely
                    p.vx *= 0.98;
                    p.vy *= 0.98;
                    p.vz *= 0.98;

                    p.x += p.vx;
                    p.y += p.vy;
                    p.z += p.vz;

                    // Boxing back collision check: bounce back off margins
                    const margin = 0.4;
                    if (p.x > halfW - margin) { p.x = halfW - margin; p.vx *= -1.0; }
                    if (p.x < -halfW + margin) { p.x = -halfW + margin; p.vx *= -1.0; }
                    
                    if (p.y > halfH - margin) { p.y = halfH - margin; p.vy *= -1.0; }
                    if (p.y < -halfH + margin) { p.y = -halfH + margin; p.vy *= -1.0; }
                    
                    if (p.z > 7) { p.z = 7; p.vz *= -1.0; }
                    if (p.z < -7) { p.z = -7; p.vz *= -1.0; }

                } else {
                    // Assemble: Rotates and Lerps coordinates back to the revolving sphere layout
                    const cosY = Math.cos(sphereAngle);
                    const sinY = Math.sin(sphereAngle);

                    const rotatedX = p.dx * cosY - p.dz * sinY;
                    const rotatedZ = p.dx * sinY + p.dz * cosY;

                    const offset = Math.sin(time + i * p.randSpeed) * currentNoise;
                    const dist = currentRadius + offset;

                    const targetX = rotatedX * dist;
                    const targetY = p.dy * dist;
                    const targetZ = rotatedZ * dist;

                    p.x += (targetX - p.x) * 0.07;
                    p.y += (targetY - p.y) * 0.07;
                    p.z += (targetZ - p.z) * 0.07;

                    p.vx = 0;
                    p.vy = 0;
                    p.vz = 0;
                }

                positionsArr[i * 3] = p.x;
                positionsArr[i * 3 + 1] = p.y;
                positionsArr[i * 3 + 2] = p.z;
            }
            posAttr.needsUpdate = true;

            renderer.render(scene, camera);
        };
        animate();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
