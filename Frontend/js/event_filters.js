
function filterEventsByCategory(containerId, category) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const eventCards = container.querySelectorAll('.event-card');
    if (eventCards.length === 0) return;
    
    if (category === 'all') {
        eventCards.forEach(card => {
            card.style.display = '';
        });
        return;
    }
    
    eventCards.forEach(card => {
        const cardCategory = card.querySelector('.event-category')?.textContent.trim().toLowerCase();
        if (cardCategory === category.toLowerCase()) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    
    const publicEventFilter = document.getElementById('public-event-filter');
    if (publicEventFilter) {
        publicEventFilter.addEventListener('change', function() {
            filterEventsByCategory('publicEventsList', this.value);
        });
    }
    
    
    const universityEventFilter = document.getElementById('university-event-filter');
    if (universityEventFilter) {
        universityEventFilter.addEventListener('change', function() {
            filterEventsByCategory('universityEventsList', this.value);
        });
    }
    
    
    const rsoCategoryFilter = document.getElementById('rso-category-filter');
    if (rsoCategoryFilter) {
        rsoCategoryFilter.addEventListener('change', function() {
            filterEventsByCategory('rsoEventsList', this.value);
        });
    }
});