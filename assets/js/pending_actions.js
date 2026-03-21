/**
 * JavaScript xử lý Approve/Reject thành viên
 */

console.log('pending_actions.js loading...');

function approveRequest(memberId, memberName) {
    console.log('approveRequest called:', memberId, memberName);
    
    // Hiển thị popup chọn phòng ban
    showSelectDepartmentModal(memberId, memberName);
}

function showSelectDepartmentModal(memberId, memberName) {
    // Lấy danh sách phòng ban từ page (cần có sẵn trong taopb.php)
    // Hoặc fetch từ API
    fetch('api/get_departments.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.departments || data.departments.length === 0) {
                alert('Vui lòng tạo phòng ban trước khi duyệt thành viên!');
                return;
            }
            
            // Tạo modal chọn phòng ban
            const modal = document.createElement('div');
            modal.className = 'modal-overlay select-dept-modal';
            modal.style.display = 'flex';
            modal.innerHTML = `
                <div class="modal-backdrop" onclick="this.closest('.modal-overlay').remove()"></div>
                <div class="modal-box select-dept-box">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h3>Chọn phòng ban</h3>
                        <p class="modal-desc">Chọn phòng ban cho thành viên <strong>${memberName}</strong></p>
                        <button class="close-btn" onclick="this.closest('.modal-overlay').remove()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="selectPhongBan" style="display: block; font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px;">Phòng ban <span style="color: #EF4444;">*</span></label>
                            <select id="selectPhongBan" class="form-select" required>
                                <option value="">-- Chọn phòng ban --</option>
                                ${data.departments.map(pb => `<option value="${pb.id}">${pb.ten_phong_ban}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn-cancel" onclick="this.closest('.modal-overlay').remove()">Hủy</button>
                        <button class="btn-submit" onclick="confirmApprove(${memberId}, '${memberName.replace(/'/g, "\\'")}')">Xác nhận</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Hiển thị modal với animation
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            
            // Lưu modal vào window để hàm confirmApprove có thể truy cập
            window.currentApproveModal = modal;
            window.currentMemberId = memberId;
            window.currentMemberName = memberName;
        })
        .catch(err => {
            console.error('Error loading departments:', err);
            alert('Lỗi khi tải danh sách phòng ban!');
        });
}

function getCsrf() {
    const modal = document.getElementById('pendingModal');
    const field = modal?.dataset?.csrfField || window.CSRF_FIELD || 'csrf_token';
    const token = modal?.dataset?.csrfToken || window.CSRF_TOKEN || '';
    return { field, token };
}

function confirmApprove(memberId, memberName) {
    const select = document.getElementById('selectPhongBan');
    const phongBanId = select ? select.value : null;
    
    if (!phongBanId || phongBanId === '') {
        alert('Vui lòng chọn phòng ban!');
        return;
    }
    
    // Đóng modal
    if (window.currentApproveModal) {
        window.currentApproveModal.remove();
    }
    
    // Tiến hành duyệt
    const card = document.getElementById(`pending-${memberId}`);
    if (!card) {
        console.error('Card not found:', `pending-${memberId}`);
        alert('Lỗi: Không tìm thấy card');
        return;
    }
    
    const approveBtn = card.querySelector('.btn-approve-new');
    const rejectBtn = card.querySelector('.btn-reject-new');
    
    if (!approveBtn || !rejectBtn) {
        console.error('Buttons not found');
        alert('Lỗi: Không tìm thấy nút');
        return;
    }
    
    // Disable buttons
    approveBtn.disabled = true;
    rejectBtn.disabled = true;
    approveBtn.innerHTML = '<span>Đang xử lý...</span>';
    
    console.log('Sending request to api/approve_member.php');
    
    const { field: csrfField, token: csrfToken } = getCsrf();
    const bodyParams = new URLSearchParams({
        member_id: memberId,
        phong_ban_id: phongBanId
    });
    if (csrfToken) bodyParams.append(csrfField, csrfToken);

    fetch('api/approve_member.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: bodyParams.toString()
    })
    .then(res => {
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            // Animation xóa
            card.classList.add('removing');
            
            // Show success notification
            showNotification(`✓ Đã chấp nhận "${memberName}" vào CLB!`, 'success');
            
            setTimeout(() => {
                card.remove();
                
                // Reload nếu không còn yêu cầu nào
                if (document.querySelectorAll('.pending-request-card').length === 0) {
                    setTimeout(() => location.reload(), 500);
                }
            }, 300);
        } else {
            showNotification('✗ ' + (data.message || 'Có lỗi xảy ra'), 'error');
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
            approveBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Đồng ý</span>';
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        showNotification('✗ Lỗi kết nối server!', 'error');
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg><span>Đồng ý</span>';
    });
}

function rejectRequest(memberId, memberName) {
    console.log('rejectRequest called:', memberId, memberName);
    
    if (!confirm(`Bạn có chắc chắn muốn từ chối yêu cầu của "${memberName}"?\n\nYêu cầu sẽ bị xóa vĩnh viễn.`)) {
        return;
    }
    
    const card = document.getElementById(`pending-${memberId}`);
    if (!card) {
        console.error('Card not found:', `pending-${memberId}`);
        alert('Lỗi: Không tìm thấy card');
        return;
    }
    
    const approveBtn = card.querySelector('.btn-approve-new');
    const rejectBtn = card.querySelector('.btn-reject-new');
    
    if (!approveBtn || !rejectBtn) {
        console.error('Buttons not found');
        alert('Lỗi: Không tìm thấy nút');
        return;
    }
    
    // Disable buttons
    approveBtn.disabled = true;
    rejectBtn.disabled = true;
    rejectBtn.innerHTML = '<span>Đang xử lý...</span>';
    
    console.log('Sending request to api/reject_member.php');
    
    const { field: csrfField, token: csrfToken } = getCsrf();
    const bodyParams = new URLSearchParams({ member_id: memberId });
    if (csrfToken) bodyParams.append(csrfField, csrfToken);

    fetch('api/reject_member.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: bodyParams.toString()
    })
    .then(res => {
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            // Animation xóa
            card.classList.add('removing');
            
            // Show notification
            showNotification(`Đã từ chối yêu cầu của "${memberName}"`, 'info');
            
            setTimeout(() => {
                card.remove();
                
                // Reload nếu không còn yêu cầu nào
                if (document.querySelectorAll('.pending-request-card').length === 0) {
                    setTimeout(() => location.reload(), 500);
                }
            }, 300);
        } else {
            showNotification('✗ ' + (data.message || 'Có lỗi xảy ra'), 'error');
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
            rejectBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg><span>Từ chối</span>';
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        showNotification('✗ Lỗi kết nối server!', 'error');
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        rejectBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg><span>Từ chối</span>';
    });
}

function showNotification(message, type = 'info') {
    console.log('showNotification:', message, type);
    
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            ${type === 'success' ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>' : ''}
            ${type === 'error' ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>' : ''}
            ${type === 'info' ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>' : ''}
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Log khi file được load
console.log('pending_actions.js loaded');

// Đảm bảo các hàm có sẵn ở global scope
window.approveRequest = approveRequest;
window.rejectRequest = rejectRequest;
window.confirmApprove = confirmApprove;
window.showSelectDepartmentModal = showSelectDepartmentModal;
