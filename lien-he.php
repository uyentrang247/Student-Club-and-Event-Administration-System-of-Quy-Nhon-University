<?php
$page_css = "lien-he.css";
require 'site.php';
require 'assets/database/connect.php';
load_top();
load_header();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = "Vui lòng điền đầy đủ thông tin!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ!";
    } else {
        // Lưu vào database (bảng inquiries)
        try {
            $stmt = $conn->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            
            if ($stmt->execute()) {
                $success_message = "success";
            } else {
                $error_message = "Có lỗi xảy ra. Vui lòng thử lại!";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Có lỗi xảy ra. Vui lòng thử lại!";
        }
    }
}
?>

<!-- Success Modal -->
<?php if ($success_message === 'success'): ?>
<div id="successModal" class="modal" style="display: flex;">
    <div class="modal-content">
        <div class="success-icon">✓</div>
        <h2>Gửi thành công!</h2>
        <p>Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất!</p>
    </div>
</div>
<script>
setTimeout(() => {
    document.getElementById('successModal').style.display = 'none';
    window.location.href = 'trangchu.php';
}, 3000);
</script>
<?php endif; ?>

<div class="contact-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Liên hệ với chúng tôi</h1>
            <p class="hero-subtitle">Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn</p>
        </div>
        <div class="hero-decoration">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Contact Info Cards -->
        <div class="info-cards">
            <div class="info-card email-card">
                <div class="card-icon-wrapper">
                    <div class="card-icon">✉</div>
                </div>
                <h3>Email</h3>
                <p>UniQClub@qnu.edu.vn</p>
                <a href="mailto:leaderclub@qnu.edu.vn" class="card-link">
                    <span>Gửi email</span>
                    <span class="arrow">→</span>
                </a>
            </div>

            <div class="info-card phone-card">
                <div class="card-icon-wrapper">
                    <div class="card-icon">📞</div>
                </div>
                <h3>Điện thoại</h3>
                <p>(+84) 123 456 789</p>
                <a href="tel:+84123456789" class="card-link">
                    <span>Gọi ngay</span>
                    <span class="arrow">→</span>
                </a>
            </div>

            <div class="info-card location-card">
                <div class="card-icon-wrapper">
                    <div class="card-icon">📍</div>
                </div>
                <h3>Địa chỉ</h3>
                <p>Đại học Quy Nhơn<br>170 An Dương Vương, Quy Nhơn</p>
                <a href="https://maps.google.com" target="_blank" class="card-link">
                    <span>Xem bản đồ</span>
                    <span class="arrow">→</span>
                </a>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="form-section">
            <div class="form-header">
                <h2>Gửi tin nhắn cho chúng tôi</h2>
                <p>Điền thông tin bên dưới và chúng tôi sẽ liên hệ lại với bạn sớm nhất</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Họ và tên *</label>
                        <input type="text" id="name" name="name" placeholder="Nhập họ và tên" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="Nhập email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Tiêu đề *</label>
                    <input type="text" id="subject" name="subject" placeholder="Nhập tiêu đề" required>
                </div>

                <div class="form-group">
                    <label for="message">Nội dung *</label>
                    <textarea id="message" name="message" rows="6" placeholder="Nhập nội dung tin nhắn..." required></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Gửi tin nhắn</span>
                    <span class="btn-icon">→</span>
                </button>
            </form>
        </div>

        <!-- Social Media -->
        <div class="social-section">
            <h3>Kết nối với chúng tôi</h3>
            <div class="social-links">
                <a href="#" class="social-link facebook">
                    <span class="social-icon">f</span>
                    <span>Facebook</span>
                </a>
                <a href="#" class="social-link instagram">
                    <span class="social-icon">📷</span>
                    <span>Instagram</span>
                </a>
                <a href="#" class="social-link youtube">
                    <span class="social-icon">▶</span>
                    <span>YouTube</span>
                </a>
                <a href="#" class="social-link zalo">
                    <span class="social-icon">Z</span>
                    <span>Zalo</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
load_footer();
?>
