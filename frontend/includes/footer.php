</main>
    <?php if ($active_page !== 'login.php' && $active_page !== 'welcome.php' && $active_page !== 'buddy.php'): ?>
    <!-- ==========================================
         BUDDY AMBIENT PRESENCE (GLOBAL FLOATING ORB)
         ========================================== -->
    <style>
        /* Modern Floating Action Button (FAB) Widget for Buddy AI */
        .buddy-ambient-container,
        #buddy-ambient-widget {
            position: fixed !important;
            bottom: 30px !important;
            right: 30px !important;
            left: auto !important;
            top: auto !important;
            z-index: 999999999 !important;
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
            transform: translate3d(0, 0, 0) !important;
            -webkit-transform: translate3d(0, 0, 0) !important;
            transition: bottom 0.25s ease, right 0.25s ease !important;
        }
        
        /* Glassmorphic speech bubble tip */
        .buddy-ambient-bubble {
            position: relative;
            background: rgba(10, 15, 30, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(0, 242, 254, 0.25);
            color: var(--text-primary);
            padding: 10px 16px;
            border-radius: 16px;
            font-size: 0.82rem;
            font-weight: 600;
            max-width: 240px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 15px rgba(0, 242, 254, 0.15);
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
        .buddy-ambient-bubble::after {
            content: '';
            position: absolute;
            right: -6px;
            top: 50%;
            transform: translateY(-50%);
            border-width: 6px 0 6px 6px;
            border-style: solid;
            border-color: transparent transparent transparent rgba(10, 15, 30, 0.9);
        }
        
        /* FAB Button Orb Wrapper - Removed Continuous Bobbing Animation */
        .buddy-ambient-orb-wrapper {
            position: relative;
            width: 68px;
            height: 68px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at center, rgba(0, 242, 254, 0.35) 0%, rgba(10, 15, 30, 0.92) 75%);
            border: 2px solid rgba(0, 242, 254, 0.55);
            box-shadow: 0 8px 25px rgba(0, 242, 254, 0.35), 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, border-color 0.2s ease;
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
        }
        
        /* Desktop Hover State */
        .buddy-ambient-orb-wrapper:hover {
            transform: scale(1.08);
            box-shadow: 0 12px 35px rgba(0, 242, 254, 0.55), 0 6px 20px rgba(0, 0, 0, 0.6);
            border-color: rgba(0, 242, 254, 0.85);
        }

        /* Touch Active Tap State */
        .buddy-ambient-orb-wrapper:active {
            transform: scale(0.92);
            box-shadow: 0 4px 15px rgba(0, 242, 254, 0.4);
        }

        .buddy-ambient-canvas {
            width: 68px;
            height: 68px;
            background: transparent;
            pointer-events: none;
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
            background: radial-gradient(circle at center, rgba(0, 114, 255, 0.28) 0%, rgba(255, 255, 255, 0.95) 75%) !important;
            border-color: rgba(0, 114, 255, 0.55) !important;
            box-shadow: 0 8px 20px rgba(0, 114, 255, 0.3), inset 0 0 10px rgba(0, 114, 255, 0.15) !important;
        }
        
        /* Tablet view positioning (769px - 1024px) */
        @media (max-width: 1024px) and (min-width: 769px) {
            .buddy-ambient-container,
            #buddy-ambient-widget {
                bottom: 24px !important;
                right: 24px !important;
            }
            .buddy-ambient-orb-wrapper {
                width: 62px !important;
                height: 62px !important;
            }
            .buddy-ambient-canvas {
                width: 62px !important;
                height: 62px !important;
            }
        }

        /* Mobile view positioning (<= 768px) */
        @media (max-width: 768px) {
            .buddy-ambient-container,
            #buddy-ambient-widget {
                bottom: 20px !important;
                right: 20px !important;
                gap: 0px !important;
            }
            .buddy-ambient-orb-wrapper {
                width: 58px !important;
                height: 58px !important;
                background: radial-gradient(circle at center, rgba(0, 242, 254, 0.4) 0%, rgba(10, 15, 30, 0.95) 75%) !important;
                border-color: rgba(0, 242, 254, 0.65) !important;
                box-shadow: 0 6px 22px rgba(0, 242, 254, 0.4), 0 4px 12px rgba(0, 0, 0, 0.6) !important;
            }
            .buddy-ambient-canvas {
                width: 58px !important;
                height: 58px !important;
            }
            .buddy-ambient-bubble {
                display: none !important; /* Hide speech bubble on mobile to keep FAB clean */
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
