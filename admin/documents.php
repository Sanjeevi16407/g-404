<?php
/**
 * Academic Documents Upload & Administration
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete file if it exists
    $file_stmt = $db->prepare("SELECT file_path FROM documents WHERE id = ?");
    $file_stmt->execute([$delete_id]);
    $file_path = $file_stmt->fetchColumn();
    
    if ($file_path) {
        $full_path = dirname(__DIR__) . '/' . $file_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Academic document deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete document. Database constraint restriction.";
    }
}

// 2. Handle Document Upload submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);

    if (!empty($title)) {
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['document_file'], 'documents', ['application/pdf']);
            if ($uploaded_path) {
                try {
                    $stmt = $db->prepare("INSERT INTO documents (title, file_path) VALUES (?, ?)");
                    $stmt->execute([$title, $uploaded_path]);
                    $success_msg = "Academic document uploaded successfully.";
                } catch (PDOException $e) {
                    $error_msg = "Database Error: Could not log document details.";
                }
            } else {
                $error_msg = "Document upload failed. Make sure it is a valid PDF file.";
            }
        } else {
            $error_msg = "Please select a valid PDF file to upload.";
        }
    } else {
        $error_msg = "Please enter a document title.";
    }
}

// Fetch all documents
$documents = $db->query("SELECT * FROM documents ORDER BY uploaded_at DESC")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">📄 Academic Documents Administration</div>
    <button onclick="openModal('document-modal')" class="btn-glass btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Documents list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Document Title</th>
                <th>File Path</th>
                <th>Uploaded At</th>
                <th>Download Link</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($documents)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-tertiary);">No academic documents uploaded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($doc['title']); ?></td>
                        <td style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo sanitize_input($doc['file_path']); ?></td>
                        <td><?php echo date('M d, Y \a\t h:i A', strtotime($doc['uploaded_at'])); ?></td>
                        <td>
                            <a href="../<?php echo $doc['file_path']; ?>" target="_blank" style="color: var(--glow-secondary); text-decoration: none;"><i class="fa-solid fa-download"></i> Download PDF</a>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="documents.php?delete=<?php echo $doc['id']; ?>" onclick="return confirm('Are you sure you want to delete this document?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="document-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('document-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 style="margin-bottom: 24px; font-size: 1.3rem;">Upload Academic Document</h3>
        
        <form method="POST" action="documents.php" enctype="multipart/form-data" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Document Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. First Year Academic Calendar 2026-27" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Document File (PDF Only)</label>
                <input type="file" name="document_file" class="form-control" accept="application/pdf" required>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">UPLOAD ACADEMIC DOCUMENT</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
