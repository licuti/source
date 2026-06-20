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
        <form action="<?= $isEdit ? route('admin.shipping.update_method', ['id' => $item->id]) : route('admin.shipping.store_method') ?>" method="POST">
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
                                <?= view('admin.components.input', [
                                    'name' => 'name',
                                    'value' => $item->name ?? '',
                                    'label' => 'Tên hiển thị <span class="text-danger">*</span>',
                                    'attrs' => ['required' => true, 'placeholder' => 'VD: Giao hàng tiêu chuẩn, Giao Hàng Nhanh']
                                ]) ?>
                            </div>

                            <div class="mb-3">
                                <?= view('admin.components.input', [
                                    'name' => 'carrier_code',
                                    'value' => $item->carrier_code ?? 'custom',
                                    'label' => 'Mã hãng (Carrier Code) <span class="text-danger">*</span>',
                                    'help_text' => "Dùng 'custom' cho phương thức giao hàng tự định cấu hình bảng giá.",
                                    'attrs' => ['required' => true, 'placeholder' => 'custom, ghn, ghtk, viettelpost...']
                                ]) ?>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <?= view('admin.components.select', [
                                        'name' => 'is_api',
                                        'value' => $item->is_api ?? 0,
                                        'label' => 'Loại cấu hình phí',
                                        'options' => [
                                            '0' => 'Tự cấu hình Bảng giá theo Vùng (Rates)',
                                            '1' => 'Gọi API tự động từ Hãng (GHN, GHTK...)'
                                        ],
                                        'attrs' => ['id' => 'is_api', 'class' => 'form-select']
                                    ]) ?>
                                </div>
                                <div class="col-md-6">
                                    <?= view('admin.components.input', [
                                        'type' => 'number',
                                        'name' => 'sort_order',
                                        'value' => $item->sort_order ?? 0,
                                        'label' => 'Thứ tự hiển thị'
                                    ]) ?>
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
                                <?= view('admin.components.switch', [
                                    'name' => 'is_active',
                                    'checked' => !isset($item) || !empty($item->is_active),
                                    'label' => 'Đang hoạt động'
                                ]) ?>
                            </div>
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
