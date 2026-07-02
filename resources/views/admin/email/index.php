<?php
$title = 'Cấu hình Email / SMTP';
?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Cấu hình Email / SMTP</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Email / SMTP</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        


        <form action="<?= route('admin.email.save') ?>" method="POST" id="email-form">
            <div class="row g-4">
                
                <!-- Cấu hình Máy chủ -->
                <div class="col-md-6">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-server text-primary me-2"></i> Cấu hình Máy chủ SMTP</h3>
                        </div>
                        <div class="card-body">
                            
                            <?= view('admin.components.select', [
                                'label' => 'Trình gửi mail (Mailer)',
                                'name' => 'MAIL_MAILER',
                                'value' => $settings['MAIL_MAILER'] ?? '',
                                'options' => [
                                    'smtp' => 'SMTP (Khuyến nghị)',
                                    'mail' => 'PHP Mail',
                                    'sendmail' => 'Sendmail'
                                ]
                            ]) ?>

                            <?= view('admin.components.input', [
                                'label' => 'Máy chủ (Host)',
                                'name' => 'MAIL_HOST',
                                'value' => $settings['MAIL_HOST'] ?? '',
                                'attrs' => ['placeholder' => 'VD: smtp.gmail.com']
                            ]) ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <?= view('admin.components.input', [
                                        'type' => 'number',
                                        'label' => 'Cổng (Port)',
                                        'name' => 'MAIL_PORT',
                                        'value' => $settings['MAIL_PORT'] ?? '',
                                        'attrs' => ['placeholder' => '465 hoặc 587']
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= view('admin.components.select', [
                                        'label' => 'Mã hóa (Encryption)',
                                        'name' => 'MAIL_ENCRYPTION',
                                        'value' => $settings['MAIL_ENCRYPTION'] ?? '',
                                        'options' => [
                                            'ssl' => 'SSL (Port 465)',
                                            'tls' => 'TLS (Port 587)',
                                            '' => 'Không mã hóa'
                                        ]
                                    ]) ?>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3 mb-0 small">
                                <i class="fa-solid fa-lightbulb"></i> <strong>Gợi ý cài đặt Gmail:</strong><br>
                                Host: <code>smtp.gmail.com</code> | Port: <code>465</code> | Mã hóa: <code>ssl</code>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Cấu hình Tài khoản -->
                <div class="col-md-6">
                    <div class="card card-dark card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-user-shield text-dark me-2"></i> Thông tin Tài khoản</h3>
                        </div>
                        <div class="card-body">
                            
                            <?= view('admin.components.input', [
                                'type' => 'email',
                                'label' => 'Email gửi (Username)',
                                'name' => 'MAIL_USERNAME',
                                'value' => $settings['MAIL_USERNAME'] ?? '',
                                'attrs' => ['placeholder' => 'VD: your_email@gmail.com']
                            ]) ?>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Mật khẩu ứng dụng (Password)</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control form-control-sm" name="MAIL_PASSWORD" id="mail-password" value="<?= htmlspecialchars($settings['MAIL_PASSWORD'] ?? '') ?>">
                                    <button class="btn btn-outline-dark" type="button" onclick="togglePassword()"><i class="fa-solid fa-eye" id="eye-icon"></i></button>
                                </div>
                                <small class="text-muted d-block mt-1">Đối với Gmail, bạn phải sử dụng Mật khẩu Ứng dụng (App Password) 16 ký tự, không dùng mật khẩu đăng nhập gốc.</small>
                            </div>
                            
                            <hr>

                            <?= view('admin.components.input', [
                                'type' => 'email',
                                'label' => 'Địa chỉ Email người gửi (From Address)',
                                'name' => 'MAIL_FROM_ADDRESS',
                                'value' => $settings['MAIL_FROM_ADDRESS'] ?? '',
                                'attrs' => ['placeholder' => 'Thường giống Email gửi ở trên']
                            ]) ?>

                            <?= view('admin.components.input', [
                                'label' => 'Tên người gửi hiển thị (From Name)',
                                'name' => 'MAIL_FROM_NAME',
                                'value' => $settings['MAIL_FROM_NAME'] ?? '',
                                'attrs' => ['placeholder' => 'VD: Tên Công Ty']
                            ]) ?>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút Hành động -->
            <div class="row mt-4 mb-5">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="input-group input-group-sm">
                            <input type="email" class="form-control form-control-sm" id="test-email-input" placeholder="Nhập email nhận test..." style="min-width: 250px;">
                            <button type="button" class="btn btn-dark" id="btn-test-email">
                                <i class="fa-solid fa-paper-plane me-1"></i> Gửi thử (Test)
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Tính năng Gửi thử sẽ dùng các thông số trên Form (chưa cần lưu) để test.</small>
                    </div>
                    
                    <?= view('admin.components.save_buttons', ['hide_cancel' => true, 'save_text' => 'Lưu cấu hình SMTP']) ?>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
function togglePassword() {
    var input = document.getElementById("mail-password");
    var icon = document.getElementById("eye-icon");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

document.getElementById('btn-test-email').addEventListener('click', function() {
    let testEmail = document.getElementById('test-email-input').value;
    if (!testEmail) {
        alert('Vui lòng nhập địa chỉ email nhận test!');
        return;
    }
    
    let btn = this;
    let originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Đang gửi...';
    btn.disabled = true;

    // Collect form data
    let formData = new FormData(document.getElementById('email-form'));
    formData.append('test_email', testEmail);

    fetch('<?= route('admin.email.test') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Thành công!', data.message, 'success');
        } else {
            Swal.fire('Lỗi gửi mail!', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Lỗi hệ thống!', 'Không thể kết nối đến server.', 'error');
        console.error(error);
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
});
</script>
