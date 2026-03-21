<?php
$page_css = "Danhsachsukien.css";
require 'site.php';
load_top();
load_header();

// Lấy danh sách sự kiện từ database
require('assets/database/connect.php');

// Lấy tổng số sự kiện
$sql_count = "SELECT COUNT(*) as total FROM events";
$result_count = $conn->query($sql_count);
$total_events = $result_count->fetch_assoc()['total'];

// Lấy danh sách sự kiện với thông tin câu lạc bộ và ảnh bìa từ media_library
$sql = "SELECT e.*, c.ten_clb, c.linh_vuc, anh_bia.file_path AS anh_bia_path
        FROM events e 
        LEFT JOIN clubs c ON e.club_id = c.id 
        LEFT JOIN media_library anh_bia ON e.anh_bia_id = anh_bia.id
        ORDER BY e.thoi_gian_bat_dau DESC";
$result = $conn->query($sql);
$events = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Đếm số lượng đã đăng ký cho mỗi sự kiện
        $count_sql = "SELECT COUNT(*) as total FROM event_registrations WHERE event_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $row['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $registered_count = 0;
        if ($count_result->num_rows > 0) {
            $registered_count = (int)$count_result->fetch_assoc()['total'];
        }
        $count_stmt->close();
        $row['registered_count'] = $registered_count;
        $events[] = $row;
    }
}

// Kiểm tra user có sở hữu CLB để bật CTA tạo sự kiện
$owner_club_id = 0;
$owner_club_name = '';
if (isset($_SESSION['user_id'])) {
    $owner_stmt = $conn->prepare("SELECT id, ten_clb FROM clubs WHERE chu_nhiem_id = ? ORDER BY id ASC LIMIT 1");
    $owner_stmt->bind_param("i", $_SESSION['user_id']);
    $owner_stmt->execute();
    $owner_res = $owner_stmt->get_result();
    if ($owner_res && $owner_res->num_rows > 0) {
        $owner = $owner_res->fetch_assoc();
        $owner_club_id = (int)$owner['id'];
        $owner_club_name = $owner['ten_clb'];
    }
    $owner_stmt->close();
}
?>

<div class="container">
    <!-- TIÊU ĐỀ -->
    <h1 class="title">
        🧡 Hãy cùng khám phá<br>
        <span class="highlight">Những sự kiện hấp dẫn</span> 🧡
    </h1>

    <!-- DANH MỤC ICON -->
    <div class="categories">
        <div class="cat-item" data-category="Học thuật">
            <img src="https://cdn-icons-png.flaticon.com/512/2995/2995541.png" alt="Học thuật">
            <p>Học thuật</p>
        </div>

        <div class="cat-item" data-category="Nghệ thuật">
            <img src="https://cdn-icons-png.flaticon.com/512/4339/4339685.png" alt="Nghệ thuật">
            <p>Nghệ thuật</p>
        </div>

        <div class="cat-item" data-category="Truyền thông">
            <img src="https://cdn-icons-png.flaticon.com/512/1048/1048945.png" alt="Truyền thông">
            <p>Truyền thông</p>
        </div>

        <div class="cat-item" data-category="Thể thao">
            <img src="https://cdn-icons-png.flaticon.com/512/2964/2964514.png" alt="Thể thao">
            <p>Thể thao</p>
        </div>

        <div class="cat-item" data-category="Sở thích">
            <img src="https://cdn-icons-png.flaticon.com/512/1946/1946488.png" alt="Sở thích">
            <p>Sở thích</p>
        </div>

        <div class="cat-item" data-category="Tình nguyện">
            <img src="https://cdn-icons-png.flaticon.com/512/2950/2950736.png" alt="Tình nguyện">
            <p>Tình nguyện</p>
        </div>

        <div class="cat-item" data-category="Ngôn ngữ">
            <img src="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" alt="Ngôn ngữ">
            <p>Ngôn ngữ</p>
        </div>

        <div class="cat-item" data-category="Điện tử">
            <img src="https://cdn-icons-png.flaticon.com/512/3063/3063187.png" alt="Điện tử">
            <p>Điện tử</p>
        </div>
    </div>

    <!-- TÌM KIẾM + BỘ LỌC -->
    <div class="filters">
        <input type="text" id="searchInput" placeholder="🔍 Tìm kiếm Câu lạc bộ theo tên...">

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
            <option value="Văn nghệ">Văn nghệ</option>
            <option value="Kỹ năng">Kỹ năng</option>
        </select>

        <select id="sortFilter">
            <option value="">Sắp xếp theo</option>
            <option value="date-asc">Số lượng thành viên tham gia nhiều nhất</option>
            <option value="date-desc">Số lượng thành viên tham gia ít nhất</option>
            <option value="participants-desc">Thời gian diễn ra gần nhất</option>
        </select>

        <button class="btn-filter" id="resetBtn">Bỏ lọc</button>
    </div>
</div>

