<?php
session_start();
require_once(__DIR__ . "/assets/database/connect.php");
require_once(__DIR__ . "/includes/constants.php");
require_once(__DIR__ . "/includes/functions.php");
require 'site.php';

// CSRF token cho các thao tác duyệt/từ chối ngay tại trang thông báo
$csrf_token = generate_csrf_token();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

load_top();
load_header();
?>

<link rel="stylesheet" href="assets/css/notifications.css">

<div class="notifications-container">
    <div class="notifications-header">
        <div class="header-content">
            <div class="header-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
            </div>
            <div>
                <h1>Thông báo</h1>
                <p>Theo dõi các hoạt động và cập nhật mới nhất</p>
            </div>
        </div>
        <button id="mark-all-read" class="btn-mark-all">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Đánh dấu tất cả đã đọc
        </button>
    </div>

    <div class="notifications-tabs">
        <button class="tab-btn active" data-filter="all">Tất cả</button>
        <button class="tab-btn" data-filter="unread">Chưa đọc</button>
        <button class="tab-btn" data-filter="club_join">CLB</button>
        <button class="tab-btn" data-filter="event_invite">Sự kiện</button>
    </div>

    <div class="notifications-body">
        <div id="notifications-list" class="notifications-list">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Đang tải thông báo...</p>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF cho fetch POST
const CSRF_FIELD = '<?php echo CSRF_TOKEN_NAME; ?>';
const CSRF_TOKEN = '<?php echo $csrf_token; ?>';

