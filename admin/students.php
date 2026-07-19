<?php
/**
 * Student CRUD Administration
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Student account deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete student. They may have active registrations.";
    }
}

// 2. Handle Insert / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = sanitize_input($_POST['name']);
    $reg_num = sanitize_input($_POST['register_number']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $dept_id = (int)$_POST['department_id'];
    $sect_id = (int)$_POST['section_id'];

    if ($action === 'add') {
        $password = $_POST['password'];
        if (!empty($name) && !empty($reg_num) && !empty($email) && !empty($password) && !empty($dept_id) && !empty($sect_id)) {
            try {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO students (register_number, name, email, phone, password_hash, department_id, section_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$reg_num, $name, $email, $phone, $password_hash, $dept_id, $sect_id]);
                
                // Seed settings & journey progress for the new student
                $student_id = $db->lastInsertId();
                $db->prepare("INSERT INTO journey_progress (student_id, current_step) VALUES (?, 'welcome')")->execute([$student_id]);
                $db->prepare("INSERT INTO settings (student_id, theme, animation_speed) VALUES (?, 'Spatial', 'high')")->execute([$student_id]);
                
                $success_msg = "Student registered successfully.";
            } catch (PDOException $e) {
                $error_msg = "Registration number or Email already exists!";
            }
        } else {
            $error_msg = "Please fill in all required fields.";
        }
    } elseif ($action === 'edit') {
        $student_id = (int)$_POST['student_id'];
        $password = $_POST['password'];
        
        if (!empty($name) && !empty($reg_num) && !empty($email) && !empty($dept_id) && !empty($sect_id)) {
            try {
                if (!empty($password)) {
                    // Update profile including password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE students SET register_number = ?, name = ?, email = ?, phone = ?, password_hash = ?, department_id = ?, section_id = ? WHERE id = ?");
                    $stmt->execute([$reg_num, $name, $email, $phone, $password_hash, $dept_id, $sect_id, $student_id]);
                } else {
                    // Update profile excluding password
                    $stmt = $db->prepare("UPDATE students SET register_number = ?, name = ?, email = ?, phone = ?, department_id = ?, section_id = ? WHERE id = ?");
                    $stmt->execute([$reg_num, $name, $email, $phone, $dept_id, $sect_id, $student_id]);
                }
                $success_msg = "Student details updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "Registration number or Email already exists!";
            }
        } else {
            $error_msg = "Please fill in all required fields.";
        }
    }
}

// 3. Load dynamic options (Departments & Sections)
$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
$sections = $db->query("SELECT s.*, d.code as dept_code FROM sections s JOIN departments d ON s.department_id = d.id ORDER BY d.code ASC, s.name ASC")->fetchAll();

// 4. Fetch all students
$students = $db->query("
    SELECT s.*, d.name as dept_name, sec.name as section_name 
    FROM students s
    JOIN departments d ON s.department_id = d.id
    JOIN sections sec ON s.section_id = sec.id
    ORDER BY s.register_number ASC
")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">🎓 Student Accounts Management</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-user-plus"></i> Add New Student</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Students Grid List -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Register No.</th>
                <th>Full Name</th>
                <th>Department & Section</th>
                <th>Email</th>
                <th>Phone No.</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-tertiary);">No students registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($students as $stu): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($stu['register_number']); ?></td>
                        <td><?php echo sanitize_input($stu['name']); ?></td>
                        <td><?php echo sanitize_input($stu['dept_name']) . " - Section " . sanitize_input($stu['section_name']); ?></td>
                        <td><?php echo sanitize_input($stu['email']); ?></td>
                        <td><?php echo sanitize_input($stu['phone']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openEditModal(<?php echo json_encode($stu); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="students.php?delete=<?php echo $stu['id']; ?>" onclick="return confirm('Are you sure you want to delete this student account?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog for Add/Edit student -->
<div id="student-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('student-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Register Student</h3>
        
        <form method="POST" action="students.php" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="student_id" id="form-student-id" value="">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" id="form-name" class="form-control" placeholder="Enter student full name" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Register Number</label>
                    <input type="text" name="register_number" id="form-reg-num" class="form-control" placeholder="Register number" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" id="form-phone" class="form-control" placeholder="Phone number">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="form-email" class="form-control" placeholder="Enter student email" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="form-dept" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo sanitize_input($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Section</label>
                    <select name="section_id" id="form-section" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $sec): ?>
                            <option value="<?php echo $sec['id']; ?>" data-dept="<?php echo $sec['department_id']; ?>">
                                <?php echo sanitize_input($sec['dept_code']) . " - Section " . sanitize_input($sec['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" id="password-label">Account Password</label>
                <input type="password" name="password" id="form-password" class="form-control" placeholder="Password (minimum 6 characters)">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    // Handle department change to filter sections
    document.getElementById('form-dept').addEventListener('change', function() {
        const selectedDept = this.value;
        const sectionSelect = document.getElementById('form-section');
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

    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-student-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-reg-num').value = '';
        document.getElementById('form-phone').value = '';
        document.getElementById('form-email').value = '';
        document.getElementById('form-dept').value = '';
        document.getElementById('form-section').value = '';
        document.getElementById('form-password').value = '';
        document.getElementById('form-password').required = true;
        document.getElementById('password-label').innerText = 'Account Password';
        document.getElementById('modal-title').innerText = 'Register Student';
        openModal('student-modal');
    }

    function openEditModal(student) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-student-id').value = student.id;
        document.getElementById('form-name').value = student.name;
        document.getElementById('form-reg-num').value = student.register_number;
        document.getElementById('form-phone').value = student.phone;
        document.getElementById('form-email').value = student.email;
        document.getElementById('form-dept').value = student.department_id;
        
        // Trigger department change filter
        const event = new Event('change');
        document.getElementById('form-dept').dispatchEvent(event);
        
        document.getElementById('form-section').value = student.section_id;
        document.getElementById('form-password').value = '';
        document.getElementById('form-password').required = false;
        document.getElementById('password-label').innerText = 'Update Password (leave blank to keep current)';
        document.getElementById('modal-title').innerText = 'Edit Student Details';
        openModal('student-modal');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
