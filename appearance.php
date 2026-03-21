<?php
$page_css = "appearance.css";
require 'site.php';
load_top();
load_header();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<div class="appearance-container">
    <div class="page-header">
        <div class="header-icon">🎨</div>
        <h1>Giao diện</h1>
    </div>

    <div class="settings-card">
        <div class="setting-item">
            <div class="setting-info">
                <h3>Chế độ tối</h3>
                <p>Chuyển sang giao diện tối để bảo vệ mắt</p>
            </div>
            <label class="toggle-switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider"></span>
            </label>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    console.log('Dark mode toggle:', darkModeToggle);
    
    // Kiểm tra dark mode từ localStorage
    try {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        console.log('Current dark mode:', isDarkMode);
        
        // Set trạng thái ban đầu
        if (isDarkMode) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
    } catch (e) {
        console.error('localStorage error:', e);
    }
    
    // Toggle dark mode
    darkModeToggle.addEventListener('change', function() {
        console.log('Toggle changed:', this.checked);
        
        if (this.checked) {
            document.body.classList.add('dark-mode');
            console.log('Dark mode enabled');
            try {
                localStorage.setItem('darkMode', 'true');
            } catch (e) {
                console.error('Cannot save to localStorage:', e);
            }
        } else {
            document.body.classList.remove('dark-mode');
            console.log('Dark mode disabled');
            try {
                localStorage.setItem('darkMode', 'false');
            } catch (e) {
                console.error('Cannot save to localStorage:', e);
            }
        }
        
        // Log body classes
        console.log('Body classes:', document.body.className);
    });
});
</script>

<?php
load_footer();
?>
