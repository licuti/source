<?php
if (!function_exists('renderCategoryFilter')) {
    function renderCategoryFilter($categories, $selectedId = 0, $prefix = '') {
        foreach ($categories as $cat) {
            $selected = ($cat->id_code == $selectedId) ? 'selected' : '';
            echo '<option value="' . $cat->id_code . '" ' . $selected . '>' . $prefix . htmlspecialchars($cat->ten ?? $cat->name) . '</option>';
            if (!empty($cat->children)) {
                renderCategoryFilter($cat->children, $selectedId, $prefix . '--- ');
            }
        }
    }
}

$breadcrumbActions = [];
$canAdd = hasPermission('admin.post', 'add');
$canDelete = hasPermission('admin.post', 'delete');
$canEdit = hasPermission('admin.post', 'edit');
$user = user();
$isAdmin = $user->is_admin == 1;

// Mặc định mọi user đều có thể "Thêm bài viết" (sẽ gán cho chính họ)
if ($canAdd) {
    $breadcrumbActions[] = ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.post.create'), 'class' => 'btn-primary'];
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Bài viết',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Bài viết', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow-sm">
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <?php if ($canDelete): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.post.destroy_multiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các bài viết đã chọn?">
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
                    <form action="<?= route('admin.post.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
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
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm bài viết..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">
                                Tìm kiếm
                            </button>
                        </div>
                        
                        <?php if (!empty($keyword) || !empty($status) || !empty($category_id)): ?>
                            <a href="<?= route('admin.post.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">
                                Hủy lọc
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
                                <th>Tiêu đề bài viết</th>
                                <th style="width: 120px;" class="text-center">Người đăng</th>
                                <th style="width: 120px;" class="text-center">Lượt xem</th>
                                <th style="width: 100px;" class="text-center">Sắp xếp</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($posts)): ?>
                                <?php foreach($posts as $item): ?>
                                    <?php 
                                    $imgHtml = '';
                                    if ($item->image) {
                                        $imgHtml = '<img src="' . getImageUrl($item->image) . '" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">';
                                    } else {
                                        $imgHtml = '<span class="badge bg-light text-dark border">Trống</span>';
                                    }

                                    // Permission check for this specific row
                                    $rowCanEdit = $canEdit && ($isAdmin || $item->created_by == $user->id);
                                    $rowCanDelete = $canDelete && ($isAdmin || $item->created_by == $user->id);

                                    $checked = $item->is_active ? 'checked' : '';
                                    if ($rowCanEdit) {
                                        $statusHtml = '
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="' . $item->id_code . '" data-field="is_active" data-url="' . route('admin.post.updateStatusAjax') . '" ' . $checked . ' style="cursor: pointer; width: 2.5em; height: 1.25em;">
                                            </div>
                                        ';
                                    } else {
                                        $statusHtml = '
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" ' . $checked . ' disabled style="width: 2.5em; height: 1.25em;">
                                            </div>
                                        ';
                                    }
                                    ?>
                                    <tr class="wp-row">
                                        <th scope="row" class="text-center align-middle">
                                            <?php if ($rowCanDelete): ?>
                                            <div class="form-check d-flex justify-content-center mb-0">
                                                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id_code ?>">
                                            </div>
                                            <?php endif; ?>
                                        </th>
                                        <td class="text-center align-middle"><?= $imgHtml ?></td>
                                        <td class="align-middle">
                                            <?php if ($rowCanEdit): ?>
                                                <strong><a href="<?= route('admin.post.edit', ['id' => $item->id_code]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->title) ?></a></strong>
                                            <?php else: ?>
                                                <strong><span class="text-dark"><?= htmlspecialchars($item->title) ?></span></strong>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $actions = [];
                                            if ($rowCanEdit) {
                                                $actions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.post.edit', ['id' => $item->id_code]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if ($rowCanDelete) {
                                                $actions['delete'] = [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.post.destroy', ['id' => $item->id_code]), 
                                                    'class' => 'text-danger', 
                                                    'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa bài viết này?\')"'
                                                ];
                                            }
                                            if (!empty($actions)) {
                                                echo view('admin.components.row_actions', ['actions' => $actions]);
                                            }
                                            ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge bg-secondary"><?= $item->created_by == $user->id ? 'Bạn' : 'ID: '.$item->created_by ?></span>
                                        </td>
                                        <td class="text-center align-middle"><?= number_format($item->views) ?></td>
                                        <td class="text-center align-middle"><?= $item->sort_order ?></td>
                                        <td class="text-center align-middle"><?= $statusHtml ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                        Chưa có bài viết nào được tìm thấy.
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
                        Hiển thị <?= count($posts ?? []) ?> / <?= $totalRows ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= paging($totalRows, 10, $page, getCurrentUrlWithoutPage()) ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->

        </div>
    </div>
</div>
