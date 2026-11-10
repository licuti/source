<?php
/**
 * LMS Enums & Shared Dictionary — Quản lý tập trung các trạng thái, danh mục dùng chung.
 *
 * PHP 7.3 compatible.
 */

class LmsEnum
{
    /** Hạng giáo viên: green (Xanh), gold (Vàng), bronze (Đỏ) */
    const TEACHER_RANKS = array(
        'green'  => array(
            'label'   => 'Xanh',
            'badge'   => 'lms-badge-green',
            'color'   => '#10b981',
            'aliases' => array('green', 'Xanh', 'xanh'),
        ),
        'gold'   => array(
            'label'   => 'Vàng',
            'badge'   => 'lms-badge-gold',
            'color'   => '#f59e0b',
            'aliases' => array('gold', 'Vàng', 'vang'),
        ),
        'bronze' => array(
            'label'   => 'Đỏ',
            'badge'   => 'lms-badge-bronze',
            'color'   => '#ef4444',
            'aliases' => array('bronze', 'Đỏ', 'do', 'Đồng', 'dong'),
        ),
    );

    /** Trạng thái giáo viên */
    const TEACHER_STATUSES = array(
        'active' => array(
            'label'   => 'Đang dạy',
            'badge'   => 'lms-badge-success',
            'color'   => '#10b981',
            'aliases' => array('active', 'Đang dạy', 'dang_day', 'hoat_dong'),
        ),
        'paused' => array(
            'label'   => 'Tạm ngưng',
            'badge'   => 'lms-badge-warning',
            'color'   => '#f59e0b',
            'aliases' => array('paused', 'Tạm ngưng', 'tam_ngung'),
        ),
        'quit'   => array(
            'label'   => 'Đã nghỉ',
            'badge'   => 'lms-badge-danger',
            'color'   => '#ef4444',
            'aliases' => array('quit', 'Đã nghỉ', 'da_nghi'),
        ),
    );

    /** Trạng thái học viên */
    const STUDENT_STATUSES = array(
        'pending_arrange' => array(
            'label'   => 'Đang sắp xếp',
            'badge'   => 'lms-badge-warning',
            'color'   => '#f59e0b',
            'aliases' => array('pending_arrange', 'Chờ xếp lớp', 'cho_xep_lop', 'Đang sắp xếp', 'dang_sap_xep'),
        ),
        'waiting_fee'     => array(
            'label'   => 'Chờ học phí',
            'badge'   => 'lms-badge-info',
            'color'   => '#0ea5e9',
            'aliases' => array('waiting_fee', 'Chờ đóng phí', 'Chờ học phí', 'cho_hoc_phi'),
        ),
        'studying'        => array(
            'label'   => 'Đang học',
            'badge'   => 'lms-badge-success',
            'color'   => '#10b981',
            'aliases' => array('studying', 'Đang học', 'active'),
        ),
        'paused'          => array(
            'label'   => 'Tạm nghỉ',
            'badge'   => 'lms-badge-secondary',
            'color'   => '#64748b',
            'aliases' => array('paused', 'Tạm ngưng', 'Tạm nghỉ', 'tam_nghi'),
        ),
        'quit'            => array(
            'label'   => 'Nghỉ học',
            'badge'   => 'lms-badge-danger',
            'color'   => '#ef4444',
            'aliases' => array('quit', 'Đã nghỉ', 'Nghỉ học', 'nghi_hoc'),
        ),
    );

    /** Giới tính */
    const GENDERS = array(
        'male'   => array(
            'label'   => 'Nam',
            'aliases' => array('male', 'Nam', 'nam'),
        ),
        'female' => array(
            'label'   => 'Nữ',
            'aliases' => array('female', 'Nữ', 'nu'),
        ),
        'other'  => array(
            'label'   => 'Khác',
            'aliases' => array('other', 'Khác', 'khac'),
        ),
    );

    /** Lựa chọn Có / Không (Boolean 1 / 0) */
    const YES_NO = array(
        '1' => array(
            'label'   => 'Có',
            'aliases' => array('1', 1, true, 'Có', 'co', 'yes'),
        ),
        '0' => array(
            'label'   => 'Không',
            'aliases' => array('0', 0, false, 'Không', 'khong', 'no'),
        ),
    );

    /** Hình thức học */
    const LEARNING_TYPES = array(
        'online'  => array(
            'label'   => 'Online',
            'aliases' => array('online', 'Online', 'Trực tuyến', 'truc_tuyen'),
        ),
        'offline' => array(
            'label'   => 'Offline',
            'aliases' => array('offline', 'Offline', 'Trực tiếp', 'truc_tiep'),
        ),
    );

    /** Phân loại tiền tố tài liệu thư viện (ADM-12 / TCH-07) */
    const LIBRARY_CATEGORIES = array(
        'none'     => array(
            'label'   => '-- Không tiền tố --',
            'aliases' => array('none', ''),
        ),
        '[Sách]'   => array(
            'label'   => '[Sách] - Sách / Giáo trình / Link',
            'aliases' => array('[Sách]', 'sach', 'book', 'link'),
        ),
        '[Review]' => array(
            'label'   => '[Review] - Bài review / PDF',
            'aliases' => array('[Review]', 'review', 'pdf'),
        ),
        '[Test]'   => array(
            'label'   => '[Test] - Đề test / Word / Excel',
            'aliases' => array('[Test]', 'test', 'doc', 'xls'),
        ),
        '[Nội bộ]' => array(
            'label'   => '[Nội bộ] - Tài liệu nội bộ',
            'aliases' => array('[Nội bộ]', 'noi_bo', 'internal'),
        ),
    );

