/**
 * Buddy Radial Command Wheel Animations Controller
 * Staggers radial item expansions using trigonometric coordinates and GSAP.
 */
class BuddyCommandWheel {
    constructor(triggerId, wheelId) {
        this.trigger = document.getElementById(triggerId);
        this.wheel = document.getElementById(wheelId);
        if (!this.trigger || !this.wheel) return;

        this.isOpen = false;
        // Grab child items
        this.items = this.wheel.querySelectorAll('.wheel-item');
        this.radius = 95; // Radius of item distribution in pixels
        
        this.init();
    }

    init() {
        // Position items in circle programmatically for flexibility
        this.positionItems();

        // Trigger action clicks
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Close on clicking outside or pressing ESC key
        document.addEventListener('click', () => this.close());
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });

        // Close wheel automatically on clicking item links (after slight transition delay)
        this.items.forEach(item => {
            item.addEventListener('click', () => {
                setTimeout(() => this.close(), 200);
            });
        });
    }

    positionItems() {
        const numItems = this.items.length;
        this.items.forEach((item, idx) => {
            // Distribute items in circle based on index angle
            // Center is (0, 0), translate elements relative to middle coordinates
            // Subtract PI/2 to start from top center index
            const angle = (idx * (2 * Math.PI / numItems)) - (Math.PI / 2);
            const x = Math.round(this.radius * Math.cos(angle));
            const y = Math.round(this.radius * Math.sin(angle));

            item.setAttribute('data-target-x', x);
            item.setAttribute('data-target-y', y);
            
            // Set initial collapse position at center
            if (typeof gsap !== 'undefined') {
                gsap.set(item, { x: 0, y: 0, scale: 0, opacity: 0 });
            } else {
                item.style.transform = 'translate(0px, 0px) scale(0)';
                item.style.opacity = '0';
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.wheel.classList.add('active');
        this.trigger.classList.add('active');

        // Close chatbot window if open to avoid conflicts
        if (typeof closeBuddyChat === 'function') {
            // Close without calling window.buddyWheel.close to prevent infinite recursion
            document.getElementById('buddy-chat-box').classList.remove('active');
        }

        if (typeof gsap !== 'undefined') {
            // Stagger radial items outward expansion
            gsap.killTweensOf(this.items);
            this.items.forEach((item) => {
                const targetX = item.getAttribute('data-target-x');
                const targetY = item.getAttribute('data-target-y');

                gsap.to(item, {
                    x: targetX,
                    y: targetY,
                    scale: 1,
                    opacity: 1,
                    duration: 0.5,
                    ease: "back.out(1.7)",
                    overwrite: "auto"
                });
            });
            
            // Animate center background scale
            gsap.fromTo(this.wheel.querySelector('.wheel-center-overlay'),
                { scale: 0.4, opacity: 0 },
                { scale: 1, opacity: 1, duration: 0.4, ease: "power2.out" }
            );
        } else {
            // CSS fallback
            this.items.forEach((item) => {
                const targetX = item.getAttribute('data-target-x');
                const targetY = item.getAttribute('data-target-y');
                item.style.transform = `translate(${targetX}px, ${targetY}px) scale(1)`;
                item.style.opacity = '1';
            });
        }
    }

    close() {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.wheel.classList.remove('active');
        this.trigger.classList.remove('active');

        if (typeof gsap !== 'undefined') {
            gsap.killTweensOf(this.items);
            gsap.to(this.items, {
                x: 0,
                y: 0,
                scale: 0,
                opacity: 0,
                duration: 0.4,
                ease: "power2.in",
                stagger: 0.03
            });
            
            gsap.to(this.wheel.querySelector('.wheel-center-overlay'),
                { scale: 0.5, opacity: 0, duration: 0.3, ease: "power2.in" }
            );
        } else {
            this.items.forEach((item) => {
                item.style.transform = 'translate(0px, 0px) scale(0)';
                item.style.opacity = '0';
            });
        }
    }
}

// Instantiate command wheel on DOM load
document.addEventListener("DOMContentLoaded", function() {
    window.buddyWheel = new BuddyCommandWheel('buddy-trigger-btn', 'buddy-wheel');
});
