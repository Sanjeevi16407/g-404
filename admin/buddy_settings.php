<?php
/**
 * Buddy AI Character and API Configuration Settings
 */
require_once __DIR__ . '/includes/header.php';

$error_msg = "";
$success_msg = "";

// 1. Fetch current settings row (ID=1)
$settings = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();

if (!$settings) {
    // Fail-safe initialization if database row missing
    $db->query("
        INSERT INTO buddy_settings (id, buddy_name, welcome_message, morning_message, afternoon_message, evening_message, night_message, daily_tips, enable_voice, enable_wheel, enable_predictive, gemini_api_key) 
        VALUES (1, 'Buddy', 'Welcome!', 'Good Morning!', 'Good Afternoon!', 'Good Evening!', 'Good Night!', 'Tip here', 1, 1, 1, '')
    ");
    $settings = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
}

// 2. Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buddy_name = sanitize_input($_POST['buddy_name']);
    $welcome = sanitize_input($_POST['welcome_message']);
    $morning = sanitize_input($_POST['morning_message']);
    $afternoon = sanitize_input($_POST['afternoon_message']);
    $evening = sanitize_input($_POST['evening_message']);
    $night = sanitize_input($_POST['night_message']);
    $tips = sanitize_input($_POST['daily_tips']);
    $voice = isset($_POST['enable_voice']) ? 1 : 0;
    $wheel = isset($_POST['enable_wheel']) ? 1 : 0;
    $predictive = isset($_POST['enable_predictive']) ? 1 : 0;
    $api_key = sanitize_input($_POST['gemini_api_key']);

    // Suggested Questions
    $q1_text = sanitize_input($_POST['suggest_q1_text']);
    $q1_query = sanitize_input($_POST['suggest_q1_query']);
    $q2_text = sanitize_input($_POST['suggest_q2_text']);
    $q2_query = sanitize_input($_POST['suggest_q2_query']);
    $q3_text = sanitize_input($_POST['suggest_q3_text']);
    $q3_query = sanitize_input($_POST['suggest_q3_query']);
    $q4_text = sanitize_input($_POST['suggest_q4_text']);
    $q4_query = sanitize_input($_POST['suggest_q4_query']);

    // Advertisement Settings
    $ad_enabled = isset($_POST['ad_enabled']) ? 1 : 0;
    $ad_go_url = sanitize_input($_POST['ad_go_url'] ?? 'dashboard.php');
    if (empty($ad_go_url)) {
        $ad_go_url = 'dashboard.php';
    }

    $ad_image_url = $settings['ad_image_url'] ?? null;

    // Handle File Upload for Ad Image
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['ad_image']['tmp_name'];
        $file_name = basename($_FILES['ad_image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_ext, $allowed_exts)) {
            // Create target folder
            $upload_dir = __DIR__ . '/../uploads/advertisements/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'ad_' . time() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $dest_path)) {
                $ad_image_url = 'uploads/advertisements/' . $new_filename;
            }
        } else {
            $error_msg = "Invalid file extension. Please upload PNG, JPG, JPEG or WEBP.";
        }
    }

    if (empty($error_msg)) {
        if (!empty($buddy_name) && !empty($welcome) && !empty($morning) && !empty($afternoon) && !empty($evening) && !empty($night) && !empty($tips)) {
            try {
                $stmt = $db->prepare("
                    UPDATE buddy_settings 
                    SET buddy_name = ?, welcome_message = ?, morning_message = ?, afternoon_message = ?, 
                        evening_message = ?, night_message = ?, daily_tips = ?, enable_voice = ?, 
                        enable_wheel = ?, enable_predictive = ?, gemini_api_key = ?,
                        suggest_q1_text = ?, suggest_q1_query = ?,
                        suggest_q2_text = ?, suggest_q2_query = ?,
                        suggest_q3_text = ?, suggest_q3_query = ?,
                        suggest_q4_text = ?, suggest_q4_query = ?,
                        ad_image_url = ?, ad_go_url = ?, ad_enabled = ?
                    WHERE id = 1
                ");
                $stmt->execute([
                    $buddy_name, $welcome, $morning, $afternoon, $evening, $night, $tips, $voice, $wheel, $predictive, $api_key,
                    $q1_text, $q1_query, $q2_text, $q2_query, $q3_text, $q3_query, $q4_text, $q4_query,
                    $ad_image_url, $ad_go_url, $ad_enabled
                ]);
                
                $success_msg = "Buddy AI character configuration updated successfully.";
                
                // Reload settings
                $settings = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
            } catch (Exception $e) {
                $error_msg = "Error updating database: " . $e->getMessage();
            }
        } else {
            $empty_fields = [];
            if (empty($buddy_name)) $empty_fields[] = "Assistant Name";
            if (empty($welcome)) $empty_fields[] = "Welcome Message";
            if (empty($morning)) $empty_fields[] = "Morning Greeting";
            if (empty($afternoon)) $empty_fields[] = "Afternoon Greeting";
            if (empty($evening)) $empty_fields[] = "Evening Greeting";
            if (empty($night)) $empty_fields[] = "Night Greeting";
            if (empty($tips)) $empty_fields[] = "Daily Tips & Advice";
            $error_msg = "Please fill in all character message prompts. Missing: " . implode(', ', $empty_fields);
        }
    }
}
?>

