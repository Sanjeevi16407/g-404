<?php
/**
 * Student Dashboard Home Panel
 */
require_once __DIR__ . '/includes/header.php';

// 1. Fetch current student details
$student_id = (int)$_SESSION['student_id'];
$student = $db->prepare("
    SELECT s.*, d.name as dept_name, sec.name as section_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE s.id = ? LIMIT 1
");
$student->execute([$student_id]);
$profile = $student->fetch();

// 2. Daily Buddy Card Greeting based on current time
// Time: 05:00-11:59 (Morning), 12:00-16:59 (Afternoon), 17:00-21:59 (Evening), 22:00-04:59 (Night)
$current_weekday = date('l');
$hour = (int)date('G');
$greeting_col = 'morning_message';
$greeting_prefix = '☀️ Good Morning!';

if ($current_weekday === 'Saturday' || $current_weekday === 'Sunday') {
    $greeting_prefix = '✨ Enjoy your Weekend!';
    $greeting_message = 'Relax, recharge, and have a wonderful weekend, ' . $profile['name'] . '!';
    $buddy_tip = 'Spend some time planning your upcoming week and getting well-rested!';
} else {
    if ($hour >= 12 && $hour < 17) {
        $greeting_col = 'afternoon_message';
        $greeting_prefix = '☀️ Good Afternoon!';
    } elseif ($hour >= 17 && $hour < 22) {
        $greeting_col = 'evening_message';
        $greeting_prefix = '🌆 Good Evening!';
    } elseif ($hour >= 22 || $hour < 5) {
        $greeting_col = 'night_message';
        $greeting_prefix = '🌙 Good Night!';
    }

    // Fetch buddy greeting configurations
    $buddy = $db->query("SELECT buddy_name, daily_tips, $greeting_col FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
    $buddy_name = $buddy['buddy_name'] ?? 'Buddy';
    $buddy_tip = $buddy['daily_tips'] ?? 'Check your classroom locations early!';
    $greeting_message = $buddy[$greeting_col] ?? "Hope you have an amazing day!";
    $greeting_message = str_replace('[Student Name]', $profile['name'], $greeting_message);
}

// 3. Fetch today's Timetable slots
$current_weekday = date('l');
$day_order_map = [
    'Monday'    => '1st Day Order',
    'Tuesday'   => '2nd Day Order',
    'Wednesday' => '3rd Day Order',
    'Thursday'  => '4th Day Order',
    'Friday'    => '5th Day Order'
];
$day_of_week = $day_order_map[$current_weekday] ?? '';

$timetable_stmt = $db->prepare("
    SELECT t.*, f.name as faculty_name 
    FROM timetable t
    JOIN faculty f ON t.faculty_id = f.id
    WHERE t.department_id = ? AND t.section_id = ? AND t.day_of_week = ?
    ORDER BY t.period_number ASC
");
$timetable_stmt->execute([$profile['department_id'], $profile['section_id'], $day_of_week]);
$today_classes = $timetable_stmt->fetchAll();

// 4. Journey Checklist Progress values
$steps_order = ['welcome', 'orientation', 'campus', 'faculty', 'timetable', 'clubs', 'events', 'dashboard'];
$current_step_idx = array_search($current_step, $steps_order);
if ($current_step_idx === false) $current_step_idx = 0;

// 5. Predictive Buddy Recommendations mapping
$recommendations = [];
if ($current_step === 'orientation') {
    $recommendations[] = [
        'title' => '📚 Complete Orientation Walkthrough',
        'desc' => 'Read regulations and requirements for your academic year.',
        'link' => 'orientation.php',
        'icon' => 'fa-graduation-cap'
    ];
} elseif ($current_step === 'campus') {
    $recommendations[] = [
        'title' => '📍 Explore Campus physical spots',
        'desc' => 'Check opening timings and guidelines for Library & Canteen.',
        'link' => 'campus.php',
        'icon' => 'fa-map-location-dot'
    ];
} elseif ($current_step === 'faculty') {
    $recommendations[] = [
        'title' => '👨‍🏫 Lookup Faculty Mentors & Cabins',
        'desc' => 'View specializations and cabin room numbers of CS teachers.',
        'link' => 'faculty.php',
        'icon' => 'fa-user-tie'
    ];
} elseif ($current_step === 'timetable') {
    $recommendations[] = [
        'title' => '🗓️ View your Weekly Timetable Schedule',
        'desc' => 'Check daily periods and assigned classrooms.',
        'link' => 'timetable.php',
        'icon' => 'fa-calendar-days'
    ];
} elseif ($current_step === 'clubs') {
    $recommendations[] = [
        'title' => '👥 Join Extracurricular Clubs',
        'desc' => 'Participate in Coding or Photography clubs.',
        'link' => 'clubs.php',
        'icon' => 'fa-people-group'
    ];
} elseif ($current_step === 'events') {
    $recommendations[] = [
        'title' => '🎉 Register for Upcoming Campus Events',
        'desc' => 'Book slots for Freshers Day or Code Storm hackathons.',
        'link' => 'events.php',
        'icon' => 'fa-calendar-star'
    ];
} else {
    // Default recommendations when onboarding completes
    $recommendations[] = [
        'title' => '🗓️ Today\'s Class Timetable',
        'desc' => 'Check your period schedule for ' . (!empty($day_of_week) ? $day_of_week : 'the week') . '.',
        'link' => 'timetable.php',
        'icon' => 'fa-calendar-days'
    ];
    $recommendations[] = [
        'title' => '💬 Ask Buddy in Tamil/Tanglish',
        'desc' => 'Need anything? Ask your senior chatbot bubble in the corner!',
        'link' => '#',
        'icon' => 'fa-comment'
    ];
}

// 6. Fetch ongoing announcements
$announcements = $db->query("SELECT * FROM announcements ORDER BY publish_date DESC LIMIT 3")->fetchAll();

// 7. Motivational Quote seeds
$quotes = [
    "Education is the most powerful weapon which you can use to change the world. — Nelson Mandela",
    "The beautiful thing about learning is that no one can take it away from you. — B.B. King",
    "Start where you are. Use what you have. Do what you can. — Arthur Ashe",
    "Strive for progress, not perfection. — Unknown"
];
$random_quote = $quotes[array_rand($quotes)];
?>



<!-- Daily Buddy Greeting card & Recommendations Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: flex-start;">
    
    <!-- Left Column: Daily Buddy Card -->
    <div style="display: flex; flex-direction: column; gap: 28px;">
        
        <!-- Daily Buddy Card Body -->
        <div class="glass-panel" style="padding: 32px; position: relative; overflow: hidden;">
            <!-- Glowing edge -->
            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--glow-primary);"></div>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                <div>
                    <h2 style="font-size: 1.7rem; color: var(--text-primary); font-weight: 700;"><?php echo $greeting_prefix; ?></h2>
                    <p style="font-size: 0.95rem; color: var(--text-secondary); margin-top: 6px;"><?php echo $greeting_message; ?></p>
                </div>
                <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--glow-primary-alpha); display: flex; align-items: center; justify-content: center; color: var(--glow-primary); font-size: 1.4rem;">
                    <i class="fa-solid fa-face-laugh-beam"></i>
                </div>
            </div>

            <!-- Tip & Quote divider grids -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 32px; border-top: 1px solid var(--border-light); padding-top: 24px;">
                <div>
                    <h4 style="font-size: 0.8rem; font-weight: 600; color: var(--glow-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">💡 Senior Tip of the Day</h4>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;"><?php echo sanitize_input($buddy_tip); ?></p>
                </div>
                <div>
                    <h4 style="font-size: 0.8rem; font-weight: 600; color: var(--glow-tertiary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">🌱 Daily Inspiration</h4>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); font-style: italic; line-height: 1.5;"><?php echo sanitize_input($random_quote); ?></p>
                </div>
            </div>
        </div>

        <!-- Today's Class Timetable summary -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.2rem; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-clock" style="color: var(--glow-primary);"></i> Today's Schedule (<?php echo !empty($day_of_week) ? $day_of_week : 'Weekend'; ?>)
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (empty($today_classes)): ?>
                    <div style="padding: 20px; text-align: center; color: var(--text-tertiary); font-size: 0.9rem;">
                        ☕ No classes scheduled for today. Enjoy your day!
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                        <?php foreach ($today_classes as $slot): ?>
                            <div style="padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="font-size: 0.75rem; font-weight: 600; color: var(--glow-primary); text-transform: uppercase;">Period <?php echo $slot['period_number']; ?></span>
                                    <span class="badge-pill badge-low" style="font-size: 0.7rem;"><?php echo sanitize_input($slot['room_number']); ?></span>
                                </div>
                                <div style="font-weight: 600; color: var(--text-primary); font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize_input($slot['subject_name']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);"><?php echo sanitize_input($slot['faculty_name']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right Column: Predictive Recommendations & Notices -->
    <div style="display: flex; flex-direction: column; gap: 28px;">
        
        <!-- Buddy Recommendations panel -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-compass" style="color: var(--glow-secondary);"></i> Buddy Recommends
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($recommendations as $rec): ?>
                    <a href="<?php echo $rec['link']; ?>" style="display: block; padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-light); text-decoration: none; transition: all var(--transition-fast);" onmouseover="this.style.background='rgba(255,255,255,0.05)';" onmouseout="this.style.background='rgba(255,255,255,0.02)';">
                        <div style="display: flex; gap: 12px; align-items: flex-start;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--glow-primary-alpha); color: var(--glow-primary); display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0;">
                                <i class="fa-solid <?php echo $rec['icon']; ?>"></i>
                            </div>
                            <div>
                                <h4 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($rec['title']); ?></h4>
                                <p style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; line-height: 1.4;"><?php echo sanitize_input($rec['desc']); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Notices panel -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-bullhorn" style="color: var(--glow-tertiary);"></i> Announcements
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (empty($announcements)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-tertiary);">No notices published today.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div onclick='openAnnouncementModal(<?php echo json_encode($ann); ?>)' style="padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.03)';" onmouseout="this.style.background='rgba(255,255,255,0.01)';">
                            <div>
                                <h4 style="font-size: 0.85rem; color: var(--text-primary); font-weight: 600;"><?php echo sanitize_input($ann['title']); ?></h4>
                                <span style="font-size: 0.7rem; color: var(--text-tertiary);"><?php echo date('M d, Y', strtotime($ann['publish_date'])); ?></span>
                            </div>
                            <span class="badge-pill badge-<?php echo $ann['priority']; ?>" style="font-size: 0.65rem; padding: 2px 6px;"><?php echo $ann['priority']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<!-- Announcement Detail Modal -->
