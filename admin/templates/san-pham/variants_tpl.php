<?php
/**
 * Giá trị thuộc tính sản phẩm.
 *
 * - Thêm / sửa giá trị thuộc tính
 * - Danh sách các giá trị thuộc tính
 */

/* ========================================================================
 * XỬ LÝ FORM
 * ======================================================================== */
$isEdit = isset($_GET['id_loai']) && $_GET['id_loai'] !== '';
$editId = $isEdit ? (int) $_GET['id_loai'] : 0;

if (isset($_POST['capnhat']) || isset($_POST['add_variation'])) {
    if (!$isEdit) {
        $id_thuoctinh = (int) ($_POST['id_thuoctinh'] ?? $_GET['variation'] ?? 0);

        $data0['ten']  = addslashes($_POST['ten'][0] ?? '');
        $data0['type'] = 5;
        $d->reset();
        $d->setTable('cf_parent');

        if ($id_code = $d->insert($data0)) {
            foreach (get_json('lang') as $key => $value) {
                $variants = [];
                $variants['id_thuoctinh'] = $id_thuoctinh;
                $variants['gia_tri']      = $d->clear(addslashes($_POST['gia_tri'] ?? ''));
                $variants['ten']          = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                $variants['alias']        = addslashes($_POST['alias'][$key] ?? '');
                $variants['mo_ta']        = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));
                $variants['id_code']      = $id_code;
                $variants['lang']         = $value['code'];

                $d->reset();
                $d->setTable('#_thuoctinh_giatri');
                $d->insert($variants);
            }
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variants&variation=" . $_GET['variation']);
        }
    } else {
        $id           = (int) $_GET['id_loai'];
        $data0['ten'] = addslashes($_POST['ten'][0] ?? '');

        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id);

        if ($d->update($data0)) {
            foreach (get_json('lang') as $key => $value) {
                $variants = [];
                $variants['gia_tri'] = $d->clear(addslashes($_POST['gia_tri'] ?? ''));
                $variants['ten']     = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                $variants['alias']   = addslashes($_POST['alias'][$key] ?? '');
                $variants['mo_ta']   = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));

                $rowId = (int) ($_POST['id_row'][$key] ?? 0);
                if ($rowId > 0) {
                    $d->reset();
                    $d->setTable('#_thuoctinh_giatri');
                    $d->setWhere('id', $rowId);
                    $d->update($variants);
                }
            }
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variants&variation=" . $_GET['variation']);
        }
    }
}

if (isset($_GET['delete']) && $_GET['delete'] != '') {
    if ($d->checkPermission_dele($id_module) == 1) {
        $id = (int) $_GET['delete'];
        $d->reset();
        $d->setTable('#_thuoctinh_giatri');
        $d->setWhere('id_code', $id);
        if ($d->delete()) {
            $d->o_que("delete from cf_parent where id=$id");
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variants&variation=" . $_GET['variation']);
        } else {
            $d->alert("Xóa dữ liệu bị lỗi!");
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variants&variation=" . $_GET['variation']);
        }
    } else {
        $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
    }
}

/* ========================================================================
 * CHUẨN BỊ DỮ LIỆU HIỂN THỊ
 * ======================================================================== */
$langs  = get_json('lang');
$variationId = (int) ($_GET['variation'] ?? 0);

$backUrl = admin_url(['p' => $_GET['p'] ?? 'san-pham', 'a' => 'variation']);

// Ưu tiên dữ liệu từ controller (show_variants), fallback query nếu thiếu
if (empty($variation) || !is_array($variation)) {
    $variation = $d->simple_fetch(
        "select * from #_thuoctinh where id_code=" . $variationId . " and lang='" . LANG . "' order by id ASC"
    ) ?: [];
}

if (!isset($data) || !is_array($data)) {
    $data = $d->o_fet(
        "select * from #_thuoctinh_giatri where id_thuoctinh=" . $variationId . " and lang='" . LANG . "' order by id ASC"
    ) ?: [];
} else {
    // Lọc theo ngôn ngữ hiện tại nếu controller trả về nhiều lang
    $data = array_values(array_filter($data, static function ($row) {
        return !isset($row['lang']) || $row['lang'] === LANG;
    }));
}

