// ────────────────────────────────────────────────
// Thème clair / sombre + persistance
// ────────────────────────────────────────────────
const themeToggle = document.getElementById('theme-toggle');
const themeIcon   = document.getElementById('theme-icon');
const htmlElement = document.documentElement;

function setTheme(theme) {
    htmlElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);

    if (themeIcon) {
        themeIcon.innerHTML = theme === 'dark'
            ? '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>'
            : '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
    }
}

if (themeToggle) {
    // Chargement initial
    const savedTheme = localStorage.getItem('theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const current = htmlElement.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
    });
}

// --------------------------------------------------
// Sidebar toggle (bouton = ouvrir/fermer)
// --------------------------------------------------
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');

if (sidebar && sidebarToggle) {
    const savedState = localStorage.getItem('sidebar');
    if (savedState === 'expanded') {
        sidebar.classList.add('expanded');
    }

    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('expanded');
        localStorage.setItem('sidebar', sidebar.classList.contains('expanded') ? 'expanded' : 'collapsed');
    });
}

// ────────────────────────────────────────────────
// Animation curseur sidebar (hover = expand)
// ────────────────────────────────────────────────