<!-- DANH SÁCH SỰ KIỆN -->
<div id="event-list">
    <?php 
    $badge_colors = ['green', 'yellow', 'blue', 'red', 'purple'];
    foreach ($events as $index => $event): 
        $hidden_class = ($index >= 6) ? 'hidden-event' : '';
        $badge_color = $badge_colors[$index % count($badge_colors)];
        
        // Xử lý ảnh bìa - lấy từ media_library
        $event_image = 'https://via.placeholder.com/400x300?text=Event+Image';
        if (!empty($event['anh_bia_path'])) {
            $anh_bia_path = $event['anh_bia_path'];
            // Kiểm tra file có tồn tại không
            if (file_exists($anh_bia_path) || file_exists(__DIR__ . '/' . $anh_bia_path)) {
                $event_image = htmlspecialchars($anh_bia_path);
            }
        }
        
        // Xử lý mô tả ngắn
        $short_desc = mb_substr($event['mo_ta'], 0, 100) . '...';
        
        // Xử lý trạng thái
        $status_class = '';
        $status_text = '';
        switch($event['trang_thai']) {
            case 'sap_dien_ra':
                $status_class = 'upcoming';
                $status_text = 'Sắp diễn ra';
                break;
            case 'dang_dien_ra':
                $status_class = 'ongoing';
                $status_text = 'Đang diễn ra';
                break;
            case 'da_ket_thuc':
                $status_class = 'ended';
                $status_text = 'Đã kết thúc';
                break;
            default:
                $status_class = 'upcoming';
                $status_text = 'Sắp diễn ra';
        }
        
        // Format ngày tháng
        $event_date = date('d', strtotime($event['thoi_gian_bat_dau']));
        $event_month = 'Tháng ' . date('m', strtotime($event['thoi_gian_bat_dau']));
    ?>
    <?php
        // Lấy lĩnh vực từ CLB
        $linh_vuc = trim((string)($event['linh_vuc'] ?? ''));
        if (empty($linh_vuc) || $linh_vuc === '0') {
            $linh_vuc = 'Chưa phân loại';
        }
    ?>
    <div class="event-card <?php echo $hidden_class; ?>" 
         data-category="<?php echo htmlspecialchars($linh_vuc); ?>"
         data-name="<?php echo htmlspecialchars($event['ten_su_kien']); ?>">
        
        <div class="event-image-wrapper">
            <img class="event-img" src="<?php echo $event_image; ?>" 
                 alt="<?php echo htmlspecialchars($event['ten_su_kien']); ?>"
                 onerror="this.src='https://via.placeholder.com/400x300?text=Event+Image'">
            <div class="event-date-badge">
                <div class="date-day"><?php echo $event_date; ?></div>
                <div class="date-month"><?php echo $event_month; ?></div>
            </div>
        </div>

        <div class="event-info">
            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            
            <h2>
                <a href="chi_tiet_su_kien.php?id=<?php echo $event['id']; ?>" class="event-title-link">
                    <?php echo htmlspecialchars($event['ten_su_kien']); ?>
                </a>
            </h2>
            
            <div class="event-meta">
                <p class="event-club">
                    <i class="icon">🏛️</i>
                    <?php echo htmlspecialchars($event['ten_clb'] ?? 'Chưa có CLB'); ?>
                </p>
                <p class="event-location">
                    <i class="icon">📍</i>
                    <?php echo htmlspecialchars($event['dia_diem'] ?: 'Chưa cập nhật'); ?>
                </p>
            </div>

            <div class="event-footer">
                <p class="participant-count">
                    👥 Đã đăng ký: <strong><?php echo $event['registered_count'] ?? 0; ?></strong> / <?php echo $event['so_luong_toi_da']; ?> người
                </p>
                <a href="chi_tiet_su_kien.php?id=<?php echo $event['id']; ?>" class="btn-join">Tham gia</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- XEM THÊM -->
<div class="xem-them-wrap">
    <button class="btn-xem-them" id="loadMoreBtn">
        Xem thêm
        <span class="arrow">▾</span>
    </button>
</div>

<!-- CTA SECTION -->
<div class="cta-full">
    <?php if ($owner_club_id): ?>
        <h2>Tạo sự kiện cho CLB của bạn<br>(<?= htmlspecialchars($owner_club_name) ?>)</h2>
        <button class="cta-btn" onclick="window.location.href='add_Su_kien.php?id=<?= $owner_club_id ?>'">
            Bắt đầu ngay →
        </button>
    <?php else: ?>
        <h2>Bạn chưa quản lý CLB nào</h2>
        <p style="color:#4a5568; max-width:520px; margin: 10px auto 18px; font-size:16px;">
            Hãy tạo hoặc được phân quyền quản lý một Câu lạc bộ để bắt đầu tạo sự kiện cho cộng đồng.
        </p>
        <button class="cta-btn" onclick="window.location.href='createCLB.php'">
            Tạo CLB mới
        </button>
    <?php endif; ?>
</div>

<script src="assets/js/Danhsachsukien.js"></script>

<?php
load_footer();
?>
