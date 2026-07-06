<?php
$title = 'Quản lý chuyển hướng (Redirect 301)';

$breadcrumbActions = [];
if (hasPermission('admin.redirect', 'add')) {
    $breadcrumbActions[] = ['label' => 'Thêm mới', 'icon' => 'fa-plus', 'url' => route('admin.redirect.create'), 'class' => 'btn-primary'];
}
?>

<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Redirect 301', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    
                    <!-- TRÁI: Hành động hàng loạt -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <?php if (hasPermission('admin.redirect', 'delete')): ?>
                            <option value="delete" data-url="<?= route('admin.redirect.destroyMultiple') ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                            <?php endif; ?>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>

                    <!-- PHẢI: Tìm kiếm & Thêm mới -->
                    <form action="<?= route('admin.redirect.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm URL..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword)): ?>
                            <a href="<?= route('admin.redirect.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <?php if (hasPermission('admin.redirect', 'add')): ?>
                        <a href="<?= route('admin.redirect.create') ?>" class="btn btn-success btn-sm">
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
                                <th style="width: 60px;" class="text-center">ID</th>
                                <th>URL Nguồn (Lỗi/Cũ)</th>
                                <th>URL Đích (Mới)</th>
                                <th style="width: 120px;" class="text-center">Trạng thái</th>
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
                                    <td class="text-center text-muted fw-bold align-middle"><?= $item->id ?></td>
                                    <td class="align-middle">
                                        <strong>
                                            <a href="<?= route('admin.redirect.edit', ['id' => $item->id]) ?>" class="text-danger text-decoration-none">
                                                <i class="fa-solid fa-link me-1"></i> <?= htmlspecialchars($item->old_url) ?>
                                            </a>
                                        </strong>
                                        
                                        <!-- WP-Style Row Actions -->
                                        <?php 
                                        $actions = [];
                                        if (hasPermission('admin.redirect', 'edit')) {
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.redirect.edit', ['id' => $item->id]), 
                                                'class' => 'text-primary'
                                            ];
                                        }
                                        if (hasPermission('admin.redirect', 'delete')) {
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => '#', 
                                                'class' => 'text-danger action-delete',
                                                'attributes' => 'data-id="' . $item->id . '" data-url="' . route('admin.redirect.destroy', ['id' => $item->id]) . '"'
                                            ];
                                        }
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-success"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i> <?= htmlspecialchars($item->new_url) ?></span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <?php if (hasPermission('admin.redirect', 'edit')): ?>
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-id="<?= $item->id ?>" data-field="status" 
                                                data-url="<?= route('admin.redirect.status') ?>" 
                                                <?= $item->status ? 'checked' : '' ?>>
                                        </div>
                                        <?php else: ?>
                                            <span class="badge <?= $item->status ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= $item->status ? 'Hoạt động' : 'Đã tắt' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-diamond-turn-right fs-1 mb-2 text-black-50"></i><br>
                                        Chưa có chuyển hướng nào.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER: PHÂN TRANG -->
            <?php if (isset($items) && count($items) > 0): ?>
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
            <?php endif; ?>
            <!-- /FOOTER -->

        </div>
    </div>
</div>

<form id="delete-form" method="POST" action="" style="display: none;">
    <input type="hidden" name="_method" value="DELETE">
</form>

<script>
    $(document).ready(function() {
        // Handle Single Delete via Row Action
        $('.action-delete').click(function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            AppNotify.confirm('Bạn có chắc chắn muốn xóa bản ghi này?', function() {
                var $form = $('#delete-form');
                $form.attr('action', url);
                $form.submit();
            });
        });
    });
</script>
