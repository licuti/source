<?php
/**
 * Trang đăng nhập admin.
 *
 * Bảo mật (đợt vá 7.2):
 *  - Rate-limit theo username + IP (dùng admin_rate_* có sẵn).
 *  - Bind tham số (safeFetch) thay addslashes.
 *  - Remember-me: cookie chứa raw 64-hex; DB lưu sha256; token XOAY mỗi lần
 *    dùng và lưu vào cột RIÊNG `remember_token` (không đụng cột `token` cũ
 *    để không phá vỡ hash md5 legacy).
 *  - Cookie remember-me có HttpOnly + SameSite=Lax.
 */
if (!isset($_SESSION)) {
    session_start();
}
error_reporting(0);

require_once __DIR__ . '/lib/bootstrap.php';
global $d;
$d = new func_index($config['database']);

/**
 * Đảm bảo cột `remember_token` tồn tại (idempotent).
 * Chạy 1 lần / request, tự bỏ qua nếu cột đã có.
 */
if (!function_exists('admin_ensure_remember_token_column')) {
    function admin_ensure_remember_token_column($d)
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!is_object($d) || empty($d->db)) {
            return;
        }
        try {
            $stmt = $d->db->query('select database()');
            $dbName = $stmt ? (string) $stmt->fetchColumn() : '';
            if ($dbName === '') {
                return;
            }
            $table = (isset($d->refix) ? $d->refix : 'db_') . 'user';
            $check = $d->db->prepare(
                'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS'
                . ' WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $check->execute(array($dbName, $table, 'remember_token'));
            if ((int) $check->fetchColumn() > 0) {
                return;
            }
            $t = str_replace('`', '``', $table);
            $d->db->exec("ALTER TABLE `{$t}` ADD COLUMN `remember_token` TEXT NULL");
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('admin_ensure_remember_token_column: ' . $e->getMessage());
            }
        }
    }
}
admin_ensure_remember_token_column($d);

