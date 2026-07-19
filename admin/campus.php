<?php
/**
 * Campus Locations CRUD Administration with Photo Uploads
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Handle Deletions
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete location photo if it exists and is not default
    $photo_stmt = $db->prepare("SELECT photo_url FROM campus_locations WHERE id = ?");
    $photo_stmt->execute([$delete_id]);
    $photo_url = $photo_stmt->fetchColumn();
    
    if ($photo_url && $photo_url !== 'assets/images/default-campus.jpg') {
        $full_path = dirname(__DIR__) . '/' . $photo_url;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    try {
        $stmt = $db->prepare("DELETE FROM campus_locations WHERE id = ?");
        $stmt->execute([$delete_id]);
        $success_msg = "Campus location deleted successfully.";
    } catch (PDOException $e) {
        $error_msg = "Could not delete location. Database constraint restriction.";
    }
}

// 2. Handle Add / Edit submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = sanitize_input($_POST['name']);
    $details = sanitize_input($_POST['location_details']);
    $open_hrs = sanitize_input($_POST['opening_hours']);
    $close_hrs = sanitize_input($_POST['closing_hours']);
    $description = sanitize_input($_POST['description']);

    if (!empty($name) && !empty($details) && !empty($open_hrs) && !empty($close_hrs) && !empty($description)) {
        
        // Handle Photo Upload
        $photo_url = 'assets/images/default-campus.jpg';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['photo'], 'campus');
            if ($uploaded_path) {
                $photo_url = $uploaded_path;
            }
        }

        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO campus_locations (name, description, photo_url, opening_hours, closing_hours, location_details) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $photo_url, $open_hrs, $close_hrs, $details]);
                $success_msg = "Campus location registered successfully.";
            } catch (PDOException $e) {
                $error_msg = "A location with this name already exists!";
            }
        } elseif ($action === 'edit') {
            $loc_id = (int)$_POST['location_id'];
            
            try {
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    // Delete old photo if it is not default
                    $old_photo_stmt = $db->prepare("SELECT photo_url FROM campus_locations WHERE id = ?");
                    $old_photo_stmt->execute([$loc_id]);
                    $old_photo = $old_photo_stmt->fetchColumn();
                    if ($old_photo && $old_photo !== 'assets/images/default-campus.jpg') {
                        $full_old_path = dirname(__DIR__) . '/' . $old_photo;
                        if (file_exists($full_old_path)) {
                            unlink($full_old_path);
                        }
                    }
                    
                    // Update location with new photo
                    $stmt = $db->prepare("UPDATE campus_locations SET name = ?, description = ?, photo_url = ?, opening_hours = ?, closing_hours = ?, location_details = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $photo_url, $open_hrs, $close_hrs, $details, $loc_id]);
                } else {
                    // Update location details without changing photo
                    $stmt = $db->prepare("UPDATE campus_locations SET name = ?, description = ?, opening_hours = ?, closing_hours = ?, location_details = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $open_hrs, $close_hrs, $details, $loc_id]);
                }
                $success_msg = "Campus location details updated successfully.";
            } catch (PDOException $e) {
                $error_msg = "A location with this name already exists!";
            }
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch all campus locations
$locations = $db->query("SELECT * FROM campus_locations ORDER BY name ASC")->fetchAll();
?>

<div class="table-header-row">
    <div class="panel-title">🗺️ Campus Physical Locations Administration</div>
    <button onclick="openAddModal()" class="btn-glass btn-primary"><i class="fa-solid fa-plus"></i> Add New Location</button>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<!-- Locations list panel -->
<div class="glass-panel data-table-container">
    <table class="custom-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>Place Name</th>
                <th>Location details</th>
                <th>Working Hours</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($locations)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-tertiary);">No campus locations registered yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($locations as $loc): ?>
                    <tr>
                        <td>
                            <img src="../<?php echo sanitize_input($loc['photo_url']); ?>" alt="<?php echo sanitize_input($loc['name']); ?>" style="width: 64px; height: 44px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-glass);">
                        </td>
                        <td style="font-weight: 600; color: var(--glow-primary);"><?php echo sanitize_input($loc['name']); ?></td>
                        <td><?php echo sanitize_input($loc['location_details']); ?></td>
                        <td><?php echo sanitize_input($loc['opening_hours']) . " - " . sanitize_input($loc['closing_hours']); ?></td>
                        <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize_input($loc['description']); ?></td>
                        <td>
                            <div class="action-btns">
                                <button onclick='openEditModal(<?php echo json_encode($loc); ?>)' class="btn-action btn-edit"><i class="fa-solid fa-pen"></i></button>
                                <a href="campus.php?delete=<?php echo $loc['id']; ?>" onclick="return confirm('Are you sure you want to delete this campus location?')" class="btn-action btn-delete"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Dialog -->
<div id="location-modal" class="glass-modal">
    <div class="glass-panel modal-content">
        <i onclick="closeModal('location-modal')" class="fa-solid fa-xmark modal-close"></i>
        <h3 id="modal-title" style="margin-bottom: 24px; font-size: 1.3rem;">Register Campus Location</h3>
        
        <form method="POST" action="campus.php" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="location_id" id="form-location-id" value="">
            
            <div class="form-group">
                <label class="form-label">Place Name</label>
                <input type="text" name="name" id="form-name" class="form-control" placeholder="e.g. Central Library" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Location Details</label>
                <input type="text" name="location_details" id="form-details" class="form-control" placeholder="e.g. Block A, First Floor" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Opening Time</label>
                    <input type="text" name="opening_hours" id="form-open" class="form-control" placeholder="e.g. 09:00 AM" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Closing Time</label>
                    <input type="text" name="closing_hours" id="form-close" class="form-control" placeholder="e.g. 05:30 PM" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="form-desc" class="form-control" style="min-height: 100px; resize: vertical;" placeholder="Add guidelines, resources, or rules..." required></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Location Photo (JPG/PNG/WebP)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 10px; padding: 14px; border-radius: 12px;">SAVE DETAILS</button>
        </form>
    </div>
</div>

<script>
    function openAddModal() {
        document.getElementById('form-action').value = 'add';
        document.getElementById('form-location-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-details').value = '';
        document.getElementById('form-open').value = '';
        document.getElementById('form-close').value = '';
        document.getElementById('form-desc').value = '';
        document.getElementById('modal-title').innerText = 'Add Campus Location';
        openModal('location-modal');
    }

    function openEditModal(loc) {
        document.getElementById('form-action').value = 'edit';
        document.getElementById('form-location-id').value = loc.id;
        document.getElementById('form-name').value = loc.name;
        document.getElementById('form-details').value = loc.location_details;
        document.getElementById('form-open').value = loc.opening_hours;
        document.getElementById('form-close').value = loc.closing_hours;
        document.getElementById('form-desc').value = loc.description;
        document.getElementById('modal-title').innerText = 'Edit Campus Location';
        openModal('location-modal');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
