<?php $title = 'Tích hợp API & Scripts'; ?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Tích hợp API', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= route('admin.api_integration.save') ?>" method="POST">
            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="alert alert-warning">
                        <h5><i class="icon fa-solid fa-triangle-exclamation"></i> CẢNH BÁO BẢO MẬT!</h5>
                        <small>Đây là khu vực vô cùng nhạy cảm. Bất kỳ đoạn mã (script) nào bạn nhập vào đây đều sẽ được thực thi trực tiếp trên giao diện website. Tuyệt đối không chèn mã từ các nguồn không đáng tin cậy để tránh nguy cơ bảo mật.</small>
                    </div>

                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thiết lập Mã nhúng (Scripts)</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="fw-bold"><i class="fa-brands fa-html5 text-danger"></i> Thẻ &lt;head&gt;</h5>
                                    <p class="text-muted small">Thường dùng để chèn <strong>Google Analytics, Facebook Pixel, Zalo Verify</strong> hoặc các thẻ <code>&lt;meta&gt;</code>, thẻ <code>&lt;style&gt;</code> CSS tuỳ chỉnh. Mã ở đây sẽ được ưu tiên tải trước tiên.</p>
                                </div>
                                <div class="col-md-8">
                                    <?= view('admin.components.code_editor', [
                                        'name' => 'api_head_scripts',
                                        'value' => $head_scripts ?? '',
                                        'rows' => 20,
                                        'theme' => 'dracula',
                                        'mode' => 'htmlmixed'
                                    ]) ?>
                                </div>
                            </div>
                            
                            <hr class="my-4 border-light">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="fw-bold"><i class="fa-solid fa-code text-primary"></i> Đầu thẻ &lt;body&gt;</h5>
                                    <p class="text-muted small">Khu vực này thường dùng cho thẻ dự phòng <strong>Google Tag Manager (noscript)</strong>. Đoạn mã sẽ xuất hiện ngay sau khi thẻ <code>&lt;body&gt;</code> được mở.</p>
                                </div>
                                <div class="col-md-8">
                                    <?= view('admin.components.code_editor', [
                                        'name' => 'api_body_scripts',
                                        'value' => $body_scripts ?? '',
                                        'rows' => 20,
                                        'theme' => 'dracula',
                                        'mode' => 'htmlmixed'
                                    ]) ?>
                                </div>
                            </div>
                            
                            <hr class="my-4 border-light">
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="fw-bold"><i class="fa-solid fa-terminal text-success"></i> Cuối thẻ &lt;body&gt;</h5>
                                    <p class="text-muted small">Lý tưởng để chèn các <strong>Widget Chat (Zalo, Tawk.to, Messenger)</strong> hoặc các đoạn mã JS tuỳ chỉnh. Chèn ở đây giúp trang web tải nhanh hơn do không bị block luồng render HTML.</p>
                                </div>
                                <div class="col-md-8">
                                    <?= view('admin.components.code_editor', [
                                        'name' => 'api_footer_scripts',
                                        'value' => $footer_scripts ?? '',
                                        'rows' => 20,
                                        'theme' => 'dracula',
                                        'mode' => 'htmlmixed'
                                    ]) ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4 position-sticky" style="top: 15px;">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thao tác</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="alert alert-info p-2 mb-0 border-0 shadow-none">
                                <small><i class="fa-solid fa-circle-info"></i> Hãy chắc chắn bạn đã kiểm tra mã cẩn thận trước khi lưu. Mã sai có thể làm hỏng giao diện web.</small>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fa-solid fa-save"></i> Lưu cấu hình
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

