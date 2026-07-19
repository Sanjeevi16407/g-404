<?php
/**
 * Student Portal - Settings & Interface Personalization
 */
require_once __DIR__ . '/includes/header.php';

$success_msg = "";
$error_msg = "";

$student_id = (int)$_SESSION['student_id'];

// Fetch current details
$stmt = $db->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Fetch settings row
$settings_stmt = $db->prepare("SELECT * FROM settings WHERE student_id = ? LIMIT 1");
$settings_stmt->execute([$student_id]);
$user_settings = $settings_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme = sanitize_input($_POST['theme']);
    $animation = sanitize_input($_POST['animation_speed']);
    $notif_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // Handle profile avatar file uploader
    $avatar_url = $user_settings['avatar_url'] ?? 'assets/images/default-avatar.png';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploaded_path = handle_file_upload($_FILES['avatar'], 'avatars');
        if ($uploaded_path) {
            // Delete old avatar if it's not the default avatar
            if ($avatar_url && $avatar_url !== 'assets/images/default-avatar.png') {
                $full_old_path = dirname(__DIR__) . '/' . $avatar_url;
                if (file_exists($full_old_path)) {
                    unlink($full_old_path);
                }
            }
            $avatar_url = $uploaded_path;
        }
    }

    try {
        $db->beginTransaction();
        
        // Update user settings
        $up_settings = $db->prepare("
            UPDATE settings 
            SET theme = ?, animation_speed = ?, notifications_enabled = ?, avatar_url = ? 
            WHERE student_id = ?
        ");
        $up_settings->execute([$theme, $animation, $notif_enabled, $avatar_url, $student_id]);
        
        // Update password if fields are supplied
        if (!empty($new_password)) {
            if (!empty($current_password) && password_verify($current_password, $student['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $up_pass = $db->prepare("UPDATE students SET password_hash = ? WHERE id = ?");
                $up_pass->execute([$new_hash, $student_id]);
            } else {
                throw new Exception("Incorrect current password verification!");
            }
        }
        
        $db->commit();
        $success_msg = "Personalization settings saved successfully.";
        
        // Refresh session theme variables
        $_SESSION['student_theme'] = $theme;
        
        // Reload settings
        $settings_stmt->execute([$student_id]);
        $user_settings = $settings_stmt->fetch();
    } catch (Exception $e) {
        $db->rollBack();
        $error_msg = $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-title">⚙️ Interface Personalization Settings</div>
    <span class="badge-pill badge-medium" style="text-transform: uppercase;">Theme: <?php echo sanitize_input($current_theme); ?></span>
</div>

<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-top: 16px; margin-bottom: 8px; font-size: 0.85rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div class="error-banner" style="margin-top: 16px; margin-bottom: 8px;">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>

<form method="POST" action="settings.php" enctype="multipart/form-data" autocomplete="off">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; align-items: flex-start; margin-top: 16px;">
        
        <!-- Identity Credentials Settings Panel -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 16px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-user" style="color: var(--glow-primary);"></i> Profile & Password Updates
            </h3>
            
            <div class="form-group" style="text-align: center;">
                <img src="../<?php echo !empty($user_settings['avatar_url']) ? sanitize_input($user_settings['avatar_url']) : 'assets/images/default-avatar.png'; ?>" alt="Avatar" style="width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid var(--glow-primary); box-shadow: 0 0 10px var(--glow-primary-alpha); margin-bottom: 12px;">
                <label class="form-label" style="text-align: center;">Change Avatar Profile</label>
                <input type="file" name="avatar" class="form-control" accept="image/*">
            </div>
            
            <div class="form-group" style="border-top: 1px solid var(--border-light); padding-top: 16px;">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Enter new password (minimum 6 characters)">
            </div>

            <div class="form-group">
                <label class="form-label" style="color: var(--glow-primary); font-weight: 700;">Current Password (required to change password)</label>
                <input type="password" name="current_password" class="form-control" placeholder="Verify password to confirm password edit">
            </div>
        </div>

        <!-- Customizations Toggles Panel -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 24px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-circle-half-stroke" style="color: var(--glow-secondary);"></i> Portal Themes & Toggles
            </h3>
            
            <div class="form-group">
                <label class="form-label">Select UI Theme</label>
                <select name="theme" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                    <?php foreach (['Light', 'Dark', 'Aurora', 'Liquid Glass', 'Spatial', 'System', 'Cyberpunk', 'Nord', 'Dracula', 'Constellation', 'Live'] as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo $current_theme === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Select Animation Rendering Speed</label>
                <select name="animation_speed" class="form-control" style="background: var(--bg-secondary); border-color: var(--border-glass);" required>
                    <option value="low" <?php echo $animations_enabled === 'low' ? 'selected' : ''; ?>>Low Animations (Smooth, light rendering)</option>
                    <option value="medium" <?php echo $animations_enabled === 'medium' ? 'selected' : ''; ?>>Medium Animations</option>
                    <option value="high" <?php echo $animations_enabled === 'high' ? 'selected' : ''; ?>>High Animations (Maximum premium effects)</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Interface switches</h4>
                
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                    <input type="checkbox" name="notifications_enabled" <?php echo ($user_settings['notifications_enabled'] ?? 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Voice Assistant</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">Allow Buddy to speak answers back out loud via text-to-speech.</div>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="padding: 16px; border-radius: 12px; width: 100%;"><i class="fa-solid fa-floppy-disk"></i> SAVE PERSONALIZATION</button>
        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
