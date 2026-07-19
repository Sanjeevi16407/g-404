<?php
/**
 * Admin Profile Management Page
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

$admin_id = (int)$_SESSION['admin_id'];

// Fetch current details
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($username) && !empty($email) && !empty($current_password)) {
        // Verify current password
        if (password_verify($current_password, $admin['password_hash'])) {
            try {
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        // Change profile including password
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $db->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ? WHERE id = ?");
                        $update_stmt->execute([$username, $email, $new_hash, $admin_id]);
                        $success_msg = "Profile and password updated successfully.";
                    } else {
                        $error_msg = "New passwords do not match!";
                    }
                } else {
                    // Update profile excluding password
                    $update_stmt = $db->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
                    $update_stmt->execute([$username, $email, $admin_id]);
                    $success_msg = "Profile updated successfully.";
                }
                
                if (empty($error_msg)) {
                    // Refresh session variables
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_email'] = $email;
                    // Reload admin details
                    $stmt->execute([$admin_id]);
                    $admin = $stmt->fetch();
                }
            } catch (PDOException $e) {
                $error_msg = "Username or Email address already taken!";
            }
        } else {
            $error_msg = "Incorrect current password!";
        }
    } else {
        $error_msg = "Please fill in Username, Email and Current Password.";
    }
}
?>

<div class="panel-title" style="margin-bottom: 24px;">👤 Admin Profile Settings</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<div class="glass-panel" style="max-width: 600px; padding: 36px; margin: 0 auto;">
    <h3 style="margin-bottom: 28px; font-size: 1.2rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
        Update Admin Credentials
    </h3>
    
    <form method="POST" action="profile.php" autocomplete="off">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" value="<?php echo sanitize_input($admin['username']); ?>" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" value="<?php echo sanitize_input($admin['email']); ?>" class="form-control" required>
        </div>
        
        <h4 style="margin-top: 32px; margin-bottom: 16px; font-size: 0.95rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">
            Change Password (Optional)
        </h4>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password">
            </div>
        </div>
        
        <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border-light); padding-top: 24px;">
            <label class="form-label" style="color: var(--glow-primary); font-weight: 700;">Current Password (Required to Save)</label>
            <input type="password" name="current_password" class="form-control" placeholder="Verify password to confirm changes" required>
        </div>

        <button type="submit" class="btn-glass btn-primary" style="width: 100%; margin-top: 16px; padding: 14px; border-radius: 12px;"><i class="fa-solid fa-user-check"></i> SAVE PROFILE CHANGES</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
