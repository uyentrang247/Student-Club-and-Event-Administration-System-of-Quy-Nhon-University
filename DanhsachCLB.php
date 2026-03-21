<?php
$page_css = "DanhsachCLB.css";
require 'site.php';
load_top();
load_header();

// Lấy danh sách CLB từ database
require('assets/database/connect.php');

// Kiểm tra xem bảng club_pages có tồn tại không
$table_check = $conn->query("SHOW TABLES LIKE 'club_pages'");
$has_club_pages = ($table_check && $table_check->num_rows > 0);

if ($has_club_pages) {
    // Nếu có bảng club_pages, join để lấy banner
    // Chỉ đếm thành viên đang hoạt động
    require_once __DIR__ . '/includes/constants.php';
    $status_active = MemberStatus::DANG_HOAT_DONG;
    // Lấy banner/logo thực tế từ bảng media_library thay vì cột banner_url cũ
    $sql = "SELECT 
                c.id, c.ten_clb, c.mo_ta, c.linh_vuc, c.color, c.ngay_thanh_lap, c.chu_nhiem_id, c.created_at,
                banner.file_path AS banner_url,
                logo.file_path AS logo_url,
                COUNT(CASE WHEN cm.trang_thai = ? THEN 1 END) AS so_thanh_vien 
            FROM clubs c 
            LEFT JOIN club_pages cp ON c.id = cp.club_id
            LEFT JOIN media_library banner ON cp.banner_id = banner.id
            LEFT JOIN media_library logo ON cp.logo_id = logo.id
            LEFT JOIN club_members cm ON c.id = cm.club_id 
            GROUP BY c.id, c.ten_clb, c.mo_ta, c.linh_vuc, c.color, c.ngay_thanh_lap, c.chu_nhiem_id, c.created_at, banner.file_path, logo.file_path
            ORDER BY c.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_active);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Nếu chưa có bảng club_pages, chỉ lấy từ clubs
    // Chỉ đếm thành viên đang hoạt động
    require_once __DIR__ . '/includes/constants.php';
    $status_active = MemberStatus::DANG_HOAT_DONG;
    $sql = "SELECT c.id, c.ten_clb, c.mo_ta, c.linh_vuc, c.color, c.ngay_thanh_lap, c.chu_nhiem_id, c.created_at,
                COUNT(CASE WHEN cm.trang_thai = ? THEN 1 END) as so_thanh_vien 
            FROM clubs c 
            LEFT JOIN club_members cm ON c.id = cm.club_id 
            GROUP BY c.id, c.ten_clb, c.mo_ta, c.linh_vuc, c.color, c.ngay_thanh_lap, c.chu_nhiem_id, c.created_at
            ORDER BY c.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_active);
    $stmt->execute();
    $result = $stmt->get_result();
}
$clubs = [];
if ($result && $result->num_rows > 0) {
    // Lấy danh sách tất cả user_id đã có trong club_members để tối ưu
    $users_in_members = [];
    $check_all_members = $conn->query("SELECT club_id, user_id FROM club_members WHERE trang_thai = 'dang_hoat_dong'");
    if ($check_all_members) {
        while ($member_row = $check_all_members->fetch_assoc()) {
            $key = $member_row['club_id'] . '_' . $member_row['user_id'];
            $users_in_members[$key] = true;
        }
    }

    // Lấy danh sách tất cả trưởng phòng ban
    $truong_phong_list = [];
    $check_truong_phong = $conn->query("SELECT club_id, truong_phong_id FROM phong_ban WHERE truong_phong_id IS NOT NULL");
    if ($check_truong_phong) {
        while ($tp_row = $check_truong_phong->fetch_assoc()) {
            $club_id = $tp_row['club_id'];
            $truong_phong_id = $tp_row['truong_phong_id'];
            if (!isset($truong_phong_list[$club_id])) {
                $truong_phong_list[$club_id] = [];
            }
            $truong_phong_list[$club_id][] = $truong_phong_id;
        }
    }

    while ($row = $result->fetch_assoc()) {
        // Lấy lĩnh vực trực tiếp từ database, không gán giá trị mặc định
        $linh_vuc = trim((string)($row['linh_vuc'] ?? ''));
        // Chỉ xử lý nếu thực sự rỗng, không gán "0" thành "Tình nguyện"
        if ($linh_vuc === '') {
            $linh_vuc = 'Chưa phân loại'; // Chỉ xử lý trường hợp rỗng hoàn toàn
        }
        $row['linh_vuc_display'] = $linh_vuc;

        $club_id = $row['id'];
        $additional_members = 0;

        // Kiểm tra đội trưởng: nếu chưa có trong club_members thì cộng thêm 1
        if (!empty($row['chu_nhiem_id'])) {
            $key = $club_id . '_' . $row['chu_nhiem_id'];
            if (!isset($users_in_members[$key])) {
                $additional_members++;
            }
        }

        // Kiểm tra trưởng phòng ban: nếu chưa có trong club_members thì cộng thêm
        if (isset($truong_phong_list[$club_id])) {
            foreach ($truong_phong_list[$club_id] as $truong_phong_id) {
                $key = $club_id . '_' . $truong_phong_id;
                if (!isset($users_in_members[$key])) {
                    $additional_members++;
                }
            }
        }

        // Cộng thêm số thành viên bị thiếu
        $row['so_thanh_vien'] = $row['so_thanh_vien'] + $additional_members;

        $clubs[] = $row;
    }
}
$total_clubs = count($clubs);
?>


