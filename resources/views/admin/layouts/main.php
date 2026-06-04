<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị hệ thống | CMS</title>
    
    <!-- Google Fonts: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="<?= asset('admin/css/adminlte.min.css') ?>">
    <!-- OverlayScrollbars -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/styles/overlayscrollbars.min.css">
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
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="<?= asset('admin/js/adminlte.min.js') ?>"></script>
    <script src="<?= asset('admin/js/slug.js') ?>"></script>
</body>
</html>
