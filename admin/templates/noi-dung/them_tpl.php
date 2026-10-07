<?php
/** Nội dung — Thêm / Sửa #_content */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$MODULE    = 'noi-dung';
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
    'parent' => $parent ?: null,
]));
?>

<?php component('page_header', [
    'title' => 'Nội dung',
    'subtitle' => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Nội dung', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form name="frm" method="post" action="<?= e($actionUrl) ?>" data-warn-unsaved>
            <div class="row g-3">
                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>
                    <div class="card-body">
                        <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'nd']); ?>
                        <div class="tab-content">
                            <?php foreach ($langs as $i => $lang):
                                $code = $lang['code'];
                                $row  = $rowByLang[$code] ?? [];
                                $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                     id="nd-<?= e($code) ?>" role="tabpanel">
                                    <?php if ($isEdit): ?>
                                        <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                    <?php endif; ?>

                                    <?php component('form/input', [
                                        'label' => 'Tiêu đề' . $sfx, 'name' => 'ten[]',
                                        'id' => 'ten_' . $code, 'value' => $row['ten'] ?? '',
                                        'placeholder' => 'Nhập tiêu đề',
                                    ]); ?>

                                    <?php component('form/input', [
                                        'label' => 'Tiêu đề phụ' . $sfx, 'name' => 'ten_phu[]',
                                        'id' => 'ten_phu_' . $code, 'value' => $row['ten_phu'] ?? '',
                                        'placeholder' => 'VD: 15+, số liệu ngắn…',
                                    ]); ?>

                                    <?php component('form/input', [
                                        'label' => 'Liên kết' . $sfx, 'name' => 'link[]',
                                        'id' => 'link_' . $code, 'value' => $row['link'] ?? '',
                                        'placeholder' => 'URL hoặc đường dẫn',
                                    ]); ?>

                                    <?php if ($i === 0): ?>
                                        <div class="d-flex flex-wrap gap-3 mb-3">
                                            <?php component('form/checkbox', [
                                                'label' => 'Nofollow', 'name' => 'nofollow',
                                                'checked' => ($main['nofollow'] ?? 0) == 1,
                                                'inline' => true, 'switch' => false,
                                            ]); ?>
                                            <?php component('form/checkbox', [
                                                'label' => 'Mở tab mới', 'name' => 'target',
                                                'checked' => ($main['target'] ?? 0) == 1,
                                                'inline' => true, 'switch' => false,
                                            ]); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php component('form/editor', [
                                        'label' => 'Nội dung' . $sfx, 'name' => 'noi_dung[]',
                                        'id' => 'noi_dung_' . $code, 'value' => $row['noi_dung'] ?? '',
                                        'height' => '300px',
                                    ]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php component('card_close', ['body' => 'none']); ?>
                </div>

                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>

                    <?php component('form/image_picker', [
                        'label' => 'Hình ảnh', 'name' => 'hinh_anh',
                        'value' => $main['hinh_anh'] ?? '',
                        'table' => '_content',
                        'row_id' => $isEdit ? (string) $editId : '',
                        'height' => 140,
                    ]); ?>

                    <?php component('form/select', [
                        'label' => 'Nhóm nội dung', 'name' => 'id_loai', 'id' => 'id_loai',
                        'value' => $parent ?: ($main['id_loai'] ?? ''),
                        'options' => $groupOpts, 'placeholder' => 'Chọn nhóm',
                        'class' => 'select2',
                    ]); ?>

                    <?php component('form/input', [
                        'label' => 'Số thứ tự', 'name' => 'so_thu_tu', 'id' => 'so_thu_tu',
                        'type' => 'number', 'value' => (int) ($main['so_thu_tu'] ?? 0),
                    ]); ?>

                    <?php component('form/checkbox', [
                        'label' => 'Hiển thị', 'name' => 'hien_thi', 'id' => 'hien_thi',
                        'checked' => $isEdit ? (($main['hien_thi'] ?? 0) == 1) : true,
                    ]); ?>

                    <?php component('form/actions_bar', [
                        'back_url'   => $backUrl,
                        'can_edit'   => $canEdit,
                        'save_label' => $isEdit ? 'Cập nhật' : 'Lưu',
                        'save_edit'  => true,
                        'save_add'   => true,
                        'sticky'     => false,
                    ]); ?>

                    <?php component('card_close'); ?>
                </div>
            </div>
        </form>
    </div>
</div>