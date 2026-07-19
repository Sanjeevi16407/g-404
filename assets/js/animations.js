/**
 * Buddy Animations Controller Page
 * Manages card entrance staggers, text fades, and metrics counters using GSAP.
 */
document.addEventListener("DOMContentLoaded", function() {
    // 1. Check user preferences for animations speed (low, medium, high)
    const animPreference = document.documentElement.getAttribute('data-animations') || 'high';
    if (animPreference === 'low') {
        // Skip entry animation delays, render items immediately
        document.querySelectorAll('.glass-panel, .glass-card').forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
        triggerCountersNoAnim();
        return;
    }

    const duration = animPreference === 'medium' ? 0.4 : 0.8;
    const stagger = animPreference === 'medium' ? 0.05 : 0.12;

    // 2. Card entrance staggers
    if (typeof gsap !== 'undefined') {
        gsap.fromTo(".glass-panel", 
            { opacity: 0, y: 30, scale: 0.98 },
            { opacity: 1, y: 0, scale: 1, duration: duration, stagger: stagger, ease: "power2.out" }
        );

        gsap.fromTo(".glass-card", 
            { opacity: 0, y: 25, scale: 0.95 },
            { opacity: 1, y: 0, scale: 1, duration: duration, stagger: stagger, ease: "back.out(1.5)" }
        );

        // 3. Hero text fades
        gsap.fromTo(".hero-title, .welcome-greeting", 
            { opacity: 0, y: -20 },
            { opacity: 1, y: 0, duration: 1, ease: "power3.out", delay: 0.2 }
        );
        
        // 4. Trigger metrics card count up animations
        triggerCountersGSAP();
    } else {
        triggerCountersNoAnim();
    }
});

/**
 * Animates metric numbers counting up using GSAP
 */
function triggerCountersGSAP() {
    document.querySelectorAll('[data-target-count]').forEach(el => {
        const targetValue = parseInt(el.getAttribute('data-target-count') || 0);
        const obj = { count: 0 };
        
        gsap.to(obj, {
            count: targetValue,
            duration: 1.5,
            ease: "power2.out",
            onUpdate: function() {
                el.innerText = Math.floor(obj.count).toLocaleString();
            }
        });
    });
}

/**
 * Fallback to print actual count values immediately if GSAP missing or low-spec mode active
 */
function triggerCountersNoAnim() {
    document.querySelectorAll('[data-target-count]').forEach(el => {
        const targetValue = el.getAttribute('data-target-count') || 0;
        el.innerText = parseInt(targetValue).toLocaleString();
    });
}
