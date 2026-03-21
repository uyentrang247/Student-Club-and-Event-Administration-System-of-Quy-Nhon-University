<?php
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
?>

<div id="createDeptModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-modal="true">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </div>
            <h3 id="modalTitle">Tạo phòng ban mới</h3>
            <p class="modal-subtitle">Tổ chức và phân công công việc hiệu quả</p>
            <button class="close-btn" onclick="closeModal()" aria-label="Đóng">&times;</button>
        </div>
        
        <form id="createDeptForm" action="process_taopb.php" method="POST" novalidate>
            <input type="hidden" name="club_id" value="<?= $club_id ?>">
            
            <div class="form-group">
                <label for="ten_phong_ban">
                    Tên phòng ban <span class="required" aria-label="bắt buộc">*</span>
                </label>
                <input type="text" 
                       id="ten_phong_ban" 
                       name="ten_phong_ban" 
                       placeholder="VD: Phòng Truyền thông, Phòng Sự kiện..." 
                       maxlength="100"
                       required
                       aria-required="true"
                       aria-describedby="ten_phong_ban_error">
                <span id="ten_phong_ban_error" class="error-message" role="alert"></span>
            </div>
            
            <div class="form-group">
                <label for="chuc_nang_nhiem_vu">
                    Chức năng nhiệm vụ <span class="required" aria-label="bắt buộc">*</span>
                </label>
                <textarea id="chuc_nang_nhiem_vu" 
                          name="chuc_nang_nhiem_vu" 
                          rows="4" 
                          placeholder="Mô tả chi tiết về chức năng, nhiệm vụ và trách nhiệm của phòng ban..."
                          maxlength="500"
                          required
                          aria-required="true"
                          aria-describedby="chuc_nang_error"></textarea>
                <span id="chuc_nang_error" class="error-message" role="alert"></span>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    <span>Hủy</span>
                </button>
                <button type="submit" class="btn-submit">
                    <span class="btn-text">Tạo phòng ban</span>
                    <span class="btn-loading" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="3" fill="none"></circle>
                        </svg>
                        Đang tạo...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="assets/css/popup_taopb.css">
