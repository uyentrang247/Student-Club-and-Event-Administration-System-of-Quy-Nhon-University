<?php
// Load dependencies FIRST
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// NOW start session
session_start();

// Kiểm tra đăng nhập
require_login();

require 'site.php'; 
require_once 'assets/database/connect.php';

$user_id = $_SESSION['user_id'];

// === 2. Lấy club_id an toàn ===
$club_id = get_club_id();

// === 3. Nếu vẫn không có club_id → chuyển hướng ===
if ($club_id <= 0) {
    $_SESSION['error'] = "Không tìm thấy câu lạc bộ. Vui lòng chọn CLB từ danh sách.";
    header("Location: myclub.php");
    exit;
}

// === 4. Kiểm tra quyền quản lý CLB ===
require_once __DIR__ . '/includes/constants.php';
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect('myclub.php', 'Bạn không có quyền quản lý câu lạc bộ này!', 'error');
}

// === 5. Lấy thông tin CLB từ database ===
$club_info = null;
$stmt = $conn->prepare("SELECT name, description, color FROM clubs WHERE id = ?");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $club_info = $result->fetch_assoc();
}
$stmt->close();

// Lấy thông tin trang đại diện (slogan, banner, etc.)
$club_page = null;
$table_check = $conn->query("SHOW TABLES LIKE 'pages'");
if ($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare("SELECT p.slogan, logo.path AS logo_path FROM pages p LEFT JOIN media logo ON p.logo_id = logo.id WHERE p.club_id = ?");
    $stmt->bind_param("i", $club_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $club_page = $result->fetch_assoc();
    }
    $stmt->close();
}

load_top();
load_header();
?>

<link rel="stylesheet" href="assets/css/Dashboard.css">
<div class="dash-contain">
    <div class="dash-head">
    <h1 class="dashboard-title">
        <span id="back-to-myclub" class="back-arrow">←</span> Dashboard
    </h1>

<script>
    document.getElementById("back-to-myclub").addEventListener("click", function () {
        window.location.href = "myclub.php";
    });
</script>    </div>
    <div class="dash-intro">
        <h2 class="title-main">👋Chào mừng đến trang Quản lý Câu Lạc Bộ</h2>
        <p class="title-sub">Đây là nơi để bạn quản lý thông tin cho CLB của bạn hoặc các CLB mà bạn đã tham gia</p>
    </div>

    <?php if ($club_info): ?>
    <div class="club-info-card">
        <div class="club-logo">
            <?php 
            // Ưu tiên logo từ pages nếu có
            $logo_display = '';
            if ($club_page && !empty($club_page['logo_path'])) {
                $logo_display = $club_page['logo_path'];
            }
            ?>
            <?php if (!empty($logo_display)): ?>
                <img src="<?= htmlspecialchars($logo_display) ?>" alt="Logo CLB" onerror="this.parentElement.innerHTML='<div class=\'no-logo\'><i class=\'ri-image-line\'></i><p>Lỗi tải logo</p></div>'">
            <?php else: ?>
                <div class="no-logo">
                    <i class="ri-image-line"></i>
                    <p>Chưa có logo</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="club-details">
            <h3><?= htmlspecialchars($club_info['name'] ?? 'Chưa có tên CLB') ?></h3>
            <p class="club-slogan"><?= htmlspecialchars($club_page['slogan'] ?? 'Chưa có slogan') ?></p>
            <p class="club-desc"><?= htmlspecialchars($club_info['description'] ?? 'Chưa có mô tả') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="task-group">
        <div class="box info-add">
            <h3>Bổ sung thông tin</h3>
            <p>Thông tin cơ bản của Câu Lạc Bộ</p>
            <button onclick="location.href='edit_inf_CLB.php?id=<?= $club_id ?>'" class="btn_addInfor">Bắt đầu</button>
         </div>

        <div class="box page-add">
            <h3>Tạo trang đại diện</h3>
            <p>Trang đại diện của CLB và công khai trang</p>
            <button onclick="location.href='tao_trang_dai_dien.php?id=<?= $club_id ?>'" class="btn_addPage">Bắt đầu</button>
            <button onclick="location.href='club-detail.php?id=<?= $club_id ?>'" class="btn_addPage" style="margin-top: 10px; background: rgba(255,255,255,0.7);">Xem trang</button>
        </div>

        <div class="box member-add">
            <h3>Tạo phòng ban</h3>
            <p>Tạo phòng ban để tổ chức và phân công công việc</p>
            <button onclick="location.href='taopb.php?id=<?= $club_id ?>'" class="btn_addPage">Bắt đầu</button>
        </div>
    </div>

    <div class="dash-main">
        <div class="event-sect">
            <div class="event-empty">
                <h2>Sự kiện</h2>
                <div class="empty-txt"> 
                    <p>Tạo sự kiện để thu hút các nhà tài trợ</p>
                </div> 
                <button onclick="location.href='add_Su_kien.php?id=<?= $club_id ?>'" class="taosk">+Tạo sự kiện</button>
                <button onclick="location.href='list_su_kien.php?id=<?= $club_id ?>'" 
                    class="xemsk">
                Xem sự kiện
            </button>
            </div>
        </div>
      
        <div class="member-list">
    <h2>Thành viên</h2>
    
    <div class="empty-txt" style="text-align: center; padding: 30px; color: #2d3748;">
        <p>Thêm thành viên cho câu lạc bộ của bạn</p>
        <button onclick="location.href='add_TV_CLB.php?id=<?= $club_id ?>'" class="taosk" style="margin-top: 10px;">
            + Thêm thành viên
        </button>
        <button onclick="location.href='view_members.php?id=<?= $club_id ?>'" 
                class="view_members">
            Xem danh sách
        </button>
    </div>
</div>


    </div>

    <div class="task-group" style="margin-top: 30px;">
        <div class="box info-add">
            <h3>📸 Thư viện ảnh</h3>
            <p>Quản lý và upload ảnh cho CLB</p>
            <button onclick="location.href='club-gallery.php?id=<?= $club_id ?>&mode=manage'" class="btn_addInfor">Quản lý</button>
        </div>
    </div>
</div>
 
<?php
load_footer();
?>