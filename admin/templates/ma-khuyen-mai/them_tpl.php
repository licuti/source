<?php
/**
 * Mã khuyến mãi — Thêm / Sửa.
 */
$canEdit    = $d->checkPermission_edit($id_module) == 1;
$MODULE     = 'ma-khuyen-mai';
$item       = is_array($item ?? null) ? $item : [];
$isEdit     = !empty($item['id']);
$editId     = $isEdit ? (int) $item['id'] : 0;
$members    = is_array($members ?? null) ? $members : [];
$used_count = (int) ($used_count ?? 0);
$usage_logs = is_array($usage_logs ?? null) ? $usage_logs : [];
$limitUse   = (int) ($item['gioi_han'] ?? 0);

$selectedTv = [];
if (!empty($item['id_thanhvien'])) {
    $selectedTv = array_values(array_filter(array_map(
        'intval',
        explode(',', trim((string) $item['id_thanhvien'], ','))
    )));
}

$actionUrl = admin_url(array_filter([
    'p'  => $MODULE,
    'a'  => 'save',
    'id' => $isEdit ? $editId : null,
]));
?>

<?php component('page_header', [
    'title'      => 'Mã khuyến mãi',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Mã khuyến mãi', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form method="post" action="<?= e($actionUrl) ?>" name="frm" data-warn-unsaved>
            <div class="row g-3">
                <div class="col-lg-8">
                    <?php component('card_open', ['title' => 'Thông tin mã']); ?>

                    <?php if ($isEdit): ?>
                        <div class="alert alert-light border small mb-3 d-flex flex-wrap gap-3 align-items-center">
                            <span>Đã dùng: <strong><?= $used_count ?></strong>
                                / <?= $limitUse > 0 ? $limitUse : '∞' ?></span>
                            <span><?= km_status_badge($item, $used_count) ?></span>
                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'history', 'id' => $editId])) ?>"
                               class="ms-auto">Xem lịch sử dùng →</a>
                        </div>
                    <?php endif; ?>

                    <?php component('form/input', [
                        'label'       => 'Tên chương trình',
                        'name'        => 'ten',
                        'id'          => 'ten',
                        'value'       => $item['ten'] ?? '',
                        'placeholder' => 'VD: Ưu đãi thành viên mới',
                        'required'    => true,
                    ]); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <?php component('form/input', [
                                'label'       => 'Mã khuyến mãi',
                                'name'        => 'ma',
                                'id'          => 'ma',
                                'value'       => $item['ma'] ?? '',
                                'placeholder' => 'VD: WELCOME10',
                                'required'    => true,
                                'hint'        => 'Chỉ A–Z, 0–9, gạch ngang/dưới. Tự chuyển hoa.',
                                'attr'        => ['style' => 'text-transform:uppercase', 'maxlength' => '40'],
                            ]); ?>
                        </div>
                        <div class="col-md-3">
                            <?php component('form/input', [
                                'label'    => 'Giá trị',
                                'name'     => 'gia_tri',
                                'id'       => 'gia_tri',
                                'type'     => 'number',
                                'value'    => $item['gia_tri'] ?? '',
                                'required' => true,
                                'attr'     => ['min' => '1', 'max' => '999999999'],
                            ]); ?>
                        </div>
                        <div class="col-md-3">
                            <?php component('form/select', [
                                'label'   => 'Đơn vị',
                                'name'    => 'don_vi',
                                'id'      => 'don_vi',
                                'value'   => (string) ($item['don_vi'] ?? '0'),
                                'options' => ['0' => 'VNĐ', '1' => '%'],
                            ]); ?>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <?php component('form/select', [
                                'label'   => 'Loại khuyến mãi',
                                'name'    => 'loai',
                                'id'      => 'loai',
                                'value'   => (string) ($item['loai'] ?? '0'),
                                'options' => [
                                    '0' => 'Giảm tổng đơn hàng',
                                    '1' => 'Giảm phí ship',
                                ],
                            ]); ?>
                        </div>
                        <div class="col-md-6" id="wrap-gia-tri-max">
                            <?php component('form/input', [
                                'label' => 'Giảm tối đa (khi %)',
                                'name'  => 'gia_tri_max',
                                'id'    => 'gia_tri_max',
                                'type'  => 'number',
                                'value' => $item['gia_tri_max'] ?? '0',
                                'hint'  => 'Chỉ khi đơn vị là %. Nhập 0 = không giới hạn.',
                                'attr'  => ['min' => '0'],
                            ]); ?>
                        </div>
                    </div>

                    <?php component('form/input', [
                        'label' => 'Điều kiện đơn tối thiểu (đ)',
                        'name'  => 'dieu_kien',
                        'id'    => 'dieu_kien',
                        'type'  => 'number',
                        'value' => $item['dieu_kien'] ?? '0',
                        'hint'  => 'Nhập 0 nếu áp dụng mọi đơn hàng.',
                        'attr'  => ['min' => '0'],
                    ]); ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="tu_ngay">Từ ngày</label>
                            <input type="date" class="form-control form-control-sm" name="tu_ngay" id="tu_ngay"
                                   value="<?= e((string) ($item['tu_ngay'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="den_ngay">Đến ngày</label>
                            <input type="date" class="form-control form-control-sm" name="den_ngay" id="den_ngay"
                                   value="<?= e((string) ($item['den_ngay'] ?? '')) ?>">
                        </div>
                    </div>

                    <?php component('form/input', [
                        'label' => 'Giới hạn lượt dùng',
                        'name'  => 'gioi_han',
                        'id'    => 'gioi_han',
                        'type'  => 'number',
                        'value' => $item['gioi_han'] ?? '0',
                        'hint'  => 'Nhập 0 nếu không giới hạn số lượt.',
                        'attr'  => ['min' => '0'],
                    ]); ?>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   name="hien_thi" id="hien_thi" value="1"
                                <?= !isset($item['hien_thi']) || (int) $item['hien_thi'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="hien_thi">Đang bật (cho phép áp dụng trên website)</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="id_thanhvien">Thành viên áp dụng</label>
                        <select class="form-select form-select-sm" name="id_thanhvien[]" id="id_thanhvien"
                                multiple data-placeholder="Tất cả (để trống)">
                            <?php foreach ($members as $tv): ?>
                                <?php $tid = (int) $tv['id']; ?>
                                <option value="<?= $tid ?>" <?= in_array($tid, $selectedTv, true) ? 'selected' : '' ?>>
                                    <?= e(($tv['ho_ten'] ?? '') . (!empty($tv['email']) ? ' — ' . $tv['email'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Để trống = mọi khách. Danh sách tối đa 300 thành viên gần nhất.</div>
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
                        <li class="mb-2">Mã nên viết hoa, không dấu, không khoảng trắng.</li>
                        <li class="mb-2">Đơn vị <strong>%</strong>: nên đặt <em>Giảm tối đa</em> để tránh giảm quá lớn (tối đa 100%).</li>
                        <li class="mb-2">Loại <strong>Giảm phí ship</strong> chỉ trừ phần vận chuyển.</li>
                        <li class="mb-2">Tắt mã bằng công tắc nếu cần tạm ngưng mà không xóa.</li>
                        <li>Lượt dùng được hệ thống tự đếm khi khách áp dụng mã.</li>
                    </ul>
                    <?php component('card_close'); ?>

                    <?php if ($isEdit && !empty($usage_logs)): ?>
                        <?php component('card_open', ['title' => 'Lượt dùng gần đây']); ?>
                        <div class="small">
                            <?php foreach (array_slice($usage_logs, 0, 8) as $ls): ?>
                                <div class="border-bottom py-2">
                                    <div class="fw-medium"><?= e((string) ($ls['email'] ?: $ls['dien_thoai'] ?: ('TV #' . ($ls['id_thanhvien'] ?? '')))) ?></div>
                                    <div class="text-secondary">
                                        <?= e((string) ($ls['ngay_dung'] ?? '')) ?>
                                        <?php if (!empty($ls['id_donhang'])): ?>
                                            · Đơn #<?= (int) $ls['id_donhang'] ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a class="btn btn-sm btn-outline-secondary w-100 mt-2"
                           href="<?= e(admin_url(['p' => $MODULE, 'a' => 'history', 'id' => $editId])) ?>">
                            Xem tất cả
                        </a>
                        <?php component('card_close'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(function () {
    function toggleMax() {
        var isPercent = String($('#don_vi').val()) === '1';
        $('#wrap-gia-tri-max').toggleClass('d-none', !isPercent);
        if (isPercent) {
            $('#gia_tri').attr('max', 100);
        } else {
            $('#gia_tri').removeAttr('max');
        }
    }
    $('#don_vi').on('change', toggleMax);
    toggleMax();

    if ($.fn.select2) {
        $('#id_thanhvien').select2({
            width: '100%',
            placeholder: 'Tất cả thành viên (để trống)',
            allowClear: true
        });
    }
});
</script>
