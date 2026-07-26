<?php
/**
 * Admin Portal - Student Registration Requests Dashboard
 * Review, verify, approve, or reject student registration requests.
 */
require_once __DIR__ . '/includes/header.php';

// Auto-heal / Ensure student_registration_requests table exists on cloud DB (Railway / Production)
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS student_registration_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_name VARCHAR(150) NOT NULL,
            register_number VARCHAR(50) NOT NULL,
            department_id INT NOT NULL,
            year_level INT NOT NULL DEFAULT 1,
            section_id INT NOT NULL,
            college_email VARCHAR(150) DEFAULT NULL,
            personal_email VARCHAR(150) NOT NULL,
            mobile_number VARCHAR(20) NOT NULL,
            message TEXT DEFAULT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            rejection_reason TEXT DEFAULT NULL,
            reviewed_by VARCHAR(100) DEFAULT NULL,
            request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_date DATETIME DEFAULT NULL,
            INDEX (register_number),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

$success_msg = "";
$error_msg = "";

// 1. Handle Approve Action
if (isset($_POST['action']) && $_POST['action'] === 'approve') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    
    if ($request_id > 0) {
        $stmt = $db->prepare("SELECT * FROM student_registration_requests WHERE id = ? LIMIT 1");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();

        if ($req && $req['status'] === 'Pending') {
            // Check if Register Number already exists in students table
            $check_dup = $db->prepare("SELECT id FROM students WHERE register_number = ? LIMIT 1");
            $check_dup->execute([$req['register_number']]);
            
            if ($check_dup->fetch()) {
                $error_msg = "Student with Register Number '{$req['register_number']}' already exists in the Students directory!";
            } else {
                try {
                    $db->beginTransaction();

                    // Initial default password is set to Register Number (student can change upon login)
                    $initial_password = $req['register_number'];
                    $password_hash = password_hash($initial_password, PASSWORD_DEFAULT);
                    $email_to_use = !empty($req['college_email']) ? $req['college_email'] : $req['personal_email'];

                    // Insert student into students table
                    $insert_student = $db->prepare("
                        INSERT INTO students (register_number, name, email, phone, password_hash, department_id, section_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insert_student->execute([
                        $req['register_number'],
                        $req['student_name'],
                        $email_to_use,
                        $req['mobile_number'],
                        $password_hash,
                        $req['department_id'],
                        $req['section_id']
                    ]);
                    
                    $new_student_id = $db->lastInsertId();

                    // Seed journey progress & theme settings
                    $db->prepare("INSERT INTO journey_progress (student_id, current_step) VALUES (?, 'welcome')")->execute([$new_student_id]);
                    $db->prepare("INSERT INTO settings (student_id, theme, animation_speed) VALUES (?, 'Spatial', 'high')")->execute([$new_student_id]);

                    // Update request status to Approved
                    $admin_user = $_SESSION['admin_username'] ?? 'Administrator';
                    $update_req = $db->prepare("
                        UPDATE student_registration_requests 
                        SET status = 'Approved', reviewed_by = ?, reviewed_date = NOW() 
                        WHERE id = ?
                    ");
                    $update_req->execute([$admin_user, $request_id]);

                    $db->commit();
                    $success_msg = "🎉 Student account '{$req['student_name']}' ({$req['register_number']}) approved & created successfully!";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error_msg = "Failed to approve student request: " . $e->getMessage();
                }
            }
        }
    }
}

// 2. Handle Reject Action
if (isset($_POST['action']) && $_POST['action'] === 'reject') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? 'Registration details could not be verified.');
    
    if ($request_id > 0) {
        $admin_user = $_SESSION['admin_username'] ?? 'Administrator';
        $update_req = $db->prepare("
            UPDATE student_registration_requests 
            SET status = 'Rejected', rejection_reason = ?, reviewed_by = ?, reviewed_date = NOW() 
            WHERE id = ? AND status = 'Pending'
        ");
        $update_req->execute([$rejection_reason, $admin_user, $request_id]);
        $success_msg = "Registration request #{$request_id} has been rejected.";
    }
}

// 3. Fetch Tab Data
$active_tab = sanitize_input($_GET['tab'] ?? 'Pending');
if (!in_array($active_tab, ['Pending', 'Approved', 'Rejected'])) {
    $active_tab = 'Pending';
}

$search_term = sanitize_input($_GET['search'] ?? '');
$query_sql = "
    SELECT r.*, d.code as dept_code, d.name as dept_name, s.name as section_name 
    FROM student_registration_requests r
    LEFT JOIN departments d ON r.department_id = d.id
    LEFT JOIN sections s ON r.section_id = s.id
    WHERE r.status = ?
";
$params = [$active_tab];

if (!empty($search_term)) {
    $query_sql .= " AND (r.student_name LIKE ? OR r.register_number LIKE ? OR r.personal_email LIKE ?)";
    $params[] = "%{$search_term}%";
    $params[] = "%{$search_term}%";
    $params[] = "%{$search_term}%";
}

$query_sql .= " ORDER BY r.request_date DESC";
$requests = $db->prepare($query_sql);
$requests->execute($params);
$request_list = $requests->fetchAll();

// Counts for badges
$pending_count = $db->query("SELECT COUNT(*) FROM student_registration_requests WHERE status = 'Pending'")->fetchColumn();
$approved_count = $db->query("SELECT COUNT(*) FROM student_registration_requests WHERE status = 'Approved'")->fetchColumn();
$rejected_count = $db->query("SELECT COUNT(*) FROM student_registration_requests WHERE status = 'Rejected'")->fetchColumn();
?>

<style>
    .tab-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 12px;
        color: var(--text-secondary);
        text-decoration: none;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--border-glass);
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .tab-btn.active {
        background: rgba(0, 242, 254, 0.15);
        border-color: var(--glow-primary);
        color: var(--text-primary);
    }
    .badge-count {
        background: rgba(255, 255, 255, 0.1);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }
    .tab-btn.active .badge-count {
        background: var(--glow-primary);
        color: #000;
        font-weight: 700;
    }
    .modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.75);
        backdrop-filter: blur(8px);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal-card {
        width: 100%;
        max-width: 520px;
        background: #0d1223;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
</style>

<div class="table-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
    <div class="panel-title" style="font-size: 1.4rem; font-weight: 700; color: var(--text-primary);">
        <i class="fa-solid fa-user-clock" style="color: var(--glow-primary); margin-right: 10px;"></i> Registration Requests
    </div>

    <!-- Search Form -->
    <form method="GET" action="registration_requests.php" style="display: flex; gap: 10px;">
        <input type="hidden" name="tab" value="<?php echo sanitize_input($active_tab); ?>">
        <input type="text" name="search" value="<?php echo sanitize_input($search_term); ?>" class="form-control" placeholder="Search name, register #..." style="width: 220px; font-size: 0.85rem; padding: 8px 12px;">
        <button type="submit" class="btn-glass" style="padding: 8px 14px; font-size: 0.85rem;"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="error-banner" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #10b981; margin-bottom: 20px;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner" style="margin-bottom: 20px;">
        ⚠️ <?php echo $error_msg; ?>
    </div>
<?php endif; ?>

<!-- Navigation Tabs -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--border-light); padding-bottom: 16px; flex-wrap: wrap;">
    <a href="registration_requests.php?tab=Pending" class="tab-btn <?php echo $active_tab === 'Pending' ? 'active' : ''; ?>">
        ⏳ Pending <span class="badge-count"><?php echo $pending_count; ?></span>
    </a>
    <a href="registration_requests.php?tab=Approved" class="tab-btn <?php echo $active_tab === 'Approved' ? 'active' : ''; ?>">
        ✅ Approved <span class="badge-count"><?php echo $approved_count; ?></span>
    </a>
    <a href="registration_requests.php?tab=Rejected" class="tab-btn <?php echo $active_tab === 'Rejected' ? 'active' : ''; ?>">
        ❌ Rejected <span class="badge-count"><?php echo $rejected_count; ?></span>
    </a>
