<?php
$title = 'Nhóm quyền (Roles)';
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Nhóm quyền</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nhóm quyền</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Danh sách nhóm quyền</h3>
                <?php if (hasPermission('admin.role', 'can_add')): ?>
                <div class="card-tools">
                    <a href="<?= route('admin.role.create') ?>" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-plus"></i> Thêm mới
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body p-0">
                <form method="GET" action="<?= route('admin.role.index') ?>" class="p-3 border-bottom bg-light">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm nhóm quyền..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                        <button type="submit" class="btn btn-default"><i class="fa-solid fa-search"></i></button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th width="50" class="text-center">ID</th>
                                <th>Tên nhóm quyền</th>
                                <th>Mô tả</th>
                                <th width="120" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roles->data)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Không có dữ liệu!</td></tr>
                            <?php else: ?>
                                <?php foreach ($roles->data as $item): ?>
                                    <tr class="wp-row">
                                        <td class="text-center"><?= $item->id ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item->name) ?></strong>
                                            <?php if ($item->is_system == 1): ?>
                                                <span class="badge bg-danger ms-1">Hệ thống</span>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $roleActions = [];
                                            if (hasPermission('admin.role', 'can_edit')) {
                                                $roleActions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.role.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if ($item->is_system != 1 && hasPermission('admin.role', 'can_delete')) {
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
                                        <td><?= htmlspecialchars($item->description) ?></td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id ?>" data-field="is_active" data-url="<?= route('admin.role.updateStatusAjax') ?>" <?= $item->is_active == 1 ? 'checked' : '' ?> <?= ($item->is_system == 1 || !hasPermission('admin.role', 'can_edit')) ? 'disabled' : '' ?> style="cursor: pointer;">
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($roles->data ?? []) ?> / <?= $roles->total ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
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