document.addEventListener('DOMContentLoaded', function() {
    let currentFilter = 'all';
    let allNotifications = [];

    // Load notifications
    loadNotifications();

    // Tab filtering
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterNotifications();
        });
    });

    // Mark all as read
    document.getElementById('mark-all-read').addEventListener('click', markAllAsRead);

    function loadNotifications() {
        fetch('api/get_notifications.php?limit=50')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allNotifications = data.notifications;
                    filterNotifications();
                } else {
                    showError('Không thể tải thông báo');
                }
            })
            .catch(err => {
                console.error(err);
                showError('Lỗi kết nối');
            });
    }

    function filterNotifications() {
        let filtered = allNotifications;

        if (currentFilter === 'unread') {
            filtered = allNotifications.filter(n => n.is_read == 0);
        } else if (currentFilter !== 'all') {
            filtered = allNotifications.filter(n => n.type === currentFilter);
        }

        renderNotifications(filtered);
    }

    function renderNotifications(notifications) {
        const container = document.getElementById('notifications-list');

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <h3>Không có thông báo</h3>
                    <p>Bạn chưa có thông báo nào ${currentFilter === 'unread' ? 'chưa đọc' : ''}</p>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notif => {
            const icon = getNotificationIcon(notif.type);
            const timeAgo = formatTimeAgo(notif.created_at);
            const unreadClass = notif.is_read == 0 ? 'unread' : '';
            const isClubJoin = notif.type === 'club_join' && notif.member_id;
            
            // Hiển thị trạng thái yêu cầu
            let statusBadge = '';
            if (isClubJoin && notif.request_status) {
                if (notif.request_status === 'approved') {
                    statusBadge = '<span class="request-status-badge status-approved">✅ Đã duyệt</span>';
                } else if (notif.request_status === 'rejected') {
                    statusBadge = '<span class="request-status-badge status-rejected">❌ Đã từ chối</span>';
                } else {
                    statusBadge = '<span class="request-status-badge status-pending">⏳ Chờ duyệt</span>';
                }
            }

            return `
                <div class="notification-item ${unreadClass}" data-id="${notif.id}" data-link="${notif.link || ''}" data-member-id="${notif.member_id || ''}" data-club-id="${notif.club_id || ''}" data-type="${notif.type}">
                    <div class="notif-icon ${notif.type}">
                        ${icon}
                    </div>
                    <div class="notif-content">
                        <h4>${notif.title}</h4>
                        <p>${notif.message}</p>
                        <div class="notif-meta">
                            <span class="notif-time">${timeAgo}</span>
                            ${statusBadge}
                        </div>
                    </div>
                    ${notif.is_read == 0 ? '<div class="unread-dot"></div>' : ''}
                </div>
            `;
        }).join('');

        // Add click handlers
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const id = this.dataset.id;
                const link = this.dataset.link;
                const memberId = this.dataset.memberId;
                const clubId = this.dataset.clubId;
                const type = this.dataset.type;
                
                // Nếu là thông báo club_join, mở modal thay vì redirect
                if (type === 'club_join' && memberId && clubId) {
                    e.stopPropagation();
                    openJoinRequestModal(memberId, clubId, id);
                } else {
                    markAsRead(id, link);
                }
            });
        });
    }

    function getNotificationIcon(type) {
        const icons = {
            'club_join': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'event_invite': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'event_join': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'club_invite': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>',
            'event_invite': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
            'role_change': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"></path><path d="M2 17l10 5 10-5"></path><path d="M2 12l10 5 10-5"></path></svg>',
            'general': '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        return icons[type] || icons['general'];
    }

    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Vừa xong';
        if (seconds < 3600) return Math.floor(seconds / 60) + ' phút trước';
        if (seconds < 86400) return Math.floor(seconds / 3600) + ' giờ trước';
        if (seconds < 604800) return Math.floor(seconds / 86400) + ' ngày trước';
        return date.toLocaleDateString('vi-VN');
    }

    function markAsRead(id, link) {
        fetch('api/mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'notification_id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update notification count in header immediately
                if (typeof window.updateNotificationCount === 'function') {
                    window.updateNotificationCount();
                }
                if (link) {
                    window.location.href = link;
                } else {
                    loadNotifications();
                }
            }
        });
    }

    function markAllAsRead() {
        const unreadIds = allNotifications.filter(n => n.is_read == 0).map(n => n.id);
        
        if (unreadIds.length === 0) {
            showNotification('Không có thông báo chưa đọc', 'info');
            return;
        }

        Promise.all(unreadIds.map(id => 
            fetch('api/mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'notification_id=' + id
            })
        )).then(() => {
            // Update notification count in header immediately
            if (typeof window.updateNotificationCount === 'function') {
                window.updateNotificationCount();
            }
            showNotification('Đã đánh dấu tất cả đã đọc', 'success');
            loadNotifications();
        });
    }

    function showError(message) {
        document.getElementById('notifications-list').innerHTML = `
            <div class="error-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <p>${message}</p>
            </div>
        `;
    }

    function showNotification(message, type) {
        const notif = document.createElement('div');
        notif.className = `toast-notification toast-${type}`;
        notif.textContent = message;
        document.body.appendChild(notif);

        setTimeout(() => notif.classList.add('show'), 10);
        setTimeout(() => {
            notif.classList.remove('show');
            setTimeout(() => notif.remove(), 300);
        }, 3000);
    }

    // Mở modal hiển thị thông tin chi tiết yêu cầu tham gia
    function openJoinRequestModal(memberId, clubId, notificationId) {
        // Đánh dấu thông báo đã đọc
        markAsRead(notificationId, null);
        
        // Load thông tin chi tiết
        fetch(`api/get_join_request_detail.php?member_id=${memberId}&club_id=${clubId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.request) {
                    showJoinRequestDetailModal(data.request, data.departments || [], memberId, notificationId, clubId);
                } else {
                    // Nếu không tìm thấy member, có thể đã bị từ chối (xóa)
                    // Hiển thị modal với thông tin từ notification
                    if (data.message && data.message.includes('Không tìm thấy')) {
                        // Tạo request object giả với trạng thái rejected
                        const rejectedRequest = {
                            member_status: 'rejected',
                            ho_ten: 'Thành viên',
                            username: '',
                            email: '',
                            avatar: 'assets/img/avatars/user.svg',
                            user_phone: '',
                            ten_clb: '',
                            ten_phong_ban: '',
                            phong_ban_id: null,
                            loi_nhan: '',
                            requested_at: null
                        };
                        showJoinRequestDetailModal(rejectedRequest, [], memberId, notificationId, clubId);
                    } else {
                        showNotification(data.message || 'Không thể tải thông tin yêu cầu', 'error');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                showNotification('Lỗi kết nối', 'error');
            });
    }

    function showJoinRequestDetailModal(request, departments, memberId, notificationId, clubId) {
        const modal = document.createElement('div');
        modal.className = 'join-request-modal-overlay';
        modal.id = 'joinRequestModal';
        
        // Format avatar
        const avatar = request.avatar || 'assets/img/avatars/user.svg';
        const avatarUrl = avatar.startsWith('http') ? avatar : (avatar + '?v=' + Date.now());
        
        // Format phone
        const phone = request.request_phone || request.user_phone || 'Chưa cung cấp';
        
        // Format department
        const currentDept = request.ten_phong_ban || 'Chưa phân công';
        const currentDeptId = request.phong_ban_id || '';
        
        // Kiểm tra trạng thái yêu cầu
        const memberStatus = request.member_status || '';
        const isApproved = memberStatus === 'dang_hoat_dong';
        const isRejected = memberStatus === 'rejected' || memberStatus === 'tu_choi' || (!memberStatus && !request.ho_ten);
        const isPending = memberStatus === 'cho_duyet' || (!isApproved && !isRejected);
        
        modal.innerHTML = `
            <div class="join-request-modal-backdrop"></div>
            <div class="join-request-modal-content">
                <div class="join-request-modal-header">
                    <h3>Chi tiết yêu cầu tham gia CLB</h3>
                    <button class="join-request-modal-close">&times;</button>
                </div>
                
                <div class="join-request-modal-body">
                    <div class="request-user-info">
                        <div class="user-avatar-section">
                            <img src="${avatarUrl}" alt="${request.ho_ten}" class="user-avatar-large">
                        </div>
                        <div class="user-details-section">
                            <h4>${request.ho_ten}</h4>
                            <p class="user-username">@${request.username}</p>
                            <div class="user-info-grid">
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value">${request.email || 'Chưa cung cấp'}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Số điện thoại:</span>
                                    <span class="info-value">${phone}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">CLB:</span>
                                    <span class="info-value">${request.ten_clb}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Thời gian gửi:</span>
                                    <span class="info-value">${request.requested_at ? formatTimeAgo(request.requested_at) : 'Chưa xác định'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="request-form-section">
                        <div class="form-group">
                            <label for="request_phong_ban">Phòng ban <span class="required">*</span></label>
                            <select id="request_phong_ban" class="form-select" required>
                                <option value="">-- Chọn phòng ban --</option>
                                ${departments.map(d => `
                                    <option value="${d.id}" ${d.id == currentDeptId ? 'selected' : ''}>${d.ten_phong_ban}</option>
                                `).join('')}
                            </select>
                            ${currentDeptId ? `<p class="form-hint">Phòng ban đã chọn: <strong>${currentDept}</strong></p>` : ''}
                        </div>
                        
                        ${request.loi_nhan ? `
                            <div class="form-group">
                                <label>Lời nhắn từ người gửi:</label>
                                <div class="message-box">
                                    <p>${request.loi_nhan}</p>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="join-request-modal-footer">
                    ${isApproved ? `
                        <button class="btn-approve-modal btn-status-disabled" disabled>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Đã duyệt
                        </button>
                    ` : isRejected ? `
                        <button class="btn-reject-modal btn-status-disabled" disabled>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Đã từ chối
                        </button>
                    ` : `
                        <button class="btn-reject-modal" data-member-id="${memberId}" data-notification-id="${notificationId}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Từ chối
                        </button>
                        <button class="btn-approve-modal" data-member-id="${memberId}" data-notification-id="${notificationId}" data-club-id="${clubId}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Duyệt
                        </button>
                    `}
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Đóng modal khi click ra ngoài
        modal.querySelector('.join-request-modal-backdrop').addEventListener('click', closeJoinRequestModal);
        
        // Đóng modal khi click nút X
        modal.querySelector('.join-request-modal-close').addEventListener('click', closeJoinRequestModal);
        
        // Xử lý nút Từ chối
        modal.querySelector('.btn-reject-modal').addEventListener('click', function() {
            const memberId = parseInt(this.dataset.memberId);
            const notificationId = parseInt(this.dataset.notificationId);
            handleRejectFromModal(memberId, notificationId);
        });
        
        // Xử lý nút Duyệt
        modal.querySelector('.btn-approve-modal').addEventListener('click', function() {
            const memberId = parseInt(this.dataset.memberId);
            const notificationId = parseInt(this.dataset.notificationId);
            const clubId = parseInt(this.dataset.clubId);
            handleApproveFromModal(memberId, notificationId, clubId);
        });
    }

    function closeJoinRequestModal() {
        const modal = document.getElementById('joinRequestModal');
        if (modal) {
            modal.remove();
        }
    }

    // Xử lý duyệt từ modal
    function handleApproveFromModal(memberId, notificationId, clubId) {
        const phongBanSelect = document.getElementById('request_phong_ban');
        const phongBanId = phongBanSelect ? phongBanSelect.value : '';
        
        // Validation
        if (!phongBanId || phongBanId <= 0) {
            showNotification('Vui lòng chọn phòng ban trước khi duyệt', 'error');
            phongBanSelect?.focus();
            return;
        }
        
        // Lấy các nút
        const approveBtn = document.querySelector('.btn-approve-modal');
        const rejectBtn = document.querySelector('.btn-reject-modal');
        
        // Disable buttons và hiển thị loading
        if (approveBtn) {
            approveBtn.disabled = true;
            approveBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spinner-svg">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                    <path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round"></path>
                </svg>
                Đang duyệt...
            `;
        }
        if (rejectBtn) {
            rejectBtn.disabled = true;
        }
        
        // Gọi API
        const formData = new FormData();
        formData.append('member_id', memberId);
        formData.append('phong_ban_id', phongBanId);
        if (CSRF_TOKEN) {
            formData.append(CSRF_FIELD, CSRF_TOKEN);
        }
        
        fetch('api/approve_member.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Đã duyệt yêu cầu thành công!', 'success');
                // Đánh dấu thông báo đã đọc
                markAsRead(notificationId, null);
                // Đóng modal
                closeJoinRequestModal();
                // Reload notifications sau 1 giây
                setTimeout(() => {
                    loadNotifications();
                }, 1000);
            } else {
                showNotification(data.message || '❌ Có lỗi xảy ra khi duyệt', 'error');
                // Re-enable buttons
                if (approveBtn) {
                    approveBtn.disabled = false;
                    approveBtn.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Duyệt
                    `;
                }
                if (rejectBtn) {
                    rejectBtn.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error('Error approving member:', err);
            showNotification('❌ Lỗi kết nối server. Vui lòng thử lại!', 'error');
            // Re-enable buttons
            if (approveBtn) {
                approveBtn.disabled = false;
                approveBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Duyệt
                `;
            }
            if (rejectBtn) {
                rejectBtn.disabled = false;
            }
        });
    }

    // Xử lý từ chối từ modal
    function handleRejectFromModal(memberId, notificationId) {
        // Lấy các nút
        const approveBtn = document.querySelector('.btn-approve-modal');
        const rejectBtn = document.querySelector('.btn-reject-modal');
        
        // Disable buttons và hiển thị loading
        if (rejectBtn) {
            rejectBtn.disabled = true;
            rejectBtn.innerHTML = `
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spinner-svg">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.3"></circle>
                    <path d="M12 2 A10 10 0 0 1 22 12" stroke-linecap="round"></path>
                </svg>
                Đang từ chối...
            `;
        }
        if (approveBtn) {
            approveBtn.disabled = true;
        }
        
        // Gọi API
        const formData = new FormData();
        formData.append('member_id', memberId);
        if (CSRF_TOKEN) {
            formData.append(CSRF_FIELD, CSRF_TOKEN);
        }
        
        fetch('api/reject_member.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Đã từ chối yêu cầu', 'success');
                // Đánh dấu thông báo đã đọc
                markAsRead(notificationId, null);
                // Đóng modal
                closeJoinRequestModal();
                // Reload notifications sau 1 giây
                setTimeout(() => {
                    loadNotifications();
                }, 1000);
            } else {
                showNotification(data.message || '❌ Có lỗi xảy ra khi từ chối', 'error');
                // Re-enable buttons
                if (rejectBtn) {
                    rejectBtn.disabled = false;
                    rejectBtn.innerHTML = `
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Từ chối
                    `;
                }
                if (approveBtn) {
                    approveBtn.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error('Error rejecting member:', err);
            showNotification('❌ Lỗi kết nối server. Vui lòng thử lại!', 'error');
            // Re-enable buttons
            if (rejectBtn) {
                rejectBtn.disabled = false;
                rejectBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Từ chối
                `;
            }
            if (approveBtn) {
                approveBtn.disabled = false;
            }
        });
    }

    // Hàm approveMember cũ - giữ lại để tương thích (nếu có code khác gọi)
    function approveMember(memberId, notificationId, phongBanId) {
        if (!phongBanId || phongBanId <= 0) {
            showNotification('Vui lòng chọn phòng ban', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('member_id', memberId);
        formData.append('phong_ban_id', phongBanId);

        fetch('api/approve_member.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Đã duyệt yêu cầu thành công', 'success');
                markAsRead(notificationId, null);
                setTimeout(() => loadNotifications(), 500);
            } else {
                showNotification(data.message || '❌ Có lỗi xảy ra', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('❌ Lỗi kết nối', 'error');
        });
    }

    // Hàm handleReject cũ - giữ lại để tương thích (nếu có code khác gọi)
    function handleReject(memberId, notificationId) {
        const formData = new FormData();
        formData.append('member_id', memberId);

        fetch('api/reject_member.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification('✅ Đã từ chối yêu cầu', 'success');
                markAsRead(notificationId, null);
                setTimeout(() => loadNotifications(), 500);
            } else {
                showNotification(data.message || '❌ Có lỗi xảy ra', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('❌ Lỗi kết nối', 'error');
        });
    }
});
</script>

<?php load_footer(); ?>
