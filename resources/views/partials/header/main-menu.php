<?php
/**
 * View: Main Menu
 * Hiển thị menu từ hệ thống db_menus & db_menu_items theo vị trí 'primary'
 */
$current_lang = $_SESSION['app_locale'] ?? 'vi';

// 1. Tìm menu_id được gán cho vị trí 'primary' theo ngôn ngữ
$location = \MenuLocationModel::where('location_name', 'primary')
    ->where('lang', $current_lang)
    ->first();

$menu_id = $location->menu_id ?? 0;

// 2. Lấy các mục menu cấp cao nhất (parent_id = 0)
$main_menu_items = [];
if ($menu_id > 0) {
    $main_menu_items = \MenuItemModel::where('menu_id', $menu_id)
        ->where('parent_id', 0)
        ->orderBy('sort_order', 'ASC')
        ->get();
}
?>

<ul class="navbar-nav mb-2 mb-lg-0">
    <li class="nav-item">
        <a class="nav-link <?= empty($com) ? 'active' : '' ?>" href="<?= url('') ?>"><?= __('Trang chủ') ?></a>
    </li>
    <?php foreach ($main_menu_items as $item): ?>
        <?php
        $children = \MenuItemModel::where('parent_id', $item->id)
            ->orderBy('sort_order', 'ASC')
            ->get();
        ?>
        <?php if (count($children) > 0): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="<?= url($item->url) ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= $item->label ?>
                </a>
                <ul class="dropdown-menu">
                    <?php foreach ($children as $child): ?>
                        <li><a class="dropdown-item" href="<?= url($child->url) ?>"><?= $child->label ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= url($item->url) ?>"><?= $item->label ?></a>
            </li>
        <?php endif; ?>
    <?php endforeach; ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= url('lien-he') ?>"><?= __('Liên hệ') ?></a>
    </li>
</ul>