<div class="panel-title" style="margin-bottom: 24px;">🤖 Buddy AI Senior Settings</div>

<?php if (!empty($error_msg)): ?>
    <div class="error-banner">⚠️ <?php echo $error_msg; ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="badge-pill badge-low" style="padding: 12px; margin-bottom: 20px; font-size: 0.9rem; display: block; text-align: left;">
        ✅ <?php echo $success_msg; ?>
    </div>
<?php endif; ?>

<form method="POST" action="buddy_settings.php" enctype="multipart/form-data" autocomplete="off">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; align-items: flex-start;">
        
        <!-- Character & Messages Column -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 16px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-face-smile" style="color: var(--glow-primary);"></i> Buddy Character & Messages
            </h3>
            
            <div class="form-group">
                <label class="form-label">Assistant Name</label>
                <input type="text" name="buddy_name" value="<?php echo sanitize_input($settings['buddy_name']); ?>" class="form-control" placeholder="e.g. Buddy" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Welcome Message (First Session)</label>
                <textarea name="welcome_message" class="form-control" style="min-height: 80px; resize: vertical;" required><?php echo sanitize_input($settings['welcome_message']); ?></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Morning Greeting</label>
                    <textarea name="morning_message" class="form-control" style="min-height: 60px; resize: vertical;" required><?php echo sanitize_input($settings['morning_message']); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Afternoon Greeting</label>
                    <textarea name="afternoon_message" class="form-control" style="min-height: 60px; resize: vertical;" required><?php echo sanitize_input($settings['afternoon_message']); ?></textarea>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Evening Greeting</label>
                    <textarea name="evening_message" class="form-control" style="min-height: 60px; resize: vertical;" required><?php echo sanitize_input($settings['evening_message']); ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Night Greeting</label>
                    <textarea name="night_message" class="form-control" style="min-height: 60px; resize: vertical;" required><?php echo sanitize_input($settings['night_message']); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Daily Tips & Advice (Rotates randomly)</label>
                <textarea name="daily_tips" class="form-control" style="min-height: 100px; resize: vertical;" required><?php echo sanitize_input($settings['daily_tips']); ?></textarea>
            </div>
        </div>

        <!-- Suggested Questions Column -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 16px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin-bottom: 0;">
                <i class="fa-solid fa-lightbulb" style="color: var(--glow-primary);"></i> Chatbot Suggested Questions
            </h3>
            
            <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <h4 style="font-size: 0.9rem; color: var(--glow-primary); margin-bottom: 8px;">Question 1</h4>
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label">Button Label</label>
                    <input type="text" name="suggest_q1_text" value="<?php echo sanitize_input($settings['suggest_q1_text'] ?? 'Where is the library?'); ?>" class="form-control" placeholder="Button Label" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Search Query (Sent to AI)</label>
                    <input type="text" name="suggest_q1_query" value="<?php echo sanitize_input($settings['suggest_q1_query'] ?? 'Where is the campus library?'); ?>" class="form-control" placeholder="Search Query" required>
                </div>
            </div>

            <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <h4 style="font-size: 0.9rem; color: var(--glow-primary); margin-bottom: 8px;">Question 2</h4>
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label">Button Label</label>
                    <input type="text" name="suggest_q2_text" value="<?php echo sanitize_input($settings['suggest_q2_text'] ?? 'What is canteen timing?'); ?>" class="form-control" placeholder="Button Label" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Search Query (Sent to AI)</label>
                    <input type="text" name="suggest_q2_query" value="<?php echo sanitize_input($settings['suggest_q2_query'] ?? 'canteen timings?'); ?>" class="form-control" placeholder="Search Query" required>
                </div>
            </div>

            <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <h4 style="font-size: 0.9rem; color: var(--glow-primary); margin-bottom: 8px;">Question 3</h4>
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label">Button Label</label>
                    <input type="text" name="suggest_q3_text" value="<?php echo sanitize_input($settings['suggest_q3_text'] ?? 'Find Dr. Natarajan\'s cabin'); ?>" class="form-control" placeholder="Button Label" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Search Query (Sent to AI)</label>
                    <input type="text" name="suggest_q3_query" value="<?php echo sanitize_input($settings['suggest_q3_query'] ?? 'Where is Natarajan maths cabin?'); ?>" class="form-control" placeholder="Search Query" required>
                </div>
            </div>

            <div>
                <h4 style="font-size: 0.9rem; color: var(--glow-primary); margin-bottom: 8px;">Question 4</h4>
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label">Button Label</label>
                    <input type="text" name="suggest_q4_text" value="<?php echo sanitize_input($settings['suggest_q4_text'] ?? 'Library yenga iruku? (Tamil)'); ?>" class="form-control" placeholder="Button Label" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Search Query (Sent to AI)</label>
                    <input type="text" name="suggest_q4_query" value="<?php echo sanitize_input($settings['suggest_q4_query'] ?? 'library yenga iruku details pathu sollu'); ?>" class="form-control" placeholder="Search Query" required>
                </div>
            </div>
        </div>

        <!-- AI Engine & Interfaces Toggles -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 24px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                <i class="fa-solid fa-sliders" style="color: var(--glow-secondary);"></i> AI Engine & Interface Switches
            </h3>
            
            <div class="form-group">
                <label class="form-label" style="color: var(--glow-primary); font-weight: 700;">Google Gemini API Key</label>
                <input type="password" name="gemini_api_key" value="<?php echo sanitize_input($settings['gemini_api_key']); ?>" class="form-control" placeholder="AIzaSy...">
                <p style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 6px;">Used to connect Buddy to the Google Gemini Flash model when local Q&As don't match.</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Enable Interfaces</h4>
                
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                    <input type="checkbox" name="enable_voice" <?php echo $settings['enable_voice'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Voice Input</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">Allow freshers to speak questions directly.</div>
                    </div>
                </label>
                
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                    <input type="checkbox" name="enable_wheel" <?php echo $settings['enable_wheel'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Command Wheel</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">Radial shortcut menu expanding from Buddy sphere.</div>
                    </div>
                </label>
                
                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light);">
                    <input type="checkbox" name="enable_predictive" <?php echo $settings['enable_predictive'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                    <div>
                        <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Predictive Navigation</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">Intelligent links recommending what freshers should explore next.</div>
                    </div>
                </label>
            </div>
            
            <button type="submit" class="btn-glass btn-primary" style="padding: 16px; border-radius: 12px; width: 100%;"><i class="fa-solid fa-floppy-disk"></i> SAVE CONFIGURATIONS</button>
        </div>
        <!-- Login Advertisement Manager -->
        <div class="glass-panel" style="padding: 28px; display: flex; flex-direction: column; gap: 16px;">
            <h3 style="font-size: 1.15rem; color: var(--text-primary); border-bottom: 1px solid var(--border-light); padding-bottom: 12px; margin-bottom: 0;">
                <i class="fa-solid fa-rectangle-ad" style="color: var(--glow-primary);"></i> Login Advertisement Splash
            </h3>
            
            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 12px; border-radius: 8px; background: rgba(255,255,255,0.01); border: 1px solid var(--border-light); margin-bottom: 8px;">
                <input type="checkbox" name="ad_enabled" <?php echo ($settings['ad_enabled'] ?? 0) ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--glow-primary);">
                <div>
                    <div style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary);">Enable Login Advertisement</div>
                    <div style="font-size: 0.75rem; color: var(--text-tertiary);">Redirect students to advertisement page upon logging in.</div>
                </div>
            </label>

            <div class="form-group">
                <label class="form-label">Advertisement Banner/Poster</label>
                <?php if (!empty($settings['ad_image_url'])): ?>
                    <div style="margin-bottom: 12px; position: relative; border-radius: 8px; overflow: hidden; border: 1px solid var(--border-glass);">
                        <img src="../<?php echo sanitize_input($settings['ad_image_url']); ?>" alt="Current Advertisement" style="width: 100%; height: auto; max-height: 150px; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <input type="file" name="ad_image" class="form-control" accept="image/*">
                <p style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 6px;">Supported: PNG, JPG, JPEG, WEBP. Maximum size 5MB.</p>
            </div>

            <div class="form-group">
                <label class="form-label">Redirect Destination (Go Button Link)</label>
                <input type="text" name="ad_go_url" value="<?php echo sanitize_input($settings['ad_go_url'] ?? 'dashboard.php'); ?>" class="form-control" placeholder="e.g. dashboard.php or registration link" required>
                <p style="font-size: 0.75rem; color: var(--text-tertiary); margin-top: 6px;">Where the student goes when they click the main "Go" button on the advertisement.</p>
            </div>
        </div>

    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