<div id="announcement-detail-modal" class="glass-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; background: rgba(13, 18, 35, 0.6); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); align-items: center; justify-content: center;">
    <div class="glass-panel modal-content" style="background: rgba(15, 23, 42, 0.85); border: 1px solid var(--border-light); padding: 32px; border-radius: 20px; max-width: 500px; width: 90%; position: relative; margin: auto;">
        <i onclick="closeAnnouncementModal()" class="fa-solid fa-xmark modal-close" style="position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-secondary); font-size: 1.2rem;"></i>
        <span id="ann-modal-priority" class="badge-pill" style="font-size: 0.7rem; padding: 3px 8px; text-transform: uppercase;"></span>
        <h3 id="ann-modal-title" style="margin-top: 14px; margin-bottom: 8px; font-size: 1.3rem; color: var(--text-primary); font-weight: 700;"></h3>
        <p id="ann-modal-date" style="font-size: 0.8rem; color: var(--text-tertiary); margin-bottom: 20px;"></p>
        <div id="ann-modal-desc" style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 24px; white-space: pre-wrap; max-height: 250px; overflow-y: auto;"></div>
        <div id="ann-modal-attachment-container" style="display: none; border-top: 1px solid var(--border-light); padding-top: 16px;">
            <a id="ann-modal-attachment-link" href="#" target="_blank" class="btn-glass btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 18px; border-radius: 10px; font-size: 0.85rem; width: 100%; justify-content: center;">
                <i class="fa-solid fa-file-pdf"></i> View Circular Document (PDF)
            </a>
        </div>
    </div>
</div>

<script>
function openAnnouncementModal(ann) {
    document.getElementById('ann-modal-title').innerText = ann.title;
    document.getElementById('ann-modal-priority').innerText = ann.priority + ' Priority';
    document.getElementById('ann-modal-priority').className = 'badge-pill badge-' + ann.priority;
    
    // Format Date
    const pubDate = new Date(ann.publish_date);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('ann-modal-date').innerText = 'Published on ' + pubDate.toLocaleDateString('en-US', options);
    
    document.getElementById('ann-modal-desc').innerText = ann.description;
    
    const attachContainer = document.getElementById('ann-modal-attachment-container');
    if (ann.pdf_path) {
        document.getElementById('ann-modal-attachment-link').href = '../' + ann.pdf_path;
        attachContainer.style.display = 'block';
    } else {
        attachContainer.style.display = 'none';
    }
    
    document.getElementById('announcement-detail-modal').style.display = 'flex';
}

function closeAnnouncementModal() {
    document.getElementById('announcement-detail-modal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
