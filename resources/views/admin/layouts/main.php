<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title>Quản trị hệ thống | CMS</title>
    
    <!-- Google Fonts: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="<?= asset('admin/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('admin/css/custom.css') ?>">
    <!-- Nestable2 CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.css">
    <!-- SweetAlert2 & Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- OverlayScrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/styles/overlayscrollbars.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Pickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css" />
    <!-- jQuery (Load in head for inline scripts) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <!-- Header -->
        <?= view('admin.partials.header') ?>
        
        <!-- Sidebar -->
        <?= view('admin.partials.sidebar') ?>
        
        <!-- Main Content -->
        <main class="app-main">
            <!-- Khu vực chứa nội dung động của từng trang -->
            <?= $content ?? '' ?>
        </main>
        
        <!-- Footer -->
        <?= view('admin.partials.footer') ?>
    </div>

    <!-- Core Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
    <script src="<?= asset('admin/js/adminlte.min.js') ?>"></script>
    <script src="<?= asset('admin/js/slug.js') ?>"></script>
    <script src="<?= asset('admin/js/common.js') ?>"></script>
    
    <!-- Notify Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/11.7.32/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="<?= asset('admin/js/notify.js') ?>"></script>

    <!-- Global Flash Messages -->
    <?php if ($msg = session('success')): ?>
        <script>document.addEventListener("DOMContentLoaded", function() { AppNotify.success("<?= htmlspecialchars(addslashes($msg)) ?>"); });</script>
    <?php endif; ?>
    <?php if ($msg = session('error')): ?>
        <script>document.addEventListener("DOMContentLoaded", function() { AppNotify.error("<?= htmlspecialchars(addslashes($msg)) ?>"); });</script>
    <?php endif; ?>
    <?php if ($msg = session('warning')): ?>
        <script>document.addEventListener("DOMContentLoaded", function() { AppNotify.warning("<?= htmlspecialchars(addslashes($msg)) ?>"); });</script>
    <?php endif; ?>
    <?php if ($msg = session('info')): ?>
        <script>document.addEventListener("DOMContentLoaded", function() { AppNotify.info("<?= htmlspecialchars(addslashes($msg)) ?>"); });</script>
    <?php endif; ?>
</body>
</html>
