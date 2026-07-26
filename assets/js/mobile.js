/**
 * Saranathan Digital Senior (Buddy)
 * Dedicated Mobile Javascript Handler
 */

if (window.innerWidth > 768) {
    // Exit immediately on desktop
} else {
    let hasCollapsedOnce = false;

    window.addEventListener('scroll', () => {
        const grid = document.getElementById('quick-access-grid-container');
        const chevron = document.getElementById('quick-access-chevron');
        if (!grid) return;

        const currentScroll = window.scrollY;

        // Auto collapse on first scroll down only once
        if (currentScroll > 40 && !grid.classList.contains('collapsed') && !hasCollapsedOnce) {
            grid.classList.add('collapsed');
            hasCollapsedOnce = true; // prevent further auto-collapses or auto-expands
            if (chevron) {
                chevron.className = 'fa-solid fa-chevron-down';
            }
        }
    });

    window.toggleQuickAccessGrid = function() {
        const grid = document.getElementById('quick-access-grid-container');
        const chevron = document.getElementById('quick-access-chevron');
        if (!grid) return;

        // User took manual action, stop any future auto scroll behaviors
        hasCollapsedOnce = true;
        
        const isCollapsed = grid.classList.toggle('collapsed');
        if (chevron) {
            chevron.className = isCollapsed ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-up';
        }
    };
}
