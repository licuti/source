<?php
$title = $title ?? 'Thêm Khách hàng';
$isEdit = !empty($item);
$action = $isEdit ? route('admin.customer.update', ['id' => $item->id]) : route('admin.customer.store');
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khách hàng', 'url' => route('admin.customer.index')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => []
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
        margin-bottom: 0 !important;
    }
    .avatar-wrapper .img-thumbnail {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #dee2e6;
        padding: 0.25rem;
        background-color: #fff;
    }
</style>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST" id="customerForm">

            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-8">
                    
                    <!-- Thông tin cơ bản -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin cá nhân</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="avatar-wrapper mb-4">
                                <?= view('admin.components.image_upload', [
                                    'name' => 'avatar',
                                    'value' => $item->avatar ?? '',
                                    'label' => 'Ảnh đại diện (Avatar)'
                                ]); ?>
                            </div>
                            
                            <hr>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="fullname" class="form-control"
                                        placeholder="Nguyễn Văn A"
                                        value="<?= htmlspecialchars($item->fullname ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="email@example.com"
                                        value="<?= htmlspecialchars($item->email ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control"
                                        placeholder="0987654321"
                                        value="<?= htmlspecialchars($item->phone ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Ngày sinh</label>
                                    <input type="date" name="birthday" class="form-control"
                                        value="<?= htmlspecialchars($item->birthday ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Giới tính</label>
                                    <select name="gender" class="form-select">
                                        <option value="0" <?= ($item->gender ?? 0) == 0 ? 'selected' : '' ?>>Nữ</option>
                                        <option value="1" <?= ($item->gender ?? 0) == 1 ? 'selected' : '' ?>>Nam</option>
                                        <option value="2" <?= ($item->gender ?? 0) == 2 ? 'selected' : '' ?>>Khác</option>
                                    </select>
                                </div>
                            </div>
                            
                            <?php if ($isEdit): ?>
                            <div class="mb-0">
                                <label class="form-label fw-bold">Mã Khách hàng</label>
                                <input type="text" name="code" class="form-control bg-light"
                                    value="<?= htmlspecialchars($item->code ?? '') ?>" readonly>
                                <div class="form-text">Mã KH được tạo tự động và không thể chỉnh sửa.</div>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>

                    <!-- Thông tin Địa chỉ -->
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin liên hệ & Địa chỉ</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Địa chỉ chi tiết (Số nhà, Tên đường...)</label>
                                <input type="text" name="address" class="form-control" placeholder="Số 123, Đường ABC..." value="<?= htmlspecialchars($item->address ?? '') ?>">
                            </div>
                            
                            <!-- Component Location Selector -->
                            <?= view('admin.components.location_selector', [
                                'item' => $item ?? [],
                                'countries' => $countries ?? [],
                                'provinces' => $provinces ?? [],
                                'districts' => $districts ?? [],
                                'wards' => $wards ?? [],
                                'country_name' => 'country_id',
                                'province_name' => 'province_id',
                                'district_name' => 'district_id',
                                'ward_name' => 'ward_id',
                                'selected_country' => $item->country_id ?? 0,
                                'selected_province' => $item->province_id ?? 0,
                                'selected_district' => $item->district_id ?? 0,
                                'selected_ward' => $item->ward_id ?? 0
                            ]) ?>
                        </div>
                    </div>
                    
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-4">
                    
                    <!-- Box: Hành động -->
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="status" id="status" <?= (!isset($item->status) || $item->status == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="status">Cho phép đăng nhập (Active)</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.customer.index') ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" name="save_action" value="exit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-save"></i> Lưu
                            </button>
                            <button type="submit" name="save_action" value="continue" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                            </button>
                        </div>
                    </div>

                    <!-- Box: Đặt lại Mật khẩu -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-key"></i> Đặt lại mật khẩu</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold">Mật khẩu mới</label>
                                <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới...">
                            </div>
                            <div class="form-text">
                                <?php if ($isEdit): ?>
                                    Để trống nếu không muốn đổi mật khẩu. Nếu nhập, mật khẩu cũ sẽ bị ghi đè.
                                <?php else: ?>
                                    Nếu để trống, hệ thống sẽ tự động tạo một mật khẩu ngẫu nhiên.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($isEdit): ?>
                    <!-- Box: Thống kê nhanh -->
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-header border-bottom-0">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-chart-pie"></i> Thống kê mua hàng</h5>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row text-center">
                                <div class="col-6 border-end border-light">
                                    <div class="display-6 fw-bold">0</div>
                                    <div class="small text-white-50">Đơn hàng</div>
                                </div>
                                <div class="col-6">
                                    <div class="display-6 fw-bold">0đ</div>
                                    <div class="small text-white-50">Tổng chi tiêu</div>
                                </div>
                            </div>
                            <div class="mt-3 text-center small text-white-50">
                                <em>(Thống kê sẽ được cập nhật khi module Order hoàn thiện)</em>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </form>
    </div>
</div>
