<?php
/**
 * Announcements CRUD Administration with PDF attachments
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete PDF file if it exists
    $pdf_stmt = $db->prepare("SELECT pdf_path FROM announcements WHERE id = ?");
    $pdf_stmt->execute([$delete_id]);
    $pdf_path = $pdf_stmt->fetchColumn();
    
    if ($pdf_path) {
        $full_path = dirname(__DIR__) . '/' . $pdf_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Announcement deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete announcement. Database constraint restriction.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = sanitize_input($_POST['title']);
    $priority = sanitize_input($_POST['priority'] ?? 'low');
    $pub_date = sanitize_input($_POST['publish_date']);
    $exp_date = sanitize_input($_POST['expiry_date']);
    $description = sanitize_input($_POST['description']);

    if (!empty($title) && !empty($pub_date) && !empty($description)) {
        if (empty($exp_date)) {
            $exp_date = null;
        }

        // Handle PDF attachment upload
        $pdf_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['attachment'], 'announcements', ['application/pdf']);
            if ($uploaded_path) {
                $pdf_path = $uploaded_path;
            } else {
                $error_msg = "Attachment upload failed. Only PDF files are allowed!";
            }
        }

        if (empty($error_msg)) {
            if ($action === 'add') {
                try {
                    $stmt = $db->prepare("INSERT INTO announcements (title, description, priority, pdf_path, publish_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $priority, $pdf_path, $pub_date, $exp_date]);
                    $success_msg = "Announcement published successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database Error: Could not save announcement.";
                }
            } elseif ($action === 'edit') {
                $ann_id = (int)$_POST['announcement_id'];
                
                try {
                    if ($pdf_path) {
                        // Delete old attachment if it exists
                        $old_pdf_stmt = $db->prepare("SELECT pdf_path FROM announcements WHERE id = ?");
                        $old_pdf_stmt->execute([$ann_id]);
                        $old_pdf = $old_pdf_stmt->fetchColumn();
                        if ($old_pdf) {
                            $full_old_path = dirname(__DIR__) . '/' . $old_pdf;
                            if (file_exists($full_old_path)) {
                                unlink($full_old_path);
                            }
                        }
                        
                        // Update with new attachment
                        $stmt = $db->prepare("UPDATE announcements SET title = ?, description = ?, priority = ?, pdf_path = ?, publish_date = ?, expiry_date = ? WHERE id = ?");
                        $stmt->execute([$title, $description, $priority, $pdf_path, $pub_date, $exp_date, $ann_id]);
                    } else {
                        // Update without changing attachment
                        $stmt = $db->prepare("UPDATE announcements SET title = ?, description = ?, priority = ?, publish_date = ?, expiry_date = ? WHERE id = ?");
                        $stmt->execute([$title, $description, $priority, $pub_date, $exp_date, $ann_id]);
                    }
                    $success_msg = "Announcement updated successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database Error: Could not update announcement.";
                }
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all announcements
$announcements = $db->query("SELECT * FROM announcements ORDER BY publish_date DESC")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">📢 Campus Announcements Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Publish Announcement</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Announcements list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>Title</th>
                <th>Published On</th>
                <th>Expiry Date</th>
                <th>Description</th>
                <th>Attachment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($announcements)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-tertiary);">No announcements published yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($announcements as $ann): ?>
                    <tr>
                        <td>
                            <span class="badge-pill badge-<?php echo $ann['priority']; ?>"><?php echo strtoupper($ann['priority']); ?></span>
                        </td>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($ann['title']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($ann['publish_date'])); ?></td>
                        <td><?php echo $ann['expiry_date'] ? date('M d, Y', strtotime($ann['expiry_date'])) : 'Never Expires'; ?></td>
                        <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize_input($ann['description']); ?></td>
                        <td>
                            <?php if ($ann['pdf_path']): ?>
                                <a href="../<?php echo $ann['pdf_path']; ?>" target="_blank" style="color: var(--glow-secondary); text-decoration: none;"><i class="fa-solid fa-file-pdf"></i> View PDF</a>
                            <?php else: ?>
                                <span style="color: var(--text-tertiary);">None</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openEditModal(<?php echo json_encode($ann); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="announcements.php?delete=<?php echo $ann['id']; ?>" onclick="return confirm('Are you sure you want to delete this announcement?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="announcement-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('announcement-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Publish Announcement</h3>
        
        <form method="POST" action="announcements.php" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="announcement_id" id="form-announcement-id" value="">
            
            <div class="form-group">
                <label class="form-label">Announcement Title</label>
                <input type="text" name="title" id="form-title" class="form-control" placeholder="e.g. Semester Fee Payment Schedule" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Publish Date</label>
                    <input type="date" name="publish_date" id="form-pub-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Expiry Date (Optional)</label>
                    <input type="date" name="expiry_date" id="form-exp-date" class="form-control">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" id="form-priority" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="low">Low Priority</option>
                        <option value="medium">Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description / Core Message</label>
                <textarea name="description" id="form-desc" class="form-control" style="min-height: 100px; resize: vertical;" placeholder="Enter details about dynamic timing, requirements..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Circular Document Attachment (PDF Only)</label>
                <input type="file" name="attachment" class="form-control" accept="application/pdf">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">PUBLISH ANNOUNCEMENT</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-announcement-id').value = '';
        document.getElementById('form-title').value = '';
        
        // Default publish date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('form-pub-date').value = today;
        
        document.getElementById('form-exp-date').value = '';
        document.getElementById('form-priority').value = 'low';
        document.getElementById('form-desc').value = '';
        document.getElementById('modal-title').innerText = 'Publish Announcement';
        openModal('announcement-modal');
    }

    function openEditModal(ann) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-announcement-id').value = ann.id;
        document.getElementById('form-title').value = ann.title;
        document.getElementById('form-pub-date').value = ann.publish_date;
        document.getElementById('form-exp-date').value = ann.expiry_date || '';
        document.getElementById('form-priority').value = ann.priority;
        document.getElementById('form-desc').value = ann.description;
        document.getElementById('modal-title').innerText = 'Edit Announcement Details';
        openModal('announcement-modal');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
