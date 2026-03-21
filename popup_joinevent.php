<?php
session_start();
require('assets/database/connect.php');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die('Bạn cần đăng nhập để đăng ký sự kiện');
}

$user_id = $_SESSION['user_id'];
$event_id = $_GET['event_id'] ?? 0;

// Lấy thông tin sự kiện
$sql = "SELECT e.*, c.ten_clb 
        FROM events e 
        JOIN clubs c ON e.club_id = c.id 
        WHERE e.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die('Sự kiện không tồn tại');
}

// Kiểm tra đã đăng ký chưa
$sql = "SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$is_registered = $stmt->get_result()->num_rows > 0;

// Format thời gian
$start_time = date('H:i', strtotime($event['thoi_gian_bat_dau']));
$end_time = date('H:i', strtotime($event['thoi_gian_ket_thuc']));
$event_date = date('d/m/Y', strtotime($event['thoi_gian_bat_dau']));
?>

<div id="joinEventModal" class="modal">
    <div class="modal-content event-modal">
        <div class="modal-header">
            <h2>Đăng ký tham gia sự kiện</h2>
            <span class="close" onclick="closeEventModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <!-- Thông tin sự kiện -->
            <div class="event-info-card">
                <h3><?php echo htmlspecialchars($event['ten_su_kien']); ?></h3>
                <div class="event-details">
                    <div class="detail-item">
                        <span class="icon">📅</span>
                        <span><?php echo $event_date; ?> | <?php echo $start_time; ?> - <?php echo $end_time; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">📍</span>
                        <span><?php echo htmlspecialchars($event['dia_diem']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="icon">👥</span>
                        <span><?php echo htmlspecialchars($event['ten_clb']); ?></span>
                    </div>
                </div>
            </div>

            <?php if ($is_registered): ?>
                <div class="already-registered">
                    <div class="success-icon">✓</div>
                    <h4>Bạn đã đăng ký sự kiện này</h4>
                    <p>Chúng tôi sẽ thông báo cho bạn khi sự kiện sắp diễn ra.</p>
                </div>
            <?php else: ?>
                <!-- Form đăng ký -->
                <form id="eventRegistrationForm" class="registration-form">
                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                    
                    <div class="form-group">
                        <label for="fullname">Họ và tên *</label>
                        <input type="text" id="fullname" name="fullname" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone">Số điện thoại *</label>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="Nhập số điện thoại của bạn">
                    </div>

                    <div class="form-group">
                        <label for="student_id">Mã sinh viên</label>
                        <input type="text" id="student_id" name="student_id" 
                               placeholder="Nhập mã sinh viên (nếu có)">
                    </div>

                    <div class="form-group">
                        <label for="note">Ghi chú</label>
                        <textarea id="note" name="note" rows="3" 
                                  placeholder="Câu hỏi hoặc yêu cầu đặc biệt..."></textarea>
                    </div>

                    <div class="form-agreement">
                        <label class="checkbox-label">
                            <input type="checkbox" name="agree_terms" required>
                            <span class="checkmark"></span>
                            Tôi đồng ý với <a href="#" target="_blank">điều khoản và điều kiện</a> tham gia sự kiện
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeEventModal()">Hủy</button>
                        <button type="submit" class="btn-submit">
                            <span>Đăng ký tham gia</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('eventRegistrationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            registerForEvent();
        });
    }
});

function registerForEvent() {
    const form = document.getElementById('eventRegistrationForm');
    const formData = new FormData(form);
    
    // Hiển thị loading
    const submitBtn = form.querySelector('.btn-submit');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span>Đang đăng ký...</span>';
    submitBtn.disabled = true;

    fetch('register_event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showRegistrationSuccess();
        } else {
            alert('Lỗi: ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Lỗi:', error);
        alert('Có lỗi xảy ra khi đăng ký');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function showRegistrationSuccess() {
    const modalBody = document.querySelector('.modal-body');
    modalBody.innerHTML = `
        <div class="registration-success">
            <div class="success-animation">🎉</div>
            <h3>Đăng ký thành công!</h3>
            <p>Cảm ơn bạn đã đăng ký tham gia sự kiện. Chúng tôi sẽ gửi email xác nhận cho bạn.</p>
            <div class="success-actions">
                <button class="btn-close-success" onclick="closeEventModalAndReload()">Đóng</button>
            </div>
        </div>
    `;
}

function closeEventModalAndReload() {
    closeEventModal();
    setTimeout(() => {
        location.reload();
    }, 300);
}

function closeEventModal() {
    const modal = document.getElementById('joinEventModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            const container = document.getElementById('eventModalContainer');
            if (container) {
                container.innerHTML = '';
            }
        }, 300);
    }
}
</script>