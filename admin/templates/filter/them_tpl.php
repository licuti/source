<?php
/** Filter — Thêm / Sửa tùy chọn */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$MODULE    = 'filter';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;
$isEdit    = !empty($isEdit);
$editId    = (int) ($editId ?? 0);
$parent    = (int) ($parent ?? 0);
$rowByLang = $rowByLang ?? [];
$main      = $main ?? [];
$groupOpts = $groupOpts ?? [];

$actionUrl = admin_url(array_filter([
    'p' => $MODULE, 'a' => 'save',
    'id' => $isEdit ? $editId : null,
    'parent' => $parent ?: null,
]));
$backUrl = admin_url(array_filter([
    'p' => $MODULE, 'a' => 'man',
    'id_loai' => $parent ?: null,
    'scroll_item' => $isEdit ? $editId : null,
]));
?>

<?php component('page_header', [
    'title'    => 'Tùy chọn filter',
    'subtitle' => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Bộ lọc', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form method="post" action="<?= e($actionUrl) ?>" data-warn-unsaved>
            <div class="row g-3">
                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>
                    <div class="card-body">
                        <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'fopt']); ?>
                        <div class="tab-content">
                            <?php foreach ($langs as $i => $lang):
                                $code = $lang['code'];
                                $row  = $rowByLang[$code] ?? [];
                                $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                     id="fopt-<?= e($code) ?>" role="tabpanel">
                                    <?php if ($isEdit): ?>
                                        <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                    <?php endif; ?>
                                    <?php component('form/input', [
                                        'label' => 'Tiêu đề' . $sfx, 'name' => 'ten[]',
                                        'id' => 'ten_' . $code, 'value' => $row['ten'] ?? '',
                                        'placeholder' => 'Nhập tiêu đề', 'required' => $i === 0,
                                    ]); ?>
                                    <?php component('form/editor', [
                                        'label' => 'Nội dung' . $sfx, 'name' => 'noi_dung[]',
                                        'id' => 'noi_dung_' . $code, 'value' => $row['noi_dung'] ?? '',
                                        'height' => '280px',
                                    ]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php component('form/actions_bar', [
                            'back_url' => $backUrl, 'can_edit' => $canEdit,
                            'save_label' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'save_edit' => true,
                        ]); ?>
                    </div>
                    <?php component('card_close', ['body' => 'none']); ?>
                </div>
                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>
                    <?php component('form/image_picker', [
                        'label' => 'Hình ảnh', 'name' => 'hinh_anh',
                        'value' => $main['hinh_anh'] ?? '',
                        'table' => '_filter_content',
                        'row_id' => $isEdit ? (string) $editId : '',
                        'height' => 140,
                    ]); ?>
                    <?php component('form/select', [
                        'label' => 'Nhóm filter', 'name' => 'id_loai', 'id' => 'id_loai',
                        'value' => $parent ?: ($main['id_loai'] ?? ''),
                        'options' => $groupOpts, 'placeholder' => 'Chọn nhóm',
                        'required' => true, 'class' => 'select2',
                    ]); ?>
                    <?php component('form/input', [
                        'label' => 'Số thứ tự', 'name' => 'so_thu_tu', 'id' => 'so_thu_tu',
                        'type' => 'number', 'value' => $main['so_thu_tu'] ?? 0,
                    ]); ?>
                    <?php component('form/checkbox', [
                        'label' => 'Hiển thị', 'name' => 'hien_thi', 'id' => 'hien_thi',
                        'checked' => $isEdit ? (($main['hien_thi'] ?? 0) == 1) : true,
                    ]); ?>
                    <?php component('card_close'); ?>
                </div>
            </div>
        </form>
    </div>
</div>

