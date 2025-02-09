const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

function updateDarkMode() {
    if (darkModeMediaQuery.matches) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

updateDarkMode();
darkModeMediaQuery.addEventListener('change', updateDarkMode);
