<?php
$isEdit = isset($role);
$title = $isEdit ? 'Chỉnh sửa Nhóm quyền' : 'Thêm Nhóm quyền';
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0"><?= $title ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= route('admin.role.index') ?>">Nhóm quyền</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= $title ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.role.update', ['id' => $role->id]) : route('admin.role.store') ?>" method="POST">
            <div class="row">
                <!-- Cột trái: Thông tin cơ bản -->
                <div class="col-md-4">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin cơ bản</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Tên nhóm quyền <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role->name ?? '') ?>" required <?= ($isEdit && $role->is_system == 1) ? 'readonly' : '' ?>>
                                <?php if ($isEdit && $role->is_system == 1): ?>
                                    <small class="text-danger">Không thể đổi tên nhóm quyền hệ thống.</small>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($role->description ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?= ($isEdit ? $role->is_active : 1) == 1 ? 'checked' : '' ?> <?= ($isEdit && $role->is_system == 1) ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="is_active">Kích hoạt</label>
                                </div>
                                <?php if ($isEdit && $role->is_system == 1): ?>
                                    <input type="hidden" name="is_active" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột phải: Ma trận phân quyền -->
                <div class="col-md-8">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">Ma trận phân quyền</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Module / Tính năng</th>
                                            <th class="text-center" width="80">Xem</th>
                                            <th class="text-center" width="80">Thêm</th>
                                            <th class="text-center" width="80">Sửa</th>
                                            <th class="text-center" width="80">Xóa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($modules)): ?>
                                            <tr><td colspan="5" class="text-center">Chưa có module nào trong hệ thống.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($modules as $module): ?>
                                                <?php
                                                    $mId = $module->id;
                                                    $canView = isset($permissions[$mId]['can_view']) && $permissions[$mId]['can_view'] == 1;
                                                    $canAdd = isset($permissions[$mId]['can_add']) && $permissions[$mId]['can_add'] == 1;
                                                    $canEdit = isset($permissions[$mId]['can_edit']) && $permissions[$mId]['can_edit'] == 1;
                                                    $canDelete = isset($permissions[$mId]['can_delete']) && $permissions[$mId]['can_delete'] == 1;
                                                ?>
                                                <tr class="table-secondary">
                                                    <td><strong><i class="fa <?= $module->icon ?? 'fa-circle' ?> me-2"></i> <?= htmlspecialchars($module->name) ?></strong></td>
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="checkbox" name="permissions[<?= $mId ?>][can_view]" value="1" <?= $canView ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="checkbox" name="permissions[<?= $mId ?>][can_add]" value="1" <?= $canAdd ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="checkbox" name="permissions[<?= $mId ?>][can_edit]" value="1" <?= $canEdit ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check d-flex justify-content-center">
                                                            <input class="form-check-input" type="checkbox" name="permissions[<?= $mId ?>][can_delete]" value="1" <?= $canDelete ? 'checked' : '' ?>>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <?php
                                                    $subModules = \ModuleAdminModel::where('parent', $mId)->where('is_active', 1)->orderBy('sort_order', 'ASC')->get();
                                                ?>
                                                <?php foreach ($subModules as $sub): ?>
                                                    <?php
                                                        $sId = $sub->id;
                                                        $sCanView = isset($permissions[$sId]['can_view']) && $permissions[$sId]['can_view'] == 1;
                                                        $sCanAdd = isset($permissions[$sId]['can_add']) && $permissions[$sId]['can_add'] == 1;
                                                        $sCanEdit = isset($permissions[$sId]['can_edit']) && $permissions[$sId]['can_edit'] == 1;
                                                        $sCanDelete = isset($permissions[$sId]['can_delete']) && $permissions[$sId]['can_delete'] == 1;
                                                    ?>
                                                    <tr>
                                                        <td class="ps-4"><i class="fa fa-angle-right text-muted me-2"></i> <?= htmlspecialchars($sub->name) ?></td>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox" name="permissions[<?= $sId ?>][can_view]" value="1" <?= $sCanView ? 'checked' : '' ?>>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox" name="permissions[<?= $sId ?>][can_add]" value="1" <?= $sCanAdd ? 'checked' : '' ?>>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox" name="permissions[<?= $sId ?>][can_edit]" value="1" <?= $sCanEdit ? 'checked' : '' ?>>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check d-flex justify-content-center">
                                                                <input class="form-check-input" type="checkbox" name="permissions[<?= $sId ?>][can_delete]" value="1" <?= $sCanDelete ? 'checked' : '' ?>>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white text-muted small">
                            <i class="fa-solid fa-info-circle me-1"></i> Quyền <strong>Xem</strong> thường là bắt buộc để truy cập vào trang danh sách của Module.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút Hành động (Component dùng chung) -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?= route('admin.role.index') ?>" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Quay lại
                        </a>
                        <div>
                            <button type="submit" name="save_action" value="save" class="btn btn-success me-2">
                                <i class="fa-solid fa-save"></i> Lưu nhóm quyền
                            </button>
                            <button type="submit" name="save_action" value="save_and_edit" class="btn btn-primary">
                                <i class="fa-solid fa-edit"></i> Lưu & Tiếp tục sửa
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


