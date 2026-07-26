<?php
/**
 * Student Portal - Header Layout & Theme Injector
 */
require_once __DIR__ . '/../../backend/db.php';
check_student_session();

$student_id = (int)$_SESSION['student_id'];
$student_name = sanitize_input($_SESSION['student_name']);
$active_page = basename($_SERVER['PHP_SELF']);

// Fetch Student settings & theme
$settings = $db->prepare("SELECT * FROM settings WHERE student_id = ? LIMIT 1");
$settings->execute([$student_id]);
$student_settings = $settings->fetch();

$current_theme = $student_settings['theme'] ?? 'Spatial';
$_SESSION['student_theme'] = $current_theme;
$animations_enabled = $student_settings['animation_speed'] ?? 'high';

// Fetch journey step
$journey_stmt = $db->prepare("SELECT current_step FROM journey_progress WHERE student_id = ? LIMIT 1");
$journey_stmt->execute([$student_id]);
$current_step = $journey_stmt->fetchColumn() ?: 'welcome';

// Define global journey steps order
$steps_order = ['welcome', 'orientation', 'campus', 'faculty', 'timetable', 'clubs', 'events', 'dashboard'];

// Fetch unread notifications count
$unread_notifs = $db->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0");
$unread_notifs->execute([$student_id]);
$notif_count = $unread_notifs->fetchColumn();

// Fetch college settings
$college_stmt = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1");
$college = $college_stmt->fetch();
$college_name = $college['college_name'] ?? 'Saranathan College of Engineering';
$college_logo = $college['college_logo'] ?? 'assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo sanitize_input($current_theme); ?>" data-animations="<?php echo $animations_enabled; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucwords(str_replace('.php', '', $active_page)); ?> | Saranathan Digital Senior</title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/themes/themes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/mobile.css?v=<?php echo time(); ?>">
    <!-- FontAwesome Vector Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            important: true, // Avoid style collisions with custom stylesheets
        }
    </script>
    <!-- Lucide Outlined Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Three.js 3D WebGL Core -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- GSAP Animations Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <!-- Custom Animation Modules -->
    <script src="../assets/js/particles.js" defer></script>
    <script src="../assets/js/animations.js" defer></script>
    <script src="../assets/js/command-wheel.js" defer></script>
    <script src="../assets/js/greetings.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            min-height: 100vh;
            background: var(--bg-primary);
            margin: 0;
            padding: 0;
        }
        .app-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }
        /* Sidebar layout styling */
        .sidebar {
            position: fixed;
            top: 24px;
            left: 24px;
            bottom: 24px;
            width: var(--sidebar-width);
            z-index: 100;
            padding: 32px 20px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 36px;
            padding-left: 8px;
        }
        .brand-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .brand-name {
            font-family: var(--font-heading);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .brand-name span {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        .menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .menu-item-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .menu-item-link i {
            width: 20px;
            font-size: 1.05rem;
            text-align: center;
            transition: color var(--transition-fast);
        }
        .menu-item-link:hover, .menu-item-link.active {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.06);
            transform: translateX(4px);
        }
        .menu-item-link.active {
            background: var(--glow-primary-alpha);
            border-left: 3px solid var(--glow-primary);
        }
        .menu-item-link.active i {
            color: var(--glow-primary);
        }
        
        /* Main workspace content wrapper */
        .main-content {
            margin-left: calc(var(--sidebar-width) + 48px);
            flex: 1;
            padding: 32px 32px 32px 0;
            display: flex;
            flex-direction: column;
            gap: 32px;
            min-height: 100vh;
        }
        
        /* Top Navigation Header */
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-radius: 16px;
        }
        .nav-title h1 {
            font-size: 1.6rem;
            color: var(--text-primary);
        }
        .nav-title p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .notif-bell {
            position: relative;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--transition-fast);
        }
        .notif-bell:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.05);
        }
        .notif-count {
            position: absolute;
            top: -4px; right: -4px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
        }
        .badge-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid var(--border-glass);
            object-fit: cover;
        }
        .badge-info {
            text-align: left;
        }
        .badge-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .badge-role {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* Grid definitions for student pages */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        /* Mobile responsive adjustments */
        .mobile-toggle-btn {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.25rem;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid var(--border-glass);
            background: var(--bg-glass);
            transition: all var(--transition-fast);
        }
        .mobile-toggle-btn:hover {
            background: var(--bg-glass-hover);
            border-color: var(--border-glass-hover);
        }
        .sidebar-close-btn {
            display: none;
            position: absolute;
            top: 24px;
            right: 20px;
            font-size: 1.4rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color var(--transition-fast);
        }
        .sidebar-close-btn:hover {
            color: var(--text-primary);
        }
        .mobile-nav-title {
            font-family: var(--font-heading);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: none;
        }

        @media (max-width: 992px) {
            .sidebar {
                left: -320px !important;
                top: 0 !important;
                bottom: 0 !important;
                height: 100% !important;
                border-radius: 0 !important;
                border-top: none !important;
                border-bottom: none !important;
                border-left: none !important;
                width: 280px !important;
                transition: left 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
                box-shadow: 0 0 40px rgba(0, 0, 0, 0.8) !important;
            }
            .sidebar.active {
                left: 0 !important;
            }
            .sidebar-close-btn {
                display: block;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 16px !important;
            }
            .mobile-toggle-btn {
                display: flex;
            }
            .top-navbar .nav-title {
                display: none !important;
            }
            .mobile-nav-title {
                display: block !important;
                flex: 1;
                margin-left: 12px;
            }
        }
        
        #linewaves-bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -2;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.8s ease;
            overflow: hidden;
        }
        #linewaves-bg-container canvas {
            display: block;
            width: 100% !important;
            height: 100% !important;
        }
        .aurora-bg-container {
            transition: opacity 0.8s ease;
        }
    </style>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('student-sidebar');
            sidebar.classList.toggle('active');
        }
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(event) {
                const sidebar = document.getElementById('student-sidebar');
                const toggleBtn = document.getElementById('sidebar-toggle');
                if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('active')) {
                    if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });

            // LineWaves Theme Handler
            const container = document.getElementById('linewaves-bg-container');
            if (container) {
                let wavesInstance = null;

                function checkTheme() {
                    const theme = document.documentElement.getAttribute('data-theme');
                    const isLiveTheme = (theme === 'Live');

                    if (isLiveTheme) {
                        if (!wavesInstance) {
                            import('../assets/js/linewaves.js')
                                .then(() => {
                                    if (window.LineWavesBackground) {
                                        wavesInstance = new window.LineWavesBackground('linewaves-bg-container');
                                        container.style.opacity = '1';
                                    }
                                })
                                .catch(err => console.error("LineWaves initialization failed:", err));
                        } else {
                            container.style.opacity = '1';
                        }
                        const aurora = document.querySelector('.aurora-bg-container');
                        if (aurora) aurora.style.opacity = '0';
                    } else {
                        container.style.opacity = '0';
                        setTimeout(() => {
                            const currentTheme = document.documentElement.getAttribute('data-theme');
                            if (currentTheme !== 'Live' && wavesInstance) {
                                wavesInstance.destroy();
                                wavesInstance = null;
                            }
                        }, 800);
                        const aurora = document.querySelector('.aurora-bg-container');
                        if (aurora) aurora.style.opacity = '1';
                    }
                }

                checkTheme();

                const observer = new MutationObserver(checkTheme);
                observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            }
        });
    </script>
