<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Thuộc tính',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thuộc tính', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm border-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Danh sách <span class="badge bg-secondary ms-1"><?= count($attributes ?? []) ?></span></h3>
                <div class="card-tools d-flex gap-2">
                    <form action="<?= route('admin.attribute.index') ?>" method="GET" class="d-inline-block m-0">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="keyword" class="form-control float-right" placeholder="Tìm kiếm thuộc tính..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                    </form>
                    <?php if (hasPermission('admin.attribute', 'add')): ?>
                    <a href="<?= route('admin.attribute.create') ?>" class="btn btn-sm btn-success"><i class="fa-solid fa-plus"></i> Thêm mới</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-0">
                <!-- Thanh Bulk Action (ẩn mặc định) -->
                <?php if (hasPermission('admin.attribute', 'delete')): ?>
                <div id="bulkActionPanel" class="px-3 py-2 bg-light border-bottom d-none">
                    <span class="fw-bold me-2"><span id="selectedCount">0</span> mục đã chọn:</span>
                    <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete">
                        <i class="fa-solid fa-trash"></i> Xóa đã chọn
                    </button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll">
                                </th>

                                <th>Tên thuộc tính (VI)</th>
                                <th>Kiểu hiển thị</th>
                                <th>Giá trị (Values)</th>
                                <th class="text-center">Sắp xếp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($attributes)): ?>
                                <?php foreach($attributes as $item): ?>
                                <tr class="wp-row">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input row-check" value="<?= $item->id_code ?>">
                                    </td>

                                    <td>
                                        <strong><?= htmlspecialchars($item->ten) ?></strong>
                                        <?php
                                        $actions = [];
                                        if (hasPermission('admin.attribute', 'edit')) {
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.attribute.edit', ['id' => $item->id_code]), 
                                                'class' => 'text-primary'
                                            ];
                                        }
                                        if (hasPermission('admin.attribute', 'delete')) {
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => route('admin.attribute.destroy', ['id' => $item->id_code]), 
                                                'class' => 'text-danger btn-delete',
                                                'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa Thuộc tính này cùng TOÀN BỘ GIÁ TRỊ của nó không?\')"'
                                            ];
                                        }
                                        if (!empty($actions)) {
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($item->loai)) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark me-2"><?= $item->value_count ?> giá trị</span>
                                        <small class="text-muted"><?= htmlspecialchars($item->values_preview) ?><?= $item->value_count > 5 ? '...' : '' ?></small>
                                    </td>
                                    <td class="text-center"><?= $item->sap_xep ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-tags fs-1 d-block mb-2"></i>
                                        Chưa có thuộc tính nào được tạo.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card-footer clearfix">
                <div class="row align-items-center">
                    <div class="col-md-6 text-muted small">
                        Hiển thị <?= count($attributes ?? []) ?> bản ghi
                    </div>
                    <div class="col-md-6">
                        <!-- Pagination here when backend supports it -->
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
