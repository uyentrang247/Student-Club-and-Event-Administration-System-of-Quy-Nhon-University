<?php
session_start();
include __DIR__ . '/assets/database/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT full_name, username, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Lấy ID CLB từ URL
$club_id = $_GET['club_id'] ?? 0;

// Lấy thông tin CLB
$stmt = $conn->prepare("SELECT name FROM clubs WHERE id = ?");
$stmt->bind_param("i", $club_id);
$stmt->execute();
$club = $stmt->get_result()->fetch_assoc();

// Kiểm tra xem người dùng đã là thành viên hoặc đã gửi yêu cầu chưa
$stmt = $conn->prepare("SELECT id, status FROM members WHERE club_id = ? AND user_id = ?");
$stmt->bind_param("ii", $club_id, $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    $message = "Bạn đã là thành viên của CLB này.";
    if ($existing['status'] == 'pending') {
        $message = "Bạn đã gửi yêu cầu tham gia CLB này. Vui lòng chờ duyệt!";
    }
    echo "<div class='modal show' id='joinClubModal'><div class='modal-content'><p>{$message}</p><div style='text-align:center;margin-top:20px'><button class='btn-submit-full' onclick='closeJoinModal()'>Đóng</button></div></div></div>";
    exit;
}

// Lấy lỗi từ session (nếu có)
$error = $_SESSION['join_error'] ?? '';
unset($_SESSION['join_error']);
?>

<link rel="stylesheet" href="assets/css/popup_join.css">

<div class="modal" id="joinClubModal" role="dialog" aria-labelledby="joinModalTitle" aria-modal="true">
  <div class="modal-backdrop" onclick="closeJoinModal()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <button class="close-btn" onclick="closeJoinModal()" aria-label="Đóng">×</button>
      <div class="modal-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="8.5" cy="7" r="4"></circle>
          <line x1="20" y1="8" x2="20" y2="14"></line>
          <line x1="23" y1="11" x2="17" y2="11"></line>
        </svg>
      </div>
      <h2 id="joinModalTitle">Tham gia Câu Lạc Bộ</h2>
      <p class="modal-subtitle">Hoàn thành thông tin để gửi yêu cầu tham gia <strong><?= htmlspecialchars($club['name'] ?? 'CLB') ?></strong></p>
    </div>

    <!-- Thông tin người đăng ký -->
    <div class="member-info">
      <div class="member-avatar">
        <?= !empty($user['full_name']) ? mb_substr($user['full_name'], 0, 1, 'UTF-8') : 'U' ?>
      </div>
      <div class="member-details">
        <h4><?= htmlspecialchars($user['full_name'] ?? 'Người dùng') ?></h4>
        <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
      </div>
      <div class="member-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Đã xác thực</span>
      </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
            </svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form id="formJoinClub" method="POST" action="process_join.php" novalidate>
      <input type="hidden" name="club_id" value="<?= (int)$club_id ?>">
      <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">

      <div class="form-group">
        <label for="full_name">
          Họ và tên <span class="required" aria-label="bắt buộc">*</span>
        </label>
        <input type="text" 
               id="full_name"
               name="full_name" 
               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" 
               readonly 
               aria-readonly="true">
        <span class="field-note">Thông tin từ tài khoản của bạn</span>
      </div>

      <div class="form-group">
        <label for="phone">
          Số điện thoại <span class="required" aria-label="bắt buộc">*</span>
        </label>
        <input type="tel" 
               id="phone"
               name="phone" 
               value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
               placeholder="Nhập số điện thoại của bạn"
               pattern="[0-9]{10,11}"
               maxlength="11"
               required
               aria-required="true"
               aria-describedby="phone_error">
        <span id="phone_error" class="error-message" role="alert"></span>
      </div>

      <div class="form-group">
        <label for="email">
          Email <span class="required" aria-label="bắt buộc">*</span>
        </label>
        <input type="email" 
               id="email"
               name="email" 
               value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
               readonly
               aria-readonly="true">
        <span class="field-note">Thông tin từ tài khoản của bạn</span>
      </div>

      <div class="form-group">
        <label for="department_id">
          Phòng ban <span class="optional">(Tùy chọn)</span>
        </label>
        <select id="department_id" name="department_id">
          <option value="">-- Chưa chọn phòng ban --</option>
          <?php
          // Lấy danh sách phòng ban của CLB
          $stmt_dept = $conn->prepare("SELECT id, name FROM departments WHERE club_id = ? ORDER BY name ASC");
          $stmt_dept->bind_param("i", $club_id);
          $stmt_dept->execute();
          $departments = $stmt_dept->get_result()->fetch_all(MYSQLI_ASSOC);
          $stmt_dept->close();
          
          foreach ($departments as $dept):
          ?>
            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <span class="field-note">Bạn có thể chọn phòng ban muốn tham gia</span>
      </div>

      <div class="form-group">
        <label for="message">
          Lời nhắn <span class="optional">(Tùy chọn)</span>
        </label>
        <textarea id="message"
                  name="message" 
                  rows="4" 
                  maxlength="300"
                  placeholder="Giới thiệu bản thân và lý do bạn muốn tham gia CLB..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
      </div>

      <div class="form-agreement">
        <label class="checkbox-wrapper">
          <input type="checkbox" name="agree_terms" required>
          <span class="checkmark"></span>
          <span class="checkbox-label">Tôi đồng ý tuân thủ <a href="#" target="_blank">nội quy và quy định</a> của CLB</span>
        </label>
      </div>

      <button type="submit" class="btn-submit-full">
        <span class="btn-text">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          Gửi yêu cầu tham gia
        </span>
        <span class="btn-loading" style="display: none;">
          <svg class="spinner" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" stroke-width="3" fill="none"></circle>
          </svg>
          Đang gửi...
        </span>
      </button>
    </form>
  </div>
</div>

<script>
// Initialize validation when popup is loaded
if (typeof initJoinFormValidation === 'function') {
    setTimeout(() => {
        initJoinFormValidation();
    }, 100);
}
</script>