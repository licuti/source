<?php 
$layout = 'admin.layouts.main'; 
$title = 'Bảng giá cước Vùng - ' . htmlspecialchars($method->name);
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình vận chuyển', 'url' => route('admin.shipping.index')],
        ['name' => 'Bảng giá cước', 'url' => '']
    ],
    'actions' => [
        ['label' => 'Thêm biểu phí', 'icon' => 'fa-plus', 'url' => route('admin.shipping.create_rate', $method->id), 'class' => 'btn-success btn-sm'],
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.shipping.index'), 'class' => 'btn-secondary btn-sm'],
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                    <form action="" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <a href="<?= route('admin.shipping.create_rate', $method->id) ?>" class="btn btn-success btn-sm">
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
                                <th>Khu vực / Tỉnh thành</th>
                                <th>Quận/Huyện</th>
                                <th>Phường/Xã</th>
                                <th class="text-end">Phí cơ bản</th>
                                <th class="text-end">Phụ thu (VNĐ/kg)</th>
                                <th class="text-end">Khối lượng miễn phí (Kg)</th>
                                <th width="100" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rates)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                    Chưa có bảng giá cước nào cho phương thức này.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($rates as $item): ?>
                                <tr class="wp-row">
                                    <td class="align-middle">
                                        <?php if ($item->country_code === '*'): ?>
                                            <span class="badge bg-dark">Toàn Cầu</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= $item->country_code ?></span>
                                            <span class="fw-bold ms-1 text-primary">
                                                <a href="<?= route('admin.shipping.edit_rate', [$method->id, $item->id]) ?>" class="text-decoration-none">
                                                    <?= $item->province_code ? htmlspecialchars($item->province_code) : 'Tất cả Tỉnh/Thành' ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>

                                        <?php 
                                        $actions = [
                                            'edit' => [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.shipping.edit_rate', [$method->id, $item->id]), 
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
                                    <td class="align-middle text-muted"><?= $item->district_code ? htmlspecialchars($item->district_code) : 'Tất cả Quận/Huyện' ?></td>
                                    <td class="align-middle text-muted"><?= $item->ward_code ? htmlspecialchars($item->ward_code) : 'Tất cả Phường/Xã' ?></td>
                                    <td class="text-end fw-bold text-success align-middle"><?= number_format($item->base_fee) ?> đ</td>
                                    <td class="text-end align-middle"><?= number_format($item->extra_fee_per_kg) ?> đ</td>
                                    <td class="text-end align-middle"><?= $item->free_weight_kg ?> kg</td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
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
