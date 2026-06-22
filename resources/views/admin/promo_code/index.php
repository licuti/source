<?php
$title = $title ?? 'Quản lý Mã Giảm Giá';
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
                    <div id="bulkActionPanel" class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.promo_code.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.promo_code.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <!-- Lọc theo Trạng thái -->
                        <select name="hien_thi" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($hien_thi ?? '') === '1' ? 'selected' : '' ?>>Kích hoạt</option>
                            <option value="0" <?= ($hien_thi ?? '') === '0' ? 'selected' : '' ?>>Đã tắt</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Mã hoặc tên..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || ($hien_thi !== null && $hien_thi !== '')): ?>
                            <a href="<?= route('admin.promo_code.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <a href="<?= route('admin.promo_code.create') ?>" class="btn btn-success btn-sm">
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
                                <th>Tên & Mã</th>
                                <th class="text-end">Mức giảm</th>
                                <th class="text-end">Đơn tối thiểu</th>
                                <th class="text-center">Thời gian</th>
                                <th class="text-center">Lượt dùng</th>
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
                                            <strong><a href="<?= route('admin.promo_code.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->name) ?></a></strong>
                                            <div class="small text-muted mt-1">
                                                <span class="badge bg-light text-dark border"><i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars($item->code) ?></span>
                                            </div>
                                            
                                            <?php 
                                            $actions = [
                                                'edit' => [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.promo_code.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ],
                                                'delete' => [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.promo_code.destroy', ['id' => $item->id]), 
                                                    'class' => 'text-danger confirm-delete',
                                                    'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa mã giảm giá này?"'
                                                ]
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $actions]);
                                            ?>
                                        </td>
                                        <td class="text-end align-middle fw-bold text-danger">
                                            <?php if ($item->discount_type == 1): ?>
                                                <?= number_format($item->discount_value, 2) ?>%
                                                <?php if ($item->max_discount_amount > 0): ?>
                                                    <div class="small text-muted fw-normal">Tối đa: <?= number_format($item->max_discount_amount) ?>đ</div>
                                                <?php endif; ?>
                                            <?php elseif ($item->discount_type == 2): ?>
                                                <?= number_format($item->discount_value) ?>đ
                                            <?php elseif ($item->discount_type == 3): ?>
                                                Miễn phí vận chuyển
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end align-middle">
                                            <?= $item->min_order_amount > 0 ? number_format($item->min_order_amount) . 'đ' : '<span class="text-muted">Không</span>' ?>
                                        </td>
                                        <td class="text-center align-middle small">
                                            <div class="text-success"><i class="fa-solid fa-calendar-check me-1"></i><?= date('d/m/Y H:i', strtotime($item->start_date)) ?></div>
                                            <div class="text-danger mt-1"><i class="fa-solid fa-calendar-times me-1"></i><?= date('d/m/Y H:i', strtotime($item->end_date)) ?></div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-secondary"><?= $item->usage_limit == 0 ? 'Không giới hạn' : '0 / ' . $item->usage_limit ?></span>
                                            <div class="small text-muted mt-1"><?= $item->usage_per_user == 0 ? 'Không giới hạn/user' : $item->usage_per_user . '/user' ?></div>
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                    data-id="<?= $item->id ?>" 
                                                    data-field="is_active"
                                                    data-url="<?= route('admin.promo_code.updateStatusAjax') ?>"
                                                    <?= $item->is_active ? 'checked' : '' ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Không tìm thấy mã giảm giá nào!
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
