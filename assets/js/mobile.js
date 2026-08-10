/**
 * Saranathan Digital Senior (Buddy)
 * Dedicated Mobile Javascript Handler
 */

window.toggleSidebar = function() {
    const sidebar = document.getElementById('student-sidebar') || document.querySelector('.sidebar');
    const backdrop = document.getElementById('student-sidebar-backdrop') || document.getElementById('admin-sidebar-backdrop');
    if (sidebar) sidebar.classList.toggle('active');
    if (backdrop) backdrop.classList.toggle('active');
};
