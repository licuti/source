<?php
/**
 * Thông tin user — hồ sơ tài khoản đang đăng nhập (self-service).
 */
$MODULE = 'thongtin-user';
$row = array();
if (isset($userRow) && is_array($userRow) && !empty($userRow)) {
    $row = $userRow;
} elseif (isset($items[0]) && is_array($items[0])) {
    $row = $items[0];
}
$uid = isset($row['id']) ? (int) $row['id'] : (isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0);
$actionUrl = admin_url(array('p' => $MODULE, 'a' => 'save'));

$quyenLabel = '';
if (isset($row['quyen_han'])) {
    $qh = (int) $row['quyen_han'];
    if ($qh === 1) {
        $quyenLabel = 'Administrator';
    } elseif ($qh === 2) {
        $quyenLabel = 'Quản trị viên';
    } else {
        $quyenLabel = 'Quyền #' . $qh;
    }
}
if (!empty($row['is_admin'])) {
    $quyenLabel = ($quyenLabel !== '' ? $quyenLabel . ' · ' : '') . 'Super admin';
}
$statusLabel = (isset($row['hien_thi']) && (int) $row['hien_thi'] === 1) ? 'Đang hoạt động' : 'Đã ẩn';
?>

<?php component('page_header', array(
    'title'      => 'Thông tin tài khoản',
    'breadcrumb' => array(
        array('label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'),
        array('label' => 'Tài khoản'),
        array('label' => 'Thông tin user'),
    ),
)); ?>

