<?php
/**
 * Student Portal - Full Weekly Timetable
 */
require_once __DIR__ . '/includes/header.php';

// Advance journey step if completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_timetable'])) {
    if ($current_step === 'timetable') {
        $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'clubs' WHERE student_id = ?");
        $stmt->execute([$student_id]);

        // Add badge: "First Guide Checked"
        try {
            $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'Planner Pro', 'fa-solid fa-calendar-days')");
            $badge_stmt->execute([$student_id]);
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: Planner Pro badge earned!')");
            $notif_stmt->execute([$student_id]);
        } catch (PDOException $e) {}

        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit;
    }
}

// 1. Fetch student's department & section
$student_id = (int)$_SESSION['student_id'];
$stu_stmt = $db->prepare("SELECT department_id, section_id FROM students WHERE id = ? LIMIT 1");
$stu_stmt->execute([$student_id]);
$stu = $stu_stmt->fetch();

// 2. Fetch all timetable entries for this student grouped by day and period
$timetable_stmt = $db->prepare("
    SELECT t.*, f.name as faculty_name 
    FROM timetable t
    JOIN faculty f ON t.faculty_id = f.id
    WHERE t.department_id = ? AND t.section_id = ?
");
$timetable_stmt->execute([$stu['department_id'], $stu['section_id']]);
$results = $timetable_stmt->fetchAll();

$schedule = [];
foreach ($results as $row) {
    $schedule[$row['day_of_week']][$row['period_number']] = $row;
}

$weekdays = ['1st Day Order', '2nd Day Order', '3rd Day Order', '4th Day Order', '5th Day Order'];
$period_times = [
    1 => '09:15-10:10',
    2 => '10:10-11:05',
    3 => '11:10-11:55',
    4 => '11:55-12:45',
    5 => '01:25-02:15',
    6 => '02:15-03:05',
    7 => '03:15-04:00',
    8 => '04:00-04:45'
];
?>

<div class="page-header">
    <div class="page-title">🗓️ Weekly Class Timetable</div>
</div>

<div style="margin-top: 16px;">
    
    <!-- Timetable Matrix Grid -->
    <div class="glass-panel" style="padding: 24px; overflow-x: auto;">
        <table class="custom-table" style="min-width: 800px;">
            <thead>
                <tr>
                    <th style="width: 120px;">Day</th>
                    <?php for ($p = 1; $p <= 8; $p++): ?>
                        <th style="text-align: center;">
                            Period <?php echo $p; ?>
                            <span style="display: block; font-size: 0.65rem; color: var(--text-tertiary); text-transform: none; font-weight: normal; margin-top: 4px;">
                                <?php echo $period_times[$p]; ?>
                            </span>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($weekdays as $day): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--glow-primary); background: rgba(255,255,255,0.01);"><?php echo $day; ?></td>
                        
                        <?php for ($p = 1; $p <= 8; $p++): ?>
                            <td style="text-align: center; font-size: 0.85rem; padding: 12px 8px;">
                                <?php if (isset($schedule[$day][$p])): ?>
                                    <div style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($schedule[$day][$p]['subject_name']); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 4px;"><?php echo sanitize_input($schedule[$day][$p]['faculty_name']); ?></div>
                                    <span class="badge-pill badge-low" style="font-size: 0.65rem; padding: 1px 4px; margin-top: 6px; display: inline-block;">
                                        <?php echo sanitize_input($schedule[$day][$p]['room_number']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-tertiary); font-style: italic;">Free Slot</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
