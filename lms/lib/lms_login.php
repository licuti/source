<?php
/**
 * Trang đăng nhập dùng chung cho 3 portal (T5).
 *
 * GET  → render form trang đơn (KHÔNG dùng layout chính — mockup không có login page,
 *        tự thiết kế tối giản cùng tông cam, R5).
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
$portalIcon  = lms_portal_icon(_LMS_PORTAL);

// Đã đăng nhập đúng portal → thẳng dashboard
$cur = lms_current_user();
if (is_array($cur) && !empty($cur['role']) && $cur['role'] === _LMS_PORTAL) {
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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <?php lms_head_assets('Đăng nhập — Portal ' . $portalLabel); ?>
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
            <label for="lms-username" class="form-label">Tên đăng nhập</label>
            <input type="text" class="form-control" id="lms-username" name="username"
                   value="<?= e($username) ?>" required autofocus>
        </div>

        <div class="mb-3">
            <label for="lms-password" class="form-label">Mật khẩu</label>
            <input type="password" class="form-control" id="lms-password" name="password" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="lms-remember" name="remember" value="1">
            <label class="form-check-label" for="lms-remember">Ghi nhớ đăng nhập</label>
        </div>

        <button type="submit" class="btn btn-lms w-100 py-2">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Đăng nhập
        </button>

        <div class="lms-login-footer-note">
            Quên mật khẩu? Liên hệ trung tâm: <strong>0903861248</strong>
        </div>
    </form>
</div>

<?php lms_footer_assets(); ?>
</body>
</html>
