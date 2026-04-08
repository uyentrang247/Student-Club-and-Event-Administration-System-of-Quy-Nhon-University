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

// User Roles (Database mới: leader, vice_leader, head, member)
class UserRole {
    const LEADER = 'leader';           // Đội trưởng
    const VICE_LEADER = 'vice_leader'; // Đội phó
    const HEAD = 'head';               // Trưởng ban
    const MEMBER = 'member';           // Thành viên
    
    // Backward compatibility (giữ lại để code cũ vẫn chạy)
    const DOI_TRUONG = 'leader';
    const DOI_PHO = 'vice_leader';
    const TRUONG_BAN = 'head';
    const THANH_VIEN = 'member';
    const CHU_NHIEM = 'leader';
    const PHO_CHU_NHIEM = 'vice_leader';
    
    public static function getAll() {
        return [
            self::LEADER,
            self::VICE_LEADER,
            self::HEAD,
            self::MEMBER
        ];
    }
    
    public static function getLabel($role) {
        $labels = [
            self::LEADER => 'Đội trưởng',
            self::VICE_LEADER => 'Đội phó',
            self::HEAD => 'Trưởng ban',
            self::MEMBER => 'Thành viên',
            // Backward compatibility
            'doi_truong' => 'Đội trưởng',
            'doi_pho' => 'Đội phó',
            'truong_ban' => 'Trưởng ban',
            'thanh_vien' => 'Thành viên',
            'chu_nhiem' => 'Đội trưởng',
            'pho_chu_nhiem' => 'Đội phó'
        ];
        return $labels[$role] ?? 'Không xác định';
    }
    
    public static function isAdmin($role) {
        return in_array($role, [self::LEADER, self::VICE_LEADER, self::HEAD]);
    }
}

// Member Status (Database mới: active, pending, inactive)
class MemberStatus {
    const ACTIVE = 'active';     // Đang hoạt động
    const PENDING = 'pending';   // Chờ duyệt
    const INACTIVE = 'inactive'; // Đã nghỉ / không hoạt động
    
    // Backward compatibility (giữ lại để code cũ vẫn chạy)
    const DANG_HOAT_DONG = 'active';
    const CHO_DUYET = 'pending';
    const DA_TU_CHOI = 'rejected';
    const NGUNG_HOAT_DONG = 'inactive';
    
    public static function getLabel($status) {
        $labels = [
            self::ACTIVE => 'Đang hoạt động',
            self::PENDING => 'Chờ duyệt',
            self::INACTIVE => 'Ngừng hoạt động',
            // Backward compatibility
            'dang_hoat_dong' => 'Đang hoạt động',
            'cho_duyet' => 'Chờ duyệt',
            'da_nghi' => 'Ngừng hoạt động',
            'da_tu_choi' => 'Đã từ chối',
            'rejected' => 'Đã từ chối'
        ];
        return $labels[$status] ?? 'Không xác định';
    }
}

// Event Status (Database mới: upcoming, ongoing, completed, cancelled)
class EventStatus {
    const UPCOMING = 'upcoming';   // Sắp diễn ra
    const ONGOING = 'ongoing';     // Đang diễn ra
    const COMPLETED = 'completed'; // Đã kết thúc
    const CANCELLED = 'cancelled'; // Đã hủy
    
    // Backward compatibility (giữ lại để code cũ vẫn chạy)
    const SAP_DIEN_RA = 'upcoming';
    const DANG_DIEN_RA = 'ongoing';
    const DA_KET_THUC = 'completed';
    const DA_HUY = 'cancelled';
    
    public static function getLabel($status) {
        $labels = [
            self::UPCOMING => 'Sắp diễn ra',
            self::ONGOING => 'Đang diễn ra',
            self::COMPLETED => 'Đã kết thúc',
            self::CANCELLED => 'Đã hủy',
            // Backward compatibility
            'sap_dien_ra' => 'Sắp diễn ra',
            'dang_dien_ra' => 'Đang diễn ra',
            'da_ket_thuc' => 'Đã kết thúc',
            'da_huy' => 'Đã hủy'
        ];
        return $labels[$status] ?? 'Không xác định';
    }
}

// Club Categories (giữ nguyên)
class ClubCategory {
    const ACADEMIC = 'Academic';
    const SPORT = 'Sport';
    const ART = 'Art';
    const VOLUNTEER = 'Volunteer';
    const SKILL = 'Skill';
    const LANGUAGE = 'Language';
    const OTHER = 'Other';
    
    public static function getAll() {
        return [
            self::ACADEMIC,
            self::SPORT,
            self::ART,
            self::VOLUNTEER,
            self::SKILL,
            self::LANGUAGE,
            self::OTHER
        ];
    }
}

// Notification Types (giữ nguyên)
class NotificationType {
    const CLUB_JOIN = 'club_join';
    const CLUB_INVITE = 'invite';
    const EVENT = 'event';
    const EVENT_REMINDER = 'event_reminder';
    const ROLE_CHANGE = 'role_change';
    const SYSTEM = 'system';
}

// HTTP Status Codes (giữ nguyên)
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
