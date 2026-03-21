// ============================================
// POPUP ADD MEMBER - ENHANCED LOGIC
// ============================================

(function () {
    const SEARCH_URL = "search_user.php";
    let debounceTimer = null;

    function qs(id) { return document.getElementById(id); }

    function renderList(resultEl, list) {
        if (!list || list.length === 0) {
            resultEl.innerHTML = `
                <div class="item" style="text-align: center; padding: 20px; color: #9CA3AF;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin: 0 auto 8px; opacity: 0.3;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <p style="margin: 0;">Không tìm thấy thành viên</p>
                </div>
            `;
            resultEl.style.display = "block";
            return;
        }

        resultEl.innerHTML = list.map(u => `
            <div class="item" 
                 data-id="${u.id}" 
                 data-name="${u.ho_ten}"
                 data-email="${u.email || ''}"
                 role="option">
                <div class="user-item">
                    <div class="user-name">${u.ho_ten}</div>
                    <div class="user-email">${u.email || 'Chưa có email'}</div>
                </div>
            </div>
        `).join("");

        resultEl.style.display = "block";

        // Add event listeners
        resultEl.querySelectorAll(".item[data-id]").forEach(item => {
            item.addEventListener("click", () => {
                selectMember(
                    item.dataset.id,
                    item.dataset.name,
                    item.dataset.email
                );
                resultEl.style.display = "none";
            });
        });
    }

    function selectMember(id, name, email) {
        // Set hidden inputs
        qs("selectedUser").value = id;
        qs("selectedUserName").value = name;
        qs("selectedUserEmail").value = email;

        // Update preview
        const preview = qs("memberPreview");
        const previewName = qs("previewName");
        const previewEmail = qs("previewEmail");
        const previewInitial = qs("previewInitial");

        if (preview && previewName && previewEmail && previewInitial) {
            previewName.textContent = name;
            previewEmail.textContent = email || 'Chưa có email';
            previewInitial.textContent = name.charAt(0).toUpperCase();
            preview.classList.remove("hidden");
        }

        // Enable confirm button
        const confirmBtn = document.querySelector(".btn-confirm");
        if (confirmBtn) {
            confirmBtn.disabled = false;
        }

        // Clear search input
        const searchInput = qs("searchMember");
        if (searchInput) {
            searchInput.value = '';
        }
    }

    function searchUser(keyword, resultEl) {
        resultEl.style.display = "block";
        resultEl.innerHTML = `
            <div class="item" style="text-align: center; padding: 20px;">
                <svg class="spinner" viewBox="0 0 24 24" style="width: 32px; height: 32px; margin: 0 auto; animation: spin 0.8s linear infinite;">
                    <circle cx="12" cy="12" r="10" stroke-width="3" fill="none" stroke="#3b82f6" stroke-dasharray="50" stroke-dashoffset="25" stroke-linecap="round"></circle>
                </svg>
                <p style="margin: 8px 0 0 0; color: #6B7280;">Đang tìm kiếm...</p>
            </div>
        `;

        fetch(`${SEARCH_URL}?keyword=${encodeURIComponent(keyword)}`)
            .then(r => r.json())
            .then(list => renderList(resultEl, list))
            .catch(() => {
                resultEl.innerHTML = `
                    <div class="item" style="text-align: center; padding: 20px; color: #dc2626;">
                        <p style="margin: 0;">Lỗi kết nối. Vui lòng thử lại!</p>
                    </div>
                `;
            });
    }

    function initSearchBox() {
        const input = qs("searchMember");
        const result = qs("searchResultMember");

        if (!input || !result) return;

        // Focus event - load all members
        input.addEventListener("focus", () => {
            if (!input.value.trim()) {
                searchUser("", result);
            }
        });

        // Input event - debounced search
        input.addEventListener("input", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                searchUser(input.value.trim(), result);
            }, 300);
        });

        // Click outside to close
        document.addEventListener("click", (e) => {
            if (!e.target.closest(".search-box")) {
                result.style.display = "none";
            }
        });
    }

    window.initAddMemberSearch = initSearchBox;
})();

// Open popup
function openAddMemberPopup(pb_id) {
    const popup = document.getElementById("popup_add_member");
    if (!popup) return;

    popup.classList.add("show");
    document.body.style.overflow = 'hidden';
    
    document.getElementById("pb_id_input").value = pb_id;
    
    // Reset form
    clearSelection();
    
    // Initialize search
    setTimeout(() => {
        initAddMemberSearch();
        const searchInput = document.getElementById("searchMember");
        if (searchInput) searchInput.focus();
    }, 100);
}

// Close popup
function closeAddMemberPopup() {
    const popup = document.getElementById("popup_add_member");
    if (!popup) return;

    popup.classList.remove("show");
    document.body.style.overflow = '';
    
    // Reset after animation
    setTimeout(() => {
        clearSelection();
        const searchResult = document.getElementById("searchResultMember");
        if (searchResult) searchResult.innerHTML = '';
    }, 300);
}

// Clear selection
function clearSelection() {
    document.getElementById("selectedUser").value = '';
    document.getElementById("selectedUserName").value = '';
    document.getElementById("selectedUserEmail").value = '';
    
    const preview = document.getElementById("memberPreview");
    if (preview) preview.classList.add("hidden");
    
    const confirmBtn = document.querySelector(".btn-confirm");
    if (confirmBtn) confirmBtn.disabled = true;
    
    const searchInput = document.getElementById("searchMember");
    if (searchInput) searchInput.value = '';
}

// Refresh search
function refreshSearch() {
    const searchInput = document.getElementById("searchMember");
    const searchResult = document.getElementById("searchResultMember");
    
    if (searchInput && searchResult) {
        searchInput.value = '';
        searchUser("", searchResult);
        searchInput.focus();
    }
}

// Add member
function addMember() {
    const user_id = document.getElementById("selectedUser").value;
    const pb_id = document.getElementById("pb_id_input").value;
    const userName = document.getElementById("selectedUserName").value;

    if (!user_id) {
        showToast("Vui lòng chọn thành viên!", "error");
        return;
    }

    const confirmBtn = document.querySelector(".btn-confirm");
    const btnText = confirmBtn.querySelector(".btn-text");
    const btnLoading = confirmBtn.querySelector(".btn-loading");

    // Show loading
    confirmBtn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = 'flex';

    fetch("add_member_department.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `user_id=${user_id}&pb_id=${pb_id}`
    })
    .then(r => r.text())
    .then(msg => {
        showToast(`Đã thêm ${userName} vào phòng ban!`, "success");
        
        setTimeout(() => {
            closeAddMemberPopup();
            location.reload();
        }, 1500);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast("Có lỗi xảy ra. Vui lòng thử lại!", "error");
        
        // Reset button
        confirmBtn.disabled = false;
        btnText.style.display = 'block';
        btnLoading.style.display = 'none';
    });
}

// Toast notification
function showToast(message, type) {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            ${type === 'success' 
                ? '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>'
            }
        </svg>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideUp 0.3s ease-out reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Inject toast styles
if (!document.getElementById('toast-notification-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-notification-styles';
    style.textContent = `
        .toast-notification {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            z-index: 999999;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toast-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .toast-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        @keyframes slideDown {
            from { 
                opacity: 0;
                transform: translate(-50%, -20px);
            }
            to { 
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
    `;
    document.head.appendChild(style);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    const popup = document.getElementById('popup_add_member');
    if (popup && popup.classList.contains('show')) {
        if (e.key === 'Escape') {
            closeAddMemberPopup();
        }
    }
});