<?php
$isEdit = isset($item);
$action = $isEdit ? route('admin.attribute.update', ['id' => $item['id']]) : route('admin.attribute.store');
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold"><?= $isEdit ? 'Cập nhật Thuộc tính' : 'Thêm Thuộc tính mới' ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.attribute.index') ?>">Thuộc tính</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Cập nhật' : 'Thêm mới' ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST" id="attributeForm">
            <div class="row">
                <!-- Cột Trái: Đa Ngôn Ngữ & Giá trị -->
                <div class="col-md-9">
                    <!-- Thông tin thuộc tính -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>" id="tab-<?= $lang['code'] ?>" data-bs-toggle="tab" data-bs-target="#content-<?= $lang['code'] ?>" type="button" role="tab" aria-controls="content-<?= $lang['code'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body bg-light">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <?php $c = $lang['code']; ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="content-<?= $c ?>" role="tabpanel" aria-labelledby="tab-<?= $c ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tên nhóm thuộc tính <span class="text-danger">*</span></label>
                                        <input type="text" name="ten[<?= $c ?>]" class="form-control" placeholder="VD: Màu sắc, Kích thước..." value="<?= htmlspecialchars($item['ten'][$c] ?? '') ?>" <?= $i === 0 ? 'required' : '' ?>>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Đường dẫn thân thiện (Alias)</label>
                                            <input type="text" name="alias[<?= $c ?>]" class="form-control text-muted" placeholder="Tự động tạo nếu để trống" value="<?= htmlspecialchars($item['alias'][$c] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Mô tả ngắn</label>
                                            <textarea class="form-control" rows="1" name="mo_ta[<?= $c ?>]"><?= htmlspecialchars($item['mo_ta'][$c] ?? '') ?></textarea>
                                        </div>
                                    </div>

                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Danh sách giá trị thuộc tính (Repeater) -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-list-ul text-primary"></i> Các Giá trị Thuộc tính (Values)</h5>
                            <button type="button" class="btn btn-sm btn-success" id="addValueBtn"><i class="fa-solid fa-plus"></i> Thêm giá trị</button>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0" id="valuesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <?php foreach($langs as $lang): ?>
                                            <th>Tên (<?= strtoupper($lang['code']) ?>) <span class="text-danger">*</span></th>
                                        <?php endforeach; ?>
                                        <th style="width: 200px;">Mã giá trị / Mã màu</th>
                                        <th style="width: 80px;" class="text-center">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($itemValues) && count($itemValues) > 0): ?>
                                        <?php $rowCount = 1; foreach($itemValues as $val_id_code => $val): ?>
                                        <tr class="value-row">
                                            <td class="text-center fw-bold text-muted row-number"><?= $rowCount++ ?></td>
                                            <input type="hidden" name="val_id_code[]" value="<?= $val_id_code ?>">
                                            <?php foreach($langs as $lang): ?>
                                                <td>
                                                    <input type="text" name="val_ten[<?= $lang['code'] ?>][]" class="form-control" value="<?= htmlspecialchars($val['ten'][$lang['code']] ?? '') ?>" required placeholder="Nhập tên...">
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" name="val_gia_tri[]" id="val_gia_tri_<?= $val_id_code ?>" class="form-control" value="<?= htmlspecialchars($val['gia_tri'] ?? '') ?>" placeholder="Mã màu / Ảnh">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCKFinder('val_gia_tri_<?= $val_id_code ?>', '/img_data/images/')"><i class="fa-solid fa-image"></i></button>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <!-- Dòng mẫu ban đầu nếu thêm mới -->
                                        <tr class="value-row">
                                            <td class="text-center fw-bold text-muted row-number">1</td>
                                            <input type="hidden" name="val_id_code[]" value="0">
                                            <?php foreach($langs as $lang): ?>
                                                <td>
                                                    <input type="text" name="val_ten[<?= $lang['code'] ?>][]" class="form-control" required placeholder="Nhập tên...">
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <div class="input-group">
                                                    <input type="text" name="val_gia_tri[]" id="val_gia_tri_new_1" class="form-control" placeholder="Mã màu / Ảnh">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="openCKFinder('val_gia_tri_new_1', '/img_data/images/')"><i class="fa-solid fa-image"></i></button>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cột Phải: Cấu Hình Chung -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 70px; z-index: 1;">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập chung</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Loại thuộc tính</label>
                                <select name="loai" class="form-select">
                                    <?php foreach ($data_type_variation as $key => $title): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= ($item['loai'] ?? '') == $key ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($title) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Số thứ tự hiển thị</label>
                                <input type="number" name="sap_xep" class="form-control" value="<?= $item['sap_xep'] ?? 0 ?>">
                                <small class="text-muted">Số càng nhỏ ưu tiên hiển thị trước.</small>
                            </div>

                            <div class="form-check form-switch mb-3 pt-2">
                                <input class="form-check-input fs-5" type="checkbox" name="hien_thi" id="hien_thi" <?= (!isset($item) || !empty($item['hien_thi'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="hien_thi">Cho phép hiển thị</label>
                            </div>

                        </div>
                        <div class="card-footer bg-white text-end border-top-0 py-3">
                            <a href="<?= route('admin.attribute.index') ?>" class="btn btn-light border me-2"><i class="fa-solid fa-arrow-left"></i> Trở về</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save"></i> <?= $isEdit ? 'Lưu cập nhật' : 'Thêm mới' ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const tableBody = document.querySelector('#valuesTable tbody');
    const addBtn = document.getElementById('addValueBtn');
    const langs = <?= json_encode(array_column($langs, 'code')) ?>;
    let newRowCounter = 2; // Dùng để tạo ID ngẫu nhiên cho input
    
    // Cập nhật số thứ tự (Row numbers)
    function updateRowNumbers() {
        const rows = tableBody.querySelectorAll('tr.value-row');
        rows.forEach((row, index) => {
            row.querySelector('.row-number').textContent = index + 1;
        });
    }

    // Thêm dòng mới
    addBtn.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.className = 'value-row';
        const uniqueId = 'val_gia_tri_new_' + (newRowCounter++);
        
        let inputsHtml = `<td class="text-center fw-bold text-muted row-number"></td>
            <input type="hidden" name="val_id_code[]" value="0">`;
            
        langs.forEach(lang => {
            inputsHtml += `<td><input type="text" name="val_ten[${lang}][]" class="form-control" required placeholder="Nhập tên..."></td>`;
        });
        
        inputsHtml += `<td>
                <div class="input-group">
                    <input type="text" name="val_gia_tri[]" id="${uniqueId}" class="form-control" placeholder="Mã màu / Ảnh">
                    <button class="btn btn-outline-secondary" type="button" onclick="openCKFinder('${uniqueId}', '/img_data/images/')"><i class="fa-solid fa-image"></i></button>
                </div>
            </td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="fa-solid fa-trash"></i></button></td>`;
            
        tr.innerHTML = inputsHtml;
        tableBody.appendChild(tr);
        updateRowNumbers();
    });

    // Xóa dòng
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-row')) {
            if (tableBody.querySelectorAll('tr.value-row').length > 1) {
                e.target.closest('tr').remove();
                updateRowNumbers();
            } else {
                alert('Phải có ít nhất 1 giá trị thuộc tính!');
            }
        }
    });
    
    // Tự động chuyển tab nếu có input bị lỗi HTML5 validation (required) nằm trong tab đang ẩn
    document.addEventListener('invalid', function(e) {
        let target = e.target;
        let tabPane = target.closest('.tab-pane:not(.active)');
        if (tabPane) {
            let tabId = tabPane.getAttribute('id');
            let tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
            if (tabButton && typeof bootstrap !== 'undefined') {
                let tab = new bootstrap.Tab(tabButton);
                tab.show();
                setTimeout(() => target.focus(), 200);
            }
        }
    }, true);
});
</script>

<?php if (!defined('CKFINDER_SCRIPT_LOADED')): ?>
    <?php define('CKFINDER_SCRIPT_LOADED', true); ?>
    <script src="/assets/admin/ckfinder/ckfinder.js"></script>
    <script>
        function openCKFinder(inputId, basePath) {
            CKFinder.modal({
                chooseFiles: true,
                width: 800,
                height: 600,
                onInit: function(finder) {
                    finder.on('files:choose', function(evt) {
                        var file = evt.data.files.first();
                        var fullPath = file.getUrl();
                        var fileName = fullPath;
                        if (basePath && fullPath.startsWith(basePath)) {
                            fileName = fullPath.substring(basePath.length);
                        } else if (fullPath.indexOf('/images/') !== -1) {
                            fileName = fullPath.substring(fullPath.indexOf('/images/') + 8);
                        } else {
                            fileName = fullPath.substring(fullPath.lastIndexOf('/') + 1);
                        }
                        document.getElementById(inputId).value = fileName;
                    });
                }
            });
        }
    </script>
<?php endif; ?>
