<?php
$title = $title ?? 'Thêm Biểu Phí Thuế';
$isEdit = !empty($item['id']);
$taxClasses = $taxClasses ?? [];
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thương mại điện tử', 'url' => '#'],
        ['name' => 'Quản lý Biểu Phí Thuế', 'url' => route('admin.tax_rate.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.tax_rate.update', ['id' => $item['id']]) : route('admin.tax_rate.store') ?>" method="POST">
            
            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin Biểu phí</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <?= view('admin.components.input', [
                                    'name' => "name",
                                    'value' => $item['name'] ?? '',
                                    'label' => 'Tên hiển thị (Tên Biểu Phí) <span class="text-danger">*</span>',
                                    'help_text' => 'Tên này sẽ hiển thị ở trang Checkout. Ví dụ: VAT 10% (Nội địa).',
                                    'attrs' => ['required' => true, 'placeholder' => 'Nhập tên biểu phí']
                                ]) ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Shop ID (Gian hàng)</label>
                                    <input type="number" class="form-control form-control-sm" name="shop_id" value="<?= $item['shop_id'] ?? 0 ?>" min="0">
                                    <div class="form-text">0 = Biểu phí do Admin hệ thống cung cấp chung.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Thuộc Nhóm Thuế <span class="text-danger">*</span></label>
                                    <select name="tax_class_id" class="form-select form-select-sm" required>
                                        <option value="">-- Chọn nhóm thuế --</option>
                                        <?php foreach ($taxClasses as $tc): ?>
                                            <option value="<?= $tc->id_code ?>" <?= ($item['tax_class_id'] ?? 0) == $tc->id_code ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tc->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h6 class="fw-bold mt-4 mb-3 border-bottom pb-2">Vị trí địa lý áp dụng</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Quốc gia</label>
                                    <input type="number" class="form-control form-control-sm" name="country_id" value="<?= $item['country_id'] ?? 0 ?>" min="0">
                                    <div class="form-text">0 = Tất cả quốc gia.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Tỉnh / Thành phố</label>
                                    <input type="number" class="form-control form-control-sm" name="province_id" value="<?= $item['province_id'] ?? 0 ?>" min="0">
                                    <div class="form-text">0 = Tất cả tỉnh thành.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Quận / Huyện</label>
                                    <input type="number" class="form-control form-control-sm" name="district_id" value="<?= $item['district_id'] ?? 0 ?>" min="0">
                                    <div class="form-text">0 = Tất cả quận huyện.</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Mức thuế (%) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.0001" min="0" max="9999" class="form-control" name="rate" value="<?= number_format($item['rate'] ?? 0, 4, '.', '') ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3 d-flex align-items-center border-top pt-3">
                                <input class="form-check-input" type="checkbox" name="is_compound" id="is_compound" <?= (!empty($item['is_compound'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="is_compound">Thuế kép</label>
                            </div>
                            <div class="form-text mb-4"><small>Tính thuế trên giá đã bao gồm các loại thuế khác. Dùng cho thuế lồng nhau.</small></div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Độ ưu tiên</label>
                                <input type="number" class="form-control form-control-sm" name="priority" value="<?= $item['priority'] ?? 0 ?>">
                                <div class="form-text"><small>Ưu tiên tính toán. Cần thiết khi kết hợp nhiều loại thuế và thuế kép.</small></div>
                            </div>

                            <div class="form-check form-switch mb-3 d-flex align-items-center border-top pt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($item['is_active']) || !empty($item['is_active'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="is_active">Trạng thái hoạt động</label>
                            </div>
                            
                        </div>
                        
                        <div class="card-footer">
                            <?= view('admin.components.save_buttons', [
                                'back_url' => route('admin.tax_rate.index')
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </form>
    </div>
</div>
