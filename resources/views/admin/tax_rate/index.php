<?php
$title = $title ?? 'Quản lý Biểu Phí Thuế';
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thương mại điện tử', 'url' => '#'],
        ['name' => $title, 'url' => '']
    ],
    'actions' => []
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            
            <!-- HEADER: Toolbar -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    
                    <!-- TRÁI: Hành động hàng loạt -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.tax_rate.destroy', ['id' => '0']) ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.tax_rate.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <!-- Nút Thêm mới -->
                        <a href="<?= route('admin.tax_rate.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm biểu phí mới
                        </a>
                    </form>
                </div>
            </div>
            <!-- /HEADER -->

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
                                <th width="60" class="text-center">ID</th>
                                <th>Tên Biểu Phí</th>
                                <th>Nhóm Thuế</th>
                                <th width="150" class="text-end">Mức Thuế (%)</th>
                                <th width="120" class="text-center">Thuế Kép</th>
                                <th width="100" class="text-center">Ưu tiên</th>
                                <th width="120" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="wp-row">
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input check-item" type="checkbox" value="<?= $item->id ?>">
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $item->id ?></td>
                                        <td class="align-middle">
                                            <a href="<?= route('admin.tax_rate.edit', ['id' => $item->id]) ?>" class="fw-medium text-dark text-decoration-none">
                                                <?= htmlspecialchars($item->name) ?>
                                            </a>
                                            <?php if ($item->shop_id > 0): ?>
                                                <div class="small text-muted fw-normal"><i class="fa-solid fa-store me-1"></i> Shop ID: <?= $item->shop_id ?></div>
                                            <?php else: ?>
                                                <div class="small text-muted fw-normal"><i class="fa-solid fa-globe me-1"></i> Hệ thống chung</div>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $actions = [];
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.tax_rate.edit', ['id' => $item->id]), 
                                                'class' => 'text-primary'
                                            ];
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => '#', 
                                                'class' => 'text-danger btn-delete',
                                                'attributes' => 'data-id="'.$item->id.'"'
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($classMap[$item->tax_class_id] ?? 'Unknown') ?></span>
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            <?= number_format($item->rate, 4) ?>%
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input status-toggle" type="checkbox" 
                                                    data-id="<?= $item->id ?>" 
                                                    data-field="is_compound"
                                                    <?= $item->is_compound ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $item->priority ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input status-toggle" type="checkbox" 
                                                    data-id="<?= $item->id ?>" 
                                                    data-field="is_active"
                                                    <?= $item->is_active ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Chưa có dữ liệu biểu phí.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FOOTER: Pagination (nếu có) -->
            <?php if (isset($pagination)): ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Hiển thị <b><?= count($items) ?></b> mục
                        </div>
                        <div class="pagination-wrapper mb-0">
                            <?= $pagination ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Checkbox All
    const checkAll = document.querySelector('.check-all');
    const checkItems = document.querySelectorAll('.check-item');
    const btnBulkApply = document.getElementById('btnBulkApply');
    
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            checkItems.forEach(item => item.checked = this.checked);
            updateBulkButton();
        });
        
        checkItems.forEach(item => {
            item.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.check-item:checked').length === checkItems.length;
                checkAll.checked = allChecked;
                updateBulkButton();
            });
        });
    }

    function updateBulkButton() {
        const hasChecked = document.querySelectorAll('.check-item:checked').length > 0;
        const hasAction = document.getElementById('bulkActionSelect').value !== '';
        btnBulkApply.disabled = !(hasChecked && hasAction);
    }
    
    document.getElementById('bulkActionSelect')?.addEventListener('change', updateBulkButton);

    // Toggle Status / Compound
    document.querySelectorAll('.status-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const id = this.dataset.id;
            const field = this.dataset.field;
            const value = this.checked ? 1 : 0;
            
            fetch('<?= route('admin.tax_rate.updateStatusAjax') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&field=${field}&value=${value}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    AppNotify.success(data.message);
                } else {
                    AppNotify.error(data.message);
                    this.checked = !this.checked;
                }
            })
            .catch(error => {
                AppNotify.error('Có lỗi xảy ra!');
                this.checked = !this.checked;
            });
        });
    });

    // Delete Item
    document.querySelectorAll('.btn-delete').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('Bạn có chắc chắn muốn xóa biểu phí thuế này?')) {
                const id = this.dataset.id;
                fetch(`<?= route('admin.tax_rate.destroy', ['id' => '']) ?>${id}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        AppNotify.error(data.message);
                    }
                })
                .catch(error => AppNotify.error('Có lỗi xảy ra!'));
            }
        });
    });
});
</script>
