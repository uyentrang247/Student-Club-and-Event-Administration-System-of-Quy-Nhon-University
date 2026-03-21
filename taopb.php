<?php
session_start();
require_once('assets/database/connect.php');

// kiểm tra login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    echo "<script>alert('Vui lòng đăng nhập'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$popup_error = $_SESSION['popup_error'] ?? '';
$popup_success = $_SESSION['popup_success'] ?? '';
$open_popup = !empty($popup_error) || isset($_GET['openPopup']);

// Load functions và constants để kiểm tra quyền
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/constants.php';

// Lấy club_id: ưu tiên URL, sau đó session
if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0) {
    $club_id = (int)$_GET['id'];
    $_SESSION['club_id'] = $club_id;
} elseif (isset($_SESSION['club_id']) && $_SESSION['club_id'] > 0) {
    $club_id = (int)$_SESSION['club_id'];
} else {
    $club_id = null;
}

// Nếu club_id không có → báo chưa có CLB hoặc redirect tới tạo CLB
if (!$club_id) {
    $stmt = $conn->prepare("SELECT id FROM clubs WHERE chu_nhiem_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $club = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($club) {
        $club_id = $club['id'];
    } else {
        echo "<script>alert('Bạn chưa có CLB — chuyển sang tạo CLB'); window.location.href='createCLB.php';</script>";
        exit;
    }
}

// Kiểm tra quyền quản lý CLB
if (!can_manage_club($conn, $user_id, $club_id)) {
    redirect('myclub.php', 'Bạn không có quyền quản lý phòng ban của câu lạc bộ này!', 'error');
}

// lấy ID đội trưởng của CLB
$stmt_cn = $conn->prepare("SELECT chu_nhiem_id FROM clubs WHERE id = ?");
$stmt_cn->bind_param("i", $club_id);
$stmt_cn->execute();
$chu_nhiem_id = $stmt_cn->get_result()->fetch_assoc()['chu_nhiem_id'] ?? 0;
$stmt_cn->close();

// Lấy danh sách thành viên của CLB (chỉ lấy thành viên đang hoạt động)
$members = [];
$sql_members = "
    SELECT u.id AS user_id, u.ho_ten, u.username, u.email, u.so_dien_thoai, u.avatar,
           pb.ten_phong_ban, cm.vai_tro
    FROM club_members cm
    JOIN users u ON cm.user_id = u.id
    LEFT JOIN phong_ban pb ON cm.phong_ban_id = pb.id
    WHERE cm.club_id = ? AND cm.trang_thai = 'dang_hoat_dong'
            ORDER BY 
            CASE cm.vai_tro
            WHEN 'doi_truong' THEN 1
            WHEN 'chu_nhiem' THEN 1
            WHEN 'doi_pho' THEN 2
            WHEN 'pho_chu_nhiem' THEN 2
            WHEN 'truong_ban' THEN 3
            ELSE 4
            END,
        u.ho_ten
";
$stmt2 = $conn->prepare($sql_members);
$stmt2->bind_param("i", $club_id);
$stmt2->execute();
$members = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// Lấy danh sách phòng ban
$departments = [];
$stmt3 = $conn->prepare("SELECT id, ten_phong_ban FROM phong_ban WHERE club_id = ? ORDER BY created_at ASC, id ASC");
$stmt3->bind_param("i", $club_id);
$stmt3->execute();
$departments = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt3->close();

$page_css = "taopb.css";
require 'site.php';
load_top();
load_header();
?>

<div class="header-strip">
  <div class="title">
    <button class="btn-back" onclick="window.location.href='dashboard.php'">&#8592;</button>
    Thành viên
  </div>
  <div class="top-actions">
    <button class="btn-outline active" onclick="openModal()">Tạo phòng ban</button>
  </div>
</div>

