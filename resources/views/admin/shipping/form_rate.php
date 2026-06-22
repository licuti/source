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
        ['name' => 'Bảng giá', 'url' => route('admin.shipping.rates', ['methodId' => $method->id])],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.shipping.update_rate', ['methodId' => $method->id, 'rateId' => $item->id]) : route('admin.shipping.store_rate', ['methodId' => $method->id]) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row">
                
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin cước phí</h5>
                        </div>
                        <div class="card-body">
                            
                        <?= view('admin.components.location_selector', [
                            'item' => $rate ?? (object)[],
                            'countries' => $countries ?? [],
                            'provinces' => $provinces ?? [],
                            'districts' => $districts ?? [],
                            'wards' => $wards ?? [],
                            'layout' => 'col-md-6 mb-3'
                        ]) ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Chi phí cơ bản (VNĐ) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control format-price" name="base_cost" value="<?= number_format($rate->base_cost ?? 0) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Điều kiện áp dụng</label>
                                <select name="condition_type" id="condition_type" class="form-select">
                                    <option value="">Không có điều kiện</option>
                                    <option value="min_order_amount" <?= (($rate->condition_type ?? '') == 'min_order_amount') ? 'selected' : '' ?>>Đơn hàng tối thiểu (VNĐ)</option>
                                    <option value="max_weight" <?= (($rate->condition_type ?? '') == 'max_weight') ? 'selected' : '' ?>>Trọng lượng tối đa (kg)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3" id="condition_value_wrapper" style="<?= empty($rate->condition_type) ? 'display:none;' : '' ?>">
                            <label class="form-label fw-bold" id="condition_value_label">Giá trị điều kiện</label>
                            <input type="text" class="form-control format-price" name="condition_value" id="condition_value" value="<?= !empty($rate->condition_value) ? number_format($rate->condition_value) : '' ?>">
                        </div>

                        <div class="form-check form-switch mb-4 d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($rate->is_active) || !empty($rate->is_active)) ? 'checked' : '' ?>>
                            <label class="form-check-label mt-1 ms-2 fw-bold" for="is_active">Trạng thái hoạt động</label>
                        </div>
                        
                        <?= view('admin.components.save_buttons', [
                            'back_url' => route('admin.shipping.index')
                        ]) ?>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    const conditionType = document.getElementById('condition_type');
    const conditionWrapper = document.getElementById('condition_value_wrapper');
    const conditionLabel = document.getElementById('condition_value_label');

    conditionType.addEventListener('change', function() {
        if (this.value === '') {
            conditionWrapper.style.display = 'none';
        } else {
            conditionWrapper.style.display = 'block';
            if (this.value === 'min_order_amount') {
                conditionLabel.textContent = 'Giá trị đơn hàng tối thiểu (VNĐ)';
            } else if (this.value === 'max_weight') {
                conditionLabel.textContent = 'Trọng lượng tối đa (kg)';
            }
        }
    });
});
</script>
