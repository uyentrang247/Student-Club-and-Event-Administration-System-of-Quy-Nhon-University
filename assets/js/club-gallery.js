// Gallery data for lightbox
let galleryData = [];
let currentImageIndex = 0;

// Load gallery data from DOM
document.addEventListener('DOMContentLoaded', function() {
    buildGalleryData();
});

function getToastContainer() {
    let c = document.getElementById('toast-container');
    if (!c) {
        c = document.createElement('div');
        c.id = 'toast-container';
        c.style.position = 'fixed';
        c.style.top = '16px';
        c.style.right = '16px';
        c.style.zIndex = '9999';
        c.style.display = 'flex';
        c.style.flexDirection = 'column';
        c.style.gap = '8px';
        document.body.appendChild(c);
    }
    return c;
}

function showToast(message, type = 'info') {
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.padding = '12px 14px';
    toast.style.borderRadius = '6px';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
    toast.style.color = '#fff';
    toast.style.fontSize = '14px';
    toast.style.maxWidth = '320px';
    toast.style.wordBreak = 'break-word';
    const colors = {
        success: '#2e7d32',
        error: '#c62828',
        info: '#1565c0',
        warning: '#ef6c00'
    };
    toast.style.background = colors[type] || colors.info;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 2400);
}

function buildGalleryData() {
    galleryData = [];
    const items = document.querySelectorAll('.gallery-item');
    items.forEach((item, index) => {
        const img = item.querySelector('img');
        const overlay = item.querySelector('.item-overlay');
        const dataId = item.getAttribute('data-id');
        galleryData.push({
            id: dataId ? parseInt(dataId, 10) : index,
            domId: item.id,
            src: img.src,
            title: overlay?.querySelector('h3')?.textContent || 'Ảnh CLB',
            description: overlay?.querySelector('p')?.textContent || '',
            uploader: overlay?.querySelector('.item-meta span:first-child')?.textContent || '',
            date: overlay?.querySelector('.item-meta span:last-child')?.textContent || ''
        });
    });
}

// Upload Modal
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('active');
    document.body.style.overflow = 'auto';
    document.getElementById('uploadForm').reset();
    document.getElementById('previewContainer').innerHTML = '';
}

// File upload handling
const uploadArea = document.getElementById('uploadArea');
const imageInput = document.getElementById('imageInput');
const previewContainer = document.getElementById('previewContainer');

if (uploadArea && imageInput) {
    uploadArea.addEventListener('click', () => imageInput.click());
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        imageInput.files = files;
        handleFiles(files);
    });
    
    imageInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

function handleFiles(files) {
    previewContainer.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.className = 'preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="preview-remove" onclick="removePreview(${index})">×</button>
                `;
                previewContainer.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
        }
    });
    
    // Hide placeholder if files selected
    if (files.length > 0) {
        document.querySelector('.upload-placeholder').style.display = 'none';
    }
}

function removePreview(index) {
    // This is simplified - in production you'd need to handle file removal properly
    const previews = previewContainer.querySelectorAll('.preview-item');
    if (previews[index]) {
        previews[index].remove();
    }
    
    if (previewContainer.children.length === 0) {
        document.querySelector('.upload-placeholder').style.display = 'block';
    }
}

// Lightbox
function getIndexById(id) {
    return galleryData.findIndex(g => g.id === id);
}

function openLightbox(imageIdx) {
    // imageIdx có thể là id (DB) hoặc index; ưu tiên tìm theo id
    let idx = imageIdx;
    if (idx >= galleryData.length || idx < 0) {
        const found = getIndexById(imageIdx);
        if (found >= 0) idx = found;
    }
    currentImageIndex = idx;
    updateLightbox();
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function updateLightbox() {
    if (galleryData.length === 0) return;
    currentImageIndex = Math.max(0, Math.min(currentImageIndex, galleryData.length - 1));
    const data = galleryData[currentImageIndex];
    document.getElementById('lightboxImage').src = data.src;
    document.getElementById('lightboxTitle').textContent = data.title;
    document.getElementById('lightboxDescription').textContent = data.description;
    document.getElementById('lightboxUploader').textContent = data.uploader;
    document.getElementById('lightboxDate').textContent = data.date;
    const delBtn = document.getElementById('lightboxDeleteBtn');
    if (delBtn) {
        delBtn.dataset.index = currentImageIndex;
    }
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + galleryData.length) % galleryData.length;
    updateLightbox();
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % galleryData.length;
    updateLightbox();
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    const lightbox = document.getElementById('lightbox');
    if (lightbox && lightbox.classList.contains('active')) {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') prevImage();
        if (e.key === 'ArrowRight') nextImage();
    }
    
    const modal = document.getElementById('uploadModal');
    if (modal && modal.classList.contains('active')) {
        if (e.key === 'Escape') closeUploadModal();
    }
});

// Close modal on outside click
document.getElementById('uploadModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'uploadModal') {
        closeUploadModal();
    }
});

document.getElementById('lightbox')?.addEventListener('click', (e) => {
    if (e.target.id === 'lightbox') {
        closeLightbox();
    }
});

// Delete photo (manage mode only)
function deletePhoto(index) {
    if (!CAN_MANAGE_GALLERY) return;
    if (!confirm('Xóa ảnh này?')) return;
    const data = galleryData[index];
    if (!data) return;
    deletePhotoRequest(data.id, index);
}

function deletePhotoById(id) {
    if (!CAN_MANAGE_GALLERY) return;
    if (!confirm('Xóa ảnh này?')) return;
    const index = galleryData.findIndex(g => g.id === id);
    deletePhotoRequest(id, index);
}

function deletePhotoRequest(id, index) {
    if (id == null || id < 0) return;
    fetch('delete-gallery.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            id,
            club_id: CLUB_ID,
            [CSRF_FIELD]: CSRF_TOKEN
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message || 'Đã xóa ảnh', 'success');
            const el = document.querySelector(`[data-id="${id}"]`);
            if (el) el.remove();
            buildGalleryData();
            if (galleryData.length === 0) {
                closeLightbox();
                location.reload();
                return;
            }
            if (index < 0) {
                currentImageIndex = 0;
            } else {
                currentImageIndex = Math.min(index, galleryData.length - 1);
            }
            updateLightbox();
        } else {
            showToast(res.message || 'Không thể xóa ảnh', 'error');
        }
    })
    .catch(() => {
        showToast('Lỗi kết nối, không thể xóa ảnh', 'error');
    });
}
