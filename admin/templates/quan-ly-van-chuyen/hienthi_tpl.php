<?php
/**
 * Quản lý vận chuyển — cấu hình chung + danh sách mức phí.
 */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'quan-ly-van-chuyen';
$TABLE     = '#_ship';
$items     = is_array($items ?? null) ? $items : [];
$page      = (int) ($_GET['page'] ?? 1);
$filter_tinh = $filter_tinh ?? ($_GET['tinh'] ?? '');
$ss = is_array($shipping_settings ?? null) ? $shipping_settings : [];
$tinhs = is_array($tinhs ?? null) ? $tinhs : [];
$wUnit = $config['shipping']['weight_unit'] ?? 'g';

$tinhOpts = ['' => '— Tất cả tỉnh —'];
foreach ($tinhs as $t) {
    $tinhOpts[(string) $t['code']] = $t['ten'];
}

$currentSearch = [
    'col' => '',
    'key' => $_GET['key'] ?? '',
];
?>

<?php component('page_header', [
    'title'      => 'Quản lý vận chuyển',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản lý bán hàng'],
        ['label' => 'Vận chuyển'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php if ($canEdit): ?>
        <?php component('card_open', ['title' => 'Cấu hình chung', 'icon' => 'fa-solid fa-sliders']); ?>
            <form method="post" action="<?= e(admin_url(['p' => $MODULE, 'a' => 'save_settings'])) ?>" class="row g-3">
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="free_ship_threshold">Miễn phí từ (đ)</label>
                    <input type="text" class="form-control form-control-sm js-money" name="free_ship_threshold"
                           id="free_ship_threshold"
                           value="<?= e(number_format((float) ($ss['free_ship_threshold'] ?? 0), 0, '', ',')) ?>">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="default_ship_phi">Phí mặc định (đ)</label>
                    <input type="text" class="form-control form-control-sm js-money" name="default_ship_phi"
                           id="default_ship_phi"
                           value="<?= e(number_format((float) ($ss['default_ship_phi'] ?? 0), 0, '', ',')) ?>">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="ship_base_weight">KL cơ bản (<?= e($wUnit) ?>)</label>
                    <input type="number" class="form-control form-control-sm" name="ship_base_weight"
                           id="ship_base_weight" min="0" step="1"
                           value="<?= (int) ($ss['ship_base_weight'] ?? 1000) ?>">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label" for="box_phi">Phí đóng thùng (đ)</label>
                    <input type="text" class="form-control form-control-sm js-money" name="box_phi" id="box_phi"
                           value="<?= e(number_format((float) ($ss['box_phi'] ?? 0), 0, '', ',')) ?>">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label" for="ship_rounding">Quy tắc cân nặng</label>
                    <select class="form-select form-select-sm" name="ship_rounding" id="ship_rounding">
                        <option value="0" <?= (int) ($ss['ship_rounding'] ?? 0) === 0 ? 'selected' : '' ?>>Không làm tròn</option>
                        <option value="1" <?= (int) ($ss['ship_rounding'] ?? 0) === 1 ? 'selected' : '' ?>>Làm tròn lên 1kg</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Lưu
                    </button>
                </div>
            </form>
        <?php component('card_close'); ?>
        <?php endif; ?>
        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ghi_chu' => 'Ghi chú / vùng',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
            'add_url'    => admin_url(['p' => $MODULE, 'a' => 'add']),
            'add_label'  => 'Thêm mức phí',
            'filters'    => [
                [
                    'name'    => 'tinh',
                    'label'   => 'Tỉnh/TP',
                    'width'   => '200px',
                    'value'   => (string) $filter_tinh,
                    'options' => $tinhOpts,
                ],
            ],
        ]); ?>

        <?php component('card_open', ['title' => 'Danh sách mức phí', 'body' => 'none']); ?>

            <form id="form" method="post"
                  action="<?= e(admin_url(['p' => $MODULE, 'a' => 'delete_all'])) ?>">

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">
                                    <input type="checkbox" id="check_all" class="form-check-input" aria-label="Chọn tất cả">
                                </th>
                                <th class="text-center col-shrink">STT</th>
                                <th>Địa bàn</th>
                                <th class="text-end col-shrink">Phí ship</th>
                                <th class="text-end col-shrink">Phụ phí/kg</th>
                                <th class="col-shrink">Ghi chú</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 8,
                                    'message' => 'Chưa có mức phí vận chuyển',
                                    'hint'    => 'Thêm mức phí theo tỉnh / huyện / xã.',
                                    'icon'    => 'fa-solid fa-truck',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 14);
                                foreach ($items as $i => $row):
                                    $id = (int) $row['id'];
                                    $parts = array_filter([
                                        $row['ten_tinh'] ?? '',
                                        !empty($row['ten_huyen']) ? $row['ten_huyen'] : null,
                                        !empty($row['ten_xa']) ? $row['ten_xa'] : null,
                                    ]);
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => 'Chọn mức phí',
                                            ]); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php component('stt_input', [
                                                'table'    => $TABLE,
                                                'col'      => 'so_thu_tu',
                                                'id'       => $id,
                                                'value'    => $row['so_thu_tu'] ?? 0,
                                                'can_edit' => $canEdit,
                                            ]); ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e(implode(' · ', $parts) ?: '—') ?></div>
                                            <div class="small text-secondary">
                                                <?php if (empty($row['id_huyen'])): ?>
                                                    <span class="badge text-bg-info">Toàn tỉnh</span>
                                                <?php elseif (empty($row['id_xa'])): ?>
                                                    <span class="badge text-bg-secondary">Toàn huyện</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end text-danger fw-semibold text-nowrap">
                                            <?= number_format($row['phi_ship'] ?? 0) ?><sup>đ</sup>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <?= number_format($row['phi_extra_kg'] ?? 0) ?><sup>đ</sup>
                                        </td>
                                        <td class="small">
                                            <?php if ((float) ($row['free_ship_weight'] ?? 0) > 0): ?>
                                                <div class="text-success">
                                                    <i class="fa-solid fa-gift me-1"></i>
                                                    Free từ <?= e((string) $row['free_ship_weight']) ?><?= e($wUnit) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?= e((string) ($row['ghi_chu'] ?? '')) ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('switch_toggle', [
                                                'table'    => $TABLE,
                                                'col'      => 'hien_thi',
                                                'id'       => $id,
                                                'checked'  => ((int) ($row['hien_thi'] ?? 0) === 1),
                                                'disabled' => !$canEdit,
                                                'title'    => 'Bật / tắt mức phí',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('row_actions', [
                                                'edit_url'   => admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id]),
                                                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]),
                                                'name'       => implode(' · ', $parts) ?: ('mức phí #' . $id),
                                                'can_edit'   => $canEdit,
                                                'can_delete' => $canDelete,
                                            ]); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> mức phí
                        <?php if (!empty($per_page) && $per_page > 0): ?>
                            &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                            / <strong><?= (int) ($total_page ?? 1) ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php
                    $keepQuery = array_filter([
                        'key'  => ($currentSearch['key'] ?? '') !== '' ? $currentSearch['key'] : null,
                        'tinh' => $filter_tinh !== '' ? $filter_tinh : null,
                    ]);
                    component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($per_page ?? 14),
                        'current'     => (int) ($current_page ?? $page),
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'keep_params' => $keepQuery,
                    ]);
                    ?>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>

<script>
$(function () {
    $(document).on('keyup blur', '.js-money', function () {
        var v = String(this.value || '').replace(/[^\d]/g, '');
        if (v === '') { this.value = ''; return; }
        this.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    });

    // STT: #_ship dùng khóa id (không phải id_code) → update_data
    $(document).on('blur', '.a_stt[data-table="#_ship"]', function (e) {
        e.stopImmediatePropagation();
        var $el = $(this);
        $.post('./sources/ajax.php', {
            do: 'update_data',
            table: $el.data('table'),
            col: $el.data('col') || 'so_thu_tu',
            val: $el.val(),
            id: $el.data('id')
        }).done(function () {
            if (window.AdminUI && AdminUI.notify) AdminUI.notify.success('Đã lưu STT');
        });
    });
});
</script>
