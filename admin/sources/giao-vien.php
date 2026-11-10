<?php
/**
 * Admin CMS — Quản lý thông tin Đội ngũ giáo viên hiển thị trên Website
 * Sử dụng bảng phụ db_lms_teacher_profiles liên kết với db_lms_teachers.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die("Error");
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : 'man';

switch ($a) {
    case 'man':
        show_teachers();
        $template = 'giao-vien/hienthi';
        break;

    case 'edit':
        show_form();
        $template = 'giao-vien/them';
        break;

    case 'save':
        save_profile($id_module);
        break;

    case 'toggle':
        toggle_website_status($id_module);
        break;

    default:
        $template = 'index';
}

/**
 * Hiển thị danh sách giáo viên kèm trạng thái hồ sơ website
 */
function show_teachers() {
    global $d, $items, $total_page, $limit, $total_records, $current_page, $keyword, $filter_status;

    $keyword = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
    $filter_status = isset($_GET['status']) ? (string) $_GET['status'] : '';

    $limit = 20;
    $current_page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($current_page - 1) * $limit;

    $where = 'WHERE t.status != "quit"';
    $params = array();

    if ($keyword !== '') {
        $where .= ' AND (t.full_name LIKE ? OR t.teacher_code LIKE ? OR p.title_badge LIKE ?)';
        $kw = '%' . $keyword . '%';
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
    }

    if ($filter_status === '1') {
        $where .= ' AND p.show_on_website = 1';
    } elseif ($filter_status === '0') {
        $where .= ' AND (p.show_on_website = 0 OR p.show_on_website IS NULL)';
    }

    $countSql = "SELECT COUNT(*) as total 
                 FROM db_lms_teachers t 
                 LEFT JOIN db_lms_teacher_profiles p ON p.teacher_id = t.user_id 
                 $where";
    $countRow = $d->safeFetchOne($countSql, $params);
    $total_records = $countRow ? (int) $countRow['total'] : 0;
    $total_page = ceil($total_records / $limit);

    $sql = "SELECT t.user_id, t.teacher_code, t.full_name, t.gender, t.status as teacher_status, t.teachable_subjects,
                   p.id as profile_id, p.slug, p.title_badge, p.avatar, p.quote, p.show_on_website, p.so_thu_tu, p.updated_at
            FROM db_lms_teachers t
            LEFT JOIN db_lms_teacher_profiles p ON p.teacher_id = t.user_id
            $where
            ORDER BY COALESCE(p.show_on_website, 0) DESC, COALESCE(p.so_thu_tu, 9999) ASC, t.full_name ASC
            LIMIT $limit OFFSET $offset";

    $items = $d->safeFetch($sql, $params);
}

/**
 * Hiển thị form chỉnh sửa hồ sơ website của giáo viên
 */
function show_form() {
    global $d, $teacher, $profile, $courseOptions, $id_module, $canEdit;

    $canEdit = $d->checkPermission_edit($id_module) == 1;
    $teacherId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($teacherId <= 0) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Không tìm thấy giáo viên.');
        }
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    $teacher = $d->safeFetchOne("SELECT * FROM db_lms_teachers WHERE user_id = ?", array($teacherId));
    if (empty($teacher)) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Giáo viên không tồn tại trong hệ thống LMS.');
        }
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    $profile = $d->safeFetchOne("SELECT * FROM db_lms_teacher_profiles WHERE teacher_id = ?", array($teacherId)) ?: array();

    // Lấy danh sách khóa học từ CMS website (module 2)
    $courseOptions = $d->safeFetch(
        "SELECT id, id_code, ten, alias FROM db_category WHERE module = 2 AND hien_thi = 1 ORDER BY so_thu_tu ASC, id ASC"
    ) ?: array();
}

/**
 * Lưu dữ liệu hồ sơ website giáo viên
 */
