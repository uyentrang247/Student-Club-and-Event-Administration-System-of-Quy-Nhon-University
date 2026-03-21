<?php
/**
 * Application Constants
 * Định nghĩa tất cả các hằng số sử dụng trong hệ thống
 */

// Prevent multiple includes
if (defined('CONSTANTS_LOADED')) {
    return;
}
define('CONSTANTS_LOADED', true);

// User Roles
class UserRole {
    const DOI_TRUONG = 'doi_truong';
    const DOI_PHO = 'doi_pho';
    const TRUONG_BAN = 'truong_ban';
    const THANH_VIEN = 'thanh_vien';
    
    // Backward compatibility
    const CHU_NHIEM = 'doi_truong';
    const PHO_CHU_NHIEM = 'doi_pho';
    
    public static function getAll() {
        return [
            self::DOI_TRUONG,
            self::DOI_PHO,
            self::TRUONG_BAN,
            self::THANH_VIEN
        ];
    }
    
    public static function getLabel($role) {
        $labels = [
            self::DOI_TRUONG => 'Đội trưởng',
            self::DOI_PHO => 'Đội phó',
            self::TRUONG_BAN => 'Trưởng ban',
            self::THANH_VIEN => 'Thành viên',
            // Backward compatibility
            'chu_nhiem' => 'Đội trưởng',
            'pho_chu_nhiem' => 'Đội phó'
        ];
        return $labels[$role] ?? 'Không xác định';
    }
    
    public static function isAdmin($role) {
        return in_array($role, [self::DOI_TRUONG, self::DOI_PHO, self::TRUONG_BAN, 'chu_nhiem', 'pho_chu_nhiem']);
    }
}

// Member Status
class MemberStatus {
    const DANG_HOAT_DONG = 'dang_hoat_dong';
    const CHO_DUYET = 'cho_duyet';
    const DA_TU_CHOI = 'da_tu_choi';
    const NGUNG_HOAT_DONG = 'ngung_hoat_dong';
    
    public static function getLabel($status) {
        $labels = [
            self::DANG_HOAT_DONG => 'Đang hoạt động',
            self::CHO_DUYET => 'Chờ duyệt',
            self::DA_TU_CHOI => 'Đã từ chối',
            self::NGUNG_HOAT_DONG => 'Ngừng hoạt động'
        ];
        return $labels[$status] ?? 'Không xác định';
    }
}

// Event Status
class EventStatus {
    const SAP_DIEN_RA = 'sap_dien_ra';
    const DANG_DIEN_RA = 'dang_dien_ra';
    const DA_KET_THUC = 'da_ket_thuc';
    const DA_HUY = 'da_huy';
    
    public static function getLabel($status) {
        $labels = [
            self::SAP_DIEN_RA => 'Sắp diễn ra',
            self::DANG_DIEN_RA => 'Đang diễn ra',
            self::DA_KET_THUC => 'Đã kết thúc',
            self::DA_HUY => 'Đã hủy'
        ];
        return $labels[$status] ?? 'Không xác định';
    }
}

// Club Categories
class ClubCategory {
    const HOC_THUAT = 'Học thuật';
    const THE_THAO = 'Thể thao';
    const NGHE_THUAT = 'Nghệ thuật';
    const TINH_NGUYEN = 'Tình nguyện';
    const KY_NANG = 'Kỹ năng';
    const KHAC = 'Khác';
    
    public static function getAll() {
        return [
            self::HOC_THUAT,
            self::THE_THAO,
            self::NGHE_THUAT,
            self::TINH_NGUYEN,
            self::KY_NANG,
            self::KHAC
        ];
    }
}

// Notification Types
class NotificationType {
    const CLUB_JOIN = 'club_join';
    const CLUB_INVITE = 'club_invite';
    const EVENT_INVITE = 'event_invite';
    const EVENT_REMINDER = 'event_reminder';
    const ROLE_CHANGE = 'role_change';
    const SYSTEM = 'system';
}

// HTTP Status Codes
class HttpStatus {
    const OK = 200;
    const CREATED = 201;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const INTERNAL_ERROR = 500;
}
?>
