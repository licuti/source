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

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Địa chỉ</label>
                                            <input type="text" name="address[<?= $c ?>]" class="form-control" value="<?= htmlspecialchars($v_addr) ?>" placeholder="VD: Số 123 Đường ABC, Quận XYZ, TP.HCM">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Giới thiệu ngắn</label>
                                            <textarea name="description[<?= $c ?>]" class="form-control" rows="4" placeholder="Mô tả ngắn gọn về cửa hàng, sản phẩm dịch vụ..."><?= htmlspecialchars($v_desc) ?></textarea>
                                        </div>

                                    </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card card-outline card-secondary shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Cài đặt nâng cao (Dùng chung)</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Đường dẫn thân thiện (Slug)</label>
                                <input type="text" name="slug" class="form-control" value="<?= $isEdit ? htmlspecialchars($firstItem->slug) : '' ?>" placeholder="VD: shopee-mall (Để trống sẽ tự tạo từ Tên gian hàng)">
                                <small class="text-muted">Dùng làm đường dẫn URL cho gian hàng: <code>/shop/ten-gian-hang</code></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Bản đồ Google Map (Iframe)</label>
                                <textarea name="map_iframe" class="form-control" rows="4" placeholder='<iframe src="https://www.google.com/maps/embed?pb=..." ...></iframe>'><?= $isEdit ? htmlspecialchars($firstItem->map_iframe) : '' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột PHẢI: Thiết lập & Đăng -->
                <div class="col-md-3">
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
                                    'path' => 'images',
                                    'label' => 'Logo Shop',
                                    'help_text' => 'Nên dùng ảnh tỷ lệ 1:1 (VD: 500x500px)'
                                ]) ?>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <?= view('admin.components.image_upload', [
                                    'name' => 'banner',
                                    'value' => $isEdit ? $firstItem->banner : '',
                                    'path' => 'images',
                                    'label' => 'Ảnh Bìa (Banner)',
                                    'help_text' => 'Nên dùng ảnh ngang (VD: 1200x400px)'
                                ]) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin liên hệ -->
                    <div class="card card-outline card-info shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin liên hệ</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Số điện thoại (Hotline)</label>
                                <input type="text" name="phone" class="form-control" value="<?= $isEdit ? htmlspecialchars($firstItem->phone) : '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= $isEdit ? htmlspecialchars($firstItem->email) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Trạng thái -->
                    <div class="card card-outline card-warning shadow-sm mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Tùy chọn hiển thị</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái duyệt</label>
                                <select name="status" class="form-select">
                                    <option value="1" <?= ($isEdit && $firstItem->status == 1) || !$isEdit ? 'selected' : '' ?>>Hoạt động (Đã duyệt)</option>
                                    <option value="2" <?= ($isEdit && $firstItem->status == 2) ? 'selected' : '' ?>>Chờ duyệt</option>
                                    <option value="0" <?= ($isEdit && $firstItem->status == 0) ? 'selected' : '' ?>>Đang khóa</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold">Số thứ tự</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= $isEdit ? $firstItem->sort_order : 0 ?>" min="0">
                            </div>
                        </div>
                        <!-- Component Save Buttons ở dưới cùng -->
                        <?= view('admin.components.save_buttons', ['list_url' => route('admin.shop.index')]) ?>
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
