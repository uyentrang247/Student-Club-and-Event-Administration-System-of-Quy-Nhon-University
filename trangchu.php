<?php
$page_css = "trangchu.css";
require 'site.php';
load_top();
load_header();
?>

<!-- HERO SECTION -->
<section class="hero-qnu">
    <img class="hero-bg-image" src="assets/img/qnu-gate.jpg" alt="Cổng trường Đại học Quy Nhơn">
    <div class="hero-overlay"></div>
    
    <div class="hero-container">
        <h1 class="hero-title">Hệ thống quản lý <span class="gradient-text">Câu lạc bộ & Sự kiện</span> QNU</h1>
        
        <p class="hero-description">
            Nền tảng chính thức dành cho các CLB đã được Trường Đại học Quy Nhơn phê duyệt. 
            Quản lý thành viên – sự kiện – tài liệu một cách khoa học và hiệu quả.
        </p>
        
        <div class="hero-buttons">
            <button class="btn primary pulse" onclick="location.href='DanhsachCLB.php'">
                <i class="ri-group-line"></i>
                Xem danh sách CLB
            </button>
        </div>
        
    </div>
</section>

<!-- TÍNH NĂNG CHÍNH -->
<section class="feature-qnu">
    <div class="feature-item">
        <i class="ri-group-fill feature-icon"></i>
        <h3>Quản lý thành viên</h3>
        <p>Theo dõi hồ sơ, phòng ban, phân quyền và hoạt động của các thành viên.</p>
    </div>

    <div class="feature-item">
        <i class="ri-calendar-event-fill feature-icon"></i>
        <h3>Quản lý sự kiện</h3>
        <p>Tạo – điểm danh – thống kê sự kiện nhanh chóng, chính xác.</p>
    </div>

    <div class="feature-item">
        <i class="ri-folder-2-fill feature-icon"></i>
        <h3>Tài liệu & truyền thông</h3>
        <p>Lưu trữ tài liệu nội bộ và đăng tin tức cho CLB.</p>
    </div>
</section>

<!-- ĐỘI NGŨ PHÁT TRIỂN -->
<section class="team-section">
    <h2>Đội ngũ phát triển</h2>
    <p class="section-subtitle">Những người đã xây dựng và phát triển hệ thống</p>

    <div class="team-grid">
        <div class="team-card">
            <div class="team-avatar">
                <img src="assets/img/team/tien.jpg" alt="Nguyễn Tiên">
            </div>
            <h4>Nguyễn Tiên</h4>
            <p class="team-role">THÀNH VIÊN</p>
            <p class="team-desc">Quản lý và điều phối dự án</p>
            <div class="team-social">
                <a href="https://github.com/NguyenTienvn" target="_blank">
                    <i class="ri-github-fill"></i>
                </a>
                <a href="https://www.facebook.com/ki.tuan.77770?locale=vi_VN" target="_blank">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="mailto:nhiemvn8@gmail.com" target="_blank">
                    <i class="ri-mail-fill"></i>
                </a>
            </div>
        </div>

        <div class="team-card">
            <div class="team-avatar">
                <img src="assets/img/team/minhtam.jpg" alt="Member 2">
            </div>
            <h4>Hà Thị Minh Tâm</h4>
            <p class="team-role">THÀNH VIÊN</p>
            <p class="team-desc">Phát triển giao diện người dùng</p>
            <div class="team-social">
                <a href="https://github.com/mihtahm05" target="_blank">
                    <i class="ri-github-fill"></i>
                </a>
                <a href="https://www.facebook.com/share/1H8kvjRtBk/?mibextid=wwXIfr" target="_blank">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="mailto:tamht25072005@gmail.com" target="_blank">
                    <i class="ri-mail-fill"></i>
                </a>
            </div>
        </div>

        <div class="team-card">
            <div class="team-avatar">
                <img src="assets/img/team/thuytrang.jpg" alt="Member 3">
            </div>
            <h4>Trương Thị Thùy Trang</h4>
            <p class="team-role">Thành viên</p>
            <p class="team-desc">Phát triển backend và database</p>
            <div class="team-social">
            <a href="https://github.com/thuytrang2005" target="_blank">
                    <i class="ri-github-fill"></i>
                </a>
                <a href="https://www.facebook.com/thuytrang24072005/" target="_blank">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="mailto:trangtruong24072005@gmail.com" target="_blank">
                    <i class="ri-mail-fill"></i>
                </a>
            </div>
        </div>

        <div class="team-card">
            <div class="team-avatar">
                <img src="assets/img/team/uyentrang.jpg" alt="Member 4">
            </div>
            <h4>Trương Thị Uyên Trang</h4>
            <p class="team-role">Thành viên</p>
            <p class="team-desc">Thiết kế UI/UX và trải nghiệm</p>
            <div class="team-social">
            <a href="https://github.com/uyentrang247" target="_blank">
                    <i class="ri-github-fill"></i>
                </a>
                <a href="https://www.facebook.com/trangg.247/" target="_blank">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="mailto:uyentrang2475@gmail.com" target="_blank">
                    <i class="ri-mail-fill"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- LỢI ÍCH KHI THAM GIA CLB -->
<section class="benefits-section">
    <h2>Tại sao nên tham gia CLB?</h2>
    <p class="section-subtitle">Những giá trị bạn nhận được khi là thành viên CLB</p>

    <div class="benefits-grid">
        <div class="benefit-item">
            <div class="benefit-icon">🎯</div>
            <h4>Phát triển kỹ năng</h4>
            <p>Rèn luyện kỹ năng mềm, làm việc nhóm, lãnh đạo và giao tiếp</p>
        </div>

        <div class="benefit-item">
            <div class="benefit-icon">🤝</div>
            <h4>Mở rộng mạng lưới</h4>
            <p>Kết nối với sinh viên cùng đam mê, xây dựng mối quan hệ bền vững</p>
        </div>

        <div class="benefit-item">
            <div class="benefit-icon">🏆</div>
            <h4>Cơ hội thăng tiến</h4>
            <p>Tham gia các vị trí quản lý, tổ chức sự kiện lớn</p>
        </div>

        <div class="benefit-item">
            <div class="benefit-icon">📜</div>
            <h4>Chứng nhận & Điểm rèn luyện</h4>
            <p>Nhận chứng nhận hoạt động và cộng điểm rèn luyện</p>
        </div>

        <div class="benefit-item">
            <div class="benefit-icon">🎨</div>
            <h4>Sáng tạo & Đam mê</h4>
            <p>Thỏa sức sáng tạo, theo đuổi đam mê của bản thân</p>
        </div>

        <div class="benefit-item">
            <div class="benefit-icon">💼</div>
            <h4>Kinh nghiệm thực tế</h4>
            <p>Tích lũy kinh nghiệm quý báu cho CV và tương lai</p>
        </div>
    </div>
</section>

<!-- CALL TO ACTION -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Sẵn sàng tham gia cộng đồng CLB QNU?</h2>
        <p>Khám phá hơn 30 câu lạc bộ và tìm nơi phù hợp với đam mê của bạn</p>
        <div class="cta-buttons">
            <button class="btn primary large" onclick="location.href='DanhsachCLB.php'">
                Khám phá CLB ngay
            </button>
            <button class="btn outline large" onclick="location.href='createCLB.php'">
                Tạo CLB mới
            </button>
        </div>
    </div>
</section>

<?php
load_footer();
?>