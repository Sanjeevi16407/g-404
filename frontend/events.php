<?php
/**
 * Student Portal - Campus Events with Registrations & Categorized Tabs
 */
require_once __DIR__ . '/includes/header.php';

$success_msg = "";
$error_msg = "";

// 1. Handle Event Registration POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $event_id = (int)$_POST['event_id'];
    $action = $_POST['action'];

    if ($action === 'register') {
        try {
            $stmt = $db->prepare("INSERT INTO event_registrations (student_id, event_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $event_id]);
            
            // Log Analytics event
            $event_name = $db->query("SELECT title FROM events WHERE id = $event_id")->fetchColumn();
            $log_stmt = $db->prepare("INSERT INTO analytics_logs (event_type, item_name) VALUES ('event_register', ?)");
            $log_stmt->execute([$event_name]);

            $success_msg = "Successfully registered for the event!";
        } catch (PDOException $e) {
            $error_msg = "You are already registered for this event.";
        }
    } elseif ($action === 'cancel') {
        try {
            $stmt = $db->prepare("DELETE FROM event_registrations WHERE student_id = ? AND event_id = ?");
            $stmt->execute([$student_id, $event_id]);
            $success_msg = "Registration cancelled successfully.";
        } catch (PDOException $e) {
            $error_msg = "Operation failed. Try again.";
        }
    }
}

// 2. Fetch events split by tabs: Upcoming, Ongoing, Completed
$today = date('Y-m-d');

$upcoming = $db->prepare("SELECT * FROM events WHERE event_date > ? ORDER BY event_date ASC");
$upcoming->execute([$today]);
$upcoming_events = $upcoming->fetchAll();

$ongoing = $db->prepare("SELECT * FROM events WHERE event_date = ? ORDER BY event_time ASC");
$ongoing->execute([$today]);
$ongoing_events = $ongoing->fetchAll();

$completed = $db->prepare("SELECT * FROM events WHERE event_date < ? ORDER BY event_date DESC");
$completed->execute([$today]);
$completed_events = $completed->fetchAll();

// 3. Fetch student's registered event IDs
$reg_stmt = $db->prepare("SELECT event_id FROM event_registrations WHERE student_id = ?");
$reg_stmt->execute([$student_id]);
$registered_event_ids = $reg_stmt->fetchAll(PDO::FETCH_COLUMN);

// Active Tab parameter
$active_tab = sanitize_input($_GET['tab'] ?? 'upcoming');
?>

<div class="page-header">
    <div class="page-title">🎉 College Events Calendar</div>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-top: 16px; margin-bottom: 8px; font-size: 0.85rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div class="error-banner" style="margin-top: 16px; margin-bottom: 8px;">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>

<!-- Tabs Selector Bar -->
<div class="glass-panel" style="padding: 12px; margin-top: 16px; display: flex; gap: 8px;">
    <a href="events.php?tab=upcoming" class="btn-glass <?php echo $active_tab === 'upcoming' ? 'btn-primary' : ''; ?>" style="padding: 10px 24px; border-radius: 8px; font-size: 0.9rem; text-decoration: none;">Upcoming Events</a>
    <a href="events.php?tab=ongoing" class="btn-glass <?php echo $active_tab === 'ongoing' ? 'btn-primary' : ''; ?>" style="padding: 10px 24px; border-radius: 8px; font-size: 0.9rem; text-decoration: none;">Ongoing Today</a>
    <a href="events.php?tab=completed" class="btn-glass <?php echo $active_tab === 'completed' ? 'btn-primary' : ''; ?>" style="padding: 10px 24px; border-radius: 8px; font-size: 0.9rem; text-decoration: none;">Completed Past</a>
</div>

