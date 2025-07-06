// Get user preference from localStorage or use system preference
function getUserThemePreference() {
    return localStorage.getItem('theme') || 'system';
}

const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

function updateDarkMode() {
    const userPreference = getUserThemePreference();

    if (userPreference === 'dark') {
        document.documentElement.classList.add('dark');
    } else if (userPreference === 'light') {
        document.documentElement.classList.remove('dark');
    } else {
        // System preference
        if (darkModeMediaQuery.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
}

// Update theme when localStorage changes (for cross-tab consistency)
window.addEventListener('storage', function(e) {
    if (e.key === 'theme') {
        updateDarkMode();
    }
});

// Listen for theme changes from Vue components
window.addEventListener('themeChanged', function(e) {
    updateDarkMode();
});

updateDarkMode();
darkModeMediaQuery.addEventListener('change', function() {
    // Only update based on system preference if user has selected 'system'
    if (getUserThemePreference() === 'system') {
        updateDarkMode();
    }
});
