<?php
/**
 * Student Portal - Clubs Directory with Join/Leave Actions
 */
require_once __DIR__ . '/includes/header.php';

$success_msg = "";
$error_msg = "";

// 1. Handle Join / Leave POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $club_id = (int)$_POST['club_id'];
    $action = $_POST['action'];

    if ($action === 'join') {
        try {
            $stmt = $db->prepare("INSERT INTO club_registrations (student_id, club_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $club_id]);
            
            // Log Analytics event
            $club_name = $db->query("SELECT name FROM clubs WHERE id = $club_id")->fetchColumn();
            $log_stmt = $db->prepare("INSERT INTO analytics_logs (event_type, item_name) VALUES ('club_join', ?)");
            $log_stmt->execute([$club_name]);

            $success_msg = "Successfully joined the club!";
        } catch (PDOException $e) {
            $error_msg = "You are already registered for this club.";
        }
    } elseif ($action === 'leave') {
        try {
            $stmt = $db->prepare("DELETE FROM club_registrations WHERE student_id = ? AND club_id = ?");
            $stmt->execute([$student_id, $club_id]);
            $success_msg = "Successfully left the club.";
        } catch (PDOException $e) {
            $error_msg = "Operation failed. Try again.";
        }
    }
}

// 2. Fetch all clubs
$clubs = $db->query("SELECT * FROM clubs ORDER BY name ASC")->fetchAll();

// 3. Fetch current student's registered club IDs
$reg_stmt = $db->prepare("SELECT club_id FROM club_registrations WHERE student_id = ?");
$reg_stmt->execute([$student_id]);
$registered_club_ids = $reg_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <div class="page-title">👥 Extracurricular Clubs Directory</div>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-top: 16px; margin-bottom: 8px; font-size: 0.85rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div class="error-banner" style="margin-top: 16px; margin-bottom: 8px;">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>

<div style="margin-top: 16px;">
    
    <!-- Clubs Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
        <?php if (empty($clubs)): ?>
            <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary); grid-column: 1 / -1;">
                No extracurricular clubs registered yet. Check back later!
            </div>
        <?php else: ?>
            <?php foreach ($clubs as $club): 
                $is_joined = in_array($club['id'], $registered_club_ids);
            ?>
                <div class="glass-card" style="padding: 24px; display: flex; flex-direction: column; gap: 16px; position: relative;">
                    
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <img src="../<?php echo sanitize_input($club['logo_url']); ?>" alt="Club Logo" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-glass); cursor: pointer; transition: transform 0.2s;" onclick='openGlobalImageLightbox("../<?php echo sanitize_input($club['logo_url']); ?>", "<?php echo addslashes(sanitize_input($club['name'])); ?>")' title="Click to view full image" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        <div>
                            <h4 style="font-weight: 700; color: var(--text-primary); font-size: 1.05rem;"><?php echo sanitize_input($club['name']); ?></h4>
                            <span style="font-size: 0.75rem; color: var(--text-secondary);">Mentor: <?php echo sanitize_input($club['faculty_coordinator']); ?></span>
                        </div>
                    </div>
                    
                    <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; flex-grow: 1;">
                        <?php echo sanitize_input($club['description']); ?>
                    </p>

                    <div style="font-size: 0.75rem; color: var(--glow-primary); font-weight: 600; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-clock"></i> Meetings: Every Saturday 03:15 PM
                    </div>
                    
                    <div style="margin-top: 8px;">
                        <?php if ($is_joined): ?>
                            <form method="POST" action="clubs.php">
                                <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                <input type="hidden" name="action" value="leave">
                                <button type="submit" class="btn-glass" style="width: 100%; padding: 10px; border-radius: 8px; border-color: #ef4444; color: #ef4444;">LEAVE CLUB</button>
                            </form>
                        <?php else: ?>
                            <button onclick='openJoinRegistrationModal(<?php echo $club['id']; ?>, "<?php echo addslashes($club['name']); ?>")' class="btn-glass btn-primary" style="width: 100%; padding: 10px; border-radius: 8px;">JOIN CLUB</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Join Club Registration Modal -->
<div id="join-registration-modal" class="glass-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 500; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: all 0.4s ease;">
    <div class="glass-panel modal-content" style="width: 100%; max-width: 500px; padding: 36px; position: relative;">
        <i onclick="closeJoinModal()" class="fa-solid fa-xmark modal-close" style="position: absolute; top: 20px; right: 20px; font-size: 1.2rem; color: var(--text-secondary); cursor: pointer;"></i>
        <h3 id="join-modal-title" style="margin-bottom: 24px; font-size: 1.3rem; color: var(--text-primary);">Club Registration</h3>
        
        <form method="POST" action="clubs.php" autocomplete="off">
            <input type="hidden" name="club_id" id="join-club-id" value="">
            <input type="hidden" name="action" value="join">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Full Name</label>
                <input type="text" name="student_name" id="join-student-name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Registration Number</label>
                <input type="text" name="reg_no" id="join-reg-no" class="form-control" placeholder="Enter your registration number" required>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; padding: 14px; border-radius: 12px;">CONFIRM REGISTRATION</button>
        </form>
    </div>
</div>

<script>
function openJoinRegistrationModal(clubId, clubName) {
    document.getElementById('join-club-id').value = clubId;
    document.getElementById('join-modal-title').innerText = 'Register for ' + clubName;
    
    // Auto-fill from session details
    document.getElementById('join-student-name').value = "<?php echo addslashes($student_name); ?>";
    document.getElementById('join-reg-no').value = "<?php echo $_SESSION['student_reg'] ?? ''; ?>";
    
    const modal = document.getElementById('join-registration-modal');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

function closeJoinModal() {
    const modal = document.getElementById('join-registration-modal');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
