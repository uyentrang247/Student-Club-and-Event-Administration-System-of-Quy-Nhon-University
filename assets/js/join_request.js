// Club Modals - Xử lý popup đăng ký CLB và sự kiện

// ===== CLUB MODAL FUNCTIONS =====
function openJoinModal(clubId) {
    console.log('Opening modal for club:', clubId);
    
    fetch(`popup_join.php?club_id=${clubId}`)
        .then(r => {
            if (!r.ok) throw new Error('Network error');
            return r.text();
        })
        .then(html => {
            document.getElementById('modalContainer').innerHTML = html;
            const modal = document.getElementById('joinClubModal');
            const modalContent = modal ? modal.querySelector('.modal-content') : null;
            if (modal) {
                // Đặt style trực tiếp để đảm bảo không bị override
                modal.style.zIndex = '100001';
                modal.style.position = 'fixed';
                modal.style.top = '0';
                modal.style.left = '0';
                modal.style.right = '0';
                modal.style.bottom = '0';
                modal.style.display = 'flex';
                modal.style.justifyContent = 'center';
                modal.style.alignItems = 'center';
                modal.style.padding = '20px';
                modal.style.paddingTop = '100px';
                modal.style.overflowY = 'auto';
                modal.style.background = 'rgba(0, 0, 0, 0.5)';
                modal.style.backdropFilter = 'blur(4px)';
                
                if (modalContent) {
                    modalContent.style.position = 'relative';
                    modalContent.style.zIndex = '100003';
                    modalContent.style.maxHeight = 'calc(100vh - 140px)';
                    modalContent.style.width = '100%';
                    modalContent.style.maxWidth = '540px';
                    modalContent.style.margin = '0 auto 40px auto';
                }
                
                modal.classList.add('show');
                
                // Khởi tạo lại validation cho form mới được load
                if (typeof initJoinFormValidation === 'function') {
                    setTimeout(() => {
                        initJoinFormValidation();
                    }, 100);
                }
                
                // Thêm event listener để đóng modal khi click outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal || e.target.classList.contains('modal-backdrop')) {
                        closeJoinModal();
                    }
                });
            }
        })
        .catch(err => {
            console.error('Lỗi khi mở popup CLB:', err);
            alert('Có lỗi xảy ra khi mở form đăng ký CLB');
        });
}

function closeJoinModal() {
    const modal = document.getElementById('joinClubModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            document.getElementById('modalContainer').innerHTML = '';
        }, 300);
    }
}

// ===== EVENT MODAL FUNCTIONS =====
function openEventModal(eventId) {
    console.log('Opening event modal for:', eventId);
    
    fetch(`popup_joinevent.php?event_id=${eventId}`)
        .then(r => {
            if (!r.ok) throw new Error('Network error');
            return r.text();
        })
        .then(html => {
            document.getElementById('eventModalContainer').innerHTML = html;
            const modal = document.getElementById('joinEventModal');
            if (modal) {
                modal.classList.add('show');
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeEventModal();
                    }
                });
            }
        })
        .catch(err => {
            console.error('Lỗi khi mở popup sự kiện:', err);
            alert('Có lỗi xảy ra khi mở form đăng ký sự kiện');
        });
}

function closeEventModal() {
    const modal = document.getElementById('joinEventModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            const container = document.getElementById('eventModalContainer');
            if (container) {
                container.innerHTML = '';
            }
        }, 300);
    }
}

// ===== GLOBAL EVENT LISTENERS =====
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeJoinModal();
        closeEventModal();
    }
});

// ===== FORM HANDLERS =====
// Xử lý form đăng ký CLB (nếu cần)
function handleClubRegistration(formData) {
    // Logic xử lý đăng ký CLB
    console.log('Club registration data:', formData);
}

// Xử lý form đăng ký sự kiện (nếu cần)  
function handleEventRegistration(formData) {
    // Logic xử lý đăng ký sự kiện
    console.log('Event registration data:', formData);
}

// ===== UTILITY FUNCTIONS =====
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<span>Đang xử lý...</span>';
    button.disabled = true;
    return originalText;
}

function resetButton(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

function showSuccessMessage(message) {
    // Có thể tích hợp thư viện toast notification sau
    alert(message);
}

function showErrorMessage(message) {
    alert('Lỗi: ' + message);
}