<?php
$title = "Cấu hình SEO cơ bản";
$langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình hệ thống', 'url' => '#'],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= route('admin.seo_config.save') ?>" method="POST">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary mb-4">
                        <?php if (count($langs) > 1): ?>
                        <div class="card-header p-0 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#pane-<?= $lang['code'] ?>"
                                        type="button" role="tab">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Nội dung thẻ Meta Mặc định</h5>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $lang): 
                                    $c = $lang['code'];
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="pane-<?= $c ?>" role="tabpanel">
                                    
                                    <?= view('admin.components.input', [
                                        'name' => "seo_title[$c]",
                                        'label' => 'Thẻ Tiêu đề (SEO Title)',
                                        'value' => \App\Models\OptionModel::getValue("seo_title_$c", ''),
                                        'help' => 'Khuyến cáo: Từ 50 - 65 ký tự.'
                                    ]) ?>
                                    
                                    <?= view('admin.components.input', [
                                        'name' => "seo_keyword[$c]",
                                        'label' => 'Thẻ Từ khóa (SEO Keywords)',
                                        'value' => \App\Models\OptionModel::getValue("seo_keyword_$c", ''),
                                        'help' => 'Ngăn cách các từ khóa bằng dấu phẩy.'
                                    ]) ?>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Thẻ Mô tả (SEO Description)</label>
                                        <textarea name="seo_description[<?= $c ?>]" class="form-control" rows="3"><?= htmlspecialchars(\App\Models\OptionModel::getValue("seo_description_$c", '')) ?></textarea>
                                        <div class="form-text text-muted">Khuyến cáo: Từ 150 - 160 ký tự. Cố gắng chứa từ khóa quan trọng ở đầu đoạn mô tả.</div>
                                    </div>
                                    
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Mạng xã hội & Khác</h5>
                        </div>
                        <div class="card-body">
                            
                            <?= view('admin.components.input', [
                                'name' => "seo_facebook_app_id",
                                'label' => 'Facebook App ID',
                                'value' => \App\Models\OptionModel::getValue("seo_facebook_app_id", ''),
                                'help' => 'Phục vụ cho thẻ fb:app_id kết nối FB Insights.'
                            ]) ?>
                            
                            <?= view('admin.components.input', [
                                'name' => "seo_twitter_site",
                                'label' => 'Twitter Site (@username)',
                                'value' => \App\Models\OptionModel::getValue("seo_twitter_site", ''),
                                'help' => 'Ví dụ: @yourcompany'
                            ]) ?>
                            
                            <div class="mb-3">
                                <?= view('admin.components.image_upload', [
                                    'name' => "seo_image",
                                    'value' => \App\Models\OptionModel::getValue("seo_image", ''),
                                    'label' => 'Chọn ảnh OG:Image'
                                ]) ?>
                                <div class="form-text text-muted mt-2">Ảnh hiển thị khi chia sẻ link lên Zalo, Facebook nếu bài viết không có ảnh đại diện. Tỷ lệ chuẩn: 1200x630px.</div>
                            </div>
                            
                            <hr>
                            <h6 class="fw-bold"><i class="fa-solid fa-user-secret text-danger"></i> Quyền riêng tư</h6>
                            <?= view('admin.components.switch', [
                                'name' => 'seo_noindex',
                                'label' => 'Ngăn chặn các công cụ tìm kiếm đánh chỉ mục trang web này',
                                'checked' => \App\Models\OptionModel::getValue('seo_noindex', '0') == '1',
                                'help_text' => 'Bật tùy chọn này sẽ thêm thẻ <meta name="robots" content="noindex, nofollow"> vào mã nguồn. Nó tùy thuộc vào các công cụ tìm kiếm để tôn trọng yêu cầu này.'
                            ]) ?>

                        </div>
                        <?= view('admin.components.save_buttons', [
                            'buttons' => [
                                [
                                    'type' => 'submit',
                                    'name' => 'save_action',
                                    'value' => 'exit',
                                    'class' => 'btn btn-primary btn-sm',
                                    'icon' => 'fa-solid fa-save',
                                    'text' => 'Lưu cấu hình SEO'
                                ]
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
