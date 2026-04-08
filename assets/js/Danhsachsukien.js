// ===== GLOBAL VARIABLES =====
let allEvents = [];
let visibleCount = 6;

// ===== INITIALIZE =====
document.addEventListener('DOMContentLoaded', function() {
    allEvents = Array.from(document.querySelectorAll('.event-card'));
    updateLoadMoreButton();
    
    // Event listeners
    document.getElementById('searchInput').addEventListener('input', filterEvents);
    document.getElementById('categoryFilter').addEventListener('change', filterEvents);
    document.getElementById('sortFilter').addEventListener('change', sortEvents);
    document.getElementById('resetBtn').addEventListener('click', resetFilters);
    document.getElementById('loadMoreBtn').addEventListener('click', loadMore);
    
    // Category icon click
    document.querySelectorAll('.cat-item').forEach(item => {
        item.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            document.getElementById('categoryFilter').value = category;
            filterEvents();
        });
    });
});

// ===== FILTER EVENTS =====
function filterEvents() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const categoryValue = document.getElementById('categoryFilter').value;
    
    let visibleEvents = 0;
    
    allEvents.forEach(card => {
        const eventName = card.getAttribute('data-name').toLowerCase();
        const eventCategory = card.getAttribute('data-category');
        
        const matchesSearch = eventName.includes(searchValue);
        const matchesCategory = !categoryValue || eventCategory === categoryValue;
        
        if (matchesSearch && matchesCategory) {
            if (visibleEvents < visibleCount) {
                card.classList.remove('hidden-event');
                card.classList.add('show-event');
            } else {
                card.classList.add('hidden-event');
                card.classList.remove('show-event');
            }
            visibleEvents++;
        } else {
            card.classList.add('hidden-event');
            card.classList.remove('show-event');
        }
    });
    
    updateLoadMoreButton();
    showNoResults(visibleEvents === 0);
}

// ===== SORT EVENTS =====
function sortEvents() {
    const sortValue = document.getElementById('sortFilter').value;
    const eventList = document.getElementById('event-list');
    
    let sortedEvents = [...allEvents];
    
    switch(sortValue) {
        case 'date-asc':
            // Sort by date ascending (oldest first)
            sortedEvents.sort((a, b) => {
                const dateA = a.querySelector('.date-day').textContent;
                const dateB = b.querySelector('.date-day').textContent;
                return parseInt(dateA) - parseInt(dateB);
            });
            break;
            
        case 'date-desc':
            // Sort by date descending (newest first)
            sortedEvents.sort((a, b) => {
                const dateA = a.querySelector('.date-day').textContent;
                const dateB = b.querySelector('.date-day').textContent;
                return parseInt(dateB) - parseInt(dateA);
            });
            break;
            
        case 'participants-desc':
            // Sort by participants descending (most first)
            sortedEvents.sort((a, b) => {
                const countA = parseInt(a.querySelector('.participant-count').textContent.match(/\d+/)[0]);
                const countB = parseInt(b.querySelector('.participant-count').textContent.match(/\d+/)[0]);
                return countB - countA;
            });
            break;
            
        case 'participants-asc':
            // Sort by participants ascending (least first)
            sortedEvents.sort((a, b) => {
                const countA = parseInt(a.querySelector('.participant-count').textContent.match(/\d+/)[0]);
                const countB = parseInt(b.querySelector('.participant-count').textContent.match(/\d+/)[0]);
                return countA - countB;
            });
            break;
    }
    
    // Re-append sorted events
    sortedEvents.forEach(event => {
        eventList.appendChild(event);
    });
    
    allEvents = sortedEvents;
    filterEvents();
}

// ===== RESET FILTERS =====
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('sortFilter').value = '';
    
    visibleCount = 6;
    
    allEvents.forEach((card, index) => {
        if (index < visibleCount) {
            card.classList.remove('hidden-event');
            card.classList.add('show-event');
        } else {
            card.classList.add('hidden-event');
            card.classList.remove('show-event');
        }
    });
    
    updateLoadMoreButton();
    showNoResults(false);
}

// ===== LOAD MORE =====
function loadMore() {
    const hiddenEvents = allEvents.filter(card => 
        card.classList.contains('hidden-event') && 
        !card.style.display.includes('none')
    );
    
    const toShow = hiddenEvents.slice(0, 6);
    toShow.forEach(card => {
        card.classList.remove('hidden-event');
        card.classList.add('show-event');
    });
    
    visibleCount += 6;
    updateLoadMoreButton();
}

// ===== UPDATE LOAD MORE BUTTON =====
function updateLoadMoreButton() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const hiddenEvents = allEvents.filter(card => 
        card.classList.contains('hidden-event')
    );
    
    if (hiddenEvents.length === 0) {
        loadMoreBtn.style.display = 'none';
        document.querySelector('.xem-them-wrap').style.display = 'none';
    } else {
        loadMoreBtn.style.display = 'flex';
        document.querySelector('.xem-them-wrap').style.display = 'flex';
    }
}

// ===== SHOW NO RESULTS MESSAGE =====
function showNoResults(show) {
    let noResultsDiv = document.querySelector('.no-results');
    
    if (show) {
        if (!noResultsDiv) {
            noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'no-results';
            noResultsDiv.innerHTML = `
                <div class="no-results-content">
                    <i>🔍</i>
                    <h3>Không tìm thấy sự kiện</h3>
                    <p>Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
                </div>
            `;
            document.getElementById('event-list').appendChild(noResultsDiv);
        }
        noResultsDiv.style.display = 'block';
    } else {
        if (noResultsDiv) {
            noResultsDiv.style.display = 'none';
        }
    }
}

// ===== SMOOTH SCROLL =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