<div style="margin-top: 24px;">
    
    <!-- Events Grid list -->
    <div>
        <?php 
            $selected_events = [];
            if ($active_tab === 'upcoming') $selected_events = $upcoming_events;
            elseif ($active_tab === 'ongoing') $selected_events = $ongoing_events;
            elseif ($active_tab === 'completed') $selected_events = $completed_events;
        ?>

        <?php if (empty($selected_events)): ?>
            <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary);">
                No events found under this category.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($selected_events as $ev): 
                    $is_registered = in_array($ev['id'], $registered_event_ids);
                ?>
                    <div class="glass-card" style="display: flex; flex-direction: row; gap: 24px; padding: 24px; align-items: center; flex-wrap: wrap;">
                        <img src="../<?php echo sanitize_input($ev['image_url']); ?>" alt="Event Poster" style="width: 180px; height: 120px; border-radius: 12px; object-fit: cover; border: 1px solid var(--border-glass); flex-shrink: 0;">
                        
                        <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 6px;">
                            <h4 style="font-weight: 700; color: var(--text-primary); font-size: 1.25rem;"><?php echo sanitize_input($ev['title']); ?></h4>
                            <div style="font-size: 0.8rem; color: var(--glow-primary); font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-map-pin"></i> Venue: <?php echo sanitize_input($ev['venue']); ?>
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-calendar-day"></i> Date: <?php echo date('M d, Y', strtotime($ev['event_date'])) . ' at ' . date('h:i A', strtotime($ev['event_time'])); ?>
                            </div>
                            
                            <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; margin-top: 8px;">
                                <?php echo sanitize_input($ev['description']); ?>
                            </p>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 10px; width: 120px;">
                            <?php if ($active_tab !== 'completed'): ?>
                                <?php if ($is_registered): ?>
                                    <form method="POST" action="events.php?tab=<?php echo $active_tab; ?>">
                                        <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit" class="btn-glass" style="width: 100%; font-size: 0.8rem; border-color: #ef4444; color: #ef4444; padding: 10px; border-radius: 8px;">CANCEL</button>
                                    </form>
                                <?php else: ?>
                                    <button onclick='openEventBookingModal(<?php echo $ev['id']; ?>, "<?php echo addslashes($ev['title']); ?>")' class="btn-glass btn-primary" style="width: 100%; font-size: 0.8rem; padding: 10px; border-radius: 8px;">BOOK SLOT</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="badge-pill badge-medium" style="text-align: center; padding: 8px;">Completed</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Event Booking Registration Modal -->
<div id="event-booking-modal" class="glass-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 500; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: all 0.4s ease;">
    <div class="glass-panel modal-content" style="width: 100%; max-width: 500px; padding: 36px; position: relative;">
        <i onclick="closeEventModal()" class="fa-solid fa-xmark modal-close" style="position: absolute; top: 20px; right: 20px; font-size: 1.2rem; color: var(--text-secondary); cursor: pointer;"></i>
        <h3 id="event-modal-title" style="margin-bottom: 24px; font-size: 1.3rem; color: var(--text-primary);">Event Registration</h3>
        
        <form method="POST" action="events.php?tab=<?php echo $active_tab; ?>" autocomplete="off">
            <input type="hidden" name="event_id" id="booking-event-id" value="">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Full Name</label>
                <input type="text" name="student_name" id="booking-student-name" class="form-control" placeholder="Enter your full name" required>
            </div>
            
            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" style="display: block; margin-bottom: 8px; font-size: 0.85rem; color: var(--text-secondary);">Registration Number</label>
                <input type="text" name="reg_no" id="booking-reg-no" class="form-control" placeholder="Enter your registration number" required>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; padding: 14px; border-radius: 12px;">CONFIRM REGISTRATION</button>
        </form>
    </div>
</div>

<script>
function openEventBookingModal(eventId, eventTitle) {
    document.getElementById('booking-event-id').value = eventId;
    document.getElementById('event-modal-title').innerText = 'Register for ' + eventTitle;
    
    // Auto-fill from session details
    document.getElementById('booking-student-name').value = "<?php echo addslashes($student_name); ?>";
    document.getElementById('booking-reg-no').value = "<?php echo $_SESSION['student_reg'] ?? ''; ?>";
    
    const modal = document.getElementById('event-booking-modal');
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
}

function closeEventModal() {
    const modal = document.getElementById('event-booking-modal');
    modal.style.opacity = '0';
    modal.style.pointerEvents = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
