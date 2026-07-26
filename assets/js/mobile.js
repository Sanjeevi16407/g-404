/**
 * Saranathan Digital Senior (Buddy)
 * Dedicated Mobile Javascript Handler
 */

if (window.innerWidth > 768) {
    // Exit immediately on desktop
} else {
    let userToggled = false;

    window.addEventListener('scroll', () => {
        const grid = document.getElementById('quick-access-grid-container');
        const chevron = document.getElementById('quick-access-chevron');
        if (!grid) return;

        const currentScroll = window.scrollY;

        // Auto collapse on scroll down
        if (currentScroll > 40 && !grid.classList.contains('collapsed') && !userToggled) {
            grid.classList.add('collapsed');
            if (chevron) {
                chevron.className = 'fa-solid fa-chevron-down';
            }
        } 
        // Auto expand when scrolling back to the very top
        else if (currentScroll <= 10 && grid.classList.contains('collapsed') && !userToggled) {
            grid.classList.remove('collapsed');
            if (chevron) {
                chevron.className = 'fa-solid fa-chevron-up';
            }
        }
    });

    window.toggleQuickAccessGrid = function() {
        const grid = document.getElementById('quick-access-grid-container');
        const chevron = document.getElementById('quick-access-chevron');
        if (!grid) return;

        userToggled = true;
        const isCollapsed = grid.classList.toggle('collapsed');
        if (chevron) {
            chevron.className = isCollapsed ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
        }

        // Reset toggle flag after transition finishes
        setTimeout(() => {
            userToggled = false;
        }, 500);
    };
}
