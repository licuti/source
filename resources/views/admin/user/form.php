<?php
$isEdit = !empty($user->id);
$title = $isEdit ? 'Chỉnh sửa Quản trị viên' : 'Thêm Quản trị viên mới';
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Tài khoản', 'url' => route('admin.user.index')],
        ['name' => $isEdit ? 'Chỉnh sửa' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<style>
    /* Chỉnh sửa style component upload ảnh riêng cho Avatar */
    .avatar-wrapper .mb-3 {
        margin-bottom: 0 !important;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .avatar-wrapper .form-label {
        font-weight: 600;
        color: #555;
    }
    .avatar-wrapper .input-group {
        max-width: 400px;
        margin: 0 auto 15px auto;
    }
    .avatar-wrapper .text-center {
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
    }
    #preview_avatar {
        width: 140px !important;
        height: 140px !important;
        object-fit: cover !important;
        border-radius: 50% !important;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    #preview_avatar:hover {
        transform: scale(1.05);
    }
</style>

<div class="app-content">
    <div class="container-fluid">
        <?php if ($msg = session('error')): ?>
            <script>document.addEventListener("DOMContentLoaded", function() { AppNotify.error("<?= $msg ?>"); });</script>
        <?php endif; ?>

        <form action="<?= $isEdit ? route('admin.user.update', ['id' => $user->id]) : route('admin.user.store') ?>" method="POST">
            <div class="row">
                <!-- Cột chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin tài khoản</h3>
                        </div>
                        <div class="card-body">
                            <div class="avatar-wrapper mb-4">
                                <?= view('admin.components.image_upload', [
                                    'name' => 'avatar',
                                    'value' => isset($user) ? $user->avatar : '',
                                    'label' => 'Ảnh đại diện (Avatar)'
                                ]); ?>
                            </div>
                            
                            <hr>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user->username ?? '') ?>" required <?= $isEdit ? 'readonly' : '' ?> placeholder="Chỉ nhập chữ cái không dấu và số">
                                    <?php if($isEdit): ?>
                                    <small class="text-muted">Tên đăng nhập không thể thay đổi sau khi tạo.</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Mật khẩu <?= !$isEdit ? '<span class="text-danger">*</span>' : '' ?></label>
                                    <input type="password" name="password" class="form-control" <?= !$isEdit ? 'required' : '' ?> placeholder="<?= $isEdit ? 'Bỏ trống nếu không muốn đổi mật khẩu' : 'Nhập mật khẩu' ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user->fullname ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user->email ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user->phone ?? '') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Giới tính</label>
                                    <select name="gender" class="form-select">
                                        <option value="0" <?= (!isset($user->gender) || $user->gender == 0) ? 'selected' : '' ?>>Nam</option>
                                        <option value="1" <?= (isset($user->gender) && $user->gender == 1) ? 'selected' : '' ?>>Nữ</option>
                                        <option value="2" <?= (isset($user->gender) && $user->gender == 2) ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Ngày sinh</label>
                                    <input type="date" name="birthday" class="form-control" value="<?= htmlspecialchars($user->birthday ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label>Địa chỉ</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user->address ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột Sidebar Phải -->
                <div class="col-md-3">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">Phân quyền & Trạng thái</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label>Nhóm quyền (Role)</label>
                                <select name="role_id" class="form-select form-select-sm" <?= ($user->is_admin == 1 && session('is_admin') != 1) ? 'disabled' : '' ?>>
                                    <option value="0">--- Chọn nhóm quyền ---</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role->id ?>" <?= ($user->role_id == $role->id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($user->is_admin == 1): ?>
                                    <small class="text-danger mt-1 d-block"><i class="fa-solid fa-crown"></i> Đây là tài khoản Super Admin gốc, không phụ thuộc vào nhóm quyền.</small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?= (!isset($user->is_active) || $user->is_active == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Cho phép đăng nhập (Kích hoạt)</label>
                                </div>
                            </div>
                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.user.index')]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>