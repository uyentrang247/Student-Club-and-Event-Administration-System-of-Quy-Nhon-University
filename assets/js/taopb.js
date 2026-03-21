// ============================================
// POPUP TAOPB - ENHANCED LOGIC
// ============================================

function openModal() {
  const modal = document.getElementById('createDeptModal');
  if (modal) {
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Khởi tạo form validation ngay lập tức
    initFormValidation();
    
    // Focus vào input đầu tiên
    setTimeout(() => {
      const firstInput = modal.querySelector('input[type="text"]');
      if (firstInput) firstInput.focus();
      
      // Đảm bảo counter được cập nhật sau khi modal hiển thị
      const chucNang = document.getElementById('chuc_nang_nhiem_vu');
      const charCount = document.getElementById('char_count');
      if (chucNang && charCount) {
        charCount.textContent = chucNang.value.length;
      }
    }, 100);
  }
}

function closeModal() {
  const modal = document.getElementById('createDeptModal');
  if (modal) {
    modal.classList.remove('show');
    document.body.style.overflow = '';
    
    // Reset form
    const form = document.getElementById('createDeptForm');
    if (form) {
      form.reset();
      clearErrors();
    }
  }
}

// Form Validation
function initFormValidation() {
  const form = document.getElementById('createDeptForm');
  if (!form) return;
  
  const tenPhongBan = document.getElementById('ten_phong_ban');
  const chucNang = document.getElementById('chuc_nang_nhiem_vu');
  
  // Giới hạn độ dài textarea
  if (chucNang) {
    chucNang.addEventListener('input', function() {
      if (this.value.length > 500) {
        this.value = this.value.substring(0, 500);
      }
    });
  }
  
  // Real-time validation
  if (tenPhongBan) {
    tenPhongBan.addEventListener('blur', function() {
      validateField(this, 'Vui lòng nhập tên phòng ban');
    });
    
    tenPhongBan.addEventListener('input', function() {
      if (this.classList.contains('error')) {
        validateField(this, 'Vui lòng nhập tên phòng ban');
      }
    });
  }
  
  if (chucNang) {
    chucNang.addEventListener('blur', function() {
      validateField(this, 'Vui lòng mô tả chức năng nhiệm vụ');
    });
    
    chucNang.addEventListener('input', function() {
      if (this.classList.contains('error')) {
        validateField(this, 'Vui lòng mô tả chức năng nhiệm vụ');
      }
    });
  }
  
  // Form submit
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate all fields
    let isValid = true;
    
    if (!validateField(tenPhongBan, 'Vui lòng nhập tên phòng ban')) {
      isValid = false;
    }
    
    if (!validateField(chucNang, 'Vui lòng mô tả chức năng nhiệm vụ')) {
      isValid = false;
    }
    
    if (isValid) {
      submitForm(form);
    }
  });
}

function validateField(field, errorMsg) {
  if (!field) return true;
  
  const value = field.value.trim();
  const errorEl = document.getElementById(field.id + '_error');
  
  if (!value) {
    field.classList.add('error');
    if (errorEl) errorEl.textContent = errorMsg;
    return false;
  } else {
    field.classList.remove('error');
    if (errorEl) errorEl.textContent = '';
    return true;
  }
}

function clearErrors() {
  const errorMessages = document.querySelectorAll('.error-message');
  errorMessages.forEach(el => el.textContent = '');
  
  const errorFields = document.querySelectorAll('.error');
  errorFields.forEach(el => el.classList.remove('error'));
}

function submitForm(form) {
  const submitBtn = form.querySelector('.btn-submit');
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
    body: formData
  })
  .then(response => response.text())
  .then(data => {
    // Success
    showMessage('Tạo phòng ban thành công!', 'success');
    
    setTimeout(() => {
      closeModal();
      location.reload();
    }, 1500);
  })
  .catch(error => {
    // Error
    console.error('Error:', error);
    showMessage('Có lỗi xảy ra. Vui lòng thử lại!', 'error');
    
    // Reset button
    submitBtn.disabled = false;
    btnText.style.display = 'block';
    btnLoading.style.display = 'none';
  });
}

function showMessage(message, type) {
  // Remove existing message
  const existing = document.querySelector('.msg-box');
  if (existing) existing.remove();
  
  // Create new message
  const msgBox = document.createElement('div');
  msgBox.className = `msg-box ${type}`;
  msgBox.innerHTML = `
    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
      ${type === 'success' 
        ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
        : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
      }
    </svg>
    <span>${message}</span>
  `;
  
  document.body.appendChild(msgBox);
  
  // Auto remove after 3s
  setTimeout(() => {
    msgBox.style.animation = 'slideUp 0.3s ease-out reverse';
    setTimeout(() => msgBox.remove(), 300);
  }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  const modal = document.getElementById('createDeptModal');
  if (modal && modal.classList.contains('show')) {
    if (e.key === 'Escape') {
      closeModal();
    }
  }
});