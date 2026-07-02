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
        
        <?php $successMsg = session('success'); if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check-circle me-1"></i> <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="<?= route('admin.email.save') ?>" method="POST" id="email-form">
            <div class="row g-4">
                
                <!-- Cấu hình Máy chủ -->
                <div class="col-md-6">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-server text-primary me-2"></i> Cấu hình Máy chủ SMTP</h3>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trình gửi mail (Mailer)</label>
                                <select class="form-select" name="MAIL_MAILER">
                                    <option value="smtp" <?= ($settings['MAIL_MAILER'] ?? '') == 'smtp' ? 'selected' : '' ?>>SMTP (Khuyến nghị)</option>
                                    <option value="mail" <?= ($settings['MAIL_MAILER'] ?? '') == 'mail' ? 'selected' : '' ?>>PHP Mail</option>
                                    <option value="sendmail" <?= ($settings['MAIL_MAILER'] ?? '') == 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Máy chủ (Host)</label>
                                <input type="text" class="form-control" name="MAIL_HOST" value="<?= htmlspecialchars($settings['MAIL_HOST'] ?? '') ?>" placeholder="VD: smtp.gmail.com">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Cổng (Port)</label>
                                        <input type="number" class="form-control" name="MAIL_PORT" value="<?= htmlspecialchars($settings['MAIL_PORT'] ?? '') ?>" placeholder="465 hoặc 587">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Mã hóa (Encryption)</label>
                                        <select class="form-select" name="MAIL_ENCRYPTION">
                                            <option value="ssl" <?= ($settings['MAIL_ENCRYPTION'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                            <option value="tls" <?= ($settings['MAIL_ENCRYPTION'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                                            <option value="" <?= empty($settings['MAIL_ENCRYPTION']) ? 'selected' : '' ?>>Không mã hóa</option>
                                        </select>
                                    </div>
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
                    <div class="card card-success card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-user-shield text-success me-2"></i> Thông tin Tài khoản</h3>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email gửi (Username)</label>
                                <input type="email" class="form-control" name="MAIL_USERNAME" value="<?= htmlspecialchars($settings['MAIL_USERNAME'] ?? '') ?>" placeholder="VD: your_email@gmail.com">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Mật khẩu ứng dụng (Password)</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="MAIL_PASSWORD" id="mail-password" value="<?= htmlspecialchars($settings['MAIL_PASSWORD'] ?? '') ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()"><i class="fa-solid fa-eye" id="eye-icon"></i></button>
                                </div>
                                <small class="text-muted d-block mt-1">Đối với Gmail, bạn phải sử dụng Mật khẩu Ứng dụng (App Password) 16 ký tự, không dùng mật khẩu đăng nhập gốc.</small>
                            </div>
                            
                            <hr>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Địa chỉ Email người gửi (From Address)</label>
                                <input type="email" class="form-control" name="MAIL_FROM_ADDRESS" value="<?= htmlspecialchars($settings['MAIL_FROM_ADDRESS'] ?? '') ?>" placeholder="Thường giống Email gửi ở trên">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên người gửi hiển thị (From Name)</label>
                                <input type="text" class="form-control" name="MAIL_FROM_NAME" value="<?= htmlspecialchars($settings['MAIL_FROM_NAME'] ?? '') ?>" placeholder="VD: Tên Công Ty">
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút Hành động -->
            <div class="row mt-4 mb-5">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="input-group">
                            <input type="email" class="form-control" id="test-email-input" placeholder="Nhập email nhận test..." style="min-width: 250px;">
                            <button type="button" class="btn btn-warning" id="btn-test-email">
                                <i class="fa-solid fa-paper-plane me-1"></i> Gửi thử (Test)
                            </button>
                        </div>
                        <small class="text-muted d-block mt-1">Tính năng Gửi thử sẽ dùng các thông số trên Form (chưa cần lưu) để test.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-save me-1"></i> Lưu cấu hình SMTP
                    </button>
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
