/**
 * Common Image Preview Utility
 * Sử dụng chung cho tất cả các form upload ảnh
 */

/**
 * Khởi tạo preview cho một input file
 * @param {string} inputId - ID của input file
 * @param {string} previewId - ID của element hiển thị preview (img hoặc div)
 * @param {object} options - Tùy chọn: { useFileReader: boolean, defaultSrc: string }
 */
function initImagePreview(inputId, previewId, options = {}) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (!input || !preview) {
        console.warn(`Image preview: Không tìm thấy input (${inputId}) hoặc preview (${previewId})`);
        return;
    }
    
    const useFileReader = options.useFileReader !== false; // Mặc định dùng FileReader
    const defaultSrc = options.defaultSrc || '';
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (!file) {
            if (defaultSrc && preview.tagName === 'IMG') {
                preview.src = defaultSrc;
            }
            return;
        }
        
        // Kiểm tra loại file
        if (!file.type.startsWith('image/')) {
            alert('Vui lòng chọn file ảnh!');
            input.value = '';
            return;
        }
        
        if (useFileReader) {
            // Sử dụng FileReader (tốt hơn cho preview lớn)
            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    preview.style.backgroundImage = `url(${e.target.result})`;
                }
            };
            reader.readAsDataURL(file);
        } else {
            // Sử dụng URL.createObjectURL (nhanh hơn nhưng cần cleanup)
            const objectUrl = URL.createObjectURL(file);
            if (preview.tagName === 'IMG') {
                preview.src = objectUrl;
                // Cleanup khi load xong
                preview.onload = function() {
                    URL.revokeObjectURL(objectUrl);
                };
            } else {
                preview.style.backgroundImage = `url(${objectUrl})`;
            }
        }
    });
}

// Auto-init khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Tự động khởi tạo cho các input có data-preview attribute
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
        const previewId = input.getAttribute('data-preview');
        initImagePreview(input.id || input.name, previewId);
    });
});

