<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/assets/database/connect.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

require 'site.php';
load_top();
load_header();

// Lấy ID CLB
$club_id = get_club_id();

if ($club_id <= 0) {
    redirect('myclub.php', 'Không tìm thấy câu lạc bộ. Vui lòng chọn CLB từ danh sách.', 'error');
}

$user_id = $_SESSION['user_id'];

// Kiểm tra quyền quản lý CLB
require_once __DIR__ . '/includes/constants.php';
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect('myclub.php', 'Bạn không có quyền tạo sự kiện cho câu lạc bộ này!', 'error');
}
?>

<link rel="stylesheet" href="assets/css/add_sk.css">
<div class = "contain">
<div class="contain-add-sk">
    <h1>Tạo sự kiện cho Câu Lạc Bộ của bạn</h1>
    
<?php $flash = get_flash_message(); ?>
<?php if ($flash): ?>
        <div style="background: <?= $flash['type'] === 'error' ? '#fee' : '#efe'; ?>; border: 1px solid <?= $flash['type'] === 'error' ? '#fcc' : '#cfc'; ?>; padding: 15px; margin: 15px 0; border-radius: 8px; color: <?= $flash['type'] === 'error' ? '#c33' : '#3c3'; ?>;">
            <strong><?= $flash['type'] === 'error' ? '⚠️ Lỗi:' : '✓ Thành công:' ?></strong><br>
            <?= $flash['message']; ?>
        </div>
<?php endif; ?>
  
<form action="add_Sukien_xuli.php" method="POST" enctype="multipart/form-data" class="form-sk">
    
    <div class="form-group">
        <label class="name-event">Tên sự kiện</label>
        <input type="text" name="ten_su_kien" required>
    </div>

    <div class="form-group mo-ta-sk">
        <h3 class="section-title">📝 Giới thiệu / mô tả</h3>
        <label>Mô tả sự kiện</label>
        <textarea name="mo_ta" rows="5" required></textarea>
    </div>

    <div class="form-group nd-chitiet">
        <h3 class="section-title">📌 Nội dung chi tiết</h3>
        <label>Mô tả nội dung chi tiết của sự kiện</label>
        <textarea name="noi_dung_chi_tiet" rows="6" required></textarea>
    </div>

    <div class="form-group upload-container">
        <label>Ảnh bìa tạo sự thu hút cho sự kiện</label>
        <img src="" class="anh_bia" id="preview" alt="Preview ảnh bìa">

        <label class="upload-anhbia">
            Chọn ảnh mới
            <input type="file" name="anhbia" id="anhbia" accept="image/*" style="display:none" required>
        </label>
    </div>

    <div class="form-group">
        <label>Địa điểm tổ chức</label>
        <input type="text" name="dia_diem" required>
    </div>

    <div class="two-col">
        <div class="form-group">
            <label>Thời gian bắt đầu</label>
            <input type="datetime-local" name="tg_bat_dau" required>
        </div>

        <div class="form-group">
            <label>Thời gian kết thúc</label>
            <input type="datetime-local" name="tg_ket_thuc" required>
        </div>
    </div>

    <div class="two-col">
        <div class="form-group">
            <label>Số lượng tối đa</label>
            <input type="number" name="so_luong" min="1" required>
        </div>

        <div class="form-group">
            <label>Hạn đăng ký</label>
            <input type="datetime-local" name="han_dang_ky" required>

        </div>
    </div>

      <div class="form-group"> 
        <input type="hidden" name="user_id"   value="<?= htmlspecialchars($user_id) ?>">
    </div>  

    <input type="hidden" name="club_id" value="<?= htmlspecialchars($club_id) ?>">
    <div class="button-group">
    <button type="submit" class="btn-submit">Tạo sự kiện</button>
    <button type="button" class="btn btn-back" 
            onclick="window.location.href='Dashboard.php?id=<?= $club_id ?>'">
        Quay lại
    </button>
    </div>
</form>
</div>
</div>

<script src="assets/js/image-preview.js"></script>
<script>
// JS preview ảnh bìa
initImagePreview('anhbia', 'preview', { useFileReader: false });
</script>

</body>
</html>
 