<link rel="stylesheet" href="assets/css/popup_add_member.css">
<div id="popup_add_member" class="popup-overlay" role="dialog" aria-labelledby="addMemberTitle" aria-modal="true">
    <div class="modal-backdrop" onclick="closeAddMemberPopup()"></div>
    <div class="popup-box">
        <div class="modal-header">
            <div class="modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h2 id="addMemberTitle">Thêm thành viên</h2>
            <p class="modal-subtitle">Vào phòng ban <strong><?= htmlspecialchars($department['name'] ?? '') ?></strong></p>
            <button class="close-btn" onclick="closeAddMemberPopup()" aria-label="Đóng">&times;</button>
        </div>

        <div class="modal-body">
            <div class="search-box">
                <div class="search-input-wrapper">
                    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" 
                           id="searchMember" 
                           placeholder="Tìm kiếm theo tên hoặc email..."
                           autocomplete="off"
                           aria-label="Tìm kiếm thành viên"
                           aria-describedby="searchResultMember">
                    <button class="refresh-btn" onclick="refreshSearch()" aria-label="Làm mới">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10"></polyline>
                            <polyline points="1 20 1 14 7 14"></polyline>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                        </svg>
                    </button>
                </div>

                <div id="searchResultMember" class="search-result" role="listbox"></div>
            </div>

            <!-- Preview khi chọn -->
            <div id="memberPreview" class="member-preview hidden">
                <div class="preview-card">
                    <div class="preview-avatar">
                        <span id="previewInitial"></span>
                    </div>
                    <div class="preview-info">
                        <h4 id="previewName"></h4>
                        <p id="previewEmail"></p>
                    </div>
                    <button class="remove-selection" onclick="clearSelection()" aria-label="Xóa lựa chọn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <input type="hidden" id="selectedUser">
            <input type="hidden" id="selectedUserName">
            <input type="hidden" id="selectedUserEmail">
            <input type="hidden" id="department_id_input">
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeAddMemberPopup()">
                <span>Hủy</span>
            </button>
            <button class="btn-confirm" onclick="addMember()" disabled>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span class="btn-text">Thêm thành viên</span>
                <span class="btn-loading" style="display: none;">
                    <svg class="spinner" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="3" fill="none"></circle>
                    </svg>
                    Đang thêm...
                </span>
            </button>
        </div>
    </div>
</div>