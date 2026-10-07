<?php
/**
 * Thuộc tính sản phẩm - Quản lý thuộc tính dùng cho biến thể.
 */

if (isset($_POST['capnhat']) || isset($_POST['add_variation'])) {
    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        admin_flash('error', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
        $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
    }
    $isEditPost = isset($_GET['id_loai']) && $_GET['id_loai'] !== '';
    if (!$isEditPost) {
        $data0['ten']  = addslashes($_POST['ten'][0] ?? '');
        $data0['type'] = 4;
        $d->reset();
        $d->setTable('cf_parent');

        if ($id_code = $d->insert($data0)) {
            foreach (get_json('lang') as $key => $value) {
                $item = [];
                $item['ten']      = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                $item['alias']    = addslashes($_POST['alias'][$key] ?? '');
                $item['mo_ta']    = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));
                $item['loai']     = $d->clear($_POST['loai'] ?? 'select');
                $item['sap_xep']  = $d->clear($_POST['sap_xep'] ?? '');
                $item['id_code']  = $id_code;
                $item['lang']     = $value['code'];

                $d->reset();
                $d->setTable('#_thuoctinh');
                $d->insert($item);
            }
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
        }
    } else {
        $id           = (int) $_GET['id_loai'];
        $data0['ten'] = addslashes($_POST['ten'][0] ?? '');

        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id);

        if ($d->update($data0)) {
            foreach (get_json('lang') as $key => $value) {
                $item = [];
                $item['ten']     = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                $item['alias']   = addslashes($_POST['alias'][$key] ?? '');
                $item['mo_ta']   = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));
                $item['loai']    = $d->clear($_POST['loai'] ?? 'select');
                $item['sap_xep'] = $d->clear($_POST['sap_xep'] ?? '');

                $rowId = (int) ($_POST['id_row'][$key] ?? 0);
                if ($rowId > 0) {
                    $d->reset();
                    $d->setTable('#_thuoctinh');
                    $d->setWhere('id', $rowId);
                    $d->update($item);
                }
            }
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
        }
    }
}

if (isset($_GET['delete']) && $_GET['delete'] != '') {
    if ($d->checkPermission_dele($id_module) == 1) {
        $id = (int) $_GET['delete'];

        $sub_content = $d->o_fet("select * from #_thuoctinh_giatri where id_thuoctinh=" . $id);
        if (count($sub_content) > 0) {
            foreach ($sub_content as $value) {
                $d->o_que("delete from cf_parent where id=" . $value['id_code']);
            }
            $d->o_que("delete from #_thuoctinh_giatri where id_thuoctinh=" . $id);
        }

        $d->reset();
        $d->setTable('#_thuoctinh');
        $d->setWhere('id_code', $id);
        if ($d->delete()) {
            $d->o_que("delete from cf_parent where id = $id");
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
        } else {
            $d->alert("Xóa dữ liệu bị lỗi!");
            $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
        }
    } else {
        $d->redirect("index.php?p=" . $_GET['p'] . "&a=variation");
    }
}

$isEdit = isset($_GET['id_loai']) && $_GET['id_loai'] !== '';
$editId = $isEdit ? (int) $_GET['id_loai'] : 0;
$langs  = get_json('lang');

$row = null;
if ($isEdit) {
    $row = $d->simple_fetch("select * from #_thuoctinh where id_code=" . $editId . " and lang='" . LANG . "'");
}

$loaiOptions = [
    'select' => 'Select',
    'color'  => 'Màu sắc',
    'image'  => 'Hình ảnh',
    'text'   => 'Text',
];
?>

