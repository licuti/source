<?php
/**
 * Layout chung 3 portal LMS (T3).
 *
 * Bố cục theo mockup (Tài liệu admin/teacher/student.md):
 *   ┌───────────────────────────────────────────────┐
 *   │ Header cam #F26B2A                            │
 *   │ [logo] [ô tìm kiếm] [🔔 chuông] [avatar+ tên] │
 *   ├───────────────┬───────────────────────────────┤
 *   │ Sidebar       │  Main content                 │
 *   │ (db_lms_      │                               │
 *   │  modules)     │                               │
 *   ├───────────────┴───────────────────────────────┤
 *   │ Footer cam                                    │
 *   └───────────────────────────────────────────────┘
 *
 * Trang đăng nhập KHÔNG dùng layout này (form trang đơn — lms_login.php).
 *
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_display_name')) {
    /**
     * Tên hiển thị từ profile table theo role.
     *
     * @param LmsDb $d
     * @param array $user
     * @return string
     */
    function lms_display_name(LmsDb $d, array $user)
    {
        $map = array(
            'admin'   => 'db_lms_admins',
            'teacher' => 'db_lms_teachers',
            'student' => 'db_lms_students',
        );
        $role = isset($user['role']) ? (string) $user['role'] : '';
        if (!isset($map[$role])) {
            return isset($user['username']) ? (string) $user['username'] : 'LMS';
        }
        $row = $d->safeFetchOne(
            'select full_name from `' . $map[$role] . '` where user_id = ? limit 1',
            array((int) $user['id'])
        );
        if (!empty($row['full_name'])) {
            return (string) $row['full_name'];
        }
        return isset($user['username']) ? (string) $user['username'] : 'LMS';
    }
}

if (!function_exists('lms_avatar_text')) {
    /**
     * Chữ cái đầu cho avatar tròn.
     *
     * @param string $name
     * @return string
     */
    function lms_avatar_text($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'L';
        }
        $parts = preg_split('/\s+/u', $name);
        if (!$parts) {
            return mb_substr($name, 0, 1, 'UTF-8');
        }
        $first = (string) $parts[0];
        return mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8');
    }
}

if (!function_exists('lms_sidebar_modules')) {
    /**
     * Danh sách module hiển thị trên sidebar portal hiện tại.
     *
     * @param LmsDb $d
     * @param string $portal
     * @return array
     */
    function lms_sidebar_modules(LmsDb $d, $portal)
    {
        return $d->safeFetch(
            'select id, name, alias, icon from db_lms_modules'
            . ' where portal = ? and hien_thi = 1 order by so_thu_tu, id',
            array((string) $portal)
        );
    }
}

if (!function_exists('lms_head_assets')) {
    /**
     * <head> dùng chung: vendor + CSS LMS + meta CSRF.
     *
     * @param string $title
     * @return void
     */
    function lms_head_assets($title)
    {
        $title = (string) $title;
        $csrf = function_exists('lms_csrf_token') ? lms_csrf_token() : '';
        ?>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> — LMS MasterStudy</title>
        <meta name="lms-csrf-token" content="<?= e($csrf) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('vendor/fontawesome6/css/all.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('vendor/bootstrap/css/bootstrap.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('vendor/select2/css/select2.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('vendor/select2-bs5/css/select2-bootstrap-5-theme.min.css')) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('vendor/toastify/toastify.css')) ?>">
        <link rel="stylesheet" href="<?= e(lms_asset_url('css/lms.css')) ?>">
        <script src="<?= e(lms_asset_url('vendor/jquery/jquery.min.js')) ?>"></script>
        <script>
            if (typeof jQuery !== 'undefined') {
                if (!jQuery.isArray) jQuery.isArray = Array.isArray;
                if (!jQuery.isFunction) jQuery.isFunction = function (obj) { return typeof obj === 'function'; };
                if (!jQuery.isNumeric) jQuery.isNumeric = function (obj) { return !isNaN(parseFloat(obj)) && isFinite(obj); };
                if (!jQuery.trim) jQuery.trim = function (str) { return str == null ? '' : String(str).trim(); };
                if (!jQuery.type) jQuery.type = function (obj) { return Object.prototype.toString.call(obj).replace(/^\[object (.+)\]$/, '$1').toLowerCase(); };
            }
        </script>
        <script src="<?= e(lms_asset_url('vendor/select2/js/select2.full.min.js')) ?>"></script>
        <?php
    }
}

