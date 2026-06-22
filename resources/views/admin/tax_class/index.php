<?php
$title = $title ?? 'Quản lý Nhóm Thuế';
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
                            <option value="delete" data-url="<?= route('admin.tax_class.destroy', ['id' => '0']) ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.tax_class.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <!-- Nút Thêm mới -->
                        <a href="<?= route('admin.tax_class.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
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
                                <th>Tên Nhóm Thuế</th>
                                <th width="150" class="text-center">Trạng thái</th>
                                <th width="150" class="text-center">Mặc định</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="wp-row">
                                        <td class="text-center">
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
                                            </div>
                                        </td>
                                        <td class="text-center"><?= $item->id ?></td>
                                        <td>
                                            <strong><a href="<?= route('admin.tax_class.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->name) ?></a></strong>
                                            
                                            <?php 
                                            $actions = [
                                                'edit' => [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.tax_class.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ],
                                                'delete' => [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.tax_class.destroy', ['id' => $item->id]), 
                                                    'class' => 'text-danger confirm-delete',
                                                    'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa nhóm thuế này? Tất cả biểu phí thuộc nhóm này sẽ bị mồ côi."'
                                                ]
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                    data-id="<?= $item->id ?>" 
                                                    data-field="is_active"
                                                    data-url="<?= route('admin.tax_class.updateStatusAjax') ?>"
                                                    <?= $item->is_active ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($item->is_default): ?>
                                                <span class="badge bg-success">Mặc định</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Không</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Không tìm thấy dữ liệu!
                                    </td>
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
