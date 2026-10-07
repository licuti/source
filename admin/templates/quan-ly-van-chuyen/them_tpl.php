<?php
/**
 * Vận chuyển — Thêm / Sửa mức phí.
 */
$canEdit = $d->checkPermission_edit($id_module) == 1;
$MODULE  = 'quan-ly-van-chuyen';
$item    = is_array($item ?? null) ? $item : [];
$isEdit  = !empty($item['id']);
$editId  = $isEdit ? (int) $item['id'] : 0;
$tinhs   = is_array($tinhs ?? null) ? $tinhs : [];
$huyens  = is_array($huyens ?? null) ? $huyens : [];
$xas     = is_array($xas ?? null) ? $xas : [];
$wUnit   = $config['shipping']['weight_unit'] ?? 'g';

$tinhOpts = ['' => '— Chọn Tỉnh / Thành phố —'];
foreach ($tinhs as $t) {
    $tinhOpts[(string) $t['code']] = $t['ten'];
}
$huyenOpts = ['' => '— Tất cả Quận / Huyện —'];
foreach ($huyens as $h) {
    $huyenOpts[(string) $h['code']] = $h['ten'];
}
$xaOpts = ['' => '— Tất cả Phường / Xã —'];
foreach ($xas as $x) {
    $xaOpts[(string) $x['code']] = $x['ten'];
}

$actionUrl = admin_url(array_filter([
    'p'  => $MODULE,
    'a'  => 'save',
    'id' => $isEdit ? $editId : null,
]));
?>

