-- =========================================================
-- LEADERCLUB 2.0 — FULL OPTIMIZED DATABASE
-- Tác giả: ChatGPT – thiết kế theo yêu cầu anh Tý
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS leaderclub;
CREATE DATABASE leaderclub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE leaderclub;

-- ============================
-- USERS
-- ============================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten VARCHAR(100),
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    avatar VARCHAR(255),
    so_dien_thoai VARCHAR(20),
    student_id VARCHAR(20),
    class VARCHAR(50),
    faculty VARCHAR(100),
    gender ENUM('nam','nu','khac') DEFAULT 'khac',
    vai_tro VARCHAR(50) DEFAULT 'THANH_VIEN',
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(255),
    remember_token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================
-- ROLES
-- ============================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    UNIQUE(user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- CLUBS
-- ============================

CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_clb VARCHAR(150) NOT NULL,
    mo_ta TEXT,
    linh_vuc VARCHAR(100) DEFAULT 'Khác',
    so_thanh_vien INT DEFAULT 0,
    color VARCHAR(20) DEFAULT '#667eea',
    ngay_thanh_lap DATE,
    chu_nhiem_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chu_nhiem_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- CLUB CONTACTS
-- ============================

CREATE TABLE club_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(20),
    website VARCHAR(255),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    twitter VARCHAR(255),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- MEDIA LIBRARY
-- ============================

CREATE TABLE media_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    uploader_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- CLUB PAGES
-- ============================

CREATE TABLE club_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL UNIQUE,
    slogan VARCHAR(255),
    description TEXT,
    banner_id INT,
    logo_id INT,
    primary_color VARCHAR(20),
    is_public TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (banner_id) REFERENCES media_library(id) ON DELETE SET NULL,
    FOREIGN KEY (logo_id) REFERENCES media_library(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- CLUB ROLES
-- ============================

CREATE TABLE club_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    UNIQUE(club_id, name),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- PHÒNG BAN
-- ============================

CREATE TABLE phong_ban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    ten_phong_ban VARCHAR(100) NOT NULL,
    chuc_nang_nhiem_vu TEXT,
    truong_phong_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (truong_phong_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- CLUB MEMBERS
-- ============================

CREATE TABLE club_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    vai_tro VARCHAR(50) DEFAULT 'THANH_VIEN',
    role_id INT,
    phong_ban_id INT,
    trang_thai ENUM('dang_hoat_dong','cho_duyet','da_nghi') DEFAULT 'cho_duyet',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(club_id, user_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES club_roles(id) ON DELETE SET NULL,
    FOREIGN KEY (phong_ban_id) REFERENCES phong_ban(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- EVENTS
-- ============================

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    ten_su_kien VARCHAR(200) NOT NULL,
    mo_ta TEXT,
    noi_dung_chi_tiet LONGTEXT,
    anh_bia_id INT,
    dia_diem VARCHAR(255),
    thoi_gian_bat_dau DATETIME,
    thoi_gian_ket_thuc DATETIME,
    so_luong_toi_da INT,
    han_dang_ky DATETIME,
    trang_thai ENUM('sap_dien_ra','dang_dien_ra','da_ket_thuc','da_huy') DEFAULT 'sap_dien_ra',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (anh_bia_id) REFERENCES media_library(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- EVENT REGISTRATIONS (merged)
-- ============================

CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('cho_duyet','da_duyet','da_huy','checked_in') DEFAULT 'cho_duyet',
    ghi_chu TEXT,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- JOIN REQUESTS
-- ============================

CREATE TABLE join_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    email VARCHAR(100),
    so_dien_thoai VARCHAR(20),
    loi_nhan TEXT,
    trang_thai ENUM('cho_duyet','da_duyet','tu_choi') DEFAULT 'cho_duyet',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duyet_boi INT,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (duyet_boi) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- ACHIEVEMENTS
-- ============================

CREATE TABLE club_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    achievement_date DATE NOT NULL,
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- ACTIVITIES
-- ============================

CREATE TABLE club_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    activity_type VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- GALLERY
-- ============================

CREATE TABLE club_gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    media_id INT,
    title VARCHAR(255),
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================
-- REVIEWS
-- ============================

CREATE TABLE club_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(club_id, user_id),
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- CLUB STATS
-- ============================

CREATE TABLE club_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL UNIQUE,
    total_events INT DEFAULT 0,
    total_members INT DEFAULT 0,
    total_reviews INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- LIÊN HỆ
-- ============================

CREATE TABLE lienhe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================
-- NOTIFICATIONS (Anh Tý's actual structure)
-- ============================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================
-- DEFAULT ADMIN ACCOUNT
-- ============================
-- Tài khoản admin mặc định: Username: Admin, Password: Admin123
-- LƯU Ý: Sau khi import database, vui lòng chạy file admin/setup_admin.php 
-- để tạo tài khoản admin với password đã được hash an toàn
-- Hoặc chạy lệnh sau để tạo tài khoản admin:
-- INSERT INTO users (username, password, ho_ten, vai_tro, created_at) 
-- VALUES ('Admin', '[HASH_PASSWORD_Admin123]', 'Administrator', 'ADMIN', NOW());

SET FOREIGN_KEY_CHECKS = 1;
