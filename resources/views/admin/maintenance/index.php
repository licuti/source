<?php
$title = 'Bảo trì hệ thống';
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Bảo trì hệ thống (Maintenance Mode)</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Bảo trì hệ thống</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        
        <?php if ($status == '1'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>Hệ thống đang trong trạng thái bảo trì!</strong> 
            Người dùng bên ngoài không thể truy cập website.
            <?php if (!empty($meta['enabled_at'])): ?>
                (Bật lúc: <?= date('H:i d/m/Y', strtotime($meta['enabled_at'])) ?>)
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form action="<?= route('admin.maintenance.save') ?>" method="POST" id="maintenance-form">
            
            <div class="row g-4">
                <!-- Cột trái: Cấu hình chung, Hiển thị & Phân quyền -->
                <div class="col-lg-9">
                    
                    <!-- Card Trạng thái -->
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-power-off text-primary me-2"></i> Trạng thái hoạt động</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch d-flex align-items-center mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch" name="maintenance_status" value="1" <?= $status == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label ms-3 fs-5" for="statusSwitch"><strong>Bật chế độ bảo trì</strong></label>
                            </div>
                            <p class="text-muted small mb-0">Khi bật, tất cả truy cập vào trang chủ sẽ bị chuyển hướng sang trang báo lỗi 503 (Ngoại trừ Admin và các IP/Token được cấp phép bên dưới).</p>
                        </div>
                    </div>

                    <!-- Card Cấu hình giao diện -->
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><i class="fa-solid fa-desktop text-primary me-2"></i> Giao diện thông báo</h3>
                            <div class="card-tools">
                                <a href="<?= route('admin.maintenance.preview') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-eye me-1"></i> Xem trước (Preview)
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.input', [
                                'id' => 'title',
                                'name' => 'title',
                                'label' => 'Tiêu đề trang',
                                'value' => $content['title'] ?? 'Hệ thống đang bảo trì',
                                'required' => true,
                                'placeholder' => 'VD: Website đang nâng cấp...'
                            ]) ?>

                            <?= view('admin.components.ckeditor', [
                                'name' => 'description',
                                'label' => 'Nội dung thông báo',
                                'value' => $content['description'] ?? '<p>Chúng tôi đang tiến hành nâng cấp hệ thống. Vui lòng quay lại sau.</p>'
                            ]) ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <?= view('admin.components.datetime', [
                                        'name' => 'eta',
                                        'label' => 'Dự kiến hoàn thành (Tuỳ chọn)',
                                        'value' => $content['eta'] ?? ''
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= view('admin.components.color_picker', [
                                        'name' => 'bg_color',
                                        'label' => 'Màu nền',
                                        'value' => $content['bg_color'] ?? '#0f0f13',
                                        'id' => 'bg_color'
                                    ]) ?>
                                </div>
                            </div>

                            <?= view('admin.components.image_upload', [
                                'name' => 'logo',
                                'label' => 'Đường dẫn Logo (Tuỳ chọn)',
                                'value' => ltrim($content['logo'] ?? '', '/'),
                                'path' => '/'
                            ]) ?>

                        </div>
                    </div>

                    <!-- Card IP Whitelist -->
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-shield-halved text-primary me-2"></i> Ngoại lệ theo IP (Whitelist)</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Các địa chỉ IP dưới đây sẽ được phép truy cập website bình thường dù đang bật bảo trì.</p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                                <span class="small">IP hiện tại của bạn: <strong class="text-primary" id="current_ip"><?= $_SERVER['REMOTE_ADDR'] ?></strong></span>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCurrentIp()">+ Thêm nhanh</button>
                            </div>

                            <div id="ip_list">
                                <?php foreach ($whitelist as $item): ?>
                                <div class="row gx-2 mb-2 ip-item">
                                    <div class="col-5">
                                        <input type="text" name="whitelist_ip[]" class="form-control form-control-sm" placeholder="192.168.1.1" value="<?= htmlspecialchars($item['ip']) ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="whitelist_label[]" class="form-control form-control-sm" placeholder="Mô tả..." value="<?= htmlspecialchars($item['label']) ?>">
                                    </div>
                                    <div class="col-1 text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-dark border mt-2" id="btn-add-ip">
                                <i class="fa-solid fa-plus"></i> Thêm IP ngoại lệ
                            </button>
                        </div>
                    </div>

                    <!-- Card URL Tokens -->
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-link text-primary me-2"></i> Token Truy Cập Khách (URL Bypass)</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Tạo link chia sẻ cho người test: <code><?= config('urls.base', '/') ?>/?bypass=TOKEN</code>. Hệ thống sẽ lưu Cookie 7 ngày.</p>
                            
                            <div id="token_list">
                                <?php foreach ($tokens as $item): ?>
                                <div class="row gx-2 mb-2 token-item bg-light p-2 rounded border">
                                    <div class="col-11">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text">Token</span>
                                            <input type="text" name="token_code[]" class="form-control" value="<?= htmlspecialchars($item['token']) ?>" placeholder="abc_123">
                                            <span class="input-group-text">Hết hạn</span>
                                            <input type="datetime-local" name="token_expire[]" class="form-control" value="<?= htmlspecialchars($item['expires_at']) ?>">
                                        </div>
                                        <input type="text" name="token_label[]" class="form-control form-control-sm" placeholder="Ghi chú (Ai đang dùng?)..." value="<?= htmlspecialchars($item['label']) ?>">
                                    </div>
                                    <div class="col-1 text-end d-flex align-items-center justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-dark border mt-2" id="btn-add-token">
                                <i class="fa-solid fa-plus"></i> Tạo Token mới
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Cột phải: Thao tác -->
                <div class="col-lg-3">
                    
                    <!-- Nút Hành động -->
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-save text-primary me-2"></i> Thao tác</h3>
                        </div>
                        <?= view('admin.components.save_buttons', ['hide_cancel' => true, 'save_text' => 'Lưu cấu hình']) ?>
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // Xử lý nút Thêm nhanh IP
    window.addCurrentIp = function() {
        const ip = document.getElementById('current_ip').innerText.trim();
        addIpRow(ip, 'Admin Device');
    };

    function addIpRow(ip = '', label = '') {
        const html = `
        <div class="row gx-2 mb-2 ip-item">
            <div class="col-5">
                <input type="text" name="whitelist_ip[]" class="form-control form-control-sm" placeholder="192.168.1.1" value="${ip}">
            </div>
            <div class="col-6">
                <input type="text" name="whitelist_label[]" class="form-control form-control-sm" placeholder="Mô tả..." value="${label}">
            </div>
            <div class="col-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
        `;
        document.getElementById('ip_list').insertAdjacentHTML('beforeend', html);
    }

    document.getElementById('btn-add-ip').addEventListener('click', function() { addIpRow(); });

    // Xử lý tạo Token
    document.getElementById('btn-add-token').addEventListener('click', function() {
        const randomStr = Math.random().toString(36).substring(2, 10);
        const html = `
        <div class="row gx-2 mb-2 token-item bg-light p-2 rounded border">
            <div class="col-11">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text">Token</span>
                    <input type="text" name="token_code[]" class="form-control" value="temp_${randomStr}" placeholder="abc_123">
                    <span class="input-group-text">Hết hạn</span>
                    <input type="datetime-local" name="token_expire[]" class="form-control" value="">
                </div>
                <input type="text" name="token_label[]" class="form-control form-control-sm" placeholder="Ghi chú (Ai đang dùng?)..." value="">
            </div>
            <div class="col-1 text-end d-flex align-items-center justify-content-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
        `;
        document.getElementById('token_list').insertAdjacentHTML('beforeend', html);
    });

    // Uỷ quyền xoá dòng
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-row')) {
            e.target.closest('.row').remove();
        }
    });

    // Confirm trước khi bật bảo trì (tránh lỡ tay)
    const form = document.getElementById('maintenance-form');
    const switchEl = document.getElementById('statusSwitch');
    
    // Check trạng thái gốc lúc vừa load
    const originalStatus = switchEl.checked;

    form.addEventListener('submit', function(e) {
        if (switchEl.checked && !originalStatus) {
            e.preventDefault();
            Swal.fire({
                title: 'Kích hoạt bảo trì?',
                text: "Tất cả khách truy cập sẽ không thể vào website, trừ các IP và Token bạn đã cấp phép. Bạn có chắc chắn không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Vâng, Bật bảo trì!',
                cancelButtonText: 'Huỷ'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });
});
</script>