</head>
<body>

    <!-- Mobile Top Header Bar -->
    <header class="mobile-top-header">
        <button class="mobile-icon-btn" onclick="toggleMobileDrawer()" title="Menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="mobile-top-title">Saranathan</div>
        <a href="profile.php#notifications" class="mobile-icon-btn" title="Notifications">
            <i class="fa-solid fa-bell"></i>
            <?php if ($notif_count > 0): ?>
                <span style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; font-size: 0.65rem; font-weight: bold; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 5px rgba(239, 68, 68, 0.5);"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" style="margin-left: 8px;">
            <img src="../<?php echo !empty($student_settings['avatar_url']) ? sanitize_input($student_settings['avatar_url']) : 'assets/images/default-avatar.png'; ?>" alt="Avatar" class="mobile-badge-avatar">
        </a>
    </header>

    <!-- Mobile Sidebar Backdrop -->
    <div class="mobile-backdrop" id="mobile-drawer-backdrop"></div>

    <!-- Mobile Sidebar Drawer -->
    <aside class="mobile-drawer" id="mobile-sidebar-drawer">
        <div class="mobile-drawer-brand">
            <img src="../<?php echo sanitize_input($college_logo); ?>" alt="College Logo" style="width: 36px; height: 36px; border-radius: 50%;">
            <div style="font-family: var(--font-heading); font-size: 1.05rem; font-weight: 700; color: var(--text-primary); line-height: 1.2;">
                Saranathan
                <span style="display: block; font-size: 0.7rem; font-weight: 500; color: var(--text-secondary); text-transform: uppercase;">Digital Senior</span>
            </div>
        </div>
        <nav class="mobile-drawer-list">
            <a href="dashboard.php" class="mobile-drawer-link <?php echo $active_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="buddy.php" class="mobile-drawer-link <?php echo $active_page === 'buddy.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-brain"></i> Ask Buddy AI
            </a>
            <a href="orientation.php" class="mobile-drawer-link <?php echo $active_page === 'orientation.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-graduation-cap"></i> Orientation
            </a>
            <a href="campus.php" class="mobile-drawer-link <?php echo $active_page === 'campus.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-map-location-dot"></i> Campus Guide
            </a>
            <a href="faculty.php" class="mobile-drawer-link <?php echo $active_page === 'faculty.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-tie"></i> Faculty
            </a>
            <a href="timetable.php" class="mobile-drawer-link <?php echo $active_page === 'timetable.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-days"></i> Timetable
            </a>
            <a href="clubs.php" class="mobile-drawer-link <?php echo $active_page === 'clubs.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-people-group"></i> Clubs Directory
            </a>
            <a href="events.php" class="mobile-drawer-link <?php echo $active_page === 'events.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i> Campus Events
            </a>
            <a href="documents.php" class="mobile-drawer-link <?php echo $active_page === 'documents.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-pdf"></i> Academic Documents
            </a>
            <a href="profile.php" class="mobile-drawer-link <?php echo $active_page === 'profile.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-circle-user"></i> My Profile
            </a>
            <a href="settings.php" class="mobile-drawer-link <?php echo $active_page === 'settings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders"></i> Settings
            </a>
            <a href="logout.php" class="mobile-drawer-link" style="color: #ef4444; margin-top: 16px;">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </a>
        </nav>
    </aside>

    <!-- LineWaves Background Canvas -->
    <div id="linewaves-bg-container"></div>

    <!-- Moving Aurora Backgrounds -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <div class="app-layout">
        <!-- Student Navigation Sidebar -->
        <aside class="glass-panel sidebar" id="student-sidebar">
        <!-- Close Button (Mobile only) -->
        <i class="fa-solid fa-xmark sidebar-close-btn" onclick="toggleSidebar()"></i>
        <div class="sidebar-brand">
            <img src="../<?php echo sanitize_input($college_logo); ?>" alt="College Logo" class="brand-logo">
            <div class="brand-name">
                Saranathan
                <span>Digital Senior</span>
            </div>
        </div>

        <nav class="menu-list">
            <li>
                <a href="dashboard.php" class="menu-item-link <?php echo $active_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="buddy.php" class="menu-item-link <?php echo $active_page === 'buddy.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-brain"></i> Ask Buddy AI
                </a>
            </li>
            <li>
                <a href="orientation.php" class="menu-item-link <?php echo $active_page === 'orientation.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-graduation-cap"></i> Orientation
                </a>
            </li>
            <li>
                <a href="campus.php" class="menu-item-link <?php echo $active_page === 'campus.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-map-location-dot"></i> Campus Guide
                </a>
            </li>
            <li>
                <a href="faculty.php" class="menu-item-link <?php echo $active_page === 'faculty.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-tie"></i> Faculty
                </a>
            </li>
            <li>
                <a href="timetable.php" class="menu-item-link <?php echo $active_page === 'timetable.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-days"></i> Timetable
                </a>
            </li>
            <li>
                <a href="clubs.php" class="menu-item-link <?php echo $active_page === 'clubs.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-people-group"></i> Clubs Directory
                </a>
            </li>
            <li>
                <a href="events.php" class="menu-item-link <?php echo $active_page === 'events.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Campus Events
                </a>
            </li>
            <li>
                <a href="documents.php" class="menu-item-link <?php echo $active_page === 'documents.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-pdf"></i> Academic Documents
                </a>
            </li>
            <li>
                <a href="profile.php" class="menu-item-link <?php echo $active_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-user"></i> My Profile
                </a>
            </li>
            <li>
                <a href="settings.php" class="menu-item-link <?php echo $active_page === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-sliders"></i> Settings
                </a>
            </li>
            <li style="margin-top: auto; padding-top: 20px;">
                <a href="logout.php" class="menu-item-link" style="color: #ef4444;">
                    <i class="fa-solid fa-right-from-bracket"></i> Sign Out
                </a>
            </li>
        </nav>
    </aside>

    <!-- Main Workspace Area -->
    <main class="main-content">
        <!-- Top navbar profile area -->
        <header class="glass-panel top-navbar">
            <!-- Mobile Sidebar Toggle -->
            <button class="mobile-toggle-btn" id="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
            <!-- Mobile Title -->
            <div class="mobile-nav-title">
                Saranathan
            </div>
            
            <div class="nav-title">
                <h1>Hello, <?php echo $student_name; ?></h1>
                <p>Register Number: <?php echo sanitize_input($_SESSION['student_reg']); ?></p>
            </div>
            
            <div class="user-badge">
                <!-- Notifications Bell -->
                <a href="profile.php#notifications" class="notif-bell">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-count"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </a>

                <div class="badge-info">
                    <div class="badge-name"><?php echo $student_name; ?></div>
                    <div class="badge-role">Student (Freshman)</div>
                </div>
                <img src="../<?php echo !empty($student_settings['avatar_url']) ? sanitize_input($student_settings['avatar_url']) : 'assets/images/default-avatar.png'; ?>" alt="Avatar" class="badge-avatar">
            </div>
        </header>