if (!function_exists('admin_remember_cookie_set')) {
    /** Ghi cookie remember-me với HttpOnly + SameSite=Lax (raw token 64 hex). */
    function admin_remember_cookie_set($raw, $ttlSeconds = 2592000)
    {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $expires = time() + (int) $ttlSeconds;
        if (PHP_VERSION_ID >= 70300) {
            setrawcookie('key_ad', $raw, array(
                'expires'  => $expires,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
        } else {
            setrawcookie('key_ad', $raw, $expires, '/; samesite=Lax', '', $secure, true);
        }
        $_COOKIE['key_ad'] = $raw;
    }
}

if (!function_exists('admin_remember_cookie_clear')) {
    /** Xoá cookie remember-me. */
    function admin_remember_cookie_clear()
    {
        setrawcookie('key_ad', '0', time() - 3600, '/', null, null, true);
        unset($_COOKIE['key_ad']);
    }
}

/*
 * Đăng nhập tự động qua cookie remember-me.
 * Cookie chứa raw token 64 hex; DB lưu hash sha256 của token (cột remember_token).
 * Token được XOAY mỗi lần đăng nhập tự động để chống replay.
 */
if (!empty($_COOKIE['key_ad']) && $_COOKIE['key_ad'] !== '0') {
    $raw = (string) $_COOKIE['key_ad'];

    if (!preg_match('/^[a-f0-9]{64}$/i', $raw)) {
        // Giá trị cũ (md5 hex, hoặc giá trị không hợp lệ) -> xoá cookie
        admin_remember_cookie_clear();
    } else {
        $hashed = hash('sha256', $raw);
        $login  = $d->safeFetch(
            "select * from #_user where remember_token = ? limit 1",
            array($hashed)
        );

        if (!empty($login)) {
            $_SESSION['id_user']    = $login[0]['id'];
            $_SESSION['user_admin'] = $login[0]['tai_khoan'];
            $_SESSION['user_hash']  = $login[0]['user_hash'];
            $_SESSION['quyen']      = $login[0]['quyen_han'];
            $_SESSION['name']       = $login[0]['ho_ten'];
            $_SESSION['is_admin']   = $login[0]['is_admin'];

            // Xoay token: cookie mới + DB hash mới
            $newRaw    = bin2hex(random_bytes(32));
            $newHashed = hash('sha256', $newRaw);

            $d->reset();
            $d->setTable('#_user');
            $d->setWhere('id', (int) $login[0]['id']);
            $d->update(array('remember_token' => $newHashed));

            admin_remember_cookie_set($newRaw);

            $d->location("index.php");
        } else {
            // Token không khớp -> có thể đã bị thu hồi/xoay ở nơi khác
            admin_remember_cookie_clear();
        }
    }
}

if (!empty($_POST['login'])) {

    $username = isset($_POST['input-username']) ? (string) $_POST['input-username'] : '';
    $password = isset($_POST['input-password']) ? (string) $_POST['input-password'] : '';

    // Rate-limit: 5 lần thử / 15 phút, theo username + IP
    $rateKey = 'login:' . strtolower(trim($username))
        . ':' . (isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '0');

    if (!admin_rate_allow($rateKey, 5, 900)) {
        $err = 'Bạn đã thử sai quá nhiều lần. Vui lòng thử lại sau 15 phút.';
    } elseif ($username === '' || $password === '') {
        // Validate server-side tối thiểu: không cho submit rỗng
        admin_rate_hit($rateKey, 900);
        $err = 'Tài khoản hoặc mật khẩu chưa đúng.';
    } else {

        $user_hash = sha1($d->clean($username));

        // Bind tham số — không còn nối chuỗi + addslashes
        $login = $d->safeFetch(
            "select * from #_user where user_hash = ? and quyen_han >= 1 limit 1",
            array($user_hash)
        );

        $passOk = !empty($login)
            && $d->verifyPassword(
                $password,
                $login[0]['pass_hash'],
                isset($login[0]['token']) ? $login[0]['token'] : ''
            );

        if ($passOk) {

            $d->upgradePasswordIfNeeded($login[0]['id'], $password, $login[0]['pass_hash']);

            $_SESSION['id_user']    = $login[0]['id'];
            $_SESSION['user_admin'] = $login[0]['tai_khoan'];
            $_SESSION['user_hash']  = $user_hash;
            $_SESSION['quyen']      = isset($login[0]['quyen_han']) ? $login[0]['quyen_han'] : 0;
            $_SESSION['name']       = isset($login[0]['ho_ten']) ? $login[0]['ho_ten'] : '';
            $_SESSION['is_admin']   = $login[0]['is_admin'];

            if (isset($_POST['checkbox'])) {
                // Token mới: raw 64 hex cho cookie, sha256 lưu DB, cột remember_token riêng
                $raw    = bin2hex(random_bytes(32));
                $hashed = hash('sha256', $raw);

                $d->reset();
                $d->setTable('#_user');
                $d->setWhere('id', (int) $login[0]['id']);
                $d->update(array('remember_token' => $hashed));

                admin_remember_cookie_set($raw);
            }

            admin_rate_clear($rateKey);
            $d->location("index.php");

        } else {
            admin_rate_hit($rateKey, 900);
            $err = 'Tài khoản hoặc mật khẩu chưa đúng.';
        }
    }
}

// Thông báo một lần sau khi đổi mật khẩu (cookie flash; fallback ?pwd=1)
if (empty($err) && function_exists('admin_login_flash_take')) {
    $okMsg = admin_login_flash_take();
    if ($okMsg !== '') {
        $ok = $okMsg;
    }
} elseif (empty($err) && isset($_GET['pwd']) && (string) $_GET['pwd'] === '1') {
    $ok = 'Đã cập nhật hồ sơ và đổi mật khẩu thành công. Vui lòng đăng nhập lại.';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Đăng nhập</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="img/logo.png" rel="shortcut icon" type="image/x-icon" />
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="public/plugin/bootstrap5/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="public/plugin/bootstrap-icons/font/bootstrap-icons.min.css">
    <!-- Style riêng cho trang đăng nhập -->
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

    <div class="login-decoration login-decoration--1"></div>
    <div class="login-decoration login-decoration--2"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-logo">
                <img src="https://phuongnamvina.com/img_data/icon/logo74.png" alt="Logo" onerror="this.src='img/logo.png'">
                <h1 class="login-title">Đăng nhập hệ thống</h1>
                <p class="login-subtitle">Vui lòng đăng nhập để tiếp tục</p>
            </div>

            <?php if (!empty($ok) || !empty($err)): ?>
                <div class="login-alert">
                    <?php if (!empty($ok)): ?>
                        <span class="text-success"><?= htmlspecialchars((string) $ok, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php elseif (!empty($err)): ?>
                        <span class="text-danger"><?= htmlspecialchars((string) $err, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" autocomplete="off" id="login_frm" class="login-form">
                <input type="hidden" name="login" value="<?=md5('login')?>">

                <div class="form-group">
                    <label class="form-label" for="input-username">Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-user-round-icon lucide-circle-user-round"><path d="M17.925 20.056a6 6 0 0 0-11.851.001"/><circle cx="12" cy="11" r="4"/><circle cx="12" cy="12" r="10"/></svg>
                        </span>
                        <input type="text" id="input-username" name="input-username"
                               class="form-control" placeholder="Nhập tên đăng nhập" autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="input-password">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round-icon lucide-key-round"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                        </span>
                        <input type="password" id="input-password" name="input-password"
                               class="form-control" placeholder="Nhập mật khẩu" autocomplete="current-password">
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="checkbox" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button id="forget-btn" type="submit" class="btn-login"
                        onclick="return kiem_tra_login()">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                </button>

                <div class="login-note">
                    <strong>Lưu ý:</strong> Mật khẩu từ 8 ký tự, bắt buộc có chữ thường, chữ HOA, số và ký tự đặc biệt. Ví dụ: <code>Phuong24!#</code>
                </div>
            </form>
        </div>

        <div class="login-support">
            <strong>Cần hỗ trợ kỹ thuật?</strong>
            <ul class="mb-0 ps-3 mt-1">
                <li>Cách 1: Truy cập <a href="https://supportphuongnamvina.com/login.html" target="_blank" rel="noopener">supportphuongnamvina.com</a> và gửi yêu cầu.</li>
                <li>Cách 2: Gửi email cho phòng kỹ thuật: <a href="mailto:kythuat@phuongnamvina.vn">kythuat@phuongnamvina.vn</a>.</li>
            </ul>
        </div>

        <div class="login-footer">
            Design by: <a href="https://phuongnamvina.com/" target="_blank" rel="noopener">phuongnamvina.com</a>
        </div>
    </div>

    <script src="public/plugin/jquery/dist/jquery.min.js"></script>
    <script src="public/plugin/bootstrap5/js/bootstrap.bundle.min.js"></script>

    <script>
        function kiem_tra_login() {

            const regex = /^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%^&*])(?=.*[A-Z]).{8,20}$/;

            if ($("#input-username").val() == '') {
                $("#input-username").focus();
                $(".login-alert .text-danger, .err").text("Chưa nhập tên tài khoản");
                return false;
            } else if ($("#input-password").val() == '') {
                $("#input-password").focus();
                $(".login-alert .text-danger, .err").text("Chưa nhập mật khẩu");
                return false;
            } else if (regex.test($("#input-password").val()) == false) {
                $("#input-password").focus();
                $(".login-alert .text-danger, .err").text("Mật khẩu chưa đúng định dạng");
                return false;
            } else {
                return true;
            }
        }
    </script>

</body>

</html>
