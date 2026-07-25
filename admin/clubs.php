<?php
/**
 * Clubs CRUD Administration with Logo Uploads
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete club logo if it exists and is not default
    $logo_stmt = $db->prepare("SELECT logo_url FROM clubs WHERE id = ?");
    $logo_stmt->execute([$delete_id]);
    $logo_url = $logo_stmt->fetchColumn();
    
    if ($logo_url && $logo_url !== 'assets/images/default-club.png') {
        $full_path = dirname(__DIR__) . '/' . $logo_url;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM clubs WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Club entry removed successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete club. Active student registrations may exist.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = sanitize_input($_POST['name']);
    $coordinator = sanitize_input($_POST['faculty_coordinator']);
    $description = sanitize_input($_POST['description']);

    if (!empty($name) && !empty($coordinator) && !empty($description)) {
        
        // Handle Club Logo Upload
        $logo_url = 'assets/images/default-club.png';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['logo'], 'clubs');
            if ($uploaded_path) {
                $logo_url = $uploaded_path;
            }
        }

        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO clubs (name, description, faculty_coordinator, logo_url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $coordinator, $logo_url]);
                
                // Create system notifications for all students
                $students = $db->query("SELECT id FROM students")->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($students)) {
                    $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
                    foreach ($students as $stu_id) {
                        $notif_stmt->execute([$stu_id, "👥 New Club: Join the newly registered " . $name . " Club!"]);
                    }
                }
                
                $success_msg = "Club registered successfully.";
            } catch (PDOException $e) {
                $error_msg = "A club with this name already exists!";
            }
        } elseif ($action === 'edit') {
            $club_id = (int)$_POST['club_id'];
            
            try {
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    // Delete old logo if it is not default
                    $old_logo_stmt = $db->prepare("SELECT logo_url FROM clubs WHERE id = ?");
                    $old_logo_stmt->execute([$club_id]);
                    $old_logo = $old_logo_stmt->fetchColumn();
                    if ($old_logo && $old_logo !== 'assets/images/default-club.png') {
                        $full_old_path = dirname(__DIR__) . '/' . $old_logo;
                        if (file_exists($full_old_path)) {
                            unlink($full_old_path);
                        }
                    }
                    
                    // Update club details with new logo
                    $stmt = $db->prepare("UPDATE clubs SET name = ?, description = ?, faculty_coordinator = ?, logo_url = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $coordinator, $logo_url, $club_id]);
                } else {
                    // Update club details without changing logo
                    $stmt = $db->prepare("UPDATE clubs SET name = ?, description = ?, faculty_coordinator = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $coordinator, $club_id]);
                }
                $success_msg = "Club details updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "A club with this name already exists!";
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all clubs
$clubs = $db->query("SELECT * FROM clubs ORDER BY name ASC")->fetchAll();

// Fetch all student club registrations
$registrations = $db->query("
    SELECT r.club_id, s.name as student_name, s.register_number, d.code as dept_code, s.email 
    FROM club_registrations r
    JOIN students s ON r.student_id = s.id
    JOIN departments d ON s.department_id = d.id
    ORDER BY s.name ASC
")->fetchAll();

// Group registrations by club_id
$club_members = [];
foreach ($registrations as $reg) {
    $club_members[$reg['club_id']][] = $reg;
}
?>

<div class="table-header-row">
    <div class="panel-title">👥 College Clubs Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Create New Club</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Clubs list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Club Name</th>
                <th>Faculty Coordinator</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($clubs)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-tertiary);">No clubs registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($clubs as $cl): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo sanitize_input($cl['logo_url']); ?>" alt="<?php echo sanitize_input($cl['name']); ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-glass);">
                        </td>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($cl['name']); ?></td>
                        <td><?php echo sanitize_input($cl['faculty_coordinator']); ?></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize_input($cl['description']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openMembersModal("<?php echo addslashes($cl['name']); ?>", <?php echo json_encode($club_members[$cl['id']] ?? []); ?>)' class="btn-action" title="View Members" style="border-color: var(--glow-secondary); color: var(--glow-secondary);"><i class="fa-solid fa-users"></i></button>
                                <button onclick='openEditModal(<?php echo json_encode($cl); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="clubs.php?delete=<?php echo $cl['id']; ?>" onclick="return confirm('Are you sure you want to delete this club?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="club-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('club-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Register Club</h3>
        
        <form method="POST" action="clubs.php" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="club_id" id="form-club-id" value="">
            
            <div class="form-group">
                <label class="form-label">Club Name</label>
                <input type="text" name="name" id="form-name" class="form-control" placeholder="e.g. Coding Club" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Faculty Coordinator</label>
                <input type="text" name="faculty_coordinator" id="form-coordinator" class="form-control" placeholder="Name of coordinator mentor" required>
            </div>

            <div class="form-group">
                <label class="form-label">Club Description</label>
                <textarea name="description" id="form-desc" class="form-control" style="min-height: 100px; resize: vertical;" placeholder="Brief details about the club's objective..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Club Logo/Emblem (JPG/PNG/WebP)</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-club-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-coordinator').value = '';
        document.getElementById('form-desc').value = '';
        document.getElementById('modal-title').innerText = 'Create New Club';
        openModal('club-modal');
    }

    function openEditModal(cl) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-club-id').value = cl.id;
        document.getElementById('form-name').value = cl.name;
        document.getElementById('form-coordinator').value = cl.faculty_coordinator;
        document.getElementById('form-desc').value = cl.description;
        document.getElementById('modal-title').innerText = 'Edit Club Details';
        openModal('club-modal');
    }
</script>

<!-- Members Modal Dialog -->
<div id="members-modal" class="glass-modal">
    <div class="glass-panel modal-content" style="max-width: 650px;">
        <i onclick="closeModal('members-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="members-modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Club Members</h3>
        
        <div style="max-height: 350px; overflow-y: auto; border-radius: 12px; border: 1px solid var(--border-light);">
            <table class="custom-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="padding: 12px 16px;">Register No</th>
                        <th style="padding: 12px 16px;">Name</th>
                        <th style="padding: 12px 16px;">Dept</th>
                        <th style="padding: 12px 16px;">Email</th>
                    </tr>
                </thead>
                <tbody id="members-table-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openMembersModal(clubName, members) {
    document.getElementById('members-modal-title').innerText = clubName + ' - Registered Members (' + members.length + ')';
    const tbody = document.getElementById('members-table-body');
    tbody.innerHTML = '';
    
    if (members.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-tertiary); padding: 24px;">No students have registered for this club yet.</td></tr>';
    } else {
        members.forEach(m => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 12px 16px;">${m.register_number}</td>
                <td style="padding: 12px 16px; font-weight: 600; color: var(--glow-primary);">${m.student_name}</td>
                <td style="padding: 12px 16px;">${m.dept_code}</td>
                <td style="padding: 12px 16px;"><a href="mailto:${m.email}" style="color: var(--text-secondary); text-decoration: none;">${m.email}</a></td>
            `;
            tbody.appendChild(tr);
        });
    }
    openModal('members-modal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
