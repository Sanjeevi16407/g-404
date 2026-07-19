<?php
/**
 * Events CRUD Administration with Image Uploads
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete event image if it exists and is not default
    $img_stmt = $db->prepare("SELECT image_url FROM events WHERE id = ?");
    $img_stmt->execute([$delete_id]);
    $image_url = $img_stmt->fetchColumn();
    
    if ($image_url && $image_url !== 'assets/images/default-event.jpg') {
        $full_path = dirname(__DIR__) . '/' . $image_url;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Event deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete event. Active registrations may exist.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = sanitize_input($_POST['title']);
    $venue = sanitize_input($_POST['venue']);
    $date = sanitize_input($_POST['event_date']);
    $time = sanitize_input($_POST['event_time']);
    $description = sanitize_input($_POST['description']);

    if (!empty($title) && !empty($venue) && !empty($date) && !empty($time) && !empty($description)) {
        
        // Handle Event Poster Image Upload
        $image_url = 'assets/images/default-event.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['image'], 'events');
            if ($uploaded_path) {
                $image_url = $uploaded_path;
            }
        }

        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO events (title, description, image_url, venue, event_date, event_time) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $image_url, $venue, $date, $time]);
                $success_msg = "Event scheduled successfully.";
            } catch (PDOException $e) {
                $error_msg = "An event with this title already exists!";
            }
        } elseif ($action === 'edit') {
            $event_id = (int)$_POST['event_id'];
            
            try {
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    // Delete old image if it is not default
                    $old_img_stmt = $db->prepare("SELECT image_url FROM events WHERE id = ?");
                    $old_img_stmt->execute([$event_id]);
                    $old_img = $old_img_stmt->fetchColumn();
                    if ($old_img && $old_img !== 'assets/images/default-event.jpg') {
                        $full_old_path = dirname(__DIR__) . '/' . $old_img;
                        if (file_exists($full_old_path)) {
                            unlink($full_old_path);
                        }
                    }
                    
                    // Update event details with new image
                    $stmt = $db->prepare("UPDATE events SET title = ?, description = ?, image_url = ?, venue = ?, event_date = ?, event_time = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $image_url, $venue, $date, $time, $event_id]);
                } else {
                    // Update event details without changing image
                    $stmt = $db->prepare("UPDATE events SET title = ?, description = ?, venue = ?, event_date = ?, event_time = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $venue, $date, $time, $event_id]);
                }
                $success_msg = "Event schedule updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "An event with this title already exists!";
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all events
$events = $db->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();

// Fetch all student event registrations
$event_registrations = $db->query("
    SELECT r.event_id, s.name as student_name, s.register_number, d.code as dept_code, s.email 
    FROM event_registrations r
    JOIN students s ON r.student_id = s.id
    JOIN departments d ON s.department_id = d.id
    ORDER BY s.name ASC
")->fetchAll();

// Group registrations by event_id
$event_bookings = [];
foreach ($event_registrations as $reg) {
    $event_bookings[$reg['event_id']][] = $reg;
}
?>

<div class="table-header-row">
    <div class="panel-title">🎉 College Events Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Schedule New Event</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Events list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Poster</th>
                <th>Event Title</th>
                <th>Venue</th>
                <th>Date & Time</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-tertiary);">No events scheduled yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo sanitize_input($ev['image_url']); ?>" alt="<?php echo sanitize_input($ev['title']); ?>" style="width: 64px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-glass);">
                        </td>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($ev['title']); ?></td>
                        <td><?php echo sanitize_input($ev['venue']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($ev['event_date'])) . ' at ' . date('h:i A', strtotime($ev['event_time'])); ?></td>
                        <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize_input($ev['description']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openAttendeesModal("<?php echo addslashes($ev['title']); ?>", <?php echo json_encode($event_bookings[$ev['id']] ?? []); ?>)' class="btn-action" title="View Bookings" style="border-color: var(--glow-secondary); color: var(--glow-secondary);"><i class="fa-solid fa-users"></i></button>
                                <button onclick='openEditModal(<?php echo json_encode($ev); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="events.php?delete=<?php echo $ev['id']; ?>" onclick="return confirm('Are you sure you want to delete this event?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="event-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('event-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Schedule Event</h3>
        
        <form method="POST" action="events.php" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="event_id" id="form-event-id" value="">
            
            <div class="form-group">
                <label class="form-label">Event Title</label>
                <input type="text" name="title" id="form-title" class="form-control" placeholder="e.g. Hackathon 2026" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Venue</label>
                <input type="text" name="venue" id="form-venue" class="form-control" placeholder="e.g. CSE Block Seminar Hall" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Event Date</label>
                    <input type="date" name="event_date" id="form-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Event Time</label>
                    <input type="time" name="event_time" id="form-time" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Event Description</label>
                <textarea name="description" id="form-desc" class="form-control" style="min-height: 100px; resize: vertical;" placeholder="Provide event schedule details, eligibility..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Event Poster Image (JPG/PNG/WebP)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-event-id').value = '';
        document.getElementById('form-title').value = '';
        document.getElementById('form-venue').value = '';
        document.getElementById('form-date').value = '';
        document.getElementById('form-time').value = '';
        document.getElementById('form-desc').value = '';
        document.getElementById('modal-title').innerText = 'Schedule New Event';
        openModal('event-modal');
    }

    function openEditModal(ev) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-event-id').value = ev.id;
        document.getElementById('form-title').value = ev.title;
        document.getElementById('form-venue').value = ev.venue;
        document.getElementById('form-date').value = ev.event_date;
        document.getElementById('form-time').value = ev.event_time;
        document.getElementById('form-desc').value = ev.description;
        document.getElementById('modal-title').innerText = 'Edit Event Details';
        openModal('event-modal');
    }
</script>

<!-- Attendees Modal Dialog -->
<div id="attendees-modal" class="glass-modal">
    <div class="glass-panel modal-content" style="max-width: 650px;">
        <i onclick="closeModal('attendees-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="attendees-modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Event Attendees</h3>
        
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
                <tbody id="attendees-table-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function openAttendeesModal(eventTitle, attendees) {
    document.getElementById('attendees-modal-title').innerText = eventTitle + ' - Booked Slots (' + attendees.length + ')';
    const tbody = document.getElementById('attendees-table-body');
    tbody.innerHTML = '';
    
    if (attendees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-tertiary); padding: 24px;">No students have booked slots for this event yet.</td></tr>';
    } else {
        attendees.forEach(a => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 12px 16px;">${a.register_number}</td>
                <td style="padding: 12px 16px; font-weight: 600; color: var(--glow-primary);">${a.student_name}</td>
                <td style="padding: 12px 16px;">${a.dept_code}</td>
                <td style="padding: 12px 16px;"><a href="mailto:${a.email}" style="color: var(--text-secondary); text-decoration: none;">${a.email}</a></td>
            `;
            tbody.appendChild(tr);
        });
    }
    openModal('attendees-modal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
