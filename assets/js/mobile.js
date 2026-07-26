/**
 * Saranathan Digital Senior (Buddy)
 * Dedicated Mobile Javascript Handler
 */

function toggleMobileDrawer() {
    if (window.innerWidth > 768) return;
    const drawer = document.getElementById('mobile-sidebar-drawer');
    const backdrop = document.getElementById('mobile-drawer-backdrop');
    if (drawer && backdrop) {
        drawer.classList.toggle('active');
        backdrop.classList.toggle('active');
    }
}

function openCampusSelectorMobile() {
    if (window.innerWidth > 768) return;
    const sheet = document.getElementById('campus-bottom-sheet');
    const backdrop = document.getElementById('campus-sheet-backdrop');
    if (sheet && backdrop) {
        sheet.classList.add('active');
        backdrop.classList.add('active');
    }
}

function closeCampusSelectorMobile() {
    if (window.innerWidth > 768) return;
    const sheet = document.getElementById('campus-bottom-sheet');
    const backdrop = document.getElementById('campus-sheet-backdrop');
    if (sheet && backdrop) {
        sheet.classList.remove('active');
        backdrop.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth > 768) {
        return;
    }

    // 1. Close mobile drawer on backdrop click
    const drawerBackdrop = document.getElementById('mobile-drawer-backdrop');
    if (drawerBackdrop) {
        drawerBackdrop.addEventListener('click', toggleMobileDrawer);
    }

    // 2. Close campus selector sheet on backdrop click
    const campusBackdrop = document.getElementById('campus-sheet-backdrop');
    if (campusBackdrop) {
        campusBackdrop.addEventListener('click', closeCampusSelectorMobile);
    }

    // 3. Close drawer on links click
    const drawerLinks = document.querySelectorAll('.mobile-drawer-link');
    drawerLinks.forEach(link => {
        link.addEventListener('click', function() {
            const drawer = document.getElementById('mobile-sidebar-drawer');
            if (drawer && drawer.classList.contains('active')) {
                toggleMobileDrawer();
            }
        });
    });
});
