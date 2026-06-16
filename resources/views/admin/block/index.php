<?php
$title = "Quản lý khối nội dung (Blocks)";
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý khối nội dung',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khối nội dung', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    
                    <!-- TRÁI: Hành động hàng loạt -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <?php if(hasPermission('admin.block', 'can_delete')): ?>
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.block.destroyMultiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                        <?php endif; ?>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.block.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <!-- Lọc theo Trạng thái -->
                        <select name="status" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm tên, alias..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || ($status ?? '') !== ''): ?>
                            <a href="<?= route('admin.block.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <?php if(hasPermission('admin.block', 'can_add')): ?>
                        <a href="<?= route('admin.block.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <!-- /HEADER -->
            
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input class="form-check-input check-all" type="checkbox" title="Chọn tất cả">
                                    </div>
                                </th>
                                <th style="width: 60px;" class="text-center">STT</th>
                                <th>Tên khối (Name)</th>
                                <th>Mã gọi (Alias)</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($items) && count($items) > 0): ?>
                                <?php foreach($items as $item): ?>
                                <tr class="wp-row">
                                    <th scope="row" class="text-center align-middle">
                                        <div class="form-check d-flex justify-content-center mb-0">
                                            <input class="form-check-input row-check" type="checkbox" value="<?= $item->id_code ?>">
                                        </div>
                                    </th>
                                    <td class="text-center align-middle">
                                        <?= $item->sort_order ?>
                                    </td>
                                    <td class="align-middle">
                                        <strong><a href="<?= route('admin.block.edit', ['id' => $item->id_code]) ?>" class="text-dark text-decoration-none">
                                            <?= htmlspecialchars($item->name ?: 'Khối nội dung ' . $item->id_code) ?>
                                        </a></strong>
                                        
                                        <!-- WP-Style Row Actions -->
                                        <?php 
                                        $actions = [];
                                        if(hasPermission('admin.block', 'can_edit')) {
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.block.edit', ['id' => $item->id_code]), 
                                                'class' => 'text-primary'
                                            ];
                                            $actions['items'] = [
                                                'label' => 'Quản lý Items', 
                                                'url' => route('admin.block_item.index', ['block_id' => $item->id_code]), 
                                                'class' => 'text-success fw-bold'
                                            ];
                                        }
                                        if(hasPermission('admin.block', 'can_delete')) {
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => route('admin.block.destroy', ['id' => $item->id_code]), 
                                                'class' => 'text-danger confirm-delete',
                                                'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa khối nội dung này?"'
                                            ];
                                        }
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($item->alias) ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-id="<?= $item->id_code ?>" data-field="is_active" 
                                                data-url="<?= route('admin.block.updateStatusAjax') ?>" 
                                                <?= $item->is_active ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                        Chưa có dữ liệu nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            
            <!-- FOOTER: PHÂN TRANG -->
            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($items ?? []) ?> / <?= $totalRows ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <ul class="pagination pagination-sm m-0 justify-content-end">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>&status=<?= urlencode($status ?? '') ?>">&laquo;</a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword ?? '') ?>&status=<?= urlencode($status ?? '') ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword ?? '') ?>&status=<?= urlencode($status ?? '') ?>">&raquo;</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check all
    const checkAll = document.querySelector('.check-all');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.check-item').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Delete selected
    const btnDeleteSelected = document.getElementById('delete-selected');
    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', function() {
            const checkedIds = Array.from(document.querySelectorAll('.check-item:checked')).map(cb => cb.value);
            if (checkedIds.length === 0) {
                AppNotify.warning('Vui lòng chọn ít nhất 1 dòng để xóa');
                return;
            }
            
            AppNotify.confirm('Bạn có chắc chắn muốn xóa ' + checkedIds.length + ' dòng đã chọn?', function() {
                const url = btnDeleteSelected.getAttribute('data-url');
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'ids=' + encodeURIComponent(JSON.stringify(checkedIds))
                })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        AppNotify.success(res.message);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        AppNotify.error(res.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    AppNotify.error('Có lỗi xảy ra');
                });
            });
        });
    }

    // Confirm delete
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            AppNotify.confirm('Bạn có chắc chắn muốn xóa mục này?', function() {
                window.location.href = href;
            });
        });
    });

    // Update status/stt via Ajax
    const updateAjax = (id, field, value) => {
        fetch('<?= route("admin.block.updateStatusAjax") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `id=${id}&field=${field}&value=${value}`
        })
        .then(res => res.json())
        .then(res => {
            if(res.success) {
                AppNotify.success('Cập nhật thành công');
            } else {
                AppNotify.error(res.message || 'Lỗi cập nhật');
            }
        });
    };

    document.querySelectorAll('.update-status').forEach(cb => {
        cb.addEventListener('change', function() {
            updateAjax(this.getAttribute('data-id'), this.getAttribute('data-field'), this.checked ? 1 : 0);
        });
    });

    document.querySelectorAll('.update-stt').forEach(input => {
        input.addEventListener('change', function() {
            updateAjax(this.getAttribute('data-id'), 'sort_order', this.value);
        });
    });
});
</script>
