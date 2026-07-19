<?php
/**
 * Buddy AI Knowledge Base Administration (Updated Columns)
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM buddy_knowledge WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Knowledge Base entry deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete database entry.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $question = sanitize_input($_POST['question']);
    $keywords = sanitize_input($_POST['question_keywords']);
    $answer = sanitize_input($_POST['answer']);
    $category = sanitize_input($_POST['category']);
    $priority = sanitize_input($_POST['priority']);
    $status = sanitize_input($_POST['status']);

    if (!empty($question) && !empty($keywords) && !empty($answer) && !empty($category) && !empty($priority) && !empty($status)) {
        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO buddy_knowledge (question, question_keywords, category, answer, priority, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$question, $keywords, $category, $answer, $priority, $status]);
                $success_msg = "Knowledge Base Q&A added successfully.";
            } catch (PDOException $e) {
                $error_msg = "Database Error: Could not save entry.";
            }
        } elseif ($action === 'edit') {
            $qa_id = (int)$_POST['qa_id'];
            
            try {
                $stmt = $db->prepare("UPDATE buddy_knowledge SET question = ?, question_keywords = ?, category = ?, answer = ?, priority = ?, status = ? WHERE id = ?");
                $stmt->execute([$question, $keywords, $category, $answer, $priority, $status, $qa_id]);
                $success_msg = "Knowledge Base Q&A updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "Database Error: Could not update entry.";
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all knowledge entries
$entries = $db->query("SELECT * FROM buddy_knowledge ORDER BY category ASC, id DESC")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">🧠 Buddy AI Knowledge Base Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Add Q&A Entry</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<div class="glass-panel" style="padding: 20px; margin-bottom: 24px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5;">
    💡 <strong>How Buddy's Knowledge Base works:</strong> Buddy matches student questions against <strong>keywords</strong> before calling the Google Gemini API. Keywords must be comma-separated, covering common search terms (including Tanglish or slang). E.g., for canteen, keywords could be: <code>canteen, canteen yenga iruku, saapaadu, canteen timings</code>.
</div>

<!-- Knowledge list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Standard Question</th>
                <th>Search Keywords</th>
                <th>Standard Response Answer</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-tertiary);">No FAQ entries pre-loaded yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($entries as $item): ?>
                    <tr>
                        <td>
                            <span class="badge-pill badge-medium" style="text-transform: uppercase;"><?php echo sanitize_input($item['category']); ?></span>
                        </td>
                        <td style="font-weight: 600; color: var(--text-primary); max-width: 180px; word-break: break-all;"><?php echo sanitize_input($item['question']); ?></td>
                        <td style="color: var(--glow-secondary); max-width: 150px; word-break: break-all;"><?php echo sanitize_input($item['question_keywords']); ?></td>
                        <td style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo sanitize_input($item['answer']); ?></td>
                        <td>
                            <span class="badge-pill badge-<?php echo $item['priority']; ?>"><?php echo strtoupper($item['priority']); ?></span>
                        </td>
                        <td>
                            <span class="badge-pill <?php echo $item['status'] === 'active' ? 'badge-low' : 'badge-high'; ?>">
                                <?php echo strtoupper($item['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openEditModal(<?php echo json_encode($item); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="knowledge.php?delete=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure you want to delete this Q&A entry?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="qa-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('qa-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Register Q&A Entry</h3>
        
        <form method="POST" action="knowledge.php" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="qa_id" id="form-qa-id" value="">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="form-category" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="library">Library Details</option>
                        <option value="campus">Campus Locations</option>
                        <option value="faculty">Faculty Offices</option>
                        <option value="timetable">Timetable Guidance</option>
                        <option value="orientation">Orientation Rules</option>
                        <option value="general">General Queries</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" id="form-priority" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="form-status" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Standard Question Text</label>
                <input type="text" name="question" id="form-question" class="form-control" placeholder="e.g. Where is the campus library?" required>
            </div>

            <div class="form-group">
                <label class="form-label">Keywords (comma-separated, include Tamil/Tanglish slang)</label>
                <input type="text" name="question_keywords" id="form-keywords" class="form-control" placeholder="e.g. library, library yenga iruku, books" required>
            </div>

            <div class="form-group">
                <label class="form-label">Standard Response Answer</label>
                <textarea name="answer" id="form-answer" class="form-control" style="min-height: 120px; resize: vertical;" placeholder="Enter response text that Buddy will output directly..." required></textarea>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-qa-id').value = '';
        document.getElementById('form-question').value = '';
        document.getElementById('form-keywords').value = '';
        document.getElementById('form-answer').value = '';
        document.getElementById('form-category').value = 'library';
        document.getElementById('form-priority').value = 'low';
        document.getElementById('form-status').value = 'active';
        document.getElementById('modal-title').innerText = 'Add FAQ Entry';
        openModal('qa-modal');
    }

    function openEditModal(item) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-qa-id').value = item.id;
        document.getElementById('form-question').value = item.question;
        document.getElementById('form-keywords').value = item.question_keywords;
        document.getElementById('form-answer').value = item.answer;
        document.getElementById('form-category').value = item.category;
        document.getElementById('form-priority').value = item.priority;
        document.getElementById('form-status').value = item.status;
        document.getElementById('modal-title').innerText = 'Edit FAQ Entry';
        openModal('qa-modal');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