<div class="container">

    <!-- TIÊU ĐỀ -->
    <h1 class="title">
        Khám phá <span class="highlight"><?php echo $total_clubs; ?> Câu Lạc Bộ</span> phù hợp với bạn!
    </h1>

    <!-- DANH MỤC ICON -->
    <div class="featured-categories-container">
        <h2 class="section-subtitle">Danh mục nổi bật</h2>

        <div class="category-grid">
            <div class="cat-card large" style="background-image: url('assets/img/danhmuc/hoctap.jpg');">
                <div class="cat-overlay">
                    <span class="cat-name">Học thuật</span>
                    <p class="cat-desc">Thực hiện tư duy nghiên cứu chuyên sâu</p>
                </div>
            </div>

            <div class="cat-right-column">
                <div class="cat-card medium" style="background-image: url('assets/img/danhmuc/nghethuat.jpg');">
                    <div class="cat-overlay">
                        <span class="cat-name">Nghệ thuật</span>
                    </div>
                </div>

                <div class="cat-sub-row">
                    <div class="cat-card small" style="background-image: url('assets/img/danhmuc/thethao.jpg');">
                        <div class="cat-overlay">
                            <span class="cat-name">Thể thao</span>
                        </div>
                    </div>
                    <div class="cat-card small" style="background-image: url('assets/img/danhmuc/tinhnguyen.jpg');">
                        <div class="cat-overlay">
                            <span class="cat-name">Tình nguyện</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TÌM KIẾM + BỘ LỌC -->
    <div class="filters">
        <input type="text" id="searchInput" placeholder="Tìm kiếm Câu Lạc Bộ theo tên...">

        <select id="categoryFilter">
            <option value="">Tất cả danh mục</option>
            <option value="Nghệ thuật">Nghệ thuật</option>
            <option value="Truyền thông">Truyền thông</option>
            <option value="Thể thao">Thể thao</option>
            <option value="Ngôn ngữ">Ngôn ngữ</option>
            <option value="Sở thích">Sở thích</option>
            <option value="Điện tử">Điện tử</option>
            <option value="Tình nguyện">Tình nguyện</option>
            <option value="Học thuật">Học thuật</option>
            <option value="Âm nhạc">Âm nhạc</option>
            <option value="Khởi nghiệp">Khởi nghiệp</option>
            <option value="Văn học">Văn học</option>
            <option value="Công nghệ">Công nghệ</option>
            <option value="Môi trường">Môi trường</option>
        </select>

        <select id="sortFilter">
            <option value="">Sắp xếp theo</option>
            <option value="name-asc">Tên A-Z</option>
            <option value="name-desc">Tên Z-A</option>
            <option value="members-desc">Nhiều thành viên nhất</option>
            <option value="members-asc">Ít thành viên nhất</option>
        </select>

        <button class="btn-filter" id="resetBtn">Bỏ lọc</button>
    </div>

</div>

<div id="club-list">
    <?php
    foreach ($clubs as $index => $club):
        $hidden_class = ($index >= 6) ? 'hidden-club' : '';
        // Dùng một màu badge thống nhất (tím brand)
        $badge_color = 'purple';
        $short_desc = mb_substr($club['mo_ta'], 0, 80) . '...';
        // Lấy lĩnh vực từ database
        $linh_vuc_display = $club['linh_vuc_display'] ?? $club['linh_vuc'] ?? '';
        $linh_vuc_display = trim((string)$linh_vuc_display);
        // Nếu rỗng hoặc "0" thì hiển thị "Chưa phân loại"
        if (empty($linh_vuc_display) || $linh_vuc_display === '0') {
            $linh_vuc_display = 'Chưa phân loại';
        }
    ?>
        <div class="club-card <?php echo $hidden_class; ?>">
            <img class="club-img" src="<?php echo htmlspecialchars($club['banner_url'] ?? $club['logo_url'] ?? 'https://i.imgur.com/1Qd7UXJ.jpeg'); ?>"
                alt="<?php echo htmlspecialchars($club['ten_clb']); ?>"
                onerror="this.src='https://i.imgur.com/1Qd7UXJ.jpeg'">
            <div class="club-info">
                <span class="badge <?php echo $badge_color; ?>"><?php echo htmlspecialchars($linh_vuc_display); ?></span>
                <h2>
                    <a href="club-detail.php?id=<?php echo $club['id']; ?>" class="club-title-link">
                        <?php echo htmlspecialchars($club['ten_clb']); ?>
                    </a>
                </h2>
                <p><?php echo htmlspecialchars($short_desc); ?></p>
                <p class="member-count">👥 <?php echo $club['so_thanh_vien']; ?> thành viên</p>
                <a href="club-detail.php?id=<?php echo $club['id']; ?>" class="btn-detail">Chi tiết</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="xem-them-wrap">
    <button class="btn-xem-them" id="loadMoreBtn">
        Xem thêm
        <span class="arrow">▾</span>
    </button>
</div>


<div class="cta-full">
    <h2>Dễ dàng Tạo & Quản lý Câu Lạc Bộ<br>ngay trên UniQCLUB</h2>
    <a class="cta-btn" href="createCLB.php">
        Bắt đầu ngay →
    </a>
</div>


<script src="assets/js/DanhsachCLB.js"></script>

<?php
load_footer();
?>