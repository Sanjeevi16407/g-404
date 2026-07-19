<?php
/**
 * Buddy - Your Digital Senior: Unified Diagnostics & Test Suite
 * Validates backend environments, databases, file systems, security, and features.
 */
require_once __DIR__ . '/../backend/db.php';

// Diagnostic variables
$php_version = PHP_VERSION;
$curl_enabled = extension_loaded('curl');
$db_connected = false;
$missing_tables = [];
$upload_directories = [
    'uploads/',
    'uploads/avatars/',
    'uploads/faculty/',
    'uploads/events/',
    'uploads/clubs/',
    'uploads/campus/',
    'uploads/documents/'
];
$writable_status = [];

// 1. Test Database Connection
try {
    if ($db) {
        $db_connected = true;
        
        // Define all required tables from final schema
        $required_tables = [
            'achievements', 'admins', 'analytics_logs', 'announcements', 'buddy_knowledge', 
            'buddy_settings', 'bus_routes', 'campus_locations', 'club_registration_report', 'club_registrations', 
            'clubs', 'college_settings', 'departments', 'documents', 'event_registrations', 
            'events', 'faculty', 'hostel_info', 'important_contacts', 'journey_progress', 
            'library_resources', 'library_rules', 'notifications', 'quick_actions', 'sections', 
            'settings', 'students', 'timetable'
        ];
        
        // Fetch existing tables in database
        $existing_tables = [];
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existing_tables[] = $row[0];
        }
        
        foreach ($required_tables as $tbl) {
            if (!in_array($tbl, $existing_tables)) {
                $missing_tables[] = $tbl;
            }
        }
    }
} catch (PDOException $e) {
    $db_connected = false;
}

// 2. Test Directory Permissions
foreach ($upload_directories as $dir) {
    $full_path = dirname(__DIR__) . '/' . $dir;
    // Attempt to create directory if not exists
    if (!file_exists($full_path)) {
        @mkdir($full_path, 0755, true);
    }
    $writable_status[$dir] = is_writable($full_path);
}

// 3. Test Bcrypt Authentication engine
$test_password = "password123";
$test_hash = password_hash($test_password, PASSWORD_BCRYPT);
$bcrypt_working = password_verify($test_password, $test_hash);

