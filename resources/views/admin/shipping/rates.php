<?php $layout = 'admin.layouts.main'; ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Bảng giá cước Vùng</h1>
            <p class="text-muted mt-1 mb-0">Phương thức: <strong class="text-primary"><?= htmlspecialchars($method->name) ?></strong></p>
        </div>
        <div>
            <a href="<?= route('admin.shipping.index') ?>" class="btn btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Trở về
            </a>
            <a href="<?= route('admin.shipping.create_rate', $method->id) ?>" class="btn btn-primary shadow-sm ms-2">
                <i class="fas fa-plus fa-sm text-white-50"></i> Thêm biểu phí
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Khu vực / Tỉnh thành</th>
                            <th>Quận/Huyện</th>
                            <th>Phường/Xã</th>
                            <th class="text-end">Phí cơ bản</th>
                            <th class="text-end">Phụ thu (VNĐ/kg)</th>
                            <th class="text-end">Khối lượng miễn phí (Kg)</th>
                            <th width="100" class="text-center">Trạng thái</th>
                            <th width="120" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rates)): ?>
                        <tr><td colspan="8" class="text-center">Chưa có bảng giá cước nào cho phương thức này.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rates as $item): ?>
                            <tr>
                                <td>
                                    <?php if ($item->country_code === '*'): ?>
                                        <span class="badge bg-dark">Toàn Cầu</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= $item->country_code ?></span>
                                        <span class="fw-bold ms-1"><?= $item->province_code ? htmlspecialchars($item->province_code) : 'Tất cả Tỉnh/Thành' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $item->district_code ? htmlspecialchars($item->district_code) : 'Tất cả Quận/Huyện' ?></td>
                                <td><?= $item->ward_code ? htmlspecialchars($item->ward_code) : 'Tất cả Phường/Xã' ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($item->base_fee) ?> đ</td>
                                <td class="text-end"><?= number_format($item->extra_fee_per_kg) ?> đ</td>
                                <td class="text-end"><?= $item->free_weight_kg ?> kg</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input toggle-status" type="checkbox" 
                                            data-id="<?= $item->id ?>" data-table="db_shipping_rates" data-field="is_active"
                                            <?= $item->is_active ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="<?= route('admin.shipping.edit_rate', [$method->id, $item->id]) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-delete-rate" data-id="<?= $item->id ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('.btn-delete-rate').click(function() {
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