<?php component('page_header', [
    'title'      => 'Vận chuyển',
    'subtitle'   => $isEdit ? '[Sửa mức phí]' : '[Thêm mức phí]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Vận chuyển', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form method="post" action="<?= e($actionUrl) ?>" name="frm" data-warn-unsaved>
            <div class="row g-3">
                <div class="col-lg-8">
                    <?php component('card_open', ['title' => 'Địa bàn & phí']); ?>

                    <?php component('form/select', [
                        'label'    => 'Tỉnh / Thành phố',
                        'name'     => 'id_tinh',
                        'id'       => 'id_tinh',
                        'value'    => (string) ($item['id_tinh'] ?? ''),
                        'options'  => $tinhOpts,
                        'required' => true,
                        'class'    => 'select2',
                    ]); ?>

                    <?php component('form/select', [
                        'label'   => 'Quận / Huyện',
                        'name'    => 'id_huyen',
                        'id'      => 'id_huyen',
                        'value'   => (string) ($item['id_huyen'] ?? ''),
                        'options' => $huyenOpts,
                        'hint'    => 'Để trống = áp dụng toàn tỉnh.',
                        'class'   => 'select2',
                    ]); ?>

                    <?php component('form/select', [
                        'label'   => 'Phường / Xã',
                        'name'    => 'id_xa',
                        'id'      => 'id_xa',
                        'value'   => (string) ($item['id_xa'] ?? ''),
                        'options' => $xaOpts,
                        'hint'    => 'Để trống = áp dụng toàn huyện.',
                        'class'   => 'select2',
                    ]); ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="phi_ship">Phí vận chuyển (đ) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm js-money" name="phi_ship" id="phi_ship"
                                   required value="<?= e(number_format((float) ($item['phi_ship'] ?? 0), 0, '', ',')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="phi_extra_kg">Phụ phí mỗi kg (đ)</label>
                            <input type="text" class="form-control form-control-sm js-money" name="phi_extra_kg" id="phi_extra_kg"
                                   value="<?= e(number_format((float) ($item['phi_extra_kg'] ?? 0), 0, '', ',')) ?>">
                            <div class="form-text">Cộng thêm cho mỗi kg vượt KL cơ bản.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="free_ship_weight">Free ship từ (<?= e($wUnit) ?>)</label>
                            <input type="number" class="form-control form-control-sm" name="free_ship_weight"
                                   id="free_ship_weight" min="0" step="1"
                                   value="<?= (int) ($item['free_ship_weight'] ?? 0) ?>">
                            <div class="form-text">0 = không áp dụng theo KL.</div>
                        </div>
                    </div>
                    <?php component('form/input', [
                        'label'       => 'Ghi chú / thời gian giao',
                        'name'        => 'ghi_chu',
                        'id'          => 'ghi_chu',
                        'value'       => $item['ghi_chu'] ?? '',
                        'placeholder' => 'VD: Giao 2–3 ngày, nội thành…',
                    ]); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <?php component('form/input', [
                                'label' => 'Số thứ tự',
                                'name'  => 'so_thu_tu',
                                'id'    => 'so_thu_tu',
                                'type'  => 'number',
                                'value' => $item['so_thu_tu'] ?? '0',
                                'attr'  => ['min' => '0'],
                            ]); ?>
                        </div>
                        <div class="col-md-8 d-flex align-items-end pb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       name="hien_thi" id="hien_thi" value="1"
                                    <?= !isset($item['hien_thi']) || (int) $item['hien_thi'] === 1 ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hien_thi">Kích hoạt mức phí này</label>
                            </div>
                        </div>
                    </div>

                    <?php component('form/actions_bar', [
                        'back_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'can_edit'  => $canEdit,
                        'save_edit' => true,
                    ]); ?>

                    <?php component('card_close'); ?>
                </div>

                <div class="col-lg-4">
                    <?php component('card_open', ['title' => 'Gợi ý']); ?>
                    <ul class="small text-secondary mb-0 ps-3">
                        <li class="mb-2">Chỉ chọn <strong>tỉnh</strong> = phí áp dụng toàn tỉnh.</li>
                        <li class="mb-2">Chọn thêm <strong>huyện</strong> (để trống xã) = toàn huyện.</li>
                        <li class="mb-2">Chọn đủ 3 cấp = phí riêng phường/xã.</li>
                        <li class="mb-2">Phụ phí/kg cộng khi vượt khối lượng cơ bản (cấu hình chung).</li>
                        <li>Không tạo trùng cùng tỉnh + huyện + xã.</li>
                    </ul>
                    <?php component('card_close'); ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(function () {
    function fmtMoney(el) {
        var v = String(el.value || '').replace(/[^\d]/g, '');
        if (v === '') { el.value = ''; return; }
        el.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    $(document).on('keyup blur', '.js-money', function () { fmtMoney(this); });

    if ($.fn.select2) {
        $('#id_tinh, #id_huyen, #id_xa').select2({ width: '100%' });
    }

    function loadHuyen(codeTinh, keepHuyen) {
        if (!codeTinh) {
            $('#id_huyen').html('<option value="">— Tất cả Quận / Huyện —</option>').val('').trigger('change');
            $('#id_xa').html('<option value="">— Tất cả Phường / Xã —</option>').val('').trigger('change');
            return;
        }
        $.post('../sources/ajax/ajax.php', { do: 'get_huyen', code_tinh: codeTinh })
            .done(function (html) {
                $('#id_huyen').html('<option value="">— Tất cả Quận / Huyện —</option>' + html);
                if (keepHuyen) {
                    $('#id_huyen').val(keepHuyen);
                }
                $('#id_huyen').trigger('change.select2');
                if (!keepHuyen) {
                    $('#id_xa').html('<option value="">— Tất cả Phường / Xã —</option>').trigger('change.select2');
                }
            });
    }

    function loadXa(codeHuyen, keepXa) {
        if (!codeHuyen) {
            $('#id_xa').html('<option value="">— Tất cả Phường / Xã —</option>').val('').trigger('change.select2');
            return;
        }
        $.post('../sources/ajax/ajax.php', { do: 'get_xa', code_huyen: codeHuyen })
            .done(function (html) {
                $('#id_xa').html('<option value="">— Tất cả Phường / Xã —</option>' + html);
                if (keepXa) {
                    $('#id_xa').val(keepXa);
                }
                $('#id_xa').trigger('change.select2');
            });
    }

    $('#id_tinh').on('change', function () {
        loadHuyen($(this).val(), '');
    });
    $('#id_huyen').on('change', function () {
        loadXa($(this).val(), '');
    });
});
</script>
