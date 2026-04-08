// ============================================
// POPUP JOIN - VALIDATION & LOGIC
// ============================================

document.addEventListener('DOMContentLoaded', function() {
  initJoinFormValidation();
});

function initJoinFormValidation() {
  const form = document.getElementById('formJoinClub');
  if (!form) return;
  
  const phoneInput = document.getElementById('so_dien_thoai');
  const messageInput = document.getElementById('loi_nhan');
  const agreeCheckbox = form.querySelector('input[name="agree_terms"]');
  
  // Phone validation
  if (phoneInput) {
    phoneInput.addEventListener('input', function() {
      // Chỉ cho phép số
      this.value = this.value.replace(/[^0-9]/g, '');
      
      if (this.classList.contains('error')) {
        validatePhone(this);
      }
    });
    
    phoneInput.addEventListener('blur', function() {
      validatePhone(this);
    });
  }
  
  // Message maxlength validation
  if (messageInput) {
    messageInput.addEventListener('input', function() {
      if (this.value.length > 300) {
        this.value = this.value.substring(0, 300);
      }
    });
  }
  
  // Form submit
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    let isValid = true;
    
    // Validate phone
    if (phoneInput && !validatePhone(phoneInput)) {
      isValid = false;
    }
    
    // Validate agreement
    if (agreeCheckbox && !agreeCheckbox.checked) {
      showMessage('Vui lòng đồng ý với nội quy và quy định của CLB', 'error');
      isValid = false;
    }
    
    if (isValid) {
      submitJoinForm(form);
    }
  });
}

function validatePhone(input) {
  const value = input.value.trim();
  const errorEl = document.getElementById('phone_error');
  
  if (!value) {
    input.classList.add('error');
    if (errorEl) errorEl.textContent = 'Vui lòng nhập số điện thoại';
    return false;
  }
  
  if (value.length < 10 || value.length > 11) {
    input.classList.add('error');
    if (errorEl) errorEl.textContent = 'Số điện thoại phải có 10-11 chữ số';
    return false;
  }
  
  if (!value.match(/^[0-9]{10,11}$/)) {
    input.classList.add('error');
    if (errorEl) errorEl.textContent = 'Số điện thoại không hợp lệ';
    return false;
  }
  
  input.classList.remove('error');
  if (errorEl) errorEl.textContent = '';
  return true;
}

function submitJoinForm(form) {
  const submitBtn = form.querySelector('.btn-submit-full');
  const btnText = submitBtn.querySelector('.btn-text');
  const btnLoading = submitBtn.querySelector('.btn-loading');
  
  // Show loading state
  submitBtn.disabled = true;
  btnText.style.display = 'none';
  btnLoading.style.display = 'flex';
  
  // Submit form
  const formData = new FormData(form);
  
  fetch(form.action, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
  })
  .then(response => {
    console.log('Response status:', response.status);
    console.log('Response ok:', response.ok);
    
    // Kiểm tra nếu response không ok
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.status);
    }
    
    const contentType = response.headers.get('content-type');
    console.log('Content-Type:', contentType);
    
    if (contentType && contentType.includes('application/json')) {
      return response.json().then(data => {
        console.log('JSON Response:', data);
        return data;
      }).catch(err => {
        console.error('Error parsing JSON:', err);
        return { success: false, message: 'Lỗi khi xử lý phản hồi từ server' };
      });
    }
    return response.text().then(text => {
      console.log('Text Response:', text);
      return text;
    }).catch(err => {
      console.error('Error reading text:', err);
      return 'error';
    });
  })
  .then(data => {
    console.log('Processing data:', data);
    
    // Handle JSON response
    if (typeof data === 'object' && data !== null) {
      if (data.success) {
        // Hiển thị thông báo thành công trước
        showMessage(data.message || 'Đã gửi yêu cầu tham gia CLB thành công!', 'success');
        // Sau đó hiển thị màn hình thành công
        setTimeout(() => {
          showSuccessScreen();
        }, 1000);
      } else {
        // Hiển thị thông báo lỗi
        showMessage(data.message || 'Có lỗi xảy ra. Vui lòng thử lại!', 'error');
        submitBtn.disabled = false;
        btnText.style.display = 'flex';
        btnLoading.style.display = 'none';
      }
    } else {
      // Handle text response (fallback)
      if (typeof data === 'string' && (data.includes('error') || data.includes('lỗi') || data.includes('Error'))) {
        showMessage('Có lỗi xảy ra. Vui lòng thử lại!', 'error');
        submitBtn.disabled = false;
        btnText.style.display = 'flex';
        btnLoading.style.display = 'none';
      } else {
        // Nếu không phải lỗi, coi như thành công
        showMessage('Đã gửi yêu cầu tham gia CLB thành công!', 'success');
        setTimeout(() => {
          showSuccessScreen();
        }, 1000);
      }
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showMessage('Có lỗi xảy ra khi kết nối đến server. Vui lòng thử lại!', 'error');
    
    // Reset button
    submitBtn.disabled = false;
    btnText.style.display = 'flex';
    btnLoading.style.display = 'none';
  });
}

