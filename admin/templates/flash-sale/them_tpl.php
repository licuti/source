<?php
/**
 * Flash Sale — Them / Sua.
 */
$canEdit = $d->checkPermission_edit($id_module) == 1;
$isEdit  = isset($_GET['id']);
$MODULE  = 'flash-sale';

$item = [];
if ($isEdit && !empty($items)) {
    $item = $items;
}

$actionUrl = admin_url(array_filter([
    'p'    => $MODULE,
    'a'    => 'save',
    'id'   => $isEdit ? (int) $_GET['id'] : '',
    'page' => $_REQUEST['page'] ?? '',
]));

function fmt_datetime_local($v) {
    if (empty($v)) return '';
    $ts = strtotime($v);
    return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
}

$tuNgay  = fmt_datetime_local($item['tu_ngay'] ?? '');
$denNgay = fmt_datetime_local($item['den_ngay'] ?? '');
?>

<?php component('page_header', [
    'title'      => 'Flash Sale',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Flash Sale', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class='app-content'>
    <div class='container-fluid'>
        <form name='frm' method='post' action='<?= e($actionUrl) ?>' enctype='multipart/form-data' data-warn-unsaved>

            <div class='row justify-content-center'>
                <div class='col-lg-8'>

                    <?php component('card_open', ['title' => 'Thông tin chương trình', 'icon' => 'fa-solid fa-bolt']); ?>

                        <?php component('form/input', [
                            'label'    => 'Tên chương trình',
                            'name'     => 'ten',
                            'id'       => 'ten',
                            'value'    => $item['ten'] ?? '',
                            'required' => true,
                        ]); ?>

                        <div class='row'>
                            <div class='col-md-6'>
                                <?php component('form/input', [
                                    'label' => 'Từ ngày',
                                    'name'  => 'tu_ngay',
                                    'id'    => 'tu_ngay',
                                    'type'  => 'datetime-local',
                                    'value' => $tuNgay,
                                ]); ?>
                            </div>
                            <div class='col-md-6'>
                                <?php component('form/input', [
                                    'label' => 'Đến ngày',
                                    'name'  => 'den_ngay',
                                    'id'    => 'den_ngay',
                                    'type'  => 'datetime-local',
                                    'value' => $denNgay,
                                ]); ?>
                            </div>
                        </div>

                        <?php component('form/checkbox', [
                            'label'   => 'Hiển thị',
                            'name'    => 'hien_thi',
                            'checked' => $isEdit ? (($item['hien_thi'] ?? 0) == 1) : true,
                        ]); ?>

                    <?php component('card_close'); ?>

                    <?php component('card_open', ['title' => 'Sản phẩm khuyến mãi', 'icon' => 'fa-solid fa-box-open', 'body' => 'none']); ?>

                        <div class='p-3'>
                            <div class='mb-3'>
                                <input type='text' id='product-search' class='form-control form-control-sm'
                                       placeholder='Tìm sản phẩm...' aria-label='Tìm sản phẩm'>
                            </div>
                            <div class='table-responsive' style='max-height:420px; overflow-y:auto;'>
                                <table class='table table-sm table-hover table-bordered align-middle' id='product-table'>
                                    <thead class='table-light sticky-top'>
                                        <tr>
                                            <th class='text-center' style='width:50px'>
                                                <input type='checkbox' id='check_all_sp' class='form-check-input' aria-label='Chọn tất cả'>
                                            </th>
                                            <th style='min-width:250px'>Sản phẩm</th>
                                            <th class='text-end' style='width:120px'>Giá gốc</th>
                                            <th class='text-end' style='width:160px'>Giá Flash Sale</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($products)): ?>
                                            <tr>
                                                <td colspan='4' class='text-center text-secondary py-3'>Chưa có sản phẩm</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($products as $sp): 
                                                $isSelected = isset($selectedProducts[(int) $sp['id_code']]); // id_code is the product identifier
                                                $flashPrice = $selectedProducts[(int) $sp['id_code']] ?? 0;
                                            ?>
                                                <tr data-name='<?= e(strtolower($sp['ten'])) ?>'>
                                                    <td class='text-center'>
                                                        <input type='checkbox' class='form-check-input js-sp-check'
                                                               name='sp[<?= (int) $sp['id_code'] ?>]' value='1'
                                                               <?= $isSelected ? 'checked' : '' ?>>
                                                    </td>
                                                    <td class='fw-medium'><?= e($sp['ten']) ?></td>
                                                    <td class='text-end'><?= number_format((int) ($sp['gia'] ?? 0)) ?> đ</td>
                                                    <td class='text-end'>
                                                        <input type='number' min='0'
                                                               name='gia_flash_sale[<?= (int) $sp['id_code'] ?>]'
                                                               value='<?= (int) $flashPrice ?>'
                                                               class='form-control form-control-sm text-end js-sp-price'
                                                               placeholder='Giá Flash Sale'>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php component('card_close', ['body' => 'none']); ?>

                    <?php component('form/actions_bar', [
                        'back_url' => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'can_edit' => $canEdit,
                    ]); ?>

                </div>
            </div>

        </form>
    </div>
</div>

<script>
(function() {
    var searchInput = document.getElementById('product-search');
    var table = document.getElementById('product-table');
    var rows = table ? table.querySelectorAll('tbody tr') : [];
    var checkAll = document.getElementById('check_all_sp');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var term = this.value.trim().toLowerCase();
            rows.forEach(function(row) {
                var name = row.getAttribute('data-name') || '';
                row.style.display = name.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            var visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
            visibleRows.forEach(function(row) {
                var cb = row.querySelector('.js-sp-check');
                if (cb) cb.checked = checkAll.checked;
            });
        });
    }
})();
</script>
