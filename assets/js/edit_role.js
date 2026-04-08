/**
 * JavaScript xử lý chỉnh sửa vai trò thành viên
 */

console.log('edit_role.js loaded');

function editRole(memberId, currentRole, memberName) {
    console.log('editRole called:', memberId, currentRole, memberName);
    
    // Tạo popup HTML
    const popupHTML = `
        <div id="editRoleModal" class="edit-role-modal-overlay">
            <div class="edit-role-modal-backdrop" onclick="closeEditRoleModal()"></div>
            <div class="edit-role-modal-box">
                <div class="edit-role-modal-header">
                    <div class="edit-role-modal-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </div>
                    <h2>Chỉnh sửa vai trò</h2>
                    <p class="edit-role-modal-desc">Thay đổi vai trò của thành viên trong CLB</p>
                    <button class="edit-role-close-btn" onclick="closeEditRoleModal()" aria-label="Đóng">&times;</button>
                </div>

                <div class="edit-role-modal-body">
                    <div class="member-info-card">
                        <div class="member-info-avatar">${memberName.charAt(0).toUpperCase()}</div>
                        <div class="member-info-details">
                            <h3>${memberName}</h3>
                            <p>Vai trò hiện tại: <span class="current-role-badge">${getRoleLabel(currentRole)}</span></p>
                        </div>
                    </div>

                    <div class="role-selection-grid">
                        <div class="role-option ${currentRole === 'thanh_vien' ? 'selected' : ''}" data-role="thanh_vien" onclick="selectRole('thanh_vien')">
                            <div class="role-option-icon">🎯</div>
                            <div class="role-option-content">
                                <h4>Thành viên</h4>
                                <p>Thành viên thông thường của CLB</p>
                            </div>
                            <div class="role-option-check">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>

                        <div class="role-option ${currentRole === 'truong_ban' ? 'selected' : ''}" data-role="truong_ban" onclick="selectRole('truong_ban')">
                            <div class="role-option-icon">⭐</div>
                            <div class="role-option-content">
                                <h4>Trưởng ban</h4>
                                <p>Quản lý một phòng ban trong CLB</p>
                            </div>
                            <div class="role-option-check">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>

                        <div class="role-option ${(currentRole === 'doi_pho' || currentRole === 'pho_chu_nhiem') ? 'selected' : ''}" data-role="doi_pho" onclick="selectRole('doi_pho')">
                            <div class="role-option-icon">👑</div>
                            <div class="role-option-content">
                                <h4>Đội phó</h4>
                                <p>Hỗ trợ đội trưởng quản lý CLB</p>
                            </div>
                            <div class="role-option-check">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>

                        <div class="role-option ${(currentRole === 'doi_truong' || currentRole === 'chu_nhiem') ? 'selected' : ''}" data-role="doi_truong" onclick="selectRole('doi_truong')">
                            <div class="role-option-icon">👑</div>
                            <div class="role-option-content">
                                <h4>Đội trưởng</h4>
                                <p>Quản lý và điều hành CLB</p>
                            </div>
                            <div class="role-option-check">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="edit-role-modal-footer">
                    <button class="btn-cancel-edit" onclick="closeEditRoleModal()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Hủy
                    </button>
                    <button class="btn-save-edit" onclick="saveRoleChange(${memberId}, '${memberName}')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    `;

    // Thêm popup vào body
    const container = document.createElement('div');
    container.id = 'editRoleContainer';
    container.innerHTML = popupHTML;
    document.body.appendChild(container);

    // Show modal với animation
    setTimeout(() => {
        const modal = document.getElementById('editRoleModal');
        if (modal) {
            modal.classList.add('show');
        }
    }, 10);
}

function getRoleLabel(role) {
    const labels = {
        'thanh_vien': 'Thành viên',
        'truong_ban': 'Trưởng ban',
        'doi_pho': 'Đội phó',
        'pho_chu_nhiem': 'Đội phó',
        'doi_truong': 'Đội trưởng',
        'chu_nhiem': 'Đội trưởng'
    };
    return labels[role] || 'Thành viên';
}

let selectedRole = null;

function selectRole(role) {
    console.log('Selected role:', role);
    selectedRole = role;
    
    // Remove selected class from all options
    document.querySelectorAll('.role-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    const selectedOption = document.querySelector(`.role-option[data-role="${role}"]`);
    if (selectedOption) {
        selectedOption.classList.add('selected');
    }
}

function saveRoleChange(memberId, memberName) {
    if (!selectedRole) {
        showNotification('⚠️ Vui lòng chọn vai trò mới', 'error');
        return;
    }
    
    console.log('Saving role change:', memberId, selectedRole);
    
    // Disable button
    const saveBtn = document.querySelector('.btn-save-edit');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span>Đang lưu...</span>';
    }
    
    // Get club_id from URL
    const urlParams = new URLSearchParams(window.location.search);
    const clubId = urlParams.get('id');
    
    fetch('process_edit_role.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `member_id=${memberId}&club_id=${clubId}&vai_tro=${selectedRole}`
    })
    .then(res => res.text())
    .then(text => {
        console.log('Response:', text);
        
        // Kiểm tra nếu response là HTML (có alert)
        if (text.includes('<script>')) {
            // Parse alert message
            const match = text.match(/alert\('([^']+)'\)/);
            if (match) {
                const message = match[1];
                if (message.includes('thành công')) {
                    showNotification('✓ ' + message, 'success');
                    closeEditRoleModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('✗ ' + message, 'error');
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>Lưu thay đổi';
                    }
                }
            }
        } else {
            showNotification('✓ Đã cập nhật vai trò thành công', 'success');
            closeEditRoleModal();
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('✗ Lỗi kết nối server!', 'error');
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>Lưu thay đổi';
        }
    });
}

function closeEditRoleModal() {
    const modal = document.getElementById('editRoleModal');
    if (!modal) return;

    modal.classList.remove('show');

    setTimeout(() => {
        const container = document.getElementById('editRoleContainer');
        if (container) {
            container.remove();
        }
        selectedRole = null;
    }, 300);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Đảm bảo các hàm có sẵn ở global scope
window.editRole = editRole;
window.selectRole = selectRole;
window.saveRoleChange = saveRoleChange;
window.closeEditRoleModal = closeEditRoleModal;

console.log('edit_role.js functions registered');
