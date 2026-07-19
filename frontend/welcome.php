<?php
/**
 * Student Portal - Interactive Buddy Onboarding Welcome Screen
 */
require_once __DIR__ . '/../backend/db.php';
check_student_session();

$student_id = (int)$_SESSION['student_id'];
$student_name = sanitize_input($_SESSION['student_name']);

// If student clicks "Begin Journey", advance journey progress step to 'orientation'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_journey'])) {
    $stmt = $db->prepare("UPDATE journey_progress SET current_step = 'orientation' WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    // Add achievement badge: "Campus Explorer"
    try {
        $badge_stmt = $db->prepare("INSERT INTO achievements (student_id, badge_name, badge_icon) VALUES (?, 'First Contact', 'fa-solid fa-handshake')");
        $badge_stmt->execute([$student_id]);
        
        $notif_stmt = $db->prepare("INSERT INTO notifications (student_id, message) VALUES (?, '🎉 Achievement unlocked: First Contact badge earned!')");
        $notif_stmt->execute([$student_id]);
    } catch (PDOException $e) {}

    header("Location: dashboard.php");
    exit;
}

// Fetch buddy details
$buddy = $db->query("SELECT * FROM buddy_settings WHERE id = 1 LIMIT 1")->fetch();
$buddy_name = $buddy['buddy_name'] ?? 'Buddy';
$welcome_message = $buddy['welcome_message'] ?? "Welcome to Saranathan College of Engineering! I'm Buddy, your Digital Senior. Let's begin your journey!";
$enable_voice = $buddy['enable_voice'] ?? 1;

// Replace placeholder in welcome message with student name
$welcome_message = str_replace('[Student Name]', $student_name, $welcome_message);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo sanitize_input($_SESSION['student_theme'] ?? 'Spatial'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buddy Welcome | Digital Senior</title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/themes/themes.css">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Buddy 3D Particle Script -->
    <script src="../assets/js/particles.js" defer></script>
    <style>
        body {
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }

        .welcome-card {
            width: 100%;
            max-width: 550px;
            padding: 40px;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Breathing Buddy Sphere */
        .buddy-bubble-container {
            position: relative;
            width: 140px;
            height: 140px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .buddy-bubble {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, rgba(255,255,255,0.25), rgba(0, 210, 255, 0.4) 40%, rgba(0, 114, 255, 0.7));
            box-shadow: 
                0 0 35px var(--glow-primary-alpha),
                inset 0 0 20px rgba(255,255,255,0.4);
            animation: pulse-bubble 3s infinite ease-in-out;
            position: relative;
            cursor: pointer;
        }
        .buddy-pulse-ring {
            position: absolute;
            width: 130px;
            height: 130px;
            border: 1px solid var(--glow-primary);
            border-radius: 50%;
            opacity: 0;
            animation: expand-ring 3s infinite ease-out;
        }

        .welcome-greeting {
            font-size: 1.8rem;
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 12px;
        }
        .welcome-message-text {
            font-size: 1rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 36px;
            max-width: 450px;
            min-height: 80px;
        }
        .btn-begin {
            padding: 14px 36px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        @keyframes pulse-bubble {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px var(--glow-primary-alpha); }
            50% { transform: scale(1.08); box-shadow: 0 0 50px rgba(0, 114, 255, 0.6); }
        }
        @keyframes expand-ring {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.3); opacity: 0; }
        }
    </style>
</head>
<body>

    <!-- Moving Aurora Backgrounds -->
    <div class="aurora-bg-container">
        <div class="aurora-blob aurora-blob-1"></div>
        <div class="aurora-blob aurora-blob-2"></div>
    </div>

    <!-- Onboarding Panel -->
    <div class="glass-panel welcome-card">
        <div class="buddy-bubble-container" onclick="speakWelcome()">
            <div class="buddy-pulse-ring"></div>
            <canvas id="buddy-canvas" class="buddy-bubble" style="width: 100px; height: 100px; background: transparent;"></canvas>
        </div>
        
        <h2 class="welcome-greeting">👋 Vanakkam, <?php echo $student_name; ?>!</h2>
        <p style="font-size: 0.85rem; color: var(--glow-primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 20px;">
            MEET <?php echo strtoupper($buddy_name); ?> — YOUR DIGITAL SENIOR
        </p>
        
        <div class="welcome-message-text" id="typewriter-text">
            <!-- Renders typed content via typewriter script -->
        </div>

        <form method="POST" action="welcome.php">
            <button type="submit" name="start_journey" class="btn-glass btn-primary btn-begin">
                BEGIN JOURNEY <i class="fa-solid fa-circle-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>
    </div>

    <script>
        const messageText = <?php echo json_encode($welcome_message); ?>;
        const enableVoice = <?php echo $enable_voice; ?>;
        let index = 0;
        const speed = 40; // typing speed in milliseconds

        function typeWriter() {
            if (index < messageText.length) {
                document.getElementById("typewriter-text").innerHTML += messageText.charAt(index);
                index++;
                setTimeout(typeWriter, speed);
            } else {
                // Speak once typing finishes
                if (enableVoice) {
                    speakWelcome();
                }
            }
        }

        function speakWelcome() {
            if ('speechSynthesis' in window) {
                // Cancel active speeches
                window.speechSynthesis.cancel();
                
                const utterance = new SpeechSynthesisUtterance(messageText);
                utterance.pitch = 1.1;
                utterance.rate = 1.0;
                
                // Fetch English voices or standard Indian accent if available
                const voices = window.speechSynthesis.getVoices();
                const engVoice = voices.find(v => v.lang.includes('en-IN') || v.lang.includes('en-GB') || v.lang.includes('en-US'));
                if (engVoice) {
                    utterance.voice = engVoice;
                }
                
                window.speechSynthesis.speak(utterance);
            }
        }

        window.addEventListener('load', function() {
            setTimeout(typeWriter, 500);
            
            // Initialize Buddy 3D Particle Sphere on welcome load
            new Buddy3DParticles('buddy-canvas');
        });
    </script>
</body>
</html>
