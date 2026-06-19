<?php
$isEdit = isset($firstItem);
$title = $isEdit ? "Cập nhật Gian hàng: " . htmlspecialchars($firstItem->name) : "Thêm Gian hàng mới";
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Gian hàng', 'url' => route('admin.shop.index')],
        ['name' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.shop.update', ['id' => $firstItem->id_code]) : route('admin.shop.store') ?>" method="POST" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-9">
                    <!-- LANGUAGE TABS -->
                    <div class="card card-outline card-primary mb-4">
                        <?php if (count($langs) > 1): ?>
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
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
                            <h5 class="card-title mb-0 fw-bold">Thông tin chính</h5>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $lang): 
                                    $c = $lang['code'];
                                    $v_name = $_POST['name'][$c] ?? ($isEdit ? ($firstItem->lang_data[$c]['name'] ?? '') : '');
                                    $v_desc = $_POST['description'][$c] ?? ($isEdit ? ($firstItem->lang_data[$c]['description'] ?? '') : '');
                                    $v_addr = $_POST['address'][$c] ?? ($isEdit ? ($firstItem->lang_data[$c]['address'] ?? '') : '');
                                ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="pane-<?= $c ?>" role="tabpanel" tabindex="0">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Tên gian hàng <span class="text-danger">*</span></label>
                                            <input type="text" name="name[<?= $c ?>]" class="form-control" value="<?= htmlspecialchars($v_name) ?>" <?= $i === 0 ? 'required' : '' ?> placeholder="VD: Apple Store">
                                            <?php if ($i === 0): ?>
                                                <div class="invalid-feedback">Vui lòng nhập tên gian hàng (Ngôn ngữ chính).</div>
                                            <?php endif; ?>
                                        </div>

                                        <?= view('admin.components.input', [
                                            'name' => "address[$c]",
                                            'value' => $v_addr,
                                            'label' => 'Địa chỉ',
                                            'attrs' => ['placeholder' => 'VD: Số 123 Đường ABC, Quận XYZ, TP.HCM']
                                        ]) ?>

                                        <?= view('admin.components.textarea', [
                                            'name' => "description[$c]",
                                            'value' => $v_desc,
                                            'label' => 'Giới thiệu ngắn',
                                            'attrs' => ['rows' => 4, 'placeholder' => 'Mô tả ngắn gọn về cửa hàng, sản phẩm dịch vụ...']
                                        ]) ?>

                                    </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card card-outline card-secondary shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Cài đặt & Liên hệ (Dùng chung)</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <?= view('admin.components.input', [
                                        'name' => 'phone',
                                        'value' => $isEdit ? $firstItem->phone : '',
                                        'label' => 'Số điện thoại (Hotline)'
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= view('admin.components.input', [
                                        'type' => 'email',
                                        'name' => 'email',
                                        'value' => $isEdit ? $firstItem->email : '',
                                        'label' => 'Email'
                                    ]) ?>
                                </div>
                            </div>
                            
                            <?= view('admin.components.input', [
                                'name' => 'slug',
                                'value' => $isEdit ? $firstItem->slug : '',
                                'label' => 'Đường dẫn thân thiện (Slug)',
                                'help_text' => 'Dùng làm đường dẫn URL cho gian hàng: /shop/ten-gian-hang',
                                'attrs' => ['placeholder' => 'VD: shopee-mall (Để trống sẽ tự tạo từ Tên gian hàng)']
                            ]) ?>
                            
                            <?= view('admin.components.textarea', [
                                'name' => 'map_iframe',
                                'value' => $isEdit ? $firstItem->map_iframe : '',
                                'label' => 'Bản đồ Google Map (Iframe)',
                                'attrs' => [
                                    'rows' => 4,
                                    'placeholder' => '<iframe src="https://www.google.com/maps/embed?pb=..." ...></iframe>'
                                ]
                            ]) ?>
                        </div>
                    </div>
                </div>

                <!-- Cột PHẢI: Thiết lập & Đăng -->
                <div class="col-md-3">
                    
                    <!-- Nút Lưu (Chuyển lên đầu) -->
                    <div class="card card-outline card-success shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fa-solid fa-gears text-secondary"></i> Tùy chọn hiển thị
                            </h3>
                        </div>
                        <div class="card-body">
                                <?= view('admin.components.select', [
                                    'name' => 'status',
                                    'value' => $isEdit ? $firstItem->status : 1,
                                    'label' => 'Trạng thái duyệt',
                                    'options' => [
                                        1 => 'Hoạt động (Đã duyệt)',
                                        2 => 'Chờ duyệt',
                                        0 => 'Đang khóa'
                                    ]
                                ]) ?>
                            <div class="mb-0">
                                <?= view('admin.components.input', [
                                    'type' => 'number',
                                    'name' => 'sort_order',
                                    'value' => $isEdit ? $firstItem->sort_order : 0,
                                    'label' => 'Số thứ tự',
                                    'attrs' => ['min' => '0']
                                ]) ?>
                            </div>
                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.shop.index')]) ?>
                    </div>

                    <!-- Ảnh đại diện -->
                    <div class="card card-outline card-success shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Hình ảnh</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <?= view('admin.components.image_upload', [
                                    'name' => 'logo',
                                    'value' => $isEdit ? $firstItem->logo : '',
                                    'label' => 'Logo Shop',
                                    'help_text' => 'Nên dùng ảnh tỷ lệ 1:1 (VD: 500x500px)'
                                ]) ?>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <?= view('admin.components.image_upload', [
                                    'name' => 'banner',
                                    'value' => $isEdit ? $firstItem->banner : '',
                                    'label' => 'Ảnh Bìa (Banner)',
                                    'help_text' => 'Nên dùng ảnh ngang (VD: 1200x400px)'
                                ]) ?>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Validation cơ bản cho tab
        const form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Nếu lỗi nằm ở tab đang ẩn, tự động switch qua tab đó
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    const tabPane = firstInvalid.closest('.tab-pane');
                    if (tabPane) {
                        const tabId = tabPane.getAttribute('id');
                        const tabLink = document.querySelector(`a[href="#${tabId}"]`);
                        if (tabLink) {
                            var tab = new bootstrap.Tab(tabLink);
                            tab.show();
                        }
                    }
                }
            }
            form.classList.add('was-validated');
        }, false);
    });
</script>
