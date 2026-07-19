/**
 * Buddy greetings.js Controller
 * Adds typewriter animations, fade-ins, and Buddy sphere bounce feedback animations.
 */
document.addEventListener("DOMContentLoaded", function() {
    // 1. Identify greeting tags on Dashboard
    const greetingHeader = document.querySelector('.welcome-greeting');
    if (greetingHeader) {
        // Stagger fade-in sequence of cards
        if (typeof gsap !== 'undefined') {
            gsap.fromTo(greetingHeader,
                { opacity: 0, scale: 0.95 },
                { opacity: 1, scale: 1, duration: 0.8, ease: "back.out(1.5)" }
            );
        }
    }

    // 2. Wave trigger: Bounces Buddy sphere to welcome the user
    triggerBuddyWave();
});

/**
 * Simulates a friendly greeting wave by bouncing/pulsing the Buddy sphere elements
 */
function triggerBuddyWave() {
    const bubble = document.querySelector('.buddy-bubble') || document.querySelector('.buddy-sphere');
    if (!bubble) return;

    if (typeof gsap !== 'undefined') {
        // Staggered bounce wave animation sequence
        const tl = gsap.timeline();
        tl.to(bubble, { y: -18, scale: 1.05, duration: 0.25, ease: "power2.out" })
          .to(bubble, { y: 5, scale: 0.98, duration: 0.2, ease: "power2.inOut" })
          .to(bubble, { y: -10, scale: 1.03, duration: 0.18, ease: "power2.out" })
          .to(bubble, { y: 0, scale: 1, duration: 0.25, ease: "back.out(1.5)" });
    } else {
        bubble.classList.add('wave-pulse-active');
        setTimeout(() => bubble.classList.remove('wave-pulse-active'), 1000);
    }
}
