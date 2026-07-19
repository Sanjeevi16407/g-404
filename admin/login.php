<?php
/**
 * Admin Portal Login (Renamed and refactored)
 */
require_once __DIR__ . '/../backend/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // Query Admin credentials
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_id'] = $admin['id'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error_msg = "Invalid username or password!";
        }
    } else {
        $error_msg = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="Spatial">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Buddy Assistant</title>
    <!-- Core stylesheets -->
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

    <!-- Login Glass Panel -->
    <div class="glass-panel login-card">
        <div class="logo-container">
            <img src="../assets/images/logo.png" alt="Saranathan Logo" class="logo-img">
        </div>
        <h2 class="login-title">Digital Senior</h2>
        <p class="login-subtitle">Administrative Login Portal</p>
        
        <?php if (!empty($error_msg)): ?>
            <div class="error-banner">
                ⚠️ <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" required>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter account password" required>
            </div>
            
            <button type="submit" class="btn-glass btn-primary btn-submit">LOG IN</button>
        </form>
    </div>

</body>
</html>
