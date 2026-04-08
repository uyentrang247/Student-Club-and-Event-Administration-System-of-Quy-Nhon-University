<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/constants.php';
require_once __DIR__ . '/includes/functions.php';
$popup_csrf_token = generate_csrf_token();
?>
<link rel="stylesheet" href="assets/css/popup_danhsachcho.css">
<link rel="stylesheet" href="assets/css/pending_notifications.css">
<div id="pendingModal"
     class="modal-overlay"
     role="dialog"
     aria-labelledby="pendingTitle"
     aria-modal="true"
     data-csrf-field="<?php echo CSRF_TOKEN_NAME; ?>"
     data-csrf-token="<?php echo $popup_csrf_token; ?>">
    <div class="modal-backdrop" onclick="closePending()"></div>
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
            <h2 id="pendingTitle">Danh sách chờ</h2>
            <p class="modal-desc">Các lời mời đã gửi hoặc yêu cầu tham gia CLB</p>
            <button class="close-btn" onclick="closePending()" aria-label="Đóng">&times;</button>
        </div>

        <div class="modal-body">
            <div id="pending-list" class="pending-list">
                <div class="loading-state">
                    <svg class="spinner" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="3" fill="none"></circle>
                    </svg>
                    <p>Đang tải danh sách...</p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-close-modal" onclick="closePending()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Xong
            </button>
        </div>
    </div>
</div>