$row = null;
if ($isEdit) {
    $row = $d->simple_fetch(
        "select * from #_thuoctinh_giatri where id_code=" . $editId . " and lang='" . LANG . "'"
    ) ?: [];
}
?>

<style>
    .show-value {
        width: 3rem;
        height: 3rem;
        border: 1px solid #ccc;
        border-radius: 50%;
        overflow: hidden;
    }
    .show-value.color {
        background-color: var(--value);
    }
    .show-value.image {
        background-image: var(--value);
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
    }
</style>

<?php component('page_header', [
    'title'      => 'Giá trị thuộc tính',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Thuộc tính', 'url' => admin_url(['p' => $_GET['p'] ?? 'san-pham', 'a' => 'variation'])],
        ['label' => $isEdit ? 'Sửa giá trị' : 'Thêm giá trị'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form name="frm" method="post" action="<?= admin_url(['p' => $_GET['p'], 'a' => 'variants', 'variation' => $_GET['variation'], 'id_loai' => $editId ?: '']) ?>" enctype="multipart/form-data">
            <input type="hidden" name="id_thuoctinh" value="<?= (int) ($_GET['variation'] ?? 0) ?>" />

            <div class="row g-3">
                <div class="col-lg-4">
                    <?php component('card_open', ['title' => $isEdit ? 'Sửa giá trị thuộc tính' : 'Thêm giá trị thuộc tính']); ?>

                    <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'val']); ?>

                    <div class="tab-content">
                    <?php foreach ($langs as $key => $lang):
                        $code     = $lang['code'];
                        $langRow  = [];
                        if ($isEdit) {
                            $langRow = $d->simple_fetch(
                                "select * from #_thuoctinh_giatri where id_code=" . $editId . " and lang='" . $code . "'"
                            ) ?: [];
                        }
                        $isFirst  = $key === 0;
                        $sfx      = count($langs) > 1 ? ' (' . $code . ')' : '';
                    ?>
                        <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?>"
                             id="val-<?= e($code) ?>" role="tabpanel">

                            <input type="hidden" name="id_row[]" value="<?= e($langRow['id'] ?? '') ?>" />

                            <?php component('form/input', [
                                'label'       => 'Tên' . $sfx,
                                'name'        => 'ten[]',
                                'id'          => 'ten_' . $code,
                                'value'       => $langRow['ten'] ?? '',
                                'placeholder' => 'Tên hiển thị',
                                'required'    => $isFirst,
                            ]); ?>

                            <?php component('form/input', [
                                'label'       => 'Đường dẫn' . $sfx,
                                'name'        => 'alias[]',
                                'id'          => 'alias_' . $code,
                                'value'       => $langRow['alias'] ?? '',
                                'placeholder' => 'Đường dẫn thân thiện',
                            ]); ?>

                            <?php component('form/textarea', [
                                'label'       => 'Mô tả' . $sfx,
                                'name'        => 'mo_ta[]',
                                'id'          => 'mo_ta_' . $code,
                                'value'       => $langRow['mo_ta'] ?? '',
                                'rows'        => 3,
                                'placeholder' => 'Mô tả ngắn',
                            ]); ?>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <hr class="my-3">

                    <?php
                    $loaiAttr = $variation['loai'] ?? 'text';
                    $giaTriValue = $row['gia_tri'] ?? '';

                    if ($loaiAttr === 'color'): ?>
                        <?php component('form/input', [
                            'label'       => 'Giá trị',
                            'name'        => 'gia_tri',
                            'id'          => 'gia_tri',
                            'value'       => $giaTriValue,
                            'type'        => 'color',
                            'placeholder' => '#ff0000',
                            'hint'        => 'Loại thuộc tính: color — nhập mã màu HEX, ví dụ: #ff0000',
                            'class'       => 'form-control-color w-100',
                        ]); ?>
                    <?php elseif ($loaiAttr === 'image'): ?>
                        <?php component('form/image_picker', [
                            'label'       => 'Giá trị',
                            'name'        => 'gia_tri',
                            'id'          => 'gia_tri',
                            'value'       => $giaTriValue,
                            'hint'        => 'Loại thuộc tính: image — chọn file ảnh từ thư viện.',
                        ]); ?>
                    <?php else: ?>
                        <?php component('form/input', [
                            'label'       => 'Giá trị',
                            'name'        => 'gia_tri',
                            'id'          => 'gia_tri',
                            'value'       => $giaTriValue,
                            'placeholder' => 'Giá trị hiển thị',
                            'hint'        => 'Loại thuộc tính: ' . $loaiAttr,
                        ]); ?>
                    <?php endif; ?>


                    <?php component('form/actions_bar', [
                        'back_url'    => $backUrl,
                        'cancel_url'  => $isEdit ? admin_url(['p' => $_GET['p'], 'a' => 'variants', 'variation' => $_GET['variation']]) : '',
                        'can_edit'    => true,
                        'save_label'  => $isEdit ? 'Cập nhật' : 'Thêm mới',
                        'save_edit'   => false,
                    ]); ?>

                    <?php component('card_close'); ?>
                </div>
                <div class="col-lg-8">
                    <?php component('card_open', ['title' => 'Danh sách các giá trị']); ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle table-admin">
                            <thead>
                                <tr>
                                    <th class="text-center col-shrink" style="width: 120px;">Giá trị</th>
                                    <th>Tên</th>
                                    <th class="col-shrink">Đường dẫn</th>
                                    <th>Mô tả</th>
                                    <th class="text-center col-shrink">Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data)): ?>
                                    <?php component('empty_state', [
                                        'colspan' => 5,
                                        'message' => 'Chưa có giá trị thuộc tính nào',
                                        'hint'    => 'Hãy điền form bên trái để thêm giá trị mới.'
                                    ]); ?>
                                <?php else: ?>
                                    <?php foreach ($data as $value): ?>
                                        <tr data-id="<?= (int) $value['id'] ?>" data-name="<?= e($value['ten']) ?>">
                                            <td class="text-center">
                                                <?php
                                                $loaiAttr = $variation['loai'] ?? '';
                                                $giaTri   = trim((string) ($value['gia_tri'] ?? ''));
                                                if ($loaiAttr === 'color' && $giaTri !== ''): ?>
                                                    <div class="show-value color mx-auto" style="--value: <?= e($giaTri) ?>" title="<?= e($giaTri) ?>"></div>
                                                    <div class="small text-secondary mt-1"><?= e($giaTri) ?></div>
                                                <?php elseif ($loaiAttr === 'image' && $giaTri !== ''): ?>
                                                    <div class="show-value image mx-auto" style="--value: url(<?= e('/media/images/' . $giaTri) ?>)" title="<?= e($giaTri) ?>"></div>
                                                    <div class="small text-secondary mt-1"><?= e($giaTri) ?></div>
                                                <?php else: ?>
                                                    <?= e($giaTri !== '' ? $giaTri : ($value['ten'] ?? '—')) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-medium"><?= e($value['ten'] ?? '') ?></div>
                                            </td>
                                            <td class="small text-secondary"><?= e($value['alias'] ?? '') ?></td>
                                            <td class="small text-secondary"><?= e($value['mo_ta'] ?? '') ?></td>
                                            <td class="text-center col-shrink">
                                                <?php component('row_actions', [
                                                    'edit_url'    => admin_url(['p' => $_GET['p'], 'a' => 'variants', 'variation' => $_GET['variation'], 'id_loai' => $value['id_code']]),
                                                    'delete_url'  => admin_url(['p' => $_GET['p'], 'a' => 'variants', 'variation' => $_GET['variation'], 'delete' => $value['id_code']]),
                                                    'name'        => $value['ten'],
                                                    'child_count' => 0,
                                                    'can_edit'    => true,
                                                    'can_delete'  => true,
                                                ]); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php component('card_close'); ?>
                </div>
            </div>
        </form>
    </div>
</div>
