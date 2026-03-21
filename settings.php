<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_css = "settings.css";
require 'site.php';
load_top();
load_header();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="settings-container">
    <div class="page-header">
        <h1>Cài đặt</h1>
        <p>Quản lý tài khoản và tùy chỉnh cá nhân</p>
    </div>

    <div class="settings-content">
        <div class="settings-card">
            <h2>🔐 Bảo mật</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Đổi mật khẩu</h3>
                    <p>Cập nhật mật khẩu để bảo mật tài khoản</p>
                </div>
                <button class="btn-action" onclick="location.href='change-password.php'">Thay đổi</button>
            </div>
        </div>

        <div class="settings-card">
            <h2>🔔 Thông báo</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Thông báo email</h3>
                    <p>Nhận thông báo về sự kiện và hoạt động CLB</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="emailNotification" checked>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Thông báo sự kiện</h3>
                    <p>Nhận nhắc nhở về sự kiện sắp diễn ra</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="eventNotification" checked>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="settings-card">
            <h2>🎨 Giao diện</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Chế độ tối</h3>
                    <p>Chuyển sang giao diện tối để bảo vệ mắt</p>
                </div>
                <label class="switch">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div class="settings-card danger">
            <h2>⚠️ Vùng nguy hiểm</h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3>Xóa tài khoản</h3>
                    <p>Xóa vĩnh viễn tài khoản và tất cả dữ liệu</p>
                </div>
                <button class="btn-danger" onclick="openDeleteAccountModal()">Xóa tài khoản</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa tài khoản -->
<div id="deleteAccountModal" class="delete-modal-overlay" style="display: none;">
    <div class="delete-modal-backdrop" onclick="closeDeleteAccountModal()"></div>
    <div class="delete-modal-content">
        <div class="delete-modal-header">
            <div class="delete-modal-icon">⚠️</div>
            <h2>Xác nhận xóa tài khoản</h2>
        </div>
        <div class="delete-modal-body">
            <p class="warning-text">
                <strong>Hành động này không thể hoàn tác!</strong>
            </p>
            <p>Tất cả dữ liệu của bạn sẽ bị xóa vĩnh viễn, bao gồm:</p>
            <ul class="delete-list">
                <li>Thông tin cá nhân</li>
                <li>Tất cả thành viên trong các CLB</li>
                <li>Đăng ký sự kiện</li>
                <li>Thông báo và lịch sử hoạt động</li>
            </ul>
            <p class="note-text">
                <strong>Lưu ý:</strong> Nếu bạn đang là chủ nhiệm của CLB nào đó, bạn cần chuyển quyền hoặc xóa CLB trước khi xóa tài khoản.
            </p>
            <div class="confirm-input-group">
                <label for="confirmDeleteInput">
                    Để xác nhận, vui lòng nhập <strong>"XÓA TÀI KHOẢN"</strong> vào ô bên dưới:
                </label>
                <input 
                    type="text" 
                    id="confirmDeleteInput" 
                    placeholder="Nhập: XÓA TÀI KHOẢN"
                    autocomplete="off"
                >
            </div>
        </div>
        <div class="delete-modal-footer">
            <button type="button" class="btn-cancel" onclick="closeDeleteAccountModal()">
                Hủy
            </button>
            <button type="button" class="btn-delete-confirm" id="btnDeleteAccount" onclick="confirmDeleteAccount()">
                Xóa tài khoản
            </button>
        </div>
    </div>
</div>

<?php 
$csrf_token_value = generate_csrf_token();
?>
<script>
window.CSRF_FIELD = '<?php echo CSRF_TOKEN_NAME; ?>';
window.CSRF_TOKEN = '<?php echo $csrf_token_value; ?>';

function openDeleteAccountModal() {
    const modal = document.getElementById('deleteAccountModal');
    const input = document.getElementById('confirmDeleteInput');
    if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
            if (input) input.focus();
        }, 10);
    }
}

function closeDeleteAccountModal() {
    const modal = document.getElementById('deleteAccountModal');
    const input = document.getElementById('confirmDeleteInput');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            if (input) input.value = '';
        }, 300);
    }
}

function confirmDeleteAccount() {
    const confirmText = document.getElementById('confirmDeleteInput').value.trim();
    const btnDelete = document.getElementById('btnDeleteAccount');
    
    if (confirmText !== 'XÓA TÀI KHOẢN') {
        alert('Vui lòng nhập chính xác "XÓA TÀI KHOẢN" để xác nhận!');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn xóa tài khoản? Hành động này không thể hoàn tác!')) {
        return;
    }
    
    // Disable button và hiển thị loading
    btnDelete.disabled = true;
    btnDelete.innerHTML = '<span class="spinner-small"></span> Đang xóa...';
    
    const formData = new URLSearchParams();
    formData.append('confirm_text', confirmText);
    if (window.CSRF_FIELD && window.CSRF_TOKEN) {
        formData.append(window.CSRF_FIELD, window.CSRF_TOKEN);
    }
    
    fetch('api/delete_account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Tài khoản đã được xóa thành công. Bạn sẽ được chuyển về trang chủ.');
            window.location.href = 'trangchu.php';
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể xóa tài khoản'));
            btnDelete.disabled = false;
            btnDelete.innerHTML = 'Xóa tài khoản';
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối server. Vui lòng thử lại!');
        btnDelete.disabled = false;
        btnDelete.innerHTML = 'Xóa tài khoản';
    });
}

// Đóng modal khi nhấn ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteAccountModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const emailNotification = document.getElementById('emailNotification');
    const eventNotification = document.getElementById('eventNotification');
    
    // Load settings từ localStorage
    try {
        // Dark mode
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Email notification
        const emailNotif = localStorage.getItem('emailNotification');
        if (emailNotif !== null) {
            emailNotification.checked = emailNotif === 'true';
        }
        
        // Event notification
        const eventNotif = localStorage.getItem('eventNotification');
        if (eventNotif !== null) {
            eventNotification.checked = eventNotif === 'true';
        }
    } catch (e) {
        console.error('localStorage error:', e);
    }
    
    // Dark mode toggle
    darkModeToggle.addEventListener('change', function() {
        if (this.checked) {
            document.body.classList.add('dark-mode');
            try {
                localStorage.setItem('darkMode', 'true');
            } catch (e) {}
        } else {
            document.body.classList.remove('dark-mode');
            try {
                localStorage.setItem('darkMode', 'false');
            } catch (e) {}
        }
    });
    
    // Email notification toggle
    emailNotification.addEventListener('change', function() {
        try {
            localStorage.setItem('emailNotification', this.checked);
            showToast(this.checked ? 'Đã bật thông báo email' : 'Đã tắt thông báo email');
        } catch (e) {}
    });
    
    // Event notification toggle
    eventNotification.addEventListener('change', function() {
        try {
            localStorage.setItem('eventNotification', this.checked);
            showToast(this.checked ? 'Đã bật thông báo sự kiện' : 'Đã tắt thông báo sự kiện');
        } catch (e) {}
    });
    
    // Toast notification
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
});
</script>

<?php
load_footer();
?>
