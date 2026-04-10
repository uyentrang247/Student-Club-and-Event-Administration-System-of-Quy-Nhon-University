<header class="header">

    <!-- LEFT -->
    <div class="left">
        <a href="trangchu.php" class="logo">
            <img src="assets/img/uniqclub_logo.png" alt="UniQClub Logo" class="logo-img">
            <span>UniQCLUB</span>
        </a>
    </div>

    <!-- CENTER MENU -->
    <nav class="center-menu">
        <a href="trangchu.php">Trang chủ</a>

        <div class="nav-item">
            <span class="nav-link">Câu lạc bộ ▾</span>

            <div class="dropdown-menu">
                <a href="DanhsachCLB.php" class="dropdown-item">
                    <span class="icon orange">📘</span>
                    <div>
                        <h4>Danh sách CLB</h4>
                        <p>Kết nối với Câu Lạc Bộ bạn yêu thích</p>
                    </div>
                </a>

                <a href="myclub.php" class="dropdown-item">
                    <span class="icon blue">⚙️</span>
                    <div>
                        <h4>Quản lý CLB</h4>
                        <p>Tạo & Quản lý Câu Lạc Bộ của riêng bạn</p>
                    </div>
                </a>
            </div>
        </div>

        <a href="Danhsachsukien.php">Sự kiện</a>
        <a href="lien-he.php">Liên hệ</a>
    </nav>

    <!-- RIGHT BUTTONS -->
    <div class="right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- User đã đăng nhập -->
            <?php
            // Lấy avatar từ session trước, nếu không có thì query database
            $avatar = $_SESSION['avatar'] ?? '';
            $full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';

            // Nếu không có trong session, query từ database và cập nhật session
            if (empty($avatar)) {
                global $conn;
                if (isset($conn) && $conn instanceof mysqli) {
                    $user_id = $_SESSION['user_id'];
                    $sql = "SELECT avatar, full_name FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user_data = $result->fetch_assoc();
                        $avatar = $user_data['avatar'] ?? '';
                        $full_name = $user_data['full_name'] ?? $full_name;
                        // Cập nhật session để tránh query lại lần sau
                        $_SESSION['avatar'] = $avatar;
                        $_SESSION['full_name'] = $full_name;
                        $stmt->close();
                    }
                }
            }
            ?>

            <!-- Notification Bell -->
            <div class="nav-item notification-bell">
                <a href="notifications.php" class="bell-icon" title="Thông báo">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-badge" id="notification-count" style="display: none;">0</span>
                </a>
            </div>
            <script>
                // Inline notification counter to avoid cache issues
                (function() {
                    function updateNotificationCount() {
                        fetch('api/get_unread_count.php')
                            .then(function(res) {
                                if (!res.ok) return Promise.reject('Network error');
                                return res.text();
                            })
                            .then(function(text) {
                                if (!text || typeof text !== 'string') return;
                                text = text.trim();
                                if (!text) return;
                                var jsonStart = text.indexOf('{');
                                var jsonEnd = text.lastIndexOf('}');
                                if (jsonStart !== -1 && jsonEnd !== -1 && jsonEnd > jsonStart) {
                                    text = text.substring(jsonStart, jsonEnd + 1);
                                } else if (jsonStart === -1) {
                                    return;
                                }
                                try {
                                    var data = JSON.parse(text);
                                    var badge = document.getElementById('notification-count');
                                    if (badge) {
                                        if (data && typeof data.count === 'number' && data.count > 0) {
                                            badge.textContent = data.count > 99 ? '99+' : data.count;
                                            badge.style.display = 'block';
                                        } else {
                                            badge.style.display = 'none';
                                        }
                                    }
                                } catch (e) {
                                    // Silently fail
                                }
                            })
                            .catch(function() {
                                // Silently fail
                            });
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', updateNotificationCount);
                    } else {
                        updateNotificationCount();
                    }
                    setInterval(updateNotificationCount, 30000);

                    // Expose function globally so other pages can call it
                    window.updateNotificationCount = updateNotificationCount;
                })();
            </script>

            <div class="nav-item user-profile">
                <div class="user-avatar">
                    <?php if (!empty($avatar) && file_exists($avatar)):
                        // Thêm timestamp để tránh cache
                        $avatar_url = htmlspecialchars($avatar) . '?v=' . filemtime($avatar);
                    ?>
                        <img src="<?php echo $avatar_url; ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?php echo htmlspecialchars(strtoupper(substr($full_name, 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
                <span class="user-name"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></span>

                <div class="dropdown-menu profile-dropdown">
                    <a href="profile.php" class="dropdown-item">
                        <span class="icon">👤</span>
                        <div>
                            <h4>Hồ sơ của tôi</h4>
                            <p>Xem và chỉnh sửa thông tin</p>
                        </div>
                    </a>

                    <a href="myclub.php" class="dropdown-item">
                        <span class="icon">🏆</span>
                        <div>
                            <h4>CLB của tôi</h4>
                            <p>Quản lý các CLB đã tham gia</p>
                        </div>
                    </a>

                    <a href="settings.php" class="dropdown-item">
                        <span class="icon">⚙️</span>
                        <div>
                            <h4>Cài đặt</h4>
                            <p>Tùy chỉnh tài khoản</p>
                        </div>
                    </a>

                    <hr style="margin: 10px 0; border: none; border-top: 1px solid #eee;">

                    <a href="logout.php" class="dropdown-item">
                        <span class="icon">🚪</span>
                        <div>
                            <h4>Đăng xuất</h4>
                            <p>Thoát khỏi tài khoản</p>
                        </div>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- User chưa đăng nhập -->
            <button class="btn" onclick="location.href='register.php'">Đăng Ký</button>
            <button class="btn outline" onclick="location.href='login.php'">Đăng Nhập</button>
        <?php endif; ?>
    </div>
</header>