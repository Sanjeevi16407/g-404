/**
 * Buddy Unified Theme Switcher
 * Updates data-theme attributes in real-time and persists preferences in the DB.
 */
document.addEventListener("DOMContentLoaded", function() {
    // 1. Identify theme selectors on settings panels (both student & admin)
    const themeSelect = document.querySelector('select[name="theme"], select[name="default_theme"]');
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            const selectedTheme = this.value;
            applyThemeInstantly(selectedTheme);
        });
    }
});

/**
 * Updates root HTML theme token and triggers background database save
 */
function applyThemeInstantly(themeName) {
    // 1. Update data-theme immediately for real-time visual shift
    document.documentElement.setAttribute('data-theme', themeName);

    // 2. Persist in database using background AJAX cURL call
    // Check if we are inside student/frontend or admin folder to map API path
    const isStudent = window.location.pathname.includes('/frontend/');
    const apiPath = isStudent ? '../api/save_theme.php' : '../../api/save_theme.php'; // wait, admin doesn't update student settings table directly via save_theme

    if (isStudent) {
        fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `theme=${encodeURIComponent(themeName)}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                console.log("Theme preference saved in database.");
            }
        })
        .catch(err => console.error("Theme background update failure:", err));
    }
}
