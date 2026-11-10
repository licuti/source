<?php
/**
 * Đổi mật khẩu dùng chung cho 3 portal (BR-AUTH-01, N3#9).
 *
 * URL: /lms/<portal>/doi-mat-khau
 *
 * Luồng:
 *  - Chưa đăng nhập → chuyển trang login.
 *  - GET  → render form (mật khẩu hiện tại + mật khẩu mới × 2).
 *  - POST → CSRF + verifyCurrentPassword + validate → changePassword
 *           → refresh $_SESSION['lms_user'] → flash + redirect dashboard.
 *
 * Gate ở lms_router.php ép user có must_change_password phải qua trang này.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_LMS_PORTAL')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/lms_layout.php';

lms_session_ensure();

$d       = lms_db();
$userSvc = new \Lms\UserService($d);

$user = lms_current_user();
if (!is_array($user) || empty($user['id'])) {
    lms_flash('error', 'Vui lòng đăng nhập để tiếp tục.');
    header('Location: ' . lms_url('', 'login'));
    exit;
}
if (empty($user['role']) || $user['role'] !== _LMS_PORTAL) {
    $portal = !empty($user['role']) ? (string) $user['role'] : 'admin';
    header('Location: ' . lms_url_to($portal, '', 'login'));
    exit;
}

$userId = (int) $user['id'];
$forced = !empty($user['must_change_password']);

$portalLabel = lms_portal_label(_LMS_PORTAL);
$portalIcon  = lms_portal_icon(_LMS_PORTAL);

$error   = '';
$success = '';

if (isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url('', 'doi-mat-khau'));
        exit;
    }

    $current = isset($_POST['current_password']) ? (string) $_POST['current_password'] : '';
    $newPass = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
    $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

    if (!$userSvc->verifyCurrentPassword($userId, $current)) {
        $error = 'Mật khẩu hiện tại không đúng.';
    } elseif ($newPass === '') {
        $error = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($newPass) < 6) {
        $error = 'Mật khẩu mới tối thiểu 6 ký tự.';
    } elseif ($newPass !== $confirm) {
        $error = 'Xác nhận mật khẩu mới không khớp.';
    } elseif ($newPass === $current) {
        $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
    } elseif ($userSvc->changePassword($userId, $newPass)) {
        // Refresh session user (xoá cờ must_change_password)
        $fresh = $d->safeFetchOne(
            'select * from db_lms_users where id = ? limit 1',
            array($userId)
        );
        if (is_array($fresh) && !empty($fresh)) {
            $_SESSION['lms_user'] = lms_enrich_user($d, $fresh);
        }
        lms_flash('success', 'Đổi mật khẩu thành công.');
        header('Location: ' . lms_url(''));
        exit;
    } else {
        $error = 'Không thể đổi mật khẩu. Vui lòng thử lại.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php lms_head_assets('Đổi mật khẩu — Portal ' . $portalLabel); ?>
</head>
<body class="lms-login-page">
<?php lms_flash_data_div(); ?>

<div class="lms-login-card">
    <div class="lms-login-logo">
        <span class="lms-login-brand"><i class="fa-solid fa-graduation-cap me-1"></i>MasterStudy</span>
        <span class="lms-login-sub">Giáo viên riêng của bạn</span>
    </div>

    <div class="lms-login-portal-label">
        <span class="lms-portal-tag"><i class="<?= e($portalIcon) ?> me-1"></i> Khu vực <?= e($portalLabel) ?></span>
    </div>

    <?php if ($forced): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Đây là lần đăng nhập đầu tiên. Vui lòng đổi mật khẩu trước khi sử dụng hệ thống.
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-circle-exclamation me-1"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e(lms_url('', 'doi-mat-khau')) ?>" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

        <div class="mb-3">
            <label for="lms-current" class="form-label">Mật khẩu hiện tại</label>
            <input type="password" class="form-control" id="lms-current" name="current_password" required autofocus>
        </div>

        <div class="mb-3">
            <label for="lms-new" class="form-label">Mật khẩu mới</label>
            <input type="password" class="form-control" id="lms-new" name="new_password" required
                   aria-describedby="lms-new-help">
            <div id="lms-new-help" class="form-text">Tối thiểu 6 ký tự, khác mật khẩu hiện tại.</div>
        </div>

        <div class="mb-3">
            <label for="lms-confirm" class="form-label">Xác nhận mật khẩu mới</label>
            <input type="password" class="form-control" id="lms-confirm" name="confirm_password" required>
        </div>

        <button type="submit" class="btn btn-lms w-100 py-2">
            <i class="fa-solid fa-floppy-disk me-1"></i> Đổi mật khẩu
        </button>

        <?php if (!$forced): ?>
            <div class="lms-login-footer-note">
                <a href="<?= e(lms_url('')) ?>" class="lms-link">Quay lại trang chính</a>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php lms_footer_assets(); ?>
</body>
</html>