<?php component('page_header', [
    'title'      => 'Biến thể sản phẩm',
    'subtitle'   => $isEdit ? '[Sửa thuộc tính]' : '[Thêm thuộc tính]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Sản phẩm', 'url' => admin_url(['p' => $_GET['p'], 'a' => 'man'])],
        ['label' => 'Biến thể'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-3">

            <div class="col-lg-4">
                <?php component('card_open', ['title' => $isEdit ? 'Sửa thuộc tính' : 'Thêm thuộc tính']); ?>

                <form method="post" action="<?= admin_url(['p' => $_GET['p'], 'a' => 'variation', 'id_loai' => $editId ?: '']) ?>">
                    <?= function_exists('admin_csrf_field') ? admin_csrf_field() : '' ?>

                    <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'attr']); ?>

                    <div class="tab-content">
                    <?php foreach ($langs as $key => $lang):
                        $code    = $lang['code'];
                        $langRow = [];
                        if ($isEdit) {
                            $langRow = $d->simple_fetch("select * from #_thuoctinh where id_code=" . $editId . " and lang='" . $code . "'") ?: [];
                        }
                        $isFirst = $key === 0;
                        $sfx     = count($langs) > 1 ? ' (' . $code . ')' : '';
                    ?>
                        <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?>"
                             id="attr-<?= e($code) ?>" role="tabpanel">

                            <input type="hidden" name="id_row[]" value="<?= e($langRow['id'] ?? '') ?>" />

                            <?php component('form/input', [
                                'label'       => 'Tên' . $sfx,
                                'name'        => 'ten[]',
                                'id'          => 'ten_' . $code,
                                'value'       => $langRow['ten'] ?? '',
                                'placeholder' => 'Tên thuộc tính',
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

                    <?php component('form/select', [
                        'label'    => 'Loại hiển thị',
                        'name'     => 'loai',
                        'value'    => $row['loai'] ?? 'select',
                        'options'  => $loaiOptions,
                    ]); ?>

                    <?php component('form/select', [
                        'label'       => 'Sắp xếp theo',
                        'name'        => 'sap_xep',
                        'value'       => $row['sap_xep'] ?? 'id',
                        'options'     => $data_type_sort,
                    ]); ?>

                    <?php component('form/actions_bar', [
                        'back_url'   => admin_url(['p' => $_GET['p'], 'a' => 'man']),
                        'cancel_url' => $isEdit ? admin_url(['p' => $_GET['p'], 'a' => 'variation']) : '',
                        'can_edit'   => true,
                        'save_label' => $isEdit ? 'Cập nhật' : 'Thêm mới',
                        'save_edit'  => false,
                    ]); ?>
                </form>

                <?php component('card_close'); ?>
            </div>

            <div class="col-lg-8">
                <?php component('card_open', ['title' => 'Danh sách thuộc tính']); ?>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">STT</th>
                                <th>Tên</th>
                                <th class="col-shrink">Loại</th>
                                <th class="text-center col-shrink">Giá trị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($data)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 5,
                                    'message' => 'Chưa có thuộc tính nào',
                                    'hint'    => 'Hãy điền form bên trái để thêm thuộc tính mới.'
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($data as $value): ?>
                                    <tr data-id="<?= (int) $value['id'] ?>" data-name="<?= e($value['ten']) ?>">
                                        <td class="text-center col-shrink">
                                            <?php component('stt_input', [
                                                'table'    => '#_thuoctinh',
                                                'id'       => $value['id'],
                                                'value'    => $value['sap_xep'] ?? 0,
                                                'can_edit' => true,
                                            ]); ?>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= e($value['ten']) ?></div>
                                            <div class="small text-secondary"><?= e($value['alias'] ?: '—') ?></div>
                                        </td>
                                        <td class="col-shrink">
                                            <?php $typeLabel = $data_type_variation[$value['loai']] ?? $value['loai']; ?>
                                            <span class="badge bg-light text-dark border"><?= e($typeLabel) ?></span>
                                        </td>
                                        <td class="col-shrink">
                                            <?php
                                            $get_variants = $d->o_fet("select ten from #_thuoctinh_giatri where id_thuoctinh=" . (int) $value['id_code'] . " and lang='" . LANG . "' order by id ASC");
                                            $names = array_filter(array_column($get_variants ?: [], 'ten'));
                                            ?>
                                            <span class="small text-secondary"><?= e($names ? implode(', ', $names) : '—') ?></span>
                                            <div class="mt-2">
                                                <a href="<?= admin_url(['p' => $_GET['p'], 'a' => 'variants', 'variation' => $value['id_code']]) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-plus me-1"></i>Giá trị
                                                </a>
                                            </div>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('row_actions', [
                                                'edit_url'    => admin_url(['p' => $_GET['p'], 'a' => 'variation', 'id_loai' => $value['id_code']]),
                                                'delete_url'  => admin_url(['p' => $_GET['p'], 'a' => 'variation', 'delete' => $value['id_code']]),
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
    </div>
</div>