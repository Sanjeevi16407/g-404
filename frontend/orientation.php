<?php
/**
 * Student Portal - Orientation Guide Onboarding
 */
require_once __DIR__ . '/includes/header.php';

// Check if progress advancement is triggered
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_orientation'])) {
    if ($current_step === 'orientation') {
        $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'campus' WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        // Add badge: "First Guide Checked"
        try {
            $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'Orientation Scholar', 'fa-solid fa-graduation-cap')");
            $badge_stmt->execute([$student_id]);
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: Orientation Scholar badge earned!')");
            $notif_stmt->execute([$student_id]);
        } catch (PDOException $e) {}
        
        header("Location: dashboard.php");
        exit;
    }
}
?>

<div class="page-header">
    <div class="page-title">🎓 Student Orientation Guide</div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px; margin-top: 16px;">
    
    <!-- Core Guide Cards -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Welcome Card -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--glow-primary); margin-bottom: 12px; font-weight: 700;">Vanakkam Freshers!</h3>
            <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6;">
                Welcome to Saranathan College of Engineering. Founded with the vision of nurturing engineers of high integrity, this campus offers top-tier labs, sports complexes, and a state-of-the-art Central Library. This guide compiles essential rules to help you settle in smoothly.
            </p>
        </div>

        <!-- Academic & Exam Structure -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-book-open" style="color: var(--glow-primary);"></i> Academic & Exams Guidelines
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5;">
                <div>
                    <strong style="color: var(--text-primary);">1. Class Timings:</strong> 
                    Sessions start strictly at 09:15 AM and close at 04:45 PM. Timetable periods consist of 8 slots per day.
                </div>
                <div>
                    <strong style="color: var(--text-primary);">2. Attendance Requirement:</strong> 
                    Minimum 75% attendance is mandatory in each subject to be eligible to sit for Anna University Semester Exams.
                </div>
                <div>
                    <strong style="color: var(--text-primary);">3. Continuous Assessment Tests (CAT):</strong> 
                    Three CAT exams are conducted per semester to gauge academic standings. Internal marks (20%) depend directly on CAT performances and assignments.
                </div>
            </div>
        </div>

        <!-- Code of Conduct & Dress Code -->
        <div class="glass-panel" style="padding: 28px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); margin-bottom: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shirt" style="color: var(--glow-secondary);"></i> Campus Code of Conduct
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5;">
                <div>
                    <strong style="color: var(--text-primary);">Dress Code Regulations:</strong> 
                    Students must wear formal dress. Boys must wear tucked-in collared shirts and leather shoes. Girls must wear formal Salwar Kameez with Dupatta pinned securely. Round-neck T-shirts and jeans are prohibited.
                </div>
                <div>
                    <strong style="color: var(--text-primary);">Identity Cards:</strong> 
                    Wearing College ID card pinned at chest level is mandatory at all times on campus.
                </div>
                <div>
                    <strong style="color: var(--text-primary);">Cell Phone Policy:</strong> 
                    Mobile phone usage inside academic blocks, classrooms, or labs is strictly forbidden. Phones will be confiscated if rules are violated.
                </div>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
