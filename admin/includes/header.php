<div class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <div class="header-right">
        <div class="user-menu">
            <div class="user-info">
                <img src="<?= !empty($_SESSION['admin_avatar']) ? '../' . htmlspecialchars($_SESSION['admin_avatar']) : '../assets/img/avatars/user.svg' ?>" 
                     alt="<?= htmlspecialchars($_SESSION['admin_name']) ?>" 
                     class="user-avatar">
                <span class="user-name"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            </div>
        </div>
    </div>
</div>

