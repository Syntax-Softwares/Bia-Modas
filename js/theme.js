// Dark Mode Toggle
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    const icon = toggle.querySelector('i');
    const html = document.documentElement;

    function updateIcon() {
        if (html.getAttribute('data-theme') === 'dark') {
            icon.className = 'bi bi-sun';
            toggle.setAttribute('aria-label', 'Mudar para modo claro');
        } else {
            icon.className = 'bi bi-moon';
            toggle.setAttribute('aria-label', 'Mudar para modo escuro');
        }
    }

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        const isDark = html.getAttribute('data-theme') === 'dark';
        if (isDark) {
            html.removeAttribute('data-theme');
            localStorage.setItem('bia_modas_theme', 'light');
        } else {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('bia_modas_theme', 'dark');
        }
        updateIcon();
    });

    updateIcon();
});
