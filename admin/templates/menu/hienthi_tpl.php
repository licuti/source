<?php
/**
 * templates/menu/hienthi_tpl.php
 * Chỉ markup + nạp asset. Logic nằm ở js/menu-builder.js
 */
$langs = get_json('lang');
$lang_count = is_array($langs) ? count($langs) : 0;
if (!is_array($current_menu)) {
    $current_menu = ['id' => 0, 'name' => ''];
}
if (empty($current_menu_items_json)) {
    $current_menu_items_json = '[]';
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css"/>
<link rel="stylesheet" href="css/menu-builder.css"/>
<script src="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js"></script>

<?php component('page_header', [
    'title'      => 'Menu',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị giao diện'],
        ['label' => 'Menu'],
    ],
]); ?>

<div class="app-content menu-page">
    <div class="container-fluid">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">

                    <!-- Cột trái: nguồn -->
                    <div class="col-lg-3">
                        <div class="menu-source">
                            <h2>Thêm mục menu</h2>

                            <div class="language-selector" style="display: <?= $lang_count > 1 ? 'block' : 'none' ?>;">
                                <h3>Ngôn ngữ</h3>
                                <?php foreach ($langs as $key => $lg): ?>
                                    <label>
                                        <input type="radio" name="lang" value="<?= htmlspecialchars($lg['code']) ?>" <?= $key == 0 ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($lg['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                                <label><input type="radio" name="lang" value="all"> Tất cả</label>
                            </div>

                            <?php foreach ($menu_sources as $key => $source): ?>
                                <div class="source-box" data-title="<?= htmlspecialchars($source['title'] ?? $key) ?>">
                                    <h3><?= htmlspecialchars($source['title']) ?></h3>
                                    <div class="source-content">
                                        <input type="text" class="source-search form-control form-control-sm" placeholder="Tìm kiếm...">
                                        <div class="source-items">
                                            <?php
                                            if (!empty($source['items'])) {
                                                echo renderMenuSourceItems($source['items']);
                                            }
                                            ?>
                                        </div>
                                        <hr class="my-2">
                                        <button type="button" class="btn btn-sm btn-primary add-selected">Thêm vào menu</button>
                                        <div class="select-all-wrap">
                                            <label><input type="checkbox" class="select-all"> Chọn tất cả</label>
                                        </div>
                                        <div class="pagination">
                                            <button type="button" class="prev">Prev</button>
                                            <span class="current-page">1 / 1</span>
                                            <button type="button" class="next">Next</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="source-box">
                                <h3>Liên kết tùy chỉnh</h3>
                                <div class="source-content">
                                    <div class="form-group">
                                        <label for="c-menu-item-url">URL</label>
                                        <input type="text" id="c-menu-item-url" class="form-control form-control-sm" placeholder="https://..." value="#">
                                    </div>
                                    <div class="form-group">
                                        <label for="c-menu-item-name">Tên đường dẫn</label>
                                        <input type="text" id="c-menu-item-name" class="form-control form-control-sm" placeholder="Tên menu">
                                    </div>
                                    <button type="button" id="add-custom-link" class="btn btn-sm btn-primary">Thêm vào menu</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: cấu trúc -->
                    <div class="col-lg-9">
                        <div class="menu-structure">
                            <div id="menu-management-bar">
                                <label for="menu-selector" class="mb-0">Chọn menu để sửa:</label>
                                <select id="menu-selector" class="form-select form-select-sm" style="width:auto;min-width:180px;">
                                    <?php if (empty($menus)): ?>
                                        <option value="">— Chưa có menu —</option>
                                    <?php else: ?>
                                        <?php foreach ($menus as $value): ?>
                                            <option value="<?= (int)$value['id'] ?>" <?= (int)$value['id'] === (int)$current_menu_id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($value['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <span>hoặc <a href="#" id="create-new-menu">tạo menu mới</a>.</span>
                            </div>

                            <h2>Cấu trúc menu</h2>

                            <div class="form-group">
                                <label for="menu-name">Tên menu</label>
                                <input type="text" id="menu-name" class="form-control" placeholder="Nhập tên menu" value="<?= htmlspecialchars($current_menu['name'] ?? '') ?>">
                            </div>

                            <div class="dd" id="menu-editor">
                                <ol class="dd-list"></ol>
                            </div>
                            <div id="menu-empty" class="menu-empty">Chưa có mục nào trong menu</div>

                            <div class="menu-locations">
                                <h3>Vị trí Menu</h3>
                                <?php foreach ($menu_location as $value):
                                    $is_active_lang = false;
                                    $lang_name = $value['lang'];
                                    foreach ($langs as $lang_item) {
                                        if ($value['lang'] === $lang_item['code']) {
                                            $is_active_lang = true;
                                            $lang_name = $lang_item['name'];
                                            break;
                                        }
                                    }
                                    if (!$is_active_lang) {
                                        continue;
                                    }
                                    // value: location__lang (2 gạch dưới — an toàn khi location có dấu _)
                                    $loc_val = $value['location_name'] . '__' . $value['lang'];
                                    $checked = ((int)$value['menu_id'] === (int)$current_menu_id) ? 'checked' : '';
                                    $label = $value['location_label'];
                                    if ($lang_count > 1) {
                                        $label .= ' (' . $lang_name . ')';
                                    }
                                    ?>
                                    <label>
                                        <input type="checkbox" class="menu-location" value="<?= htmlspecialchars($loc_val) ?>" <?= $checked ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="menu-footer">
                                <div class="menu-footer-left">
                                    <button type="button" id="delete-selected" class="btn btn-sm btn-danger">Xóa mục đã chọn</button>
                                    <a href="#" id="select-all-items">Chọn tất cả</a>
                                    <a href="#" id="delete-menu">Xóa menu</a>
                                </div>
                                <div class="menu-footer-right">
                                    <button type="button" id="save-menu-button" class="btn btn-primary">Lưu Menu</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template 1 item Nestable -->
<template id="menu-item-template">
    <li class="dd-item">
        <div class="dd-item-bar">
            <div class="dd-tree-btns dd-nodrag">
                <button type="button" class="dd-expand hidden" title="Mở">+</button>
                <button type="button" class="dd-collapse hidden" title="Thu">−</button>
            </div>
            <div class="dd-handle">
                <div class="handle-left">
                    <input type="checkbox" class="item-select dd-nodrag" onclick="event.stopPropagation();">
                    <span class="item-title">(Không có tiêu đề)</span>
                </div>
            </div>
            <div class="handle-actions dd-nodrag">
                <span class="item-type"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary item-toggle" title="Cài đặt" aria-label="Cài đặt"><i class="fa-solid fa-gear" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="item-settings">
            <div class="item-settings-inner">
            <div class="form-group">
                <label>Tiêu đề</label>
                <input type="text" class="item-input form-control form-control-sm" data-name="label" placeholder="Tên hiển thị">
            </div>
            <div class="form-group">
                <label>Nhãn phụ (Hot, New...)</label>
                <input type="text" class="item-input form-control form-control-sm" data-name="sub_label" placeholder="Nhãn phụ">
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="text" class="item-input form-control form-control-sm" data-name="url" placeholder="https://...">
            </div>
            <div class="form-group">
                <label>Class tùy chỉnh</label>
                <input type="text" class="item-input form-control form-control-sm" data-name="class" placeholder="vd: menu-item--custom">
            </div>
            <div class="form-group">
                <label>Kiểu menu</label>
                <select class="item-input form-select form-select-sm" data-name="style">
                    <option value="default">Mặc định</option>
                </select>
            </div>
            <div class="form-group">
                <label>Block tùy chỉnh</label>
                <select class="item-input form-select form-select-sm" data-name="block">
                    <option value="">-- Không chọn --</option>
                </select>
            </div>
            <div class="form-group">
                <label>Target</label>
                <select class="item-input form-select form-select-sm" data-name="target">
                    <option value="_self">Mở trong cùng tab</option>
                    <option value="_blank">Mở trong tab mới</option>
                    <option value="_parent">Mở trong frame cha</option>
                    <option value="_top">Mở trong toàn bộ cửa sổ</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ảnh</label>
                <input type="hidden" class="item-input item-image-input" data-name="image" value="">
                <a href="#" class="btn btn-sm btn-outline-primary iframe-btn item-image-picker">
                    <i class="fa fa-upload"></i> Chọn hình ảnh
                </a>
                <div class="image-preview mt-2" style="max-width:200px;">
                    <img src="img/no-image.png" alt="" class="item-image-preview img-fluid">
                </div>
            </div>
            <div class="item-actions">
                <a class="link-btn move-up">Lên 1 cấp</a>
                <a class="link-btn move-down">Xuống 1 cấp</a>
                <a class="link-btn item-cancel">Hủy</a>
                <a class="link-btn danger item-remove">Xóa</a>
            </div>
            </div><!-- /.item-settings-inner -->
        </div><!-- /.item-settings -->
    </li>
</template>

<script>
// Dữ liệu ban đầu từ PHP (Nestable load trong menu-builder.js)
window.MENU_INITIAL_ITEMS = <?= $current_menu_items_json ?>;
</script>
<script src="js/menu-builder.js"></script>
