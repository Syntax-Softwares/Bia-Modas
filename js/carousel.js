// Carousel System
const carousels = {};

function initCarousel(id) {
    const track = document.getElementById(`carousel-${id}`);
    const dotsContainer = document.getElementById(`dots-${id}`);
    if (!track) return;
    
    const items = track.querySelectorAll('.carousel-slide');
    const itemsPerView = getItemsPerView();
    const totalPages = Math.ceil(items.length / itemsPerView);
    
    carousels[id] = {
        currentPage: 0,
        totalPages: totalPages,
        itemsPerView: itemsPerView
    };
    
    // Create dots
    if (dotsContainer) {
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalPages; i++) {
            const dot = document.createElement('button');
            dot.className = `carousel-dot ${i === 0 ? 'active' : ''}`;
            dot.onclick = () => goToPage(id, i);
            dot.ariaLabel = `Página ${i + 1}`;
            dotsContainer.appendChild(dot);
        }
    }
    
    updateCarouselPosition(id);
}

function getItemsPerView() {
    if (window.innerWidth >= 992) return 4;
    if (window.innerWidth >= 768) return 3;
    if (window.innerWidth >= 576) return 2;
    return 1;
}

function moveCarousel(id, direction) {
    const carousel = carousels[id];
    if (!carousel) return;
    
    const newPage = carousel.currentPage + direction;
    if (newPage >= 0 && newPage < carousel.totalPages) {
        goToPage(id, newPage);
    }
}

function goToPage(id, page) {
    const carousel = carousels[id];
    if (!carousel) return;
    
    carousel.currentPage = page;
    updateCarouselPosition(id);
    
    // Update dots
    const dotsContainer = document.getElementById(`dots-${id}`);
    if (dotsContainer) {
        const dots = dotsContainer.querySelectorAll('.carousel-dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === page);
        });
    }
    
    // Update buttons
    const container = document.getElementById(`carousel-${id}`).closest('.carousel-container');
    const prevBtn = container.querySelector('.carousel-btn.prev');
    const nextBtn = container.querySelector('.carousel-btn.next');
    if (prevBtn) prevBtn.disabled = page === 0;
    if (nextBtn) nextBtn.disabled = page === carousel.totalPages - 1;
}

function updateCarouselPosition(id) {
    const track = document.getElementById(`carousel-${id}`);
    const carousel = carousels[id];
    if (!track || !carousel) return;
    
    // Move 100% per page to show the next set of items
    const translateX = -(carousel.currentPage * 100);
    track.style.transform = `translateX(${translateX}%)`;
}

function updateAllCarousels() {
    const newItemsPerView = getItemsPerView();
    Object.keys(carousels).forEach(id => {
        const track = document.getElementById(`carousel-${id}`);
        if (!track) return;
        
        const items = track.querySelectorAll('.carousel-slide');
        carousels[id].itemsPerView = newItemsPerView;
        carousels[id].totalPages = Math.ceil(items.length / newItemsPerView);
        
        // Reset to first page if current page is out of bounds
        if (carousels[id].currentPage >= carousels[id].totalPages) {
            carousels[id].currentPage = 0;
        }
        
        // Recreate dots
        const dotsContainer = document.getElementById(`dots-${id}`);
        if (dotsContainer) {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < carousels[id].totalPages; i++) {
                const dot = document.createElement('button');
                dot.className = `carousel-dot ${i === carousels[id].currentPage ? 'active' : ''}`;
                dot.onclick = () => goToPage(id, i);
                dot.ariaLabel = `Página ${i + 1}`;
                dotsContainer.appendChild(dot);
            }
        }
        
        updateCarouselPosition(id);
        
        // Update buttons
        const container = track.closest('.carousel-container');
        const prevBtn = container.querySelector('.carousel-btn.prev');
        const nextBtn = container.querySelector('.carousel-btn.next');
        if (prevBtn) prevBtn.disabled = carousels[id].currentPage === 0;
        if (nextBtn) nextBtn.disabled = carousels[id].currentPage === carousels[id].totalPages - 1;
    });
}
