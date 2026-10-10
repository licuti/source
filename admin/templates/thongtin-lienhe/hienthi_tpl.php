<?php
/**
 * Thông tin liên hệ — cấu hình site (#_thongtin).
 */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$MODULE    = 'thongtin-lienhe';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;
$rowByLang = $rowByLang ?? [];

if (empty($rowByLang)) {
    foreach ($langs as $lang) {
        $code = $lang['code'];
        $row  = $d->simple_fetch("select * from #_thongtin where lang = '" . $code . "'");
        $rowByLang[$code] = is_array($row) ? $row : [];
    }
}

$actionUrl = admin_url(['p' => $MODULE, 'a' => 'save']);
$firstCode = $langs[0]['code'] ?? 'vi';
$defaultRow = $rowByLang[$firstCode] ?? [];
?>

<?php component('page_header', [
    'title'      => 'Thông tin liên hệ',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Cấu hình website'],
        ['label' => 'Thông tin liên hệ'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form method="post" action="<?= e($actionUrl) ?>" data-warn-unsaved>
            <div class="row">
                <div class="col-lg-9">
                    <ul class="nav nav-pills mb-4" id="main-config-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-company-tab" data-bs-toggle="pill" data-bs-target="#tab-company" type="button" role="tab" aria-selected="true"><i class="fa-solid fa-building me-1"></i> Thông tin công ty</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-social-tab" data-bs-toggle="pill" data-bs-target="#tab-social" type="button" role="tab" aria-selected="false"><i class="fa-solid fa-share-nodes me-1"></i> Mạng xã hội & Bản đồ</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-system-tab" data-bs-toggle="pill" data-bs-target="#tab-system" type="button" role="tab" aria-selected="false"><i class="fa-solid fa-cogs me-1"></i> Cấu hình hệ thống</button>
                </li>
            </ul>

            <div class="tab-content" id="main-config-tabsContent">
                <!-- TAB 1: THÔNG TIN CÔNG TY (Đa ngôn ngữ) -->
                <div class="tab-pane fade show active" id="tab-company" role="tabpanel" aria-labelledby="tab-company-tab">
                    <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'ttl']); ?>
                    
                    <div class="tab-content mt-3">
                        <?php foreach ($langs as $i => $lang):
                            $code = $lang['code'];
                            $row  = $rowByLang[$code] ?? [];
                            $sfx  = $multiLang ? ' (' . $code . ')' : '';
                            ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                 id="ttl-<?= e($code) ?>" role="tabpanel">

                                <div class="row g-3">
                                    <div class="col-lg-12">
                                        <?php component('card_open', [
                                            'title' => 'Thông tin công ty' . $sfx,
                                            'icon'  => 'fa-solid fa-building',
                                        ]); ?>

                                        <div class="d-flex flex-column gap-3">
                                            <?php component('form/input', [
                                                'label' => 'Tên công ty', 'name' => 'company[]',
                                                'id' => 'company_' . $code, 'value' => $row['company'] ?? '',
                                                'placeholder' => 'Tên công ty / thương hiệu',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Thời gian làm việc', 'name' => 'thoi_gian[]',
                                                'id' => 'thoi_gian_' . $code, 'value' => $row['thoi_gian'] ?? '',
                                                'placeholder' => 'VD: 8:00 – 17:30 (T2–T7)',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Hotline', 'name' => 'hotline[]',
                                                'id' => 'hotline_' . $code, 'value' => $row['hotline'] ?? '',
                                                'placeholder' => 'Số hotline',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Điện thoại', 'name' => 'dien_thoai[]',
                                                'id' => 'dien_thoai_' . $code, 'value' => $row['dien_thoai'] ?? '',
                                                'placeholder' => 'Số điện thoại',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Email', 'name' => 'email[]',
                                                'id' => 'email_' . $code, 'value' => $row['email'] ?? '',
                                                'type' => 'email', 'placeholder' => 'email@domain.com',
                                            ]); ?>
                                            <?php component('form/textarea', [
                                                'label' => 'Địa chỉ', 'name' => 'address[]',
                                                'id' => 'address_' . $code, 'value' => $row['address'] ?? '',
                                                'rows' => 2, 'placeholder' => 'Địa chỉ trụ sở / giao dịch',
                                            ]); ?>
                                            <?php component('form/textarea', [
                                                'label' => 'Văn phòng', 'name' => 'office[]',
                                                'id' => 'office_' . $code, 'value' => $row['office'] ?? '',
                                                'rows' => 2, 'placeholder' => 'Địa chỉ văn phòng (nếu có)',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Website', 'name' => 'website[]',
                                                'id' => 'website_' . $code, 'value' => $row['website'] ?? '',
                                                'placeholder' => 'https://…',
                                            ]); ?>
                                            <?php component('form/input', [
                                                'label' => 'Copyright', 'name' => 'coppy_right[]',
                                                'id' => 'coppy_right_' . $code, 'value' => $row['coppy_right'] ?? '',
                                                'placeholder' => '© 2024 Tên công ty',
                                            ]); ?>
                                        </div>

                                        <?php component('card_close'); ?>
                                    </div>

                                    <div class="col-lg-12">
                                        <?php component('card_open', [
                                            'title' => 'Biểu tượng' . $sfx,
                                            'icon'  => 'fa-solid fa-image',
                                        ]); ?>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <?php component('form/image_picker', [
                                                    'label'  => 'Favicon',
                                                    'name'   => 'favicon[]',
                                                    'id'     => 'favicon_' . $code,
                                                    'value'  => $row['favicon'] ?? '',
                                                    'height' => 80,
                                                    'hint'   => 'Khuyến nghị .ico / png vuông nhỏ',
                                                ]); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?php component('form/image_picker', [
                                                    'label'  => 'Ảnh share (Open Graph)',
                                                    'name'   => 'icon_share[]',
                                                    'id'     => 'icon_share_' . $code,
                                                    'value'  => $row['icon_share'] ?? '',
                                                    'height' => 120,
                                                    'hint'   => 'Gợi ý ≥ 1200×630',
                                                ]); ?>
                                            </div>
                                        </div>
                                        <?php component('card_close'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- TAB 2: MẠNG XÃ HỘI & BẢN ĐỒ (Không đa ngôn ngữ) -->
                <div class="tab-pane fade" id="tab-social" role="tabpanel" aria-labelledby="tab-social-tab">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <?php component('card_open', [
                                'title' => 'Mạng xã hội & kênh liên hệ',
                                'icon'  => 'fa-solid fa-share-nodes',
                            ]); ?>
                            <div class="row g-2">
                                <?php
                                $socials = [
                                    'facebook'  => ['Facebook', 'fab fa-facebook'],
                                    'messenger' => ['Messenger', 'fab fa-facebook-messenger'],
                                    'zalo'      => ['Zalo', 'fa-solid fa-comment'],
                                    'youtube'   => ['YouTube', 'fab fa-youtube'],
                                    'tiktok'    => ['TikTok', 'fab fa-tiktok'],
                                    'instagram' => ['Instagram', 'fab fa-instagram'],
                                    'twitter'   => ['Twitter / X', 'fab fa-x-twitter'],
                                    'linkedin'  => ['LinkedIn', 'fab fa-linkedin'],
                                    'pinterest' => ['Pinterest', 'fab fa-pinterest'],
                                    'telegram'  => ['Telegram', 'fab fa-telegram'],
                                    'whatsapp'  => ['WhatsApp', 'fab fa-whatsapp'],
                                    'skype'     => ['Skype', 'fab fa-skype'],
                                    'shoppe'    => ['Shopee', 'fa-solid fa-bag-shopping'],
                                ];
                                foreach ($socials as $fname => $meta):
                                    [$label, $icon] = $meta;
                                    ?>
                                    <div class="col-md-6">
                                        <?php component('form/input', [
                                            'label' => $label,
                                            'name'  => $fname,
                                            'id'    => $fname,
                                            'value' => $defaultRow[$fname] ?? '',
                                            'placeholder' => 'URL / số / ID ' . $label,
                                        ]); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php component('card_close'); ?>
                        </div>

                        <div class="col-lg-5">
                            <?php component('card_open', [
                                'title' => 'Bản đồ & nhúng',
                                'icon'  => 'fa-solid fa-map-location-dot',
                            ]); ?>
                            <?php component('form/textarea', [
                                'label' => 'Mã nhúng Google Map (iframe)', 'name' => 'map',
                                'id' => 'map', 'value' => $defaultRow['map'] ?? '',
                                'rows' => 4, 'placeholder' => '<iframe …>',
                                'attr' => ['data-ace-editor' => '1', 'data-ace-mode' => 'html', 'data-ace-height' => '140'],
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Link mở map', 'name' => 'link_map',
                                'id' => 'link_map', 'value' => $defaultRow['link_map'] ?? '',
                                'placeholder' => 'https://maps.google.com/…',
                            ]); ?>
                            <?php component('form/textarea', [
                                'label' => 'Iframe khác (chat, widget…)', 'name' => 'iframe',
                                'id' => 'iframe', 'value' => $defaultRow['iframe'] ?? '',
                                'rows' => 3,
                                'attr' => ['data-ace-editor' => '1', 'data-ace-mode' => 'html', 'data-ace-height' => '120'],
                            ]); ?>
                            <?php component('card_close'); ?>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: CẤU HÌNH HỆ THỐNG (Không đa ngôn ngữ) -->
                <div class="tab-pane fade" id="tab-system" role="tabpanel" aria-labelledby="tab-system-tab">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <?php component('card_open', [
                                'title' => 'Google reCAPTCHA',
                                'icon'  => 'fa-solid fa-shield-halved',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Site key', 'name' => 'site_key',
                                'id' => 'site_key', 'value' => $defaultRow['site_key'] ?? '',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Secret key', 'name' => 'secret_key',
                                'id' => 'secret_key', 'value' => $defaultRow['secret_key'] ?? '',
                            ]); ?>
                            <?php component('card_close'); ?>
                        </div>

                        <div class="col-lg-6">
                            <?php component('card_open', [
                                'title' => 'Cấu hình SMTP Mail',
                                'icon'  => 'fa-solid fa-envelope',
                            ]); ?>
                            <p class="text-muted small mb-3"><i>Lưu ý: Nếu để trống, hệ thống sẽ sử dụng cấu hình mặc định trong file <code>.env</code>.</i></p>
                            <?php component('form/input', [
                                'label' => 'SMTP Host', 'name' => 'smtp_host',
                                'id' => 'smtp_host', 'value' => $defaultRow['smtp_host'] ?? '',
                                'placeholder' => 'VD: smtp.gmail.com',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'SMTP Port', 'name' => 'smtp_port',
                                'id' => 'smtp_port', 'value' => $defaultRow['smtp_port'] ?? '',
                                'placeholder' => 'VD: 465 hoặc 587',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Tài khoản SMTP (User)', 'name' => 'smtp_user',
                                'id' => 'smtp_user', 'value' => $defaultRow['smtp_user'] ?? '',
                                'placeholder' => 'email@example.com',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Mật khẩu SMTP (Pass)', 'name' => 'smtp_pass',
                                'id' => 'smtp_pass', 'value' => $defaultRow['smtp_pass'] ?? '',
                                'placeholder' => 'Mật khẩu ứng dụng hoặc mật khẩu email',
                                'type' => 'password',
                            ]); ?>
                            <?php component('form/input', [
                                'label' => 'Bảo mật (Secure)', 'name' => 'smtp_secure',
                                'id' => 'smtp_secure', 'value' => $defaultRow['smtp_secure'] ?? '',
                                'placeholder' => 'VD: ssl, tls (Thường cổng 465 -> ssl, 587 -> tls)',
                            ]); ?>
                            <?php component('card_close'); ?>
                        </div>
                    </div>
                </div>



            </div>
                </div>

                <div class="col-lg-3">
                    <?php if ($canEdit): ?>
                        <div class="sticky-top" style="top: 80px;">
                            <?php component('form/actions_bar', [
                                'back_url'   => '',
                                'can_edit'   => true,
                                'save_label' => 'Cập nhật',
                                'save_edit'  => false,
                                'sticky'     => true,
                            ]); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="js/ace-editor.js"></script>
