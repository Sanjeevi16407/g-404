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
    
    <!-- Timetable Matrix Grid (Desktop Only) -->
    <div class="glass-panel desktop-only" style="padding: 24px; overflow-x: auto;">
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

    <!-- Mobile Day Orders Tabs Selector (Mobile Only) -->
    <div class="mobile-only" style="margin-bottom: 16px;">
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; scrollbar-width: none; -ms-overflow-style: none;">
            <?php foreach ($weekdays as $index => $day): ?>
                <button onclick="switchMobileDayOrder(<?php echo $index; ?>)" id="day-tab-<?php echo $index; ?>" class="day-tab-btn" style="flex-shrink: 0; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.08); background: <?php echo $index === 0 ? 'var(--glow-primary-alpha)' : 'rgba(255,255,255,0.02)'; ?>; color: <?php echo $index === 0 ? 'var(--text-primary)' : 'var(--text-secondary)'; ?>; border-color: <?php echo $index === 0 ? 'var(--glow-primary)' : 'rgba(255,255,255,0.08)'; ?>; box-shadow: <?php echo $index === 0 ? '0 0 10px var(--glow-primary-alpha)' : 'none'; ?>; cursor: pointer; transition: all 0.2s; min-height: auto !important;">
                    <?php echo $day; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mobile Timetable Lists (Mobile Only) -->
    <div class="mobile-only">
        <?php foreach ($weekdays as $index => $day): ?>
            <div id="day-content-<?php echo $index; ?>" class="day-content-panel" style="<?php echo $index === 0 ? 'display: flex;' : 'display: none;'; ?> flex-direction: column; gap: 12px;">
                <?php for ($p = 1; $p <= 8; $p++): ?>
                    <div class="glass-panel" style="margin: 0 !important; padding: 14px 18px !important; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--glow-primary); flex-shrink: 0; min-height: auto !important;">
                                <span style="font-size: 0.75rem; font-weight: 700; line-height: 1;">P<?php echo $p; ?></span>
                                <span style="font-size: 0.55rem; color: var(--text-tertiary); margin-top: 2px; text-align: center;"><?php echo explode('-', $period_times[$p])[0]; ?></span>
                            </div>
                            <div style="text-align: left;">
                                <?php if (isset($schedule[$day][$p])): ?>
                                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;"><?php echo sanitize_input($schedule[$day][$p]['subject_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 3px;"><?php echo sanitize_input($schedule[$day][$p]['faculty_name']); ?></div>
                                <?php else: ?>
                                    <div style="color: var(--text-tertiary); font-style: italic; font-size: 0.9rem;">Free Slot</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (isset($schedule[$day][$p])): ?>
                            <span class="badge-pill badge-low" style="font-size: 0.75rem; padding: 4px 8px; min-height: auto !important; flex-shrink: 0;">
                                Room <?php echo sanitize_input($schedule[$day][$p]['room_number']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Script to toggle tabs -->
    <script>
        function switchMobileDayOrder(index) {
            // Hide all panels
            document.querySelectorAll('.day-content-panel').forEach(p => p.style.display = 'none');
            // Show selected panel
            const activePanel = document.getElementById('day-content-' + index);
            if (activePanel) activePanel.style.display = 'flex';

            // Deactivate all tab buttons
            document.querySelectorAll('.day-tab-btn').forEach(b => {
                b.style.background = 'rgba(255,255,255,0.02)';
                b.style.color = 'var(--text-secondary)';
                b.style.borderColor = 'rgba(255,255,255,0.08)';
                b.style.boxShadow = 'none';
            });
            // Activate selected tab button
            const activeBtn = document.getElementById('day-tab-' + index);
            if (activeBtn) {
                activeBtn.style.background = 'var(--glow-primary-alpha)';
                activeBtn.style.color = 'var(--text-primary)';
                activeBtn.style.borderColor = 'var(--glow-primary)';
                activeBtn.style.boxShadow = '0 0 10px var(--glow-primary-alpha)';
            }
        }
    </script>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
