<?php
$isUpdate = isset($item);
$title = $isUpdate ? 'Sửa Chuyển hướng' : 'Thêm Chuyển hướng';
$action = $isUpdate ? route('admin.redirect.update', ['id' => $item->id]) : route('admin.redirect.store');
?>

<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Redirect 301', 'url' => route('admin.redirect.index')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => [
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.redirect.index'), 'class' => 'btn-secondary']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">
            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4 shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Cấu hình URL Chuyển hướng</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <?= view('admin.components.input', [
                                    'name' => 'old_url',
                                    'label' => 'Link cũ (Bị lỗi 404 hoặc Link cần bỏ) <span class="text-danger">*</span>',
                                    'value' => $item->old_url ?? '',
                                    'help_text' => 'Ví dụ: <code>/danh-muc-cu/san-pham-a.html</code>',
                                    'attrs' => ['required' => 'required']
                                ]) ?>
                            </div>
                            
                            <div class="mb-3">
                                <?= view('admin.components.input', [
                                    'name' => 'new_url',
                                    'label' => 'Link mới (Đích đến) <span class="text-danger">*</span>',
                                    'value' => $item->new_url ?? '',
                                    'help_text' => 'Ví dụ: <code>/danh-muc-moi/san-pham-a.html</code> hoặc link bên ngoài <code>https://domain-khac.com/link</code>',
                                    'attrs' => ['required' => 'required']
                                ]) ?>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Thiết lập & Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="status" id="status" <?= (!isset($item) || !empty($item->status)) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="status">Cho phép hoạt động</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.redirect.index') ?>" class="btn btn-secondary btn-sm">
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
