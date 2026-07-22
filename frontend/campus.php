<?php
/**
 * Student Portal - 3D Campus Navigator
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

// Fetch Mapbox token with robust version-independent self-healing migration
$mapbox_token = '';
try {
    $buddy_settings = $db->query("SELECT mapbox_token FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
    $mapbox_token = $buddy_settings['mapbox_token'] ?? '';
} catch (PDOException $e) {
    if ($e->getCode() == '42S22' || strpos($e->getMessage(), '1054') !== false) {
        try {
            $db->exec("ALTER TABLE buddy_settings ADD COLUMN mapbox_token VARCHAR(255) DEFAULT NULL");
            $buddy_settings = $db->query("SELECT mapbox_token FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
            $mapbox_token = $buddy_settings['mapbox_token'] ?? '';
        } catch (PDOException $ex) {}
    }
}
if (empty($mapbox_token)) {
    $mapbox_token = getenv('MAPBOX_TOKEN') ?? '';
}

// Check if user submitted a temporary token for session testing
if (isset($_POST['temp_mapbox_token'])) {
    $temp_token = sanitize_input($_POST['temp_mapbox_token']);
    if (!empty($temp_token)) {
        // Save to DB for persistence
        try {
            $up_stmt = $db->prepare("UPDATE buddy_settings SET mapbox_token = ? WHERE id = 1");
            $up_stmt->execute([$temp_token]);
            $mapbox_token = $temp_token;
            echo "<script>window.location.href = 'campus.php';</script>";
            exit;
        } catch (PDOException $e) {}
    }
}
?>

<div class="page-header" style="margin-bottom: 20px;">
    <div class="page-title">🗺️ 3D Campus Navigator</div>
    <div style="font-size: 0.9rem; color: var(--text-secondary);">Interactive 3D Satellite Map & Real-time Buddy AI Directions</div>
</div>

<?php if (empty($mapbox_token)): ?>
    <!-- Mapbox Token Missing Warning Configuration Card -->
    <div class="glass-panel" style="max-width: 600px; margin: 40px auto; padding: 36px; text-align: center; display: flex; flex-direction: column; gap: 20px;">
        <div style="font-size: 3.5rem; color: var(--glow-primary); animation: pulse 2s infinite ease-out;">🗺️</div>
        <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">Mapbox Access Token Required</h3>
        <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">
            To render the satellite maps and 3D buildings, the campus navigator requires a valid Mapbox Access Token.
        </p>
        
        <form method="POST" action="campus.php" style="display: flex; flex-direction: column; gap: 16px; margin-top: 10px;">
            <div class="form-group" style="text-align: left;">
                <label class="form-label" style="font-weight: 600;">Paste Mapbox Access Token</label>
                <input type="password" name="temp_mapbox_token" class="form-control" placeholder="pk.eyJ1I..." required style="background: rgba(0,0,0,0.2);">
            </div>
            <button type="submit" class="btn-glass btn-primary" style="padding: 12px; border-radius: 8px; width: 100%;">
                <i class="fa-solid fa-key" style="margin-right: 8px;"></i> ACTIVATE NAVIGATOR
            </button>
        </form>
        
        <p style="font-size: 0.8rem; color: var(--text-tertiary);">
            Don't have a token? Get one for free at <a href="https://mapbox.com" target="_blank" style="color: var(--glow-primary); text-decoration: underline;">mapbox.com</a>
        </p>
    </div>
<?php else: ?>

    <!-- Load Mapbox CSS and JS -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.1.2/mapbox-gl.js"></script>

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

        /* Pulsing Glow marker style */
        .pulsing-glow-marker {
            width: 32px;
            height: 32px;
            border: 3px solid #00f2fe;
            border-radius: 50%;
            background: rgba(0, 242, 254, 0.2);
            box-shadow: 0 0 15px #00f2fe, inset 0 0 10px #00f2fe;
            animation: pulse-ring 1.4s infinite ease-out;
            pointer-events: none;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.4); opacity: 1; }
            100% { transform: scale(2.0); opacity: 0; }
        }

        /* Custom Mapbox Info card overlay */
        .map-info-card {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 320px;
            z-index: 10;
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
            z-index: 10;
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

        <!-- 3D Map Viewport Area -->
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
                            Hey! Ask me where any block is located, and I'll fly you directly there in 3D! Try clicking the suggestions below.
                        </div>
                    </div>

                    <!-- Horizontal Suggestions Scroll -->
                    <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; scrollbar-width: none;">
                        <div class="suggestion-chip" onclick="askBuddyDrawer('Where is library?')">Where is Library?</div>
                        <div class="suggestion-chip" onclick="askBuddyDrawer('Take me to RV Block')">Take me to RV Block</div>
                        <div class="suggestion-chip" onclick="askBuddyDrawer('Show canteen')">Show Canteen</div>
                        <div class="suggestion-chip" onclick="askBuddyDrawer('Navigate to Admin Block')">Navigate to Admin Block</div>
                        <div class="suggestion-chip" onclick="askBuddyDrawer('Where to park?')">Where to park?</div>
                    </div>

                    <div style="display: flex; gap: 8px; border-top: 1px solid var(--border-light); padding-top: 10px; align-items: center;">
                        <input type="text" id="buddy-drawer-input" class="form-control" placeholder="Ask Buddy directions..." style="font-size: 0.8rem; padding: 8px 12px;" onkeypress="if(event.key === 'Enter') sendBuddyDrawerMessage();">
                        <button onclick="sendBuddyDrawerMessage()" class="btn-glass btn-primary" style="padding: 8px 12px; border-radius: 8px; font-size: 0.8rem;"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapbox Map Activation and Data binding -->
    <script>
        mapboxgl.accessToken = '<?php echo $mapbox_token; ?>';

        const locations = {
            main_gate: {
                name: "Main Gate",
                coords: [78.651586, 10.757302],
                details: "College Entrance Gate",
                hours: "24 Hours Open",
                description: "The primary entry and exit gate of Saranathan College of Engineering. Security checks and visitor registrations are handled here.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-door-open"
            },
            admin_block: {
                name: "Admin Block",
                coords: [78.651228, 10.756091],
                details: "Main Building, Ground & First Floor",
                hours: "9:00 AM - 5:00 PM",
                description: "Houses the offices of the Principal, Director, administrative staff, student service counters, and accounts department.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-building-columns"
            },
            rv_block: {
                name: "RV Block",
                coords: [78.650630, 10.755820],
                details: "Academic Block (West Side)",
                hours: "8:30 AM - 6:00 PM",
                description: "Contains classrooms, advanced labs for CSE and IT departments, faculty cabins, and department seminar halls.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-laptop-code"
            },
            js_block: {
                name: "JS Block",
                coords: [78.651810, 10.755850],
                details: "Academic Block (East Side)",
                hours: "8:30 AM - 6:00 PM",
                description: "Dedicated academic building housing classrooms and state-of-the-art labs for ECE and EEE branches.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-microchip"
            },
            library: {
                name: "Library",
                coords: [78.651150, 10.755500],
                details: "Central Library Building",
                hours: "9:00 AM - 5:30 PM",
                description: "Central repository containing over 50,000 physical volumes, research journals, e-learning terminals, and quiet study zones.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-book-open"
            },
            canteen: {
                name: "Canteen",
                coords: [78.650800, 10.754800],
                details: "Cafeteria & Dining Hall",
                hours: "8:00 AM - 4:30 PM",
                description: "Serves hygiene vegetarian meals, quick lunches, fresh juices, tea, coffee, and snacks for students and faculty.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-utensils"
            },
            auditorium: {
                name: "Auditorium",
                coords: [78.651600, 10.755100],
                details: "Central Indoor Auditorium",
                hours: "Event-based Open",
                description: "Air-conditioned indoor seating venue hosting college convocations, cultural fests, workshops, and international symposiums.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-masks-theater"
            },
            bus_stop: {
                name: "Bus Stop",
                coords: [78.652050, 10.757000],
                details: "College Bus Bay",
                hours: "7:30 AM - 9:00 AM, 4:00 PM - 6:00 PM",
                description: "Boarding point for all college buses connecting to various parts of Tiruchirappalli and nearby districts.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-bus"
            },
            parking: {
                name: "Parking Area",
                coords: [78.652250, 10.756800],
                details: "Student & Faculty Parking Lot",
                hours: "7:00 AM - 7:00 PM",
                description: "Secure parking space for two-wheelers and four-wheelers, equipped with CCTV surveillance.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-square-parking"
            },
            hostel: {
                name: "Campus Hostel",
                coords: [78.650100, 10.754100],
                details: "Boys & Girls Hostel Blocks",
                hours: "Residence Facility",
                description: "Comfortable residential blocks providing accommodation, multi-sport grounds, high-speed Wi-Fi, and 24/7 warden guidance.",
                photo: "assets/images/default-campus.jpg",
                icon: "fa-solid fa-hotel"
            }
        };

        let map;
        let activeMarker = null;
        let activeGlowElement = null;

        // Initialize Map
        document.addEventListener('DOMContentLoaded', () => {
            renderDestinationsList();

            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/satellite-streets-v12',
                center: [78.651228, 10.756091],
                zoom: 18,
                pitch: 65,
                bearing: -20,
                antialias: true
            });

            // Add standard Mapbox Controls
            map.addControl(new mapboxgl.NavigationControl(), 'top-left');
            map.addControl(new mapboxgl.FullscreenControl(), 'top-left');
            map.addControl(new mapboxgl.GeolocateControl({
                positionOptions: { enableHighAccuracy: true },
                trackUserLocation: true
            }), 'top-left');

            // Render 3D Buildings Extrusions Layer
            map.on('style.load', () => {
                const layers = map.getStyle().layers;
                const labelLayerId = layers.find(
                    (layer) => layer.type === 'symbol' && layer.layout['text-field']
                )?.id;

                map.addLayer({
                    'id': 'add-3d-buildings',
                    'source': 'composite',
                    'source-layer': 'building',
                    'filter': ['==', 'extrude', 'true'],
                    'type': 'fill-extrusion',
                    'minzoom': 15,
                    'paint': {
                        'fill-extrusion-color': '#00f2fe',
                        'fill-extrusion-height': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'height']
                        ],
                        'fill-extrusion-base': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'min_height']
                        ],
                        'fill-extrusion-opacity': 0.28
                    }
                }, labelLayerId);
            });

            // If "fly" parameter exists in URL, fly there automatically after load
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

        // Live Search sidebar filtering
        document.getElementById('search-destinations').addEventListener('input', (e) => {
            renderDestinationsList(e.target.value);
        });

        // Fly Camera & Highlight Coordinates
        function flyToLocation(key) {
            if (!locations[key]) return;
            const loc = locations[key];

            // 1. Zoom Camera
            map.flyTo({
                center: loc.coords,
                zoom: 18.5,
                pitch: 70,
                bearing: -10,
                speed: 1.2,
                curve: 1.4,
                essential: true
            });

            // 2. Clear previous active states
            if (activeMarker) activeMarker.remove();
            if (activeGlowElement) activeGlowElement.remove();
            
            document.querySelectorAll('.location-item').forEach(el => el.classList.remove('active'));
            const sidebarEl = document.getElementById(`sidebar-loc-${key}`);
            if (sidebarEl) {
                sidebarEl.classList.add('active');
                sidebarEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // 3. Spawns pulsing glow indicator
            const el = document.createElement('div');
            el.className = 'pulsing-glow-marker';
            activeGlowElement = el;

            activeMarker = new mapboxgl.Marker({ element: el })
                .setLngLat(loc.coords)
                .addTo(map);

            // 4. Open Glassmorphism Info Overlay card
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

        function askBuddyDrawer(query) {
            document.getElementById('buddy-drawer-input').value = query;
            sendBuddyDrawerMessage();
        }

        function sendBuddyDrawerMessage() {
            const input = document.getElementById('buddy-drawer-input');
            const query = input.value.trim();
            if (!query) return;

            // Open drawer if it was minimized
            const drawer = document.getElementById('buddy-drawer');
            if (drawer.classList.contains('minimized')) {
                drawer.classList.remove('minimized');
                document.getElementById('buddy-drawer-icon').style.transform = 'rotate(180deg)';
            }

            // Append user bubble
            appendBuddyDrawerBubble(query, 'user');
            input.value = '';

            // Query backend
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

                // Detect building match keywords and trigger map flyTo
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
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