<div class="app-content">
    <div class="container-fluid">
        <form method="post" action="<?= e($actionUrl) ?>" data-warn-unsaved id="form-thongtin-user" autocomplete="off">
            <?= function_exists('admin_csrf_field') ? admin_csrf_field() : '' ?>

            <div class="row g-3">
                <div class="col-lg-4">
                    <?php component('card_open', array(
                        'title' => 'Ảnh đại diện',
                        'icon'  => 'fa-solid fa-image',
                    )); ?>
                    <?php component('form/image_picker', array(
                        'label'  => 'Hình ảnh',
                        'name'   => 'hinh_anh',
                        'id'     => 'hinh_anh',
                        'value'  => isset($row['hinh_anh']) ? $row['hinh_anh'] : '',
                        'table'  => '_user',
                        'row_id' => $uid,
                        'height' => 180,
                        'hint'   => 'Ảnh vuông, khuyến nghị ≥ 200×200',
                    )); ?>
                    <?php component('card_close'); ?>

                    <?php component('card_open', array(
                        'title' => 'Tài khoản',
                        'icon'  => 'fa-solid fa-user-lock',
                    )); ?>
                    <?php component('form/input', array(
                        'label'    => 'Tên đăng nhập',
                        'name'     => '',
                        'id'       => 'tai_khoan',
                        'value'    => isset($row['tai_khoan']) ? $row['tai_khoan'] : '',
                        'readonly' => true,
                        'hint'     => 'Không thể đổi tên đăng nhập tại đây',
                    )); ?>
                    <?php if ($quyenLabel !== ''): ?>
                        <?php component('form/input', array(
                            'label'    => 'Quyền',
                            'name'     => '',
                            'id'       => 'quyen_han_display',
                            'value'    => $quyenLabel,
                            'readonly' => true,
                        )); ?>
                    <?php endif; ?>
                    <?php component('form/input', array(
                        'label'    => 'Trạng thái',
                        'name'     => '',
                        'id'       => 'hien_thi_display',
                        'value'    => $statusLabel,
                        'readonly' => true,
                    )); ?>
                    <?php component('card_close'); ?>
                </div>

                <div class="col-lg-8">
                    <?php component('card_open', array(
                        'title' => 'Thông tin cá nhân',
                        'icon'  => 'fa-solid fa-id-card',
                    )); ?>
                    <?php component('form/input', array(
                        'label'       => 'Họ tên',
                        'name'        => 'ho_ten',
                        'id'          => 'ho_ten',
                        'value'       => isset($row['ho_ten']) ? $row['ho_ten'] : '',
                        'required'    => true,
                        'placeholder' => 'Họ và tên hiển thị',
                    )); ?>
                    <?php component('form/input', array(
                        'label'       => 'Email',
                        'name'        => 'email',
                        'id'          => 'email',
                        'type'        => 'email',
                        'value'       => isset($row['email']) ? $row['email'] : '',
                        'placeholder' => 'email@domain.com',
                    )); ?>
                    <?php component('form/editor', array(
                        'label'   => 'Giới thiệu bản thân',
                        'name'    => 'noi_dung',
                        'id'      => 'noi_dung',
                        'value'   => isset($row['noi_dung']) ? $row['noi_dung'] : '',
                        'height'  => '200px',
                        'toolbar' => 'Basic',
                    )); ?>
                    <?php component('card_close'); ?>

                    <?php component('card_open', array(
                        'title' => 'Đổi mật khẩu',
                        'icon'  => 'fa-solid fa-key',
                    )); ?>
                    <p class="text-muted small mb-3">
                        Để trống nếu không muốn đổi mật khẩu.
                        Khi đổi mật khẩu, hồ sơ (họ tên, email, ảnh…) cũng được lưu và bạn sẽ được yêu cầu đăng nhập lại.
                    </p>
                    <div id="tt-user-pass-err" class="alert alert-danger py-2 px-3 d-none" role="alert"></div>
                    <?php component('form/input', array(
                        'label'       => 'Mật khẩu cũ',
                        'name'        => 'old-password',
                        'id'          => 'old-password',
                        'type'        => 'password',
                        'value'       => '',
                        'placeholder' => 'Nhập mật khẩu hiện tại',
                        'attr'        => array('autocomplete' => 'current-password'),
                    )); ?>
                    <?php component('form/input', array(
                        'label'       => 'Mật khẩu mới',
                        'name'        => 'password',
                        'id'          => 'password',
                        'type'        => 'password',
                        'value'       => '',
                        'placeholder' => '8-20 ký tự',
                        'attr'        => array('autocomplete' => 'new-password'),
                        'hint'        => 'Bắt buộc có chữ thường, chữ HOA, số và ký tự đặc biệt (!@#$%^&*). Ví dụ: Phuong24!#',
                    )); ?>
                    <?php component('form/input', array(
                        'label'       => 'Nhập lại mật khẩu',
                        'name'        => 'cfpassword',
                        'id'          => 'cfpassword',
                        'type'        => 'password',
                        'value'       => '',
                        'placeholder' => 'Nhập lại mật khẩu mới',
                        'attr'        => array('autocomplete' => 'new-password'),
                    )); ?>
                    <?php component('card_close'); ?>
                </div>
            </div>

            <div class="mt-3">
                <?php component('form/actions_bar', array(
                    'back_url'   => '',
                    'can_edit'   => true,
                    'save_label' => 'Cập nhật',
                    'save_edit'  => false,
                    'sticky'     => true,
                )); ?>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('form-thongtin-user');
    if (!form) return;

    var errBox = document.getElementById('tt-user-pass-err');
    var passRe = /^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%^&*])(?=.*[A-Z]).{8,20}$/;

    function showErr(msg, focusId) {
        if (errBox) {
            errBox.textContent = msg;
            errBox.classList.remove('d-none');
        }
        if (window.AdminUI && AdminUI.notify) {
            AdminUI.notify.error(msg);
        }
        if (focusId) {
            var el = document.getElementById(focusId);
            if (el) el.focus();
        }
    }

    function hideErr() {
        if (errBox) {
            errBox.textContent = '';
            errBox.classList.add('d-none');
        }
    }

    form.addEventListener('submit', function (e) {
        hideErr();
        // Đồng bộ CKEditor vào textarea trước khi gửi
        if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.noi_dung) {
            try { CKEDITOR.instances.noi_dung.updateElement(); } catch (err) {}
        }

        var password = (document.getElementById('password') || {}).value || '';
        var oldPass  = (document.getElementById('old-password') || {}).value || '';
        var rePass   = (document.getElementById('cfpassword') || {}).value || '';

        if (password === '' && oldPass === '' && rePass === '') {
            return true;
        }

        if (oldPass === '') {
            e.preventDefault();
            showErr('Chưa nhập mật khẩu cũ', 'old-password');
            return false;
        }
        if (password === '') {
            e.preventDefault();
            showErr('Chưa nhập mật khẩu mới', 'password');
            return false;
        }
        if (!passRe.test(password)) {
            e.preventDefault();
            showErr('Mật khẩu mới chưa đúng định dạng', 'password');
            return false;
        }
        if (rePass === '') {
            e.preventDefault();
            showErr('Chưa nhập lại mật khẩu', 'cfpassword');
            return false;
        }
        if (password !== rePass) {
            e.preventDefault();
            showErr('Mật khẩu nhập lại không đúng', 'cfpassword');
            return false;
        }
        if (oldPass === password) {
            e.preventDefault();
            showErr('Mật khẩu mới phải khác mật khẩu cũ', 'password');
            return false;
        }
        return true;
    });
})();
</script>