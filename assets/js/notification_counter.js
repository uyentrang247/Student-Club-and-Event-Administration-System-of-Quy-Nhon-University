// Update notification count in header
function updateNotificationCount() {
    fetch('api/get_unread_count.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notification-count');
            if (badge && data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.style.display = 'block';
            } else if (badge) {
                badge.style.display = 'none';
            }
        })
        .catch(err => console.error('Error fetching notification count:', err));
}

// Update count on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateNotificationCount);
} else {
    updateNotificationCount();
}

// Update count every 30 seconds
setInterval(updateNotificationCount, 30000);