if (!function_exists('lms_footer_assets')) {
    /**
     * Script dùng chung cuối trang.
     *
     * @return void
     */
    function lms_footer_assets()
    {
        ?>
        <script src="<?= e(lms_asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
        <script src="<?= e(lms_asset_url('vendor/toastify/toastify.js')) ?>"></script>
        <script src="<?= e(lms_asset_url('js/lms.js')) ?>"></script>
        <?php
    }
}

if (!function_exists('lms_flash_data_div')) {
    /**
     * Hidden div chứa flash message để lms.js render thành toast.
     * Đọc + xoá flash ngay tại đây (trang login cũng dùng).
     *
     * @return void
     */
    function lms_flash_data_div()
    {
        lms_session_ensure();
        $type = '';
        $message = '';
        if (!empty($_SESSION['lms_flash']) && is_array($_SESSION['lms_flash'])) {
            $type = isset($_SESSION['lms_flash']['type']) ? (string) $_SESSION['lms_flash']['type'] : '';
            $message = isset($_SESSION['lms_flash']['message']) ? (string) $_SESSION['lms_flash']['message'] : '';
            unset($_SESSION['lms_flash']);
        }
        ?>
        <div id="lms-flash-data" hidden data-type="<?= e($type) ?>" data-message="<?= e($message) ?>"></div>
        <?php
    }
}

