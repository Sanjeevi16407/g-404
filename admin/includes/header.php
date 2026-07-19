<?php
/**
 * Shared Admin Portal Header Layout (Restructured Sidebar)
 */
require_once __DIR__ . '/../../backend/db.php';

// Verify Admin Session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="Spatial">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Buddy - Your Digital Senior</title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/themes/themes.css?v=<?php echo time(); ?>">
    <!-- FontAwesome Vector Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            important: true, // Avoid style collisions
        }
    </script>
    <!-- Lucide Outlined Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- GSAP Animations Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="../assets/js/animations.js" defer></script>
    <script src="../assets/js/theme-switcher.js" defer></script>
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            display: flex;
            min-height: 100vh;
            background: var(--bg-primary);
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
        
        .submenu {
            list-style: none;
            padding-left: 32px;
            margin-top: 4px;
            margin-bottom: 4px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .submenu-link {
            display: block;
            padding: 6px 12px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 6px;
            transition: all var(--transition-fast);
        }
        .submenu-link:hover, .submenu-link.active {
            color: var(--text-primary);
            background: rgba(255,255,255,0.04);
        }
        .submenu-link.active {
            color: var(--glow-primary);
            font-weight: 600;
        }
        
        /* Main workspace content wrapper */
        .main-content {
            margin-left: calc(var(--sidebar-width) + 48px);
            flex: 1;
            padding: 32px 32px 32px 0;
            display: flex;
            flex-direction: column;
            gap: 32px;
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
            gap: 12px;
        }
        .badge-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--glow-primary-alpha);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--glow-primary);
            font-weight: bold;
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
        
        /* Table Styles for Management panels */
        .data-table-container {
            padding: 24px;
            border-radius: 20px;
            overflow-x: auto;
        }
        .table-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .panel-title {
            font-size: 1.4rem;
            color: var(--text-primary);
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        .custom-table th, .custom-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
        }
        .custom-table th {
            font-family: var(--font-heading);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.02);
        }
        .custom-table td {
            font-size: 0.95rem;
            color: var(--text-primary);
        }
        .custom-table tr:hover td {
            background: rgba(255, 255, 255, 0.01);
        }
        
        /* Badges and Actions */
        .badge-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-medium { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-low { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }

        .action-btns {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
            background: rgba(255,255,255,0.03);
            color: var(--text-secondary);
            cursor: pointer;
            text-decoration: none;
            transition: all var(--transition-fast);
        }
        .btn-action:hover {
            color: var(--text-primary);
            background: rgba(255,255,255,0.08);
            transform: translateY(-2px);
        }
        .btn-edit:hover {
            border-color: var(--glow-primary);
            color: var(--glow-primary);
            box-shadow: 0 0 10px var(--glow-primary-alpha);
        }
        .btn-delete:hover {
            border-color: #ef4444;
            color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.2);
        }

        /* Form fields custom styling inside panels */
        .glass-modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(8px);
            z-index: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0; pointer-events: none;
            transition: all var(--transition-normal);
        }
        .glass-modal.active {
            opacity: 1; pointer-events: auto;
        }
        .modal-content {
            width: 100%;
            max-width: 550px;
            padding: 36px;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 20px; right: 20px;
            font-size: 1.2rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color var(--transition-fast);
        }
        .modal-close:hover { color: var(--text-primary); }

        /* Upload Preview Styles */
        .upload-preview-container {
            margin-top: 10px;
            margin-bottom: 16px;
            display: none;
            text-align: center;
        }
        .upload-preview-img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--glow-primary);
            box-shadow: 0 0 15px var(--glow-primary-alpha);
        }
    </style>
</head>
<body>

    <!-- Space Background -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <!-- Restructured Sidebar -->
    <aside class="glass-panel sidebar">
        <div class="sidebar-brand">
            <img src="../assets/images/logo.png" alt="Saranathan Logo" class="brand-logo">
            <div class="brand-name">
                Saranathan
                <span>Digital Senior</span>
            </div>
        </div>

        <nav class="menu-list">
            <li>
                <a href="dashboard.php" class="menu-item-link <?php echo $active_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="students.php" class="menu-item-link <?php echo $active_page === 'students.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i> Students
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
                <a href="campus.php" class="menu-item-link <?php echo $active_page === 'campus.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-map-location-dot"></i> Campus Nav
                </a>
            </li>
            <li>
                <a href="events.php" class="menu-item-link <?php echo $active_page === 'events.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Events
                </a>
            </li>
            <li>
                <a href="clubs.php" class="menu-item-link <?php echo $active_page === 'clubs.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-people-group"></i> Clubs
                </a>
            </li>
            <li>
                <a href="registrations.php" class="menu-item-link <?php echo $active_page === 'registrations.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-id-card-clip"></i> Registrations
                </a>
            </li>
            <li>
                <a href="announcements.php" class="menu-item-link <?php echo $active_page === 'announcements.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bullhorn"></i> Announcements
                </a>
            </li>
            <li>
                <a href="documents.php" class="menu-item-link <?php echo $active_page === 'documents.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-pdf"></i> Documents
                </a>
            </li>
            
            <!-- Buddy AI Section -->
            <li>
                <div class="menu-item-link <?php echo ($active_page === 'knowledge.php' || $active_page === 'buddy_settings.php') ? 'active' : ''; ?>" style="cursor: pointer;">
                    <i class="fa-solid fa-brain"></i> Buddy AI
                </div>
                <ul class="submenu">
                    <li>
                        <a href="knowledge.php" class="submenu-link <?php echo $active_page === 'knowledge.php' ? 'active' : ''; ?>">
                            Knowledge Base
                        </a>
                    </li>
                    <li>
                        <a href="buddy_settings.php" class="submenu-link <?php echo $active_page === 'buddy_settings.php' ? 'active' : ''; ?>">
                            Buddy Settings
                        </a>
                    </li>
                </ul>
            </li>

            <li>
                <a href="settings.php" class="menu-item-link <?php echo $active_page === 'settings.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gears"></i> Settings
                </a>
            </li>
            <li>
                <a href="profile.php" class="menu-item-link <?php echo $active_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i> Profile
                </a>
            </li>
            <li style="margin-top: auto; padding-top: 20px;">
                <a href="logout.php" class="menu-item-link" style="color: #ef4444;">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </li>
        </nav>
    </aside>

    <!-- Main Workspace -->
    <main class="main-content">
        <!-- Top navbar profile area -->
        <header class="glass-panel top-navbar">
            <div class="nav-title">
                <h1>Control Panel</h1>
                <p>Welcome back, <?php echo sanitize_input($_SESSION['admin_username']); ?></p>
            </div>
            
            <div class="user-badge">
                <div class="badge-info">
                    <div class="badge-name"><?php echo sanitize_input($_SESSION['admin_username']); ?></div>
                    <div class="badge-role">System Administrator</div>
                </div>
                <div class="badge-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                </div>
            </div>
        </header>
