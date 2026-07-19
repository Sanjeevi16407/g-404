<?php
/**
 * Student Portal - Faculty Directory
 */
require_once __DIR__ . '/includes/header.php';

// Advance journey step if completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_faculty'])) {
    if ($current_step === 'faculty') {
        $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'timetable' WHERE student_id = ?");
        $stmt->execute([$student_id]);

        // Add badge: "First Guide Checked"
        try {
            $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'Academic Networker', 'fa-solid fa-user-tie')");
            $badge_stmt->execute([$student_id]);
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: Academic Networker badge earned!')");
            $notif_stmt->execute([$student_id]);
        } catch (PDOException $e) {}

        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit;
    }
}

// Fetch all faculties with designations included
$faculties = $db->query("
    SELECT f.*, d.name as dept_name 
    FROM faculty f 
    JOIN departments d ON f.department_id = d.id 
    ORDER BY f.name ASC
")->fetchAll();

// Fetch departments for select filter
$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
?>

<div class="page-header">
    <div class="page-title">👨‍🏫 Faculty Directory & Cabins</div>
</div>

<!-- Filters Bar with real-time keyup handlers -->
<div class="glass-panel" style="padding: 20px; margin-top: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; align-items: flex-end;">
    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.75rem;">Search Teacher</label>
        <input type="text" id="search-input" oninput="filterFaculty()" class="form-control" placeholder="Search by name, subject, or cabin...">
    </div>
    
    <div class="form-group" style="margin-bottom: 0;">
        <label class="form-label" style="font-size: 0.75rem;">Filter Department</label>
        <select id="dept-select" onchange="filterFaculty()" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);">
            <option value="0">All Departments</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo sanitize_input($dept['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <button onclick="filterFaculty()" class="btn-glass btn-primary" style="padding: 12px; border-radius: 12px; height: 46px; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i data-lucide="search" style="width: 16px; height: 16px;"></i> Search
    </button>
</div>

<div style="margin-top: 24px;">
    
    <!-- Faculty Grid -->
    <div>
        <div id="no-results-panel" class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary); display: none;">
            No faculty members match your query parameters.
        </div>

        <?php if (empty($faculties)): ?>
            <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary);">
                No faculty members registered.
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                <?php foreach ($faculties as $fac): ?>
                    <div class="glass-card faculty-card" data-name="<?php echo sanitize_input($fac['name']); ?>" data-dept="<?php echo $fac['department_id']; ?>" data-specialization="<?php echo sanitize_input($fac['subject_specialization']); ?>" data-cabin="<?php echo sanitize_input($fac['cabin_location']); ?>" style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 24px;">
                        <img src="../<?php echo sanitize_input($fac['photo_url']); ?>" alt="<?php echo sanitize_input($fac['name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--glow-primary); box-shadow: 0 0 10px var(--glow-primary-alpha); margin-bottom: 16px;">
                        
                        <h4 style="font-size: 1.05rem; color: var(--text-primary); font-weight: 700; margin-bottom: 2px;"><?php echo sanitize_input($fac['name']); ?></h4>
                        <div style="font-size: 0.78rem; color: var(--text-secondary); margin-bottom: 8px;"><?php echo sanitize_input($fac['designation']); ?></div>
                        <span style="font-size: 0.72rem; padding: 4px 10px; border-radius: 50px; background: rgba(0, 242, 254, 0.08); border: 1px solid rgba(0, 242, 254, 0.15); color: var(--glow-primary); font-weight: 600;"><?php echo sanitize_input($fac['dept_name']); ?></span>
                        
                        <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px; width: 100%; text-align: left; border-top: 1px solid var(--border-light); padding-top: 16px;">
                            <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-door-closed" style="color: var(--glow-secondary); width: 16px; text-align: center;"></i>
                                <span>Cabin: <strong><?php echo sanitize_input($fac['cabin_location']); ?></strong></span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-graduation-cap" style="color: var(--glow-tertiary); width: 16px; text-align: center;"></i>
                                <span>Specialization: <?php echo sanitize_input($fac['subject_specialization']); ?></span>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <i class="fa-solid fa-envelope" style="color: var(--glow-primary); width: 16px; text-align: center;"></i>
                                <span>Email: <a href="mailto:<?php echo $fac['email']; ?>" style="color: var(--text-secondary); text-decoration: none;"><?php echo sanitize_input($fac['email']); ?></a></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function filterFaculty() {
    const query = document.getElementById('search-input').value.toLowerCase().trim();
    const deptId = document.getElementById('dept-select').value;
    const cards = document.querySelectorAll('.faculty-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const spec = card.getAttribute('data-specialization').toLowerCase();
        const cabin = card.getAttribute('data-cabin').toLowerCase();
        const cardDeptId = card.getAttribute('data-dept');
        
        const matchesSearch = name.includes(query) || spec.includes(query) || cabin.includes(query);
        const matchesDept = (deptId === '0') || (cardDeptId === deptId);
        
        if (matchesSearch && matchesDept) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const noResults = document.getElementById('no-results-panel');
    if (visibleCount === 0) {
        noResults.style.display = '';
    } else {
        noResults.style.display = 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