<div class="container">
  <div class="hero-card">
    <div class="hero-left">
        <h3>Phòng ban</h3>
        <p>Quản lý danh sách thông tin thành viên theo từng phòng ban</p>
        <button class="create-btn" onclick="openModal()">Tạo phòng ban</button>
    </div>

    <div class="hero-right">
        <img src="assets/img/Ketnoitre.png" alt="illustration">
    </div>
  </div>

  <!-- DANH SÁCH PHÒNG BAN -->
  <div class="departments-list">
      <?php if (empty($departments)): ?>
          <div class="empty-dept">Chưa có phòng ban nào — hãy tạo mới!</div>
      <?php else: ?>
          <?php foreach ($departments as $d): 
                $cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM club_members WHERE phong_ban_id = ? AND club_id = ? AND trang_thai = 'dang_hoat_dong'");
                $cnt_stmt->bind_param("ii", $d['id'], $club_id);
                $cnt_stmt->execute();
                $cnt = $cnt_stmt->get_result()->fetch_assoc()['c'] ?? 0;
                $cnt_stmt->close();
          ?>
              <div class="dept-card inline-dept">
                <div class="dept-icon-mini">
                    <img src="assets/img/logoQuyNhon-icon.jpg" alt="icon">
                </div>
                <div class="dept-info-inline">
                    <div class="dept-name"><?= htmlspecialchars($d['ten_phong_ban']) ?></div>
                    <div class="dept-count"><?= $cnt ?> thành viên</div>
                </div>
            </div>
          <?php endforeach; ?>
      <?php endif; ?>
  </div>

  <!-- BẢNG THÀNH VIÊN CLB -->
  <div class="table-card">
    <table class="members-table">
      <thead>
        <tr>
          <th>Thành viên CLB</th>
          <th>Số điện thoại</th>
          <th>Email</th>
          <th>Phòng ban</th>
          <th>Chức vụ</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($members)): ?>
          <tr><td colspan="5" class="empty-row">Chưa có thành viên nào trong CLB</td></tr>
        <?php else: ?>
          <?php foreach ($members as $m): ?>
            <?php
              $avatar_path = $m['avatar'] ?? '';
              if (!empty($avatar_path) && file_exists($avatar_path)) {
                $anh_avatar = htmlspecialchars($avatar_path) . '?v=' . filemtime($avatar_path);
              } else {
                $anh_avatar = "assets/img/avatars/user.svg";
              }
            ?>
            <tr>
              <td>
                <div class="member-cell">
                  <img src="<?= $anh_avatar ?>" alt="<?= htmlspecialchars($m['ho_ten']) ?>" class="member-avatar-small">
                  <div>
                    <div class="member-name"><?= htmlspecialchars($m['ho_ten']) ?></div>
                    <div class="subid"><?= htmlspecialchars($m['username']) ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($m['so_dien_thoai'] ?? '-') ?></td>
              <td><?= htmlspecialchars($m['email'] ?? '-') ?></td>
              <td><?= htmlspecialchars($m['ten_phong_ban'] ?? '-') ?></td>
              <td>
                <?php
                  $role_map = [
                    'doi_truong' => 'Đội trưởng',
                    'chu_nhiem' => 'Đội trưởng',
                    'doi_pho' => 'Đội phó',
                    'pho_chu_nhiem' => 'Đội phó',
                    'truong_ban' => 'Trưởng ban',
                    'thanh_vien' => 'Thành viên'
                  ];
                  $vai_tro = $m['vai_tro'] ?? 'thanh_vien';
                  $role_text = $role_map[$vai_tro] ?? 'Thành viên';
                  $role_class = (in_array($vai_tro, ['doi_truong', 'chu_nhiem'])) ? 'president' : '';
                ?>
                <div style="display:flex;align-items:center;gap:8px;">
                  <span class="role <?= $role_class ?>">
                    <?= $role_text ?>
                  </span>
                  <?php if ($user_id == $chu_nhiem_id && $m['user_id'] != $chu_nhiem_id): ?>
                    <button class="btn-edit-role" onclick="openEditRole(<?= $m['user_id'] ?>, <?= $club_id ?>)" title="Chỉnh sửa chức vụ">✏️</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="modalContainer"></div>
<div id="roleModalContainer"></div>

<script>
function openModal() {
    const clubId = <?= (int)$club_id ?>;
    fetch('popup_taopb.php?club_id=' + clubId)
        .then(r => r.text())
        .then(html => {
            const mc = document.getElementById('modalContainer');
            mc.innerHTML = html;

            const modal = document.getElementById('createDeptModal');
            if (modal) {
                modal.classList.add('show');
                
                // Khởi tạo lại validation và counter sau khi HTML được load
                setTimeout(() => {
                    if (typeof initFormValidation === 'function') {
                        initFormValidation();
                    }
                }, 100);
            }
        });
}

function closeModal() {
    const modal = document.getElementById('createDeptModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            document.getElementById('modalContainer').innerHTML = '';
        }, 300);
    }
}

function openEditRole(memberId, clubId) {
    fetch(`popup_edit_role.php?member_id=${memberId}&club_id=${clubId}`)
        .then(r => r.text())
        .then(html => {
            const mc = document.getElementById('roleModalContainer');
            mc.innerHTML = html;

            const modal = document.getElementById('editRoleModal');
            if (modal) modal.classList.add('show');
        });
}

function closeRoleModal() {
    const modal = document.getElementById('editRoleModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            document.getElementById('roleModalContainer').innerHTML = '';
        }, 300);
    }
}

<?php if ($open_popup): ?>
window.addEventListener('DOMContentLoaded', () => setTimeout(openModal, 100));
<?php endif; ?>

<?php if ($popup_success): ?>
window.addEventListener('DOMContentLoaded', () => {
    const box = document.createElement('div');
    box.className = 'msg-box success';
    box.innerText = '<?= $popup_success ?>';
    document.body.appendChild(box);
    setTimeout(() => box.remove(), 3000);
});
<?php endif; ?>
</script>

<?php
load_footer();
exit; 
?>
