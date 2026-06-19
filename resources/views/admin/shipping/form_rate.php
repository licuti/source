<?php 
$layout = 'admin.layouts.main'; 
$isEdit = isset($item);
$title = $isEdit ? 'Chỉnh sửa Cước Vận Chuyển' : 'Thêm Cước Vận Chuyển';
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình vận chuyển', 'url' => route('admin.shipping.index')],
        ['name' => 'Bảng giá', 'url' => route('admin.shipping.rates', $method->id)],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.shipping.update_rate', [$method->id, $item->id]) : route('admin.shipping.store_rate', $method->id) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row">
                
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin cước phí</h5>
                        </div>
                        <div class="card-body">
                            
                            <h5 class="fw-bold mb-3 border-bottom pb-2">Vùng áp dụng (Geography Zone)</h5>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Quốc gia <span class="text-danger">*</span></label>
                                    <select name="country_code" id="country_code" class="form-select select2">
                                        <?php foreach ($countries as $code => $name): ?>
                                            <option value="<?= $code ?>" <?= ($isEdit && $item->country_code === $code) || (!$isEdit && $code === 'VN') ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 vn-only-field">
                                    <label class="form-label fw-bold">Tỉnh / Thành phố</label>
                                    <select name="province_code" id="province_code" class="form-select select2">
                                        <option value="">-- Áp dụng Toàn quốc --</option>
                                        <?php foreach ($provinces as $prov): ?>
                                            <option value="<?= $prov['code'] ?>" <?= ($isEdit && $item->province_code === $prov['code']) ? 'selected' : '' ?>><?= $prov['ten'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-4 vn-only-field">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Quận / Huyện</label>
                                    <select name="district_code" id="district_code" class="form-select select2">
                                        <option value="">-- Áp dụng Toàn Tỉnh --</option>
                                        <?php if ($isEdit && !empty($districts)): ?>
                                            <?php foreach ($districts as $dist): ?>
                                                <option value="<?= $dist['code'] ?>" <?= ($item->district_code === $dist['code']) ? 'selected' : '' ?>><?= $dist['ten'] ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phường / Xã</label>
                                    <select name="ward_code" id="ward_code" class="form-select select2">
                                        <option value="">-- Áp dụng Toàn Quận --</option>
                                        <?php if ($isEdit && $item->ward_code): ?>
                                            <option value="<?= $item->ward_code ?>" selected><?= htmlspecialchars($item->ward_code) ?></option>
                                        <?php endif; ?>
                                    </select>
                                    <small class="text-muted">Nhập mã phường nếu cần chi tiết (ít dùng).</small>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-3 border-bottom pb-2">Biểu phí (Pricing)</h5>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phí vận chuyển cơ bản (VNĐ) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="base_fee" class="form-control format-price" value="<?= $isEdit ? number_format($item->base_fee, 0, '', ',') : '0' ?>" required>
                                        <span class="input-group-text">đ</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Khối lượng miễn phí ban đầu (Kg)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="free_weight_kg" class="form-control" value="<?= $isEdit ? $item->free_weight_kg : '0.00' ?>">
                                        <span class="input-group-text">kg</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phí phụ thu vượt mức (VNĐ / 1kg)</label>
                                    <div class="input-group">
                                        <input type="text" name="extra_fee_per_kg" class="form-control format-price" value="<?= $isEdit ? number_format($item->extra_fee_per_kg, 0, '', ',') : '0' ?>">
                                        <span class="input-group-text">đ / kg</span>
                                    </div>
                                    <small class="text-muted">Nếu đơn hàng nặng hơn "Khối lượng miễn phí", mỗi kg dư ra sẽ cộng thêm mức phí này.</small>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Trạng thái & Action -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0 fw-bold">Trạng thái</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= (!$isEdit || $item->is_active) ? 'selected' : '' ?>>Đang hoạt động</option>
                                    <option value="0" <?= ($isEdit && !$item->is_active) ? 'selected' : '' ?>>Đã tắt</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-save me-1"></i> Lưu thay đổi</button>
                            <a href="<?= route('admin.shipping.rates', $method->id) ?>" class="btn btn-outline-secondary w-100 mt-2">Hủy bỏ</a>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Country logic
    const countrySelect = document.getElementById('country_code');
    const vnFields = document.querySelectorAll('.vn-only-field');
    
    function toggleVnFields() {
        if (countrySelect.value === 'VN') {
            vnFields.forEach(el => el.style.display = 'block');
        } else {
            vnFields.forEach(el => el.style.display = 'none');
            $('#province_code').val('').trigger('change');
            $('#district_code').val('').trigger('change');
            $('#ward_code').val('').trigger('change');
        }
    }
    
    countrySelect.addEventListener('change', toggleVnFields);
    toggleVnFields();

    // Province AJAX
    $('#province_code').change(function() {
        let provinceCode = $(this).val();
        let districtSelect = $('#district_code');
        districtSelect.html('<option value="">-- Áp dụng Toàn Tỉnh --</option>');
        $('#ward_code').html('<option value="">-- Áp dụng Toàn Quận --</option>');
        
        if (provinceCode) {
            $.post('/location/district', { code_tinh: provinceCode, _token: '<?= csrf_token() ?>' }, function(res) {
                districtSelect.html(res);
            });
        }
    });

    $('#district_code').change(function() {
        let districtCode = $(this).val();
        let wardSelect = $('#ward_code');
        wardSelect.html('<option value="">-- Áp dụng Toàn Quận --</option>');
        
        if (districtCode) {
            $.post('/location/ward', { code_huyen: districtCode, _token: '<?= csrf_token() ?>' }, function(res) {
                wardSelect.html(res);
            });
        }
    });
    
    // Formatting price inputs
    document.querySelectorAll('.format-price').forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value, 10).toLocaleString('en-US');
            } else {
                this.value = '';
            }
        });
        // Before submit, remove commas
        input.closest('form').addEventListener('submit', function() {
            input.value = input.value.replace(/,/g, '');
        });
    });
});
</script>
