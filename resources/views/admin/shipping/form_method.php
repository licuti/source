<?php 
$layout = 'admin.layouts.main'; 
$isEdit = isset($item);
$title = $isEdit ? 'Chỉnh sửa Phương thức' : 'Thêm Phương thức Vận chuyển';
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cấu hình vận chuyển', 'url' => route('admin.shipping.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.shipping.update_method', $item->id) : route('admin.shipping.store_method') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="row">
                
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin chính</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên hiển thị <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?= $isEdit ? htmlspecialchars($item->name) : '' ?>" required placeholder="VD: Giao hàng tiêu chuẩn, Giao Hàng Nhanh">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Mã hãng (Carrier Code) <span class="text-danger">*</span></label>
                                <input type="text" name="carrier_code" class="form-control" value="<?= $isEdit ? htmlspecialchars($item->carrier_code) : 'custom' ?>" required placeholder="custom, ghn, ghtk, viettelpost...">
                                <small class="text-muted">Dùng 'custom' cho phương thức giao hàng tự định cấu hình bảng giá.</small>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Loại cấu hình phí</label>
                                    <select name="is_api" id="is_api" class="form-select">
                                        <option value="0" <?= ($isEdit && !$item->is_api) ? 'selected' : '' ?>>Tự cấu hình Bảng giá theo Vùng (Rates)</option>
                                        <option value="1" <?= ($isEdit && $item->is_api) ? 'selected' : '' ?>>Gọi API tự động từ Hãng (GHN, GHTK...)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Thứ tự hiển thị</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?= $isEdit ? $item->sort_order : 0 ?>">
                                </div>
                            </div>

                            <div id="api_config_section" class="border rounded p-3 bg-light mb-3" style="<?= ($isEdit && $item->is_api) ? '' : 'display: none;' ?>">
                                <h5 class="fw-bold mb-3">Cấu hình API Key / Token</h5>
                                <p class="text-muted small">Điền các thông số kết nối API của hãng vận chuyển vào đây.</p>
                                
                                <?php 
                                $apiKeys = [];
                                if ($isEdit && $item->api_config) {
                                    $apiKeys = json_decode($item->api_config, true) ?: [];
                                }
                                ?>
                                
                                <div id="api_keys_container">
                                    <?php if (empty($apiKeys)): ?>
                                    <div class="row mb-2 api-key-row">
                                        <div class="col-5"><input type="text" name="api_keys[key][]" class="form-control" placeholder="Tên Key (VD: token)"></div>
                                        <div class="col-6"><input type="text" name="api_keys[value][]" class="form-control" placeholder="Giá trị"></div>
                                        <div class="col-1"><button type="button" class="btn btn-danger btn-remove-key"><i class="fas fa-times"></i></button></div>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($apiKeys as $k => $v): ?>
                                            <div class="row mb-2 api-key-row">
                                                <div class="col-5"><input type="text" name="api_keys[key][]" class="form-control" value="<?= htmlspecialchars($k) ?>" placeholder="Tên Key"></div>
                                                <div class="col-6"><input type="text" name="api_keys[value][]" class="form-control" value="<?= htmlspecialchars($v) ?>" placeholder="Giá trị"></div>
                                                <div class="col-1"><button type="button" class="btn btn-danger btn-remove-key"><i class="fas fa-times"></i></button></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btn_add_key">
                                    <i class="fas fa-plus"></i> Thêm tham số
                                </button>
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
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.shipping.index') ?>" class="btn btn-secondary btn-sm">
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
                </div>

            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isApiSelect = document.getElementById('is_api');
    const apiConfigSection = document.getElementById('api_config_section');
    
    isApiSelect.addEventListener('change', function() {
        if (this.value == '1') {
            apiConfigSection.style.display = 'block';
        } else {
            apiConfigSection.style.display = 'none';
        }
    });

    document.getElementById('btn_add_key').addEventListener('click', function() {
        const row = document.createElement('div');
        row.className = 'row mb-2 api-key-row';
        row.innerHTML = `
            <div class="col-5"><input type="text" name="api_keys[key][]" class="form-control" placeholder="Tên Key"></div>
            <div class="col-6"><input type="text" name="api_keys[value][]" class="form-control" placeholder="Giá trị"></div>
            <div class="col-1"><button type="button" class="btn btn-danger btn-remove-key"><i class="fas fa-times"></i></button></div>
        `;
        document.getElementById('api_keys_container').appendChild(row);
    });

    document.getElementById('api_keys_container').addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-key')) {
            e.target.closest('.api-key-row').remove();
        }
    });
});
</script>