function save_profile($id_module) {
    global $d;

    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Phiên làm việc hết hạn (CSRF). Vui lòng thử lại.');
        }
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    if ($teacherId <= 0) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Thiếu mã giáo viên.');
        }
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    $teacher = $d->safeFetchOne("SELECT * FROM db_lms_teachers WHERE user_id = ?", array($teacherId));
    if (empty($teacher)) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Giáo viên không tồn tại.');
        }
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    // Xử lý slug
    $slug = trim((string) ($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = function_exists('slugify') ? slugify($teacher['full_name']) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $teacher['full_name'])));
    } else {
        $slug = function_exists('slugify') ? slugify($slug) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug)));
    }

    // Đảm bảo slug duy nhất
    $existSlug = $d->safeFetchOne(
        "SELECT id FROM db_lms_teacher_profiles WHERE slug = ? AND teacher_id != ?",
        array($slug, $teacherId)
    );
    if ($existSlug) {
        $slug .= '-' . rand(10, 99);
    }

    // Xử lý các trường dữ liệu
    $titleBadge = trim((string) ($_POST['title_badge'] ?? ''));
    $avatar = trim((string) ($_POST['avatar'] ?? ''));
    $quote = trim((string) ($_POST['quote'] ?? ''));
    $introText = trim((string) ($_POST['intro_text'] ?? ''));
    $certificates = trim((string) ($_POST['certificates'] ?? ''));
    $experience = trim((string) ($_POST['experience'] ?? ''));
    $awards = trim((string) ($_POST['awards'] ?? ''));
    $education = trim((string) ($_POST['education'] ?? ''));
    $educationExp = trim((string) ($_POST['education_experience'] ?? ''));
    if ($educationExp === '') {
        $educationExp = trim($education . "\n" . $experience);
    }
    $introVideo = trim((string) ($_POST['intro_video'] ?? ''));
    $videoThumbnail = trim((string) ($_POST['video_thumbnail'] ?? ''));
    $showOnWebsite = !empty($_POST['show_on_website']) ? 1 : 0;
    $soThuTu = (int) ($_POST['so_thu_tu'] ?? 0);

    // Chuẩn hóa video URL
    if ($introVideo !== '' && (strpos($introVideo, 'youtube.com') !== false || strpos($introVideo, 'youtu.be') !== false)) {
        if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $introVideo, $m)) {
            $introVideo = $m[1];
        }
    }

    // Xử lý danh sách bằng cấp chuyên môn (repeater: nhập tên, upload ảnh)
    $diplomaImages = '';
    $rawImages = isset($_POST['diploma_images']) && is_array($_POST['diploma_images']) ? $_POST['diploma_images'] : array();
    $rawNames  = isset($_POST['diploma_names']) && is_array($_POST['diploma_names']) ? $_POST['diploma_names'] : array();

    $diplomaList = array();
    $maxItems = max(count($rawImages), count($rawNames));
    for ($i = 0; $i < $maxItems; $i++) {
        $img = trim((string) ($rawImages[$i] ?? ''));
        $nm  = trim((string) ($rawNames[$i] ?? ''));
        if ($img !== '' || $nm !== '') {
            $diplomaList[] = array(
                'name'  => $nm,
                'image' => $img,
            );
        }
    }
    if (!empty($diplomaList)) {
        $diplomaImages = json_encode($diplomaList, JSON_UNESCAPED_UNICODE);
    }

    // Xử lý danh sách khóa học phụ trách (course_ids)
    $courseIds = '';
    if (!empty($_POST['course_ids']) && is_array($_POST['course_ids'])) {
        $cleanCourseIds = array_values(array_filter(array_map('intval', $_POST['course_ids'])));
        $courseIds = json_encode($cleanCourseIds);
    }

    // SEO (hỗ trợ cả seo_* truyền thống và title/des/keyword từ component form/seo_panel)
    $seoTitle = trim((string) ($_POST['seo_title'] ?? (is_array($_POST['title'] ?? null) ? ($_POST['title'][0] ?? '') : ($_POST['title'] ?? ''))));
    $seoKeyword = trim((string) ($_POST['seo_keyword'] ?? (is_array($_POST['keyword'] ?? null) ? ($_POST['keyword'][0] ?? '') : ($_POST['keyword'] ?? ''))));
    $seoDes = trim((string) ($_POST['seo_des'] ?? (is_array($_POST['des'] ?? null) ? ($_POST['des'][0] ?? '') : ($_POST['des'] ?? ''))));

    // Kiểm tra xem đã có bản ghi chưa
    $existingProfile = $d->safeFetchOne(
        "SELECT id FROM db_lms_teacher_profiles WHERE teacher_id = ?",
        array($teacherId)
    );

    if ($existingProfile) {
        $sql = "UPDATE db_lms_teacher_profiles SET
                    slug = ?,
                    title_badge = ?,
                    avatar = ?,
                    quote = ?,
                    intro_text = ?,
                    certificates = ?,
                    experience = ?,
                    awards = ?,
                    education = ?,
                    education_experience = ?,
                    diploma_images = ?,
                    intro_video = ?,
                    video_thumbnail = ?,
                    course_ids = ?,
                    so_thu_tu = ?,
                    show_on_website = ?,
                    seo_title = ?,
                    seo_keyword = ?,
                    seo_des = ?,
                    updated_at = NOW()
                WHERE teacher_id = ?";
        $params = array(
            $slug, $titleBadge, $avatar, $quote, $introText,
            $certificates, $experience, $awards, $education, $educationExp, $diplomaImages,
            $introVideo, $videoThumbnail, $courseIds, $soThuTu, $showOnWebsite,
            $seoTitle, $seoKeyword, $seoDes, $teacherId
        );
        $d->safeExec($sql, $params);
    } else {
        $sql = "INSERT INTO db_lms_teacher_profiles (
                    teacher_id, slug, title_badge, avatar, quote, intro_text,
                    certificates, experience, awards, education, education_experience, diploma_images,
                    intro_video, video_thumbnail, course_ids, so_thu_tu, show_on_website,
                    seo_title, seo_keyword, seo_des, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, NOW(), NOW()
                )";
        $params = array(
            $teacherId, $slug, $titleBadge, $avatar, $quote, $introText,
            $certificates, $experience, $awards, $education, $educationExp, $diplomaImages,
            $introVideo, $videoThumbnail, $courseIds, $soThuTu, $showOnWebsite,
            $seoTitle, $seoKeyword, $seoDes
        );
        $d->safeExec($sql, $params);
    }

    if (function_exists('admin_flash')) {
        admin_flash('success', 'Đã lưu thông tin hồ sơ giáo viên hiển thị trên website thành công.');
    }

    if (isset($_POST['capnhat']) && $_POST['capnhat'] === 'capnhat_sua') {
        $d->redirect('index.php?p=giao-vien&a=edit&id=' . $teacherId);
    } else {
        $d->redirect('index.php?p=giao-vien&a=man');
    }
}

