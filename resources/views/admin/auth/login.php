<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | CMS Panel</title>
    <!-- AdminLTE v4 (Bootstrap 5) -->
    <link rel="stylesheet" href="<?= asset('admin/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.login-page {
            background-color: #e9ecef;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-box { width: 400px; }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header text-center">
                <a href="#" class="h2 text-decoration-none"><b>CMS</b>Panel</a>
            </div>
            <div class="card-body">
                <p class="login-box-msg text-center mb-3">ĐĂNG NHẬP HỆ THỐNG QUẢN TRỊ</p>
                
                <?php if (!empty($err)): ?>
                    <div class="alert alert-danger text-center p-2"><?= $err ?></div>
                <?php endif; ?>
                
                <form action="<?= route('admin.login') ?>" method="post">
                    <!-- CSRF Token (nếu framework hỗ trợ) -->
                    <?= function_exists('csrf_field') ? csrf_field() : '' ?>
                    
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="input-username" placeholder="Tên đăng nhập" required>
                        <div class="input-group-text"><span class="fas fa-user"></span></div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" name="input-password" placeholder="Mật khẩu" required>
                        <div class="input-group-text"><span class="fas fa-lock"></span></div>
                    </div>
                    
                    <div class="row align-items-center">
                        <div class="col-7">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="checkbox" id="remember" value="1">
                                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-primary w-100">Đăng nhập</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
