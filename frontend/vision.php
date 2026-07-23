<?php
/**
 * Student Portal - Buddy AR Live Campus Guide (Buddy Live Vision)
 * Implements real-time WebRTC camera feed, Geolocation, Device Orientation,
 * AR-style overlay calculations, and floating Buddy AI Senior assistant.
 */
require_once __DIR__ . '/includes/header.php';

// Fetch buddy details
$buddy_stmt = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1");
$buddy = $buddy_stmt->fetch();
$buddy_name = $buddy['buddy_name'] ?? 'Buddy';
?>

<!-- AR Live Vision Immersive Container -->
<style>
    /* Full-viewport camera container */
    .ar-vision-wrapper {
        position: relative;
        width: 100%;
        height: calc(100vh - 100px);
        overflow: hidden;
        border-radius: 24px;
        border: 1px solid var(--border-glass);
        box-shadow: var(--box-shadow);
        background: #000;
        margin-bottom: 24px;
    }
    
    /* Full-screen WebRTC video element */
    #camera-feed {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
    }

    /* Spatial Canvas for AR overlays */
    .ar-overlay-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
        pointer-events: none;
    }

    /* Floating AR Label Styles */
    .ar-label-card {
        position: absolute;
        pointer-events: auto;
        transform: translate(-50%, -50%);
        background: rgba(13, 18, 35, 0.68);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(0, 242, 254, 0.45);
        box-shadow: 0 0 20px rgba(0, 242, 254, 0.25);
        color: #fff;
        padding: 10px 16px;
        border-radius: 16px;
        transition: left 0.1s ease-out, top 0.1s ease-out, transform 0.2s ease-out;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        font-family: inherit;
        z-index: 5;
    }

    .ar-label-card.active-facing {
        border-color: #10b981;
        box-shadow: 0 0 25px rgba(16, 185, 129, 0.4);
    }

    .ar-icon {
        font-size: 1.3rem;
        color: var(--glow-primary);
        filter: drop-shadow(0 0 4px var(--glow-primary));
    }
    
    .ar-label-card.active-facing .ar-icon {
        color: #10b981;
        filter: drop-shadow(0 0 4px #10b981);
    }

    .ar-title {
        font-weight: 700;
        font-size: 0.95rem;
    }

    .ar-dist {
        font-size: 0.8rem;
        opacity: 0.85;
        font-weight: 500;
    }

    .ar-arrow {
        font-size: 1.1rem;
        color: var(--glow-primary);
        animation: pulseArrow 1.5s infinite alternate;
    }
    
    .ar-label-card.active-facing .ar-arrow {
        color: #10b981;
        animation: none;
    }

    @keyframes pulseArrow {
        0% { transform: translateY(0); }
        100% { transform: translateY(-4px); }
    }

    /* Screen edge indicators for off-screen blocks */
    .ar-edge-indicator {
        position: absolute;
        pointer-events: auto;
        background: rgba(13, 18, 35, 0.7);
        border: 1px solid var(--border-glass);
        padding: 6px 12px;
        border-radius: 10px;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 4;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: var(--box-shadow);
        cursor: pointer;
    }

    /* Developer Simulation Control Widget */
    .dev-control-widget {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10;
        width: 320px;
        background: rgba(13, 18, 35, 0.85);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 16px;
        color: #fff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        pointer-events: auto;
        transition: all 0.3s ease;
    }
    
    .dev-control-widget.collapsed {
        width: 50px;
        height: 50px;
        padding: 0;
        overflow: hidden;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dev-toggle-btn {
        width: 100%;
        background: transparent;
        border: none;
        color: var(--glow-primary);
        font-size: 1.1rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px;
    }

    .dev-control-widget.collapsed .dev-toggle-btn {
        justify-content: center;
        height: 100%;
    }

    .dev-title {
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .dev-form-group {
        margin-top: 12px;
    }

    .dev-label {
        font-size: 0.75rem;
        color: var(--text-secondary);
        display: block;
        margin-bottom: 4px;
    }

    .dev-select, .dev-input {
        width: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-glass);
        border-radius: 8px;
        padding: 6px 10px;
        color: #fff;
        font-size: 0.8rem;
        outline: none;
    }

    /* Floating AR Chat Panel overlay */
    .ar-chat-panel {
        position: absolute;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 48px);
        max-width: 650px;
        background: rgba(13, 18, 35, 0.75);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 24px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        z-index: 10;
        padding: 16px 20px;
        pointer-events: auto;
        display: grid;
        grid-template-columns: 80px 1fr;
        gap: 16px;
        align-items: center;
    }

    .ar-buddy-canvas-container {
        width: 80px;
        height: 80px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ar-buddy-canvas {
        width: 80px;
        height: 80px;
    }

    .ar-chat-content {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ar-chat-text {
        font-size: 0.95rem;
        font-weight: 500;
        line-height: 1.4;
        color: #fff;
        max-height: 80px;
        overflow-y: auto;
    }

    .ar-chat-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
    }

    .ar-status-text {
        font-size: 0.75rem;
        color: var(--text-secondary);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .status-dot-active {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 8px #10b981;
    }
    
    .status-dot-listening {
        background: #ef4444;
        box-shadow: 0 0 8px #ef4444;
        animation: pulseListen 1.2s infinite;
    }

    @keyframes pulseListen {
        0% { opacity: 0.3; }
        50% { opacity: 1; }
        100% { opacity: 0.3; }
    }

    .btn-ar {
        padding: 6px 16px;
        font-size: 0.8rem;
        font-weight: 600;
        border-radius: 30px;
        border: 1px solid var(--border-glass);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    
    .btn-ar-stop {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.3);
    }
    
    .btn-ar-stop:hover {
        background: rgba(239, 68, 68, 0.3);
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
    }

    .btn-ar-nav {
        background: linear-gradient(135deg, #0072ff, #7f00ff);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.2);
    }

    .btn-ar-nav:hover {
        box-shadow: 0 0 15px rgba(0, 114, 255, 0.4);
    }
</style>

<div class="container-fluid chat-hub-body">
    
    <!-- AR live Vision panel wrapper -->
    <div class="ar-vision-wrapper">
        <!-- Live camera WebRTC feed -->
        <video id="camera-feed" autoplay playsinline muted></video>
        
        <!-- AR HUD Overlay Layer -->
        <div class="ar-overlay-container" id="ar-overlay">
            <!-- Dynamic building cards injected by JS -->
        </div>

        <!-- Dev Control Panel -->
        <div class="dev-control-widget" id="dev-widget">
            <button class="dev-toggle-btn" onclick="toggleDevWidget()">
                <span class="dev-title" id="dev-widget-title"><i class="fa-solid fa-screwdriver-wrench"></i> Simulation panel</span>
                <i class="fa-solid fa-chevron-up" id="dev-widget-icon"></i>
            </button>
            <div id="dev-widget-content" style="margin-top: 10px;">
                <div class="dev-form-group">
                    <label class="dev-label">Preset Location (GPS Mock)</label>
                    <select class="dev-select" id="mock-loc-select" onchange="applyLocationMock()">
                        <option value="main_gate">Main Gate (Entrance)</option>
                        <option value="parking">Parking Area (Near Entrance)</option>
                        <option value="ks_block">KS Block (Bottom Left)</option>
                        <option value="canteen">Canteen (Middle Left)</option>
                        <option value="rv_block" selected>RV Block (Middle Right)</option>
                        <option value="hostel">Boys Hostel (Top North)</option>
                        <option value="ground_main">Main Ground (Sports Field)</option>
                    </select>
                </div>
                <div class="dev-form-group">
                    <label class="dev-label">Heading Direction Offset: <span id="heading-val" style="color: var(--glow-primary); font-weight: 700;">180°</span></label>
                    <input type="range" class="w-full" id="mock-heading-slider" min="0" max="360" value="180" oninput="applyHeadingMock()">
                </div>
                <div class="dev-form-group">
                    <label class="dev-label">Custom Lat/Lng Offset</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.0001" class="dev-input" id="custom-lat" value="10.7563">
                        <input type="number" step="0.0001" class="dev-input" id="custom-lng" value="78.6515">
                    </div>
                    <button class="btn-glass btn-primary w-full mt-2 py-1 text-xs" onclick="applyCustomCoordinates()">Apply custom position</button>
                </div>
            </div>
        </div>

        <!-- Floating Transparent Chat Panel -->
        <div class="ar-chat-panel">
            <div class="ar-buddy-canvas-container">
                <canvas id="ar-buddy-canvas" class="ar-buddy-canvas"></canvas>
            </div>
            
            <div class="ar-chat-content">
                <div class="ar-chat-text" id="ar-voice-bubble">
                    👋 Welcome to **Buddy Live Vision**! Press the mic or speak naturally. Turn around to view spatial labels pointing to blocks.
                </div>
                
                <div class="ar-chat-actions">
                    <div class="ar-status-text">
                        <span class="status-dot-active" id="listening-indicator"></span>
                        <span id="listening-text">Buddy ready</span>
                    </div>
                    
                    <div class="flex gap-2">
                        <button class="btn-ar btn-ar-stop" onclick="stopARVision()"><i class="fa-solid fa-circle-stop"></i> Stop</button>
                        <button class="btn-ar btn-ar-nav" id="navigate-btn" style="display: none;" onclick="navigateToSelection()"><i class="fa-solid fa-location-arrow"></i> Navigate</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Three.js Visualisation canvas loader -->
<script>
    // College building database
    const locations = {
        main_gate: { name: "Main Gate", coords: [10.7548, 78.6524], icon: "fa-solid fa-door-open" },
        parking: { name: "Parking Area", coords: [10.7552, 78.6521], icon: "fa-solid fa-square-parking" },
        ks_block: { name: "KS Block", coords: [10.7562, 78.6511], icon: "fa-solid fa-microchip" },
        rv_block: { name: "RV Block", coords: [10.7565, 78.6517], icon: "fa-solid fa-laptop-code" },
        bd_js_block: { name: "JS & BD Block", coords: [10.7572, 78.6513], icon: "fa-solid fa-building-columns" },
        canteen: { name: "Canteen", coords: [10.7570, 78.6508], icon: "fa-solid fa-utensils" },
        staff_parking: { name: "Staff Parking", coords: [10.7577, 78.6510], icon: "fa-solid fa-car" },
        mech_block: { name: "Mech Block", coords: [10.7577, 78.6505], icon: "fa-solid fa-gears" },
        bus_parking: { name: "Bus Parking", coords: [10.7582, 78.6507], icon: "fa-solid fa-bus" },
        hostel: { name: "Boys Hostel", coords: [10.7588, 78.6504], icon: "fa-solid fa-hotel" },
        ground_main: { name: "Main Ground", coords: [10.7570, 78.6499], icon: "fa-solid fa-circle-play" }
    };

    let userLat = 10.7563;
    let userLng = 78.6515;
    let compassHeading = 180; // 0 = North, 90 = East, 180 = South, 270 = West
    let currentNavTarget = null;
    let speakOutput = true;

    // WebRTC Camera Feed handler
    async function startCamera() {
        const video = document.getElementById('camera-feed');
        if (!video) return;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
            video.srcObject = stream;
        } catch (err) {
            console.warn("Back camera media stream failed, trying any default video input...", err);
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
            } catch (fallbackErr) {
                console.error("Camera access denied or missing inputs.", fallbackErr);
                document.getElementById('ar-voice-bubble').innerHTML = "⚠️ **Camera Feed Blocked**: Please grant camera access permissions. The AR overlays will still update in simulation mode.";
            }
        }
    }

    // Toggle dev simulation control view
    function toggleDevWidget() {
        const widget = document.getElementById('dev-widget');
        const icon = document.getElementById('dev-widget-icon');
        const content = document.getElementById('dev-widget-content');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            widget.classList.remove('collapsed');
            icon.className = 'fa-solid fa-chevron-up';
        } else {
            content.style.display = 'none';
            widget.classList.add('collapsed');
            icon.className = 'fa-solid fa-chevron-down';
        }
    }

    // Collapsed by default on mobile, open on desktop
    document.addEventListener("DOMContentLoaded", () => {
        if (window.innerWidth < 768) {
            toggleDevWidget();
        }
    });

    // Haversine formula to compute distance in metres
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // Earth radius in metres
        const f1 = lat1 * Math.PI / 180;
        const f2 = lat2 * Math.PI / 180;
        const df = (lat2 - lat1) * Math.PI / 180;
        const dl = (lon2 - lon1) * Math.PI / 180;

        const a = Math.sin(df / 2) * Math.sin(df / 2) +
                  Math.cos(f1) * Math.cos(f2) *
                  Math.sin(dl / 2) * Math.sin(dl / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

        return R * c;
    }

    // Calculate absolute compass bearing from user to target location
    function calculateBearing(lat1, lon1, lat2, lon2) {
        const y = Math.sin((lon2 - lon1) * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180);
        const x = Math.cos(lat1 * Math.PI / 180) * Math.sin(lat2 * Math.PI / 180) -
                  Math.sin(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.cos((lon2 - lon1) * Math.PI / 180);
        const bearing = Math.atan2(y, x) * 180 / Math.PI;
        return (bearing + 360) % 360;
    }

    // Update AR elements positioning based on GPS and Compass
    function updateAROverlay() {
        const overlay = document.getElementById('ar-overlay');
        if (!overlay) return;

        // Clear existing overlays
        overlay.innerHTML = "";

        const width = overlay.clientWidth;
        const height = overlay.clientHeight;
        const fovHorizontal = 80; // Field of View angle threshold in degrees

        Object.keys(locations).forEach(key => {
            const loc = locations[key];
            const dist = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);
            const bearing = calculateBearing(userLat, userLng, loc.coords[0], loc.coords[1]);

            // Relative bearing to current device rotation
            let relativeBearing = bearing - compassHeading;
            // Normalize relative bearing to range [-180, 180]
            relativeBearing = (relativeBearing + 180) % 360 - 180;

            // If the target is inside the viewport Field of View
            if (Math.abs(relativeBearing) <= fovHorizontal / 2) {
                // Calculate horizontal percentage screen coordinate
                const xPct = 50 + (relativeBearing / (fovHorizontal / 2)) * 50;
                
                // Stagger markers vertically depending on distance to make overlaps less severe
                let yPct = 40 + (Math.sin(bearing) * 8); // Base stagger
                
                // Closer buildings appear lower, farther buildings higher
                const distOffset = Math.min(dist / 150, 1) * 20; // 0 to 20% shift
                yPct = yPct - 10 + distOffset;

                // Create glass card label
                const card = document.createElement('div');
                const isFacingTarget = Math.abs(relativeBearing) <= 6;
                card.className = `ar-label-card ${isFacingTarget ? 'active-facing' : ''}`;
                card.style.left = `${xPct}%`;
                card.style.top = `${yPct}%`;
                card.style.transform = `translate(-50%, -50%) scale(${isFacingTarget ? 1.05 : 0.9})`;

                card.innerHTML = `
                    <i class="${loc.icon} ar-icon"></i>
                    <span class="ar-title">${loc.name}</span>
                    <span class="ar-dist">${Math.round(dist)} m</span>
                    <span class="ar-arrow">${isFacingTarget ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-arrow-up"></i>'}</span>
                    ${isFacingTarget ? '<span class="text-[0.65rem] text-emerald-400 font-bold">✓ Straight Ahead</span>' : ''}
                `;

                // Set nav target when clicking any card label
                card.onclick = () => {
                    selectLocationTarget(key);
                };

                overlay.appendChild(card);
            } else {
                // Off-screen helpers drawn on left or right borders
                const directionArrow = relativeBearing < 0 ? '←' : '→';
                const sideClass = relativeBearing < 0 ? 'left' : 'right';
                
                const indicator = document.createElement('div');
                indicator.className = `ar-edge-indicator`;
                indicator.style.top = `calc(50% + ${Math.sin(bearing) * 120}px)`;
                if (sideClass === 'left') {
                    indicator.style.left = '16px';
                    indicator.innerHTML = `<span>${directionArrow} ${loc.name} (${Math.round(dist)}m)</span>`;
                } else {
                    indicator.style.right = '16px';
                    indicator.innerHTML = `<span>${loc.name} (${Math.round(dist)}m) ${directionArrow}</span>`;
                }

                indicator.onclick = () => {
                    selectLocationTarget(key);
                };

                overlay.appendChild(indicator);
            }
        });
    }

    // Select location and show Nav options
    function selectLocationTarget(key) {
        currentNavTarget = key;
        const loc = locations[key];
        const dist = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);

        document.getElementById('navigate-btn').style.display = 'inline-flex';
        document.getElementById('ar-voice-bubble').innerHTML = `📍 **Target Selected**: **${loc.name}** is **${Math.round(dist)} meters** away from you. Click **Navigate** to open the 3D Satellite Map guide.`;

        // If voice synthesis is running, let Buddy confirm
        if (speakOutput) {
            speakAROutput(`Selected ${loc.name}. It is about ${Math.round(dist)} meters away.`);
        }
    }

    // GPS Geolocation Sensor tracking
    function initGPS() {
        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(
                (pos) => {
                    // Update user coordinates dynamically
                    userLat = pos.coords.latitude;
                    userLng = pos.coords.longitude;

                    // Sync custom input dev markers
                    document.getElementById('custom-lat').value = userLat.toFixed(6);
                    document.getElementById('custom-lng').value = userLng.toFixed(6);

                    updateAROverlay();
                },
                (err) => {
                    console.warn("GPS tracking access blocked, using simulated mock positions.", err);
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    }

    // Compass device sensors (iOS / Android support)
    function initCompass() {
        // Request iOS device permission request block
        if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
            DeviceOrientationEvent.requestPermission()
                .then(response => {
                    if (response === 'granted') {
                        window.addEventListener('deviceorientation', handleOrientationEvent, true);
                    }
                })
                .catch(console.error);
        } else {
            window.addEventListener('deviceorientationabsolute', handleOrientationEvent, true);
            window.addEventListener('deviceorientation', handleOrientationEvent, true);
        }
    }

    function handleOrientationEvent(event) {
        // compass heading in degrees (alpha or webkitCompassHeading)
        let heading = event.webkitCompassHeading || event.alpha;
        if (heading !== undefined) {
            compassHeading = 360 - heading; // Mirror logic for camera viewport
            document.getElementById('mock-heading-slider').value = Math.round(compassHeading);
            document.getElementById('heading-val').innerText = `${Math.round(compassHeading)}°`;
            updateAROverlay();
        }
    }

    // Desktop Developer Panel overrides
    function applyLocationMock() {
        const val = document.getElementById('mock-loc-select').value;
        const loc = locations[val];
        if (loc) {
            userLat = loc.coords[0];
            userLng = loc.coords[1];

            document.getElementById('custom-lat').value = userLat.toFixed(6);
            document.getElementById('custom-lng').value = userLng.toFixed(6);

            updateAROverlay();
        }
    }

    function applyHeadingMock() {
        const val = parseInt(document.getElementById('mock-heading-slider').value);
        compassHeading = val;
        document.getElementById('heading-val').innerText = `${val}°`;
        updateAROverlay();
    }

    function applyCustomCoordinates() {
        const lat = parseFloat(document.getElementById('custom-lat').value);
        const lng = parseFloat(document.getElementById('custom-lng').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            userLat = lat;
            userLng = lng;
            updateAROverlay();
        }
    }

    // Stop AR and return to chat screen
    function stopARVision() {
        window.location.href = 'buddy.php';
    }

    // Jump to Leaflet 3D Sat navigator focus page
    function navigateToSelection() {
        if (currentNavTarget) {
            window.location.href = `campus.php?fly=${currentNavTarget}`;
        }
    }

    // Zero-click AR Voice recognition (Speech to Text)
    let arRecognition;
    let arListening = false;

    function initARSpeechRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            arRecognition = new SpeechRecognition();
            arRecognition.continuous = true;
            arRecognition.interimResults = false;
            arRecognition.lang = 'en-IN'; // Indian English accent context parser

            arRecognition.onstart = () => {
                arListening = true;
                const ind = document.getElementById('listening-indicator');
                ind.className = "status-dot-listening";
                document.getElementById('listening-text').innerText = "Listening...";
            };

            arRecognition.onresult = (event) => {
                const query = event.results[event.results.length - 1][0].transcript.trim();
                if (query) {
                    processVoiceQuery(query);
                }
            };

            arRecognition.onend = () => {
                arListening = false;
                const ind = document.getElementById('listening-indicator');
                ind.className = "status-dot-active";
                document.getElementById('listening-text').innerText = "Buddy ready";
                
                // Restart listening automatically for hands-free mode
                if (window.location.pathname.includes('vision.php')) {
                    setTimeout(() => {
                        try { arRecognition.start(); } catch(e) {}
                    }, 500);
                }
            };

            arRecognition.start();
        }
    }

    // Send query to Gemini API and print/speak back
    function processVoiceQuery(query) {
        document.getElementById('ar-voice-bubble').innerHTML = `🎙️ *You said:* "${query}"<br><span style='opacity: 0.7;'>Thinking...</span>`;

        fetch('../api/buddy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `query=${encodeURIComponent(query)}`
        })
        .then(res => res.json())
        .then(data => {
            let replyText = data.answer;
            
            // Clean markdown syntax indicators
            const cleanText = replyText.replace(/\*\*|\*/g, '');
            document.getElementById('ar-voice-bubble').innerHTML = replyText;

            // Highlight target location if Buddy references a block name
            Object.keys(locations).forEach(key => {
                const nameLower = locations[key].name.toLowerCase();
                const cleanQuery = query.toLowerCase();
                if (cleanQuery.includes(nameLower) || cleanQuery.includes(key.replace('_', ' '))) {
                    selectLocationTarget(key);
                }
            });

            if (speakOutput) {
                speakAROutput(cleanText);
            }
        })
        .catch(() => {
            document.getElementById('ar-voice-bubble').innerText = "🤖 Buddy says: I couldn't reach my AI senior brain. Please check your connection.";
        });
    }

    // Text to Speech
    let activeUtterance = null;
    function speakAROutput(text) {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
            window.speechSynthesis.resume();

            // Trigger visual particle scattering
            if (typeof window.setChatBackgroundState === 'function') {
                window.setChatBackgroundState('scatter');
            }

            activeUtterance = new SpeechSynthesisUtterance(text);
            
            activeUtterance.onend = () => {
                activeUtterance = null;
                if (typeof window.setChatBackgroundState === 'function') {
                    window.setChatBackgroundState('assemble');
                }
            };
            activeUtterance.onerror = () => {
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

    // 3D Buddy profile canvas inside transparent card
    document.addEventListener("DOMContentLoaded", () => {
        startCamera();
        initGPS();
        initCompass();
        updateAROverlay();
        initARSpeechRecognition();

        // Spawn Three.js 3D particles in the card
        if (typeof THREE !== 'undefined') {
            const canvas = document.getElementById('ar-buddy-canvas');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(60, 1, 0.1, 1000);
            camera.position.z = 12;

            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            renderer.setSize(80, 80);

            // Simple 3D revolving particle sphere
            const count = 400;
            const geo = new THREE.BufferGeometry();
            const pos = new Float32Array(count * 3);
            const radius = 4.5;

            for (let i = 0; i < count; i++) {
                const theta = Math.random() * Math.PI * 2;
                const phi = Math.acos(Math.random() * 2 - 1);
                pos[i * 3] = radius * Math.sin(phi) * Math.cos(theta);
                pos[i * 3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
                pos[i * 3 + 2] = radius * Math.cos(phi);
            }

            geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
            
            const mat = new THREE.PointsMaterial({
                color: 0x00f2fe,
                size: 0.28,
                transparent: true,
                opacity: 0.9,
                blending: THREE.AdditiveBlending
            });

            const points = new THREE.Points(geo, mat);
            scene.add(points);

            let particleState = 'assemble';
            let rotY = 0;

            window.setChatBackgroundState = (state) => {
                particleState = state;
                if (state === 'scatter') {
                    mat.color.setHex(0x7f00ff); // Shift to energetic purple when talking
                } else {
                    mat.color.setHex(0x00f2fe);
                }
            };

            const animate = () => {
                requestAnimationFrame(animate);
                
                rotY += 0.008;
                points.rotation.y = rotY;

                // Simple particle pulse based on state
                if (particleState === 'scatter') {
                    const scale = 1.0 + Math.sin(Date.now() * 0.015) * 0.15;
                    points.scale.setScalar(scale);
                } else {
                    points.scale.setScalar(1.0);
                }

                renderer.render(scene, camera);
            };
            animate();
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
