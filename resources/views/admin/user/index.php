<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Tài khoản (Admins)',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Tài khoản', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <!-- Thông báo Flash Messages đã được chuyển về layouts/main.php -->

        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Danh sách Quản trị viên</h3>
                <div class="card-tools">
                    <form action="<?= route('admin.user.index') ?>" method="GET" class="d-inline-block">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" name="keyword" class="form-control float-right" placeholder="Tìm tên, email, tài khoản..." value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                    </form>
                    <?php if (hasPermission('admin.user', 'add')): ?>
                    <a href="<?= route('admin.user.create') ?>" class="btn btn-sm btn-success ms-2"><i class="fa-solid fa-plus"></i> Thêm Mới</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>

                                <th>Tài khoản</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th class="text-center">Nhóm Quyền</th>
                                <th class="text-center" width="120">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users->data)): ?>
                                <tr><td colspan="6" class="text-center text-muted">Không tìm thấy tài khoản nào!</td></tr>
                            <?php else: ?>
                                <?php foreach ($users->data as $item): ?>
                                    <tr class="wp-row">

                                        <td>
                                            <strong><?= htmlspecialchars($item->username) ?></strong>
                                            <?php if ($item->is_admin == 1): ?>
                                                <span class="badge bg-danger ms-1" title="Super Admin"><i class="fa-solid fa-crown"></i> Root</span>
                                            <?php endif; ?>
                                            
                                            <!-- WP-Style Row Actions -->
                                            <?php 
                                            $userActions = [];
                                            if (hasPermission('admin.user', 'edit')) {
                                                $userActions['edit'] = [
                                                    'label' => 'Chỉnh sửa', 
                                                    'url' => route('admin.user.edit', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ];
                                            }
                                            if ($item->id != session('id_user') && $item->is_admin != 1 && hasPermission('admin.user', 'delete')) {
                                                $userActions['delete'] = [
                                                    'label' => 'Xóa', 
                                                    'url' => route('admin.user.destroy', ['id' => $item->id]), 
                                                    'class' => 'text-danger btn-delete'
                                                ];
                                            }
                                            if (!empty($userActions)) {
                                                echo view('admin.components.row_actions', ['actions' => $userActions]);
                                            }
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($item->fullname) ?></td>
                                        <td><?= htmlspecialchars($item->email) ?></td>
                                        <td class="text-center">
                                            <?php if ($item->role): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($item->role->name) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="<?= $item->id ?>" data-field="is_active" data-url="<?= route('admin.user.updateStatusAjax') ?>" <?= $item->is_active == 1 ? 'checked' : '' ?> <?= ($item->is_admin == 1 && session('is_admin') != 1 || !hasPermission('admin.user', 'edit')) ? 'disabled' : '' ?> style="cursor: pointer;">
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
                        Hiển thị <?= count($users->data ?? []) ?> / <?= $users->total ?? 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= paging($users->total, $users->per_page, $users->current_page, getCurrentUrlWithoutPage()) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Confirm Delete
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            AppNotify.confirm('Bạn có chắc chắn muốn xóa tài khoản này không? Mọi dữ liệu liên quan có thể bị ảnh hưởng.', function() {
                window.location.href = href;
            });
        });
    });
</script>
