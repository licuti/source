<?php
$title = $title ?? 'Thêm Mã Giảm Giá';
$isEdit = !empty($item);
$action = $isEdit ? route('admin.promo_code.update', ['id' => $item->id]) : route('admin.promo_code.store');
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Mã giảm giá', 'url' => route('admin.promo_code.index')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => []
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST" id="promoCodeForm">

            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    
                    <!-- Thông tin cơ bản -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin mã giảm giá</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label fw-bold">Tên chương trình / Tên mã <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control"
                                        placeholder="VD: Khuyến mãi mùa hè 20%..."
                                        value="<?= htmlspecialchars($item->name ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Mã Code <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="code" id="code_input" class="form-control text-uppercase"
                                            placeholder="VD: SUMMER20"
                                            value="<?= htmlspecialchars($item->code ?? '') ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" id="btnGenerateCode" title="Sinh mã ngẫu nhiên">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Mã khách hàng nhập lúc thanh toán (chỉ chữ và số, viết hoa).</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Mô tả chi tiết</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Ghi chú thêm về điều kiện áp dụng..."><?= htmlspecialchars($item->description ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Phạm vi áp dụng</label>
                                    <select name="apply_to" class="form-select">
                                        <option value="1" <?= ($item->apply_to ?? 1) == 1 ? 'selected' : '' ?>>Toàn bộ đơn hàng</option>
                                        <option value="2" <?= ($item->apply_to ?? 1) == 2 ? 'selected' : '' ?>>Sản phẩm cụ thể (Comming Soon)</option>
                                        <option value="3" <?= ($item->apply_to ?? 1) == 3 ? 'selected' : '' ?>>Danh mục cụ thể (Comming Soon)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thiết lập giá trị -->
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Giá trị & Điều kiện</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Loại giảm giá <span class="text-danger">*</span></label>
                                    <select name="discount_type" id="discount_type" class="form-select">
                                        <option value="1" <?= ($item->discount_type ?? 1) == 1 ? 'selected' : '' ?>>Giảm theo phần trăm (%)</option>
                                        <option value="2" <?= ($item->discount_type ?? 1) == 2 ? 'selected' : '' ?>>Giảm số tiền cố định</option>
                                        <option value="3" <?= ($item->discount_type ?? 1) == 3 ? 'selected' : '' ?>>Miễn phí vận chuyển</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3" id="wrap_discount_value">
                                    <label class="form-label fw-bold" id="lbl_discount_value">Mức giảm</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control"
                                            value="<?= $item->discount_value ?? 0 ?>">
                                        <span class="input-group-text" id="addon_discount_value">%</span>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 mb-3" id="wrap_max_discount">
                                    <label class="form-label fw-bold">Giảm tối đa (VNĐ)</label>
                                    <div class="input-group">
                                        <input type="number" name="max_discount_amount" class="form-control"
                                            value="<?= $item->max_discount_amount ?? 0 ?>">
                                        <span class="input-group-text">đ</span>
                                    </div>
                                    <div class="form-text">Để 0 = Không giới hạn.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Điều kiện giá trị đơn tối thiểu (VNĐ)</label>
                                    <div class="input-group">
                                        <input type="number" name="min_order_amount" class="form-control"
                                            value="<?= $item->min_order_amount ?? 0 ?>">
                                        <span class="input-group-text">đ</span>
                                    </div>
                                    <div class="form-text">Khách phải mua tối thiểu số tiền này mới được dùng mã. Để 0 = Không yêu cầu.</div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-3">
                    
                    <!-- Box: Hành động -->
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($item->is_active) || $item->is_active == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="is_active">Kích hoạt</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.promo_code.index') ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" name="save_action" value="exit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-save"></i> Lưu
                            </button>
                            <button type="submit" name="save_action" value="continue" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                            </button>
                        </div>
                    </div>

                    <!-- Box: Thời gian -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-regular fa-clock"></i> Thời gian áp dụng</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Từ ngày <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_date" class="form-control"
                                    value="<?= isset($item->start_date) ? date('Y-m-d\TH:i', strtotime($item->start_date)) : date('Y-m-d\TH:i') ?>" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold">Đến ngày <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_date" class="form-control"
                                    value="<?= isset($item->end_date) ? date('Y-m-d\TH:i', strtotime($item->end_date)) : date('Y-m-d\T23:59', strtotime('+1 month')) ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Box: Giới hạn sử dụng -->
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-ban"></i> Giới hạn sử dụng</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tổng lượt sử dụng tối đa</label>
                                <input type="number" name="usage_limit" class="form-control"
                                    value="<?= $item->usage_limit ?? 0 ?>">
                                <div class="form-text small">Số lần mã này được dùng trên toàn hệ thống. Để 0 = Không giới hạn.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold">Giới hạn trên mỗi Khách hàng</label>
                                <input type="number" name="usage_per_user" class="form-control"
                                    value="<?= $item->usage_per_user ?? 1 ?>">
                                <div class="form-text small">Số lần 1 user (hoặc 1 email) được dùng. Để 0 = Không giới hạn.</div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate Code AJAX
    const btnGenerate = document.getElementById('btnGenerateCode');
    const inputCode = document.getElementById('code_input');
    
    if (btnGenerate && inputCode) {
        btnGenerate.addEventListener('click', function() {
            btnGenerate.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btnGenerate.disabled = true;
            
            fetch('<?= route('admin.promo_code.generateCodeAjax') ?>')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    inputCode.value = data.code;
                }
                btnGenerate.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i>';
                btnGenerate.disabled = false;
            })
            .catch(err => {
                btnGenerate.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i>';
                btnGenerate.disabled = false;
            });
        });
    }

    // Dynamic fields based on discount type
    const typeSelect = document.getElementById('discount_type');
    const wrapValue = document.getElementById('wrap_discount_value');
    const wrapMax = document.getElementById('wrap_max_discount');
    const addonValue = document.getElementById('addon_discount_value');
    
    function toggleDiscountFields() {
        const type = parseInt(typeSelect.value);
        if (type === 1) {
            // Percent
            wrapValue.style.display = 'block';
            wrapMax.style.display = 'block';
            addonValue.innerHTML = '%';
        } else if (type === 2) {
            // Fixed
            wrapValue.style.display = 'block';
            wrapMax.style.display = 'none';
            addonValue.innerHTML = 'đ';
        } else if (type === 3) {
            // Free Shipping
            wrapValue.style.display = 'none';
            wrapMax.style.display = 'block'; // Max free shipping discount
        }
    }
    
    if (typeSelect) {
        typeSelect.addEventListener('change', toggleDiscountFields);
        toggleDiscountFields(); // Init on load
    }
});
</script>
