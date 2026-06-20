<?php 
$layout = 'admin.layouts.main'; 
$title = 'Bảng giá cước Vùng - ' . htmlspecialchars($method->name);
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình vận chuyển', 'url' => route('admin.shipping.index')],
        ['name' => 'Bảng giá ('.$method->name.')', 'url' => '']
    ],
    'actions' => [
        ['label' => 'Thêm Cước phí', 'icon' => 'fa-plus', 'url' => route('admin.shipping.create_rate', ['methodId' => $method->id]), 'class' => 'btn-success btn-sm'],
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.shipping.index'), 'class' => 'btn-secondary btn-sm'],
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                    <form action="" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <a href="<?= route('admin.shipping.create_rate', ['methodId' => $method->id]) ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
                        </a>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Khu vực áp dụng</th>
                                <th>Cấu hình Phí</th>
                                <th>Cài đặt chung</th>
                                <th width="100" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rates)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                    Chưa có bảng giá cước nào cho phương thức này.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($rates as $item): ?>
                                <tr class="wp-row">
                                    <td class="align-middle">
                                        <?php if ($item->country_code === '*'): ?>
                                            <span class="badge bg-dark mb-1">Toàn Cầu</span><br>
                                            <a href="<?= route('admin.shipping.edit_rate', ['methodId' => $method->id, 'rateId' => $item->id]) ?>" class="text-decoration-none fw-bold text-primary">Tất cả (Khác)</a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mb-1"><?= htmlspecialchars($countries[$item->country_code] ?? $item->country_code) ?></span><br>
                                            <a href="<?= route('admin.shipping.edit_rate', ['methodId' => $method->id, 'rateId' => $item->id]) ?>" class="text-decoration-none fw-bold text-primary">
                                                <?php if ($item->country_code === 'VN'): ?>
                                                    <?php
                                                        $locs = [];
                                                        $locs[] = $item->province_code ? htmlspecialchars($item->province->ten ?? $item->province_code) : 'Toàn Quốc';
                                                        if ($item->province_code && $item->district_code) {
                                                            $locs[] = htmlspecialchars($item->district->ten ?? $item->district_code);
                                                        }
                                                        if ($item->district_code && $item->ward_code) {
                                                            $locs[] = htmlspecialchars($item->ward->ten ?? $item->ward_code);
                                                        }
                                                        echo implode(' › ', $locs);
                                                    ?>
                                                <?php else: ?>
                                                    Toàn lãnh thổ
                                                <?php endif; ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $actions = [
                                            'edit' => [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.shipping.edit_rate', ['methodId' => $method->id, 'rateId' => $item->id]), 
                                                'class' => 'text-primary'
                                            ],
                                            'delete' => [
                                                'label' => 'Xóa', 
                                                'url' => '#', 
                                                'class' => 'text-danger btn-delete-rate',
                                                'attributes' => 'data-id="'.$item->id.'"'
                                            ]
                                        ];
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="mb-1">
                                            <span class="text-muted small">Cơ bản:</span> 
                                            <strong class="text-success"><?= number_format($item->base_fee) ?> đ</strong>
                                        </div>
                                        <?php if ($item->extra_fee_per_kg > 0): ?>
                                        <div class="mb-1">
                                            <span class="text-muted small">Phụ thu:</span> 
                                            <strong>+<?= number_format($item->extra_fee_per_kg) ?> đ/kg</strong>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($item->free_weight_kg > 0): ?>
                                        <div>
                                            <span class="badge bg-light text-dark border border-secondary"><i class="fas fa-gift text-danger"></i> Miễn phí <?= $item->free_weight_kg ?>kg đầu</span>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="mb-1">
                                            <span class="text-muted small"><i class="far fa-clock"></i> Thời gian:</span> 
                                            <strong><?= htmlspecialchars($item->estimated_time ?? '-') ?></strong>
                                        </div>
                                        <div>
                                            <span class="text-muted small"><i class="fas fa-sort-amount-up"></i> Ưu tiên:</span> 
                                            <span class="badge bg-secondary"><?= $item->priority ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-url="<?= route('admin.shipping.updateRateStatusAjax') ?>"
                                                data-id="<?= $item->id ?>" data-table="db_shipping_rates" data-field="is_active"
                                                <?= $item->is_active ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.btn-delete-rate').click(function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        AppNotify.confirm('Bạn có chắc muốn xóa biểu phí cước vận chuyển này?', function() {
            $.post('<?= route('admin.shipping.destroy_rate') ?>', { id: id, _token: '<?= csrf_token() ?>' }, function(res) {
                if (res.success) {
                    AppNotify.success(res.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    AppNotify.error(res.message);
                }
            });
        });
    });
});
</script>