function showSuccessScreen() {
  const modalContent = document.querySelector('#joinClubModal .modal-content');
  if (!modalContent) return;
  
  modalContent.innerHTML = `
    <div style="padding: 60px 40px; text-align: center;">
      <div style="width: 80px; height: 80px; margin: 0 auto 24px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3); animation: scaleIn 0.3s ease-out;">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      
      <h2 style="font-size: 28px; font-weight: 700; color: #111827; margin: 0 0 12px 0; animation: fadeInUp 0.4s ease-out 0.1s backwards;">
        ✅ Gửi yêu cầu thành công!
      </h2>
      
      <p style="font-size: 16px; color: #6B7280; line-height: 1.6; margin: 0 0 32px 0; animation: fadeInUp 0.4s ease-out 0.2s backwards;">
        Yêu cầu tham gia CLB của bạn đã được gửi đi thành công.<br>
        Vui lòng chờ quản trị viên CLB duyệt yêu cầu của bạn.<br>
        <strong style="color: #10b981;">Bạn sẽ nhận được thông báo khi có phản hồi.</strong>
      </p>
      
      <button onclick="closeJoinModalAndReload()" style="padding: 14px 32px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); transition: all 0.3s; animation: fadeInUp 0.4s ease-out 0.3s backwards;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(16, 185, 129, 0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.4)'">
        Đóng
      </button>
    </div>
    <style>
      @keyframes scaleIn {
        from { transform: scale(0); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
      }
      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }
    </style>
  `;
}

function closeJoinModalAndReload() {
  closeJoinModal();
  setTimeout(() => {
    location.reload();
  }, 300);
}

function showMessage(message, type) {
  console.log('showMessage called:', message, type);
  
  // Đảm bảo toast styles đã được inject
  if (!document.getElementById('toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
      .toast-message {
        position: fixed;
        top: 100px;
        left: 50%;
        transform: translateX(-50%);
        padding: 14px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        color: white;
        z-index: 100002 !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        animation: slideDown 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 90%;
        min-width: 300px;
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
  
  // Remove existing message
  const existing = document.querySelector('.toast-message');
  if (existing) existing.remove();
  
  // Create new message
  const toast = document.createElement('div');
  toast.className = `toast-message toast-${type}`;
  toast.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink: 0;">
      ${type === 'success' 
        ? '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
        : '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>'
      }
    </svg>
    <span style="flex: 1; word-wrap: break-word;">${message}</span>
  `;
  
  document.body.appendChild(toast);
  console.log('Toast added to DOM:', toast);
  
  // Auto remove after 4s (tăng thời gian để người dùng đọc được)
  setTimeout(() => {
    toast.style.animation = 'slideUp 0.3s ease-out reverse';
    setTimeout(() => toast.remove(), 300);
  }, type === 'success' ? 4000 : 5000); // Thông báo thành công hiển thị 4s, lỗi 5s
}

