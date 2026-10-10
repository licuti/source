<?php
/**
 * Trang chọn vai trò LMS (T7 + D8) — công khai, không cần đăng nhập.
 *
 * 3 thẻ: Admin / Giáo viên / Học viên → link login portal tương ứng.
 *
 * PHP 7.3 compatible.
 */

define('_LMS_PORTAL', 'index');
require_once __DIR__ . '/lib/lms_bootstrap.php';

lms_session_ensure();

// Nếu đã đăng nhập 1 portal → vào thẳng dashboard portal đó
$cur = lms_current_user();
if (is_array($cur) && !empty($cur['role']) && in_array($cur['role'], array('admin', 'teacher', 'student'), true)) {
    header('Location: ' . lms_url_to((string) $cur['role'], ''));
    exit;
}

$portals = array(
    array(
        'portal' => 'admin',
        'name'   => 'Quản trị',
        'desc'   => 'Quản lý học viên, giáo viên, lớp học và toàn hệ thống',
        'icon'   => 'fa-solid fa-user-shield',
    ),
    array(
        'portal' => 'teacher',
        'name'   => 'Giáo viên',
        'desc'   => 'Lịch dạy, học viên, bài tập về nhà và phí dịch vụ',
        'icon'   => 'fa-solid fa-chalkboard-user',
    ),
    array(
        'portal' => 'student',
        'name'   => 'Học viên',
        'desc'   => 'Khoá học, lịch học, bài tập về nhà và nhận xét tháng',
        'icon'   => 'fa-solid fa-user-graduate',
    ),
);

$title = 'Chọn vai trò đăng nhập';
require_once __DIR__ . '/lib/lms_layout.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php lms_head_assets($title); ?>
</head>
<body class="lms-role-page">
<?php lms_flash_data_div(); ?>

<header class="lms-header">
    <a href="<?= e(lms_base_url()) ?>" class="lms-brand">
        <i class="fa-solid fa-graduation-cap"></i>
        <span>MasterStudy</span>
        <span class="lms-brand-sub d-none d-md-inline">Giáo viên riêng của bạn</span>
    </a>
</header>

<main class="lms-role-main">
    <div class="container">
        <h1 class="text-center mb-2" style="font-weight:800;color:#232a35;">Đăng nhập hệ thống LMS</h1>
        <p class="text-center text-muted mb-4 pb-2">Vui lòng chọn khu vực phù hợp với vai trò của bạn</p>

        <div class="lms-role-grid">
            <?php foreach ($portals as $p): ?>
                <a href="<?= e(lms_url_to($p['portal'], '', 'login')) ?>" class="lms-role-card">
                    <span class="lms-role-icon"><i class="<?= e($p['icon']) ?>"></i></span>
                    <span class="lms-role-name"><?= e($p['name']) ?></span>
                    <span class="lms-role-desc"><?= e($p['desc']) ?></span>
                    <span class="lms-role-btn">Đăng nhập <i class="fa-solid fa-arrow-right ms-1"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<footer class="lms-footer">
    <div class="lms-footer-grid">
        <div class="lms-footer-brand">
            <i class="fa-solid fa-graduation-cap me-2"></i> MasterStudy
        </div>
        <div class="lms-footer-links">
            <a href="<?= e(lms_base_url()) ?>">Về chúng tôi</a>
            <a href="<?= e(lms_base_url()) ?>">Tuyển dụng</a>
        </div>
        <div class="lms-footer-contact">
            <span><i class="fa-solid fa-location-dot me-1"></i> 181 Cao Thắng, Block B, Charmington La Pointe Building, Phường Hoà Hưng, TPHCM</span>
            <span><i class="fa-solid fa-phone me-1"></i> 0903861248</span>
        </div>
    </div>
</footer>

<?php lms_footer_assets(); ?>
</body>
</html>
