<?php
$title = $title ?? 'Quản lý Khách hàng';
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khách hàng & CRM', 'url' => '#'],
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
                    <div id="bulkActionPanel" class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.customer.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa khách hàng đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.customer.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <!-- Lọc theo Trạng thái -->
                        <select name="hien_thi" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($hien_thi ?? '') === '1' ? 'selected' : '' ?>>Đang hoạt động</option>
                            <option value="0" <?= ($hien_thi ?? '') === '0' ? 'selected' : '' ?>>Đã khóa</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tên, Email, SĐT, Mã KH..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || ($hien_thi !== null && $hien_thi !== '')): ?>
                            <a href="<?= route('admin.customer.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <a href="<?= route('admin.customer.create') ?>" class="btn btn-success btn-sm">
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
                                <th width="60">Avatar</th>
                                <th>Thông tin khách hàng</th>
                                <th>Liên hệ</th>
                                <th class="text-center">Ngày tạo</th>
                                <th width="120" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items) && count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="wp-row">
                                        <th scope="row" class="text-center align-middle">
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
                                            </div>
                                        </th>
                                        <td class="align-middle">
                                            <?php if (!empty($item->avatar)): ?>
                                                <img src="<?= getImageUrl($item->avatar) ?>" class="rounded-circle border" width="40" height="40" alt="Avatar">
                                            <?php else: ?>
                                                <div class="rounded-circle border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 40px; height: 40px;">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <strong><a href="<?= route('admin.customer.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->fullname) ?></a></strong>
                                            <div class="small text-muted mt-1">
                                                <span class="badge bg-light text-dark border me-1"><i class="fa-solid fa-id-card me-1"></i><?= htmlspecialchars($item->code) ?></span>
                                                <?php if ($item->gender == 1): ?>
                                                    <i class="fa-solid fa-mars text-primary" title="Nam"></i>
                                                <?php elseif ($item->gender == 0): ?>
                                                    <i class="fa-solid fa-venus text-danger" title="Nữ"></i>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php 
                                            $actions = [
                                                'edit' => [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.customer.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ],
                                                'delete' => [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.customer.destroy', ['id' => $item->id]), 
                                                    'class' => 'text-danger confirm-delete',
                                                    'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa khách hàng này?"'
                                                ]
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        <td class="align-middle">
                                            <div><i class="fa-solid fa-envelope text-muted me-2"></i><?= htmlspecialchars($item->email) ?></div>
                                            <?php if (!empty($item->phone)): ?>
                                                <div class="mt-1"><i class="fa-solid fa-phone text-muted me-2"></i><?= htmlspecialchars($item->phone) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle small text-muted">
                                            <?= date('d/m/Y H:i', strtotime($item->created_at)) ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                    data-id="<?= $item->id ?>" 
                                                    data-field="status"
                                                    data-url="<?= route('admin.customer.updateStatusAjax') ?>"
                                                    <?= $item->status ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Không tìm thấy khách hàng nào!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FOOTER: Pagination -->
            <?php if (isset($items) && method_exists($items, 'links')): ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Hiển thị <b><?= count($items) ?></b> / <b><?= $items->total() ?></b> mục
                        </div>
                        <div class="pagination-wrapper mb-0">
                            <?= $items->links() ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
