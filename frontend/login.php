<?php
/**
 * Student Portal - Authentication Login Page
 */
require_once __DIR__ . '/../backend/db.php';

// Redirect if already logged in
if (isset($_SESSION['student_logged_in']) && $_SESSION['student_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $register_number = sanitize_input($_POST['register_number']);
    $password = $_POST['password'];

    if (!empty($register_number) && !empty($password)) {
        // Query Student details
        $stmt = $db->prepare("SELECT * FROM students WHERE register_number = ? LIMIT 1");
        $stmt->execute([$register_number]);
        $student = $stmt->fetch();

        if ($student && password_verify($password, $student['password_hash'])) {
            // Set student sessions
            $_SESSION['student_logged_in'] = true;
            $_SESSION['student_id'] = (int)$student['id'];
            $_SESSION['student_name'] = $student['name'];
            $_SESSION['student_reg'] = $student['register_number'];
            $_SESSION['student_dept'] = (int)$student['department_id'];
            $_SESSION['student_section'] = (int)$student['section_id'];

            // Fetch theme settings to load immediately
            $settings_stmt = $db->prepare("SELECT theme FROM settings WHERE student_id = ? LIMIT 1");
            $settings_stmt->execute([$student['id']]);
            $theme = $settings_stmt->fetchColumn() ?: 'Spatial';
            $_SESSION['student_theme'] = $theme;

            // Log activity log
            $log_stmt = $db->prepare("INSERT INTO analytics_logs (event_type, item_name) VALUES ('login', ?)");
            $log_stmt->execute([$student['name']]);

            // Query journey progress step
            $journey_stmt = $db->prepare("SELECT current_step FROM journey_progress WHERE student_id = ? LIMIT 1");
            $journey_stmt->execute([$student['id']]);
            $current_step = $journey_stmt->fetchColumn() ?: 'welcome';

            // Check if login advertisement is active
            $ad_check = $db->query("SELECT ad_enabled, ad_image_url FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
            if ($ad_check && $ad_check['ad_enabled'] == 1 && !empty($ad_check['ad_image_url'])) {
                $_SESSION['ad_shown'] = false;
                header("Location: advertisement.php");
            } else {
                if ($current_step === 'welcome') {
                    header("Location: welcome.php");
                } else {
                    header("Location: dashboard.php");
                }
            }
            exit;
        } else {
            $error_msg = "Invalid register number or password!";
        }
    } else {
        $error_msg = "Please fill in all fields.";
    }
}

// Fetch college details
$college = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();
$college_name = $college['college_name'] ?? 'Saranathan College of Engineering';
$college_logo = $college['college_logo'] ?? 'assets/images/logo.png';
?>
<!DOCTYPE html>
<html lang="en" data-theme="Spatial">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Buddy Assistant</title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/themes/themes.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            text-align: center;
        }
        .logo-container {
            margin-bottom: 24px;
        }
        .logo-img {
            width: 80px;
            height: auto;
            border-radius: 50%;
            box-shadow: 0 0 20px var(--glow-primary-alpha);
        }
        .login-title {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-family: var(--font-body);
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: all var(--transition-fast);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--glow-primary);
            box-shadow: 0 0 10px var(--glow-primary-alpha);
            background: rgba(255, 255, 255, 0.07);
        }
        .btn-submit {
            width: 100%;
            margin-top: 10px;
            padding: 14px;
            font-weight: 600;
            border-radius: 12px;
        }
        .error-banner {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: left;
        }
    </style>
</head>
<body>

    <!-- Moving Aurora Backgrounds -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <!-- Login Glass Card -->
    <div class="glass-panel login-card">
        <div class="logo-container">
            <img src="../<?php echo sanitize_input($college_logo); ?>" alt="College Logo" class="logo-img">
        </div>
        <h2 class="login-title">Student Login</h2>
        <p class="login-subtitle">Access your Digital Senior Portal</p>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error-banner">
                ⚠️ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="register_number">Register Number</label>
                <input type="text" id="register_number" name="register_number" class="form-control" placeholder="e.g. 2114001" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            
            <button type="submit" class="btn-glass btn-primary btn-submit">SIGN IN</button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-light); text-align: center;">
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 12px; font-weight: 500;">
                New Student? Don't have an account yet?
            </p>
            <a href="student_registration_request.php" class="btn-glass" style="display: block; width: 100%; padding: 12px; font-weight: 600; border-radius: 12px; text-decoration: none; font-size: 0.9rem; text-align: center;">
                <i class="fa-solid fa-user-plus" style="margin-right: 8px; color: var(--glow-primary);"></i> Request Registration
            </a>
        </div>

        <p style="margin-top: 18px; font-size: 0.78rem; color: var(--text-tertiary);">
            First time logging in? Use your college-assigned register number and approved credentials.
        </p>
    </div>

</body>
</html>