// 4. Test Gemini Key Status
$configured_key = GEMINI_API_KEY;
try {
    if ($db_connected) {
        $buddy_settings = $db->query("SELECT gemini_api_key FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
        if ($buddy_settings) {
            $configured_key = $buddy_settings['gemini_api_key'] ?? GEMINI_API_KEY;
        }
    }
} catch (PDOException $e) {
    // Table does not exist yet
}
$gemini_status = !empty($configured_key) ? "Configured (Ready)" : "Not Configured";
?>
<!DOCTYPE html>
<html lang="en" data-theme="Spatial">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostics Panel | Digital Senior Project</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/themes/themes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--font-sans);
            padding: 40px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .diag-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 28px;
            margin-top: 24px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pass { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-fail { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        
        .feature-checklist {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 16px;
        }
        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.01);
            border: 1px solid var(--border-light);
            padding: 12px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .checklist-item:hover {
            background: rgba(255,255,255,0.03);
            border-color: var(--glow-primary-alpha);
        }
        .checklist-item input {
            width: 18px;
            height: 18px;
            accent-color: var(--glow-primary);
        }
        .checklist-item.checked span {
            text-decoration: line-through;
            color: var(--text-tertiary);
        }
    </style>
</head>
<body>

    <header class="page-header" style="margin-bottom: 32px;">
        <div>
            <h1 class="page-title">⚙️ System Diagnostics & Testing Portal</h1>
            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px;">Automated Environment Validation & Manual Verification Checklist</p>
        </div>
        <a href="../frontend/index.php" class="btn-glass"><i class="fa-solid fa-house"></i> Enter Student Portal</a>
    </header>

    <div class="diag-grid">
        
        <!-- Environment & Core Info -->
        <div class="glass-panel" style="padding: 24px;">
            <h3 style="font-size: 1.1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin-bottom: 16px;">
                <i class="fa-solid fa-server" style="color: var(--glow-primary);"></i> Environment Status
            </h3>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">PHP Version</span>
                    <span class="status-badge status-pass"><?php echo $php_version; ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">cURL Extension</span>
                    <span class="status-badge <?php echo $curl_enabled ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $curl_enabled ? 'Available' : 'Missing'; ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Database Connection</span>
                    <span class="status-badge <?php echo $db_connected ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $db_connected ? 'Connected' : 'Failed'; ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Bcrypt Hashing core</span>
                    <span class="status-badge <?php echo $bcrypt_working ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $bcrypt_working ? 'Operational' : 'Error'; ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-secondary);">Gemini AI Key</span>
                    <span class="status-badge status-pass"><?php echo $gemini_status; ?></span>
                </div>
            </div>
        </div>

        <!-- Database Tables Validation -->
        <div class="glass-panel" style="padding: 24px;">
            <h3 style="font-size: 1.1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin-bottom: 16px;">
                <i class="fa-solid fa-database" style="color: var(--glow-secondary);"></i> Database Integrity
            </h3>
            <?php if (!$db_connected): ?>
                <div class="error-banner">❌ Could not connect to mysql database buddy_senior_db. Verify XAMPP is running.</div>
            <?php elseif (empty($missing_tables)): ?>
                <div class="badge-pill badge-low" style="display: block; padding: 12px; font-size: 0.85rem; text-align: left;">
                    ✅ All 28 required tables defined in the final database schema exist and are configured in buddy_senior_db.
                </div>
            <?php else: ?>
                <div class="error-banner" style="text-align: left; padding: 12px;">
                    ⚠️ Missing database tables:
                    <ul style="margin-top: 8px; margin-left: 16px; font-size: 0.8rem;">
                        <?php foreach ($missing_tables as $tbl): ?>
                            <li><?php echo $tbl; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upload Folders Writable States -->
        <div class="glass-panel" style="padding: 24px;">
            <h3 style="font-size: 1.1rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin-bottom: 16px;">
                <i class="fa-solid fa-folder-open" style="color: var(--glow-tertiary);"></i> Upload Permissions
            </h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($writable_status as $dir => $writable): ?>
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                        <span style="font-family: monospace; color: var(--text-secondary);"><?php echo $dir; ?></span>
                        <span class="status-badge <?php echo $writable ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $writable ? 'Writable' : 'Blocked'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Feature Validation Interactive Checklists -->
    <div style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 32px;">
        
        <!-- Student Portal Validation Checklist -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-graduation-cap" style="color: var(--glow-primary); margin-right: 8px;"></i> Student Portal validation
            </h3>
            <div class="feature-checklist" id="student-checks">
                <label class="checklist-item"><input type="checkbox" data-check="s1"> <span>Verify Login credentials checks (2114001 / password123)</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s2"> <span>Verify Welcome onboarding typewriter and voice synthesizers</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s3"> <span>Verify Dashboard counts cards load and counters count up</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s4"> <span>Verify Weekly Timetable schedules matrix loads from seeds</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s5"> <span>Verify Faculty Phonebook directories search filter queries</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s6"> <span>Verify Campus locator locations filters and detail hours</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s7"> <span>Verify Joining and Leaving Clubs via AJAX DB transactions</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s8"> <span>Verify Event registration bookings and tab divisions</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="s9"> <span>Verify PDF downloads listing in Documents library</span></label>
            </div>
        </div>

        <!-- Admin Portal & CRUD Checklist -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-screwdriver-wrench" style="color: var(--glow-secondary); margin-right: 8px;"></i> Admin CRUD & Media validation
            </h3>
            <div class="feature-checklist" id="admin-checks">
                <label class="checklist-item"><input type="checkbox" data-check="a1"> <span>Verify Admin Login credential checks (admin / admin@saranathan)</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a2"> <span>Verify Clickable Metrics stats cards navigate to CRUD views</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a3"> <span>Verify Dashboard Quick Search lookup engine finds records</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a4"> <span>Verify Student profile registration inserts and edits</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a5"> <span>Verify Faculty uploader saves image profiles to uploads/faculty/</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a6"> <span>Verify Timetable period schedules create, edit, and delete</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a7"> <span>Verify Event poster file-uploader (blocking arbitrary files)</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="a8"> <span>Verify Knowledge Base QA keywords list insertion</span></label>
            </div>
        </div>

        <!-- Themes & Animations Checklist -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--glow-tertiary); margin-right: 8px;"></i> Theme & Animation validations
            </h3>
            <div class="feature-checklist" id="theme-checks">
                <label class="checklist-item"><input type="checkbox" data-check="t1"> <span>Verify default Spatial VisionOS card styling and translucent glass blurs</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t2"> <span>Verify Liquid Glass theme selects and loads cyan variable glows</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t3"> <span>Verify Aurora theme and background rotating fluorescent layers</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t4"> <span>Verify Dark & Light themes override card border colors</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t5"> <span>Verify real-time theme shifts without page refresh</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t6"> <span>Verify background theme persistence saves on reload</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="t7"> <span>Verify GSAP page entrance staggers and metric counter scales</span></label>
            </div>
        </div>

        <!-- Security & Deployment Checklist -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-shield-halved" style="color: rgba(239, 68, 68, 0.8); margin-right: 8px;"></i> Security & Deployment Checklist
            </h3>
            <div class="feature-checklist" id="security-checks">
                <label class="checklist-item"><input type="checkbox" data-check="sec1"> <span>Verify password hashes are stored encrypted using Bcrypt DB hashes</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec2"> <span>Verify direct admin URL folder blocks redirect unauthorized requests</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec3"> <span>Verify SQL input sanitizers block basic script injection codes</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec4"> <span>Verify file uploader size filters restrict extreme image payload buffers</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec5"> <span>Verify Gemini API key credentials are saved outside client scripts</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec6"> <span>[DEPLOY] Exported final Database seeds and structure</span></label>
                <label class="checklist-item"><input type="checkbox" data-check="sec7"> <span>[DEPLOY] Set folder configurations write states on host Apache server</span></label>
            </div>
        </div>

    </div>

    <!-- Script to preserve checkbox states in local storage -->
    <script>
        document.querySelectorAll('.checklist-item input').forEach(input => {
            const checkId = input.getAttribute('data-check');
            const parent = input.closest('.checklist-item');
            
            // Restore state from LocalStorage
            if (localStorage.getItem(checkId) === 'true') {
                input.checked = true;
                parent.classList.add('checked');
            }

            input.addEventListener('change', function() {
                localStorage.setItem(checkId, this.checked);
                if (this.checked) {
                    parent.classList.add('checked');
                } else {
                    parent.classList.remove('checked');
                }
            });
        });
    </script>

</body>
</html>
