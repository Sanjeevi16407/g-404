<?php
/**
 * Admin Portal - Registrations Management Dashboard (Clubs & Events)
 */
require_once __DIR__ . '/includes/header.php';

$success_msg = "";
$error_msg = "";

// 1. Handle Deletions/Cancellations
if (isset($_GET['cancel_club_reg'])) {
    $reg_id = (int)$_GET['cancel_club_reg'];
    try {
        $stmt = $db->prepare("DELETE FROM club_registrations WHERE id = ?");
        $stmt->execute([$reg_id]);
        $success_msg = "Club registration removed successfully.";
    } catch (PDOException $e) {
        $error_msg = "Failed to cancel club registration.";
    }
}

if (isset($_GET['cancel_event_reg'])) {
    $reg_id = (int)$_GET['cancel_event_reg'];
    try {
        $stmt = $db->prepare("DELETE FROM event_registrations WHERE id = ?");
        $stmt->execute([$reg_id]);
        $success_msg = "Event slot registration removed successfully.";
    } catch (PDOException $e) {
        $error_msg = "Failed to cancel event booking.";
    }
}

// 2. Fetch Registrations
$club_regs = $db->query("
    SELECT r.id, s.name as student_name, s.register_number, d.code as dept_code, s.email, c.name as club_name, r.registered_at
    FROM club_registrations r
    JOIN students s ON r.student_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN clubs c ON r.club_id = c.id
    ORDER BY r.registered_at DESC
")->fetchAll();

$event_regs = $db->query("
    SELECT r.id, s.name as student_name, s.register_number, d.code as dept_code, s.email, e.title as event_title, r.registered_at
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    JOIN departments d ON s.department_id = d.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registered_at DESC
")->fetchAll();

$active_tab = sanitize_input($_GET['tab'] ?? 'events');
?>

<div class="table-header-row">
    <div class="panel-title">🪪 Registrations & Bookings Management</div>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Analytics Info Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 28px;">
    <div class="glass-panel" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(0, 242, 254, 0.1); border: 1px solid rgba(0, 242, 254, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--glow-primary);">
            <i class="fa-solid fa-calendar-check"></i>
        </div>
        <div>
            <span style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Total Event Bookings</span>
            <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?php echo count($event_regs); ?></h3>
        </div>
    </div>
    
    <div class="glass-panel" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(127, 0, 255, 0.1); border: 1px solid rgba(127, 0, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--glow-tertiary);">
            <i class="fa-solid fa-users-viewfinder"></i>
        </div>
        <div>
            <span style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Total Club Members</span>
            <h3 style="font-size: 1.8rem; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?php echo count($club_regs); ?></h3>
        </div>
    </div>
</div>

<!-- Category Tabs and Search Filters -->
<div class="glass-panel" style="padding: 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
    <div style="display: flex; gap: 8px;">
        <a href="registrations.php?tab=events" class="btn-glass <?php echo $active_tab === 'events' ? 'btn-primary' : ''; ?>" style="padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;">Event Slot Bookings</a>
        <a href="registrations.php?tab=clubs" class="btn-glass <?php echo $active_tab === 'clubs' ? 'btn-primary' : ''; ?>" style="padding: 10px 20px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;">Club Registrations</a>
    </div>
    
    <div style="position: relative; width: 300px;">
        <input type="text" id="reg-search" oninput="filterRegistrations()" class="form-control" placeholder="Search by name, reg no, or unit..." style="padding-left: 40px; font-size: 0.85rem;">
        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-tertiary); font-size: 0.9rem;"></i>
    </div>
</div>

<!-- Registrations List Table -->
<div class="glass-panel data-table-container">
    <?php if ($active_tab === 'events'): ?>
        <table class="custom-table" id="regs-table">
            <thead>
                <tr>
                    <th>Booked At</th>
                    <th>Register Number</th>
                    <th>Student Name</th>
                    <th>Department</th>
                    <th>Event Title</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($event_regs)): ?>
                    <tr class="table-row-item">
                        <td colspan="7" style="text-align: center; color: var(--text-tertiary); padding: 24px;">No event registrations yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($event_regs as $er): ?>
                        <tr class="table-row-item">
                            <td><?php echo date('M d, Y h:i A', strtotime($er['registered_at'])); ?></td>
                            <td style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($er['register_number']); ?></td>
                            <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($er['student_name']); ?></td>
                            <td><?php echo sanitize_input($er['dept_code']); ?></td>
                            <td style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($er['event_title']); ?></td>
                            <td><a href="mailto:<?php echo $er['email']; ?>" style="color: var(--text-secondary); text-decoration: none;"><?php echo sanitize_input($er['email']); ?></a></td>
                            <td>
                                <a href="registrations.php?tab=events&cancel_event_reg=<?php echo $er['id']; ?>" onclick="return confirm('Are you sure you want to cancel this event registration?')" class="btn-action btn-delete" title="Cancel Booking"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="custom-table" id="regs-table">
            <thead>
                <tr>
                    <th>Registered At</th>
                    <th>Register Number</th>
                    <th>Student Name</th>
                    <th>Department</th>
                    <th>Club Name</th>
                    <th>Email</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($club_regs)): ?>
                    <tr class="table-row-item">
                        <td colspan="7" style="text-align: center; color: var(--text-tertiary); padding: 24px;">No club registrations yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($club_regs as $cr): ?>
                        <tr class="table-row-item">
                            <td><?php echo date('M d, Y h:i A', strtotime($cr['registered_at'])); ?></td>
                            <td style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($cr['register_number']); ?></td>
                            <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($cr['student_name']); ?></td>
                            <td><?php echo sanitize_input($cr['dept_code']); ?></td>
                            <td style="font-weight: 600; color: var(--text-primary);"><?php echo sanitize_input($cr['club_name']); ?></td>
                            <td><a href="mailto:<?php echo $cr['email']; ?>" style="color: var(--text-secondary); text-decoration: none;"><?php echo sanitize_input($cr['email']); ?></a></td>
                            <td>
                                <a href="registrations.php?tab=clubs&cancel_club_reg=<?php echo $cr['id']; ?>" onclick="return confirm('Are you sure you want to delete this student club registration?')" class="btn-action btn-delete" title="Cancel Registration"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function filterRegistrations() {
    const query = document.getElementById('reg-search').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#regs-table tbody tr.table-row-item');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
