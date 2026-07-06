<?php
$breadcrumbActions = [
    ['label' => 'Thêm Album', 'icon' => 'fa-plus', 'url' => route('admin.gallery.create'), 'class' => 'btn-primary']
];
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Thư viện ảnh (Gallery)',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thư viện ảnh', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>
                            Áp dụng
                        </button>
                    </div>

                    <form action="<?= route('admin.gallery.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm album..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <?php if (!empty($keyword)): ?>
                            <a href="<?= route('admin.gallery.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <a href="<?= route('admin.gallery.create') ?>" class="btn btn-success btn-sm">
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
                                <th style="width: 40px;" class="text-center">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input class="form-check-input check-all" type="checkbox" title="Chọn tất cả">
                                    </div>
                                </th>
                                <th style="width: 100px;" class="text-center">Hình bìa</th>
                                <th>Tên Album</th>
                                <th style="width: 120px;" class="text-center">Số lượng ảnh</th>
                                <th style="width: 100px;" class="text-center">Sắp xếp</th>
                                <th style="width: 120px;" class="text-center">Hiển thị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($albums->items())): ?>
                                <?php foreach($albums as $item): ?>
                                    <tr class="wp-row" id="row-<?= $item->id ?>">
                                        <th scope="row" class="text-center align-middle">
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
                                            </div>
                                        </th>
                                        
                                        <td class="text-center align-middle">
                                            <?php if ($item->image): ?>
                                                <img src="<?= url('upload/album/' . $item->image) ?>" alt="Cover" class="img-thumbnail" style="height: 45px; width: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">Trống</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="align-middle">
                                            <strong><a href="<?= route('admin.gallery.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->title['vi'] ?? '') ?></a></strong>
                                            
                                            <?php
                                            $actions = [];
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.gallery.edit', ['id' => $item->id]), 
                                                'class' => 'text-primary'
                                            ];
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => 'javascript:void(0)', 
                                                'class' => 'text-danger btn-delete', 
                                                'attributes' => 'data-id="' . $item->id . '"'
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        
                                        <td class="text-center align-middle">
                                            <span class="badge bg-info text-dark">
                                                <?= count($item->gallery) ?> ảnh
                                            </span>
                                        </td>
                                        
                                        <td class="text-center align-middle">
                                            <?= $item->sort_order ?>
                                        </td>
                                        
                                        <td class="text-center align-middle">
                                            <?= view('admin.components.switch', [
                                                'name' => 'status_' . $item->id,
                                                'checked' => $item->status == 1,
                                                'table' => 'db_galleries',
                                                'id' => $item->id,
                                                'field' => 'status'
                                            ]) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Chưa có album nào được tạo.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($albums->lastPage() > 1): ?>
            <div class="card-footer bg-white">
                <?= $albums->links() ?>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<script>
$(document).ready(function() {
    $('.check-all').change(function() {
        $('.row-check').prop('checked', $(this).prop('checked'));
        toggleBulkApplyButton();
    });

    $('.row-check').change(function() {
        toggleBulkApplyButton();
        if ($('.row-check:checked').length === $('.row-check').length) {
            $('.check-all').prop('checked', true);
        } else {
            $('.check-all').prop('checked', false);
        }
    });

    function toggleBulkApplyButton() {
        if ($('.row-check:checked').length > 0) {
            $('#btnBulkApply').prop('disabled', false).removeClass('btn-outline-secondary').addClass('btn-primary');
        } else {
            $('#btnBulkApply').prop('disabled', true).removeClass('btn-primary').addClass('btn-outline-secondary');
        }
    }
    
    $('.btn-delete').click(function() {
        let id = $(this).data('id');
        let row = $('#row-' + id);
        
        if(confirm('Bạn có chắc chắn muốn xóa album này và toàn bộ ảnh bên trong?')) {
            $.ajax({
                url: '<?= route('admin.gallery.destroy_ajax') ?>',
                type: 'POST',
                data: { id: id },
                success: function(res) {
                    if (res.success) {
                        row.fadeOut(300, function() { $(this).remove(); });
                        Toast.fire({ icon: 'success', title: res.message });
                    } else {
                        Toast.fire({ icon: 'error', title: res.message });
                    }
                }
            });
        }
    });
});
</script>
