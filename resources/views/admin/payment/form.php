<?php 
$layout = 'admin.layouts.main'; 
$isEdit = isset($item);
$title = $isEdit ? 'Chỉnh sửa cổng thanh toán' : 'Thêm cổng thanh toán';

// Đảm bảo itemData luôn đầy đủ nếu là form thêm mới
if (!$isEdit) {
    $item = [
        'id' => 0,
        'code' => '',
        'api_config' => [],
        'is_active' => 1,
        'sort_order' => 0,
        'name' => [],
        'description' => []
    ];
}
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cổng thanh toán', 'url' => route('admin.payment.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.payment.update', ['id' => $item['id']]) : route('admin.payment.store') ?>" method="POST" id="form-payment">
            <?= csrf_field() ?>
            <div class="row">
                
                <!-- CỘT TRÁI: Nội dung chính đa ngôn ngữ -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <!-- Tabs Ngôn Ngữ -->
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <?php $c = $lang['code']; ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>" id="tab-<?= $c ?>" data-bs-toggle="tab" data-bs-target="#content-<?= $c ?>" type="button" role="tab" aria-controls="content-<?= $c ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name'] ?? strtoupper($c)) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <?php $c = $lang['code']; ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="content-<?= $c ?>" role="tabpanel" aria-labelledby="tab-<?= $c ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <?= view('admin.components.input', [
                                                'name' => "name[$c]",
                                                'value' => $item['name'][$c] ?? '',
                                                'label' => 'Tên hiển thị (' . strtoupper($c) . ') <span class="text-danger">*</span>',
                                                'attrs' => ['required' => true, 'placeholder' => 'Ví dụ: Chuyển khoản ngân hàng']
                                            ]) ?>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <?= view('admin.components.ckeditor', [
                                            'name' => "description[$c]",
                                            'value' => $item['description'][$c] ?? '',
                                            'label' => "Mô tả / Hướng dẫn thanh toán (" . strtoupper($c) . ")"
                                        ]) ?>
                                        <div class="form-text mt-1">Sẽ hiển thị cho khách hàng lúc chọn phương thức này.</div>
                                    </div>
                                    
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Cấu hình API (Dùng chung) -->
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Cấu hình API Keys (Dùng chung)</h5>
                            <button type="button" class="btn btn-sm btn-outline-info" id="btn-add-key">
                                <i class="fa-solid fa-plus me-1"></i> Thêm tham số
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info py-2 small">
                                Chỉ dành cho các cổng yêu cầu API (VNPay, Momo, PayPal). Các thông số cấu hình này sẽ có tác dụng trên <strong>tất cả ngôn ngữ</strong>.
                            </div>
                            <div id="api-keys-container">
                                <?php 
                                $apiConfig = $item['api_config'] ?? [];
                                if (!is_array($apiConfig) && !empty($apiConfig)) {
                                    $apiConfig = json_decode($apiConfig, true) ?? [];
                                }
                                if (!empty($apiConfig)): 
                                    foreach ($apiConfig as $key => $val):
                                ?>
                                    <div class="row mb-2 api-key-row">
                                        <div class="col-4">
                                            <input type="text" class="form-control form-control-sm" placeholder="Tên biến (Key)" value="<?= htmlspecialchars($key) ?>" onchange="updateApiInputName(this)">
                                        </div>
                                        <div class="col-7">
                                            <input type="text" name="api_keys[<?= htmlspecialchars($key) ?>]" class="form-control form-control-sm" placeholder="Giá trị (Value)" value="<?= htmlspecialchars($val) ?>">
                                        </div>
                                        <div class="col-1 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-key"><i class="fa-solid fa-trash"></i></button>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Trạng thái & Action -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0 fw-bold">Trạng thái & Mã hệ thống</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <?= view('admin.components.input', [
                                    'name' => 'code',
                                    'value' => $item['code'] ?? '',
                                    'label' => 'Mã định danh (Code) <span class="text-danger">*</span>',
                                    'help_text' => 'Viết liền không dấu, dùng chung cho mọi ngôn ngữ (VD: vnpay, cod).',
                                    'attrs' => ['required' => true, 'placeholder' => 'Ví dụ: bank, cod, vnpay']
                                ]) ?>
                            </div>

                            <div class="mb-3 border-top pt-3">
                                <?= view('admin.components.switch', [
                                    'name' => 'is_active',
                                    'checked' => !isset($item['is_active']) || !empty($item['is_active']),
                                    'label' => 'Đang hoạt động'
                                ]) ?>
                            </div>

                            <div class="mb-3">
                                <?= view('admin.components.input', [
                                    'name' => 'sort_order',
                                    'value' => $item['sort_order'] ?? 0,
                                    'label' => 'Sắp xếp',
                                    'attrs' => ['type' => 'number']
                                ]) ?>
                            </div>
                        </div>
                        
                        <?= view('admin.components.save_buttons', [
                            'back_url' => route('admin.payment.index')
                        ]) ?>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#btn-add-key').click(function() {
        let html = `
            <div class="row mb-2 api-key-row">
                <div class="col-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Tên biến (Key)" onchange="updateApiInputName(this)">
                </div>
                <div class="col-7">
                    <input type="text" class="form-control form-control-sm" placeholder="Giá trị (Value)">
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-key"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        $('#api-keys-container').append(html);
    });

    $(document).on('click', '.btn-remove-key', function() {
        $(this).closest('.api-key-row').remove();
    });
});

function updateApiInputName(input) {
    let key = $(input).val().trim();
    let valInput = $(input).closest('.api-key-row').find('input').eq(1);
    if (key) {
        valInput.attr('name', 'api_keys[' + key + ']');
    } else {
        valInput.removeAttr('name');
    }
}
</script>
