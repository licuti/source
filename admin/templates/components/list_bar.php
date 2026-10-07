<?php
/**
 * Component: list_bar
 * Thanh lọc + bulk-action + thêm mới đặt NGOÀI card_open, ngay sau page_header.
 *
 * Props:
 * @var string $module     Module cho input hidden p=
 * @var string $action     Action cho input hidden a= (mặc định 'man')
 * @var array  $columns    ['ten' => 'Tên', ...] cột whitelist để tìm
 * @var string $category   Chuỗi <option>...</option> đã build (dropdown danh mục)
 * @var bool   $show_loai  Hiện dropdown danh mục (mặc định true)
 * @var array  $current    ['col'=>..., 'key'=>..., 'loai'=>...]
 * @var string $reset_url  URL nút Reset
 * @var array  $actions    ['delete' => 'Xóa'] bulk action
 * @var bool   $can_delete Có quyền xoá -> hiện bulk-action
 * @var bool   $can_edit   Có quyền thêm -> hiện nút Thêm mới
 * @var string $add_url    URL nút thêm mới
 * @var string $add_label  Nhãn nút thêm mới
 * @var array  $extra_actions [['url'=>..., 'label'=>..., 'icon'=>'fa-solid fa-...', 'class'=>'btn-outline-...'], ...]
 *                            thêm nút cạnh "Thêm mới" (vd "Biến thể sản phẩm")
 * @var array  $filters   Dropdown lọc thêm GET:
 *   [['name'=>'type','label'=>'Loại','options'=>[''=>'Tất cả'],'value'=>'','width'=>'160px'], ...]
 */
$module        = $module        ?? '';
$action        = $action        ?? 'man';
$columns       = $columns       ?? [];
$category      = $category      ?? '';
$show_loai     = $show_loai     ?? true;
$current       = $current       ?? ['col' => '', 'key' => '', 'loai' => 0];
$reset_url     = $reset_url     ?? '';
$actions       = $actions       ?? [];
$can_delete    = $can_delete    ?? false;
$can_edit      = $can_edit      ?? false;
$add_url       = $add_url       ?? admin_url(['p' => $module, 'a' => 'add']);
$add_label     = $add_label     ?? 'Thêm mới';
$extra_actions = $extra_actions ?? [];
$filters       = $filters       ?? [];
$hidden        = $hidden        ?? []; // ['from'=>'...','to'=>'...'] giữ query GET

if (empty($columns) && (!$can_delete || empty($actions)) && !$can_edit && empty($extra_actions)) {
    return;
}
?>

<div class="list-bar d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php if ($can_delete && !empty($actions)): ?>
            <select id="action" name="action" data-bulk-action data-form="#form"
                    class="form-select form-select-sm w-auto" aria-label="Tác vụ hàng loạt">
                <option value="">Tác vụ</option>
                <?php foreach ($actions as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <?php if ($can_edit): ?>
            <a href="<?= e($add_url) ?>" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-plus me-1"></i><?= e($add_label) ?>
            </a>
        <?php endif; ?>

        <?php foreach ($extra_actions as $btn): ?>
            <a href="<?= e($btn['url']) ?>" class="btn btn-sm <?= e($btn['class'] ?? 'btn-outline-primary') ?>">
                <?php if (!empty($btn['icon'])): ?><i class="<?= e($btn['icon']) ?> me-1"></i><?php endif; ?>
                <?= e($btn['label'] ?? '') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($columns)): ?>
    <form method="get" class="d-flex flex-wrap gap-2 justify-content-end align-items-center flex-grow-1" action="">
        <input type="hidden" name="p" value="<?= e($module) ?>">
        <input type="hidden" name="a" value="<?= e($action) ?>">
        <?php foreach ($hidden as $hk => $hv): ?>
            <?php if ($hv === '' || $hv === null) { continue; } ?>
            <input type="hidden" name="<?= e((string) $hk) ?>" value="<?= e((string) $hv) ?>">
        <?php endforeach; ?>

        <input type="text" name="key" value="<?= e($current['key'] ?? '') ?>"
               class="form-control form-control-sm" style="max-width:220px"
               placeholder="Từ khoá..." aria-label="Từ khoá">

        <select name="search" class="form-select form-select-sm" style="max-width:180px" aria-label="Tìm theo">
            <option value="">— Tất cả —</option>
            <?php foreach ($columns as $colKey => $colLabel): ?>
                <option value="<?= e($colKey) ?>" <?= ($current['col'] ?? '') === $colKey ? 'selected' : '' ?>>
                    <?= e($colLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($show_loai && $category !== ''): ?>
            <select name="loai" class="form-select form-select-sm" style="max-width:200px" aria-label="Danh mục">
                <option value="0">— Tất cả danh mục —</option>
                <?= $category ?>
            </select>
        <?php endif; ?>


        <?php foreach ($filters as $flt): ?>
            <?php
            $fname  = (string) ($flt['name'] ?? '');
            $flabel = (string) ($flt['label'] ?? $fname);
            $fopts  = (array)  ($flt['options'] ?? []);
            $fval   = (string) ($flt['value'] ?? '');
            $fwidth = (string) ($flt['width'] ?? '160px');
            if ($fname === '' || empty($fopts)) { continue; }
            ?>
            <select name="<?= e($fname) ?>" class="form-select form-select-sm"
                    style="max-width:<?= e($fwidth) ?>" aria-label="<?= e($flabel) ?>">
                <?php foreach ($fopts as $ok => $ol): ?>
                    <option value="<?= e((string) $ok) ?>" <?= (string) $ok === $fval ? 'selected' : '' ?>>
                        <?= e((string) $ol) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>

        <button class="btn btn-sm btn-primary" type="submit">
            <i class="fa-solid fa-magnifying-glass"></i> Tìm
        </button>

        <?php if ($reset_url !== ''): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e($reset_url) ?>">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </a>
        <?php endif; ?>
    </form>
    <?php endif; ?>
</div>