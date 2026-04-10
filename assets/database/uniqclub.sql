-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th4 08, 2026 lúc 11:36 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `uniqclub`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attendance_details`
--

CREATE TABLE `attendance_details` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL COMMENT 'ID buổi điểm danh',
  `member_id` int(11) NOT NULL COMMENT 'ID thành viên (từ bảng members)',
  `status` enum('present','absent_with_permission','absent_without_permission') NOT NULL DEFAULT 'absent_without_permission' COMMENT 'present: có mặt, absent_with_permission: vắng có phép, absent_without_permission: vắng không phép',
  `note` text DEFAULT NULL COMMENT 'Ghi chú (lý do vắng mặt)',
  `updated_by` int(11) DEFAULT NULL COMMENT 'Người cập nhật điểm danh',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu trạng thái điểm danh của từng thành viên trong từng buổi';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL COMMENT 'ID câu lạc bộ',
  `title` varchar(200) DEFAULT NULL COMMENT 'Tiêu đề buổi điểm danh',
  `session_date` date NOT NULL COMMENT 'Ngày diễn ra buổi điểm danh',
  `start_time` time DEFAULT NULL COMMENT 'Thời gian bắt đầu',
  `end_time` time DEFAULT NULL COMMENT 'Thời gian kết thúc',
  `note` text DEFAULT NULL COMMENT 'Ghi chú cho buổi điểm danh',
  `created_by` int(11) NOT NULL COMMENT 'Người tạo buổi điểm danh',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lưu thông tin các buổi điểm danh của CLB';

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `clubs`
--

CREATE TABLE `clubs` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Other',
  `color` varchar(20) DEFAULT '#667eea',
  `founded_date` date DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_members` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `slug`, `description`, `category`, `color`, `founded_date`, `leader_id`, `status`, `created_at`, `updated_at`, `total_members`) VALUES
(1, 'Câu lạc bộ Tình nguyện Những người bạn', '', 'Kết nối - Trái tim - Thiện nguyện', 'Tình nguyện', '#5973e8', '2010-01-13', 23, 'active', '2026-02-06 09:30:22', '2026-04-07 08:51:50', 38),
(2, 'Câu lạc bộ Kỹ năng', 'cu-lc-b-k-nng', 'Tăng Skill tăng độ siêu', 'Học thuật', '#2f52ee', '2013-10-20', 48, 'active', '2026-02-06 09:54:27', '2026-04-07 10:25:55', 10),
(3, 'Dấu chân tình nguyện', 'du-chn-tnh-nguyn', 'Nơi tập trung những con người giàu nhiệt huyết\r\nCống hiến sức trẻ cho Tổ quốc', 'Tình nguyện', '#667eea', '2014-10-05', 80, 'active', '2026-02-06 09:59:09', '2026-04-07 07:13:18', 20),
(4, 'Kết nối trẻ', 'kt-ni-tr', 'Kết nối đam mê – Tỏa sáng tài năng.', 'Nghệ thuật', '#ed263a', '2012-05-21', 88, 'active', '2026-02-06 13:15:18', '2026-04-07 09:37:34', 15),
(5, 'CLB Sách & Hành động', 'clb-sch-hnh-ng', 'Đọc để biết – Hành động để thay đổi.', 'Học thuật', '#85ffa3', '2015-12-12', 101, 'active', '2026-02-06 13:19:10', '2026-04-07 10:08:16', 16),
(6, 'Viết tiếp ước mơ giảng đường', 'vit-tip-c-m-ging-ng', 'Tiếp bước ước mơ – Vững bước giảng đường.', 'Volunteer', '#eae666', NULL, 76, 'active', '2026-02-06 13:23:36', '2026-04-07 14:30:08', 10),
(7, 'CLB Tiếng Trung', 'clb-ting-trung', 'Học tiếng Trung – Kết nối văn hóa.', 'Học thuật', '#ff6842', NULL, 68, 'active', '2026-02-06 13:32:16', '2026-04-07 09:16:55', 10),
(8, 'CLB Khởi nghiệp', 'clb-khi-nghip', 'Khơi nguồn ý tưởng – Kiến tạo tương lai', 'Khởi nghiệp', '#92804f', NULL, 30, 'active', '2026-02-06 13:37:29', '2026-04-07 09:17:35', 11),
(9, 'CLB Điền kinh', 'clb-in-kinh', 'Rèn luyện hôm nay – Khỏe mạnh ngày mai.', 'Thể thao', '#656772', NULL, 57, 'active', '2026-02-06 13:41:22', '2026-04-07 10:14:17', 10),
(10, 'CLB Những nhà giáo trẻ', 'clb-nhng-nh-gio-tr', 'Chắp cánh ước mơ nghề giáo', 'Học thuật', '#66c9ea', NULL, 67, 'active', '2026-02-06 13:47:24', '2026-04-07 10:15:55', 20),
(11, 'CLB Tiếng Việt QNU', 'clb-ting-vit-qnu', 'Học tiếng Việt – Kết nối tình bạn Việt – Lào.', 'Học thuật', '#5466b6', NULL, 92, 'active', '2026-02-06 13:48:46', '2026-04-07 09:44:11', 6),
(12, 'Đội Thanh niên tình nguyện Đại học Quy Nhơn', 'i-thanh-nin-tnh-nguyn-i-hc-quy-nhn', '', 'Tình nguyện', '#6b86ff', '2001-03-26', 47, 'active', '2026-02-06 13:51:52', '2026-04-07 09:15:04', 34),
(13, 'Đội Thanh niên xung kích Khoa CNTT', 'i-thanh-nin-xung-kch-khoa-cntt', 'Nhiệt huyết – Trách nhiệm – Tiên phong', 'Tình nguyện', '#5670f0', NULL, 22, 'active', '2026-02-06 13:53:50', '2026-04-07 08:47:43', 35);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `contacts`
--

