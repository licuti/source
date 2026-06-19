<?php $layout = 'admin.layouts.main'; ?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Cấu hình Hãng / Phương thức Vận chuyển</h1>
        <a href="<?= route('admin.shipping.create_method') ?>" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Thêm Phương thức
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Tên Phương Thức</th>
                            <th>Mã Hãng (Carrier)</th>
                            <th width="150" class="text-center">Loại phí</th>
                            <th width="120" class="text-center">Trạng thái</th>
                            <th width="200" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($methods)): ?>
                        <tr><td colspan="6" class="text-center">Chưa có phương thức vận chuyển nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($methods as $item): ?>
                            <tr>
                                <td><?= $item->sort_order ?></td>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($item->name) ?></td>
                                <td><code><?= htmlspecialchars($item->carrier_code) ?></code></td>
                                <td class="text-center">
                                    <?php if ($item->is_api): ?>
                                        <span class="badge bg-info"><i class="fas fa-cloud"></i> Phí qua API</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="fas fa-table"></i> Bảng giá Vùng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input toggle-status" type="checkbox" 
                                            data-id="<?= $item->id ?>" data-table="db_shipping_methods" data-field="is_active"
                                            <?= $item->is_active ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if (!$item->is_api): ?>
                                    <a href="<?= route('admin.shipping.rates', $item->id) ?>" class="btn btn-sm btn-info" title="Bảng giá">
                                        <i class="fas fa-list-ol"></i> Bảng giá
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= route('admin.shipping.edit_method', $item->id) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-danger btn-delete-method" data-id="<?= $item->id ?>">
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
    $('.btn-delete-method').click(function() {
        let id = $(this).data('id');
        AppNotify.confirm('Bạn có chắc muốn xóa phương thức vận chuyển này và toàn bộ bảng giá của nó?', function() {
            $.post('<?= route('admin.shipping.destroy_method') ?>', { id: id, _token: '<?= csrf_token() ?>' }, function(res) {
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
