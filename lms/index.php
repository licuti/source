<?php
/**
 * Trang chọn vai trò LMS (T7 + D8) — công khai, không cần đăng nhập.
 *
 * 2 thẻ: Học viên / Giáo viên → link login portal tương ứng.
 * Admin đăng nhập trực tiếp tại /lms/admin/login (không hiện trên trang chọn vai trò).
 * Mockup: Tai-lieu-phat-trien/login/login-role-selection.png
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
        'portal' => 'student',
        'name'   => 'Học viên',
        'desc'   => 'Đăng nhập để học tập, xem khóa học, theo dõi tiến độ và nhiều tiện ích khác.',
        'icon'   => 'fa-solid fa-user-graduate',
        'image'  => lms_asset_url('images/login/hoc-vien.png'),
    ),
    array(
        'portal' => 'teacher',
        'name'   => 'Giáo viên',
        'desc'   => 'Đăng nhập để quản lý lớp học, theo dõi học viên và tổ chức giảng dạy.',
        'icon'   => 'fa-solid fa-chalkboard-user',
        'image'  => lms_asset_url('images/login/giao-vien.png'),
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

<main class="lms-role-main">
    <div class="lms-role-shell">
        <div class="lms-auth-logo">
            <span class="lms-auth-brand"><i class="fa-solid fa-graduation-cap me-1"></i>MasterStudy</span>
            <span class="lms-auth-sub">Giáo viên riêng của bạn</span>
        </div>

        <h1 class="lms-auth-title">
            <img class="lms-auth-spark" src="<?= e(lms_asset_url('images/login/spark-left.svg')) ?>"
                 alt="" aria-hidden="true">
            <span>Chào mừng <span class="lms-accent">bạn!</span></span>
            <img class="lms-auth-spark" src="<?= e(lms_asset_url('images/login/spark-right.svg')) ?>"
                 alt="" aria-hidden="true">
        </h1>
        <p class="lms-auth-subtitle">Vui lòng chọn vai trò của bạn để đăng nhập vào hệ thống</p>

        <div class="lms-role-grid">
            <?php foreach ($portals as $p): ?>
                <a href="<?= e(lms_url_to($p['portal'], '', 'login')) ?>" class="lms-role-card">
                    <span class="lms-role-photo">
                        <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
                        <span class="lms-role-icon"><i class="<?= e($p['icon']) ?>"></i></span>
                    </span>
                    <span class="lms-role-body">
                        <span class="lms-role-name"><i class="<?= e($p['icon']) ?>"></i> <?= e($p['name']) ?></span>
                        <span class="lms-role-desc"><?= e($p['desc']) ?></span>
                        <span class="lms-role-btn">Đăng nhập <i class="fa-solid fa-arrow-right"></i></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php lms_footer_assets(); ?>
</body>
</html>
