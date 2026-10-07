<?php
/**
 * Menu bên trái (AdminLTE 4 / Bootstrap 5).
 * - 1 query modules, dựng cây trong PHP (bỏ N+1)
 * - Badge: COUNT thay vì select *
 */
$allModules = $d->o_fet(
    "select id, name, alias, parent, so_thu_tu from #_module_admin where hien_thi = 1 order by so_thu_tu ASC, id ASC"
) ?: array();

$childrenMap = array();
$nav = array();
foreach ($allModules as $m) {
    $pid = (int) $m['parent'];
    if ($pid === 0) {
        $nav[] = $m;
    } else {
        if (!isset($childrenMap[$pid])) {
            $childrenMap[$pid] = array();
        }
        $childrenMap[$pid][] = $m;
    }
}

// Badge / list: chỉ query khi module bật + có quyền (tránh lãng phí)
$canOrderSb  = function_exists('admin_module_available') && admin_module_available('quan-ly-don-hang');
$canLienheSb = function_exists('admin_module_available') && admin_module_available('lien-he');

if (!isset($countDonhangMoi)) {
    $countDonhangMoi = $canOrderSb
        ? (int) $d->num_rows('select id from #_dathang where trangthai_xuly = 0')
        : 0;
}
if (!isset($countLienheMoiBadge)) {
    $countLienheMoiBadge = $canLienheSb
        ? (int) $d->num_rows('select id from #_lienhe where da_xem = 0')
        : 0;
}

// Danh sách chi tiết chỉ khi dashboard + module bật
$isDashboard = empty($_GET['p']);
if ($isDashboard && $canOrderSb) {
    if (!isset($donhangmoi) || !is_array($donhangmoi)) {
        $donhangmoi = $d->o_fet('select id, ma_dh, ho_ten, dien_thoai, ngay_dathang, trangthai_xuly from #_dathang where trangthai_xuly = 0 order by id desc limit 10') ?: array();
    }
}
if ($isDashboard && $canLienheSb) {
    if (!isset($lienhemoi) || !is_array($lienhemoi)) {
        $lienhemoi = $d->o_fet('select id, ho_ten, tieu_de, noi_dung, ngay_gui from #_lienhe where da_xem = 0 order by id desc limit 10') ?: array();
    }
}

$currentPage = isset($_GET['p']) ? (string) $_GET['p'] : '';
$uid = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;

// Avatar (đã có từ header; fallback)
if (!isset($avatar)) {
    $avatar = 'img/icon-user.png';
}
$displayName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

    <div class="sidebar-brand">
        <a href="index.php" class="brand-link">
            <img src="img/logo.png" alt="Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light">Web admin</span>
        </a>
    </div>

    <div class="sidebar-wrapper">

        <div class="d-flex align-items-center gap-2 px-3 py-3 border-bottom border-secondary-subtle">
            <img src="<?= e($avatar) ?>" class="rounded-circle object-fit-cover"
                 width="38" height="38" alt="Ảnh đại diện">
            <div class="small">
                <div class="fw-medium"><?= e($displayName) ?></div>
                <span class="text-success"><i class="fa-solid fa-circle" style="font-size:.5rem"></i> Online</span>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Main navigation" data-accordion="false">

                <?php foreach ($nav as $group):
                    $gid = (int) $group['id'];
                    $children = isset($childrenMap[$gid]) ? $childrenMap[$gid] : array();
                    if (empty($children)) {
                        continue;
                    }

                    $childIds   = array();
                    $childAlias = array();
                    foreach ($children as $child) {
                        $childIds[]   = (int) $child['id'];
                        $childAlias[] = $child['alias'];
                    }

                    if ($d->checkPermission($uid, implode(',', $childIds)) != 1) {
                        continue;
                    }

                    $isOpen = $currentPage !== '' && in_array($currentPage, $childAlias, true);

                    // Badge nhóm: nếu nhóm chứa con đơn hàng (43) hoặc liên hệ (33)
                    $groupBadge = 0;
                    if (in_array(43, $childIds, true) || $gid === 43) {
                        $groupBadge += $countDonhangMoi;
                    }
                    if (in_array(33, $childIds, true) || $gid === 33) {
                        $groupBadge += $countLienheMoiBadge;
                    }
                    // Giữ logic cũ: group id 43 cộng cả 2
                    if ((string) $group['id'] === '43' || $gid === 43) {
                        $groupBadge = $countDonhangMoi + $countLienheMoiBadge;
                    }
                    ?>
                    <li class="nav-item <?= $isOpen ? 'menu-open' : '' ?>">
                        <a href="#" class="nav-link <?= $isOpen ? 'active' : '' ?>">
                            <i class="nav-icon fa <?= e($group['alias']) ?>"></i>
                            <p>
                                <?= e($group['name']) ?>
                                <i class="nav-arrow fa-solid fa-chevron-right"></i>
                                <?php if ($groupBadge > 0): ?>
                                    <span class="badge text-bg-danger me-3"><?= (int) $groupBadge ?></span>
                                <?php endif; ?>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($children as $child):
                                $cid = (int) $child['id'];
                                if ($d->checkPermission($uid, $cid) != 1) {
                                    continue;
                                }
                                $childBadge = 0;
                                if ($cid === 43 || (string) $child['id'] === '43') {
                                    $childBadge = $countDonhangMoi;
                                }
                                if ($cid === 33 || (string) $child['id'] === '33') {
                                    $childBadge = $countLienheMoiBadge;
                                }
                                $isActive = ($currentPage === $child['alias']);
                                ?>
                                <li class="nav-item">
                                    <a href="<?= e(admin_url(array('p' => $child['alias'], 'a' => 'man'))) ?>"
                                       class="nav-link <?= $isActive ? 'active' : '' ?>">
                                        <i class="nav-icon fa-regular fa-circle"></i>
                                        <p>
                                            <?= e($child['name']) ?>
                                            <?php if ($childBadge > 0): ?>
                                                <span class="badge text-bg-danger"><?= (int) $childBadge ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>

                <li class="nav-item">
                    <a href="<?= e(admin_url(array('p' => 'thongtin-user', 'a' => 'man'))) ?>"
                       class="nav-link <?= $currentPage === 'thongtin-user' ? 'active' : '' ?>">
                        <i class="nav-icon fa-solid fa-user-gear"></i>
                        <p>Thông tin đăng nhập</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>