/**
 * Bật/tắt nhanh trạng thái hiển thị ngoài website
 */
function toggle_website_status($id_module) {
    global $d;

    $teacherId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($teacherId <= 0) {
        $d->redirect('index.php?p=giao-vien&a=man');
        return;
    }

    $existingProfile = $d->safeFetchOne(
        "SELECT id, show_on_website FROM db_lms_teacher_profiles WHERE teacher_id = ?",
        array($teacherId)
    );

    if ($existingProfile) {
        $newStatus = $existingProfile['show_on_website'] == 1 ? 0 : 1;
        $d->safeExec(
            "UPDATE db_lms_teacher_profiles SET show_on_website = ?, updated_at = NOW() WHERE teacher_id = ?",
            array($newStatus, $teacherId)
        );
    } else {
        // Tự tạo profile rỗng với show_on_website = 1
        $teacher = $d->safeFetchOne("SELECT full_name FROM db_lms_teachers WHERE user_id = ?", array($teacherId));
        $slug = function_exists('slugify') ? slugify($teacher['full_name'] ?? 'gv-' . $teacherId) : 'gv-' . $teacherId;
        $d->safeExec(
            "INSERT INTO db_lms_teacher_profiles (teacher_id, slug, show_on_website, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())",
            array($teacherId, $slug)
        );
    }

    if (function_exists('admin_flash')) {
        admin_flash('success', 'Đã thay đổi trạng thái hiển thị trên website.');
    }
    $d->redirect('index.php?p=giao-vien&a=man');
}
