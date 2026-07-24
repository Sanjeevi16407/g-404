<?php
/**
 * Student Portal - Buddy AR Live Campus Guide (Buddy Live Vision)
 * Clean, minimal AR assistant using pure device sensors (GPS & Compass).
 * Displays only the requested destination marker with voice guidance.
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
        background: rgba(13, 18, 35, 0.72);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid #10b981;
        box-shadow: 0 0 25px rgba(16, 185, 129, 0.45);
        color: #fff;
        padding: 12px 20px;
        border-radius: 18px;
        transition: left 0.1s ease-out, top 0.1s ease-out, transform 0.2s ease-out;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        font-family: inherit;
        z-index: 5;
    }

    .ar-icon {
        font-size: 1.4rem;
        color: #10b981;
        filter: drop-shadow(0 0 4px #10b981);
    }

    .ar-title {
        font-weight: 700;
        font-size: 1rem;
    }

    .ar-dist {
        font-size: 0.85rem;
        opacity: 0.9;
        font-weight: 600;
    }

    .ar-arrow {
        font-size: 1.2rem;
        color: #10b981;
    }

    /* Screen edge indicators for off-screen blocks */
    .ar-edge-indicator {
        position: absolute;
        pointer-events: auto;
        background: rgba(13, 18, 35, 0.75);
        border: 1px solid rgba(0, 242, 254, 0.4);
        padding: 8px 16px;
        border-radius: 12px;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
        z-index: 4;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: var(--box-shadow);
        cursor: pointer;
    }

    /* Debugging Card (Developer Panel - clean and minimal) */
    .dev-debug-card {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 10;
        width: 250px;
        background: rgba(13, 18, 35, 0.7);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 12px;
        color: #fff;
        font-size: 0.75rem;
        font-family: monospace;
        pointer-events: auto;
        box-shadow: var(--box-shadow);
    }

    .debug-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 4px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        padding-bottom: 2px;
    }

    .debug-val {
        color: var(--glow-primary);
        font-weight: bold;
    }



    .dev-debug-card.collapsed {
        max-height: 40px;
        overflow: hidden;
    }

    .debug-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        padding-bottom: 6px;
        font-weight: 700;
        font-size: 0.85rem;
        color: #ef4444;
        cursor: pointer;
        user-select: none;
        margin-bottom: 6px;
    }

    /* Floating Transparent Chat Panel overlay */
    .ar-chat-panel {
        position: absolute;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 48px);
        max-width: 650px;
        background: rgba(13, 18, 35, 0.72);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.12);
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

    .ar-chat-text::-webkit-scrollbar {
        width: 4px;
    }

    .ar-chat-text::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
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
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
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
            <!-- Requested destination card is injected here dynamically -->
        </div>



        <!-- Dev Debug Card (Top-right corner, compact) -->
        <div class="dev-debug-card" id="debug-card">
            <div class="debug-header" onclick="toggleDebugCollapse()">
                <span>⚙️ Debug Sensors</span>
                <i class="fa-solid fa-chevron-down" id="debug-collapse-icon" style="font-size: 0.7rem; color: #ef4444;"></i>
            </div>
            <div id="debug-card-body" style="display: none;">
                <div class="debug-row"><span>Status</span><span class="debug-val" id="dbg-status">Searching...</span></div>
                <div class="debug-row"><span>Latitude</span><span class="debug-val" id="dbg-lat">-</span></div>
                <div class="debug-row"><span>Longitude</span><span class="debug-val" id="dbg-lng">-</span></div>
                <div class="debug-row"><span>Accuracy</span><span class="debug-val" id="dbg-acc">-</span></div>
                <div class="debug-row"><span>Heading</span><span class="debug-val" id="dbg-heading">-</span></div>
                <div class="debug-row"><span>Nearest</span><span class="debug-val" id="dbg-nearest">-</span></div>
                <div class="debug-row"><span>Target</span><span class="debug-val" id="dbg-target">None</span></div>
                <div class="debug-row"><span>Distance</span><span class="debug-val" id="dbg-dist">-</span></div>
            </div>
        </div>

        <!-- Floating Transparent Chat Panel -->
        <div class="ar-chat-panel">
            <div class="ar-buddy-canvas-container">
                <canvas id="ar-buddy-canvas" class="ar-buddy-canvas"></canvas>
            </div>
            
            <div class="ar-chat-content">
                <div class="ar-chat-text" id="ar-voice-bubble">
                    👋 Ask me where any building is (e.g., *"Where is RV Block?"*), and I will guide you there in real time!
                </div>
                
                <div class="ar-chat-actions">
                    <div class="ar-status-text">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span class="status-dot-active" id="listening-indicator"></span>
                            <span id="listening-text">Buddy ready</span>
                        </div>
                        <div id="gps-status-indicator" style="font-size: 0.7rem; opacity: 0.85; display: flex; align-items: center; gap: 4px; color: var(--text-secondary);">
                            <i class="fa-solid fa-location-crosshairs"></i> GPS: Initializing...
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button class="btn-ar btn-ar-stop" onclick="stopARVision()"><i class="fa-solid fa-circle-stop"></i> Close</button>
                        <button class="btn-ar btn-ar-nav" id="navigate-btn" style="display: none;" onclick="navigateToSelection()"><i class="fa-solid fa-location-arrow"></i> 3D Map</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    // College building database
    const locations = {
        main_gate: { name: "Main Gate", coords: [10.753976, 78.652241], icon: "fa-solid fa-door-open", keywords: ["main gate", "gate", "entrance", "nuzhaiyil"] },
        parking: { name: "Main Parking", coords: [10.754449, 78.652613], icon: "fa-solid fa-square-parking", keywords: ["parking", "main parking", "two wheeler", "four wheeler", "bike parking"] },
        football_ground: { name: "Football Ground", coords: [10.754952, 78.652247], icon: "fa-solid fa-circle-play", keywords: ["football", "football ground", "soccer"] },
        ground_main: { name: "Main Ground", coords: [10.755983, 78.650219], icon: "fa-solid fa-circle-play", keywords: ["main ground", "ground", "sports ground", "play ground"] },
        ks_block: { name: "KS Block", coords: [10.755848, 78.651437], icon: "fa-solid fa-microchip", keywords: ["ks block", "ks", "kamaraj block", "eee", "ece"] },
        rv_block: { name: "RV Block", coords: [10.756346, 78.651692], icon: "fa-solid fa-laptop-code", keywords: ["rv block", "rv", "computer science", "cse block", "it block"] },
        js_block: { name: "JS Block", coords: [10.756776, 78.651475], icon: "fa-solid fa-building-columns", keywords: ["js block", "js", "jeyaram block", "civil block", "aids"] },
        canteen: { name: "Canteen", coords: [10.756991, 78.650814], icon: "fa-solid fa-utensils", keywords: ["canteen", "saapaadu", "mess", "hotel", "food"] },
        bd_block: { name: "BD Block", coords: [10.757188, 78.651255], icon: "fa-solid fa-building-columns", keywords: ["bd block", "bd", "mba block", "administration"] },
        girls_mess: { name: "Girls' Mess", coords: [10.757191, 78.650665], icon: "fa-solid fa-utensils", keywords: ["girls mess", "girls' mess", "ladies mess"] },
        mech_block: { name: "Mechanical Block", coords: [10.757420, 78.650594], icon: "fa-solid fa-gears", keywords: ["mechanical block", "mech block", "mech"] },
        bus_parking: { name: "Bus Parking", coords: [10.757707, 78.651126], icon: "fa-solid fa-bus", keywords: ["bus parking", "bus stand", "bus depot", "bus yard"] },
        hostel: { name: "Boys' Hostel", coords: [10.758197, 78.650906], icon: "fa-solid fa-hotel", keywords: ["hostel", "boys hostel", "boys' hostel", "mens hostel"] },
        tennis_ground: { name: "Tennis Ground", coords: [10.755788, 78.652249], icon: "fa-solid fa-circle-play", keywords: ["tennis ground", "tennis court", "tennis"] }
    };

    let userLat = 0;
    let userLng = 0;
    let compassHeading = 0; // 0 = North, 90 = East, 180 = South, 270 = West
    let currentNavTarget = null; // Key of locations database (starts null/empty!)
    let gpsAccuracy = 0;
    let gpsStatus = "Searching...";
    let speakOutput = true;
    let hasAnnouncedArrival = false;

    // WebRTC Camera Feed handler
    async function startCamera() {
        const video = document.getElementById('camera-feed');
        if (!video) return;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });
            video.srcObject = stream;
        } catch (err) {
            console.warn("Back camera media stream failed, trying any default video input...", err);
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
            } catch (fallbackErr) {
                console.error("Camera access denied.", fallbackErr);
                document.getElementById('ar-voice-bubble').innerHTML = "⚠️ **Camera Feed Blocked**: Please grant camera access permissions to proceed.";
            }
        }
    }

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

    // Update Debugging Card Info
    function updateDebugCard() {
        document.getElementById('dbg-status').innerText = gpsStatus;
        if (gpsStatus === "Active") {
            document.getElementById('dbg-status').style.color = "#10b981";
        } else {
            document.getElementById('dbg-status').style.color = "#ef4444";
        }
        
        document.getElementById('dbg-lat').innerText = userLat ? userLat.toFixed(6) : "-";
        document.getElementById('dbg-lng').innerText = userLng ? userLng.toFixed(6) : "-";
        document.getElementById('dbg-acc').innerText = gpsAccuracy ? `${Math.round(gpsAccuracy)} m` : "-";
        document.getElementById('dbg-heading').innerText = `${Math.round(compassHeading)}°`;
        
        // Find nearest building
        let nearestName = "-";
        let nearestDist = Infinity;
        if (userLat && userLng) {
            Object.keys(locations).forEach(key => {
                const loc = locations[key];
                const d = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);
                if (d < nearestDist) {
                    nearestDist = d;
                    nearestName = loc.name;
                }
            });
        }
        
        if (nearestDist !== Infinity) {
            document.getElementById('dbg-nearest').innerText = `${nearestName} (${Math.round(nearestDist)}m)`;
        } else {
            document.getElementById('dbg-nearest').innerText = "-";
        }
        
        if (currentNavTarget && locations[currentNavTarget]) {
            const loc = locations[currentNavTarget];
            const dist = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);
            document.getElementById('dbg-target').innerText = loc.name;
            document.getElementById('dbg-dist').innerText = `${Math.round(dist)} m`;
        } else {
            document.getElementById('dbg-target').innerText = "None";
            document.getElementById('dbg-dist').innerText = "-";
        }
    }

    // Update AR elements positioning based on GPS and Compass
    function updateAROverlay() {
        const overlay = document.getElementById('ar-overlay');
        if (!overlay) return;

        // Clear existing overlays
        overlay.innerHTML = "";

        if (!userLat || !userLng) {
            document.getElementById('navigate-btn').style.display = 'none';
            return;
        }

        // Show/hide 3D Map navigation button based on whether a target is active
        if (currentNavTarget && locations[currentNavTarget]) {
            document.getElementById('navigate-btn').style.display = 'inline-flex';
        } else {
            document.getElementById('navigate-btn').style.display = 'none';
        }

        const fovHorizontal = 80; // Field of View angle threshold in degrees
        const offScreenLocs = [];

        Object.keys(locations).forEach(key => {
            const loc = locations[key];
            const dist = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);
            const bearing = calculateBearing(userLat, userLng, loc.coords[0], loc.coords[1]);

            // Relative bearing to current device rotation
            let relativeBearing = bearing - compassHeading;
            relativeBearing = (relativeBearing + 180) % 360 - 180; // range [-180, 180]

            const isTarget = currentNavTarget === key;

            // If the target is inside the viewport Field of View
            if (Math.abs(relativeBearing) <= fovHorizontal / 2) {
                const xPct = 50 + (relativeBearing / (fovHorizontal / 2)) * 50;
                
                // Stagger markers vertically depending on distance to make overlaps less severe
                let yPct = 45;
                const distOffset = Math.min(dist / 200, 1) * 25; // 0 to 25% shift
                yPct = yPct - 12 + distOffset + (Math.sin(bearing * 10) * 8);

                const isFacingTarget = Math.abs(relativeBearing) <= 6;
                const scale = isTarget ? 1.15 : Math.max(0.7, 1 - (dist / 350));
                const opacity = isTarget ? 1.0 : Math.max(0.45, 1 - (dist / 300));
                const borderCol = isTarget ? '#10b981' : 'rgba(0, 242, 254, 0.4)';
                const glowShadow = isTarget ? '0 0 20px rgba(16, 185, 129, 0.4)' : '0 0 10px rgba(0, 242, 254, 0.15)';

                const card = document.createElement('div');
                card.className = `ar-label-card`;
                card.style.left = `${xPct}%`;
                card.style.top = `${yPct}%`;
                card.style.opacity = opacity;
                card.style.transform = `translate(-50%, -50%) scale(${scale})`;
                card.style.border = `1px solid ${borderCol}`;
                card.style.boxShadow = glowShadow;

                // Clicking a card focuses it as the nav target!
                card.onclick = () => {
                    currentNavTarget = key;
                    hasAnnouncedArrival = false;
                    updateDebugCard();
                    updateAROverlay();
                };

                card.innerHTML = `
                    <i class="${loc.icon} ar-icon" style="color: ${isTarget ? '#10b981' : 'var(--glow-primary)'}"></i>
                    <span class="ar-title">${loc.name}</span>
                    <span class="ar-dist">${Math.round(dist)} m</span>
                    <span class="ar-arrow">${isFacingTarget ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-arrow-up"></i>'}</span>
                    ${isTarget && isFacingTarget ? '<span class="text-[0.65rem] text-emerald-400 font-bold">✓ Straight Ahead</span>' : ''}
                `;
                overlay.appendChild(card);
            } else {
                // Collect off-screen locations
                offScreenLocs.push({ key, loc, dist, relativeBearing, isTarget });
            }
        });

        // Sort off-screen locations by distance
        offScreenLocs.sort((a, b) => a.dist - b.dist);

        // Render off-screen edge indicators for target + nearest 3 off-screen locations
        let indicatorsRendered = 0;
        offScreenLocs.forEach(item => {
            const shouldRender = item.isTarget || (indicatorsRendered < 3);
            if (shouldRender) {
                const directionArrow = item.relativeBearing < 0 ? '←' : '→';
                const sideClass = item.relativeBearing < 0 ? 'left' : 'right';
                
                const indicator = document.createElement('div');
                indicator.className = `ar-edge-indicator`;
                
                // Vertical spacing for multiple indicators on the same side
                const verticalOffset = 30 + (indicatorsRendered * 12);
                indicator.style.top = `${verticalOffset}%`;
                
                if (item.isTarget) {
                    indicator.style.border = '1px solid #10b981';
                    indicator.style.boxShadow = '0 0 10px rgba(16, 185, 129, 0.3)';
                }

                if (sideClass === 'left') {
                    indicator.style.left = '16px';
                    indicator.innerHTML = `<span>${directionArrow} ${item.loc.name} (${Math.round(item.dist)}m)</span>`;
                } else {
                    indicator.style.right = '16px';
                    indicator.innerHTML = `<span>${item.loc.name} (${Math.round(item.dist)}m) ${directionArrow}</span>`;
                }

                indicator.onclick = () => {
                    currentNavTarget = item.key;
                    hasAnnouncedArrival = false;
                    updateDebugCard();
                    updateAROverlay();
                };

                overlay.appendChild(indicator);
                indicatorsRendered++;
            }
        });
    }

    // Check if user has arrived at target destination
    function checkArrival() {
        if (!currentNavTarget || !locations[currentNavTarget] || !userLat || !userLng || hasAnnouncedArrival) return;

        const loc = locations[currentNavTarget];
        const dist = calculateDistance(userLat, userLng, loc.coords[0], loc.coords[1]);

        if (dist <= 10) {
            hasAnnouncedArrival = true;
            const arrivalText = `🎉 You have arrived at **${loc.name}**! Would you like to navigate somewhere else?`;
            
            document.getElementById('ar-voice-bubble').innerHTML = arrivalText;
            if (speakOutput) {
                speakAROutput(`You have arrived at ${loc.name}. Would you like to navigate somewhere else?`);
            }
            
            // Reset target after arrival
            currentNavTarget = null;
        }
    }

    // GPS Geolocation Sensor tracking
    function initGPS() {
        if ('geolocation' in navigator) {
            navigator.geolocation.watchPosition(
                (pos) => {
                    userLat = pos.coords.latitude;
                    userLng = pos.coords.longitude;
                    gpsAccuracy = pos.coords.accuracy;
                    gpsStatus = "Active";

                    // Update UI status row indicator
                    const accText = gpsAccuracy ? `(${Math.round(gpsAccuracy)}m accuracy)` : "";
                    document.getElementById('gps-status-indicator').innerHTML = 
                        `<span style="color: #10b981;"><i class="fa-solid fa-location-dot"></i> GPS: Active ${accText}</span>`;

                    updateDebugCard();
                    updateAROverlay();
                    checkArrival();
                },
                (err) => {
                    let errMsg = "Error";
                    if (err.code === 1) errMsg = "Permission Denied";
                    else if (err.code === 2) errMsg = "Position Unavailable";
                    else if (err.code === 3) errMsg = "Timeout";
                    
                    gpsStatus = "Error: " + errMsg;
                    document.getElementById('gps-status-indicator').innerHTML = 
                        `<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> GPS: ${errMsg}</span>`;
                    
                    updateDebugCard();
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 0,
                    timeout: 10000
                }
            );
        } else {
            gpsStatus = "Not Supported";
            document.getElementById('gps-status-indicator').innerHTML = 
                `<span style="color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> GPS Not Supported</span>`;
            updateDebugCard();
        }
    }

    // Compass device sensors (iOS / Android support)
    function initCompass() {
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
        let heading = event.webkitCompassHeading || event.alpha;
        if (heading !== undefined) {
            compassHeading = 360 - heading; // Mirror logic for camera viewport
            updateDebugCard();
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

    // Continuous voice recognition
    let arRecognition;
    let arListening = false;

    function initARSpeechRecognition() {
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            arRecognition = new SpeechRecognition();
            arRecognition.continuous = true;
            arRecognition.interimResults = false;
            arRecognition.lang = 'en-IN'; // Indian English accent context

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
                
                // Restart listening automatically
                if (window.location.pathname.includes('vision.php')) {
                    setTimeout(() => {
                        try { arRecognition.start(); } catch(e) {}
                    }, 500);
                }
            };

            arRecognition.start();
        }
    }

    // Match query keywords to set destination targets
    function detectTargetDestination(query) {
        const queryLower = query.toLowerCase();
        let matchedKey = null;

        Object.keys(locations).forEach(key => {
            const loc = locations[key];
            loc.keywords.forEach(keyword => {
                if (queryLower.includes(keyword)) {
                    matchedKey = key;
                }
            });
        });

        return matchedKey;
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
            const cleanText = replyText.replace(/\*\*|\*/g, '');
            document.getElementById('ar-voice-bubble').innerHTML = replyText;

            // Detect target destination keywords
            const matchedTarget = detectTargetDestination(query);
            if (matchedTarget) {
                currentNavTarget = matchedTarget;
                hasAnnouncedArrival = false; // Reset arrival triggers for new target
                updateDebugCard();
                updateAROverlay();
            }

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

    // Toggle Sensor Debug collapse/expand
    function toggleDebugCollapse() {
        const card = document.getElementById('debug-card');
        const body = document.getElementById('debug-card-body');
        const icon = document.getElementById('debug-collapse-icon');
        if (body.style.display === 'none') {
            body.style.display = 'block';
            card.classList.remove('collapsed');
            icon.className = "fa-solid fa-chevron-up";
        } else {
            body.style.display = 'none';
            card.classList.add('collapsed');
            icon.className = "fa-solid fa-chevron-down";
        }
    }

    // Initialize scripts
    document.addEventListener("DOMContentLoaded", () => {
        startCamera();
        initGPS();
        initCompass();
        initARSpeechRecognition();
        updateDebugCard();

        // Spawn Three.js 3D particles in the card
        if (typeof THREE !== 'undefined') {
            const canvas = document.getElementById('ar-buddy-canvas');
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(60, 1, 0.1, 1000);
            camera.position.z = 12;

            const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
            renderer.setSize(80, 80);

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
                    mat.color.setHex(0x7f00ff);
                } else {
                    mat.color.setHex(0x00f2fe);
                }
            };

            const animate = () => {
                requestAnimationFrame(animate);
                rotY += 0.008;
                points.rotation.y = rotY;

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
