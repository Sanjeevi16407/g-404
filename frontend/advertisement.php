<?php
/**
 * Student Portal - Login Advertisement Splash Page
 */
require_once __DIR__ . '/../backend/db.php';

// 1. Verify student session
if (!isset($_SESSION['student_logged_in']) || $_SESSION['student_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$student_id = (int)$_SESSION['student_id'];
$student_name = $_SESSION['student_name'];

// 2. Fetch advertisement settings
$settings = $db->query("SELECT ad_image_url, ad_go_url, ad_enabled FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();

// 3. Fallback redirect if advertisement is disabled or empty
if (!$settings || $settings['ad_enabled'] != 1 || empty($settings['ad_image_url'])) {
    header("Location: dashboard.php");
    exit;
}

$ad_image = $settings['ad_image_url'];
$ad_go_url = $settings['ad_go_url'];

// 4. Mark ad as shown in this session so they only see it once per login
$_SESSION['ad_shown'] = true;

// 5. Fetch student theme settings to inherit stylesheet overrides
$theme = $_SESSION['student_theme'] ?? 'Spatial';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Buddy Assistant</title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/themes/themes.css?v=<?php echo time(); ?>">
    <!-- FontAwesome Vector Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
            overflow-y: auto;
            position: relative;
        }

        .ad-container {
            max-width: 960px;
            width: 100%;
            border-radius: 24px;
            border: 1px solid var(--border-glass);
            background: var(--bg-glass);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: var(--box-shadow);
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            transform: translateY(20px);
            opacity: 0;
            animation: ad-show-card 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes ad-show-card {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .ad-poster {
            width: 100%;
            height: auto;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            display: block;
            object-fit: contain;
        }

        .btn-ad-go {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
            margin-bottom: 8px;
            padding: 16px 44px;
            border-radius: 50px;
            background: linear-gradient(90deg, #1d4ed8 0%, #7c3aed 50%, #db2777 100%);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.35), inset 0 1px 1px rgba(255, 255, 255, 0.3);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-ad-go:hover {
            transform: scale(1.04) translateY(-2px);
            box-shadow: 0 0 35px rgba(124, 58, 237, 0.6), inset 0 1.5px 2px rgba(255, 255, 255, 0.4);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .btn-ad-go i {
            font-size: 1.15rem;
            transition: transform 0.3s ease;
        }

        .btn-ad-go:hover i {
            transform: translateX(6px);
        }

        .ad-subtext {
            font-size: 0.8rem;
            color: var(--text-tertiary);
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>

    <!-- Moving Aurora Backgrounds -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <!-- Main Advertisement Card Container -->
    <div class="ad-container">
        <!-- Event Poster Image -->
        <img src="../<?php echo htmlspecialchars($ad_image); ?>" alt="Special Announcement" class="ad-poster">
        
        <!-- Action Button -->
        <a href="<?php echo htmlspecialchars($ad_go_url); ?>" class="btn-ad-go">
            START YOUR JOURNEY WITH BUDDY <i class="fa-solid fa-arrow-right-long"></i>
        </a>
        
        <!-- Fine print subtext -->
        <div class="ad-subtext">
            One journey. Endless memories. &bull; Powered by Google Gemini
        </div>
    </div>

</body>
</html>