INSERT INTO `contacts` (`id`, `club_id`, `email`, `phone`, `website`, `facebook`, `instagram`, `twitter`) VALUES
(1, 1, '', '', '', '', '', ''),
(2, 3, NULL, NULL, '', '', '', ''),
(3, 13, '', '', '', '', '', ''),
(4, 7, NULL, NULL, '', '', '', ''),
(5, 6, '', '', '', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `head_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `departments`
--

INSERT INTO `departments` (`id`, `club_id`, `name`, `description`, `head_id`, `created_at`, `updated_at`) VALUES
(1, 3, 'Ban sự kiện', 'Là bộ phận lên ý tưởng, tổ chức và triển khai các chương trình – sự kiện của CLB.\r\nĐảm bảo các hoạt động phù hợp với mục tiêu tình nguyện, cộng đồng của CLB.\r\nKết nối các ban khác để biến kế hoạch thành hoạt động thực tế.\r\nLên kế hoạch sự kiện: xây dựng concept, timeline, nội dung chương trình.\r\nĐảm bảo sự kiện diễn ra đúng kế hoạch, an toàn, hiệu quả.\r\nQuản lý tiến độ, nhân sự và chất lượng hoạt động.\r\nChịu trách nhiệm về nội dung và hình ảnh của CLB trong sự kiện.', NULL, '2026-04-07 06:49:59', '2026-04-07 06:49:59'),
(2, 3, 'Ban Truyền thông', 'Phụ trách truyền thông, quảng bá hình ảnh và hoạt động của CLB.\r\nXây dựng và duy trì hình ảnh thương hiệu của CLB trên các kênh truyền thông.\r\nLà cầu nối thông tin giữa CLB với sinh viên và cộng đồng.\r\nĐảm bảo thông tin chính xác, kịp thời và phù hợp với định hướng CLB.\r\nGiữ gìn hình ảnh chuyên nghiệp và uy tín của CLB.\r\nĐảm bảo tiến độ truyền thông đúng timeline sự kiện.\r\nChịu trách nhiệm về chất lượng nội dung và sản phẩm truyền thông.', NULL, '2026-04-07 06:51:00', '2026-04-07 06:51:00'),
(3, 3, 'Ban Văn nghệ', 'Phụ trách xây dựng và biểu diễn các tiết mục văn nghệ trong hoạt động, sự kiện của CLB.\r\nTạo không khí sôi động, gắn kết và truyền cảm hứng cho chương trình tình nguyện.\r\nGóp phần nâng cao chất lượng nội dung và trải nghiệm của người tham gia.\r\nLên ý tưởng tiết mục: hát, múa, kịch, hoạt cảnh phù hợp với chủ đề chương trình.\r\nTập luyện và dàn dựng các tiết mục văn nghệ.\r\nChuẩn bị trang phục, đạo cụ, âm nhạc cho biểu diễn.', NULL, '2026-04-07 06:51:39', '2026-04-07 06:51:39'),
(4, 3, 'Ban Tài chính', 'Quản lý nguồn thu – chi tài chính của CLB.\r\nĐảm bảo sử dụng kinh phí minh bạch, hợp lý và hiệu quả.\r\nHỗ trợ các ban trong việc lập và kiểm soát ngân sách cho hoạt động.\r\nĐảm bảo tính minh bạch, chính xác và rõ ràng trong tài chính.\r\nSử dụng ngân sách đúng mục đích, tránh lãng phí.\r\nChịu trách nhiệm về số liệu tài chính và báo cáo.\r\nĐảm bảo các hoạt động có đủ nguồn lực tài chính để triển khai.\r\nTuân thủ quy định của CLB và nhà trường về quản lý tài chính.', NULL, '2026-04-07 06:52:14', '2026-04-07 06:52:14'),
(5, 13, 'Ban Văn nghệ', 'Phụ trách xây dựng và biểu diễn các tiết mục văn nghệ phục vụ hoạt động của đội.\r\nGóp phần tạo không khí sôi nổi, gắn kết tinh thần trong các chương trình.\r\nHỗ trợ nâng cao hình ảnh và chất lượng sự kiện.', NULL, '2026-04-07 07:22:18', '2026-04-07 07:22:18'),
(6, 13, 'Ban Phong trào', 'Phụ trách tổ chức và phát động các hoạt động phong trào trong đội và khoa.\r\nTạo môi trường gắn kết, năng động và nâng cao tinh thần tập thể.\r\nThúc đẩy sự tham gia của sinh viên vào các hoạt động Đoàn – Hội.', NULL, '2026-04-07 07:22:43', '2026-04-07 07:22:43'),
(7, 13, 'Ban Học tập', 'Hỗ trợ hoạt động học tập, học thuật cho thành viên trong đội.\r\nNâng cao kiến thức chuyên môn CNTT và kỹ năng học tập.\r\nTạo môi trường chia sẻ, hỗ trợ lẫn nhau trong học tập.', NULL, '2026-04-07 07:23:08', '2026-04-07 07:23:08'),
(8, 13, 'Tổ 1', 'Là đơn vị nhỏ trực thuộc đội, trực tiếp thực hiện các nhiệm vụ được phân công.\r\nHỗ trợ triển khai các hoạt động, phong trào và sự kiện của đội.\r\nĐảm bảo tính linh hoạt và hiệu quả trong tổ chức nhân sự.', NULL, '2026-04-07 07:23:54', '2026-04-07 07:23:54'),
(9, 13, 'Tổ 2', 'Là đơn vị thực thi nhiệm vụ trực thuộc đội, phối hợp với các tổ khác.\r\nTham gia triển khai hoạt động phong trào, sự kiện và hỗ trợ công tác của khoa.\r\nGóp phần đảm bảo hoạt động của đội diễn ra liên tục và hiệu quả.', NULL, '2026-04-07 07:24:17', '2026-04-07 07:24:17'),
(10, 13, 'Tổ 3', 'Là đơn vị trực thuộc đội, tham gia trực tiếp vào việc triển khai các hoạt động.\r\nHỗ trợ duy trì hoạt động thường xuyên và liên tục của đội.\r\nPhối hợp với các tổ khác nhằm đảm bảo phân bổ nhân lực hợp lý.', NULL, '2026-04-07 07:24:39', '2026-04-07 07:24:39'),
(11, 13, 'Tổ 4', 'Là đơn vị trực thuộc đội, tham gia triển khai các nhiệm vụ được phân công.\r\nĐảm bảo duy trì lực lượng hoạt động liên tục, đặc biệt trong các ca trực và thời điểm cao điểm.\r\nPhối hợp với các tổ khác để tối ưu phân bổ nhân sự.', NULL, '2026-04-07 07:24:58', '2026-04-07 07:24:58'),
(12, 1, 'Ban Văn nghệ', 'Phụ trách xây dựng, dàn dựng và biểu diễn các tiết mục văn nghệ trong các hoạt động của CLB.\r\nTạo không khí sôi động, gần gũi và truyền cảm hứng trong các chương trình tình nguyện.\r\nGóp phần lan tỏa giá trị nhân văn và tinh thần cộng đồng thông qua nghệ thuật.', NULL, '2026-04-07 08:16:33', '2026-04-07 08:16:33'),
(13, 1, 'Ban Truyền thông', 'Phụ trách truyền thông và quảng bá hình ảnh, hoạt động của CLB.\r\nXây dựng và duy trì thương hiệu, độ nhận diện của CLB trong sinh viên và cộng đồng.\r\nLà cầu nối thông tin giữa CLB với người tham gia, nhà tài trợ và cộng đồng.', NULL, '2026-04-07 08:16:50', '2026-04-07 08:16:50'),
(14, 1, 'Ban Tài chính Hậu cần', 'Quản lý tài chính và đảm bảo hậu cần cho các hoạt động của CLB.\r\nCung cấp đầy đủ nguồn lực (kinh phí, vật dụng) để chương trình diễn ra hiệu quả.\r\nĐảm bảo việc sử dụng ngân sách hợp lý, minh bạch và tiết kiệm.', NULL, '2026-04-07 08:17:14', '2026-04-07 08:17:14'),
(15, 1, 'Ban Sự kiện', 'Phụ trách lên ý tưởng, tổ chức và điều phối các chương trình – sự kiện của CLB.\r\nĐảm bảo các hoạt động diễn ra đúng mục tiêu, đúng kế hoạch và hiệu quả.\r\nLà đầu mối triển khai chính, kết nối các ban để thực hiện chương trình.', NULL, '2026-04-07 08:17:39', '2026-04-07 08:17:39'),
(16, 1, 'Ban Kỹ thuật', 'Phụ trách các vấn đề kỹ thuật và công nghệ phục vụ hoạt động của CLB.\r\nĐảm bảo hệ thống, thiết bị và công cụ hỗ trợ hoạt động ổn định, hiệu quả.\r\nỨng dụng CNTT để tối ưu hóa quản lý và tổ chức sự kiện.', NULL, '2026-04-07 08:17:57', '2026-04-07 08:17:57'),
(17, 12, 'Ban Sự kiện', 'Phụ trách lên kế hoạch, tổ chức và điều phối các hoạt động tình nguyện của đội.\r\nĐảm bảo các chương trình diễn ra đúng mục tiêu cộng đồng, đúng tiến độ và hiệu quả.\r\nLà đầu mối triển khai chính, kết nối các ban và lực lượng tham gia.', NULL, '2026-04-07 08:42:24', '2026-04-07 08:42:24'),
(18, 12, 'Ban Văn nghệ', 'Phụ trách xây dựng và biểu diễn các tiết mục văn nghệ trong các hoạt động của đội.\r\nTạo không khí sôi nổi, gắn kết và truyền cảm hứng trong các chương trình tình nguyện.\r\nGóp phần nâng cao chất lượng nội dung và hình ảnh hoạt động.', NULL, '2026-04-07 08:42:49', '2026-04-07 08:42:49'),
(19, 12, 'Ban Truyền thông', 'Phụ trách truyền thông và quảng bá hoạt động của đội.\r\nXây dựng và duy trì hình ảnh, thương hiệu của đội trong sinh viên và cộng đồng.\r\nLà cầu nối thông tin giữa đội với người tham gia và các bên liên quan.', NULL, '2026-04-07 08:43:11', '2026-04-07 08:43:11'),
(20, 8, 'Ban chuyên môn', 'Phụ trách nội dung chuyên môn về khởi nghiệp của CLB.\r\nNghiên cứu, xây dựng và phát triển kiến thức, kỹ năng khởi nghiệp cho thành viên.\r\nĐịnh hướng và hỗ trợ các ý tưởng/dự án startup trong CLB.', NULL, '2026-04-07 09:04:19', '2026-04-07 09:04:19'),
(21, 8, 'Ban truyền thông', 'Phụ trách truyền thông, quảng bá hình ảnh và hoạt động khởi nghiệp của CLB.\r\nXây dựng và phát triển thương hiệu CLB trong cộng đồng sinh viên và doanh nghiệp.\r\nKết nối thông tin giữa CLB với thành viên, đối tác và nhà tài trợ', NULL, '2026-04-07 09:04:54', '2026-04-07 09:04:54'),
(22, 7, 'Ban Hậu cần', 'Phụ trách chuẩn bị cơ sở vật chất và điều kiện tổ chức cho các hoạt động của CLB.\r\nĐảm bảo các chương trình (học thuật, giao lưu, workshop) diễn ra thuận lợi, đầy đủ và đúng kế hoạch.\r\nHỗ trợ các ban khác về vật tư và vận hành thực tế.', NULL, '2026-04-07 09:13:51', '2026-04-07 09:13:51'),
(23, 7, 'Ban Nội dung', 'Phụ trách xây dựng và phát triển nội dung học thuật liên quan đến tiếng Trung.\r\nĐảm bảo chất lượng kiến thức, tài liệu và chương trình học của CLB.\r\nTạo môi trường học tập hiệu quả, thực tiễn và phù hợp trình độ thành viên.', NULL, '2026-04-07 09:14:12', '2026-04-07 09:14:12'),
(24, 7, 'Ban Truyền thông', 'Phụ trách truyền thông và quảng bá hoạt động học tập, giao lưu tiếng Trung của CLB.\r\nXây dựng và phát triển hình ảnh, thương hiệu CLB trong sinh viên.\r\nLà cầu nối thông tin giữa CLB với thành viên và cộng đồng người học tiếng Trung.', NULL, '2026-04-07 09:15:45', '2026-04-07 09:15:45'),
(25, 4, 'Nhạc trẻ', 'Phụ trách xây dựng và biểu diễn các tiết mục nhạc trẻ trong các hoạt động của CLB.\r\nTạo không khí năng động, hiện đại và thu hút sinh viên.\r\nGóp phần tăng sự kết nối, giao lưu và trải nghiệm trong chương trình.', NULL, '2026-04-07 09:22:00', '2026-04-07 09:22:00'),
(26, 4, 'Nhạc truyền thống', 'Phụ trách xây dựng và biểu diễn các tiết mục nhạc truyền thống.\r\nGóp phần bảo tồn, phát huy giá trị văn hóa dân tộc trong hoạt động CLB.\r\nTạo sự đa dạng nội dung bên cạnh các tiết mục hiện đại.', NULL, '2026-04-07 09:22:30', '2026-04-07 09:22:30'),
(27, 4, 'Dance', 'Phụ trách xây dựng và biểu diễn các tiết mục nhảy/dance trong hoạt động của CLB.\r\nTạo không khí sôi động, hiện đại và thu hút sinh viên.\r\nGóp phần tăng tính giải trí, tương tác và kết nối trong chương trình.', NULL, '2026-04-07 09:22:53', '2026-04-07 09:22:53'),
(28, 4, 'Nhạc cụ', 'Phụ trách biểu diễn và hỗ trợ âm nhạc bằng nhạc cụ trong các hoạt động của CLB.\r\nTăng chất lượng âm nhạc và chiều sâu nghệ thuật cho chương trình.\r\nGóp phần tạo không gian âm nhạc chuyên nghiệp và đa dạng', NULL, '2026-04-07 09:23:18', '2026-04-07 09:23:18'),
(29, 4, 'Múa', 'Phụ trách xây dựng và biểu diễn các tiết mục múa trong hoạt động của CLB.\r\nTạo hiệu ứng sân khấu đẹp mắt, giàu cảm xúc và nghệ thuật.\r\nGóp phần làm đa dạng nội dung, tăng tính thẩm mỹ và thu hút cho chương trình.', NULL, '2026-04-07 09:23:39', '2026-04-07 09:23:39'),
(30, 4, 'Kịch', 'Phụ trách xây dựng và biểu diễn các tiết mục kịch/tiểu phẩm trong hoạt động của CLB.\r\nTruyền tải thông điệp, nội dung ý nghĩa thông qua hình thức sân khấu hóa.\r\nTăng chiều sâu nội dung và tính giáo dục – giải trí cho chương trình.', NULL, '2026-04-07 09:24:12', '2026-04-07 09:24:12'),
(31, 4, 'Truyền thông & Sự kiện', 'Phụ trách truyền thông và tổ chức các chương trình, sự kiện của CLB.\r\nĐảm bảo hoạt động được triển khai hiệu quả và lan tỏa rộng rãi.\r\nLà đầu mối kết nối giữa nội dung chương trình và người tham gia', NULL, '2026-04-07 09:24:46', '2026-04-07 09:24:46'),
(32, 5, 'Ban Hạnh phúc', 'Quản lý nhân sự, phòng đọc\r\nKiểm kê, quản lý sách\r\nTham gia lên ý tưởng, lập kế hoạch và thiết kế\r\nTổ chức các hoạt động gắn kết\r\nPhối hợp với các ban khác để đảm bảo chất lượng cho mỗi hoạt động', NULL, '2026-04-07 09:32:17', '2026-04-07 09:32:17'),
(33, 5, 'Ban Truyền thông', 'Truyền thông các hoạt động, sự kiện\r\nViết bài cho fanpage và truyền bá văn hóa đọc\r\nThiết kế hình ảnh, video cho từng đợt sự kiện\r\nPhó nháy ảnh trong tầm tay', NULL, '2026-04-07 09:33:17', '2026-04-07 09:33:17'),
(34, 5, 'Ban Sự kiện', 'Chúng mình sẽ cùng nhau đưa ra những ý tưởng cho hàng tháng\r\nĐiều phối những chương trình to nhỏ của CLB, đảm bảo những chương trình ấy diễn ra đúng timeline\r\nCùng nhau báo cáo những ưu điểm và nhược điểm sau khi tổ chức một sự kiện nào đó rồi để rứt những kinh nghiệm từ đó ra\r\nQuản lý sự kiện diễn ra thành công và bám sát theo kế hoạch phân công', NULL, '2026-04-07 09:35:04', '2026-04-07 09:35:04'),
(35, 11, 'Ban Truyền thông', 'Phụ trách truyền thông, quảng bá hoạt động và giá trị tiếng Việt của CLB.\r\nXây dựng hình ảnh, thương hiệu CLB trong sinh viên và cộng đồng.', NULL, '2026-04-07 09:42:12', '2026-04-07 09:42:12'),
(36, 11, 'Ban Phong trào', 'Tổ chức các hoạt động phong trào, giao lưu liên quan đến tiếng Việt.\r\nTạo môi trường gắn kết và thực hành ngôn ngữ cho thành viên.', NULL, '2026-04-07 09:42:30', '2026-04-07 09:42:30'),
(37, 11, 'Ban Đối nội - Đối ngoại', 'Phụ trách quan hệ nội bộ và kết nối bên ngoài của CLB.\r\nXây dựng mạng lưới đối tác, khách mời, đơn vị liên kết.', NULL, '2026-04-07 09:42:57', '2026-04-07 09:42:57'),
(38, 11, 'Ban Văn nghệ', 'Phụ trách các tiết mục văn nghệ liên quan đến tiếng Việt và văn hóa.\r\nTạo điểm nhấn nghệ thuật và cảm xúc cho chương trình.', NULL, '2026-04-07 09:43:11', '2026-04-07 09:43:11'),
(39, 10, 'Ban Truyền thông', 'Phụ trách truyền thông, quảng bá hoạt động và hình ảnh của CLB.\r\nLan tỏa giá trị giáo dục, hình ảnh người giáo viên trẻ đến sinh viên và cộng đồng.', NULL, '2026-04-07 10:03:56', '2026-04-07 10:03:56'),
(40, 10, 'Ban Sự kiện', 'Phụ trách tổ chức các hoạt động, chương trình đào tạo và giao lưu sư phạm.\r\nĐảm bảo chương trình diễn ra hiệu quả, đúng kế hoạch.', NULL, '2026-04-07 10:04:14', '2026-04-07 10:04:14'),
(41, 10, 'Ban nghiệp vụ sư phạm', 'Phụ trách chuyên môn sư phạm và đào tạo kỹ năng giảng dạy cho thành viên.\r\nNâng cao năng lực giảng dạy, giao tiếp và xử lý tình huống giáo dục.', NULL, '2026-04-07 10:04:42', '2026-04-07 10:04:42'),
(42, 9, 'Ban Vận động', 'Phụ trách tổ chức và thúc đẩy các hoạt động thể thao, đặc biệt là điền kinh trong CLB.\r\nKhuyến khích sinh viên rèn luyện thể chất và tham gia phong trào thể thao.', NULL, '2026-04-07 10:12:41', '2026-04-07 10:12:41'),
(43, 9, 'Ban Truyền thông', 'Phụ trách quảng bá hoạt động và hình ảnh của CLB Điền kinh.\r\nLan tỏa tinh thần thể thao và lối sống năng động đến sinh viên.', NULL, '2026-04-07 10:12:59', '2026-04-07 10:12:59'),
(44, 2, 'Ban Nhân sự', 'Quản lý thành viên và hoạt động nội bộ của CLB.\r\nĐảm bảo sự phân công hợp lý và gắn kết giữa các thành viên.', NULL, '2026-04-07 10:22:51', '2026-04-07 10:22:51'),
(45, 2, 'Ban Truyền thông', 'Phụ trách truyền thông và quảng bá hình ảnh CLB.\r\nLan tỏa các hoạt động và kỹ năng mà CLB tổ chức.', NULL, '2026-04-07 10:23:13', '2026-04-07 10:23:13'),
(46, 2, 'Ban Văn nghệ', 'Phụ trách các tiết mục văn nghệ trong hoạt động CLB.\r\nTạo không khí sôi động, gắn kết thành viên.', NULL, '2026-04-07 10:23:41', '2026-04-07 10:23:41'),
(47, 2, 'Ban Lửa trại – Thắt gút dây', 'Phụ trách các kỹ năng sinh hoạt tập thể ngoài trời.\r\nHướng dẫn và tổ chức hoạt động lửa trại, thắt gút dây.', NULL, '2026-04-07 10:24:06', '2026-04-07 10:24:06'),
(48, 2, 'Ban Kỹ thuật – Truyền thông', 'Hỗ trợ kỹ thuật và thiết bị trong các hoạt động của CLB.\r\nĐảm bảo hệ thống âm thanh, trình chiếu và công nghệ hoạt động ổn định.', NULL, '2026-04-07 10:24:25', '2026-04-07 10:24:25'),
(49, 2, 'Ban Truyền tin', 'Phụ trách truyền đạt thông tin trong các hoạt động kỹ năng và trò chơi lớn.\r\nRèn luyện kỹ năng truyền tin, mật thư, tín hiệu cho thành viên.', NULL, '2026-04-07 10:24:45', '2026-04-07 10:24:45'),
(50, 6, 'ban Đối ngoại', 'Liên hệ và làm việc với các đối tác cho chương trình của CLB.\r\nTìm kiếm nguồn tài trợ và hỗ trợ cho các hoạt động.\r\nPhối hợp tổ chức các chương trình giao lưu, hợp tác với các CLB hoặc tổ chức khác.\r\nHỗ trợ công tác đón tiếp khách mời và đối tác trong sự kiện.', NULL, '2026-04-07 10:29:25', '2026-04-07 10:29:25'),
(51, 6, 'ban Văn nghệ-Thể dục thể thao', 'Phụ trách các hoạt động văn nghệ và thể dục thể thao trong CLB.\r\nTạo môi trường sinh hoạt năng động, gắn kết thành viên.', NULL, '2026-04-07 10:30:27', '2026-04-07 10:30:27'),
(52, 6, 'ban Tổ chức - Kế hoạch', 'Phụ trách lập kế hoạch và tổ chức các chương trình, hoạt động của CLB.\r\nĐiều phối các ban để triển khai hoạt động đúng tiến độ và mục tiêu.', NULL, '2026-04-07 10:30:53', '2026-04-07 10:30:53'),
(53, 6, 'ban Tài chính', 'Phụ trách quản lý nguồn thu – chi và ngân sách của CLB.\r\nĐảm bảo tài chính minh bạch và sử dụng hiệu quả.', NULL, '2026-04-07 10:31:13', '2026-04-07 10:31:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `short_desc` text DEFAULT NULL,
  `full_desc` longtext DEFAULT NULL,
  `cover_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `reg_deadline` datetime DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `events`
--

INSERT INTO `events` (`id`, `club_id`, `name`, `slug`, `short_desc`, `full_desc`, `cover_id`, `location`, `start_time`, `end_time`, `max_participants`, `reg_deadline`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 'Chào đón Tân thành viên', '', '🌟 CHÀO ĐÓN TÂN THÀNH VIÊN ĐỢT 2 – NHỮNG MẢNH GHÉP MỚI 👣💙', '🌿 Tháng 4 ghé đến, mang theo những khởi đầu mới và nguồn năng lượng tươi trẻ. Sau hành trình tuyển chọn, đại gia đình Dấu Chân Tình Nguyện chính thức chào đón những gương mặt mới – những trái tim nhiệt huyết sẵn sàng đồng hành cùng CLB trên chặng đường phía trước 🏠\r\n\r\n🥰 DCTNers ơi, đã đến lúc chúng ta lại được quây quần bên nhau, cùng trò chuyện, kết nối và chào đón những người bạn mới. Sự xuất hiện của các bạn sẽ mang đến những màu sắc mới, góp phần làm hành trình của CLB thêm rực rỡ và ý nghĩa hơn \r\n\r\n🌸 Buổi sinh hoạt lần này không chỉ là dịp để làm quen, gắn kết mà còn giúp các bạn hiểu rõ hơn về CLB và định hướng hoạt động trong thời gian tới. Bên cạnh đó, những trò chơi thú vị và khoảnh khắc vui vẻ chắc chắn sẽ giúp chúng ta xích lại gần nhau hơn.\r\n\r\n⏰ Thời gian: 18h45, Thứ 4 ngày 1/04/2026\r\n📍 Địa điểm: A8.14\r\n👕 Trang phục: Áo Đoàn/Áo CLB, quần tối màu,  \r\ndép có quai hậu hoặc giày, đeo thẻ thành viên.\r\n⚠️ Lưu ý: Đây là hoạt động có điểm danh. Nếu không thể tham gia, vui lòng báo trước cho Tổ trưởng/Trưởng ban.\r\n\r\n💙 Hẹn gặp tất cả mọi người trong buổi sinh hoạt đặc biệt này – nơi chúng ta cùng nhau bắt đầu một hành trình mới đầy ý nghĩa 🌷\r\n\r\n💫Tik Tok CLB: https://www.tiktok.com/@clb.dctn?_r=1&amp;_t=ZS-91SlzggrCDw\r\n🗞️Confession CLB: Dấu Chân Tình Nguyện Confessions  \r\n-----------------------------------------------------------------------------------\r\nMọi thắc mắc xin vui lòng liên hệ:\r\n☎️SĐT: 0912422104 - Nguyên Hồng (Chủ nhiệm CLB)\r\n📧Email: dctnqn@gmail.com\r\n🌐Website CLB: https://dctnqn.io.vn\r\nTrân trọng!!', 16, 'Giảng đường A8.14', '2026-04-01 18:45:00', '2026-04-01 21:00:00', 45, '0000-00-00 00:00:00', 'completed', 80, '2026-03-28 06:30:26', '2026-04-07 07:15:10'),
(2, 13, 'Team Building 2026', 'team-building-2026', '🔥 TEAM BUILDING – THANH NIÊN XUNG KÍCH KHOA CÔNG NGHỆ THÔNG TIN 🔥', 'Một ngày không chỉ là trải nghiệm, mà còn là hành trình lưu giữ những khoảnh khắc đáng nhớ 💙\r\nTừ những thử thách sôi động đến những tiếng cười rộn ràng, tất cả đã tạo nên một tập thể gắn kết và đầy nhiệt huyết.\r\n\r\nMỗi hoạt động là một lần cùng nhau vượt qua giới hạn, mỗi khoảnh khắc là một dấu ấn của tinh thần đoàn kết và xung kích 💪\r\nĐó cũng chính là điều làm nên bản sắc riêng của Thanh niên xung kích – luôn năng động, trách nhiệm và sẵn sàng cống hiến.\r\n\r\nĐội Thanh niên xung kích - Khoa CNTT QNU\r\n\r\nCùng nhau nhìn lại và bứt phá hơn nữa trong năm 2026 🚀', 29, 'Bãi biển Quy Hòa', '2026-03-15 07:30:00', '2026-03-15 19:30:00', 70, '2026-03-14 18:00:00', 'completed', 22, '2026-04-07 07:32:46', '2026-04-07 07:56:38'),
(3, 1, 'Tuyển thành viên đợt 2', 'tuyn-thnh-vin-t-2', '📢 THÔNG BÁO TUYỂN THÀNH VIÊN ĐỢT 2', 'Bạn có đang tìm mảnh ghép còn thiếu của mình?\r\n\r\nCâu lạc bộ đang mở đợt tuyển thành viên lần 2 dành cho những bạn mang trong mình ngọn lửa tình nguyện và nhiệt huyết của tuổi trẻ. \r\n\r\nKhi tham gia, bạn sẽ được cùng nhau lan tỏa yêu thương, trải nghiệm nhiều hoạt động ý nghĩa và trở thành một phần của Đại gia đình NNB. 🔥💙\r\n\r\n⏰ Thời gian: 11/03/2026, 18h00 – 20h30\r\n📍 Địa điểm: Phòng A3.101 – 102\r\n\r\nNếu bạn sẵn sàng cháy hết mình với tuổi trẻ, chúng mình đang chờ bạn! ✨\r\n\r\n------‐-------------------------------------\r\n🔎THÔNG TIN LIÊN HỆ:\r\n📧Email: clbnhungnguoiban.qnu@gmail.com\r\n☎️SĐT: 0974769324 (Thùy Duyên) - 0368148392 (Nhật Quang)\r\n\r\n#CLBTinhnguyenNhungNguoiBan\r\n#NNB \r\n#GiaDinhNNB\r\n#TuyenThanhVienDot2\r\n#NhietHuyetTuoiTre', 37, 'A3.101-102', '2026-03-11 18:00:00', '2026-03-11 21:00:00', 70, '2026-03-10 18:00:00', 'completed', 23, '2026-04-07 08:21:34', '2026-04-07 08:33:43'),
(10, 13, 'Sinh hoạt tháng 1', 'sinh-hot-thng-1', '🔥🔥🔥 SINH HOẠT THÁNG 1 &amp; GIẢI ĐẤU LIÊN QUÂN MOBILE 🔥🔥🔥', '✨ Sinh hoạt tháng 1 là dịp để Đội TNXK cùng nhìn lại chặng đường hoạt động trong thời gian qua, chia sẻ những kết quả đã đạt được, đồng thời tạo không gian gặp gỡ, giao lưu thân mật giữa các thành viên. Sau những ngày học tập và làm việc căng thẳng, buổi sinh hoạt đã mang đến bầu không khí ấm áp, vui tươi và đầy năng lượng tích cực. 💖\r\n\r\n🎮 Vừa qua, Đội TNXK đã tổ chức thành công giải đấu Esports “Liên Quân Mobile” dành cho toàn thể thành viên trong đội. Đây không chỉ là sân chơi giải trí bổ ích mà còn là cơ hội để các bạn thể hiện kỹ năng, niềm đam mê với bộ môn thể thao điện tử đang được yêu thích hiện nay. 💪\r\n\r\n🔥 Trong không khí sôi động và hào hứng, các trận đấu diễn ra vô cùng kịch tính, gay cấn nhưng không kém phần vui vẻ. Giải đấu không chỉ mang đến những phút giây thư giãn mà còn góp phần gắn kết tinh thần đồng đội, tạo nên nhiều kỷ niệm đáng nhớ giữa các thành viên TNXK.\r\n\r\n❣ Đội TNXK xin gửi lời cảm ơn chân thành đến tất cả các thành viên đã nhiệt tình tham gia và cổ vũ cho giải đấu. Đặc biệt, xin cảm ơn BTC cùng những cá nhân đã đóng góp công sức để chương trình được diễn ra thành công tốt đẹp. \r\n\r\n🌟 Hy vọng rằng sinh hoạt tháng 1 và giải đấu Liên Quân Mobile sẽ là khởi đầu đầy hứng khởi cho chuỗi hoạt động sôi nổi, ý nghĩa hơn nữa trong thời gian tới.\r\n\r\n👉 Hãy cùng chờ đón những chương trình tiếp theo và tiếp tục đồng hành cùng Đội TNXK trên hành trình chinh phục những thành công mới!\r\n📸 Dưới đây là một số hình ảnh đáng nhớ của chương trình 👇', 35, 'Giảng đường A8.14', '2026-01-23 19:30:00', '2026-01-23 21:00:00', 50, '0000-00-00 00:00:00', 'completed', 22, '2026-04-07 08:03:08', '2026-04-07 08:12:31'),
(12, 4, 'Tuyển thành viên đợt 2', 'tuyn-thnh-vin-t-2-1775555349', '💗 CHÍNH THỨC MỞ ĐƠN ĐĂNG KÍ TUYỂN THÀNH VIÊN ĐỢT 2 CLB KẾT NỐI TRẺ QNU.', '💥 Đến hẹn lại lên, CLB Kết Nối Trẻ lại náo nức đi tìm những mảnh ghép mới.\r\n\r\n🤔 Bạn yêu thích hoạt động phong trào, đam mê sân khấu hoặc muốn góp sức phía sau ánh đèn để tạo nên một tiết mục bùng nổ? Dù ở vị trí nào, chỉ cần bạn nhiệt huyết và sẵn sàng cống hiến, CLB Kết Nối Trẻ luôn nồng nhiệt chào đón!\r\n\r\n🤔Có bao giờ bạn muốn thử thách bản thân, làm điều gì đó điên rồ nhưng rực rỡ cho thanh xuân? KNT sẽ giúp bạn làm điều đó. CLB chúng tớ gồm 7 mảng:\r\n✨Mảng Nhạc trẻ\r\n✨Mảng Nhạc truyền thống\r\n✨Mảng Dance\r\n✨Mảng Múa\r\n✨Mảng Kịch\r\n✨Mảng Nhạc cụ\r\n✨Mảng Truyền thông &amp; Sự kiện\r\n\r\n🔈 Còn chần chừ gì nữa mà không nhanh tay đăng ký để nhận buổi phỏng vấn!!!\r\n\r\n📝 Link đăng ký: https://forms.gle/yNMiEJDaKHEk9EcY6\r\n⏰ Hạn đăng ký: 12h00 ngày 10/4/2026\r\n🔥 Nếu bạn sẵn sàng cháy hết mình, đừng ngần ngại điền form đăng ký ngay hôm nay!\r\n\r\n⚡ Kết Nối Trẻ – Kết nối đam mê ⚡\r\n---------------------------------------------- \r\n☎Chi tiết liên hệ: \r\n🚩Trang fanpage chính thức CLB Kết Nối Trẻ QNU :\r\nhttps://www.facebook.com/clbketnoitreqnu\r\n💌 Email : clbketnoitreqnu@gmail.com\r\n#clbketnoitre #CLBKETNOITRE #kntqnu #KNTQNU', 39, 'Thông báo sau', '2026-04-11 19:00:00', '2026-04-11 21:00:00', 60, '0000-00-00 00:00:00', 'upcoming', 88, '2026-04-07 09:49:09', '2026-04-07 09:49:09'),
(13, 7, 'Ngày hội văn hóa Phương Đông và Việt Nam', 'ngy-hi-vn-ha-phng-ng-v-vit-nam', '🌏✨ [THÔNG BÁO] GHÉ THĂM GIAN TRẠI CLB TIẾNG TRUNG ✨🌏', '🎐 Bạn đã có kế hoạch gì cho Ngày hội Văn hóa Phương Đông &amp; Việt Nam chưa?\r\nNếu chưa, thì đừng bỏ lỡ cơ hội “du hành” đến một không gian đậm sắc màu Trung Hoa ngay giữa khuôn viên trường nhé!\r\n\r\n🎎 Gian trại của CLB Tiếng Trung hứa hẹn sẽ mang đến cho bạn:\r\n✨ Những trò chơi thú vị, dễ chơi – dễ “ghiền” do chính CLB tổ chức\r\n✨ Cơ hội trải nghiệm văn hóa Trung Hoa độc đáo\r\n✨ Những phần quà nhỏ xinh nhưng đầy bất ngờ\r\n✨ Không gian check-in đậm chất cổ phong cực “xịn sò”\r\n\r\n💫 Dù bạn là người yêu thích tiếng Trung hay chỉ đơn giản muốn khám phá điều mới mẻ, gian trại của chúng mình luôn chào đón bạn!\r\n\r\n📍 Thời gian: 22/03\r\n📍 Địa điểm: Sân trước Hội trường B\r\n\r\n💌 Hãy rủ hội bạn thân và ghé qua gian trại để cùng vui, cùng chơi và lưu lại những khoảnh khắc thật đáng nhớ nhé!\r\n\r\n———\r\n\r\n🌏✨【通知】欢迎来到汉语俱乐部展位 ✨🌏\r\n\r\n🎐 你已经为东方与越南文化节做好计划了吗？\r\n如果还没有，那一定不要错过这次“沉浸式”体验中华文化的机会！\r\n\r\n🎎 汉语俱乐部的展位将为你带来：\r\n✨ 轻松有趣、让人上瘾的互动游戏\r\n✨ 独特的中华文化体验\r\n✨ 精美的小礼物等你领取\r\n✨ 超有氛围感的古风打卡空间\r\n\r\n💫 无论你是否学习过汉语，只要你对新鲜事物充满好奇，我们都欢迎你的到来！\r\n\r\n📍 时间：3月22日\r\n📍 地点：B礼堂前广场\r\n\r\n💌 约上你的朋友，一起来玩、一起打卡，留下美好的回忆吧！\r\n______________________\r\n 归仁大学汉语俱乐部\r\n汉语不太难😜\r\n💌clbtiengtrungqnu23@gmail.com\r\n🎬tiktok:clbtiengtrungqnu\r\n📍Fanpage:https://www.facebook.com/profile.php?id=100090946006078&amp;mibextid=LQQJ4d\r\n#ngonngutrungquoc\r\n#daihocquynhon #caulacbotiengtrung #qnu #汉语不太难', 40, 'Sân trước Hội trường B', '2026-03-22 07:00:00', '2026-03-22 18:00:00', 60, '0000-00-00 00:00:00', 'completed', 68, '2026-04-07 11:45:29', '2026-04-07 11:46:15'),
(14, 12, 'Tuyển tình nguyện viên hỗ trợ Hội thao QNU 2026', 'tuyn-tnh-nguyn-vin-h-tr-hi-thao-qnu-2026', '🔥 TUYỂN TÌNH NGUYỆN VIÊN HỖ TRỢ HỘI THAO QNU 2026 🔥', '🏃‍♀️ Sân đã sẵn sàng.\r\n🔥 Không khí đã nóng lên.\r\nCòn bạn – đã sẵn sàng “nhập cuộc” chưa? \r\n👉Hội Sinh viên Trường Đại học Quy Nhơn phối hợp với Đội Thanh Niên Tình Nguyện QNU chính thức tìm kiếm những “chiến binh tình nguyện năng động” cho HỘI THAO TRƯỜNG ĐẠI HỌC QUY NHƠN 2026!\r\n⏰ Thông tin về hội thao:\r\n📍 Thời gian hội thao: 22/3/2026 – 04/4/2026\r\n📍 Địa điểm: Cụm sân thể thao &amp; Nhà thi đấu đa năng Trường Đại học Quy Nhơn\r\n⚡ Khi tham gia bạn sẽ được:\r\n • Trực tiếp hỗ trợ tại các khu vực thi đấu\r\n • Hỗ trợ vận hành sự kiện lớn nhất nhì trường\r\n • Trải nghiệm không khí của một Hội thao “cực cháy”\r\n🎯 Bạn cần có:\r\n✔️ Nhiệt tình + trách nhiệm\r\n✔️ Tinh thần teamwork cao\r\n🎯 Quyền lợi:\r\n✔️ Giấy chứng nhận hoạt động\r\n✔️ Kỹ năng tổ chức – làm việc nhóm \r\n✔️ Kết nối bạn bè, mở rộng mối quan hệ\r\n✔️ Một kỷ niệm thanh xuân đáng nhớ\r\n💌 Cách thức đăng ký:\r\n Đã đủ số lượng TNV\r\n📣 Tuổi trẻ là những lần dám thử, dám dấn thân và dám cống hiến. Đừng bỏ lỡ cơ hội trở thành một phần của Hội thao QNU – nơi nhiệt huyết được lan tỏa và dấu ấn thanh xuân được viết nên!\r\n💚 Đội Thanh Niên Tình Nguyện QNU luôn chờ bạn!\r\n-----------------------------------------------------------\r\n🔎Đội Thanh Niên Tình Nguyện                                                                                              📩 Email: doithanhnientinhnguyenqnu@gmail.com\r\nLink Facebook: https://www.facebook.com DTNTNQNU/\r\n☎ SĐT: 0376217236 (Đỗ Minh Tú)\r\n#DoiThanhNienTinhNguyenQNU \r\n#TuyenTinhNguyenVien\r\n#HoiThaoQNU2026', 41, 'Cụm sân thể thao và Nhà thi đấu đa năng Trường Đại học Quy Nhơn', '2026-03-22 06:00:00', '2026-04-04 18:00:00', 40, '0000-00-00 00:00:00', 'upcoming', 47, '2026-04-07 11:51:46', '2026-04-07 11:51:46'),
(15, 6, 'Giao lưu tri thức: Văn hóa đọc', 'giao-lu-tri-thc-vn-ha-c', '🌟 GIAO LƯU TRI THỨC: KHI CDU “CẬP BẾN” THƯ VIỆN 📚', 'Sáng ngày 22/12/2025, các thành viên CDU đã có một hành trình trải nghiệm đầy cảm hứng tại sự kiện “Văn hoá đọc trong môi trường Sư phạm và Toán học hiện đại” do Trung tâm Số và Học liệu tổ chức.\r\n💡 Điểm chạm giữa Tư duy và Trang sách\r\nBuổi tọa đàm với sự dẫn dắt của TS. Võ Văn Duyên Em và TS. Trần Ngọc Nguyên đã mở ra góc nhìn mới: Toán học là ngôn ngữ tư duy, còn Sách là người bạn nuôi dưỡng tâm hồn sư phạm. Sự kết hợp này giúp sinh viên chúng mình tự tin làm chủ tri thức hiện đại. 🚀\r\n🎯 Những trải nghiệm giá trị tại sự kiện\r\nTại không gian Phòng Đọc mở 1, team CDU đã cùng nhau:\r\n🔍 Khai thác tri thức: Tập huấn kỹ năng sử dụng tài nguyên thư viện và CSDL MathSciNet.\r\n✍️ Lên tiếng vì cộng đồng: Đề xuất những đầu sách thiết thực theo nhu cầu thực tế của sinh viên.\r\n🎁 Kết nối niềm vui: Tham gia mini game và nhận về những món quà xinh xắn từ Thư viện.\r\n🤝 Kết nối và Lan tỏa\r\nSự kiện là nhịp cầu ý nghĩa kết nối Thư viện – Giảng viên – Sinh viên. CDU tin rằng văn hóa đọc chính là chìa khóa để vận dụng tri thức vào thực tiễn giảng dạy sau này. 🏫\r\n💌 Chân thành cảm ơn Trung tâm Số và Học liệu đã tổ chức một chương trình ý nghĩa. Hẹn gặp lại các bạn ở những hành trình tri thức tiếp theo! 💖\r\n👉Mọi thắc mắc xin liên hệ:\r\n🪩Link fanpage: https://www.facebook.com/viettiepuocmogiangduong\r\nEmail: luutrucdu@gmail.com\r\nLink youtube: https://m.youtube.com/channel/UCcmOiDb48K0rm_QoXeUQCNw?fbclid=IwAR0HM_uyRWqj3LK3WvKoooFn9svghgQWtJzcB2vh4b0qZ65a5I66KiHDXl0\r\n☎️Hotline : 0869803657 (chủ nhiệm Duyên)', 42, 'Thư viện trường Đại học Quy Nhơn', '2025-12-22 08:30:00', '2025-12-22 11:00:00', 35, '0000-00-00 00:00:00', 'upcoming', 76, '2026-04-07 14:13:52', '2026-04-07 14:13:52');

--
-- Bẫy `events`
--
DELIMITER $$
CREATE TRIGGER `update_stats_after_event_insert` AFTER INSERT ON `events` FOR EACH ROW BEGIN
    INSERT INTO stats (club_id, total_events, updated_at) 
    VALUES (NEW.club_id, 1, NOW())
    ON DUPLICATE KEY UPDATE 
        total_events = total_events + 1,
        updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','approved','cancelled','checked_in') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `user_id`, `status`, `notes`, `registered_at`, `updated_at`) VALUES
(13, 1, 11, 'approved', 'Đăng ký tham gia', '2026-03-29 01:00:00', '2026-04-07 07:09:09'),
(14, 1, 87, 'approved', 'Đăng ký tham gia', '2026-03-29 01:30:00', '2026-04-07 07:09:09'),
(15, 1, 31, 'approved', 'Đăng ký tham gia', '2026-03-29 02:00:00', '2026-04-07 07:09:09'),
(16, 1, 62, 'approved', 'Đăng ký tham gia', '2026-03-29 02:30:00', '2026-04-07 07:09:09'),
(17, 1, 7, 'approved', 'Đăng ký tham gia', '2026-03-29 03:00:00', '2026-04-07 07:09:09'),
(18, 1, 98, 'approved', 'Đăng ký tham gia', '2026-03-29 03:30:00', '2026-04-07 07:09:09'),
(19, 1, 82, 'approved', 'Đăng ký tham gia', '2026-03-30 01:00:00', '2026-04-07 07:09:09'),
(20, 1, 97, 'approved', 'Đăng ký tham gia', '2026-03-30 02:00:00', '2026-04-07 07:09:09'),
(21, 1, 64, 'approved', 'Đăng ký tham gia', '2026-03-31 01:00:00', '2026-04-07 07:09:09'),
(22, 1, 20, 'approved', 'Đăng ký tham gia', '2026-03-31 02:00:00', '2026-04-07 07:09:09'),
(23, 1, 75, 'approved', 'Đăng ký tham gia', '2026-03-31 03:00:00', '2026-04-07 07:13:30'),
(24, 1, 5, 'approved', 'Đăng ký tham gia', '2026-03-31 03:30:00', '2026-04-07 07:13:30'),
(25, 1, 18, 'approved', 'Đăng ký tham gia', '2026-03-31 04:00:00', '2026-04-07 07:13:30'),
(26, 1, 50, 'approved', 'Đăng ký tham gia', '2026-03-31 04:30:00', '2026-04-07 07:13:30'),
(27, 1, 51, 'approved', 'Đăng ký tham gia', '2026-03-31 06:00:00', '2026-04-07 07:13:30'),
(28, 1, 33, 'approved', 'Đăng ký tham gia', '2026-03-31 06:30:00', '2026-04-07 07:13:30'),
(29, 1, 43, 'approved', 'Đăng ký tham gia', '2026-03-31 07:00:00', '2026-04-07 07:13:30'),
(30, 1, 105, 'approved', 'Đăng ký tham gia', '2026-03-31 07:30:00', '2026-04-07 07:13:30'),
(31, 1, 91, 'approved', 'Đăng ký tham gia', '2026-03-31 08:00:00', '2026-04-07 07:13:30'),
(32, 1, 35, 'approved', 'Đăng ký tham gia', '2026-03-31 08:30:00', '2026-04-07 07:13:30'),
(33, 2, 101, 'approved', 'Đăng ký tham gia', '2026-03-12 03:00:00', '2026-04-07 07:58:38'),
(34, 2, 53, 'approved', 'Đăng ký tham gia', '2026-03-12 03:30:00', '2026-04-07 07:58:38'),
(35, 2, 93, 'approved', 'Đăng ký tham gia', '2026-03-12 04:00:00', '2026-04-07 07:58:38'),
(36, 2, 47, 'approved', 'Đăng ký tham gia', '2026-03-12 04:30:00', '2026-04-07 07:58:38'),
(37, 2, 26, 'approved', 'Đăng ký tham gia', '2026-03-12 06:00:00', '2026-04-07 07:58:38'),
(38, 2, 48, 'approved', 'Đăng ký tham gia', '2026-03-12 06:30:00', '2026-04-07 07:58:38'),
(39, 2, 42, 'approved', 'Đăng ký tham gia', '2026-03-12 07:00:00', '2026-04-07 07:58:38'),
(40, 2, 70, 'approved', 'Đăng ký tham gia', '2026-03-12 07:30:00', '2026-04-07 07:58:38'),
(41, 2, 27, 'approved', 'Đăng ký tham gia', '2026-03-12 08:00:00', '2026-04-07 07:58:38'),
(42, 2, 54, 'approved', 'Đăng ký tham gia', '2026-03-12 08:30:00', '2026-04-07 07:58:38'),
(43, 2, 69, 'approved', 'Đăng ký tham gia', '2026-03-13 03:00:00', '2026-04-07 07:58:38'),
(44, 2, 46, 'approved', 'Đăng ký tham gia', '2026-03-13 03:30:00', '2026-04-07 07:58:38'),
(45, 2, 99, 'approved', 'Đăng ký tham gia', '2026-03-13 04:00:00', '2026-04-07 07:58:38'),
(46, 2, 76, 'approved', 'Đăng ký tham gia', '2026-03-13 04:30:00', '2026-04-07 07:58:38'),
(47, 2, 77, 'approved', 'Đăng ký tham gia', '2026-03-13 06:00:00', '2026-04-07 07:58:38'),
(48, 2, 45, 'approved', 'Đăng ký tham gia', '2026-03-13 06:30:00', '2026-04-07 07:58:38'),
(49, 2, 38, 'approved', 'Đăng ký tham gia', '2026-03-13 07:00:00', '2026-04-07 07:58:38'),
(50, 2, 52, 'approved', 'Đăng ký tham gia', '2026-03-13 07:30:00', '2026-04-07 07:58:38'),
(51, 2, 73, 'approved', 'Đăng ký tham gia', '2026-03-13 08:00:00', '2026-04-07 07:58:38'),
(52, 2, 18, 'approved', 'Đăng ký tham gia', '2026-03-14 03:00:00', '2026-04-07 07:58:38'),
(53, 2, 19, 'approved', 'Đăng ký tham gia', '2026-03-14 03:30:00', '2026-04-07 07:58:38'),
(54, 2, 6, 'approved', 'Đăng ký tham gia', '2026-03-14 04:00:00', '2026-04-07 07:58:38'),
(55, 2, 63, 'approved', 'Đăng ký tham gia', '2026-03-14 04:30:00', '2026-04-07 07:58:38'),
(56, 2, 75, 'approved', 'Đăng ký tham gia', '2026-03-14 06:00:00', '2026-04-07 07:58:38'),
(57, 2, 82, 'approved', 'Đăng ký tham gia', '2026-03-14 06:30:00', '2026-04-07 07:58:38'),
(58, 2, 61, 'approved', 'Đăng ký tham gia', '2026-03-14 07:00:00', '2026-04-07 07:58:38'),
(59, 2, 86, 'approved', 'Đăng ký tham gia', '2026-03-14 07:30:00', '2026-04-07 07:58:38'),
(60, 2, 66, 'approved', 'Đăng ký tham gia', '2026-03-14 08:00:00', '2026-04-07 07:58:38'),
(61, 2, 94, 'approved', 'Đăng ký tham gia', '2026-03-14 03:00:00', '2026-04-07 07:58:38'),
(62, 2, 29, 'approved', 'Đăng ký tham gia', '2026-03-14 03:30:00', '2026-04-07 07:58:38'),
(63, 2, 71, 'approved', 'Đăng ký tham gia', '2026-03-14 04:00:00', '2026-04-07 07:58:38'),
(64, 2, 17, 'approved', 'Đăng ký tham gia', '2026-03-14 04:30:00', '2026-04-07 07:58:38'),
(65, 2, 103, 'approved', 'Đăng ký tham gia', '2026-03-14 06:00:00', '2026-04-07 07:58:38'),
(66, 2, 21, 'approved', 'Đăng ký tham gia', '2026-03-14 06:30:00', '2026-04-07 07:58:38'),
(67, 10, 42, 'approved', 'Đăng ký tham gia', '2026-01-15 01:00:00', '2026-04-07 08:11:53'),
(68, 10, 76, 'approved', 'Đăng ký tham gia', '2026-01-15 01:30:00', '2026-04-07 08:11:53'),
(69, 10, 27, 'approved', 'Đăng ký tham gia', '2026-01-15 02:00:00', '2026-04-07 08:11:53'),
(70, 10, 71, 'approved', 'Đăng ký tham gia', '2026-01-15 02:30:00', '2026-04-07 08:11:53'),
(71, 10, 66, 'approved', 'Đăng ký tham gia', '2026-01-15 03:00:00', '2026-04-07 08:11:53'),
(72, 10, 101, 'approved', 'Đăng ký tham gia', '2026-01-16 01:00:00', '2026-04-07 08:11:53'),
(73, 10, 49, 'approved', 'Đăng ký tham gia', '2026-01-16 01:30:00', '2026-04-07 08:11:53'),
(74, 10, 73, 'approved', 'Đăng ký tham gia', '2026-01-16 02:00:00', '2026-04-07 08:11:53'),
(75, 10, 48, 'approved', 'Đăng ký tham gia', '2026-01-16 02:30:00', '2026-04-07 08:11:53'),
(76, 10, 103, 'approved', 'Đăng ký tham gia', '2026-01-16 03:00:00', '2026-04-07 08:11:53'),
(77, 10, 93, 'approved', 'Đăng ký tham gia', '2026-01-17 01:00:00', '2026-04-07 08:11:53'),
(78, 10, 82, 'approved', 'Đăng ký tham gia', '2026-01-17 01:30:00', '2026-04-07 08:11:53'),
(79, 10, 61, 'approved', 'Đăng ký tham gia', '2026-01-17 02:00:00', '2026-04-07 08:11:53'),
(80, 10, 77, 'approved', 'Đăng ký tham gia', '2026-01-17 02:30:00', '2026-04-07 08:11:53'),
(81, 10, 17, 'approved', 'Đăng ký tham gia', '2026-01-17 03:00:00', '2026-04-07 08:11:53'),
(82, 10, 46, 'approved', 'Đăng ký tham gia', '2026-01-18 01:00:00', '2026-04-07 08:11:53'),
(83, 10, 38, 'approved', 'Đăng ký tham gia', '2026-01-18 01:30:00', '2026-04-07 08:11:53'),
(84, 10, 53, 'approved', 'Đăng ký tham gia', '2026-01-18 02:00:00', '2026-04-07 08:11:53'),
(85, 10, 21, 'approved', 'Đăng ký tham gia', '2026-01-18 02:30:00', '2026-04-07 08:11:53'),
(86, 10, 54, 'approved', 'Đăng ký tham gia', '2026-01-18 03:00:00', '2026-04-07 08:11:53'),
(87, 10, 69, 'approved', 'Đăng ký tham gia', '2026-01-16 02:00:00', '2026-04-07 08:11:53'),
(88, 10, 63, 'approved', 'Đăng ký tham gia', '2026-01-16 02:30:00', '2026-04-07 08:11:53'),
(89, 10, 26, 'approved', 'Đăng ký tham gia', '2026-01-16 03:00:00', '2026-04-07 08:11:53'),
(90, 10, 75, 'approved', 'Đăng ký tham gia', '2026-01-17 01:00:00', '2026-04-07 08:11:53'),
(91, 10, 18, 'approved', 'Đăng ký tham gia', '2026-01-17 01:30:00', '2026-04-07 08:11:53'),
(92, 10, 29, 'approved', 'Đăng ký tham gia', '2026-01-17 02:00:00', '2026-04-07 08:11:53'),
(93, 10, 52, 'approved', 'Đăng ký tham gia', '2026-01-17 02:30:00', '2026-04-07 08:11:53'),
(94, 10, 70, 'approved', 'Đăng ký tham gia', '2026-01-17 03:00:00', '2026-04-07 08:11:53'),
(95, 10, 86, 'approved', 'Đăng ký tham gia', '2026-01-18 01:00:00', '2026-04-07 08:11:53'),
(96, 10, 47, 'approved', 'Đăng ký tham gia', '2026-01-18 01:30:00', '2026-04-07 08:11:53'),
(97, 10, 94, 'approved', 'Đăng ký tham gia', '2026-01-18 02:00:00', '2026-04-07 08:11:53'),
(98, 10, 19, 'approved', 'Đăng ký tham gia', '2026-01-18 02:30:00', '2026-04-07 08:11:53'),
(99, 10, 45, 'approved', 'Đăng ký tham gia', '2026-01-18 03:00:00', '2026-04-07 08:11:53'),
(219, 3, 2, 'approved', 'Đăng ký tham gia', '2026-02-25 01:30:00', '2026-04-07 09:00:51'),
(220, 3, 3, 'approved', 'Đăng ký tham gia', '2026-02-25 02:00:00', '2026-04-07 09:00:51'),
(221, 3, 4, 'approved', 'Đăng ký tham gia', '2026-02-25 02:30:00', '2026-04-07 09:00:51'),
(222, 3, 5, 'approved', 'Đăng ký tham gia', '2026-02-25 03:00:00', '2026-04-07 09:00:51'),
(223, 3, 6, 'approved', 'Đăng ký tham gia', '2026-02-25 03:30:00', '2026-04-07 09:00:51'),
(224, 3, 7, 'approved', 'Đăng ký tham gia', '2026-02-25 04:00:00', '2026-04-07 09:00:51'),
(225, 3, 8, 'approved', 'Đăng ký tham gia', '2026-02-25 04:30:00', '2026-04-07 09:00:51'),
(226, 3, 9, 'approved', 'Đăng ký tham gia', '2026-02-25 06:00:00', '2026-04-07 09:00:51'),
(227, 3, 10, 'approved', 'Đăng ký tham gia', '2026-02-25 06:30:00', '2026-04-07 09:00:51'),
(228, 3, 11, 'approved', 'Đăng ký tham gia', '2026-02-26 01:00:00', '2026-04-07 09:00:51'),
(229, 3, 12, 'approved', 'Đăng ký tham gia', '2026-02-26 01:30:00', '2026-04-07 09:00:51'),
(230, 3, 13, 'approved', 'Đăng ký tham gia', '2026-02-26 02:00:00', '2026-04-07 09:00:51'),
(231, 3, 14, 'approved', 'Đăng ký tham gia', '2026-02-26 02:30:00', '2026-04-07 09:00:51'),
(232, 3, 15, 'approved', 'Đăng ký tham gia', '2026-02-26 03:00:00', '2026-04-07 09:00:51'),
(233, 3, 16, 'approved', 'Đăng ký tham gia', '2026-02-26 03:30:00', '2026-04-07 09:00:51'),
(234, 3, 17, 'approved', 'Đăng ký tham gia', '2026-02-26 04:00:00', '2026-04-07 09:00:51'),
(235, 3, 18, 'approved', 'Đăng ký tham gia', '2026-02-26 04:30:00', '2026-04-07 09:00:51'),
(236, 3, 19, 'approved', 'Đăng ký tham gia', '2026-02-26 06:00:00', '2026-04-07 09:00:51'),
(237, 3, 20, 'approved', 'Đăng ký tham gia', '2026-02-26 06:30:00', '2026-04-07 09:00:51'),
(238, 3, 21, 'approved', 'Đăng ký tham gia', '2026-02-27 01:00:00', '2026-04-07 09:00:51'),
(239, 3, 42, 'approved', 'Đăng ký tham gia', '2026-02-27 01:30:00', '2026-04-07 09:00:51'),
(240, 3, 43, 'approved', 'Đăng ký tham gia', '2026-02-27 02:00:00', '2026-04-07 09:00:51'),
(241, 3, 24, 'approved', 'Đăng ký tham gia', '2026-02-27 02:30:00', '2026-04-07 09:00:51'),
(242, 3, 25, 'approved', 'Đăng ký tham gia', '2026-02-27 03:00:00', '2026-04-07 09:00:51'),
(243, 3, 26, 'approved', 'Đăng ký tham gia', '2026-02-27 03:30:00', '2026-04-07 09:00:51'),
(244, 3, 27, 'approved', 'Đăng ký tham gia', '2026-02-27 04:00:00', '2026-04-07 09:00:51'),
(245, 3, 28, 'approved', 'Đăng ký tham gia', '2026-02-27 04:30:00', '2026-04-07 09:00:51'),
(246, 3, 29, 'approved', 'Đăng ký tham gia', '2026-02-27 06:00:00', '2026-04-07 09:00:51'),
(247, 3, 30, 'approved', 'Đăng ký tham gia', '2026-02-27 06:30:00', '2026-04-07 09:00:51'),
(248, 3, 31, 'approved', 'Đăng ký tham gia', '2026-02-28 01:00:00', '2026-04-07 09:00:51'),
(249, 3, 32, 'approved', 'Đăng ký tham gia', '2026-02-28 01:30:00', '2026-04-07 09:00:51'),
(250, 3, 33, 'approved', 'Đăng ký tham gia', '2026-02-28 02:00:00', '2026-04-07 09:00:51'),
(251, 3, 34, 'approved', 'Đăng ký tham gia', '2026-02-28 02:30:00', '2026-04-07 09:00:51'),
(252, 3, 35, 'approved', 'Đăng ký tham gia', '2026-02-28 03:00:00', '2026-04-07 09:00:51'),
(253, 3, 36, 'approved', 'Đăng ký tham gia', '2026-02-28 03:30:00', '2026-04-07 09:00:51'),
(254, 3, 37, 'approved', 'Đăng ký tham gia', '2026-02-28 04:00:00', '2026-04-07 09:00:51'),
(255, 3, 38, 'approved', 'Đăng ký tham gia', '2026-02-28 04:30:00', '2026-04-07 09:00:51'),
(256, 12, 31, 'approved', 'Đăng ký tham gia', '2026-04-01 01:00:00', '2026-04-07 09:52:01'),
(257, 12, 2, 'approved', 'Đăng ký tham gia', '2026-04-01 01:30:00', '2026-04-07 09:52:01'),
(258, 12, 3, 'approved', 'Đăng ký tham gia', '2026-04-01 02:00:00', '2026-04-07 09:52:01'),
(259, 12, 4, 'approved', 'Đăng ký tham gia', '2026-04-01 02:30:00', '2026-04-07 09:52:01'),
(260, 12, 5, 'approved', 'Đăng ký tham gia', '2026-04-01 03:00:00', '2026-04-07 09:52:01'),
(261, 12, 6, 'approved', 'Đăng ký tham gia', '2026-04-01 03:30:00', '2026-04-07 09:52:01'),
(262, 12, 7, 'approved', 'Đăng ký tham gia', '2026-04-01 04:00:00', '2026-04-07 09:52:01'),
(263, 12, 8, 'approved', 'Đăng ký tham gia', '2026-04-01 04:30:00', '2026-04-07 09:52:01'),
(264, 12, 9, 'approved', 'Đăng ký tham gia', '2026-04-01 06:00:00', '2026-04-07 09:52:01'),
(265, 12, 10, 'approved', 'Đăng ký tham gia', '2026-04-01 06:30:00', '2026-04-07 09:52:01'),
(266, 12, 11, 'approved', 'Đăng ký tham gia', '2026-04-02 01:00:00', '2026-04-07 09:52:01'),
(267, 12, 12, 'approved', 'Đăng ký tham gia', '2026-04-02 01:30:00', '2026-04-07 09:52:01'),
(268, 12, 13, 'approved', 'Đăng ký tham gia', '2026-04-02 02:00:00', '2026-04-07 09:52:01'),
(269, 12, 14, 'approved', 'Đăng ký tham gia', '2026-04-02 02:30:00', '2026-04-07 09:52:01'),
(270, 12, 15, 'approved', 'Đăng ký tham gia', '2026-04-02 03:00:00', '2026-04-07 09:52:01'),
(271, 12, 16, 'approved', 'Đăng ký tham gia', '2026-04-02 03:30:00', '2026-04-07 09:52:01'),
(272, 12, 17, 'approved', 'Đăng ký tham gia', '2026-04-02 04:00:00', '2026-04-07 09:52:01'),
(273, 12, 18, 'approved', 'Đăng ký tham gia', '2026-04-02 04:30:00', '2026-04-07 09:52:01'),
(274, 12, 19, 'approved', 'Đăng ký tham gia', '2026-04-02 06:00:00', '2026-04-07 09:52:01'),
(275, 12, 20, 'approved', 'Đăng ký tham gia', '2026-04-02 06:30:00', '2026-04-07 09:52:01'),
(276, 12, 21, 'approved', 'Đăng ký tham gia', '2026-04-03 01:00:00', '2026-04-07 09:52:01'),
(277, 12, 22, 'approved', 'Đăng ký tham gia', '2026-04-03 01:30:00', '2026-04-07 09:52:01'),
(278, 12, 23, 'approved', 'Đăng ký tham gia', '2026-04-03 02:00:00', '2026-04-07 09:52:01'),
(279, 12, 24, 'approved', 'Đăng ký tham gia', '2026-04-03 02:30:00', '2026-04-07 09:52:01'),
(280, 12, 25, 'approved', 'Đăng ký tham gia', '2026-04-03 03:00:00', '2026-04-07 09:52:01'),
(281, 12, 26, 'approved', 'Đăng ký tham gia', '2026-04-03 03:30:00', '2026-04-07 09:52:01'),
(282, 12, 27, 'approved', 'Đăng ký tham gia', '2026-04-03 04:00:00', '2026-04-07 09:52:01'),
(283, 12, 28, 'approved', 'Đăng ký tham gia', '2026-04-03 04:30:00', '2026-04-07 09:52:01'),
(284, 12, 29, 'approved', 'Đăng ký tham gia', '2026-04-03 06:00:00', '2026-04-07 09:52:01'),
(285, 12, 30, 'approved', 'Đăng ký tham gia', '2026-04-03 06:30:00', '2026-04-07 09:52:01'),
(286, 13, 50, 'approved', 'Đăng ký tham gia', '2026-03-10 01:00:00', '2026-04-07 11:47:42'),
(287, 13, 2, 'approved', 'Đăng ký tham gia', '2026-03-10 01:30:00', '2026-04-07 11:47:42'),
(288, 13, 3, 'approved', 'Đăng ký tham gia', '2026-03-10 02:00:00', '2026-04-07 11:47:42'),
(289, 13, 4, 'approved', 'Đăng ký tham gia', '2026-03-10 02:30:00', '2026-04-07 11:47:42'),
(290, 13, 5, 'approved', 'Đăng ký tham gia', '2026-03-10 03:00:00', '2026-04-07 11:47:42'),
(291, 13, 6, 'approved', 'Đăng ký tham gia', '2026-03-10 03:30:00', '2026-04-07 11:47:42'),
(292, 13, 7, 'approved', 'Đăng ký tham gia', '2026-03-10 04:00:00', '2026-04-07 11:47:42'),
(293, 13, 8, 'approved', 'Đăng ký tham gia', '2026-03-10 04:30:00', '2026-04-07 11:47:42'),
(294, 13, 9, 'approved', 'Đăng ký tham gia', '2026-03-10 06:00:00', '2026-04-07 11:47:42'),
(295, 13, 10, 'approved', 'Đăng ký tham gia', '2026-03-10 06:30:00', '2026-04-07 11:47:42'),
(296, 13, 11, 'approved', 'Đăng ký tham gia', '2026-03-11 01:00:00', '2026-04-07 11:47:42'),
(297, 13, 12, 'approved', 'Đăng ký tham gia', '2026-03-11 01:30:00', '2026-04-07 11:47:42'),
(298, 13, 13, 'approved', 'Đăng ký tham gia', '2026-03-11 02:00:00', '2026-04-07 11:47:42'),
(299, 13, 14, 'approved', 'Đăng ký tham gia', '2026-03-11 02:30:00', '2026-04-07 11:47:42'),
(300, 13, 15, 'approved', 'Đăng ký tham gia', '2026-03-11 03:00:00', '2026-04-07 11:47:42'),
(301, 13, 16, 'approved', 'Đăng ký tham gia', '2026-03-11 03:30:00', '2026-04-07 11:47:42'),
(302, 13, 17, 'approved', 'Đăng ký tham gia', '2026-03-11 04:00:00', '2026-04-07 11:47:42'),
(303, 13, 18, 'approved', 'Đăng ký tham gia', '2026-03-11 04:30:00', '2026-04-07 11:47:42'),
(304, 13, 19, 'approved', 'Đăng ký tham gia', '2026-03-11 06:00:00', '2026-04-07 11:47:42'),
(305, 13, 20, 'approved', 'Đăng ký tham gia', '2026-03-11 06:30:00', '2026-04-07 11:47:42'),
(306, 13, 21, 'approved', 'Đăng ký tham gia', '2026-03-12 01:00:00', '2026-04-07 11:47:42'),
(307, 13, 22, 'approved', 'Đăng ký tham gia', '2026-03-12 01:30:00', '2026-04-07 11:47:42'),
(308, 13, 23, 'approved', 'Đăng ký tham gia', '2026-03-12 02:00:00', '2026-04-07 11:47:42'),
(309, 13, 24, 'approved', 'Đăng ký tham gia', '2026-03-12 02:30:00', '2026-04-07 11:47:42'),
(310, 13, 25, 'approved', 'Đăng ký tham gia', '2026-03-12 03:00:00', '2026-04-07 11:47:42'),
(311, 13, 26, 'approved', 'Đăng ký tham gia', '2026-03-12 03:30:00', '2026-04-07 11:47:42'),
(312, 13, 27, 'approved', 'Đăng ký tham gia', '2026-03-12 04:00:00', '2026-04-07 11:47:42'),
(313, 13, 28, 'approved', 'Đăng ký tham gia', '2026-03-12 04:30:00', '2026-04-07 11:47:42'),
(314, 13, 29, 'approved', 'Đăng ký tham gia', '2026-03-12 06:00:00', '2026-04-07 11:47:42'),
(315, 13, 30, 'approved', 'Đăng ký tham gia', '2026-03-12 06:30:00', '2026-04-07 11:47:42'),
(316, 13, 31, 'approved', 'Đăng ký tham gia', '2026-03-13 01:00:00', '2026-04-07 11:47:42'),
(317, 13, 32, 'approved', 'Đăng ký tham gia', '2026-03-13 01:30:00', '2026-04-07 11:47:42'),
(318, 13, 33, 'approved', 'Đăng ký tham gia', '2026-03-13 02:00:00', '2026-04-07 11:47:42'),
(319, 13, 34, 'approved', 'Đăng ký tham gia', '2026-03-13 02:30:00', '2026-04-07 11:47:42'),
(320, 13, 35, 'approved', 'Đăng ký tham gia', '2026-03-13 03:00:00', '2026-04-07 11:47:42'),
(321, 13, 36, 'approved', 'Đăng ký tham gia', '2026-03-13 03:30:00', '2026-04-07 11:47:42'),
(322, 13, 37, 'approved', 'Đăng ký tham gia', '2026-03-13 04:00:00', '2026-04-07 11:47:42'),
(323, 13, 38, 'approved', 'Đăng ký tham gia', '2026-03-13 04:30:00', '2026-04-07 11:47:42'),
(324, 13, 45, 'approved', 'Đăng ký tham gia', '2026-03-13 06:00:00', '2026-04-07 11:47:42'),
(325, 13, 47, 'approved', 'Đăng ký tham gia', '2026-03-13 06:30:00', '2026-04-07 11:47:42'),
(486, 14, 50, 'approved', 'Đăng ký tham gia', '2026-03-01 01:00:00', '2026-04-07 13:50:04'),
(487, 14, 2, 'approved', 'Đăng ký tham gia', '2026-03-01 01:30:00', '2026-04-07 13:50:04'),
(488, 14, 3, 'approved', 'Đăng ký tham gia', '2026-03-01 02:00:00', '2026-04-07 13:50:04'),
(489, 14, 4, 'approved', 'Đăng ký tham gia', '2026-03-01 02:30:00', '2026-04-07 13:50:04'),
(490, 14, 5, 'approved', 'Đăng ký tham gia', '2026-03-01 03:00:00', '2026-04-07 13:50:04'),
(491, 14, 6, 'approved', 'Đăng ký tham gia', '2026-03-01 03:30:00', '2026-04-07 13:50:04'),
(492, 14, 7, 'approved', 'Đăng ký tham gia', '2026-03-01 04:00:00', '2026-04-07 13:50:04'),
(493, 14, 8, 'approved', 'Đăng ký tham gia', '2026-03-01 04:30:00', '2026-04-07 13:50:04'),
(494, 14, 9, 'approved', 'Đăng ký tham gia', '2026-03-01 06:00:00', '2026-04-07 13:50:04'),
(495, 14, 10, 'approved', 'Đăng ký tham gia', '2026-03-01 06:30:00', '2026-04-07 13:50:04'),
(496, 14, 11, 'approved', 'Đăng ký tham gia', '2026-03-02 01:00:00', '2026-04-07 13:50:04'),
(497, 14, 12, 'approved', 'Đăng ký tham gia', '2026-03-02 01:30:00', '2026-04-07 13:50:04'),
(498, 14, 13, 'approved', 'Đăng ký tham gia', '2026-03-02 02:00:00', '2026-04-07 13:50:04'),
(499, 14, 14, 'approved', 'Đăng ký tham gia', '2026-03-02 02:30:00', '2026-04-07 13:50:04'),
(500, 14, 15, 'approved', 'Đăng ký tham gia', '2026-03-02 03:00:00', '2026-04-07 13:50:04'),
(501, 14, 16, 'approved', 'Đăng ký tham gia', '2026-03-02 03:30:00', '2026-04-07 13:50:04'),
(502, 14, 17, 'approved', 'Đăng ký tham gia', '2026-03-02 04:00:00', '2026-04-07 13:50:04'),
(503, 14, 18, 'approved', 'Đăng ký tham gia', '2026-03-02 04:30:00', '2026-04-07 13:50:04'),
(504, 14, 19, 'approved', 'Đăng ký tham gia', '2026-03-02 06:00:00', '2026-04-07 13:50:04'),
(505, 14, 20, 'approved', 'Đăng ký tham gia', '2026-03-02 06:30:00', '2026-04-07 13:50:04'),
(506, 14, 21, 'approved', 'Đăng ký tham gia', '2026-03-03 01:00:00', '2026-04-07 13:50:04'),
(507, 14, 22, 'approved', 'Đăng ký tham gia', '2026-03-03 01:30:00', '2026-04-07 13:50:04'),
(508, 14, 23, 'approved', 'Đăng ký tham gia', '2026-03-03 02:00:00', '2026-04-07 13:50:04'),
(509, 14, 24, 'approved', 'Đăng ký tham gia', '2026-03-03 02:30:00', '2026-04-07 13:50:04'),
(510, 14, 25, 'approved', 'Đăng ký tham gia', '2026-03-03 03:00:00', '2026-04-07 13:50:04'),
(511, 14, 26, 'approved', 'Đăng ký tham gia', '2026-03-03 03:30:00', '2026-04-07 13:50:04'),
(512, 14, 27, 'approved', 'Đăng ký tham gia', '2026-03-03 04:00:00', '2026-04-07 13:50:04'),
(513, 14, 28, 'approved', 'Đăng ký tham gia', '2026-03-03 04:30:00', '2026-04-07 13:50:04'),
(514, 14, 29, 'approved', 'Đăng ký tham gia', '2026-03-03 06:00:00', '2026-04-07 13:50:04'),
(515, 14, 30, 'approved', 'Đăng ký tham gia', '2026-03-03 06:30:00', '2026-04-07 13:50:04'),
(516, 14, 31, 'approved', 'Đăng ký tham gia', '2026-03-04 01:00:00', '2026-04-07 13:50:04'),
(517, 14, 32, 'approved', 'Đăng ký tham gia', '2026-03-04 01:30:00', '2026-04-07 13:50:04'),
(518, 14, 33, 'approved', 'Đăng ký tham gia', '2026-03-04 02:00:00', '2026-04-07 13:50:04'),
(519, 14, 34, 'approved', 'Đăng ký tham gia', '2026-03-04 02:30:00', '2026-04-07 13:50:04'),
(520, 14, 35, 'approved', 'Đăng ký tham gia', '2026-03-04 03:00:00', '2026-04-07 13:50:04'),
(521, 14, 36, 'approved', 'Đăng ký tham gia', '2026-03-04 03:30:00', '2026-04-07 13:50:04'),
(522, 14, 37, 'approved', 'Đăng ký tham gia', '2026-03-04 04:00:00', '2026-04-07 13:50:04'),
(523, 14, 38, 'approved', 'Đăng ký tham gia', '2026-03-04 04:30:00', '2026-04-07 13:50:04'),
(524, 14, 49, 'approved', 'Đăng ký tham gia', '2026-03-04 06:00:00', '2026-04-07 13:50:04'),
(525, 14, 47, 'approved', 'Đăng ký tham gia', '2026-03-04 06:30:00', '2026-04-07 13:50:04'),
(526, 15, 45, 'approved', 'Đăng ký tham gia', '2025-12-10 01:00:00', '2026-04-07 14:17:13'),
(527, 15, 2, 'approved', 'Đăng ký tham gia', '2025-12-10 01:30:00', '2026-04-07 14:17:13'),
(528, 15, 3, 'approved', 'Đăng ký tham gia', '2025-12-10 02:00:00', '2026-04-07 14:17:13'),
(529, 15, 4, 'approved', 'Đăng ký tham gia', '2025-12-10 02:30:00', '2026-04-07 14:17:13'),
(530, 15, 5, 'approved', 'Đăng ký tham gia', '2025-12-10 03:00:00', '2026-04-07 14:17:13'),
(531, 15, 6, 'approved', 'Đăng ký tham gia', '2025-12-10 03:30:00', '2026-04-07 14:17:13'),
(532, 15, 7, 'approved', 'Đăng ký tham gia', '2025-12-10 04:00:00', '2026-04-07 14:17:13'),
(533, 15, 8, 'approved', 'Đăng ký tham gia', '2025-12-10 04:30:00', '2026-04-07 14:17:13'),
(534, 15, 9, 'approved', 'Đăng ký tham gia', '2025-12-10 06:00:00', '2026-04-07 14:17:13'),
(535, 15, 10, 'approved', 'Đăng ký tham gia', '2025-12-10 06:30:00', '2026-04-07 14:17:13'),
(536, 15, 11, 'approved', 'Đăng ký tham gia', '2025-12-11 01:00:00', '2026-04-07 14:17:13'),
(537, 15, 12, 'approved', 'Đăng ký tham gia', '2025-12-11 01:30:00', '2026-04-07 14:17:13'),
(538, 15, 13, 'approved', 'Đăng ký tham gia', '2025-12-11 02:00:00', '2026-04-07 14:17:13'),
(539, 15, 14, 'approved', 'Đăng ký tham gia', '2025-12-11 02:30:00', '2026-04-07 14:17:13'),
(540, 15, 15, 'approved', 'Đăng ký tham gia', '2025-12-11 03:00:00', '2026-04-07 14:17:13'),
(541, 15, 16, 'approved', 'Đăng ký tham gia', '2025-12-11 03:30:00', '2026-04-07 14:17:13'),
(542, 15, 17, 'approved', 'Đăng ký tham gia', '2025-12-11 04:00:00', '2026-04-07 14:17:13'),
(543, 15, 18, 'approved', 'Đăng ký tham gia', '2025-12-11 04:30:00', '2026-04-07 14:17:13'),
(544, 15, 19, 'approved', 'Đăng ký tham gia', '2025-12-11 06:00:00', '2026-04-07 14:17:13'),
(545, 15, 20, 'approved', 'Đăng ký tham gia', '2025-12-11 06:30:00', '2026-04-07 14:17:13'),
(546, 15, 21, 'approved', 'Đăng ký tham gia', '2025-12-12 01:00:00', '2026-04-07 14:17:13'),
(547, 15, 22, 'approved', 'Đăng ký tham gia', '2025-12-12 01:30:00', '2026-04-07 14:17:13'),
(548, 15, 23, 'approved', 'Đăng ký tham gia', '2025-12-12 02:00:00', '2026-04-07 14:17:13'),
(549, 15, 24, 'approved', 'Đăng ký tham gia', '2025-12-12 02:30:00', '2026-04-07 14:17:13'),
(550, 15, 25, 'approved', 'Đăng ký tham gia', '2025-12-12 03:00:00', '2026-04-07 14:17:13'),
(551, 15, 26, 'approved', 'Đăng ký tham gia', '2025-12-12 03:30:00', '2026-04-07 14:17:13'),
(552, 15, 27, 'approved', 'Đăng ký tham gia', '2025-12-12 04:00:00', '2026-04-07 14:17:13'),
(553, 15, 28, 'approved', 'Đăng ký tham gia', '2025-12-12 04:30:00', '2026-04-07 14:17:13'),
(554, 15, 29, 'approved', 'Đăng ký tham gia', '2025-12-12 06:00:00', '2026-04-07 14:17:13'),
(555, 15, 30, 'approved', 'Đăng ký tham gia', '2025-12-12 06:30:00', '2026-04-07 14:17:13'),
(556, 15, 31, 'approved', 'Đăng ký tham gia', '2025-12-13 01:00:00', '2026-04-07 14:17:13'),
(557, 15, 32, 'approved', 'Đăng ký tham gia', '2025-12-13 01:30:00', '2026-04-07 14:17:13'),
(558, 15, 33, 'approved', 'Đăng ký tham gia', '2025-12-13 02:00:00', '2026-04-07 14:17:13'),
(559, 15, 34, 'approved', 'Đăng ký tham gia', '2025-12-13 02:30:00', '2026-04-07 14:17:13'),
(560, 15, 35, 'approved', 'Đăng ký tham gia', '2025-12-13 03:00:00', '2026-04-07 14:17:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gallery`
--

CREATE TABLE `gallery` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `media_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `gallery`
--

INSERT INTO `gallery` (`id`, `club_id`, `media_id`, `title`, `description`, `uploaded_at`, `uploaded_by`) VALUES
(4, 3, 20, '[RECAP] Tổng kết buổi tuyển thành viên đợt 2', '', '2026-04-07 06:36:46', 80),
(5, 3, 21, 'Tổng kết chiến dịch Xuân yêu thương 2026', '', '2026-04-07 06:37:18', 80),
(6, 3, 22, 'Tổng kết chiến dịch Xuân yêu thương 2026', '', '2026-04-07 06:37:18', 80),
(8, 3, 24, 'Tổng kết chiến dịch Xuân yêu thương 2026', '', '2026-04-07 06:44:13', 80),
(9, 3, 25, 'Recap 13 năm thành lập CLB Dấu chân tình nguyện', '', '2026-04-07 06:45:34', 80),
(10, 13, 30, 'Tổng kết Team Building 2026', '', '2026-04-07 07:39:19', 22),
(11, 13, 31, 'Tổng kết Team Building 2026', '', '2026-04-07 07:39:19', 22),
(12, 13, 32, 'Tổng kết Team Building 2026', '', '2026-04-07 07:39:20', 22),
(13, 13, 33, 'Tổng kết Team Building 2026', '', '2026-04-07 07:39:20', 22),
(14, 13, 34, 'Tổng kết Team Building 2026', '', '2026-04-07 07:39:20', 22),
(15, 6, 43, 'Tổng kết buổi giao lưu tri thức về văn hóa đọc trong môi trường sư phạm và toán học hiện đại', '', '2026-04-07 14:15:30', 76),
(16, 6, 44, 'Tổng kết buổi giao lưu tri thức về văn hóa đọc trong môi trường sư phạm và toán học hiện đại', '', '2026-04-07 14:15:30', 76),
(17, 6, 45, 'Tổng kết buổi giao lưu tri thức về văn hóa đọc trong môi trường sư phạm và toán học hiện đại', '', '2026-04-07 14:15:30', 76),
(18, 6, 46, 'Tổng kết buổi giao lưu tri thức về văn hóa đọc trong môi trường sư phạm và toán học hiện đại', '', '2026-04-07 14:15:30', 76);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Uyên Trang', 'trang2005@gmail.com', 'Xin chào', 'Xin chào', 'new', '2026-04-08 09:26:22', '2026-04-08 09:26:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `join_requests`
--

CREATE TABLE `join_requests` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `join_requests`
--

INSERT INTO `join_requests` (`id`, `club_id`, `user_id`, `email`, `phone`, `message`, `status`, `requested_at`, `processed_by`, `processed_at`) VALUES
(1, 2, 76, 'kimAnh@gmail.com', '0981234654', '', 'pending', '2026-04-08 09:27:07', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `media`
--

CREATE TABLE `media` (
  `id` int(11) NOT NULL,
  `path` varchar(500) NOT NULL,
  `uploader_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `media`
--

INSERT INTO `media` (`id`, `path`, `uploader_id`, `created_at`) VALUES
(1, 'uploads/clubs/clb_logo_1775468274_7d361c347b7f8895.jpg', NULL, '2026-04-06 09:37:54'),
(2, 'uploads/clubs/clb_logo_1775468501_a414f803f1fa2198.jpg', NULL, '2026-04-06 09:41:41'),
(3, 'uploads/clubs/clb_1775468569_db5f528876fbf236.jpg', NULL, '2026-04-06 09:42:49'),
(4, 'uploads/clubs/clb_logo_1775469267_53a71caf1bdffdd5.jpg', NULL, '2026-04-06 09:54:27'),
(5, 'uploads/clubs/clb_logo_1775469549_68a9399a07a036e0.jpg', NULL, '2026-04-06 09:59:09'),
(6, 'uploads/clubs/clb_logo_1775481363_a5bca6ff40276ab2.jpg', NULL, '2026-04-06 13:16:03'),
(7, 'uploads/clubs/clb_logo_1775481550_337ca15c0abfaf68.jpg', NULL, '2026-04-06 13:19:10'),
(8, 'uploads/clubs/clb_logo_1775481869_0c7fdf81c93e3dc5.jpg', NULL, '2026-04-06 13:24:29'),
(9, 'uploads/clubs/clb_logo_1775482336_998c901e8d0347d4.jpg', NULL, '2026-04-06 13:32:16'),
(10, 'uploads/clubs/clb_logo_1775482681_09d9e880106bc90f.jpg', NULL, '2026-04-06 13:38:01'),
(11, 'uploads/clubs/clb_logo_1775482882_fd577c99b692a87c.jpg', NULL, '2026-04-06 13:41:22'),
(12, 'uploads/clubs/clb_logo_1775483244_ede3fa5357122566.jpg', NULL, '2026-04-06 13:47:24'),
(13, 'uploads/clubs/clb_logo_1775483326_823809807ad0061e.jpg', NULL, '2026-04-06 13:48:46'),
(14, 'uploads/clubs/clb_logo_1775483512_0788bbac205dd9f8.jpg', NULL, '2026-04-06 13:51:52'),
(15, 'uploads/clubs/clb_logo_1775483630_d5d999275a820334.png', NULL, '2026-04-06 13:53:50'),
(16, 'anh_bia_sk/cover_1775543426_8b886643d65e63a8.jpg', 80, '2026-04-07 06:30:26'),
(20, 'assets/img/gallery/gallery_3_1775543806_0_6dc0361a.jpg', 80, '2026-04-07 06:36:46'),
(21, 'assets/img/gallery/gallery_3_1775543838_0_8be33fd3.jpg', 80, '2026-04-07 06:37:18'),
(22, 'assets/img/gallery/gallery_3_1775543838_1_54215165.jpg', 80, '2026-04-07 06:37:18'),
(24, 'assets/img/gallery/gallery_3_1775544253_0_3e136c5b.jpg', 80, '2026-04-07 06:44:13'),
(25, 'assets/img/gallery/gallery_3_1775544334_0_249db8cf.jpg', 80, '2026-04-07 06:45:34'),
(26, 'assets/img/banner_3_1775544980_6b061bca.jpg', 80, '2026-04-07 06:56:20'),
(27, 'assets/img/banner_13_1775546249_26b78c4a.jpg', 22, '2026-04-07 07:17:29'),
(28, 'anh_bia_sk/cover_1775547035_a26ed5dd70cc3152.jpg', 22, '2026-04-07 07:30:35'),
(29, 'anh_bia_sk/cover_1775547166_9c2d5b467a684bae.jpg', 22, '2026-04-07 07:32:46'),
(30, 'assets/img/gallery/gallery_13_1775547559_0_6ad4755d.jpg', 22, '2026-04-07 07:39:19'),
(31, 'assets/img/gallery/gallery_13_1775547559_1_3f84b16c.jpg', 22, '2026-04-07 07:39:19'),
(32, 'assets/img/gallery/gallery_13_1775547559_2_9c8514bd.jpg', 22, '2026-04-07 07:39:19'),
(33, 'assets/img/gallery/gallery_13_1775547560_3_2e98da96.jpg', 22, '2026-04-07 07:39:20'),
(34, 'assets/img/gallery/gallery_13_1775547560_4_65bd2ac5.jpg', 22, '2026-04-07 07:39:20'),
(35, 'anh_bia_sk/cover_1775548988_bd9852688485109c.jpg', 22, '2026-04-07 08:03:08'),
(36, 'assets/img/banner_1_1775549750_a1adcaab.jpg', 23, '2026-04-07 08:15:50'),
(37, 'anh_bia_sk/cover_1775550094_4d0c3d0f9712e86c.jpg', 23, '2026-04-07 08:21:34'),
(38, 'assets/img/banner_7_1775553156_e09b2887.jpg', 68, '2026-04-07 09:12:36'),
(39, 'anh_bia_sk/cover_1775555349_7151343037d5cf10.jpg', 88, '2026-04-07 09:49:09'),
(40, 'anh_bia_sk/cover_1775562329_9f01033576827f44.jpg', 68, '2026-04-07 11:45:29'),
(41, 'anh_bia_sk/cover_1775562706_c7ceb7dcafa2925c.jpg', 47, '2026-04-07 11:51:46'),
(42, 'anh_bia_sk/cover_1775571232_259f9933a9b6f285.jpg', 76, '2026-04-07 14:13:52'),
(43, 'assets/img/gallery/gallery_6_1775571330_0_d09987f1.jpg', 76, '2026-04-07 14:15:30'),
(44, 'assets/img/gallery/gallery_6_1775571330_1_f3d04a09.jpg', 76, '2026-04-07 14:15:30'),
(45, 'assets/img/gallery/gallery_6_1775571330_2_7f7c60be.jpg', 76, '2026-04-07 14:15:30'),
(46, 'assets/img/gallery/gallery_6_1775571330_3_28b21b23.jpg', 76, '2026-04-07 14:15:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member',
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','pending','inactive') DEFAULT 'pending',
  `joined_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `members`
--

INSERT INTO `members` (`id`, `club_id`, `user_id`, `role`, `department_id`, `status`, `joined_at`, `updated_at`) VALUES
(1, 3, 7, 'member', 1, 'active', '2026-04-07 06:52:45', '2026-04-07 06:52:45'),
(2, 3, 64, 'member', 2, 'active', '2026-04-07 06:53:57', '2026-04-07 06:53:57'),
(3, 3, 62, 'member', 2, 'active', '2026-04-07 06:54:01', '2026-04-07 06:54:01'),
(4, 3, 11, 'member', 1, 'active', '2026-04-07 06:54:07', '2026-04-07 06:54:07'),
(5, 3, 20, 'member', 3, 'active', '2026-04-07 06:54:15', '2026-04-07 06:54:15'),
(6, 3, 82, 'member', 1, 'active', '2026-04-07 06:54:21', '2026-04-07 06:54:21'),
(7, 3, 87, 'member', 4, 'active', '2026-04-07 06:54:33', '2026-04-07 06:54:33'),
(8, 3, 98, 'member', 3, 'active', '2026-04-07 06:54:41', '2026-04-07 06:54:41'),
(9, 3, 97, 'member', 4, 'active', '2026-04-07 06:54:47', '2026-04-07 06:54:47'),
(10, 3, 31, 'member', 4, 'active', '2026-04-07 06:54:52', '2026-04-07 06:54:52'),
(11, 3, 75, 'member', 1, 'active', '2026-04-07 07:09:31', '2026-04-07 07:09:31'),
(12, 3, 5, 'member', 2, 'active', '2026-04-07 07:11:01', '2026-04-07 07:11:01'),
(13, 3, 18, 'member', 3, 'active', '2026-04-07 07:11:18', '2026-04-07 07:11:18'),
(14, 3, 50, 'member', 3, 'active', '2026-04-07 07:11:33', '2026-04-07 07:11:33'),
(15, 3, 51, 'member', 1, 'active', '2026-04-07 07:11:48', '2026-04-07 07:11:48'),
(16, 3, 33, 'member', 4, 'active', '2026-04-07 07:12:04', '2026-04-07 07:12:04'),
(17, 3, 43, 'member', 2, 'active', '2026-04-07 07:12:20', '2026-04-07 07:12:20'),
(18, 3, 105, 'member', 3, 'active', '2026-04-07 07:12:42', '2026-04-07 07:12:42'),
(19, 3, 91, 'member', 4, 'active', '2026-04-07 07:13:00', '2026-04-07 07:13:00'),
(20, 3, 35, 'member', 2, 'active', '2026-04-07 07:13:18', '2026-04-07 07:13:18'),
(21, 13, 101, 'member', 6, 'active', '2026-04-07 07:34:46', '2026-04-07 07:34:46'),
(22, 13, 53, 'member', 9, 'active', '2026-04-07 07:35:06', '2026-04-07 07:35:06'),
(23, 13, 93, 'member', 7, 'active', '2026-04-07 07:35:23', '2026-04-07 07:35:23'),
(24, 13, 47, 'member', 8, 'active', '2026-04-07 07:35:43', '2026-04-07 07:35:43'),
(25, 13, 26, 'member', 11, 'active', '2026-04-07 07:35:56', '2026-04-07 07:35:56'),
(26, 13, 48, 'member', 10, 'active', '2026-04-07 07:36:09', '2026-04-07 07:36:09'),
(27, 13, 42, 'member', 5, 'active', '2026-04-07 07:36:23', '2026-04-07 07:36:23'),
(28, 13, 70, 'member', 7, 'active', '2026-04-07 07:36:39', '2026-04-07 07:36:39'),
(29, 13, 27, 'member', 6, 'active', '2026-04-07 07:36:57', '2026-04-07 07:36:57'),
(30, 13, 54, 'member', 7, 'active', '2026-04-07 07:37:12', '2026-04-07 07:37:12'),
(31, 13, 69, 'member', 6, 'active', '2026-04-07 07:39:57', '2026-04-07 07:39:57'),
(32, 13, 64, 'member', 5, 'active', '2026-04-07 07:40:18', '2026-04-07 07:40:18'),
(33, 13, 99, 'member', 9, 'active', '2026-04-07 07:40:37', '2026-04-07 07:40:37'),
(34, 13, 76, 'member', 8, 'active', '2026-04-07 07:40:52', '2026-04-07 07:40:52'),
(35, 13, 77, 'member', 10, 'active', '2026-04-07 07:41:23', '2026-04-07 07:41:23'),
(36, 13, 45, 'member', 11, 'active', '2026-04-07 07:41:35', '2026-04-07 07:41:35'),
(37, 13, 38, 'member', 7, 'active', '2026-04-07 07:41:56', '2026-04-07 07:41:56'),
(38, 13, 52, 'member', 7, 'active', '2026-04-07 07:42:21', '2026-04-07 07:42:21'),
(39, 13, 73, 'member', 6, 'active', '2026-04-07 07:42:40', '2026-04-07 07:42:40'),
(40, 13, 18, 'member', 8, 'active', '2026-04-07 07:42:55', '2026-04-07 07:42:55'),
(41, 13, 19, 'member', 9, 'active', '2026-04-07 07:46:57', '2026-04-07 07:46:57'),
(42, 13, 6, 'member', 10, 'active', '2026-04-07 07:47:19', '2026-04-07 07:47:19'),
(43, 13, 63, 'member', 6, 'active', '2026-04-07 07:47:39', '2026-04-07 07:47:39'),
(44, 13, 75, 'member', 10, 'active', '2026-04-07 07:47:58', '2026-04-07 07:47:58'),
(45, 13, 82, 'member', 10, 'active', '2026-04-07 07:48:19', '2026-04-07 07:48:19'),
(46, 13, 61, 'member', 9, 'active', '2026-04-07 07:48:51', '2026-04-07 07:48:51'),
(47, 13, 86, 'member', 11, 'active', '2026-04-07 07:49:08', '2026-04-07 07:49:08'),
(48, 13, 66, 'member', 7, 'active', '2026-04-07 07:49:23', '2026-04-07 07:49:23'),
(49, 13, 94, 'member', 5, 'active', '2026-04-07 07:49:39', '2026-04-07 07:49:39'),
(50, 13, 29, 'member', 10, 'active', '2026-04-07 07:49:58', '2026-04-07 07:49:58'),
(51, 13, 49, 'member', 11, 'active', '2026-04-07 07:52:20', '2026-04-07 07:52:20'),
(52, 13, 71, 'member', 11, 'active', '2026-04-07 07:53:11', '2026-04-07 07:53:11'),
(53, 13, 17, 'member', 8, 'active', '2026-04-07 07:53:18', '2026-04-07 07:53:18'),
(54, 13, 103, 'member', 9, 'active', '2026-04-07 07:53:37', '2026-04-07 07:53:37'),
(55, 13, 21, 'member', 7, 'active', '2026-04-07 07:53:57', '2026-04-07 07:53:57'),
(56, 1, 4, 'member', 14, 'active', '2026-04-07 08:18:30', '2026-04-07 08:18:30'),
(57, 1, 2, 'member', 13, 'active', '2026-04-07 08:28:08', '2026-04-07 08:28:08'),
(58, 1, 3, 'member', 12, 'active', '2026-04-07 08:28:21', '2026-04-07 08:28:21'),
(59, 1, 5, 'member', 16, 'active', '2026-04-07 08:28:33', '2026-04-07 08:28:33'),
(60, 1, 6, 'member', 12, 'active', '2026-04-07 08:28:44', '2026-04-07 08:28:44'),
(61, 1, 7, 'member', 12, 'active', '2026-04-07 08:28:52', '2026-04-07 08:28:52'),
(62, 1, 8, 'member', 15, 'active', '2026-04-07 08:29:01', '2026-04-07 08:29:01'),
(63, 1, 9, 'member', 15, 'active', '2026-04-07 08:29:14', '2026-04-07 08:29:14'),
(64, 1, 10, 'member', 14, 'active', '2026-04-07 08:29:24', '2026-04-07 08:29:24'),
(65, 1, 21, 'member', 12, 'active', '2026-04-07 08:29:34', '2026-04-07 08:29:34'),
(66, 1, 24, 'member', 13, 'active', '2026-04-07 08:30:07', '2026-04-07 08:30:07'),
(67, 1, 25, 'member', 14, 'active', '2026-04-07 08:33:54', '2026-04-07 08:33:54'),
(68, 1, 26, 'member', 15, 'active', '2026-04-07 08:34:07', '2026-04-07 08:34:07'),
(69, 1, 27, 'member', 13, 'active', '2026-04-07 08:34:32', '2026-04-07 08:34:32'),
(70, 1, 28, 'member', 16, 'active', '2026-04-07 08:34:45', '2026-04-07 08:34:45'),
(71, 1, 29, 'member', 13, 'active', '2026-04-07 08:34:58', '2026-04-07 08:34:58'),
(72, 1, 30, 'member', 14, 'active', '2026-04-07 08:35:07', '2026-04-07 08:35:07'),
(73, 1, 31, 'member', 13, 'active', '2026-04-07 08:35:17', '2026-04-07 08:35:17'),
(74, 1, 32, 'member', 15, 'active', '2026-04-07 08:35:26', '2026-04-07 08:35:26'),
(75, 1, 42, 'member', 12, 'active', '2026-04-07 08:35:35', '2026-04-07 08:35:35'),
(76, 1, 43, 'member', 16, 'active', '2026-04-07 08:35:57', '2026-04-07 08:35:57'),
(77, 12, 33, 'member', 17, 'active', '2026-04-07 08:43:26', '2026-04-07 08:43:26'),
(78, 12, 51, 'member', 19, 'active', '2026-04-07 08:43:31', '2026-04-07 08:43:31'),
(79, 12, 98, 'member', 18, 'active', '2026-04-07 08:43:34', '2026-04-07 08:43:34'),
(80, 12, 18, 'member', 17, 'active', '2026-04-07 08:43:40', '2026-04-07 08:43:40'),
(81, 12, 58, 'member', 19, 'active', '2026-04-07 08:43:45', '2026-04-07 08:43:45'),
(82, 12, 44, 'member', 17, 'active', '2026-04-07 08:43:50', '2026-04-07 08:43:50'),
(83, 12, 88, 'member', 17, 'active', '2026-04-07 08:43:59', '2026-04-07 08:43:59'),
(84, 12, 105, 'member', 17, 'active', '2026-04-07 08:44:03', '2026-04-07 08:44:03'),
(85, 12, 16, 'member', 18, 'active', '2026-04-07 08:44:08', '2026-04-07 08:44:08'),
(86, 12, 72, 'member', 19, 'active', '2026-04-07 08:44:12', '2026-04-07 08:44:12'),
(87, 1, 11, 'member', 13, 'active', '2026-04-07 08:44:37', '2026-04-07 08:44:37'),
(88, 1, 12, 'member', 13, 'active', '2026-04-07 08:44:48', '2026-04-07 08:44:48'),
(89, 1, 13, 'member', 13, 'active', '2026-04-07 08:44:57', '2026-04-07 08:44:57'),
(90, 1, 14, 'member', 14, 'active', '2026-04-07 08:45:07', '2026-04-07 08:45:07'),
(91, 1, 15, 'member', 14, 'active', '2026-04-07 08:45:18', '2026-04-07 08:45:18'),
(92, 1, 33, 'member', 16, 'active', '2026-04-07 08:45:28', '2026-04-07 08:45:28'),
(93, 1, 34, 'member', 12, 'active', '2026-04-07 08:45:39', '2026-04-07 08:45:39'),
(94, 1, 35, 'member', 16, 'active', '2026-04-07 08:45:46', '2026-04-07 08:45:46'),
(95, 1, 16, 'member', 14, 'active', '2026-04-07 08:46:00', '2026-04-07 08:46:00'),
(96, 1, 17, 'member', 15, 'active', '2026-04-07 08:46:15', '2026-04-07 08:46:15'),
(97, 1, 18, 'member', 15, 'active', '2026-04-07 08:50:41', '2026-04-07 08:50:41'),
(98, 1, 19, 'member', 13, 'active', '2026-04-07 08:50:55', '2026-04-07 08:50:55'),
(99, 1, 20, 'member', 14, 'active', '2026-04-07 08:51:05', '2026-04-07 08:51:05'),
(100, 1, 36, 'member', 13, 'active', '2026-04-07 08:51:15', '2026-04-07 08:51:15'),
(101, 1, 37, 'member', 12, 'active', '2026-04-07 08:51:26', '2026-04-07 08:51:26'),
(102, 1, 38, 'member', 15, 'active', '2026-04-07 08:51:36', '2026-04-07 08:51:36'),
(103, 1, 48, 'member', 12, 'active', '2026-04-07 08:51:50', '2026-04-07 08:51:50'),
(104, 12, 76, 'member', 18, 'active', '2026-04-07 09:01:19', '2026-04-07 09:01:19'),
(105, 12, 36, 'member', 17, 'active', '2026-04-07 09:01:25', '2026-04-07 09:01:25'),
(106, 12, 55, 'member', 19, 'active', '2026-04-07 09:01:30', '2026-04-07 09:01:30'),
(107, 12, 50, 'member', 18, 'active', '2026-04-07 09:01:42', '2026-04-07 09:01:42'),
(108, 12, 46, 'member', 18, 'active', '2026-04-07 09:01:49', '2026-04-07 09:01:49'),
(109, 12, 81, 'member', 17, 'active', '2026-04-07 09:01:53', '2026-04-07 09:01:53'),
(110, 12, 73, 'member', 19, 'active', '2026-04-07 09:01:59', '2026-04-07 09:01:59'),
(111, 12, 69, 'member', 17, 'active', '2026-04-07 09:02:03', '2026-04-07 09:02:03'),
(112, 12, 53, 'member', 19, 'active', '2026-04-07 09:02:08', '2026-04-07 09:02:08'),
(113, 12, 65, 'member', 17, 'active', '2026-04-07 09:02:13', '2026-04-07 09:02:13'),
(114, 8, 83, 'member', 20, 'active', '2026-04-07 09:05:14', '2026-04-07 09:05:14'),
(115, 8, 95, 'member', 21, 'active', '2026-04-07 09:05:23', '2026-04-07 09:05:23'),
(116, 8, 32, 'member', 20, 'active', '2026-04-07 09:05:27', '2026-04-07 09:05:27'),
(117, 8, 42, 'member', 21, 'active', '2026-04-07 09:05:34', '2026-04-07 09:05:34'),
(118, 8, 74, 'member', 20, 'active', '2026-04-07 09:05:39', '2026-04-07 09:05:39'),
(119, 8, 88, 'member', 21, 'active', '2026-04-07 09:05:45', '2026-04-07 09:05:45'),
(120, 8, 101, 'member', 20, 'active', '2026-04-07 09:05:51', '2026-04-07 09:05:51'),
(121, 8, 63, 'member', 20, 'active', '2026-04-07 09:05:58', '2026-04-07 09:05:58'),
(122, 8, 79, 'member', 20, 'active', '2026-04-07 09:06:03', '2026-04-07 09:06:03'),
(123, 8, 55, 'member', 21, 'active', '2026-04-07 09:06:09', '2026-04-07 09:06:09'),
(124, 12, 93, 'member', 18, 'active', '2026-04-07 09:06:51', '2026-04-07 09:06:51'),
(125, 12, 63, 'member', 17, 'active', '2026-04-07 09:06:57', '2026-04-07 09:06:57'),
(126, 12, 96, 'member', 18, 'active', '2026-04-07 09:07:03', '2026-04-07 09:07:03'),
(127, 12, 86, 'member', 19, 'active', '2026-04-07 09:07:10', '2026-04-07 09:07:10'),
(128, 12, 64, 'member', 18, 'active', '2026-04-07 09:07:18', '2026-04-07 09:07:18'),
(129, 12, 14, 'member', 18, 'active', '2026-04-07 09:07:24', '2026-04-07 09:07:24'),
(130, 12, 78, 'member', 17, 'active', '2026-04-07 09:07:31', '2026-04-07 09:07:31'),
(131, 12, 101, 'member', 17, 'active', '2026-04-07 09:07:40', '2026-04-07 09:07:40'),
(132, 12, 100, 'member', 18, 'active', '2026-04-07 09:07:45', '2026-04-07 09:07:45'),
(133, 12, 15, 'member', 18, 'active', '2026-04-07 09:07:49', '2026-04-07 09:07:49'),
(134, 12, 97, 'member', 17, 'active', '2026-04-07 09:14:42', '2026-04-07 09:14:42'),
(135, 12, 77, 'member', 19, 'active', '2026-04-07 09:14:48', '2026-04-07 09:14:48'),
(136, 12, 56, 'member', 18, 'active', '2026-04-07 09:14:57', '2026-04-07 09:14:57'),
(137, 12, 94, 'member', 18, 'active', '2026-04-07 09:15:04', '2026-04-07 09:15:04'),
(138, 7, 63, 'member', 23, 'active', '2026-04-07 09:15:55', '2026-04-07 09:15:55'),
(139, 7, 105, 'member', 24, 'active', '2026-04-07 09:16:03', '2026-04-07 09:16:03'),
(140, 7, 32, 'member', 22, 'active', '2026-04-07 09:16:09', '2026-04-07 09:16:09'),
(141, 7, 68, 'member', 22, 'active', '2026-04-07 09:16:15', '2026-04-07 09:16:15'),
(142, 7, 86, 'member', 23, 'active', '2026-04-07 09:16:24', '2026-04-07 09:16:24'),
(143, 7, 89, 'member', 22, 'active', '2026-04-07 09:16:30', '2026-04-07 09:16:30'),
(144, 7, 103, 'member', 24, 'active', '2026-04-07 09:16:36', '2026-04-07 09:16:36'),
(145, 7, 67, 'member', 24, 'active', '2026-04-07 09:16:42', '2026-04-07 09:16:42'),
(146, 7, 51, 'member', 24, 'active', '2026-04-07 09:16:48', '2026-04-07 09:16:48'),
(147, 7, 47, 'member', 23, 'active', '2026-04-07 09:16:55', '2026-04-07 09:16:55'),
(148, 8, 21, 'member', 20, 'active', '2026-04-07 09:17:35', '2026-04-07 09:17:35'),
(149, 4, 76, 'member', 25, 'active', '2026-04-07 09:25:01', '2026-04-07 09:25:01'),
(150, 4, 95, 'member', 27, 'active', '2026-04-07 09:25:09', '2026-04-07 09:25:09'),
(151, 4, 87, 'member', 26, 'active', '2026-04-07 09:25:22', '2026-04-07 09:25:22'),
(152, 4, 98, 'member', 29, 'active', '2026-04-07 09:25:27', '2026-04-07 09:25:27'),
(153, 4, 69, 'member', 31, 'active', '2026-04-07 09:25:32', '2026-04-07 09:25:32'),
(154, 4, 37, 'member', 28, 'active', '2026-04-07 09:25:41', '2026-04-07 09:25:41'),
(155, 4, 44, 'member', 30, 'active', '2026-04-07 09:25:48', '2026-04-07 09:25:48'),
(156, 4, 57, 'member', 31, 'active', '2026-04-07 09:25:59', '2026-04-07 09:25:59'),
(157, 4, 92, 'member', 27, 'active', '2026-04-07 09:26:05', '2026-04-07 09:26:05'),
(158, 4, 9, 'member', 30, 'active', '2026-04-07 09:26:12', '2026-04-07 09:26:12'),
(159, 5, 83, 'member', 32, 'active', '2026-04-07 09:35:20', '2026-04-07 09:35:20'),
(160, 5, 92, 'member', 34, 'active', '2026-04-07 09:35:28', '2026-04-07 09:35:28'),
(161, 5, 95, 'member', 33, 'active', '2026-04-07 09:35:35', '2026-04-07 09:35:35'),
(162, 5, 4, 'member', 32, 'active', '2026-04-07 09:35:45', '2026-04-07 09:35:45'),
(163, 5, 45, 'member', 34, 'active', '2026-04-07 09:35:51', '2026-04-07 09:35:51'),
(164, 5, 69, 'member', 33, 'active', '2026-04-07 09:36:02', '2026-04-07 09:36:02'),
(165, 5, 2, 'member', 33, 'active', '2026-04-07 09:36:10', '2026-04-07 09:36:10'),
(166, 5, 24, 'member', 34, 'active', '2026-04-07 09:36:16', '2026-04-07 09:36:16'),
(167, 5, 22, 'member', 34, 'active', '2026-04-07 09:36:29', '2026-04-07 09:36:29'),
(168, 5, 52, 'member', 32, 'active', '2026-04-07 09:36:37', '2026-04-07 09:36:37'),
(169, 4, 65, 'member', 26, 'active', '2026-04-07 09:36:52', '2026-04-07 09:36:52'),
(170, 4, 18, 'member', 25, 'active', '2026-04-07 09:37:07', '2026-04-07 09:37:07'),
(171, 4, 67, 'member', 27, 'active', '2026-04-07 09:37:12', '2026-04-07 09:37:12'),
(172, 4, 85, 'member', 26, 'active', '2026-04-07 09:37:26', '2026-04-07 09:37:26'),
(173, 4, 71, 'member', 28, 'active', '2026-04-07 09:37:34', '2026-04-07 09:37:34'),
(174, 11, 67, 'member', 37, 'active', '2026-04-07 09:43:28', '2026-04-07 09:43:28'),
(175, 11, 8, 'member', 35, 'active', '2026-04-07 09:43:38', '2026-04-07 09:43:38'),
(176, 11, 57, 'member', 38, 'active', '2026-04-07 09:43:42', '2026-04-07 09:43:42'),
(177, 11, 93, 'member', 36, 'active', '2026-04-07 09:43:51', '2026-04-07 09:43:51'),
(178, 11, 96, 'member', 36, 'active', '2026-04-07 09:44:02', '2026-04-07 09:44:02'),
(179, 11, 27, 'member', 35, 'active', '2026-04-07 09:44:11', '2026-04-07 09:44:11'),
(180, 10, 25, 'member', 39, 'active', '2026-04-07 10:04:57', '2026-04-07 10:04:57'),
(181, 10, 103, 'member', 40, 'active', '2026-04-07 10:05:06', '2026-04-07 10:05:06'),
(182, 10, 8, 'member', 39, 'active', '2026-04-07 10:05:14', '2026-04-07 10:05:14'),
(183, 10, 7, 'member', 39, 'active', '2026-04-07 10:05:18', '2026-04-07 10:05:18'),
(184, 10, 104, 'member', 40, 'active', '2026-04-07 10:05:22', '2026-04-07 10:05:22'),
(185, 10, 12, 'member', 41, 'active', '2026-04-07 10:05:26', '2026-04-07 10:05:26'),
(186, 10, 73, 'member', 40, 'active', '2026-04-07 10:05:34', '2026-04-07 10:05:34'),
(187, 10, 57, 'member', 39, 'active', '2026-04-07 10:05:58', '2026-04-07 10:05:58'),
(188, 10, 15, 'member', 41, 'active', '2026-04-07 10:06:01', '2026-04-07 10:06:01'),
(189, 10, 63, 'member', 40, 'active', '2026-04-07 10:06:05', '2026-04-07 10:06:05'),
(190, 5, 12, 'member', 33, 'active', '2026-04-07 10:06:34', '2026-04-07 10:06:34'),
(191, 5, 77, 'member', 32, 'active', '2026-04-07 10:07:47', '2026-04-07 10:07:47'),
(192, 5, 102, 'member', 33, 'active', '2026-04-07 10:07:53', '2026-04-07 10:07:53'),
(193, 5, 63, 'member', 34, 'active', '2026-04-07 10:08:03', '2026-04-07 10:08:03'),
(194, 5, 54, 'member', 33, 'active', '2026-04-07 10:08:09', '2026-04-07 10:08:09'),
(195, 5, 56, 'member', 32, 'active', '2026-04-07 10:08:16', '2026-04-07 10:08:16'),
(196, 9, 5, 'member', 42, 'active', '2026-04-07 10:13:11', '2026-04-07 10:13:11'),
(197, 9, 19, 'member', 43, 'active', '2026-04-07 10:13:18', '2026-04-07 10:13:18'),
(198, 9, 50, 'member', 42, 'active', '2026-04-07 10:13:23', '2026-04-07 10:13:23'),
(199, 9, 76, 'member', 42, 'active', '2026-04-07 10:13:29', '2026-04-07 10:13:29'),
(200, 9, 100, 'member', 42, 'active', '2026-04-07 10:13:35', '2026-04-07 10:13:35'),
(201, 9, 73, 'member', 43, 'active', '2026-04-07 10:13:42', '2026-04-07 10:13:42'),
(202, 9, 92, 'member', 43, 'active', '2026-04-07 10:13:49', '2026-04-07 10:13:49'),
(203, 9, 22, 'member', 43, 'active', '2026-04-07 10:13:56', '2026-04-07 10:13:56'),
(204, 9, 43, 'member', 42, 'active', '2026-04-07 10:14:03', '2026-04-07 10:14:03'),
(205, 9, 21, 'member', 43, 'active', '2026-04-07 10:14:17', '2026-04-07 10:14:17'),
(206, 10, 37, 'member', 39, 'active', '2026-04-07 10:15:02', '2026-04-07 10:15:02'),
(207, 10, 90, 'member', 39, 'active', '2026-04-07 10:15:15', '2026-04-07 10:15:15'),
(208, 10, 51, 'member', 40, 'active', '2026-04-07 10:15:20', '2026-04-07 10:15:20'),
(209, 10, 88, 'member', 39, 'active', '2026-04-07 10:15:24', '2026-04-07 10:15:24'),
(210, 10, 64, 'member', 41, 'active', '2026-04-07 10:15:32', '2026-04-07 10:15:32'),
(211, 10, 23, 'member', 39, 'active', '2026-04-07 10:15:37', '2026-04-07 10:15:37'),
(212, 10, 28, 'member', 39, 'active', '2026-04-07 10:15:41', '2026-04-07 10:15:41'),
(213, 10, 86, 'member', 40, 'active', '2026-04-07 10:15:45', '2026-04-07 10:15:45'),
(214, 10, 74, 'member', 41, 'active', '2026-04-07 10:15:50', '2026-04-07 10:15:50'),
(215, 10, 33, 'member', 41, 'active', '2026-04-07 10:15:55', '2026-04-07 10:15:55'),
(216, 2, 103, 'member', 44, 'active', '2026-04-07 10:25:01', '2026-04-07 10:25:01'),
(217, 2, 102, 'member', 45, 'active', '2026-04-07 10:25:06', '2026-04-07 10:25:06'),
(218, 2, 98, 'member', 47, 'active', '2026-04-07 10:25:11', '2026-04-07 10:25:11'),
(219, 2, 94, 'member', 44, 'active', '2026-04-07 10:25:15', '2026-04-07 10:25:15'),
(220, 2, 18, 'member', 48, 'active', '2026-04-07 10:25:20', '2026-04-07 10:25:20'),
(221, 2, 77, 'member', 49, 'active', '2026-04-07 10:25:25', '2026-04-07 10:25:25'),
(222, 2, 50, 'member', 47, 'active', '2026-04-07 10:25:30', '2026-04-07 10:25:30'),
(223, 2, 83, 'member', 46, 'active', '2026-04-07 10:25:34', '2026-04-07 10:25:34'),
(224, 2, 25, 'member', 44, 'active', '2026-04-07 10:25:50', '2026-04-07 10:25:50'),
(225, 2, 97, 'member', 47, 'active', '2026-04-07 10:25:55', '2026-04-07 10:25:55'),
(226, 6, 84, 'member', 50, 'active', '2026-04-07 10:31:28', '2026-04-07 10:31:28'),
(227, 6, 32, 'member', 51, 'active', '2026-04-07 10:31:34', '2026-04-07 10:31:34'),
(228, 6, 87, 'member', 51, 'active', '2026-04-07 10:31:41', '2026-04-07 10:31:41'),
(229, 6, 79, 'member', 52, 'active', '2026-04-07 10:31:47', '2026-04-07 10:31:47'),
(230, 6, 19, 'member', 51, 'active', '2026-04-07 10:31:52', '2026-04-07 10:31:52'),
(231, 6, 86, 'member', 52, 'active', '2026-04-07 10:31:59', '2026-04-07 10:31:59'),
(232, 6, 10, 'member', 50, 'active', '2026-04-07 10:32:05', '2026-04-07 10:32:05'),
(233, 6, 17, 'member', 53, 'active', '2026-04-07 10:32:14', '2026-04-07 10:32:14'),
(234, 6, 2, 'member', 51, 'active', '2026-04-07 10:32:22', '2026-04-07 10:32:22'),
(235, 6, 63, 'member', 52, 'active', '2026-04-07 10:32:27', '2026-04-07 10:32:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 7, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 1, '2026-04-07 06:52:45'),
(2, 64, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:53:57'),
(3, 62, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:01'),
(4, 11, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:07'),
(5, 20, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:15'),
(6, 82, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:21'),
(7, 87, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:33'),
(8, 98, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:41'),
(9, 97, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:47'),
(10, 31, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 06:54:52'),
(11, 75, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:09:31'),
(12, 5, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:11:01'),
(13, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:11:18'),
(14, 50, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:11:33'),
(15, 51, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:11:48'),
(16, 33, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:12:04'),
(17, 43, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:12:20'),
(18, 105, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:12:43'),
(19, 91, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:13:00'),
(20, 35, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Dấu chân tình nguyện. Chào mừng bạn đến với CLB!', 'club-detail.php?id=3', 0, '2026-04-07 07:13:18'),
(21, 101, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:34:46'),
(22, 53, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:35:06'),
(23, 93, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:35:23'),
(24, 47, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 1, '2026-04-07 07:35:43'),
(25, 26, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:35:56'),
(26, 48, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 1, '2026-04-07 07:36:09'),
(27, 42, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:36:23'),
(28, 70, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:36:39'),
(29, 27, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:36:57'),
(30, 54, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:37:12'),
(31, 69, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:39:57'),
(32, 64, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:40:18'),
(33, 99, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:40:37'),
(34, 76, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 1, '2026-04-07 07:40:52'),
(35, 77, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:41:23'),
(36, 45, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:41:35'),
(37, 38, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:41:56'),
(38, 52, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:42:21'),
(39, 73, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:42:40'),
(40, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:42:55'),
(41, 19, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:46:57'),
(42, 6, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:47:19'),
(43, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:47:40'),
(44, 75, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:47:58'),
(45, 82, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:48:19'),
(46, 61, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:48:51'),
(47, 86, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:49:08'),
(48, 66, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:49:23'),
(49, 94, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:49:39'),
(50, 29, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:49:58'),
(51, 49, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:52:20'),
(52, 71, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:53:11'),
(53, 17, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:53:18'),
(54, 103, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:53:37'),
(55, 21, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên xung kích Khoa CNTT. Chào mừng bạn đến với CLB!', 'club-detail.php?id=13', 0, '2026-04-07 07:53:57'),
(56, 4, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:18:30'),
(57, 2, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:28:08'),
(58, 3, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:28:21'),
(59, 5, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:28:33'),
(60, 6, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:28:44'),
(61, 7, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:28:52'),
(62, 8, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:29:01'),
(63, 9, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:29:14'),
(64, 10, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:29:24'),
(65, 21, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:29:34'),
(66, 24, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:30:07'),
(67, 25, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:33:54'),
(68, 26, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:34:07'),
(69, 27, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:34:32'),
(70, 28, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:34:45'),
(71, 29, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:34:58'),
(72, 30, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 1, '2026-04-07 08:35:07'),
(73, 31, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:35:17'),
(74, 32, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:35:26'),
(75, 42, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:35:35'),
(76, 43, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:35:57'),
(77, 33, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:26'),
(78, 51, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:31'),
(79, 98, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:34'),
(80, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:40'),
(81, 58, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:45'),
(82, 44, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:43:50'),
(83, 88, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 1, '2026-04-07 08:43:59'),
(84, 105, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:44:03'),
(85, 16, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:44:08'),
(86, 72, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 08:44:12'),
(87, 11, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:44:37'),
(88, 12, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:44:48'),
(89, 13, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:44:57'),
(90, 14, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:45:07'),
(91, 15, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:45:18'),
(92, 33, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:45:28'),
(93, 34, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:45:39'),
(94, 35, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:45:46'),
(95, 16, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:46:00'),
(96, 17, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:46:15'),
(97, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:50:41'),
(98, 19, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:50:55'),
(99, 20, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:51:05'),
(100, 36, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:51:15'),
(101, 37, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:51:26'),
(102, 38, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:51:36'),
(103, 48, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Tình nguyện Những người bạn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=1', 0, '2026-04-07 08:51:50'),
(104, 76, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 1, '2026-04-07 09:01:19'),
(105, 36, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:25'),
(106, 55, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:30'),
(107, 50, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:42'),
(108, 46, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:49'),
(109, 81, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:53'),
(110, 73, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:01:59'),
(111, 69, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:02:03'),
(112, 53, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:02:08'),
(113, 65, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:02:13'),
(114, 83, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:14'),
(115, 95, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:23'),
(116, 32, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:27'),
(117, 42, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:34'),
(118, 74, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:39'),
(119, 88, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 1, '2026-04-07 09:05:45'),
(120, 101, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:51'),
(121, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:05:58'),
(122, 79, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:06:03'),
(123, 55, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:06:09'),
(124, 93, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:06:51'),
(125, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:06:57'),
(126, 96, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:03'),
(127, 86, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:10'),
(128, 64, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:18'),
(129, 14, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:24'),
(130, 78, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:31'),
(131, 101, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:40'),
(132, 100, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:45'),
(133, 15, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:07:49'),
(134, 97, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:14:42'),
(135, 77, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:14:48'),
(136, 56, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:14:57'),
(137, 94, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Đội Thanh niên tình nguyện Đại học Quy Nhơn. Chào mừng bạn đến với CLB!', 'club-detail.php?id=12', 0, '2026-04-07 09:15:04'),
(138, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:15:55'),
(139, 105, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:03'),
(140, 32, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:09'),
(141, 68, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 1, '2026-04-07 09:16:15'),
(142, 86, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:24'),
(143, 89, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:30'),
(144, 103, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:36'),
(145, 67, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:42'),
(146, 51, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 0, '2026-04-07 09:16:48'),
(147, 47, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Trung. Chào mừng bạn đến với CLB!', 'club-detail.php?id=7', 1, '2026-04-07 09:16:55'),
(148, 21, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Khởi nghiệp. Chào mừng bạn đến với CLB!', 'club-detail.php?id=8', 0, '2026-04-07 09:17:35'),
(149, 76, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 1, '2026-04-07 09:25:01'),
(150, 95, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:09'),
(151, 87, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:22'),
(152, 98, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:27'),
(153, 69, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:32'),
(154, 37, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:41'),
(155, 44, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:48'),
(156, 57, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:25:59'),
(157, 92, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:26:05'),
(158, 9, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:26:12'),
(159, 83, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:35:20'),
(160, 92, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:35:28'),
(161, 95, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:35:35'),
(162, 4, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:35:45'),
(163, 45, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:35:51'),
(164, 69, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:36:02'),
(165, 2, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:36:10'),
(166, 24, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:36:16'),
(167, 22, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:36:29'),
(168, 52, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 09:36:37'),
(169, 65, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:36:52'),
(170, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:37:07'),
(171, 67, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:37:12'),
(172, 85, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:37:26'),
(173, 71, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Kết nối trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=4', 0, '2026-04-07 09:37:34'),
(174, 67, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:43:28'),
(175, 8, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:43:38'),
(176, 57, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:43:42'),
(177, 93, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:43:51'),
(178, 96, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:44:02'),
(179, 27, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Tiếng Việt QNU. Chào mừng bạn đến với CLB!', 'club-detail.php?id=11', 0, '2026-04-07 09:44:11'),
(180, 25, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:04:57'),
(181, 103, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:06'),
(182, 8, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:14'),
(183, 7, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:18'),
(184, 104, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:22'),
(185, 12, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:26'),
(186, 73, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:34'),
(187, 57, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:05:58'),
(188, 15, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:06:01'),
(189, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:06:05'),
(190, 12, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:06:34'),
(191, 77, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:07:47'),
(192, 102, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:07:53'),
(193, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:08:03'),
(194, 54, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:08:09'),
(195, 56, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Sách & Hành động. Chào mừng bạn đến với CLB!', 'club-detail.php?id=5', 0, '2026-04-07 10:08:16'),
(196, 5, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:11'),
(197, 19, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:18'),
(198, 50, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:23'),
(199, 76, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 1, '2026-04-07 10:13:29'),
(200, 100, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:35'),
(201, 73, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:42'),
(202, 92, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:49'),
(203, 22, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:13:56'),
(204, 43, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:14:03'),
(205, 21, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Điền kinh. Chào mừng bạn đến với CLB!', 'club-detail.php?id=9', 0, '2026-04-07 10:14:17'),
(206, 37, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:02'),
(207, 90, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:15'),
(208, 51, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:20'),
(209, 88, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:24'),
(210, 64, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:32'),
(211, 23, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:37'),
(212, 28, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:41'),
(213, 86, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:45'),
(214, 74, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:50'),
(215, 33, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB CLB Những nhà giáo trẻ. Chào mừng bạn đến với CLB!', 'club-detail.php?id=10', 0, '2026-04-07 10:15:55'),
(216, 103, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:01'),
(217, 102, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:06'),
(218, 98, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:11'),
(219, 94, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:15'),
(220, 18, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:20'),
(221, 77, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:25'),
(222, 50, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:30'),
(223, 83, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:34'),
(224, 25, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:50'),
(225, 97, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Câu lạc bộ Kỹ năng. Chào mừng bạn đến với CLB!', 'club-detail.php?id=2', 0, '2026-04-07 10:25:55'),
(226, 84, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:28'),
(227, 32, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:34'),
(228, 87, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:41'),
(229, 79, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:47'),
(230, 19, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:52'),
(231, 86, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:31:59'),
(232, 10, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:32:05'),
(233, 17, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:32:14'),
(234, 2, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:32:22'),
(235, 63, 'club_join', 'Bạn đã được thêm vào CLB', 'Bạn đã được thêm vào CLB Viết tiếp ước mơ giảng đường. Chào mừng bạn đến với CLB!', 'club-detail.php?id=6', 0, '2026-04-07 10:32:27'),
(236, 48, 'club_join', 'Có yêu cầu tham gia CLB mới', 'Chu Thị Kim Anh đã gửi yêu cầu tham gia CLB \"Câu lạc bộ Kỹ năng\"', 'Dashboard.php?id=2&member_id=236#pending-requests', 1, '2026-04-08 09:27:07'),
(237, 76, 'club_join', '❌ Yêu cầu tham gia CLB bị từ chối', 'Rất tiếc, yêu cầu tham gia CLB \"Câu lạc bộ Kỹ năng\" của bạn đã bị Lại Thị Thúy từ chối. Bạn có thể thử lại sau hoặc tham gia các CLB khác.', 'DanhsachCLB.php', 1, '2026-04-08 09:28:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `banner_id` int(11) DEFAULT NULL,
  `logo_id` int(11) DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `pages`
--

INSERT INTO `pages` (`id`, `club_id`, `slogan`, `about`, `banner_id`, `logo_id`, `primary_color`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 1, '', 'Kết nối - Trái tim - Thiện nguyện', 36, 3, '#5973e8', 1, '2026-04-06 09:42:49', '2026-04-07 08:15:50'),
(2, 2, NULL, NULL, NULL, 4, NULL, 1, '2026-04-06 09:54:27', '2026-04-06 09:54:27'),
(3, 3, '', 'Nơi tập trung những con người giàu nhiệt huyết\r\nCống hiến sức trẻ cho Tổ quốc', 26, 5, '#667eea', 1, '2026-04-06 09:59:09', '2026-04-07 06:56:20'),
(4, 4, NULL, NULL, NULL, 6, NULL, 1, '2026-04-06 13:15:18', '2026-04-06 13:16:03'),
(5, 5, NULL, NULL, NULL, 7, NULL, 1, '2026-04-06 13:19:10', '2026-04-06 13:19:10'),
(6, 6, NULL, NULL, NULL, 8, NULL, 1, '2026-04-06 13:23:36', '2026-04-06 13:24:29'),
(7, 7, '', 'Học tiếng Trung – Kết nối văn hóa.', 38, 9, '#ff6842', 1, '2026-04-06 13:32:16', '2026-04-07 09:12:36'),
(8, 8, NULL, NULL, NULL, 10, NULL, 1, '2026-04-06 13:37:29', '2026-04-06 13:38:01'),
(9, 9, NULL, NULL, NULL, 11, NULL, 1, '2026-04-06 13:41:22', '2026-04-06 13:41:22'),
(10, 10, NULL, NULL, NULL, 12, NULL, 1, '2026-04-06 13:47:24', '2026-04-06 13:47:24'),
(11, 11, NULL, NULL, NULL, 13, NULL, 1, '2026-04-06 13:48:46', '2026-04-06 13:48:46'),
(12, 12, NULL, NULL, NULL, 14, NULL, 1, '2026-04-06 13:51:52', '2026-04-06 13:51:52'),
(13, 13, '', 'Nhiệt huyết – Trách nhiệm – Tiên phong', 27, 15, '#5670f0', 1, '2026-04-06 13:53:50', '2026-04-07 07:17:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `stats`
--

CREATE TABLE `stats` (
  `id` int(11) NOT NULL,
  `club_id` int(11) NOT NULL,
  `total_events` int(11) DEFAULT 0,
  `total_members` int(11) DEFAULT 0,
  `total_reviews` int(11) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `stats`
--

INSERT INTO `stats` (`id`, `club_id`, `total_events`, `total_members`, `total_reviews`, `updated_at`) VALUES
(1, 1, 1, 0, 0, '2026-04-07 14:36:44'),
(2, 3, 1, 0, 0, '2026-04-07 14:36:44'),
(3, 4, 1, 0, 0, '2026-04-07 14:36:44'),
(4, 6, 1, 0, 0, '2026-04-07 14:36:44'),
(5, 7, 1, 0, 0, '2026-04-07 14:36:44'),
(6, 12, 1, 0, 0, '2026-04-07 14:36:44'),
(7, 13, 2, 0, 0, '2026-04-07 14:36:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `class_name` varchar(50) DEFAULT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT 'other',
  `role` varchar(50) DEFAULT 'member',
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `avatar`, `phone`, `student_id`, `class_name`, `faculty`, `gender`, `role`, `password`, `remember_token`, `remember_expiry`, `created_at`) VALUES
(1, 'Administrator', 'Admin', 'admin@leaderclub.com', NULL, NULL, NULL, NULL, NULL, 'other', 'admin', '$2y$10$oHyNZ5CmLzw6ieN.mbiUKucTsrpxZtOtWYwp/DulUGB6PDevtkwRu', NULL, NULL, '2026-03-29 14:23:03'),
(2, 'Lê Chí Thành', 'thanhle', 'thanhle2003@gmail.com', NULL, '0981234568', '4657510228', 'Ngôn Ngữ Anh K46', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-01 02:30:00'),
(3, 'Trần Bảo Trân', 'tran0204', 'tran0204@gmail.com', NULL, '0981234569', '4654010108', 'QTKD K46B', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-02 03:15:00'),
(4, 'Uyên Trang', 'trang2005', 'trang2005@gmail.com', NULL, '4651050284', '4651050284', 'CNTT K46', 'Công nghệ thông tin', 'female', 'member', '$2y$10$e8mFY4ZHIb.szHMgmY/eQO/EE4lNWqTjC2GPNiHvoxo6.tyW6j9pi', NULL, NULL, '2026-03-29 04:45:41'),
(5, 'Nguyễn Thanh Hiệp', 'hiepnguyen', 'hiepnguyen@gmail.com', NULL, '0981234571', '4659010147', 'Giáo dục tiểu học K46', 'Giáo dục tiểu học và mầm non', 'female', 'member', '12345678', NULL, NULL, '2026-03-03 04:00:00'),
(6, 'Ngô Thị Phượng', 'phuongngo', 'phuong.ngo@gmail.com', NULL, '0981234572', '4654030100', 'Kinh tế K46', 'Kinh tế kế toán', 'female', 'member', '12345678', NULL, NULL, '2026-03-04 01:45:00'),
(7, 'Lê Trà My', 'letramy', 'letramy@gmail.com', NULL, '0981234573', '4654040068', 'Kế toán K46', 'Kinh tế kế toán', 'female', 'member', '$2y$10$x3XyWI11bK8OxixbiLhraeL6nHkDqTFd05Tf8ch477gciCOh4sdeG', NULL, NULL, '2026-03-04 06:30:00'),
(8, 'Vũ Thị Hà', 'havu', 'ha.vu@gmail.com', NULL, '0981234574', '4652070053', 'Công nghệ thực phẩm K46', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-04 09:20:00'),
(9, 'Nguyễn Văn Sơn', 'nguyenson', 'nguyenson@gmail.com', NULL, '0981234575', '46513000150', 'Công nghệ kỹ thuật ô tô K46', 'Kỹ thuật ô tô', 'male', 'member', '12345678', NULL, NULL, '2026-03-05 03:00:00'),
(10, 'Nguyễn Như Ý', 'nguyeny', 'nguyeny@gmail.com', NULL, '0981234576', '4653030013', 'Nông học K46', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-06 02:15:00'),
(11, 'Trương Văn Hải', 'haitruong', 'hai.truong@gmail.com', NULL, '0981234577', '4756060049', 'Văn học K47', 'Khoa học xã hội nhân văn', 'male', 'member', '12345678', NULL, NULL, '2026-03-06 07:00:00'),
(12, 'Đặng Thị Lan', 'landang', 'lan.dang@gmail.com', NULL, '0981234578', '4756130105', 'Đông phương học K47', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-07 04:30:00'),
(13, 'Phan Văn Minh', 'minhphan', 'minh.phan@gmail.com', NULL, '0981234579', '4757510265', 'Ngôn ngữ Anh K47', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-08 01:00:00'),
(14, 'Hồ Thị Ngọc', 'ngocho', 'ngoc.ho@gmail.com', NULL, '0981234580', '4756060012', 'Văn học K47', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-08 08:45:00'),
(15, 'Nguyễn Văn Phúc', 'phucnguyen', 'phuc.nguyen@gmail.com', NULL, '0981234581', '4751050156', 'CNTT K47', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-09 03:30:00'),
(16, 'Lưu Thị Quỳnh', 'quynhluu', 'quynh.luu@gmail.com', NULL, '0981234582', '4853030029', 'Nông học K48', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-10 02:00:00'),
(17, 'Mai Văn Sơn', 'sonmai', 'son.mai@gmail.com', NULL, '0981234583', '4856130109', 'Đông phương học K48', 'Khoa học xã hội nhân văn', 'male', 'member', '12345678', NULL, NULL, '2026-03-10 06:20:00'),
(18, 'Trần Ngọc Tâm', 'tranngoctam', 'tranngoctam@gmail.com', NULL, '0981234584', '4857010043', 'Sư phạm Tiếng Anh K48', 'Sư phạm', 'female', 'member', '12345678', NULL, NULL, '2026-03-10 09:00:00'),
(19, 'Võ Văn Tùng', 'tungvo', 'tung.vo@gmail.com', NULL, '0981234585', '4852010015', 'Sư phạm Hóa học K48', 'Sư phạm', 'male', 'member', '12345678', NULL, NULL, '2026-03-11 04:15:00'),
(20, 'Nguyễn Thị Yến', 'yennguyen', 'yen.nguyen@gmail.com', NULL, '0981234586', '4857520018', 'Ngôn ngữ Trung Quốc K48', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-12 01:45:00'),
(21, 'Phạm Thị Dung', 'dungpham', 'dungpham@gmail.com', NULL, '0981234570', '4651130020', 'Sư phạm Tin học K46', 'Sư phạm', 'female', 'member', '12345678', NULL, NULL, '2026-03-02 07:20:00'),
(22, 'Nguyễn Tiên', 'nguyentien', 'nguyentien@gmail.com', NULL, '0981558907', '4651050108', 'CNTT K46', 'Công nghệ thông tin', 'male', 'member', '$2y$10$D.inljuMk5xKJ5if83kICe2mL8vsC/SrzGKM50DiEo/ektwvJTrUO', NULL, NULL, '2026-03-15 01:00:00'),
(23, 'Nguyễn Mai Thùy Duyên', 'duyennguyen', 'duyennguyen@gmail.com', NULL, '0986438192', '4654060010', 'Du lịch K46', 'Quản trị dịch vụ du lịch và lữ hành', 'female', 'member', '$2y$10$27U3I6Lhs.y89ITYi75cKOIt2OMMp9.9MPpQAfUDGbo1R2aYIqW4O', NULL, NULL, '2026-03-15 11:00:00'),
(24, 'Phùng Nhật Quang', 'nhatquang', 'nahtquang@gmail.com', NULL, '0824624742', '4654010026', 'Quản trị kinh doanh K46', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-16 00:00:00'),
(25, 'Phạm Hoàng Thủy Tiên', 'thuytien', 'thuytien@gmail.com', NULL, '0981247192', '4657510228', 'Ngôn Ngữ Anh K46', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-16 01:00:00'),
(26, 'Tạ Trung Hiếu', 'trunghieu', 'trunghieu@gmail.com', NULL, '0981118637', '4657520023', 'Ngôn Ngữ Trung Quốc K46', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-16 03:20:00'),
(27, 'Dương Hải Anh', 'haianh', 'haianh@gmail.com', NULL, '0975632859', '4651190026', 'Kỹ thuật phần mềm K46', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-16 07:40:00'),
(28, 'Lê Minh Tú', 'minhtu', 'minhtu@gmail.com', NULL, '0273482935', '4654030067', 'Kinh tế K46', 'Kinh tế kế toán', 'male', 'member', '12345678', NULL, NULL, '2026-03-16 08:00:00'),
(29, 'Trần Quốc Hùng', 'quochung', 'quochung@gmail.com', NULL, '0881428609', '4651300065', 'Công nghệ kỹ thuật ô tô K46', 'Kỹ thuật ô tô', 'male', 'member', '12345678', NULL, NULL, '2026-03-16 10:00:00'),
(30, 'Đỗ Thị Thanh Tâm', 'thanhtam', 'thanhtam@gmail.com', NULL, '0981234608', '4651050120', 'CNTT K46', 'Công nghệ thông tin', 'female', 'member', '$2y$10$fTIsQGOlDLzW8k.bws5d.eXbVTpwKbOvnPX1tELpjTxknXp7Z3KGW', NULL, NULL, '2026-03-17 01:00:00'),
(31, 'Bùi Quang Huy', 'quanghuy', 'quanghuy@gmail.com', NULL, '0981234609', '4654010210', 'QTKD K46', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-17 02:00:00'),
(32, 'Lý Thị Mỹ Duyên', 'myduyenly', 'myduyenly@gmail.com', NULL, '0981234610', '4657510230', 'Ngôn ngữ Anh K46', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-17 03:00:00'),
(33, 'Đinh Văn Hoàng', 'hoangdinh', 'hoangdinh@gmail.com', NULL, '0981234611', '4751050159', 'CNTT K47', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-17 04:00:00'),
(34, 'Trương Thị Hồng', 'hongtruong', 'hongtruong@gmail.com', NULL, '0981234612', '4754010108', 'QTKD K47', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-17 07:00:00'),
(35, 'Lương Văn Đức', 'ducluong', 'ducluong@gmail.com', NULL, '0981234613', '4757510267', 'Ngôn ngữ Anh K47', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-17 08:00:00'),
(36, 'Vương Thị Ngọc', 'ngocvuong', 'ngocvuong@gmail.com', NULL, '0981234614', '4851050161', 'CNTT K48', 'Công nghệ thông tin', 'female', 'member', '12345678', NULL, NULL, '2026-03-18 01:00:00'),
(37, 'Hà Văn Thắng', 'thangha', 'thangha@gmail.com', NULL, '0981234615', '4854010110', 'QTKD K48', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-18 02:00:00'),
(38, 'Nguyễn Thị Thu Trang', 'thutrang', 'thutrang@gmail.com', NULL, '0981234616', '4857510268', 'Ngôn ngữ Anh K48', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-18 03:00:00'),
(42, 'Chu Thị Huyền', 'huyenchu', 'huyenchu@gmail.com', NULL, '0981234620', '4651050121', 'CNTT K46', 'Công nghệ thông tin', 'female', 'member', '12345678', NULL, NULL, '2026-03-19 01:00:00'),
(43, 'Phùng Văn Kiên', 'kienphung', 'kienphung@gmail.com', NULL, '0981234621', '4654010211', 'QTKD K46', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-19 02:00:00'),
(44, 'Triệu Thị Thanh', 'thanhtrieu', 'thanhtrieu@gmail.com', NULL, '0981234622', '4657510231', 'Ngôn ngữ Anh K46', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-19 03:00:00'),
(45, 'Vũ Quang Hải', 'haivu', 'haivu@gmail.com', NULL, '0981234623', '4751050160', 'CNTT K47', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-19 04:00:00'),
(46, 'Nguyễn Thị Hồng Nhung', 'hongnhung', 'hongnhung@gmail.com', NULL, '0981234624', '4754010109', 'QTKD K47', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-19 07:00:00'),
(47, 'Trịnh Văn Đại', 'daitrinh', 'daitrinh@gmail.com', NULL, '0981234625', '4757510268', 'Ngôn ngữ Anh K47', 'Ngoại ngữ', 'male', 'member', '$2y$10$yKiUawWmGQ1M2gZduiai/OSWDf8QfSGf16Hcs8SRYfgycZCeXGY1u', NULL, NULL, '2026-03-19 08:00:00'),
(48, 'Lại Thị Thúy', 'thuylai', 'thuylai@gmail.com', NULL, '0981234626', '4851050162', 'CNTT K48', 'Công nghệ thông tin', 'female', 'member', '$2y$10$vnxeq0uWApvdaodehA0INunriYnKSMLbl64tjiaIlTBjiv3tfsCtG', NULL, NULL, '2026-03-20 01:00:00'),
(49, 'Đỗ Văn Thịnh', 'thinhdo', 'thinhdo@gmail.com', NULL, '0981234627', '4854010111', 'QTKD K48', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-20 02:00:00'),
(50, 'Bạch Thị Lan', 'lanbach', 'lanbach@gmail.com', NULL, '0981234628', '4857510269', 'Ngôn ngữ Anh K48', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-20 03:00:00'),
(51, 'Hồ Văn Long', 'longho', 'longho@gmail.com', NULL, '0981234629', '4851050164', 'CNTT K48', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-20 04:00:00'),
(52, 'Trần Thị Minh Thư', 'minhthu', 'minhthu@gmail.com', NULL, '0981234630', '4854010113', 'QTKD K48', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-20 07:00:00'),
(53, 'Nguyễn Văn Thành', 'thanhnguyen', 'thanhnguyen@gmail.com', NULL, '0981234631', '4653030014', 'Nông học K46', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-21 01:00:00'),
(54, 'Phạm Thị Thu Hà', 'thuha', 'thuha2@gmail.com', NULL, '0981234632', '4654040069', 'Kế toán K46', 'Kinh tế kế toán', 'female', 'member', '12345678', NULL, NULL, '2026-03-21 02:00:00'),
(55, 'Đặng Văn Tuấn', 'tuandang', 'tuandang@gmail.com', NULL, '0981234633', '4756060050', 'Văn học K47', 'Khoa học xã hội nhân văn', 'male', 'member', '12345678', NULL, NULL, '2026-03-21 03:00:00'),
(56, 'Lê Thị Phương Thảo', 'phuongthao', 'phuongthao@gmail.com', NULL, '0981234634', '4756130106', 'Đông phương học K47', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-21 04:00:00'),
(57, 'Hoàng Văn Đạt', 'dathoang', 'dathoang@gmail.com', NULL, '0981234635', '4853030030', 'Nông học K48', 'Khoa học tự nhiên', 'male', 'member', '$2y$10$V/t26Rizk15xIYQr2TrnUuJ/9TUScZWrHjYCqw14I1joQFqA2Dh86', NULL, NULL, '2026-03-21 07:00:00'),
(58, 'Vũ Thị Thanh Huyền', 'thanhhuyen', 'thanhhuyen@gmail.com', NULL, '0981234636', '4856130110', 'Đông phương học K48', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-21 08:00:00'),
(61, 'Lý Văn Hùng', 'hungly', 'hungly@gmail.com', NULL, '0981234639', '4652070054', 'Công nghệ thực phẩm K46', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-22 03:00:00'),
(62, 'Đỗ Thị Thanh Thủy', 'thanhthuy', 'thanhthuy2@gmail.com', NULL, '0981234640', '4659010148', 'Giáo dục tiểu học K46', 'Giáo dục tiểu học và mầm non', 'female', 'member', '12345678', NULL, NULL, '2026-03-22 04:00:00'),
(63, 'Phan Văn Long', 'longphan', 'longphan@gmail.com', NULL, '0981234641', '4752070055', 'Công nghệ thực phẩm K47', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-22 07:00:00'),
(64, 'Nguyễn Thị Hồng', 'hongnguyen', 'hongnguyen@gmail.com', NULL, '0981234642', '4759010149', 'Giáo dục tiểu học K47', 'Giáo dục tiểu học và mầm non', 'female', 'member', '12345678', NULL, NULL, '2026-03-22 08:00:00'),
(65, 'Hà Văn Dũng', 'dungha', 'dungha@gmail.com', NULL, '0981234643', '4852070056', 'Công nghệ thực phẩm K48', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-22 07:00:00'),
(66, 'Đinh Thị Mỹ Linh', 'myLinh', 'myLinh1@gmail.com', NULL, '0981234644', '4651050122', 'CNTT K46', 'Công nghệ thông tin', 'female', 'member', '12345678', NULL, NULL, '2026-03-23 01:00:00'),
(67, 'Lâm Văn Hòa', 'hoalam', 'hoalam@gmail.com', NULL, '0981234645', '4651050123', 'CNTT K46', 'Công nghệ thông tin', 'male', 'member', '$2y$10$JoDR7KcBXioTGE97iy3BR.UGS6Q/iMzfWpBeqbRDZNhfXy4ItT3FG', NULL, NULL, '2026-03-23 02:00:00'),
(68, 'Trương Thị Bích Trâm', 'bichtram', 'bichtram@gmail.com', NULL, '0981234646', '4654010212', 'QTKD K46', 'Quản trị kinh doanh', 'female', 'member', '$2y$10$PX0Au8awVzVFw2OyKok3H.vNSlhPAluiiUasGl/VHFs3/GJo7hdWC', NULL, NULL, '2026-03-23 03:00:00'),
(69, 'Phan Văn Khải', 'khaiphan', 'khaiphan@gmail.com', NULL, '0981234647', '4654010213', 'QTKD K46', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-23 04:00:00'),
(70, 'Trịnh Thị Hồng Anh', 'honganh', 'honganh2@gmail.com', NULL, '0981234648', '4657510232', 'Ngôn ngữ Anh K46', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-23 07:00:00'),
(71, 'Đặng Văn Thông', 'thongdang', 'thongdang@gmail.com', NULL, '0981234649', '4657510233', 'Ngôn ngữ Anh K46', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-23 08:00:00'),
(72, 'Võ Thị Thanh Trúc', 'thanhtruc', 'thanhtruc@gmail.com', NULL, '0981234650', '4653030015', 'Nông học K46', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-24 01:00:00'),
(73, 'Hồ Văn Phúc', 'phucho', 'phucho@gmail.com', NULL, '0981234651', '4653030016', 'Nông học K46', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-24 02:00:00'),
(74, 'Lưu Thị Thanh Hương', 'thanhhuong', 'thanhhuong@gmail.com', NULL, '0981234652', '4654040070', 'Kế toán K46', 'Kinh tế kế toán', 'female', 'member', '12345678', NULL, NULL, '2026-03-24 03:00:00'),
(75, 'Thái Văn Lợi', 'loithai', 'loithai@gmail.com', NULL, '0981234653', '4654040071', 'Kế toán K46', 'Kinh tế kế toán', 'male', 'member', '12345678', NULL, NULL, '2026-03-24 04:00:00'),
(76, 'Chu Thị Kim Anh', 'kimAnh', 'kimAnh@gmail.com', NULL, '0981234654', '4652070055', 'Công nghệ thực phẩm K46', 'Khoa học tự nhiên', 'female', 'member', '$2y$10$/Il4bjMmEXwYAx9GkuTdBuxV1UmXLpeE9lNJLYhS3SvMEfDKooml2', NULL, NULL, '2026-03-24 07:00:00'),
(77, 'Mạc Văn Thịnh', 'thinhmac', 'thinhmac@gmail.com', NULL, '0981234655', '4652070056', 'Công nghệ thực phẩm K46', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-24 08:00:00'),
(78, 'Lê Thị Thu Hương', 'thuhuong', 'thuhuong2@gmail.com', NULL, '0981234656', '4651300066', 'Công nghệ kỹ thuật ô tô K46', 'Kỹ thuật ô tô', 'female', 'member', '12345678', NULL, NULL, '2026-03-25 01:00:00'),
(79, 'Bùi Văn Tài', 'taibui', 'taibui@gmail.com', NULL, '0981234657', '4651300067', 'Công nghệ kỹ thuật ô tô K46', 'Kỹ thuật ô tô', 'male', 'member', '12345678', NULL, NULL, '2026-03-25 02:00:00'),
(80, 'Đỗ Thị Mỹ Hạnh', 'myhanh', 'myhanh@gmail.com', NULL, '0981234658', '4659010149', 'Giáo dục tiểu học K46', 'Giáo dục tiểu học và mầm non', 'female', 'member', '$2y$10$dE00PaDtRBjKUG5LaFLEqeNkowt3i40VciGFHsXlrLG8.ljuoZThW', NULL, NULL, '2026-03-25 03:00:00'),
(81, 'Trần Thị Bảo Trân', 'baotran', 'baotran2@gmail.com', NULL, '0981234659', '4751050161', 'CNTT K47', 'Công nghệ thông tin', 'female', 'member', '12345678', NULL, NULL, '2026-03-25 04:00:00'),
(82, 'Lý Văn Đức', 'ducly', 'ducly@gmail.com', NULL, '0981234660', '4751050162', 'CNTT K47', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-25 07:00:00'),
(83, 'Phùng Thị Hồng Thắm', 'hongtham', 'hongtham@gmail.com', NULL, '0981234661', '4754010110', 'QTKD K47', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-25 08:00:00'),
(84, 'Đoàn Văn Hiệp', 'hiepdoan', 'hiepdoan@gmail.com', NULL, '0981234662', '4754010111', 'QTKD K47', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-26 01:00:00'),
(85, 'Vương Thị Mỹ Dung', 'mydung', 'mydung@gmail.com', NULL, '0981234663', '4757510269', 'Ngôn ngữ Anh K47', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-26 02:00:00'),
(86, 'Trịnh Văn Bảo', 'baotrinh', 'baotrinh@gmail.com', NULL, '0981234664', '4757510270', 'Ngôn ngữ Anh K47', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-26 03:00:00'),
(87, 'Hồ Thị Minh Tâm', 'minhtam', 'minhtam@gmail.com', NULL, '0981234665', '4756060051', 'Văn học K47', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-26 04:00:00'),
(88, 'Đặng Văn Nhân', 'nhandang', 'nhandang@gmail.com', NULL, '0981234666', '4756060052', 'Văn học K47', 'Khoa học xã hội nhân văn', 'male', 'member', '$2y$10$CaDhaWs9qC8lOdPAhJFl1.R2B5MCJEKP5Jf2wo/CBvgK0E4QzfMIC', NULL, NULL, '2026-03-26 07:00:00'),
(89, 'Lâm Thị Phương Thúy', 'phuongthuy', 'phuongthuy@gmail.com', NULL, '0981234667', '4756130107', 'Đông phương học K47', 'Khoa học xã hội nhân văn', 'female', 'member', '12345678', NULL, NULL, '2026-03-26 08:00:00'),
(90, 'Đinh Văn Tú', 'tudinh', 'tudinh@gmail.com', NULL, '0981234668', '4756130108', 'Đông phương học K47', 'Khoa học xã hội nhân văn', 'male', 'member', '12345678', NULL, NULL, '2026-03-27 01:00:00'),
(91, 'Nguyễn Thị Thanh Ngân', 'thanhngan', 'thanhngan@gmail.com', NULL, '0981234669', '4752070056', 'Công nghệ thực phẩm K47', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-27 02:00:00'),
(92, 'Phan Văn Huy', 'huyphan', 'huyphan@gmail.com', NULL, '0981234670', '4752070057', 'Công nghệ thực phẩm K47', 'Khoa học tự nhiên', 'male', 'member', '$2y$10$0SRRjdbboKOzKFb8fnZKzu.G.MhATLzK.wVHdxjrj3cqsvJMZNx8q', NULL, NULL, '2026-03-27 03:00:00'),
(93, 'Lưu Thị Bích Hằng', 'bichhang', 'bichhang@gmail.com', NULL, '0981234671', '4759010150', 'Giáo dục tiểu học K47', 'Giáo dục tiểu học và mầm non', 'female', 'member', '12345678', NULL, NULL, '2026-03-27 04:00:00'),
(94, 'Trương Thị Kim Oanh', 'kimoanh', 'kimoanh@gmail.com', NULL, '0981234672', '4851050165', 'CNTT K48', 'Công nghệ thông tin', 'female', 'member', '12345678', NULL, NULL, '2026-03-27 07:00:00'),
(95, 'Hà Văn Phương', 'phuongha', 'phuongha@gmail.com', NULL, '0981234673', '4851050166', 'CNTT K48', 'Công nghệ thông tin', 'male', 'member', '12345678', NULL, NULL, '2026-03-27 08:00:00'),
(96, 'Lại Thị Thùy Trang', 'thuytrang', 'thuytrang2@gmail.com', NULL, '0981234674', '4854010114', 'QTKD K48', 'Quản trị kinh doanh', 'female', 'member', '12345678', NULL, NULL, '2026-03-28 01:00:00'),
(97, 'Mai Văn Quyết', 'quyetmai', 'quyetmai@gmail.com', NULL, '0981234675', '4854010115', 'QTKD K48', 'Quản trị kinh doanh', 'male', 'member', '12345678', NULL, NULL, '2026-03-28 02:00:00'),
(98, 'Lý Thị Hồng Anh', 'anh3234', 'anh3234@gmail.com', NULL, '0981234676', '4857510270', 'Ngôn ngữ Anh K48', 'Ngoại ngữ', 'female', 'member', '12345678', NULL, NULL, '2026-03-28 03:00:00'),
(99, 'Vũ Văn Khánh', 'khanhvu', 'khanhvu@gmail.com', NULL, '0981234677', '4857510271', 'Ngôn ngữ Anh K48', 'Ngoại ngữ', 'male', 'member', '12345678', NULL, NULL, '2026-03-28 04:00:00'),
(100, 'Bùi Thị Thu Thảo', 'thuthao', 'thuthao@gmail.com', NULL, '0981234678', '4853030031', 'Nông học K48', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-28 07:00:00'),
(101, 'Đỗ Văn Cường', 'cuongdo', 'cuongdo@gmail.com', NULL, '0981234679', '4853030032', 'Nông học K48', 'Khoa học tự nhiên', 'male', 'member', '$2y$10$.Z09l9C2HenE.LJkPcfoxesruc78rm1P/yUpKDCpnqHNEPRW7twZm', NULL, NULL, '2026-03-28 08:00:00'),
(102, 'Trần Thị Huyền Trang', 'huyentrang', 'huyentrang@gmail.com', NULL, '0981234680', '4854040072', 'Kế toán K48', 'Kinh tế kế toán', 'female', 'member', '12345678', NULL, NULL, '2026-03-29 01:00:00'),
(103, 'Lê Văn Toàn', 'toanle', 'toanle@gmail.com', NULL, '0981234681', '4854040073', 'Kế toán K48', 'Kinh tế kế toán', 'male', 'member', '12345678', NULL, NULL, '2026-03-29 02:00:00'),
(104, 'Phạm Thị Thúy Nga', 'thuynga', 'thuynga@gmail.com', NULL, '0981234682', '4852070057', 'Công nghệ thực phẩm K48', 'Khoa học tự nhiên', 'female', 'member', '12345678', NULL, NULL, '2026-03-29 03:00:00'),
(105, 'Hoàng Văn Thiện', 'thienhoang', 'thienhoang@gmail.com', NULL, '0981234683', '4852070058', 'Công nghệ thực phẩm K48', 'Khoa học tự nhiên', 'male', 'member', '12345678', NULL, NULL, '2026-03-29 04:00:00');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `attendance_details`
--
ALTER TABLE `attendance_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_session_member` (`session_id`,`member_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_updated_by` (`updated_by`);

--
-- Chỉ mục cho bảng `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_club_id` (`club_id`),
  ADD KEY `idx_session_date` (`session_date`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Chỉ mục cho bảng `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Chỉ mục cho bảng `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_id` (`club_id`);

--
-- Chỉ mục cho bảng `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `head_id` (`head_id`);

--
-- Chỉ mục cho bảng `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `events_ibfk_3` (`cover_id`);

--
-- Chỉ mục cho bảng `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `media_id` (`media_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Chỉ mục cho bảng `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `join_requests`
--
ALTER TABLE `join_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `club_id` (`club_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `join_requests_ibfk_3` (`processed_by`);

--
-- Chỉ mục cho bảng `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Chỉ mục cho bảng `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_id` (`club_id`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_id` (`club_id`),
  ADD KEY `banner_id` (`banner_id`),
  ADD KEY `logo_id` (`logo_id`);

--
-- Chỉ mục cho bảng `stats`
--
ALTER TABLE `stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `club_id` (`club_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `attendance_details`
--
ALTER TABLE `attendance_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT cho bảng `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `event_registrations`
--
ALTER TABLE `event_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=561;

--
-- AUTO_INCREMENT cho bảng `gallery`
--
ALTER TABLE `gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `join_requests`
--
ALTER TABLE `join_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT cho bảng `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=237;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=238;

--
-- AUTO_INCREMENT cho bảng `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `stats`
--
ALTER TABLE `stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `attendance_details`
--
ALTER TABLE `attendance_details`
  ADD CONSTRAINT `fk_attendance_details_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_details_session` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_details_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD CONSTRAINT `fk_attendance_sessions_club` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_sessions_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `departments_ibfk_2` FOREIGN KEY (`head_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`cover_id`) REFERENCES `media` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `event_registrations_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gallery_ibfk_2` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gallery_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `join_requests`
--
ALTER TABLE `join_requests`
  ADD CONSTRAINT `join_requests_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `join_requests_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_3` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `stats`
--
ALTER TABLE `stats`
  ADD CONSTRAINT `stats_ibfk_1` FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
