<?php
/**
 * Component: toolbar
 * Thanh công cụ phía trên bảng danh sách: tác vụ hàng loạt, tìm kiếm,
 * làm mới và nút "Thêm mới".
 *
 * Hành vi do admin-ui.js xử lý (không dùng onclick inline):
 *  - [data-bulk-action] -> xác nhận qua AdminUI.confirm rồi submit form
 *  - #key-search  (ô nhập từ khoá)
 *  - #search-input (chọn cột tìm, có data-p) -> điều hướng khi change
 *
 * Props:
 * @var string $module      Giá trị ?p= (vd 'category')
 * @var array  $actions     ['delete' => 'Xóa'] danh sách tác vụ hàng loạt
 * @var array  $searchable  ['ten' => 'Tên danh mục', 'id_code' => 'ID']
 * @var bool   $can_delete  Có quyền xoá -> hiện dropdown tác vụ
 * @var bool   $can_edit    Có quyền thêm/sửa -> hiện nút Thêm mới
 * @var string $add_url     (tuỳ chọn) URL nút thêm mới
 * @var string $add_label   (tuỳ chọn) nhãn nút thêm mới
 * @var string $extra       (tuỳ chọn) HTML chèn thêm vào bên trái
 */
$module     = $module     ?? ($_GET['p'] ?? '');
$actions    = $actions    ?? ['delete' => 'Xóa'];
$searchable = $searchable ?? [];
$can_delete = $can_delete ?? false;
$can_edit   = $can_edit   ?? false;
$add_url    = $add_url    ?? admin_url(['p' => $module, 'a' => 'add']);
$add_label  = $add_label  ?? 'Thêm mới';
$extra      = $extra      ?? '';

// Giá trị đang tìm kiếm hiện tại (để giữ lại trên ô input sau khi reload)
$currentKey = $_GET['key'] ?? '';
// Lưu ý: admin.js đọc $_GET['search'] cho cột tìm kiếm
$currentCol = $_GET['search'] ?? '';
?>
<div class="d-flex flex-wrap align-items-center gap-2 w-100 justify-content-end">

    <?php // Nhóm công cụ bên trái: tác vụ, tìm kiếm, làm mới, phần chèn thêm ?>
    <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">

        <?php if ($can_delete && $actions): ?>
            <select id="action" name="action" data-bulk-action data-form="#form"
                    class="form-select form-select-sm w-auto" aria-label="Tác vụ hàng loạt">
                <option value="">Tác vụ</option>
                <?php foreach ($actions as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ($searchable): ?>
            <div class="input-group input-group-sm" style="max-width:340px">
                <span class="input-group-text" title="Tìm trong toàn bộ dữ liệu">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="key-search" class="form-control form-control-sm"
                       value="<?= e($currentKey) ?>" placeholder="Tìm trong CSDL...">
                <select id="search-input" class="form-select form-select-sm" data-p="<?= e($module) ?>"
                        style="max-width:140px" aria-label="Tìm theo">
                    <option>Tìm theo...</option>
                    <?php foreach ($searchable as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $currentCol === $value ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <a href="<?= e(admin_url(['p' => $module, 'a' => $_GET['a'] ?? 'man'])) ?>"
           class="btn btn-sm btn-outline-secondary" title="Làm mới">
            <i class="fa-solid fa-rotate-right"></i>
        </a>

        <?= $extra ?>
    </div>

    <?php if ($can_edit): ?>
        <a href="<?= e($add_url) ?>" class="btn btn-sm btn-primary flex-shrink-0">
            <i class="fa-solid fa-plus me-1"></i><?= e($add_label) ?>
        </a>
    <?php endif; ?>
</div>