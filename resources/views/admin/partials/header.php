<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <!-- Bật tắt sidebar -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="fa-solid fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="/" target="_blank" class="nav-link">Xem Website</a>
            </li>
        </ul>

        <!-- Menu người dùng -->
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="<?= asset('admin/assets/img/user2-160x160.jpg') ?>" onerror="this.src='https://ui-avatars.com/api/?name=Admin'" class="user-image rounded-circle shadow" alt="User Image">
                    <span class="d-none d-md-inline">Administrator</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <li class="user-header text-bg-primary">
                        <img src="https://ui-avatars.com/api/?name=Admin" class="rounded-circle shadow" alt="User Image">
                        <p>
                            Administrator
                            <small>Quản trị viên cấp cao</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <a href="#" class="btn btn-default btn-flat">Hồ sơ</a>
                        <a href="<?= route('admin.logout') ?>" class="btn btn-default btn-flat float-end">Đăng xuất</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
