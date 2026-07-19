<?php
/**
 * Student Portal - Campus Interactive Guide
 */
require_once __DIR__ . '/includes/header.php';

// Advance journey step if campus guide is completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_campus'])) {
    if ($current_step === 'campus') {
        $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'faculty' WHERE student_id = ?");
        $stmt->execute([$student_id]);

        // Add badge: "First Guide Checked"
        try {
            $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'Campus Explorer', 'fa-solid fa-map-location-dot')");
            $badge_stmt->execute([$student_id]);
            
            $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: Campus Explorer badge earned!')");
            $notif_stmt->execute([$student_id]);
        } catch (PDOException $e) {}

        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit;
    }
}

// 1. Search filter & Category filter parameters
$search_query = sanitize_input($_GET['search'] ?? '');
$category_filter = sanitize_input($_GET['category'] ?? 'all');

// 2. Fetch campus locations dynamically
$sql = "SELECT * FROM campus_locations WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR description LIKE ? OR location_details LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Category filter
if ($category_filter !== 'all') {
    if ($category_filter === 'academic') {
        $sql .= " AND name LIKE '%Block%'";
    } elseif ($category_filter === 'facilities') {
        $sql .= " AND name LIKE '%Library%' OR name LIKE '%Office%'";
    }
}

$sql .= " ORDER BY name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$locations = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-title">🗺️ Campus Interactive Guide</div>
</div>

<!-- Search & Filters Bar -->
<div class="glass-panel" style="padding: 20px; margin-top: 16px; display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <form method="GET" action="campus.php" style="display: flex; gap: 12px; flex: 1; min-width: 280px;">
        <input type="text" name="search" value="<?php echo $search_query; ?>" class="form-control" placeholder="Search classrooms, blocks, library...">
        <button type="submit" class="btn-glass btn-primary" style="padding: 10px 20px; border-radius: 8px;">SEARCH</button>
    </form>
    
    <div style="display: flex; gap: 8px;">
        <a href="campus.php?category=all&search=<?php echo $search_query; ?>" class="btn-glass <?php echo $category_filter === 'all' ? 'btn-primary' : ''; ?>" style="padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;">All Places</a>
        <a href="campus.php?category=academic&search=<?php echo $search_query; ?>" class="btn-glass <?php echo $category_filter === 'academic' ? 'btn-primary' : ''; ?>" style="padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;">Academic Blocks</a>
        <a href="campus.php?category=facilities&search=<?php echo $search_query; ?>" class="btn-glass <?php echo $category_filter === 'facilities' ? 'btn-primary' : ''; ?>" style="padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; text-decoration: none;">Facilities</a>
    </div>
</div>

<div style="margin-top: 24px;">
    
    <!-- Places Grid -->
    <div>
        <?php if (empty($locations)): ?>
            <div class="glass-panel" style="padding: 40px; text-align: center; color: var(--text-tertiary);">
                No locations match your search query. Try asking Buddy instead!
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                <?php foreach ($locations as $loc): ?>
                    <div class="glass-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                        <img src="../<?php echo sanitize_input($loc['photo_url']); ?>" alt="<?php echo sanitize_input($loc['name']); ?>" style="width: 100%; height: 160px; object-fit: cover; border-bottom: 1px solid var(--border-light);">
                        
                        <div style="padding: 20px; display: flex; flex-direction: column; gap: 8px; flex: 1;">
                            <h4 style="font-size: 1.1rem; color: var(--glow-primary); font-weight: 700;"><?php echo sanitize_input($loc['name']); ?></h4>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-solid fa-location-crosshairs" style="margin-right: 6px;"></i> <?php echo sanitize_input($loc['location_details']); ?></p>
                            <p style="font-size: 0.8rem; color: var(--text-secondary);"><i class="fa-solid fa-clock" style="margin-right: 6px;"></i> Open: <?php echo sanitize_input($loc['opening_hours']) . " - " . sanitize_input($loc['closing_hours']); ?></p>
                            
                            <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; margin-top: 10px; flex-grow: 1;">
                                <?php echo sanitize_input($loc['description']); ?>
                            </p>
                            
                            <button onclick="openBuddyChat(); document.getElementById('chat-user-input').value='Where is <?php echo addslashes($loc['name']); ?>?'; sendChatMessage();" class="btn-glass" style="margin-top: 16px; font-size: 0.8rem; border-radius: 8px;"><i class="fa-solid fa-location-arrow"></i> Ask Directions</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