if (!function_exists('lms_layout_open')) {
    /**
     * Render đầu trang (doctype + head + header cam + sidebar + mở <main>).
     * Template nội dung include NGAY SAU ở scope global để thấy biến từ source.
     *
     * @param array $opts array('portal','title','active','fluid','hide_title','alert')
     *                    alert: array('message' => string) — banner toàn cục dưới header.
     * @return void
     */
    function lms_layout_open(array $opts)
    {
        $portal = isset($opts['portal']) ? (string) $opts['portal'] : _LMS_PORTAL;
        $title = isset($opts['title']) ? (string) $opts['title'] : 'LMS MasterStudy';
        $active = isset($opts['active']) ? (string) $opts['active'] : '';
        $fluid = !empty($opts['fluid']);
        $hideTitle = !empty($opts['hide_title']);
        $alert = isset($opts['alert']) && is_array($opts['alert']) ? $opts['alert'] : array();

        $d = lms_db();
        $user = lms_current_user();

        $displayName = is_array($user) ? lms_display_name($d, $user) : '';
        $avatarText = lms_avatar_text($displayName);

        $userMeta = '';
        if ($portal === 'teacher' && is_array($user) && !empty($user['id'])) {
            $teacherRow = $d->safeFetchOne(
                'select `rank` from db_lms_teachers where user_id = ? limit 1',
                array((int) $user['id'])
            );
            if (!empty($teacherRow['rank'])) {
                $userMeta = 'Hạng: ' . lms_enum_label(
                    'teacher_ranks',
                    (string) $teacherRow['rank'],
                    (string) $teacherRow['rank']
                );
            }
        }

        $unread = 0;
        $modules = lms_sidebar_modules($d, $portal);
        if (is_array($user) && !empty($user['id'])) {
            $notifSvc = new \Lms\NotificationService($d);
            $unread = $notifSvc->unreadCount((int) $user['id']);
        }
        ?>
        <!DOCTYPE html>
        <html lang="vi">
        <head>
            <?php lms_head_assets($title); ?>
        </head>
        <body>
        <?php lms_flash_data_div(); ?>

        <div class="lms-wrapper">
            <!-- ============ Header cam ============ -->
            <header class="lms-header">
                <button type="button" class="lms-icon-btn lms-sidebar-toggle d-md-none" aria-label="Mở menu">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <a href="<?= e(lms_url('')) ?>" class="lms-brand">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>MasterStudy</span>
                    <span class="lms-brand-sub d-none d-md-inline">Giáo viên riêng của bạn</span>
                </a>

                <div class="lms-search d-none d-md-block">
                    <form class="position-relative" method="get" action="<?= e(lms_url('', 'tim-kiem')) ?>">
                        <input type="search" name="search" class="form-control form-control-sm"
                               placeholder="Tìm kiếm trong LMS..." aria-label="Tìm kiếm"
                               value="<?= e(isset($_GET['search']) && (isset($_GET['lms_route']) || isset($_GET['a'])) ? trim((string) $_GET['search']) : '') ?>">
                    </form>
                </div>

                <div class="lms-header-actions">
                    <!-- Chuông thông báo (T10) -->
                    <div class="lms-dropdown">
                        <button type="button" class="lms-icon-btn lms-dropdown-toggle" id="lms-notif-btn"
                                aria-label="Thông báo" aria-haspopup="true" aria-expanded="false">
                            <i class="fa-solid fa-bell"></i>
                            <?php if ($unread > 0): ?>
                                <span class="lms-badge-dot" id="lms-notif-count"><?= $unread > 99 ? '99+' : (int) $unread ?></span>
                            <?php else: ?>
                                <span class="lms-badge-dot" id="lms-notif-count" hidden>0</span>
                            <?php endif; ?>
                        </button>
                        <div class="lms-dropdown-menu lms-notif-menu" role="menu" aria-labelledby="lms-notif-btn">
                            <div class="lms-dd-header">
                                <span>Thông báo</span>
                                <button type="button" class="btn btn-sm btn-link p-0 lms-mark-all-read"
                                        data-url="<?= e(lms_url('lms-thong-bao', 'mark_all_read')) ?>">
                                    Đánh dấu tất cả đã đọc
                                </button>
                            </div>
                            <div class="lms-dd-list" id="lms-notif-list"
                                 data-list-url="<?= e(lms_url('lms-thong-bao', 'unread')) ?>"
                                 data-mark-read-url="<?= e(lms_url('lms-thong-bao', 'mark_read')) ?>">
                                <div class="lms-dd-empty">Đang tải thông báo...</div>
                            </div>
                            <div class="lms-dd-footer">
                                <a href="<?= e(lms_url('lms-thong-bao')) ?>">Xem tất cả thông báo</a>
                            </div>
                        </div>
                    </div>

                    <!-- Avatar + tên -->
                    <div class="lms-dropdown">
                        <a href="#" class="lms-user-chip lms-dropdown-toggle" id="lms-user-btn"
                           role="button" aria-haspopup="true" aria-expanded="false">
                            <span class="lms-avatar"><?= e($avatarText) ?></span>
                            <span class="lms-user-name">
                                <?= e($displayName) ?>
                                <?php if ($userMeta !== ''): ?>
                                    <small class="lms-user-meta"><?= e($userMeta) ?></small>
                                <?php endif; ?>
                            </span>
                        </a>
                        <div class="lms-dropdown-menu lms-user-menu" role="menu" aria-labelledby="lms-user-btn">
                            <span class="lms-dd-item" style="cursor:default;">
                                <small class="text-muted d-block">Tài khoản</small>
                                <strong><?= e(isset($user['username']) ? $user['username'] : '') ?></strong>
                            </span>
                            <a class="lms-dd-item" href="<?= e(lms_url('')) ?>">
                                <i class="fa-solid fa-gauge-high me-2"></i> Tổng quan
                            </a>
                            <a class="lms-dd-item" href="<?= e(lms_url('', 'doi-mat-khau')) ?>">
                                <i class="fa-solid fa-key me-2"></i> Đổi mật khẩu
                            </a>
                            <a class="lms-dd-item" href="<?= e(lms_url('', 'logout')) ?>">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <?php if (!empty($alert['message'])): ?>
                <div class="lms-alert-banner lms-alert-banner-global" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= e((string) $alert['message']) ?></span>
                    <button type="button" class="btn-close-lms" aria-label="Đóng">&times;</button>
                </div>
            <?php endif; ?>

            <div class="lms-body">
                <div class="lms-sidebar-backdrop"></div>

                <!-- ============ Sidebar ============ -->
                <nav class="lms-sidebar" id="lms-sidebar" aria-label="Menu LMS">
                    <?php foreach ($modules as $m): ?>
                        <?php
                        $mAlias = (string) $m['alias'];
                        $mIcon = (string) $m['icon'];
                        $mName = (string) $m['name'];
                        $isActive = $mAlias === $active;
                        ?>
                        <a href="<?= e(lms_url($mAlias)) ?>"
                           class="lms-nav-link<?= $isActive ? ' active' : '' ?>"
                           <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <i class="<?= e($mIcon !== '' ? $mIcon : 'fa-solid fa-circle') ?>"></i>
                            <span><?= e($mName) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- ============ Main content ============ -->
                <main class="lms-content<?= $fluid ? ' lms-fluid' : '' ?>">
                    <?php if ($title !== '' && !$hideTitle): ?>
                        <h1 class="lms-page-title"><?= e($title) ?></h1>
                    <?php endif; ?>
        <?php
    }
}

