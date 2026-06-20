<?php 
$layout = 'admin.layouts.main'; 
$title = 'Cấu hình Hãng / Phương thức Vận chuyển';
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình vận chuyển', 'url' => '']
    ],
    'actions' => [
        ['label' => 'Thêm Phương thức', 'icon' => 'fa-plus', 'url' => route('admin.shipping.create_method'), 'class' => 'btn-success btn-sm'],
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.shipping.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <a href="<?= route('admin.shipping.create_method') ?>" class="btn btn-success btn-sm">
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
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Tên Phương Thức</th>
                                <th>Mã Hãng (Carrier)</th>
                                <th style="width: 150px;" class="text-center">Loại phí</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($methods)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                    Chưa có phương thức vận chuyển nào.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($methods as $item): ?>
                                <tr class="wp-row">
                                    <td class="text-center text-muted fw-bold align-middle"><?= $item->sort_order ?></td>
                                    <td class="align-middle">
                                        <strong><a href="<?= route('admin.shipping.edit_method', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->name) ?></a></strong>
                                        
                                        <?php 
                                        $actions = [];
                                        if (!$item->is_api) {
                                            $actions['rates'] = [
                                                'label' => 'Bảng giá',
                                                'url' => route('admin.shipping.rates', ['methodId' => $item->id]),
                                                'class' => 'text-info'
                                            ];
                                        }
                                        $actions['edit'] = [
                                            'label' => 'Chỉnh sửa', 
                                            'url' => route('admin.shipping.edit_method', ['id' => $item->id]), 
                                            'class' => 'text-primary'
                                        ];
                                        $actions['delete'] = [
                                            'label' => 'Xóa', 
                                            'url' => '#', 
                                            'class' => 'text-danger btn-delete-method',
                                            'attributes' => 'data-id="'.$item->id.'"'
                                        ];
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="align-middle"><code><?= htmlspecialchars($item->carrier_code) ?></code></td>
                                    <td class="text-center align-middle">
                                        <?php if ($item->is_api): ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-cloud"></i> Phí qua API</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-table"></i> Bảng giá Vùng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-url="<?= route('admin.shipping.updateStatusAjax') ?>"
                                                data-id="<?= $item->id ?>" data-table="db_shipping_methods" data-field="is_active"
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
    $('.btn-delete-method').click(function(e) {
        e.preventDefault();
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

    // Common JS now handles the AJAX toggle automatically because we added data-url!
});
</script>
