    </main>
    <?php if ($active_page !== 'login.php' && $active_page !== 'welcome.php' && $active_page !== 'buddy.php'): ?>
    <!-- ==========================================
         BUDDY AMBIENT PRESENCE (GLOBAL FLOATING ORB)
         ========================================== -->
    <style>
        .buddy-ambient-container {
            position: fixed !important;
            bottom: 30px !important;
            right: 30px !important;
            left: auto !important;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        /* Glassmorphic speech bubble */
        .buddy-ambient-bubble {
            position: relative;
            background: rgba(10, 15, 30, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-secondary);
            padding: 10px 16px;
            border-radius: 16px;
            font-size: 0.82rem;
            max-width: 240px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            opacity: 0;
            transform: translateX(15px) scale(0.9);
            pointer-events: none;
            transition: all var(--transition-normal);
        }
        .buddy-ambient-bubble.active {
            opacity: 1;
            transform: translateX(0) scale(1);
            pointer-events: auto;
        }
        /* Triangle indicator for bubble */
        .buddy-ambient-bubble::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px 0 6px 6px;
            border-style: solid;
            border-color: transparent transparent transparent rgba(10, 15, 30, 0.85);
        }
        
        /* Floating mini particle core wrapper with glowing fallback background */
        .buddy-ambient-orb-wrapper {
            position: relative;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform var(--transition-fast);
            animation: buddy-ambient-float 4s infinite ease-in-out;
            background: radial-gradient(circle at center, rgba(0, 242, 254, 0.28) 0%, rgba(10, 15, 30, 0.8) 70%);
            border: 1.5px solid rgba(0, 242, 254, 0.45);
            box-shadow: 0 0 25px rgba(0, 242, 254, 0.35), inset 0 0 12px rgba(0, 242, 254, 0.2);
        }
        .buddy-ambient-orb-wrapper:hover {
            transform: scale(1.08);
            box-shadow: 0 0 35px rgba(0, 242, 254, 0.5);
            border-color: rgba(0, 242, 254, 0.65);
        }
        .buddy-ambient-canvas {
            width: 72px;
            height: 72px;
            background: transparent;
            pointer-events: none;
        }
        
        /* Breathing float keyframes */
        @keyframes buddy-ambient-float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-6px) scale(1.02); }
        }
        
        /* Light Theme compatibility overrides */
        html[data-theme="Light"] .buddy-ambient-bubble {
            background: rgba(255, 255, 255, 0.95);
            color: #1c202e;
            border-color: rgba(0, 0, 0, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        html[data-theme="Light"] .buddy-ambient-bubble::after {
            border-color: transparent transparent transparent rgba(255, 255, 255, 0.95);
        }
        html[data-theme="Light"] .buddy-ambient-orb-wrapper {
            background: radial-gradient(circle at center, rgba(0, 114, 255, 0.22) 0%, rgba(255, 255, 255, 0.92) 70%) !important;
            border-color: rgba(0, 114, 255, 0.45) !important;
            box-shadow: 0 8px 20px rgba(0, 114, 255, 0.25), inset 0 0 10px rgba(0, 114, 255, 0.1) !important;
        }
        
        /* Mobile view structural optimization overrides */
        @media (max-width: 768px) {
            .buddy-ambient-container {
                bottom: 20px !important;
                right: 20px !important;
                left: auto !important;
                gap: 0px !important;
            }
            .buddy-ambient-orb-wrapper {
                width: 56px !important;
                height: 56px !important;
                background: radial-gradient(circle at center, rgba(0, 242, 254, 0.32) 0%, rgba(10, 15, 30, 0.9) 70%) !important;
                border-color: rgba(0, 242, 254, 0.5) !important;
                box-shadow: 0 4px 18px rgba(0, 242, 254, 0.3) !important;
            }
            .buddy-ambient-canvas {
                width: 56px !important;
                height: 56px !important;
            }
            .buddy-ambient-bubble {
                display: none !important; /* Hide speech bubble on mobile */
            }
        }
    </style>

    <script>
        function openBuddyChatbot(e) {
            if (e) {
                if (e.cancelable) e.preventDefault();
                e.stopPropagation();
            }
            window.location.href = 'buddy.php';
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Ensure shortcut widget is top-level child of body
            const widget = document.getElementById('buddy-ambient-widget');
            if (widget && widget.parentElement !== document.body) {
                document.body.appendChild(widget);
            }

            // Initialize Lucide Icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // 1. Initialize the 3D particles inside the ambient canvas
            if (typeof Buddy3DParticles === 'function') {
                new Buddy3DParticles('buddy-ambient-canvas');
            }
            
            // 2. Setup Context-sensitive Tips Rotation
            const pageName = "<?php echo $active_page; ?>";
            let tips = [];
            
            if (pageName.includes('dashboard')) {
                tips = [
                    "Today's timetable is ready! 🗓️",
                    "A new announcement has been posted! 📢",
                    "Click on any metric card to explore SCE! 🚀",
                    "Your onboarding journey is in progress! 🎯"
                ];
            } else if (pageName.includes('campus')) {
                tips = [
                    "Need help finding your classroom? 📍",
                    "Check out library rules in the tabs! 📚",
                    "Bus routes are fully detailed here! 🚌"
                ];
            } else if (pageName.includes('faculty')) {
                tips = [
                    "Search faculty members by name or department! 🔍",
                    "Cabin numbers help you find them in person! 👨‍🏫"
                ];
            } else if (pageName.includes('timetable')) {
                tips = [
                    "This is your personalized weekly schedule! 🗓️",
                    "Double check periods for lab sessions! ⏰"
                ];
            } else if (pageName.includes('clubs')) {
                tips = [
                    "Click Join to register for college clubs! 🏆",
                    "Get active in tech and cultural communities! 💻"
                ];
            } else if (pageName.includes('events')) {
                tips = [
                    "Register for upcoming symposiums and codeathons! 🎉",
                    "Earn badges by checking events! 🏅"
                ];
            } else {
                tips = [
                    "Need anything? Click me to chat! 💬",
                    "I can help you navigate Saranathan College! 🏢",
                    "Ask me questions in English or Tamil! 🗣️"
                ];
            }
            
            const tooltip = document.getElementById('buddy-ambient-tooltip');
            
            function showTip() {
                if (tips.length === 0) return;
                const randomTip = tips[Math.floor(Math.random() * tips.length)];
                tooltip.innerHTML = randomTip;
                tooltip.classList.add('active');
                
                // Keep visible for 6 seconds, then fade out
                setTimeout(() => {
                    tooltip.classList.remove('active');
                }, 6000);
            }
            
            // Trigger first tip after 10 seconds
            setTimeout(showTip, 10000);
            
            // Repeat showing tips every 32 seconds
            setInterval(showTip, 32000);
        });
    </script>
    <?php endif; ?>

    <!-- Global Image Lightbox Modal for Events, Announcements & Posters -->
    <div id="global-image-lightbox" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); z-index: 99999; display: none; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box;" onclick="closeGlobalImageLightbox()">
        <div style="position: relative; max-width: 90vw; max-height: 90vh; display: flex; flex-direction: column; align-items: center;" onclick="event.stopPropagation()">
            <button type="button" onclick="closeGlobalImageLightbox()" style="position: absolute; top: -45px; right: -10px; background: rgba(255, 255, 255, 0.2); border: none; color: #fff; border-radius: 50%; width: 36px; height: 36px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <img id="lightbox-image-src" src="" alt="Full Preview" style="max-width: 100%; max-height: 80vh; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2); object-fit: contain; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
            <div id="lightbox-image-title" style="margin-top: 14px; font-size: 0.95rem; font-weight: 700; color: #ffffff; text-align: center; background: rgba(10, 15, 30, 0.8); padding: 8px 18px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);"></div>
        </div>
    </div>

    <script>
    function openGlobalImageLightbox(src, title) {
        if (!src) return;
        const lightbox = document.getElementById('global-image-lightbox');
        const img = document.getElementById('lightbox-image-src');
        const caption = document.getElementById('lightbox-image-title');
        img.src = src;
        caption.innerText = title || 'Image Preview';
        lightbox.style.display = 'flex';
    }

    function closeGlobalImageLightbox() {
        const lightbox = document.getElementById('global-image-lightbox');
        if (lightbox) lightbox.style.display = 'none';
    }
    </script>

    <?php if ($active_page !== 'login.php' && $active_page !== 'welcome.php'): ?>
    <!-- Mobile-only Script Loader -->
    <script src="../assets/js/mobile.js?v=<?php echo time(); ?>" defer></script>
    <?php endif; ?>
</body>
</html>
