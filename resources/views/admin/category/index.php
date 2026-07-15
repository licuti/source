
<?php
$breadcrumbActions = [];
if (hasPermission('admin.category', 'add')) {
    $breadcrumbActions[] = ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.category.create'), 'class' => 'btn-primary'];
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Danh mục',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Danh mục', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow-sm">
            <!-- HEADER: Gộp Bulk Action, Filter, và Search theo chuẩn WordPress -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <?php if (hasPermission('admin.category', 'delete')): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.category.destroy_multiple') ?>" data-confirm="Lưu ý: Xóa danh mục cha sẽ XÓA LUÔN các danh mục con bên trong nó. Bạn có chắc chắn muốn tiếp tục?">
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

                    <!-- Right: Search & Filter -->
                    <form action="<?= route('admin.category.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <select name="status" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>

                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm danh mục..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">
                                Tìm kiếm
                            </button>
                        </div>
                        
                        <?php if (!empty($keyword) || !empty($status)): ?>
                            <a href="<?= route('admin.category.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">
                                Hủy lọc
                            </a>
                        <?php endif; ?>

                        <?php if (hasPermission('admin.category', 'add')): ?>
                            <a href="<?= route('admin.category.create') ?>" class="btn btn-success btn-sm">
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
                                <th>Tên danh mục</th>
                                <th style="width: 150px;" class="text-center">Ngôn ngữ</th>
                                <th style="width: 100px;" class="text-center">Sắp xếp</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($categories)): ?>
                                <?= view('admin.category.table_tree', [
                                    'categories' => $categories, 
                                    'level' => 0, 
                                    'isSearch' => $isSearch ?? false,
                                    'langs' => $langs,
                                    'translations' => $translations
                                ]) ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fs-1 mb-2"></i><br>
                                        Chưa có danh mục nào được tìm thấy.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER: PHÂN TRANG -->
            <?php if ($isSearch ?? false): ?>
            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($categories ?? []) ?> / <?= $totalRows ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= $categories->links() ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- /FOOTER -->

        </div>
    </div>
</div>