if (!function_exists('lms_layout_close')) {
    /**
     * Render cuối trang (đóng <main> + footer cam + chat widget + script).
     *
     * @return void
     */
    function lms_layout_close()
    {
        ?>
                </main>
            </div>

            <!-- ============ Footer cam ============ -->
            <footer class="lms-footer">
                <div class="lms-footer-grid">
                    <div class="lms-footer-brand">
                        <i class="fa-solid fa-graduation-cap me-2"></i> MasterStudy
                    </div>
                    <div class="lms-footer-links">
                        <a href="<?= e(lms_url('')) ?>">Về chúng tôi</a>
                        <a href="<?= e(lms_url('')) ?>">Tuyển dụng</a>
                    </div>
                    <div class="lms-footer-contact">
                        <span><i class="fa-solid fa-location-dot me-1"></i> 181 Cao Thắng, Block B, Charmington La Pointe Building, Phường Hoà Hưng, TPHCM</span>
                        <span><i class="fa-solid fa-phone me-1"></i> 0903861248</span>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Chat widget placeholder (Phase 3+, không build logic) -->
        <button type="button" class="lms-chat-fab" id="lms-chat-fab" aria-label="Hỗ trợ trực tuyến">
            <i class="fa-solid fa-comment-dots"></i>
        </button>
        <div class="lms-chat-popover" id="lms-chat-popover">
            <strong>Hỗ trợ trực tuyến</strong>
            <p class="mb-0 mt-2 text-muted" style="font-size:.85rem;">
                Tính năng chat đang được phát triển và sẽ ra mắt sớm. Vui lòng gọi
                <strong>0903861248</strong> nếu cần hỗ trợ gấp.
            </p>
        </div>

        <?php lms_footer_assets(); ?>
        <script src="<?= e(lms_asset_url('js/lms-notif.js')) ?>"></script>
        <script>
            (function ($) {
                $('#lms-chat-fab').on('click', function () {
                    $('#lms-chat-popover').toggleClass('show');
                });
            })(jQuery);
        </script>
        </body>
        </html>
        <?php
    }
}

if (!function_exists('lms_layout_render')) {
    /**
     * Render trang hoàn chỉnh với nội dung là chuỗi HTML (dùng cho 404, các trang
     * không có source/template). Template có biến từ source thì dùng
     * lms_layout_open() + include + lms_layout_close() (xem lms_router.php).
     *
     * @param array $opts array('portal','title','active','content','fluid')
     * @return void
     */
    function lms_layout_render(array $opts)
    {
        lms_layout_open($opts);
        if (!empty($opts['content'])) {
            echo (string) $opts['content'];
        }
        lms_layout_close();
    }
}

if (!function_exists('lms_not_found')) {
    /**
     * Render trang 404 trong layout LMS.
     *
     * @param string $message
     * @return void
     */
    function lms_not_found($message = 'Trang bạn tìm không tồn tại.')
    {
        http_response_code(404);
        lms_layout_render(array(
            'portal'  => defined('_LMS_PORTAL') ? _LMS_PORTAL : 'admin',
            'title'   => '404 — Không tìm thấy trang',
            'content' => '<div class="lms-card"><div class="lms-card-body text-center py-5">'
                . '<i class="fa-solid fa-circle-question" style="font-size:2.6rem;color:#F26B2A;"></i>'
                . '<h3 class="mt-3">404</h3>'
                . '<p class="text-muted">' . e($message) . '</p>'
                . '<a href="' . e(lms_url('')) . '" class="btn-lms btn btn-primary">Về trang tổng quan</a>'
                . '</div></div>',
        ));
        exit;
    }
}
