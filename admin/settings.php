<?php
/**
 * College Settings Configuration Page
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Fetch current settings row (ID=1)
$settings = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();

if (!$settings) {
    // Fail-safe initialization
    $db->query("
        INSERT INTO college_settings (id, college_name, college_logo, college_email, college_phone, address, footer_text, default_theme, maintenance_mode) 
        VALUES (1, 'Saranathan College of Engineering', 'assets/images/logo.png', 'info@saranathan.ac.in', '0431-2908446', 'Tiruchirappalli', 'All rights reserved.', 'Spatial', 0)
    ");
    $settings = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();
}

// 2. Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['college_name']);
    $email = sanitize_input($_POST['college_email']);
    $phone = sanitize_input($_POST['college_phone']);
    $address = sanitize_input($_POST['address']);
    $footer = sanitize_input($_POST['footer_text']);
    $theme = sanitize_input($_POST['default_theme']);
    $maintenance = isset($_POST['maintenance_mode']) ? 1 : 0;

    if (!empty($name) && !empty($email) && !empty($phone) && !empty($address) && !empty($footer) && !empty($theme)) {
        
        // Handle College Logo Upload
        $logo_url = $settings['college_logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploaded_path = handle_file_upload($_FILES['logo'], 'campus');
            if ($uploaded_path) {
                // Delete old logo if it's not the default logo
                if ($logo_url && $logo_url !== 'assets/images/logo.png') {
                    $full_old_path = dirname(__DIR__) . '/' . $logo_url;
                    if (file_exists($full_old_path)) {
                        unlink($full_old_path);
                    }
                }
                $logo_url = $uploaded_path;
            }
        }

        try {
            $stmt = $db->prepare("
                UPDATE college_settings 
                SET college_name = ?, college_logo = ?, college_email = ?, college_phone = ?, 
                    address = ?, footer_text = ?, default_theme = ?, maintenance_mode = ? 
                WHERE id = 1
            ");
            $stmt->execute([$name, $logo_url, $email, $phone, $address, $footer, $theme, $maintenance]);
            
            $success_msg = "General college settings saved successfully.";
            
            // Reload settings
            $settings = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please fill in all required configuration fields.";
    }
}
?>

<div class="panel-title" style="margin-bottom: 24px;">⚙️ College General Configuration Settings</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<form method="POST" action="settings.php" enctype="multipart/form-data" autocomplete="off">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; align-items: flex-start;">
        
        <!-- General Identity Card -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 16px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-university" style="color: var(--glow-primary);"></i> College Identity Details
            </h3>
            
            <div class="form-group">
                <label class="form-label">College Name</label>
                <input type="text" name="college_name" value="<?php echo sanitize_input($settings['college_name']); ?>" class="form-control" placeholder="College Name" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="college_email" value="<?php echo sanitize_input($settings['college_email']); ?>" class="form-control" placeholder="info@college.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="college_phone" value="<?php echo sanitize_input($settings['college_phone']); ?>" class="form-control" placeholder="Phone Number" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Physical Address</label>
                <textarea name="address" class="form-control" style="min-height: 80px; resize: vertical;" required><?php echo sanitize_input($settings['address']); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Footer Copyright Text</label>
                <input type="text" name="footer_text" value="<?php echo sanitize_input($settings['footer_text']); ?>" class="form-control" required>
            </div>
        </div>

        <!-- Portal Operations & Appearance -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 24px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-circle-half-stroke" style="color: var(--glow-secondary);"></i> Portal Control & Styling
            </h3>
            
            <div class="form-group">
                <label class="form-label">Default Theme (For guest login)</label>
                <select name="default_theme" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                    <?php foreach (['Light', 'Dark', 'Aurora', 'Liquid Glass', 'Spatial', 'System', 'Cyberpunk', 'Nord', 'Dracula', 'Live'] as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $settings['default_theme'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="text-align: left;">
                <label class="form-label">College Logo Image</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                <!-- If logo exists, show current logo preview -->
                <?php if ($settings['college_logo']): ?>
                    <div style="margin-top: 12px; text-align: center;">
                        <span style="font-size: 0.75rem; color: var(--text-tertiary); display: block; margin-bottom: 6px;">Current Logo:</span>
                        <img src="../<?php echo $settings['college_logo']; ?>" alt="Current Logo" style="width: 60px; height: 60px; border-radius: 50%; border: 1px solid var(--border-glass);">
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">System Actions</h4>
                
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                    <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Maintenance Mode</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">Lock student pages during system configuration.</div>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="padding: 16px; border-radius: 12px; width: 100%;"><i class="fa-solid fa-floppy-disk"></i> SAVE CONFIGURATIONS</button>
        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
