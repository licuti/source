<?php
/**
 * Thanh điều hướng trên cùng (AdminLTE 4 / Bootstrap 5).
 *
 * Biến $user_login được sidebar_tpl.php dùng lại (header include trước sidebar).
 */
$uid = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
$user_login = array();
if ($uid > 0) {
    if (method_exists($d, 'safeFetchOne')) {
        $user_login = $d->safeFetchOne('select * from #_user where id = ?', array($uid));
    } else {
        $user_login = $d->simple_fetch('select * from #_user where id = ' . $uid);
    }
    if (!is_array($user_login)) {
        $user_login = array();
    }
}

$avatarFile = '';
if (!empty($user_login['hinh_anh']) && function_exists('admin_sanitize_image_path')) {
    $avatarFile = admin_sanitize_image_path($user_login['hinh_anh']);
} elseif (!empty($user_login['hinh_anh'])) {
    $avatarFile = (string) $user_login['hinh_anh'];
}
$avatar = ($avatarFile !== '' && function_exists('img_url'))
    ? img_url($avatarFile, 'img/icon-user.png')
    : 'img/icon-user.png';

$displayName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
if ($displayName === '' && !empty($user_login['ho_ten'])) {
    $displayName = (string) $user_login['ho_ten'];
}
?>
<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Thu gọn menu">
                    <i class="fa-solid fa-bars fs-5"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-block">
                <a class="nav-link" href="<?= e(defined('URLPATH') ? URLPATH : '/') ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Xem website
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                   data-bs-toggle="dropdown">
                    <div style="width: 32px; height: 32px;" class="rounded-circle overflow-hidden flex-shrink-0">
                        <img src="<?= e($avatar) ?>" class="w-100 h-100 object-fit-cover" alt="Ảnh đại diện">
                    </div>
                    <span class="d-none d-md-inline"><?= e($displayName) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li class="px-3 py-2 text-center border-bottom">
                        <div style="width: 64px; height: 64px;" class="rounded-circle overflow-hidden mx-auto mb-2">
                            <img src="<?= e($avatar) ?>" class="w-100 h-100 object-fit-cover" alt="Ảnh đại diện">
                        </div>
                        <p class="mb-0 fw-medium"><?= e($displayName) ?></p>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= e(admin_url(array('p' => 'thongtin-user', 'a' => 'man'))) ?>">
                            <i class="fa-solid fa-user me-2"></i>Thông tin cá nhân
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= e(admin_url(array('p' => 'out'))) ?>">
                            <i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>