<?php
/**
 * Student Portal - Student Profile, Badges, & Achievements
 */
require_once __DIR__ . '/includes/header.php';

$student_id = (int)$_SESSION['student_id'];

// Handle delete or clear notifications actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_notification') {
        $notif_id = (int)$_POST['notification_id'];
        $delete_stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND student_id = ?");
        $delete_stmt->execute([$notif_id, $student_id]);
        echo "<script>window.location.href = 'profile.php#notifications';</script>";
        exit;
    } elseif ($_POST['action'] === 'clear_all_notifications') {
        $clear_stmt = $db->prepare("DELETE FROM notifications WHERE student_id = ?");
        $clear_stmt->execute([$student_id]);
        echo "<script>window.location.href = 'profile.php#notifications';</script>";
        exit;
    }
}

// 1. Fetch complete student details
$stu_stmt = $db->prepare("
    SELECT s.*, d.name as dept_name, sec.name as section_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE s.id = ? LIMIT 1
");
$stu_stmt->execute([$student_id]);
$profile = $stu_stmt->fetch();

// 2. Fetch student's joined clubs details
$clubs_stmt = $db->prepare("
    SELECT c.* FROM club_registrations cr
    JOIN clubs c ON cr.club_id = c.id
    WHERE cr.student_id = ?
");
$clubs_stmt->execute([$student_id]);
$joined_clubs = $clubs_stmt->fetchAll();

// 3. Fetch student's registered events details
$events_stmt = $db->prepare("
    SELECT e.* FROM event_registrations er
    JOIN events e ON er.event_id = e.id
    WHERE er.student_id = ?
");
$events_stmt->execute([$student_id]);
$registered_events = $events_stmt->fetchAll();

// 4. Fetch achievements/badges earned by student
$badge_stmt = $db->prepare("SELECT * FROM achievements WHERE student_id = ? ORDER BY unlocked_at DESC");
$badge_stmt->execute([$student_id]);
$badges = $badge_stmt->fetchAll();

// 5. Fetch recent system notifications log
$notifs_stmt = $db->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 10");
$notifs_stmt->execute([$student_id]);
$notifications = $notifs_stmt->fetchAll();

// Mark notifications as read
$db->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?")->execute([$student_id]);
?>

<div class="page-header">
    <div class="page-title">👤 My Senior Profile Portfolio</div>
    <span class="badge-pill badge-low" style="text-transform: uppercase;">Register ID: <?php echo sanitize_input($profile['register_number']); ?></span>
</div>

<!-- Profile Info Card -->
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; align-items: flex-start; margin-top: 16px;">
    
    <!-- Left Column: Avatar & Profile info -->
    <div style="display: flex; flex-direction: column; gap: 28px;">
        <div class="glass-panel" style="padding: 32px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 12px;">
            <img src="../<?php echo !empty($student_settings['avatar_url']) ? sanitize_input($student_settings['avatar_url']) : 'assets/images/default-avatar.png'; ?>" alt="Avatar" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid var(--glow-primary); box-shadow: 0 0 15px var(--glow-primary-alpha);">
            
            <h3 style="font-weight: 700; color: var(--text-primary); font-size: 1.3rem; margin-top: 8px;"><?php echo sanitize_input($profile['name']); ?></h3>
            <span class="badge-pill badge-medium" style="font-size: 0.75rem; text-transform: uppercase;"><?php echo sanitize_input($profile['dept_name']); ?></span>
            
            <div style="width: 100%; border-top: 1px solid var(--border-light); margin-top: 16px; padding-top: 16px; text-align: left; display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem; color: var(--text-secondary);">
                <div><strong>Section:</strong> <?php echo sanitize_input($profile['section_name']); ?></div>
                <div><strong>Email:</strong> <?php echo sanitize_input($profile['email']); ?></div>
                <div><strong>Phone No:</strong> <?php echo sanitize_input($profile['phone']); ?></div>
                <div><strong>Onboarding Step:</strong> <span style="color: var(--glow-primary); font-weight: bold; text-transform: capitalize;"><?php echo $current_step; ?></span></div>
            </div>
            
            <a href="settings.php" class="btn-glass" style="width: 100%; margin-top: 12px; border-radius: 8px; font-size: 0.8rem; text-decoration: none; display: block;"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
        </div>

        <!-- Badges & Achievements -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-award" style="color: var(--glow-secondary);"></i> Unlocked Badges
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 16px; justify-items: center;">
                <?php if (empty($badges)): ?>
                    <p style="font-size: 0.8rem; color: var(--text-tertiary); grid-column: 1/-1;">Earn onboarding steps to unlock badges!</p>
                <?php else: ?>
                    <?php foreach ($badges as $badge): ?>
                        <div style="text-align: center; display: flex; flex-direction: column; align-items: center; gap: 6px;" title="Unlocked: <?php echo date('M d, Y', strtotime($badge['unlocked_at'])); ?>">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--glow-primary-alpha); color: var(--glow-primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid var(--glow-primary);">
                                <i class="<?php echo $badge['badge_icon']; ?>"></i>
                            </div>
                            <span style="font-size: 0.65rem; color: var(--text-secondary); font-weight: 600; text-align: center; line-height: 1.2; max-width: 60px;"><?php echo sanitize_input($badge['badge_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Timeline tracker, Clubs, Events, Notifications -->
    <div style="display: flex; flex-direction: column; gap: 28px;">
        
        <!-- Joined Clubs & Registered Events Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Clubs Panel -->
            <div class="glass-panel" style="padding: 24px;">
                <h4 style="font-size: 0.95rem; color: var(--text-primary); font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-people-group" style="color: var(--glow-primary);"></i> My Clubs
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if (empty($joined_clubs)): ?>
                        <p style="font-size: 0.8rem; color: var(--text-tertiary);">You haven't joined any clubs.</p>
                    <?php else: ?>
                        <?php foreach ($joined_clubs as $cl): ?>
                            <div style="padding: 10px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light); font-size: 0.85rem; color: var(--text-primary); font-weight: 600;">
                                👥 <?php echo sanitize_input($cl['name']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Events Panel -->
            <div class="glass-panel" style="padding: 24px;">
                <h4 style="font-size: 0.95rem; color: var(--text-primary); font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-calendar-check" style="color: var(--glow-secondary);"></i> Registered Events
                </h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php if (empty($registered_events)): ?>
                        <p style="font-size: 0.8rem; color: var(--text-tertiary);">No upcoming events registered.</p>
                    <?php else: ?>
                        <?php foreach ($registered_events as $ev): ?>
                            <div style="padding: 10px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light); font-size: 0.85rem; color: var(--text-primary); font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                                <span>🎉 <?php echo sanitize_input($ev['title']); ?></span>
                                <span style="font-size: 0.7rem; color: var(--text-tertiary); font-weight: normal;"><?php echo date('M d', strtotime($ev['event_date'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications History log -->
        <div class="glass-panel" id="notifications" style="padding: 28px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 16px; flex-wrap: wrap;">
                <h3 style="font-size: 1.15rem; color: var(--text-primary); font-weight: 700; display: flex; align-items: center; gap: 8px; margin: 0;">
                    <i class="fa-solid fa-bell" style="color: var(--glow-tertiary);"></i> Notifications Log
                </h3>
                <?php if (!empty($notifications)): ?>
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to clear all notifications?');">
                        <input type="hidden" name="action" value="clear_all_notifications">
                        <button type="submit" class="btn-glass" style="padding: 6px 14px; font-size: 0.75rem; border-radius: 8px; cursor: pointer; color: #ef4444; border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-trash-can"></i> Clear All
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php if (empty($notifications)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-tertiary); text-align: center; padding: 20px;">No alerts logged yet.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <?php 
                        $icon = 'fa-bell';
                        $color = 'var(--glow-primary)';
                        $msg = $notif['message'];
                        if (stripos($msg, 'Document') !== false || stripos($msg, 'documnet') !== false) {
                            $icon = 'fa-file-pdf';
                            $color = 'var(--glow-primary)';
                        } elseif (stripos($msg, 'Announcement') !== false) {
                            $icon = 'fa-bullhorn';
                            $color = 'var(--glow-tertiary)';
                        } elseif (stripos($msg, 'Event') !== false) {
                            $icon = 'fa-calendar-check';
                            $color = 'var(--glow-secondary)';
                        } elseif (stripos($msg, 'Club') !== false) {
                            $icon = 'fa-people-group';
                            $color = 'var(--glow-primary)';
                        } elseif (stripos($msg, 'Reminder') !== false) {
                            $icon = 'fa-clock';
                            $color = 'var(--glow-secondary)';
                        }
                        ?>
                        <div style="padding: 14px 20px; border-radius: 12px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light); font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-light); display: flex; align-items: center; justify-content: center; color: <?php echo $color; ?>; font-size: 0.95rem; flex-shrink: 0;">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                </div>
                                <span><?php echo sanitize_input($notif['message']); ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 16px; flex-shrink: 0;">
                                <span style="font-size: 0.75rem; color: var(--text-tertiary);"><?php echo format_to_local_time($notif['created_at']); ?></span>
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this notification?');">
                                    <input type="hidden" name="action" value="delete_notification">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: var(--text-tertiary); cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; transition: color 0.2s;" onmouseover="this.style.color='#ef4444';" onmouseout="this.style.color='var(--text-tertiary)';" title="Delete Notification">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
