<?php
$breadcrumbActions = [];
$canAdd = hasPermission('admin.product', 'add');
$canDelete = hasPermission('admin.product', 'delete');
$canEdit = hasPermission('admin.product', 'edit');
$user = user();
$isAdmin = $user->is_admin == 1;

if ($canAdd) {
    $breadcrumbActions[] = ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.product.create'), 'class' => 'btn-primary'];
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Sản phẩm',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Sản phẩm', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow-sm">
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <!-- Left: Bulk actions -->
                    <?php if ($canDelete): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.product.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các sản phẩm đã chọn?">
                                Xóa
                            </option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>
                            Áp dụng
                        </button>
                    </div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>

                    <!-- Right: Search, Filter & Add New -->
                    <form action="<?= route('admin.product.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <select name="category_id" class="form-select form-select-sm w-auto">
                            <option value="0">Tất cả danh mục</option>
                            <?php renderCategoryFilter($categories ?? [], $category_id ?? 0); ?>
                        </select>

                        <select name="status" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>

                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <?php if (!empty($keyword) || ($status ?? '') !== '' || !empty($category_id)): ?>
                            <a href="<?= route('admin.product.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <?php if ($canAdd): ?>
                            <a href="<?= route('admin.product.create') ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-plus me-1"></i> Thêm mới
                            </a>
                        <?php endif; ?>
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
                                <th style="width: 100px;" class="text-center">Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th style="width: 150px;" class="text-center">Giá bán</th>
                                <th style="width: 100px;" class="text-center">Tồn kho</th>
                                <th style="width: 100px;" class="text-center">Nổi bật</th>
                                <th style="width: 120px;" class="text-center">Hiển thị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($items)): ?>
                                <?php foreach($items as $item): ?>
                                    <?php 
                                    $rowCanEdit = $canEdit;
                                    $rowCanDelete = $canDelete;
                                    ?>
                                    <tr class="wp-row">
                                        <th scope="row" class="text-center align-middle">
                                            <?php if ($rowCanDelete): ?>
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id_code ?>">
                                            </div>
                                            <?php endif; ?>
                                        </th>
                                        
                                        <!-- Hình ảnh -->
                                        <td class="text-center align-middle">
                                            <?php if ($item->thumbnail): ?>
                                                <img src="<?= getImageUrl($item->thumbnail) ?>" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">Trống</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Tiêu đề -->
                                        <td class="align-middle">
                                            <?php if ($rowCanEdit): ?>
                                                <strong><a href="<?= route('admin.product.edit', ['id' => $item->id_code]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->title) ?></a></strong>
                                            <?php else: ?>
                                                <strong><span class="text-dark"><?= htmlspecialchars($item->title) ?></span></strong>
                                            <?php endif; ?>
                                            
                                            <div class="text-muted small mt-1">
                                                SKU: <?= htmlspecialchars($item->sku) ?>
                                            </div>

                                            <?php
                                            $actions = [];
                                            if ($rowCanEdit) {
                                                $actions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.product.edit', ['id' => $item->id_code]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if ($rowCanDelete) {
                                                $actions['delete'] = [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.product.destroy', ['id' => $item->id_code]), 
                                                    'class' => 'text-danger', 
                                                    'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa sản phẩm này?\')"'
                                                ];
                                            }
                                            if (!empty($actions)) {
                                                echo view('admin.components.row_actions', ['actions' => $actions]);
                                            }
                                            ?>
                                        </td>
                                        
                                        <!-- Giá -->
                                        <td class="text-center align-middle text-danger fw-bold">
                                            <?php
                                            if ($item->product_type === 'variable' && !empty($item->variants)) {
                                                $prices = [];
                                                foreach ($item->variants as $v) {
                                                    $p = $v->promotional_price > 0 ? $v->promotional_price : $v->price;
                                                    if ($p > 0) $prices[] = $p;
                                                }
                                                if (count($prices) > 0) {
                                                    $minPrice = min($prices);
                                                    $maxPrice = max($prices);
                                                    if ($minPrice == $maxPrice) {
                                                        echo number_format($minPrice, 0, ',', '.') . 'đ';
                                                    } else {
                                                        echo number_format($minPrice, 0, ',', '.') . 'đ - ' . number_format($maxPrice, 0, ',', '.') . 'đ';
                                                    }
                                                } else {
                                                    echo 'Liên hệ';
                                                }
                                            } else {
                                                if ($item->promotional_price > 0): ?>
                                                    <?= number_format($item->promotional_price, 0, ',', '.') ?>đ
                                                    <br>
                                                    <del class="text-muted small fw-normal"><?= number_format($item->price, 0, ',', '.') ?>đ</del>
                                                <?php else: ?>
                                                    <?= $item->price > 0 ? number_format($item->price, 0, ',', '.') . 'đ' : 'Liên hệ' ?>
                                                <?php endif;
                                            }
                                            ?>
                                        </td>

                                        <!-- Tồn kho -->
                                        <td class="text-center align-middle">
                                            <?= $item->stock_quantity ?>
                                        </td>
                                        
                                        <!-- is_featured -->
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <?php if ($rowCanEdit): ?>
                                                    <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id_code ?>" data-field="is_featured" data-url="<?= route('admin.product.updateStatusAjax') ?>" <?= $item->is_featured ? 'checked' : '' ?>>
                                                <?php else: ?>
                                                    <input class="form-check-input" type="checkbox" <?= $item->is_featured ? 'checked' : '' ?> disabled>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- hien_thi -->
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <?php if ($rowCanEdit): ?>
                                                    <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id_code ?>" data-field="status" data-url="<?= route('admin.product.updateStatusAjax') ?>" <?= $item->status ? 'checked' : '' ?>>
                                                <?php else: ?>
                                                    <input class="form-check-input" type="checkbox" <?= $item->status ? 'checked' : '' ?> disabled>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-box-open fs-1 mb-2"></i><br>
                                        Chưa có sản phẩm nào được tìm thấy.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER: PHÂN TRANG -->
            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($items) ?> / <?= $items->total() ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= $items->links() ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->

        </div>
    </div>
</div>