</div>

<!-- Requests Table / Cards -->
<div class="glass-panel" style="padding: 20px; border-radius: 16px; overflow-x: auto;">
    <?php if (empty($request_list)): ?>
        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
            <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
            <p>No <?php echo strtolower($active_tab); ?> registration requests found.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-light); color: var(--text-secondary); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">
                    <th style="padding: 12px;">ID / Date</th>
                    <th style="padding: 12px;">Student Name</th>
                    <th style="padding: 12px;">Register No.</th>
                    <th style="padding: 12px;">Dept & Sec</th>
                    <th style="padding: 12px;">Year</th>
                    <th style="padding: 12px;">Contact</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($request_list as $r): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 12px;">
                            <div style="font-weight: 700; color: var(--text-primary);">#<?php echo $r['id']; ?></div>
                            <div style="font-size: 0.72rem; color: var(--text-tertiary);"><?php echo date('M d, Y', strtotime($r['request_date'])); ?></div>
                        </td>
                        <td style="padding: 12px; font-weight: 600; color: var(--text-primary);">
                            <?php echo sanitize_input($r['student_name']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <span style="font-family: monospace; font-size: 0.9rem; background: rgba(0, 242, 254, 0.1); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-glass); color: var(--glow-primary);">
                                <?php echo sanitize_input($r['register_number']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <div><?php echo sanitize_input($r['dept_code']); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-tertiary);">Sec <?php echo sanitize_input($r['section_name']); ?></div>
                        </td>
                        <td style="padding: 12px; font-weight: 600;">
                            Year <?php echo (int)$r['year_level']; ?>
                        </td>
                        <td style="padding: 12px; font-size: 0.8rem;">
                            <div><i class="fa-solid fa-envelope" style="color: var(--glow-primary); margin-right: 4px;"></i> <?php echo sanitize_input($r['personal_email']); ?></div>
                            <div><i class="fa-solid fa-phone" style="color: var(--glow-primary); margin-right: 4px;"></i> <?php echo sanitize_input($r['mobile_number']); ?></div>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($r['status'] === 'Pending'): ?>
                                <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.78rem; border: 1px solid rgba(245, 158, 11, 0.3);">
                                    ⏳ Pending
                                </span>
                            <?php elseif ($r['status'] === 'Approved'): ?>
                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.78rem; border: 1px solid rgba(16, 185, 129, 0.3);">
                                    ✅ Approved
                                </span>
                            <?php else: ?>
                                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 0.78rem; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    ❌ Rejected
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                <button type="button" class="btn-glass" onclick="openViewModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" style="padding: 6px 10px; font-size: 0.8rem;" title="View Details">
                                    <i class="fa-solid fa-eye"></i> View
                                </button>

                                <?php if ($r['status'] === 'Pending'): ?>
                                    <form method="POST" action="registration_requests.php?tab=Pending" style="display: inline;" onsubmit="return confirm('Approve registration for <?php echo sanitize_input($r['student_name']); ?>? Initial password will be set to register number.');">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" class="btn-glass" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(16, 185, 129, 0.2); color: #10b981; border-color: #10b981;" title="Approve Student">
                                            <i class="fa-solid fa-circle-check"></i> Approve
                                        </button>
                                    </form>

                                    <button type="button" class="btn-glass" onclick="openRejectModal(<?php echo $r['id']; ?>, '<?php echo sanitize_input($r['student_name']); ?>')" style="padding: 6px 10px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: #ef4444;" title="Reject Request">
                                        <i class="fa-solid fa-circle-xmark"></i> Reject
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- View Details Modal -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 14px; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin: 0;">📋 Request Details</h3>
            <button type="button" onclick="closeViewModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
        
        <div id="modal-details-body" style="font-size: 0.88rem; display: flex; flex-direction: column; gap: 10px;">
            <!-- Injected dynamically -->
        </div>

        <div style="margin-top: 20px; text-align: right;">
            <button type="button" onclick="closeViewModal()" class="btn-glass" style="padding: 8px 16px; font-size: 0.85rem;">Close</button>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay">
    <div class="modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-light); padding-bottom: 14px; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #ef4444; margin: 0;">❌ Reject Registration Request</h3>
            <button type="button" onclick="closeRejectModal()" style="background: none; border: none; color: var(--text-secondary); font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="registration_requests.php?tab=Pending">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" id="reject_request_id" name="request_id" value="">

            <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 14px;" id="reject_student_text">
                Please enter a reason for rejecting this registration request:
            </p>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-size: 0.8rem; text-transform: uppercase;">Rejection Reason</label>
                <textarea name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Invalid register number. Student details do not match college records." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeRejectModal()" class="btn-glass" style="padding: 8px 16px; font-size: 0.85rem;">Cancel</button>
                <button type="submit" class="btn-glass" style="padding: 8px 16px; font-size: 0.85rem; background: rgba(239, 68, 68, 0.2); color: #ef4444; border-color: #ef4444;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openViewModal(req) {
    const body = document.getElementById('modal-details-body');
    body.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div><strong>Student Name:</strong> ${req.student_name}</div>
            <div><strong>Register Number:</strong> <span style="font-family: monospace; color: var(--glow-primary);">${req.register_number}</span></div>
            <div><strong>Department:</strong> ${req.dept_code || 'N/A'} - ${req.dept_name || ''}</div>
            <div><strong>Section:</strong> ${req.section_name || 'N/A'} (Year ${req.year_level})</div>
            <div><strong>Personal Email:</strong> ${req.personal_email}</div>
            <div><strong>College Email:</strong> ${req.college_email || 'None'}</div>
            <div><strong>Mobile Number:</strong> ${req.mobile_number}</div>
            <div><strong>Request Date:</strong> ${req.request_date}</div>
            <div><strong>Status:</strong> ${req.status}</div>
            ${req.reviewed_by ? `<div><strong>Reviewed By:</strong> ${req.reviewed_by} (${req.reviewed_date})</div>` : ''}
        </div>
        ${req.message ? `<div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid var(--border-glass);"><strong>Note from Student:</strong><br>${req.message}</div>` : ''}
        ${req.rejection_reason ? `<div style="margin-top: 10px; padding: 10px; background: rgba(239,68,68,0.1); border-radius: 8px; border: 1px solid rgba(239,68,68,0.3); color: #ef4444;"><strong>Rejection Reason:</strong><br>${req.rejection_reason}</div>` : ''}
    `;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function openRejectModal(id, name) {
    document.getElementById('reject_request_id').value = id;
    document.getElementById('reject_student_text').innerText = `Rejecting request for ${name} (#${id}). Please specify the reason:`;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
