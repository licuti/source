<?php
/**
 * Trang đăng nhập dùng chung cho 3 portal (T5).
 *
 * GET  → render form portal (mockup Tai-lieu-phat-trien/login/Đăng nhập admin.png)
 * POST → lms_csrf_verify() → UserService::authenticate($u, $p, _LMS_PORTAL):
 *        - ok            → set $_SESSION['lms_user'] + remember cookie (nếu check)
 *                         → flash success + redirect dashboard
 *        - correct_portal (D9) → báo lỗi + link portal đúng (KHÔNG tự chuyển)
 *        - lỗi khác      → báo lỗi + số lần thử còn lại (rate-limit 5 lần/15 phút)
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

$portalLabel = lms_portal_label(_LMS_PORTAL);

// Đã đăng nhập đúng portal → thẳng dashboard (hoặc trang đổi mật khẩu nếu bị ép)
$cur = lms_current_user();
if (is_array($cur) && !empty($cur['role']) && $cur['role'] === _LMS_PORTAL) {
    if (!empty($cur['must_change_password'])) {
        header('Location: ' . lms_url('', 'doi-mat-khau'));
        exit;
    }
    header('Location: ' . lms_url(''));
    exit;
}

$username       = '';
$wrongPortal    = '';
$wrongPortalUrl = '';
$error          = '';

if (isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url('', 'login'));
        exit;
    }

    $username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    $remember = !empty($_POST['remember']);

    $res = $userSvc->authenticate($username, $password, _LMS_PORTAL);

    if (!empty($res['ok']) && is_array($res['user'])) {
        $u = lms_enrich_user($d, $res['user']);
        $_SESSION['lms_user'] = $u;

        if ($remember) {
            lms_set_remember_cookie((int) $u['id'], $d);
        }

        // BR-AUTH-01: chưa đổi mật khẩu lần đầu → thẳng trang đổi mật khẩu
        if (!empty($u['must_change_password'])) {
            lms_flash('warning', 'Tài khoản của bạn cần đổi mật khẩu trước khi sử dụng hệ thống.');
            header('Location: ' . lms_url('', 'doi-mat-khau'));
            exit;
        }

        lms_flash('success', 'Đăng nhập thành công. Chào mừng bạn đến với MasterStudy!');
        header('Location: ' . lms_url(''));
        exit;
    }

    // D9: sai portal → từ chối + link portal đúng (không tự chuyển)
    if (!empty($res['correct_portal'])) {
        $wrongPortal    = lms_portal_label((string) $res['correct_portal']);
        $wrongPortalUrl = lms_url_to((string) $res['correct_portal'], '', 'login');
        $error          = 'Tài khoản không thuộc khu vực này. Vui lòng đăng nhập tại portal '
            . $wrongPortal . '.';
    } else {
        $error = !empty($res['error']) ? (string) $res['error'] : 'Đăng nhập thất bại.';
        $remaining = lms_login_attempts_remaining($username);
        if ($remaining > 0) {
            $error .= ' Bạn còn ' . $remaining . ' lần thử.';
        } else {
            $error .= ' Vui lòng thử lại sau 15 phút.';
        }
    }
}
$loginLabel = _LMS_PORTAL === 'admin' ? 'Admin đăng nhập' : $portalLabel . ' đăng nhập';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php lms_head_assets('Đăng nhập — Portal ' . $portalLabel); ?>
</head>
<body class="lms-login-page lms-login-page--portal">
<?php lms_flash_data_div(); ?>

<div class="lms-auth-shell lms-auth-shell--login">
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

    <div class="lms-login-card lms-login-card--portal">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation me-1"></i>
                <?= e($error) ?>
                <?php if ($wrongPortal !== ''): ?>
                    <div class="mt-2">
                        <a href="<?= e($wrongPortalUrl) ?>" class="lms-link">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>
                            Đi tới portal <?= e($wrongPortal) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(lms_url('', 'login')) ?>" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

            <div class="mb-3">
                <label for="lms-username" class="lms-auth-label"><?= e($loginLabel) ?></label>
                <input type="text" class="form-control lms-auth-input" id="lms-username" name="username"
                       value="<?= e($username) ?>" required autofocus>
            </div>

            <div class="mb-2">
                <label for="lms-password" class="lms-auth-label">Password</label>
                <div class="lms-password-wrap">
                    <input type="password" class="form-control lms-auth-input" id="lms-password" name="password" required>
                    <button type="button" class="lms-password-toggle" id="lms-password-toggle"
                            aria-label="Hiện/ẩn mật khẩu">
                        <i class="fa-regular fa-eye-slash"></i>
                    </button>
                </div>
            </div>

            <div class="lms-auth-options">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="lms-remember" name="remember" value="1">
                    <label class="form-check-label" for="lms-remember">Ghi nhớ đăng nhập</label>
                </div>
                <a class="lms-auth-forgot" href="<?= e(lms_url('', 'quen-mat-khau')) ?>">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn btn-lms w-100 lms-auth-submit">Đăng nhập</button>
        </form>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('lms-password');
    var toggle = document.getElementById('lms-password-toggle');
    if (!input || !toggle) {
        return;
    }
    toggle.addEventListener('click', function () {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = toggle.querySelector('i');
        if (icon) {
            icon.className = show ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        }
    });
})();
</script>

<?php lms_footer_assets(); ?>
</body>
</html>
