<?php
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
                    <!-- Left: Bulk actions -->
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

                    <!-- Right: Search, Filter & Add New -->
                    <form action="<?= route('admin.post.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <select name="lang" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <?php foreach ($langs as $l): ?>
                                <option value="<?= $l['code'] ?>" <?= ($currentLang ?? 'vi') == $l['code'] ? 'selected' : '' ?>><?= $l['name'] ?></option>
                            <?php endforeach; ?>
                        </select>

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
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <?php if (!empty($keyword) || ($status ?? '') !== '' || !empty($category_id)): ?>
                            <a href="<?= route('admin.post.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <?php if ($canAdd): ?>
                            <a href="<?= route('admin.post.create') ?>" class="btn btn-success btn-sm">
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
                                <th>Tiêu đề bài viết</th>
                                <th style="width: 120px;" class="text-center">Người đăng</th>
                                <th style="width: 120px;" class="text-center">Lượt xem</th>
                                <th style="width: 100px;" class="text-center">Sắp xếp</th>
                                <th style="width: 100px;" class="text-center">Nổi bật</th>
                                <th style="width: 120px;" class="text-center">Hiển thị</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($posts)): ?>
                                <?php foreach($posts as $item): ?>
                                    <?php 
                                    // Permission check for this specific row
                                    $rowCanEdit = $canEdit && ($isAdmin || $item->created_by == $user->id);
                                    $rowCanDelete = $canDelete && ($isAdmin || $item->created_by == $user->id);
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
                                            <?php if ($item->image): ?>
                                                <img src="<?= getImageUrl($item->image) ?>" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border">Trống</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Tiêu đề -->
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
                                        
                                        <!-- Người đăng -->
                                        <td class="text-center align-middle">
                                            <span class="badge bg-secondary"><?= $item->created_by == $user->id ? 'Bạn' : 'ID: '.$item->created_by ?></span>
                                        </td>
                                        
                                        <!-- Lượt xem -->
                                        <td class="text-center align-middle"><?= number_format($item->views) ?></td>
                                        
                                        <!-- Sắp xếp -->
                                        <td class="text-center align-middle"><?= $item->sort_order ?></td>
                                        
                                        <!-- is_featured -->
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <?php if ($rowCanEdit): ?>
                                                    <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id_code ?>" data-field="is_featured" data-url="<?= route('admin.post.updateStatusAjax') ?>" <?= $item->is_featured ? 'checked' : '' ?>>
                                                <?php else: ?>
                                                    <input class="form-check-input" type="checkbox" <?= $item->is_featured ? 'checked' : '' ?> disabled>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <!-- hien_thi -->
                                        <td class="text-center align-middle">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <?php if ($rowCanEdit): ?>
                                                    <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id_code ?>" data-field="status" data-url="<?= route('admin.post.updateStatusAjax') ?>" <?= $item->status ? 'checked' : '' ?>>
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
                        Hiển thị <?= count($posts) ?> / <?= $posts->total() ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= $posts->links() ?>
                    </div>
                </div>
            </div>
            <!-- /FOOTER -->

        </div>
    </div>
</div>
