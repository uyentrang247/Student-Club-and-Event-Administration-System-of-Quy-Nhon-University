<?php
session_start();
require 'site.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require('assets/database/connect.php');
$user_id = $_SESSION['user_id'];
$club_id = $_GET['id'] ?? 0;

// Lấy thông tin CLB
$sql = "SELECT * FROM clubs WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

if (!$club) {
    header("Location: DanhsachCLB.php");
    exit();
}

// Kiểm tra user có phải ban quản lý không (chỉ đội trưởng hoặc admin mới upload được)
$is_admin = can_manage_club($conn, $user_id, $club_id);

// Kiểm tra chế độ xem: view (chỉ xem) hoặc manage (quản lý)
$mode = $_GET['mode'] ?? 'view';

// Nếu không phải admin thì chỉ cho xem
if (!$is_admin) {
    $mode = 'view';
}

// Lấy tất cả ảnh (join media_library để lấy file_path)
$sql = "SELECT cg.*, ml.file_path AS image_url
        FROM club_gallery cg
        LEFT JOIN media_library ml ON cg.media_id = ml.id
        WHERE cg.club_id = ?
        ORDER BY cg.uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $club_id);
$stmt->execute();
$gallery = $stmt->get_result();

$page_css = "club-gallery.css";
load_top();
load_header();
?>

<div class="gallery-container">
    <!-- Thông báo -->
    <?php if (isset($_SESSION['flash_message'])): 
        $flash_type = $_SESSION['flash_type'] ?? 'info';
        $class = $flash_type === 'success' ? 'alert-success' : ($flash_type === 'error' || $flash_type === 'danger' ? 'alert-error' : 'alert-info');
    ?>
        <div class="alert <?= $class ?>">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            ✗ <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="gallery-header">
        <div class="header-left">
            <?php if ($mode === 'manage'): ?>
                <a href="Dashboard.php?id=<?= $club_id ?>" class="back-btn">← Quay lại Dashboard</a>
            <?php else: ?>
                <a href="club-detail.php?id=<?= $club_id ?>" class="back-btn">← Quay lại</a>
            <?php endif; ?>
            <h1>📸 Thư viện ảnh <?= $mode === 'manage' ? '- Quản lý' : '' ?></h1>
            <p class="club-name"><?= htmlspecialchars($club['ten_clb']) ?></p>
        </div>
        <?php if ($is_admin && $mode === 'manage'): ?>
        <button class="btn-upload" onclick="openUploadModal()">
            <span>+</span> Thêm ảnh
        </button>
        <?php endif; ?>
    </div>

    <?php if ($gallery->num_rows > 0): ?>
    <div class="gallery-grid">
        <?php while ($photo = $gallery->fetch_assoc()): 
            $upload_date = new DateTime($photo['uploaded_at']);
        ?>
        <?php 
            $img_src = $photo['image_url'] ?? '';
            if (empty($img_src)) {
                $img_src = 'assets/img/default-club.png';
            }
        ?>
        <div class="gallery-item" id="gallery-item-<?= $photo['id'] ?>" data-id="<?= $photo['id'] ?>" onclick="openLightbox(<?= $photo['id'] ?>)">
            <img src="<?= htmlspecialchars($img_src) ?>" 
                 alt="<?= htmlspecialchars($photo['title'] ?? 'Ảnh CLB') ?>"
                 loading="lazy">
            <div class="item-overlay">
                <h3><?= htmlspecialchars($photo['title'] ?? 'Ảnh CLB') ?></h3>
                <p><?= htmlspecialchars($photo['description'] ?? '') ?></p>
                <div class="item-meta">
                    <span>📅 <?= $upload_date->format('d/m/Y') ?></span>
                </div>
                <?php if ($is_admin && $mode === 'manage'): ?>
                <button class="btn-delete-thumb" onclick="event.stopPropagation(); deletePhotoById(<?= $photo['id'] ?>);">🗑️</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-gallery">
        <div class="empty-icon">📷</div>
        <h2>Chưa có ảnh nào</h2>
        <p><?= $mode === 'manage' ? 'Hãy thêm ảnh đầu tiên cho CLB' : 'CLB chưa có ảnh nào' ?></p>
        <?php if ($is_admin && $mode === 'manage'): ?>
        <button class="btn-upload-primary" onclick="openUploadModal()">
            + Thêm ảnh đầu tiên
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<?php if ($is_admin && $mode === 'manage'): ?>
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📤 Thêm ảnh mới</h2>
            <span class="close" onclick="closeUploadModal()">&times;</span>
        </div>
        <form action="upload-gallery.php" method="POST" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="club_id" value="<?= $club_id ?>">
            
            <div class="upload-area" id="uploadArea">
                <input type="file" name="images[]" id="imageInput" accept="image/*" multiple required style="display:none">
                <div class="upload-placeholder">
                    <div class="upload-icon">📷</div>
                    <p>Kéo thả ảnh vào đây hoặc click để chọn</p>
                    <p class="hint">Hỗ trợ nhiều ảnh cùng lúc (JPG, PNG, GIF)</p>
                </div>
                <div id="previewContainer" class="preview-container"></div>
            </div>

            <div class="form-group">
                <label>Tiêu đề (tùy chọn)</label>
                <input type="text" name="title" placeholder="VD: Sự kiện Workshop 2024">
            </div>

            <div class="form-group">
                <label>Mô tả (tùy chọn)</label>
                <textarea name="description" rows="3" placeholder="Mô tả về bức ảnh..."></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeUploadModal()">Hủy</button>
                <button type="submit" class="btn-submit">Tải lên</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-content">
        <img id="lightboxImage" src="" alt="">
        <div class="lightbox-info">
            <h3 id="lightboxTitle"></h3>
            <p id="lightboxDescription"></p>
            <div class="lightbox-meta">
                <span id="lightboxUploader"></span>
                <span id="lightboxDate"></span>
            </div>
            <?php if ($is_admin && $mode === 'manage'): ?>
            <button class="btn-delete" onclick="deletePhoto(currentImageIndex)" id="lightboxDeleteBtn">🗑️ Xóa ảnh</button>
            <?php endif; ?>
        </div>
    </div>
    <button class="lightbox-prev" onclick="prevImage()">❮</button>
    <button class="lightbox-next" onclick="nextImage()">❯</button>
</div>

<script>
  const CSRF_FIELD = '<?php echo CSRF_TOKEN_NAME; ?>';
  const CSRF_TOKEN = '<?php echo generate_csrf_token(); ?>';
  const CAN_MANAGE_GALLERY = <?php echo ($is_admin && $mode === 'manage') ? 'true' : 'false'; ?>;
  const CLUB_ID = <?php echo (int)$club_id; ?>;
</script>
<script src="assets/js/club-gallery.js"></script>

<?php
load_footer();
?>
