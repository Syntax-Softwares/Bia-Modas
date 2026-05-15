// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    initCarousel('novidades');
    initCarousel('recentes');
    initCarousel('promocoes');
});

// Update on resize
let resizeTimer;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(updateAllCarousels, 250);
});