    /** Phân loại quyền tải xuống tài liệu thư viện */
    const LIBRARY_DOWNLOADABLE = array(
        '1' => array(
            'label'   => 'Cho phép tải xuống',
            'aliases' => array('1', 1, true, 'yes', 'co'),
        ),
        '0' => array(
            'label'   => 'Không cho phép tải xuống (Chỉ xem)',
            'aliases' => array('0', 0, false, 'no', 'khong'),
        ),
    );

    /**
     * Lấy toàn bộ danh sách định nghĩa theo tên Enum.
     *
     * @param string $enumName
     * @return array
     */
    public static function get($enumName)
    {
        switch ($enumName) {
            case 'teacher_ranks':
            case 'teacher_rank':
            case 'rank':
                return self::TEACHER_RANKS;

            case 'teacher_statuses':
            case 'teacher_status':
                return self::TEACHER_STATUSES;

            case 'student_statuses':
            case 'student_status':
                return self::STUDENT_STATUSES;

            case 'genders':
            case 'gender':
                return self::GENDERS;

            case 'yes_no':
            case 'boolean':
                return self::YES_NO;

            case 'learning_types':
            case 'learning_type':
                return self::LEARNING_TYPES;

            case 'library_categories':
            case 'library_category':
                return self::LIBRARY_CATEGORIES;

            case 'library_downloadable':
                return self::LIBRARY_DOWNLOADABLE;

            default:
                return array();
        }
    }

    /**
     * Lấy mảng key => label dùng cho dropdown select.
     *
     * @param string $enumName
     * @return array
     */
    public static function options($enumName)
    {
        $defs = self::get($enumName);
        $opts = array();
        foreach ($defs as $key => $meta) {
            $opts[$key] = isset($meta['label']) ? $meta['label'] : (string) $key;
        }
        return $opts;
    }

    /**
     * Lấy nhãn hiển thị của 1 giá trị.
     *
     * @param string $enumName
     * @param mixed  $key
     * @param string $default
     * @return string
     */
    public static function label($enumName, $key, $default = '')
    {
        $key = self::normalize($enumName, $key);
        $defs = self::get($enumName);
        if (isset($defs[$key]['label'])) {
            return $defs[$key]['label'];
        }
        return $default !== '' ? $default : (string) $key;
    }

    /**
     * Chuẩn hoá input về key chuẩn lưu database.
     *
     * @param string $enumName
     * @param mixed  $value
     * @param string $default
     * @return string
     */
    public static function normalize($enumName, $value, $default = '')
    {
        $defs = self::get($enumName);
        if (empty($defs) || $value === null || $value === '') {
            return $default;
        }

        $str = (string) $value;
        if (isset($defs[$str])) {
            return $str;
        }

        // Tìm qua aliases
        $lower = mb_strtolower(trim($str), 'UTF-8');
        foreach ($defs as $k => $meta) {
            if (isset($meta['aliases']) && is_array($meta['aliases'])) {
                foreach ($meta['aliases'] as $alias) {
                    if (mb_strtolower((string) $alias, 'UTF-8') === $lower) {
                        return (string) $k;
                    }
                }
            }
        }

        return $default !== '' ? $default : $str;
    }

    /**
     * Lấy mã màu của 1 giá trị enum.
     *
     * @param string $enumName
     * @param mixed  $key
     * @param string $default
     * @return string
     */
     public static function color($enumName, $key, $default = '#64748b')
     {
         $key = self::normalize($enumName, $key);
         $defs = self::get($enumName);
         if (isset($defs[$key]['color'])) {
             return $defs[$key]['color'];
         }
         return $default;
     }

    /**
     * Render badge HTML.
     *
     * @param string $enumName
     * @param mixed  $key
     * @return string
     */
    public static function badge($enumName, $key)
    {
        $key = self::normalize($enumName, $key);
        $defs = self::get($enumName);
        if (!isset($defs[$key])) {
            return '<span>' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $meta = $defs[$key];
        $label = htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8');
        $badgeClass = isset($meta['badge']) ? $meta['badge'] : 'lms-badge-secondary';

        return '<span class="lms-badge ' . htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') . '">' . $label . '</span>';
    }
}

// Global helper wrappers
if (!function_exists('lms_enum_options')) {
    function lms_enum_options($enumName) {
        return LmsEnum::options($enumName);
    }
}
if (!function_exists('lms_enum_label')) {
    function lms_enum_label($enumName, $key, $default = '') {
        return LmsEnum::label($enumName, $key, $default);
    }
}
if (!function_exists('lms_enum_color')) {
    function lms_enum_color($enumName, $key, $default = '#64748b') {
        return LmsEnum::color($enumName, $key, $default);
    }
}
if (!function_exists('lms_enum_normalize')) {
    function lms_enum_normalize($enumName, $value, $default = '') {
        return LmsEnum::normalize($enumName, $value, $default);
    }
}
if (!function_exists('lms_enum_badge')) {
    function lms_enum_badge($enumName, $key) {
        return LmsEnum::badge($enumName, $key);
    }
}
