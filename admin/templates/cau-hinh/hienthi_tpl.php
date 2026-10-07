<?php
/**
 * Cấu hình hệ thống — múi giờ, đơn vị tiền tệ, chữ ký email.
 */

$canEdit = $d->checkPermission_edit($id_module) == 1;
$MODULE  = 'cau-hinh';

/*
 * Module cần 3 bảng: cauhinh, timezones, country. Một số bản triển khai không
 * có sẵn các bảng này -> kiểm tra trước để hiện thông báo thay vì lỗi PDO.
 */
$missing = array();
foreach (array('cauhinh', 'timezones', 'country') as $table) {
    if (!count($d->o_fet("show tables like '" . $d->refix . $table . "'"))) {
        $missing[] = $d->refix . $table;
    }
}

if (!$missing && isset($_POST['capnhat']) && $canEdit) {
    $data['chu_ky_mail']  = addslashes($_POST['chu_ky_mail']);
    $data['timezones_id'] = addslashes($_POST['timezones']);
    $data['timezones']    = $d->getDataId('timezones', $data['timezones_id'], 'name')['name'];
    $data['currency']     = addslashes($_POST['currency']);

    $d->reset();
    $d->setTable('#_cauhinh');
    $d->setWhere('id', 1);

    if ($d->update($data)) {
        $d->redirect(admin_url(['p' => $MODULE, 'a' => 'man']));
    }
}

$row             = array();
$timezoneOptions = [];
$currencyOptions = [];

if (!$missing) {
    $row = $d->simple_fetch("select * from #_cauhinh where id = 1");

    foreach ($d->getData('timezones', 'id, name') as $tz) {
        $timezoneOptions[$tz['id']] = $tz['name'];
    }

    foreach ($d->getData('country', 'currency_code, currency_name') as $country) {
        if ($country['currency_code'] !== '') {
            $currencyOptions[$country['currency_code']] =
                '(' . $country['currency_code'] . ') ' . $country['currency_name'];
        }
    }
}
?>

<?php component('page_header', [
    'title'      => 'Cấu hình hệ thống',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Dữ liệu'],
        ['label' => 'Cấu hình hệ thống'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <?php if ($missing): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>
                    Chưa có bảng dữ liệu: <strong><?= e(implode(', ', $missing)) ?></strong>.
                    Vui lòng nhập cấu trúc database trước khi dùng chức năng này.
                </span>
            </div>
        <?php endif; ?>

        <form method="post" action="" data-warn-unsaved>
            <div class="row">
                <div class="col-xl-9">
                    <?php component('card_open'); ?>

                        <?php
                        component('form/select', [
                            'label'   => 'Múi giờ',
                            'name'    => 'timezones',
                            'id'      => 'timezones',
                            'class'   => 'select2',
                            'value'   => $row['timezones_id'] ?? '',
                            'options' => $timezoneOptions,
                            'hint'    => 'Múi giờ mặc định dùng cho toàn hệ thống.',
                        ]);

                        component('form/select', [
                            'label'   => 'Đơn vị tiền tệ',
                            'name'    => 'currency',
                            'id'      => 'currency',
                            'class'   => 'select2',
                            'value'   => $row['currency'] ?? '',
                            'options' => $currencyOptions,
                        ]);

                        component('form/editor', [
                            'label'   => 'Chữ ký email',
                            'name'    => 'chu_ky_mail',
                            'id'      => 'chu_ky_mail',
                            'value'   => $row['chu_ky_mail'] ?? '',
                            'height'  => '250px',
                            'toolbar' => 'Basic',
                            'hint'    => 'Nội dung chèn cuối các email hệ thống gửi đi.',
                        ]);
                        ?>

                    <?php component('card_close'); ?>

                    <?php component('form/actions_bar', [
                        'can_edit'  => $canEdit && !$missing,
                        'save_edit' => false,
                        'sticky'    => false,
                    ]); ?>
                </div>
            </div>
        </form>
    </div>
</div>
