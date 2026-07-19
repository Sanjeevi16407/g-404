<?php
/**
 * Faculty CRUD Administration with Photo Uploads & Designations
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // Delete photo file if it exists and is not default
    $photo_stmt = $db->prepare("SELECT photo_url FROM faculty WHERE id = ?");
    $photo_stmt->execute([$delete_id]);
    $photo_url = $photo_stmt->fetchColumn();
    
    if ($photo_url && $photo_url !== 'assets/images/default-faculty.png') {
        $full_path = dirname(__DIR__) . '/' . $photo_url;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM faculty WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Faculty profile deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete faculty. They may be assigned to courses or timetables.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = sanitize_input($_POST['name']);
    $designation = sanitize_input($_POST['designation'] ?? 'Assistant Professor');
    $email = sanitize_input($_POST['email']);
    $cabin = sanitize_input($_POST['cabin_location']);
    $specialization = sanitize_input($_POST['subject_specialization']);
    $dept_id = (int)$_POST['department_id'];

    if (!empty($name) && !empty($designation) && !empty($email) && !empty($cabin) && !empty($specialization) && !empty($dept_id)) {
        
        // Handle Photo Upload
        $photo_url = 'assets/images/default-faculty.png';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['photo'], 'faculty');
            if ($uploaded_path) {
                $photo_url = $uploaded_path;
            }
        }

        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO faculty (name, designation, photo_url, department_id, email, cabin_location, subject_specialization) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $designation, $photo_url, $dept_id, $email, $cabin, $specialization]);
                $success_msg = "Faculty member added successfully.";
            } catch (PDOException $e) {
                $error_msg = "Email address already registered!";
            }
        } elseif ($action === 'edit') {
            $faculty_id = (int)$_POST['faculty_id'];
            
            try {
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    // Delete old photo if it is not default
                    $old_photo_stmt = $db->prepare("SELECT photo_url FROM faculty WHERE id = ?");
                    $old_photo_stmt->execute([$faculty_id]);
                    $old_photo = $old_photo_stmt->fetchColumn();
                    if ($old_photo && $old_photo !== 'assets/images/default-faculty.png') {
                        $full_old_path = dirname(__DIR__) . '/' . $old_photo;
                        if (file_exists($full_old_path)) {
                            unlink($full_old_path);
                        }
                    }
                    
                    // Update profile including new photo
                    $stmt = $db->prepare("UPDATE faculty SET name = ?, designation = ?, photo_url = ?, department_id = ?, email = ?, cabin_location = ?, subject_specialization = ? WHERE id = ?");
                    $stmt->execute([$name, $designation, $photo_url, $dept_id, $email, $cabin, $specialization, $faculty_id]);
                } else {
                    // Update profile excluding photo
                    $stmt = $db->prepare("UPDATE faculty SET name = ?, designation = ?, department_id = ?, email = ?, cabin_location = ?, subject_specialization = ? WHERE id = ?");
                    $stmt->execute([$name, $designation, $dept_id, $email, $cabin, $specialization, $faculty_id]);
                }
                $success_msg = "Faculty profile updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "Email address already registered!";
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch departments for select
$departments = $db->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();

// Fetch all faculties
$faculties = $db->query("
    SELECT f.*, d.name as dept_name 
    FROM faculty f
    JOIN departments d ON f.department_id = d.id
    ORDER BY f.name ASC
")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">👨‍🏫 Faculty Directory Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Add Faculty Profile</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Faculty list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Profile</th>
                <th>Name / Designation</th>
                <th>Department</th>
                <th>Email</th>
                <th>Cabin Location</th>
                <th>Specialization</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($faculties)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-tertiary);">No faculty profiles registered.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($faculties as $fac): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo sanitize_input($fac['photo_url']); ?>" alt="<?php echo sanitize_input($fac['name']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-glass);">
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($fac['name']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 2px; font-weight: normal;"><?php echo sanitize_input($fac['designation']); ?></div>
                        </td>
                        <td><?php echo sanitize_input($fac['dept_name']); ?></td>
                        <td><?php echo sanitize_input($fac['email']); ?></td>
                        <td><?php echo sanitize_input($fac['cabin_location']); ?></td>
                        <td><?php echo sanitize_input($fac['subject_specialization']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openEditModal(<?php echo json_encode($fac); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="faculty.php?delete=<?php echo $fac['id']; ?>" onclick="return confirm('Are you sure you want to delete this faculty member?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="faculty-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('faculty-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Register Faculty member</h3>
        
        <form method="POST" action="faculty.php" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="faculty_id" id="form-faculty-id" value="">
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" id="form-name" class="form-control" placeholder="Dr. / Mr. / Mrs. Name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" id="form-designation" class="form-control" placeholder="e.g. Assistant Professor" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="form-email" class="form-control" placeholder="faculty@saranathan.ac.in" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Cabin Location</label>
                    <input type="text" name="cabin_location" id="form-cabin" class="form-control" placeholder="Block B, L203" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject Specialization</label>
                    <input type="text" name="subject_specialization" id="form-specialization" class="form-control" placeholder="e.g. Mathematics" required>
                </div>
            </div>

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
                <label class="form-label">Profile Photo (JPG/PNG/WebP)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-faculty-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-designation').value = 'Assistant Professor';
        document.getElementById('form-email').value = '';
        document.getElementById('form-cabin').value = '';
        document.getElementById('form-specialization').value = '';
        document.getElementById('form-dept').value = '';
        document.getElementById('modal-title').innerText = 'Add Faculty Profile';
        openModal('faculty-modal');
    }

    function openEditModal(fac) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-faculty-id').value = fac.id;
        document.getElementById('form-name').value = fac.name;
        document.getElementById('form-designation').value = fac.designation || 'Assistant Professor';
        document.getElementById('form-email').value = fac.email;
        document.getElementById('form-cabin').value = fac.cabin_location;
        document.getElementById('form-specialization').value = fac.subject_specialization;
        document.getElementById('form-dept').value = fac.department_id;
        document.getElementById('modal-title').innerText = 'Edit Faculty Profile';
        openModal('faculty-modal');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
