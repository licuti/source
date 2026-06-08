<?php
$title = 'Nhóm quyền (Roles)';
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Nhóm quyền', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">

        <!-- BẢNG DỮ LIỆU -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    Danh sách <span class="badge bg-secondary ms-1"><?= $roles->total ?? 0 ?></span>
                </h3>
                <div class="card-tools">
                    <form action="<?= route('admin.role.index') ?>" method="GET" class="d-inline-block">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="keyword" class="form-control float-right" placeholder="Tìm tên nhóm quyền..." value="<?= htmlspecialchars($_GET['keyword'] ?? $keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                    </form>
                    <?php if (hasPermission('admin.role', 'add')): ?>
                        <a href="<?= route('admin.role.create') ?>" class="btn btn-sm btn-success ms-2"><i class="fa-solid fa-plus"></i> Thêm Mới</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>

                                <th>Tên nhóm quyền</th>
                                <th>Mô tả</th>
                                <th width="120" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roles->data)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-inbox fs-1 d-block mb-2"></i>
                                        Chưa có dữ liệu nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($roles->data as $item): ?>
                                    <tr class="wp-row">

                                        <td>
                                            <strong><?= htmlspecialchars($item->name ?? '') ?></strong>
                                            <?php if ($item->is_system == 1): ?>
                                                <span class="badge bg-danger ms-1">Hệ thống</span>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $roleActions = [];
                                            if (hasPermission('admin.role', 'edit')) {
                                                $roleActions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.role.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if ($item->is_system != 1 && hasPermission('admin.role', 'delete')) {
                                                $roleActions['delete'] = [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.role.destroy', ['id' => $item->id]), 
                                                    'class' => 'text-danger btn-delete'
                                                ];
                                            }
                                            if (!empty($roleActions)) {
                                                echo view('admin.components.row_actions', ['actions' => $roleActions]);
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($item->description ?? '') ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id ?>" data-field="is_active" data-url="<?= route('admin.role.updateStatusAjax') ?>" <?= $item->is_active == 1 ? 'checked' : '' ?> <?= ($item->is_system == 1 || !hasPermission('admin.role', 'edit')) ? 'disabled' : '' ?> style="cursor: pointer;">
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer clearfix">
                <div class="row align-items-center">
                    <div class="col-md-6 text-muted small">
                        Hiển thị <?= count($roles->data ?? []) ?> / <?= $roles->total ?? 0 ?> bản ghi
                    </div>
                    <div class="col-md-6 text-end pagination-right-sm">
                        <?= paging($roles->total, $roles->per_page, $roles->current_page, getCurrentUrlWithoutPage()) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            AppNotify.confirm('Bạn có chắc chắn muốn xóa nhóm quyền này không?', function() {
                window.location.href = href;
            });
        });
    });
</script>


