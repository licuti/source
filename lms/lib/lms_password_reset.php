<?php
/** Public, portal-scoped password recovery flow. */
if (!defined('_LMS_PORTAL')) {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/lms_layout.php';
lms_session_ensure();

$svc = new \Lms\PasswordResetService(lms_db());
$step = isset($_SESSION['lms_reset_step']) ? (int) $_SESSION['lms_reset_step'] : 1;
$identifier = isset($_SESSION['lms_reset_identifier']) ? (string) $_SESSION['lms_reset_identifier'] : '';
$message = '';
$error = '';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lms_csrf_verify()) {
        $error = 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.';
    } elseif (isset($_POST['request_otp'])) {
        $identifier = trim(isset($_POST['identifier']) ? (string) $_POST['identifier'] : '');
        $_SESSION['lms_reset_identifier'] = $identifier;
        $_SESSION['lms_reset_step'] = 2;
        $message = $svc->requestOtp($identifier, _LMS_PORTAL, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
        $step = 2;
    } elseif (isset($_POST['verify_otp'])) {
        $identifier = isset($_SESSION['lms_reset_identifier']) ? (string) $_SESSION['lms_reset_identifier'] : '';
        $token = $svc->verifyOtp($identifier, _LMS_PORTAL, isset($_POST['otp']) ? $_POST['otp'] : '');
        if ($token === null) {
            $error = 'Mã xác minh không hợp lệ hoặc đã hết hạn.';
            $step = 2;
        } else {
            $_SESSION['lms_reset_token'] = $token;
            $_SESSION['lms_reset_step'] = 3;
            $step = 3;
        }
    } elseif (isset($_POST['set_password'])) {
        $token = isset($_SESSION['lms_reset_token']) ? (string) $_SESSION['lms_reset_token'] : '';
        if ($svc->resetPassword($token, isset($_POST['new_password']) ? $_POST['new_password'] : '',
            isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '', _LMS_PORTAL)) {
            unset($_SESSION['lms_reset_step'], $_SESSION['lms_reset_identifier'], $_SESSION['lms_reset_token']);
            lms_flash('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
            header('Location: ' . lms_url('', 'login'));
            exit;
        }
        $error = 'Không thể đặt mật khẩu mới. Kiểm tra độ dài tối thiểu 6 ký tự và xác nhận mật khẩu.';
        $step = 3;
    }
}

$portalLabel = lms_portal_label(_LMS_PORTAL);
?>
<!DOCTYPE html>
<html lang="vi">
<head><?php lms_head_assets('Quên mật khẩu — Portal ' . $portalLabel); ?></head>
<body class="lms-login-page">
<?php lms_flash_data_div(); ?>
<div class="lms-login-card">
    <div class="lms-login-logo"><span class="lms-login-brand">MasterStudy</span><span class="lms-login-sub">Đặt lại mật khẩu · <?= e($portalLabel) ?></span></div>
    <?php if ($message !== ''): ?><div class="alert alert-info" role="status"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($step <= 1): ?>
        <form method="post" action="<?= e(lms_url('', 'quen-mat-khau')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
            <label class="form-label" for="reset-identifier">Tên đăng nhập hoặc email</label>
            <input class="form-control mb-3" id="reset-identifier" name="identifier" required autocomplete="username">
            <button class="btn btn-lms w-100" name="request_otp" value="1">Gửi mã xác minh</button>
        </form>
    <?php elseif ($step === 2): ?>
        <p>Nhập mã 6 chữ số nếu email đã đăng ký với tài khoản.</p>
        <form method="post" action="<?= e(lms_url('', 'quen-mat-khau')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
            <label class="form-label" for="reset-otp">Mã OTP</label>
            <input class="form-control mb-3" id="reset-otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code">
            <button class="btn btn-lms w-100" name="verify_otp" value="1">Xác minh</button>
        </form>
    <?php else: ?>
        <form method="post" action="<?= e(lms_url('', 'quen-mat-khau')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
            <label class="form-label" for="reset-password">Mật khẩu mới</label>
            <input type="password" class="form-control mb-3" id="reset-password" name="new_password" minlength="6" required autocomplete="new-password">
            <label class="form-label" for="reset-confirm">Xác nhận mật khẩu mới</label>
            <input type="password" class="form-control mb-3" id="reset-confirm" name="confirm_password" minlength="6" required autocomplete="new-password">
            <button class="btn btn-lms w-100" name="set_password" value="1">Đặt mật khẩu mới</button>
        </form>
    <?php endif; ?>
    <div class="lms-login-footer-note"><a href="<?= e(lms_url('', 'login')) ?>">Quay lại đăng nhập</a></div>
</div>
<?php lms_footer_assets(); ?>
</body>
</html>
