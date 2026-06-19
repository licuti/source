<?php
$title = "Quản lý Gian hàng (Shops)";
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Gian hàng',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Gian hàng', 'url' => '']
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
                        <?php if(hasPermission('admin.shop', 'can_delete')): ?>
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.shop.destroyMultiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                        <?php endif; ?>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.shop.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <!-- Lọc theo Trạng thái -->
                        <select name="status" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
                            <option value="2" <?= ($status ?? '') === '2' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Bị khóa</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm tên, sđt..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || ($status ?? '') !== ''): ?>
                            <a href="<?= route('admin.shop.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <?php if(hasPermission('admin.shop', 'can_add')): ?>
                        <a href="<?= route('admin.shop.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <!-- /HEADER -->

            <!-- BODY: Bảng dữ liệu -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 wp-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="checkAll" class="form-check-input">
                                </th>
                                <th class="text-center" style="width: 70px;">ID</th>
                                <th style="width: 100px;">Logo</th>
                                <th>Thông tin Gian hàng</th>
                                <th>Liên hệ</th>
                                <th class="text-center" style="width: 120px;">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($items) && count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="wp-row">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input check-item" value="<?= $item->id_code ?>">
                                        </td>
                                        <td class="text-center"><?= $item->id_code ?></td>
                                        <td>
                                            <?php if ($item->logo): ?>
                                                <img src="<?= (defined('URLPATH') ? URLPATH : '') . 'img_data/images/' . $item->logo ?>" alt="Logo" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center text-muted img-thumbnail" style="width: 60px; height: 60px;">
                                                    <i class="fas fa-store"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><a href="<?= route('admin.shop.edit', ['id' => $item->id_code]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->name) ?></a></strong><br>
                                            <small class="text-muted"><i class="fas fa-link"></i> /shop/<?= $item->slug ?></small>
                                            <?php 
                                            $actions = [];
                                            if(hasPermission('admin.shop', 'can_edit')) {
                                                $actions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.shop.edit', ['id' => $item->id_code]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if(hasPermission('admin.shop', 'can_delete')) {
                                                $actions['delete'] = [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.shop.destroy', ['id' => $item->id_code]), 
                                                    'class' => 'text-danger confirm-delete',
                                                    'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa gian hàng này?"'
                                                ];
                                            }
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($item->phone): ?><div style="font-size: 13px;"><i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($item->phone) ?></div><?php endif; ?>
                                            <?php if($item->email): ?><div style="font-size: 13px;"><i class="fas fa-envelope me-1 text-muted"></i> <?= htmlspecialchars($item->email) ?></div><?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if($item->status == 1): ?>
                                                <span class="badge bg-success">Hoạt động</span>
                                            <?php elseif($item->status == 2): ?>
                                                <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Bị khóa</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                        <p>Không có gian hàng nào được tìm thấy.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if (isset($items) && method_exists($items, 'links') && $items->lastPage() > 1): ?>
            <div class="card-footer clearfix">
                <?= $items->links('admin.components.pagination') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
