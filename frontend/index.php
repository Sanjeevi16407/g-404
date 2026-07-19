<?php
/**
 * Student Portal - Landing & Splash Screen Page (Cinematic Visual Overhaul)
 */
require_once __DIR__ . '/../backend/db.php';

// Fetch college settings
$college = $db->query("SELECT * FROM college_settings WHERE id = 1 LIMIT 1")->fetch();
$college_name = $college['college_name'] ?? 'Saranathan College of Engineering';
$college_logo = $college['college_logo'] ?? 'assets/images/logo.png';
$default_theme = $college['default_theme'] ?? 'Spatial';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $default_theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buddy - Your Digital Senior | <?php echo sanitize_input($college_name); ?></title>
    <!-- Core styles -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/themes/themes.css">
    <!-- Tailwind CSS Engine -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Outlined Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Three.js 3D WebGL Core -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
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

        /* 1. Splash Screen Styling */
        .splash-screen {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: var(--bg-primary);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.8s ease-in-out, visibility 0.8s;
        }
        .splash-logo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            animation: pulse-glow 2s infinite ease-in-out;
            margin-bottom: 24px;
        }
        .splash-text {
            font-family: var(--font-heading);
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: 0.05em;
        }
        .splash-loader {
            width: 150px;
            height: 3px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
            margin-top: 24px;
            position: relative;
            overflow: hidden;
        }
        .splash-loader-bar {
            position: absolute;
            left: 0; top: 0; height: 100%; width: 0;
            background: linear-gradient(90deg, var(--glow-primary), var(--glow-secondary));
            border-radius: 3px;
            animation: load-bar 2.2s forwards cubic-bezier(0.1, 0.8, 0.25, 1);
        }

        /* 2. Hero and Buddy Sphere Layout */
        .landing-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            max-width: 620px;
            opacity: 0;
            transform: translateY(25px);
            animation: fade-up 1.2s 2.4s forwards cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Animated 3D Buddy Sphere Representation */
        .buddy-sphere-wrapper {
            position: relative;
            width: 300px;
            height: 300px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float-sphere 6s infinite ease-in-out;
        }
        .buddy-sphere-canvas {
            width: 240px;
            height: 240px;
            background: transparent;
            z-index: 5;
            filter: drop-shadow(0 0 40px rgba(0, 242, 254, 0.45));
        }
        
        /* Outer particle ring representing command field */
        .buddy-ring {
            position: absolute;
            width: 290px;
            height: 290px;
            border: 1px dashed rgba(0, 242, 254, 0.3);
            border-radius: 50%;
            animation: spin-ring 15s infinite linear;
        }
        .buddy-ring::after {
            content: '';
            position: absolute;
            top: -6px; left: 50%;
            width: 12px; height: 12px;
            background: var(--glow-primary);
            border-radius: 50%;
            box-shadow: 0 0 15px var(--glow-primary);
        }

        /* 3. Text and CTAs */
        .hero-title {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--text-primary), var(--glow-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }
        .hero-subtitle {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 36px;
            line-height: 1.6;
        }
        .btn-start {
            padding: 16px 40px;
            font-size: 1.05rem;
            font-weight: 600;
            border-radius: 50px;
            letter-spacing: 0.05em;
        }

        /* Animations */
        @keyframes pulse-glow {
            0%, 100% { transform: scale(1); box-shadow: 0 0 20px rgba(0, 242, 254, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(0, 242, 254, 0.5); }
        }
        @keyframes load-bar {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        @keyframes float-sphere {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-12px) rotate(1deg); }
        }
        @keyframes spin-ring {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(25px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Twinkling Starfield Ambient Canvas -->
    <canvas id="starfield-canvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1; pointer-events: none;"></canvas>

    <!-- Moving Aurora Blobs -->
    <div class="aurora-bg-container" style="z-index: 2;">
        <div class="aurora-blob aurora-blob-1" style="width: 550px; height: 550px; left: -100px; top: -100px; background: rgba(0, 242, 254, 0.22);"></div>
        <div class="aurora-blob aurora-blob-2" style="width: 550px; height: 550px; right: -100px; bottom: -100px; background: rgba(127, 0, 255, 0.22);"></div>
    </div>

    <!-- 1. Splash Screen Loader -->
    <div id="splash" class="splash-screen">
        <img src="../<?php echo sanitize_input($college_logo); ?>" alt="College Logo" class="splash-logo">
        <div class="splash-text">SARANATHAN</div>
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 4px; letter-spacing: 0.1em;">DIGITAL SENIOR PROJECT</p>
        <div class="splash-loader">
            <div class="splash-loader-bar"></div>
        </div>
    </div>

    <!-- 2. Main Landing Page Hero -->
    <div class="glass-panel landing-container" style="padding: 48px; border-radius: 28px; z-index: 10; border: 1px solid rgba(255, 255, 255, 0.12); background: rgba(13, 18, 35, 0.55); box-shadow: 0 40px 100px rgba(0, 0, 0, 0.65);">
        <div class="buddy-sphere-wrapper">
            <div class="buddy-ring"></div>
            <div class="buddy-ring" style="width: 350px; height: 350px; border-color: rgba(127, 0, 255, 0.15); animation-duration: 20s; animation-direction: reverse;"></div>
            <canvas id="buddy-canvas" class="buddy-sphere-canvas"></canvas>
        </div>
        
        <h1 class="hero-title">Buddy</h1>
        <p style="font-size: 1.1rem; color: var(--glow-primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 16px;">Your Digital Senior</p>
        
        <p class="hero-subtitle">
            Navigate your first year at Saranathan College. Plan your timetables, locate classrooms, register for clubs and campus events, and talk with our AI Senior in Tanglish!
        </p>
        
        <a href="login.php" class="btn-glass btn-primary btn-start">
            START JOURNEY <i class="fa-solid fa-chevron-right" style="margin-left: 8px;"></i>
        </a>
    </div>

    <script>
        // Starfield Canvas Background Renderer
        const starCanvas = document.getElementById('starfield-canvas');
        const starCtx = starCanvas.getContext('2d');
        let stars = [];
        const numStars = 120;

        function resizeStars() {
            starCanvas.width = window.innerWidth;
            starCanvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resizeStars);
        resizeStars();

        // Initialize stars
        for (let i = 0; i < numStars; i++) {
            stars.push({
                x: Math.random() * starCanvas.width,
                y: Math.random() * starCanvas.height,
                radius: Math.random() * 1.5,
                alpha: Math.random(),
                speed: Math.random() * 0.05 + 0.01
            });
        }

        function animateStars() {
            requestAnimationFrame(animateStars);
            starCtx.clearRect(0, 0, starCanvas.width, starCanvas.height);
            
            stars.forEach(star => {
                star.alpha += star.speed;
                if (star.alpha > 1 || star.alpha < 0) {
                    star.speed = -star.speed;
                }
                starCtx.fillStyle = `rgba(255, 255, 255, ${Math.max(0.1, star.alpha)})`;
                starCtx.beginPath();
                starCtx.arc(star.x, star.y, star.radius, 0, Math.PI * 2);
                starCtx.fill();
            });
        }
        animateStars();

        // Timeout to fade out splash screen and reveal landing content
        window.addEventListener('load', function() {
            setTimeout(function() {
                const splash = document.getElementById('splash');
                splash.style.opacity = '0';
                splash.style.visibility = 'hidden';
                
                // Initialize Buddy 3D Particle Sphere on splash screen load complete
                if (typeof Buddy3DParticles === 'function') {
                    new Buddy3DParticles('buddy-canvas');
                }
            }, 2500); // 2.5 seconds matching loading progress
        });
    </script>
</body>
</html>
