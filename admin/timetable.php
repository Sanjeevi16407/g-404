<?php
/**
 * Timetable Scheduler Administration
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle form submissions to save timetable slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_timetable'])) {
    $dept_id = (int)$_POST['department_id'];
    $sect_id = (int)$_POST['section_id'];
    $day = sanitize_input($_POST['day_of_week']);
    
    // Arrays representing inputs for all 8 periods
    $subjects = $_POST['subject_name'] ?? [];
    $faculties = $_POST['faculty_id'] ?? [];
    $rooms = $_POST['room_number'] ?? [];

    if (!empty($dept_id) && !empty($sect_id) && !empty($day)) {
        try {
            $db->beginTransaction();
            
            // Loop through all 8 periods
            for ($p = 1; $p <= 8; $p++) {
                $subject = sanitize_input($subjects[$p] ?? '');
                $fac_id = (int)($faculties[$p] ?? 0);
                $room = sanitize_input($rooms[$p] ?? '');

                if (!empty($subject) && !empty($fac_id) && !empty($room)) {
                    // UPSERT logic: Insert or Update on duplicate key
                    $stmt = $db->prepare("
                        INSERT INTO timetable (department_id, section_id, day_of_week, period_number, subject_name, faculty_id, room_number)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE subject_name = VALUES(subject_name), faculty_id = VALUES(faculty_id), room_number = VALUES(room_number)
                    ");
                    $stmt->execute([$dept_id, $sect_id, $day, $p, $subject, $fac_id, $room]);
                } else {
                    // If any parameters are empty, delete that slot if it exists
                    $stmt = $db->prepare("
                        DELETE FROM timetable 
                        WHERE department_id = ? AND section_id = ? AND day_of_week = ? AND period_number = ?
                    ");
                    $stmt->execute([$dept_id, $sect_id, $day, $p]);
                }
            }
            
            $db->commit();
            $success_msg = "Timetable schedule saved successfully.";
        } catch (PDOException $e) {
            $db->rollBack();
            $error_msg = "Database Error: Could not save timetable slots. " . $e->getMessage();
        }
    } else {
        $error_msg = "Please select Department, Section, and Day of the week.";
    }
}

// 2. Fetch options
$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$sections = $db->query("SELECT s.*, d.code as dept_code FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY d.code ASC, s.name ASC")->fetchAll();
$all_faculty = $db->query("SELECT id, name, department_id FROM faculty ORDER BY name ASC")->fetchAll();

// 3. Load active timetable filters if set in URL/POST
$selected_dept = (int)($_GET['department_id'] ?? 0);
$selected_section = (int)($_GET['section_id'] ?? 0);
$selected_day = sanitize_input($_GET['day_of_week'] ?? '1st Day Order');

$slots = [];
if ($selected_dept > 0 && $selected_section > 0 && !empty($selected_day)) {
    $stmt = $db->prepare("
        SELECT * FROM timetable 
        WHERE department_id = ? AND section_id = ? AND day_of_week = ?
    ");
    $stmt->execute([$selected_dept, $selected_section, $selected_day]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        $slots[$row['period_number']] = $row;
    }
}

// Timetable period time definitions
$period_times = [
    1 => '09:15 AM - 10:10 AM',
    2 => '10:10 AM - 11:05 AM',
    3 => '11:10 AM - 11:55 AM',
    4 => '11:55 AM - 12:45 PM',
    5 => '01:25 PM - 02:15 PM',
    6 => '02:15 PM - 03:05 PM',
    7 => '03:15 PM - 04:00 PM',
    8 => '04:00 PM - 04:45 PM'
];
?>

<div class="panel-title" style="margin-bottom: 24px;">📅 Timetable Scheduler Administration</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Filter Selector Panel -->
<div class="glass-panel" style="padding: 24px; margin-bottom: 28px;">
    <form method="GET" action="timetable.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 16px; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Department</label>
            <select name="department_id" id="filter-dept" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                <option value="">Select Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>" <?php echo $selected_dept === $dept['id'] ? 'selected' : ''; ?>><?php echo sanitize_input($dept['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Section</label>
            <select name="section_id" id="filter-section" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                <option value="">Select Section</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?php echo $sec['id']; ?>" data-dept="<?php echo $sec['department_id']; ?>" <?php echo $selected_section === $sec['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitize_input($sec['dept_code']) . " - Section " . sanitize_input($sec['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
         <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Day of Week</label>
            <select name="day_of_week" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                <?php foreach (['1st Day Order','2nd Day Order','3rd Day Order','4th Day Order','5th Day Order'] as $d): ?>
                    <option value="<?php echo $d; ?>" <?php echo $selected_day === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn-glass btn-primary" style="padding: 12px 24px; border-radius: 12px; height: 46px;"><i class="fa-solid fa-magnifying-glass"></i> Load Slots</button>
    </form>
</div>

<?php if ($selected_dept > 0 && $selected_section > 0): ?>
    
    <!-- Period Inputs Form -->
    <form method="POST" action="timetable.php?department_id=<?php echo $selected_dept; ?>&section_id=<?php echo $selected_section; ?>&day_of_week=<?php echo $selected_day; ?>">
        <input type="hidden" name="department_id" value="<?php echo $selected_dept; ?>">
        <input type="hidden" name="section_id" value="<?php echo $selected_section; ?>">
        <input type="hidden" name="day_of_week" value="<?php echo $selected_day; ?>">
        
        <div class="glass-panel" style="padding: 24px;">
            <h3 style="margin-bottom: 24px; font-size: 1.15rem; color: var(--text-primary);">
                Scheduling for <span style="color: var(--glow-primary);"><?php echo $selected_day; ?></span> 
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php for ($p = 1; $p <= 8; $p++): ?>
                    <?php 
                        $slot_subject = $slots[$p]['subject_name'] ?? '';
                        $slot_faculty = $slots[$p]['faculty_id'] ?? 0;
                        $slot_room = $slots[$p]['room_number'] ?? '';
                    ?>
                    
                    <div style="display: grid; grid-template-columns: 80px 180px 220px 220px 120px; gap: 16px; align-items: center; padding: 16px; border-radius: 12px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                        
                        <div style="font-weight: 700; font-family: var(--font-heading); color: var(--text-primary); font-size: 1.05rem;">Period <?php echo $p; ?></div>
                        
                        <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo $period_times[$p]; ?></div>
                        
                        <div>
                            <input type="text" name="subject_name[<?php echo $p; ?>]" value="<?php echo sanitize_input($slot_subject); ?>" class="form-control" style="padding: 8px 12px;" placeholder="Subject Name">
                        </div>
                        
                        <div>
                            <select name="faculty_id[<?php echo $p; ?>]" class="form-control" style="padding: 8px 12px; background: var(--bg-secondary); border-color: var(--border-glass);">
                                <option value="">Select Faculty</option>
                                <?php foreach ($all_faculty as $fac): ?>
                                    <option value="<?php echo $fac['id']; ?>" <?php echo $slot_faculty === $fac['id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize_input($fac['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <input type="text" name="room_number[<?php echo $p; ?>]" value="<?php echo sanitize_input($slot_room); ?>" class="form-control" style="padding: 8px 12px;" placeholder="Room No.">
                        </div>
                        
                    </div>
                    
                <?php endfor; ?>
            </div>
            
            <button type="submit" name="save_timetable" class="btn-glass btn-primary" style="margin-top: 24px; padding: 16px 32px; border-radius: 12px; width: 100%;"><i class="fa-solid fa-save"></i> SAVE WEEKDAY TIMETABLE</button>
        </div>
    </form>

<?php else: ?>
    <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary);">
        Please select a Department, Section, and Day from the filters above, then click "Load Slots" to schedule.
    </div>
<?php endif; ?>

<script>
    // Handle department change to filter sections
    document.getElementById('filter-dept').addEventListener('change', function() {
        const selectedDept = this.value;
        const sectionSelect = document.getElementById('filter-section');
        const options = sectionSelect.querySelectorAll('option');
        
        sectionSelect.value = "";
        options.forEach(opt => {
            if (opt.value === "") {
                opt.style.display = "block";
            } else {
                const deptId = opt.getAttribute('data-dept');
                opt.style.display = (deptId === selectedDept) ? "block" : "none";
            }
        });
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
