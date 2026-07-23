<?php
/**
 * Student Portal - 3D Campus Navigator (Leaflet.js Edition - No Tokens Required)
 */
require_once __DIR__ . '/includes/header.php';

// Advance journey step if campus guide is completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_campus'])) {
    if ($current_step === 'campus') {
        $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'faculty' WHERE student_id = ?");
        $stmt->execute([$student_id]);

        // Add badge: "Campus Explorer"
        try {
            $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'Campus Explorer', 'fa-solid fa-map-location-dot')");
            $badge_stmt->execute([$student_id]);
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: Campus Explorer badge earned!')");
            $notif_stmt->execute([$student_id]);
        } catch (PDOException $e) {}

        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit;
    }
}
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div class="page-title">🗺️ Campus Navigator</div>
    <div style="font-size: 0.9rem; color: var(--text-secondary);">Interactive Satellite Map & Real-time Buddy AI Directions (No Keys Required)</div>
</div>

<!-- Load Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    .navigator-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 24px;
        height: calc(100vh - 180px);
        min-height: 550px;
    }

    #map {
        width: 100%;
        height: 100%;
        border-radius: 16px;
        border: 1px solid var(--border-glass);
        overflow: hidden;
        position: relative;
        z-index: 1; /* Keep map below overlays */
    }

    /* Apply futuristic dark satellite filters for space theme compatibility */
    [data-theme="Dark"] #map, [data-theme="Spatial"] #map, [data-theme="Liquid Glass"] #map {
        filter: brightness(0.7) contrast(1.15) saturate(1.1) hue-rotate(350deg);
    }

    .location-item {
        cursor: pointer;
        padding: 12px 16px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-light);
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .location-item:hover, .location-item.active {
        background: rgba(0, 242, 254, 0.08);
        border-color: var(--glow-primary);
        transform: translateX(4px);
    }

    /* Pulsing Leaflet glow marker ring */
    .pulsing-glow-marker {
        width: 24px;
        height: 24px;
        border: 3px solid #00f2fe;
        border-radius: 50%;
        background: rgba(0, 242, 254, 0.35);
        box-shadow: 0 0 15px #00f2fe, inset 0 0 10px #00f2fe;
        animation: pulse-ring 1.3s infinite ease-out;
    }
    @keyframes pulse-ring {
        0% { transform: scale(0.5); opacity: 1; }
        100% { transform: scale(2.2); opacity: 0; }
    }

    /* Leaflet DivIcon center point styling */
    .custom-div-icon {
        background: none;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Custom Mapbox Info card overlay */
    .map-info-card {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 320px;
        z-index: 1000; /* Float above Leaflet map container */
        display: none;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        background: rgba(10, 15, 30, 0.85);
        border: 1px solid var(--border-glass);
        border-radius: 14px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        overflow: hidden;
        animation: slideInDown 0.3s ease;
    }

    /* Buddy floating drawer style */
    .buddy-nav-drawer {
        position: absolute;
        bottom: 20px;
        right: 20px;
        width: 340px;
        z-index: 1000; /* Float above Leaflet map container */
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        background: rgba(10, 15, 30, 0.85);
        border: 1px solid var(--border-glass);
        border-radius: 14px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .buddy-nav-drawer.minimized {
        height: 52px;
        overflow: hidden;
    }

    .buddy-drawer-header {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        background: rgba(255, 255, 255, 0.02);
    }

    .buddy-drawer-body {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        height: 280px;
    }

    .buddy-drawer-feed {
        flex-grow: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding-right: 4px;
    }

    .buddy-drawer-bubble {
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 0.85rem;
        line-height: 1.4;
        max-width: 85%;
    }
    .buddy-drawer-bubble-buddy {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--border-light);
        align-self: flex-start;
        border-bottom-left-radius: 2px;
        color: var(--text-primary);
    }
    .buddy-drawer-bubble-user {
        background: linear-gradient(135deg, var(--glow-primary), var(--glow-secondary));
        align-self: flex-end;
        border-bottom-right-radius: 2px;
        color: #ffffff;
    }

    .suggestion-chip {
        padding: 6px 12px;
        border-radius: 14px;
        background: rgba(255,255,255,0.03);
        border: 1px solid var(--border-light);
        font-size: 0.75rem;
        color: var(--glow-primary);
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .suggestion-chip:hover {
        background: rgba(0, 242, 254, 0.1);
        border-color: var(--glow-primary);
    }

    /* Style zoom controls of Leaflet */
    .leaflet-bar {
        border: 1px solid var(--border-glass) !important;
        box-shadow: none !important;
        border-radius: 8px !important;
        overflow: hidden;
    }
    .leaflet-bar a {
        background-color: rgba(10, 15, 30, 0.85) !important;
        color: var(--text-primary) !important;
        border-bottom: 1px solid var(--border-light) !important;
    }
    .leaflet-bar a:hover {
        background-color: var(--glow-primary) !important;
        color: #000000 !important;
    }

    @media (max-width: 820px) {
        .navigator-layout {
            grid-template-columns: 1fr;
            height: auto;
        }
        #map {
            height: 400px;
        }
        .map-info-card {
            position: relative;
            top: 0;
            right: 0;
            width: 100%;
            margin-top: 16px;
            display: none;
        }
        .buddy-nav-drawer {
            position: relative;
            bottom: 0;
            right: 0;
            width: 100%;
            margin-top: 16px;
        }
    }
</style>

<div class="navigator-layout">
    <!-- Sidebar Navigation List -->
    <div class="glass-panel" style="padding: 20px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">
            <i class="fa-solid fa-compass" style="color: var(--glow-primary); margin-right: 8px;"></i> Destinations
        </h3>

        <a href="vision.php" class="btn-glass" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; background: rgba(0, 242, 254, 0.1); border: 1px solid var(--glow-primary); color: var(--text-primary); transition: all 0.2s;" onmouseover="this.style.background='rgba(0, 242, 254, 0.2)'" onmouseout="this.style.background='rgba(0, 242, 254, 0.1)'">
            <i class="fa-solid fa-camera-retro" style="color: var(--glow-primary);"></i> Launch Buddy Live Vision
        </a>

        <input type="text" id="search-destinations" class="form-control" placeholder="Search classrooms, blocks..." style="font-size: 0.85rem; padding: 10px 14px;">

        <div id="destinations-list" style="display: flex; flex-direction: column; gap: 8px; flex-grow: 1; overflow-y: auto; max-height: 320px;">
            <!-- Filled dynamically by JavaScript -->
        </div>

        <!-- Complete Tour Progress Form -->
        <form method="POST" action="campus.php" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-light);">
            <input type="hidden" name="complete_campus" value="1">
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; padding: 12px; border-radius: 8px; font-size: 0.85rem;">
                <i class="fa-solid fa-circle-check" style="margin-right: 8px;"></i> Complete Tour & Claim Badge
            </button>
        </form>
    </div>

    <!-- Map Viewport Area -->
    <div style="position: relative; width: 100%; height: 100%;">
        <div id="map"></div>

        <!-- Glowing Information Card overlay -->
        <div class="map-info-card" id="location-info-card">
            <img id="info-card-photo" src="" alt="Location Photo" style="width: 100%; height: 140px; object-fit: cover; border-bottom: 1px solid var(--border-light);">
            <div style="padding: 16px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <h4 id="info-card-title" style="font-size: 1.1rem; font-weight: 700; color: var(--glow-primary);">Main Block</h4>
                    <button onclick="closeInfoCard()" style="background: none; border: none; color: var(--text-tertiary); cursor: pointer; font-size: 1rem;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-location-crosshairs"></i> <span id="info-card-details">Location details</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-clock"></i> <span id="info-card-hours">Open: Always</span>
                </div>
                <p id="info-card-desc" style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.4; margin-top: 6px;">
                    Description text goes here.
                </p>
            </div>
        </div>

        <!-- Floating Buddy Chat Drawer widget -->
        <div class="buddy-nav-drawer minimized" id="buddy-drawer">
            <div class="buddy-drawer-header" onclick="toggleBuddyDrawer()">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.1rem;">🤖</span>
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">Ask Buddy Directions</div>
                </div>
                <i class="fa-solid fa-chevron-up" id="buddy-drawer-icon" style="color: var(--text-secondary); font-size: 0.8rem; transition: transform 0.3s;"></i>
            </div>

            <div class="buddy-drawer-body">
                <div class="buddy-drawer-feed" id="buddy-drawer-feed">
                    <div class="buddy-drawer-bubble buddy-drawer-bubble-buddy">
                        Hey! Ask me where any block is located, and I'll fly you directly there! Try clicking the suggestions below.
                    </div>
                </div>

                <!-- Horizontal Suggestions Scroll -->
                <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none;">
                    <div class="suggestion-chip" onclick="askBuddyDrawer('Where is KS Block?')">Where is KS Block?</div>
                    <div class="suggestion-chip" onclick="askBuddyDrawer('Take me to RV Block')">Take me to RV Block</div>
                    <div class="suggestion-chip" onclick="askBuddyDrawer('Show canteen')">Show Canteen</div>
                    <div class="suggestion-chip" onclick="askBuddyDrawer('Navigate to BD and JS Block')">Navigate to BD and JS Block</div>
                    <div class="suggestion-chip" onclick="askBuddyDrawer('Where is Mech Block?')">Where is Mech Block?</div>
                </div>

                <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-light); padding-top: 10px; align-items: center;">
                    <input type="text" id="buddy-drawer-input" class="form-control" placeholder="Ask Buddy directions..." style="font-size: 0.8rem; padding: 8px 12px;" onkeypress="if(event.key === 'Enter') sendBuddyDrawerMessage();">
                    <button onclick="sendBuddyDrawerMessage()" class="btn-glass btn-primary" style="padding: 8px 12px; border-radius: 8px; font-size: 0.8rem;"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const locations = {
        main_gate: {
            name: "Main Gate",
            coords: [10.7548, 78.6524], // Leaflet uses [lat, lng]
            details: "College Entrance Gate",
            hours: "24 Hours Open",
            description: "The primary entry and exit gate of Saranathan College of Engineering on the Trichy-Madurai Highway. Security checks and visitor registrations are handled here.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-door-open"
        },
        parking: {
            name: "Parking Area",
            coords: [10.7552, 78.6521],
            details: "Visitor & Student Parking Lot",
            hours: "7:00 AM - 7:00 PM",
            description: "Secure parking space for two-wheelers and four-wheelers located right near the main entrance highway road.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-square-parking"
        },
        ks_block: {
            name: "KS Block",
            coords: [10.7562, 78.6511],
            details: "Kamaraj Academic Block",
            hours: "8:30 AM - 6:00 PM",
            description: "Contains classrooms and state-of-the-art laboratories for EEE, ECE, and ICE departments.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-microchip"
        },
        rv_block: {
            name: "RV Block",
            coords: [10.7565, 78.6517],
            details: "Shri R.V. Block (CSE & IT)",
            hours: "8:30 AM - 6:00 PM",
            description: "Major block housing class sessions, advanced programming labs for CSE and IT departments, faculty cabins, and the main office.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-laptop-code"
        },
        bd_js_block: {
            name: "JS and BD Block",
            coords: [10.7572, 78.6513],
            details: "JS & BD Academic Block",
            hours: "8:30 AM - 6:00 PM",
            description: "Academic building housing classrooms and research labs for Civil Engineering, AI&DS, and CSBS departments.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-building-columns"
        },
        canteen: {
            name: "Canteen",
            coords: [10.7570, 78.6508],
            details: "Main Dining & Cafeteria",
            hours: "8:00 AM - 4:30 PM",
            description: "Serves hygiene vegetarian meals, hot snacks, tea, and beverages, featuring separate dining halls for students and faculty.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-utensils"
        },
        staff_parking: {
            name: "Staff Parking",
            coords: [10.7577, 78.6510],
            details: "Faculty Vehicle Parking Lot",
            hours: "7:30 AM - 6:30 PM",
            description: "Exclusive reserved parking spaces for staff members, teachers, and directors.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-car"
        },
        mech_block: {
            name: "Mech Block",
            coords: [10.7577, 78.6505],
            details: "Mechanical Engineering Block",
            hours: "8:30 AM - 6:00 PM",
            description: "Dedicated to the Mechanical Engineering department, housing advanced thermodynamic, design, and CAD labs.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-gears"
        },
        bus_parking: {
            name: "Bus Parking",
            coords: [10.7582, 78.6507],
            details: "College Bus Transit Depot",
            hours: "7:30 AM - 9:00 AM, 4:00 PM - 6:00 PM",
            description: "Transit zone where college buses arrive and park, connecting students to various zones of Tiruchirappalli.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-bus"
        },
        hostel: {
            name: "Boys Hostel",
            coords: [10.7588, 78.6504],
            details: "Student Hostels & Residence",
            hours: "Residence Facility",
            description: "Residential hostels for male students, complete with study halls, sports grounds, and dining mess operations.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-hotel"
        },
        ground_main: {
            name: "Main Ground",
            coords: [10.7570, 78.6499],
            details: "Sports & Athletic Field",
            hours: "Open Play",
            description: "Large open playground for cricket, football, athletic practice, and annual collegiate sports events.",
            photo: "assets/images/default-campus.jpg",
            icon: "fa-solid fa-circle-play"
        }
    };

    let map;
    let activeMarker = null;

    document.addEventListener('DOMContentLoaded', () => {
        renderDestinationsList();

        // Initialize Free Leaflet Satellite Map (ArcGIS Esri Satellite Imagery - No tokens required!)
        map = L.map('map', {
            center: [10.7568, 78.6510],
            zoom: 17.5,
            zoomControl: true
        });

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19
        }).addTo(map);

        // Click listener to print coordinates in developer console for fine-tuning
        map.on('click', (e) => {
            console.log(`Clicked Coordinate: [${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}]`);
        });

        // Add standard scale indicator
        L.control.scale().addTo(map);

        // If URL parameter goto/fly is present
        const urlParams = new URLSearchParams(window.location.search);
        const flyTarget = urlParams.get('fly') || urlParams.get('goto');
        if (flyTarget && locations[flyTarget]) {
            setTimeout(() => {
                flyToLocation(flyTarget);
            }, 1200);
        }
    });

    // Render Destinations in Sidebar
    function renderDestinationsList(filter = '') {
        const list = document.getElementById('destinations-list');
        list.innerHTML = '';
        
        Object.keys(locations).forEach(key => {
            const loc = locations[key];
            if (filter && !loc.name.toLowerCase().includes(filter.toLowerCase())) return;

            const item = document.createElement('div');
            item.className = 'location-item';
            item.id = `sidebar-loc-${key}`;
            item.innerHTML = `
                <div style="background: rgba(255,255,255,0.05); width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--glow-primary);">
                    <i class="${loc.icon}"></i>
                </div>
                <div>
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-primary);">${loc.name}</div>
                    <div style="font-size: 0.7rem; color: var(--text-secondary);">${loc.details}</div>
                </div>
            `;
            item.onclick = () => flyToLocation(key);
            list.appendChild(item);
        });
    }

    // Live search destinations filter
    document.getElementById('search-destinations').addEventListener('input', (e) => {
        renderDestinationsList(e.target.value);
    });

    // Fly camera coordinates & place pulsing halo ring
    function flyToLocation(key) {
        if (!locations[key]) return;
        const loc = locations[key];

        // 1. Smooth flyTo camera panning
        map.flyTo(loc.coords, 18, {
            animate: true,
            duration: 1.5
        });

        // 2. Remove existing active marker highlight
        if (activeMarker) {
            map.removeLayer(activeMarker);
        }

        document.querySelectorAll('.location-item').forEach(el => el.classList.remove('active'));
        const sidebarEl = document.getElementById(`sidebar-loc-${key}`);
        if (sidebarEl) {
            sidebarEl.classList.add('active');
            sidebarEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // 3. Add custom Leaflet pulsing neon DivIcon marker
        const pulsingIcon = L.divIcon({
            html: '<div class="pulsing-glow-marker"></div>',
            className: 'custom-div-icon',
            iconSize: [24, 24]
        });

        activeMarker = L.marker(loc.coords, { icon: pulsingIcon }).addTo(map);

        // 4. Open Glassmorphism Overlay info card
        openInfoCard(loc);
    }

    function openInfoCard(loc) {
        const card = document.getElementById('location-info-card');
        document.getElementById('info-card-photo').src = '../' + loc.photo;
        document.getElementById('info-card-title').textContent = loc.name;
        document.getElementById('info-card-details').textContent = loc.details;
        document.getElementById('info-card-hours').textContent = loc.hours;
        document.getElementById('info-card-desc').textContent = loc.description;
        card.style.display = 'block';
    }

    function closeInfoCard() {
        document.getElementById('location-info-card').style.display = 'none';
    }

    // Buddy drawer UI controls
    function toggleBuddyDrawer() {
        const drawer = document.getElementById('buddy-drawer');
        const icon = document.getElementById('buddy-drawer-icon');
        if (drawer.classList.contains('minimized')) {
            drawer.classList.remove('minimized');
            icon.style.transform = 'rotate(180deg)';
        } else {
            drawer.classList.add('minimized');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // Ask Buddy helper
    function askBuddyDrawer(query) {
        document.getElementById('buddy-drawer-input').value = query;
        sendBuddyDrawerMessage();
    }

    function sendBuddyDrawerMessage() {
        const input = document.getElementById('buddy-drawer-input');
        const query = input.value.trim();
        if (!query) return;

        const drawer = document.getElementById('buddy-drawer');
        if (drawer.classList.contains('minimized')) {
            drawer.classList.remove('minimized');
            document.getElementById('buddy-drawer-icon').style.transform = 'rotate(180deg)';
        }

        appendBuddyDrawerBubble(query, 'user');
        input.value = '';

        const formData = new FormData();
        formData.append('query', query);

        fetch('../api/buddy.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const answer = data.answer || "I'm not sure about that path. Let's ask security!";
            appendBuddyDrawerBubble(answer, 'buddy');

            // Play voice output if speech is available
            if ('speechSynthesis' in window) {
                const speech = new SpeechSynthesisUtterance(answer.replace(/[^\w\s.,?!]/g, ''));
                speech.rate = 1.0;
                window.speechSynthesis.speak(speech);
            }

            // Keyword analysis to center the map dynamically
            const lowerAns = answer.toLowerCase();
            const lowerQue = query.toLowerCase();
            
            let matchedKey = null;
            if (lowerAns.includes('library') || lowerQue.includes('library')) matchedKey = 'library';
            else if (lowerAns.includes('canteen') || lowerQue.includes('canteen')) matchedKey = 'canteen';
            else if (lowerAns.includes('rv block') || lowerQue.includes('rv') || lowerAns.includes('rv')) matchedKey = 'rv_block';
            else if (lowerAns.includes('js block') || lowerQue.includes('js') || lowerAns.includes('js')) matchedKey = 'js_block';
            else if (lowerAns.includes('admin') || lowerQue.includes('admin')) matchedKey = 'admin_block';
            else if (lowerAns.includes('gate') || lowerQue.includes('gate') || lowerAns.includes('entrance') || lowerQue.includes('entrance')) matchedKey = 'main_gate';
            else if (lowerAns.includes('auditorium') || lowerQue.includes('auditorium') || lowerAns.includes('seminar hall') || lowerQue.includes('seminar')) matchedKey = 'auditorium';
            else if (lowerAns.includes('bus') || lowerQue.includes('bus') || lowerAns.includes('transport') || lowerQue.includes('transport')) matchedKey = 'bus_stop';
            else if (lowerAns.includes('parking') || lowerQue.includes('parking')) matchedKey = 'parking';
            else if (lowerAns.includes('hostel') || lowerQue.includes('hostel')) matchedKey = 'hostel';

            if (matchedKey) {
                flyToLocation(matchedKey);
            }
        })
        .catch(() => {
            appendBuddyDrawerBubble("Oops, I lost connection to my senior brain network!", 'buddy');
        });
    }

    function appendBuddyDrawerBubble(text, sender) {
        const feed = document.getElementById('buddy-drawer-feed');
        const bubble = document.createElement('div');
        bubble.className = `buddy-drawer-bubble buddy-drawer-bubble-${sender}`;
        bubble.textContent = text;
        feed.appendChild(bubble);
        feed.scrollTop = feed.scrollHeight;
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
