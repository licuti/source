<?php
$title = 'Thiết kế Form: ' . $form->name;
?>
<!-- jQuery UI for Sortable -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
.builder-sidebar .list-group-item {
    cursor: grab;
    transition: all 0.2s;
}
.builder-sidebar .list-group-item:hover {
    background-color: var(--bs-primary);
    color: white;
}
.builder-canvas {
    min-height: 400px;
    background: var(--bs-body-bg);
    border: 2px dashed var(--bs-border-color);
    padding: 15px;
    border-radius: 8px;
}
.field-item {
    background: var(--bs-body-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
    position: relative;
    cursor: move;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.field-item:hover {
    border-color: var(--bs-primary);
}
.field-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: none;
}
.field-item:hover .field-actions {
    display: block;
}
.field-settings {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px dashed var(--bs-border-color);
}
.sortable-placeholder {
    height: 60px;
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    border: 2px dashed var(--bs-primary);
    border-radius: 6px;
    margin-bottom: 10px;
}
</style>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Form liên hệ', 'url' => route('admin.form.index')],
        ['name' => 'Thiết kế', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-plus text-secondary"></i> Thêm Trường</h5>
                    </div>
                    <div class="card-body p-0 builder-sidebar bg-light">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item" onclick="addField('text', 'Văn bản ngắn')">
                                <i class="fa-solid fa-font fa-fw"></i> Văn bản ngắn (Text)
                            </li>
                            <li class="list-group-item" onclick="addField('email', 'Email')">
                                <i class="fa-solid fa-envelope fa-fw"></i> Email
                            </li>
                            <li class="list-group-item" onclick="addField('tel', 'Số điện thoại')">
                                <i class="fa-solid fa-phone fa-fw"></i> Số điện thoại
                            </li>
                            <li class="list-group-item" onclick="addField('textarea', 'Văn bản dài')">
                                <i class="fa-solid fa-align-left fa-fw"></i> Văn bản dài (Textarea)
                            </li>
                            <li class="list-group-item" onclick="addField('select', 'Menu thả xuống')">
                                <i class="fa-solid fa-caret-square-down fa-fw"></i> Menu thả xuống (Select)
                            </li>
                            <li class="list-group-item" onclick="addField('radio', 'Chọn một')">
                                <i class="fa-regular fa-dot-circle fa-fw"></i> Chọn một (Radio)
                            </li>
                            <li class="list-group-item" onclick="addField('checkbox', 'Chọn nhiều')">
                                <i class="fa-regular fa-check-square fa-fw"></i> Chọn nhiều (Checkbox)
                            </li>
                            <li class="list-group-item" onclick="addField('file', 'Upload File')">
                                <i class="fa-solid fa-upload fa-fw"></i> Tải tệp lên (File)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold">Cấu trúc Form</h5>
                            <div>
                                <a href="<?= route('admin.form.preview', ['id' => $form->id]) ?>" target="_blank" class="btn btn-success btn-sm me-1">
                                    <i class="fa-solid fa-eye"></i> Xem thử
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" onclick="saveFormBuilder()">
                                    <i class="fa-solid fa-save"></i> Lưu
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info p-2 small">
                            <i class="fa-solid fa-info-circle"></i> Bấm vào nút bên phải để thêm trường. Kéo thả để thay đổi vị trí. Bấm biểu tượng ✏️ để cấu hình.
                        </div>
                        
                        <div id="builder-canvas" class="builder-canvas">
                            <!-- Fields will be rendered here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let fieldCounter = 0;
const canvas = $('#builder-canvas');

// Cấu trúc dữ liệu hiện tại lấy từ Server
const existingFields = <?= json_encode($fields) ?>;

$(document).ready(function() {
    // Khởi tạo Sortable (Kéo thả)
    canvas.sortable({
        placeholder: "sortable-placeholder",
        handle: ".field-handle",
        axis: "y"
    });
    
    // Load dữ liệu cũ nếu có
    if (existingFields && existingFields.length > 0) {
        existingFields.forEach(f => {
            let options = f.options ? (typeof f.options === 'string' ? JSON.parse(f.options) : f.options) : [];
            let optionsText = Array.isArray(options) ? options.join('\n') : '';
            
            let adv = f.advanced_settings ? (typeof f.advanced_settings === 'string' ? JSON.parse(f.advanced_settings) : f.advanced_settings) : {};
            
            renderFieldHTML({
                id: f.id,
                type: f.type,
                name: f.name,
                label: f.label,
                placeholder: f.placeholder,
                is_required: f.is_required == 1,
                col_width: f.col_width || 'col-md-12',
                optionsText: optionsText,
                advanced_settings: adv
            });
        });
    } else {
        canvas.html('<div class="text-center text-muted mt-5" id="empty-state">Chưa có trường dữ liệu nào.</div>');
    }
});

function addField(type, typeName) {
    $('#empty-state').remove();
    fieldCounter++;
    
    const fieldData = {
        type: type,
        name: type + '_' + Date.now(),
        label: typeName,
        placeholder: '',
        is_required: false,
        col_width: 'col-md-12',
        optionsText: 'Tùy chọn 1\nTùy chọn 2\nTùy chọn 3',
        advanced_settings: {}
    };
    
    renderFieldHTML(fieldData);
}

function renderFieldHTML(data) {
    const uid = 'field_' + Math.random().toString(36).substr(2, 9);
    const adv = data.advanced_settings || {};
    
    let optionsHtml = '';
    if (['select', 'radio', 'checkbox'].includes(data.type)) {
        optionsHtml = `
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Các tùy chọn (Mỗi dòng 1 tùy chọn):</label>
                <textarea class="form-control form-control-sm field-options" rows="3">${data.optionsText || ''}</textarea>
            </div>
        `;
    }

    const html = `
        <div class="field-item" data-type="${data.type}">
            <div class="field-handle d-flex align-items-center">
                <i class="fa-solid fa-grip-vertical text-muted me-3 fs-5"></i>
                <div class="flex-grow-1">
                    <strong class="field-display-label">${data.label}</strong>
                    <span class="badge text-bg-secondary ms-2">${data.type}</span>
                    <span class="badge text-bg-danger ms-1 req-badge" style="display: ${data.is_required ? 'inline-block' : 'none'}">Bắt buộc</span>
                </div>
            </div>
            
            <div class="field-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleSettings(this)" title="Cài đặt">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeField(this)" title="Xóa">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            
            <div class="field-settings mt-3 p-3 bg-light border rounded" style="display:none;">
                <ul class="nav nav-tabs nav-sm mb-3" id="tabs_${uid}" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basic_${uid}" type="button" role="tab">Cơ bản</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#adv_${uid}" type="button" role="tab">Nâng cao</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#val_${uid}" type="button" role="tab">Xác thực</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#logic_${uid}" type="button" role="tab">Logic (Hiển thị)</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="tabsContent_${uid}">
                    <!-- Tab: Cơ bản -->
                    <div class="tab-pane fade show active" id="basic_${uid}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Nhãn hiển thị (Label)</label>
                                <input type="text" class="form-control form-control-sm field-label" value="${data.label}" oninput="updateDisplayLabel(this)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Tên biến (Name - Cần viết liền, không dấu)</label>
                                <input type="text" class="form-control form-control-sm field-name" value="${data.name}">
                            </div>
                            
                            ${['text', 'email', 'tel', 'textarea'].includes(data.type) ? `
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted small fw-bold">Gợi ý mờ (Placeholder)</label>
                                <input type="text" class="form-control form-control-sm field-placeholder" value="${data.placeholder || ''}">
                            </div>
                            ` : ''}
                            
                            <div class="col-md-12">
                                ${optionsHtml}
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Độ rộng cột (Grid Layout)</label>
                                <select class="form-select form-select-sm field-width">
                                    <option value="col-md-12" ${data.col_width === 'col-md-12' ? 'selected' : ''}>100% (Full Width)</option>
                                    <option value="col-md-9" ${data.col_width === 'col-md-9' ? 'selected' : ''}>75% (3/4 Width)</option>
                                    <option value="col-md-8" ${data.col_width === 'col-md-8' ? 'selected' : ''}>66.6% (2/3 Width)</option>
                                    <option value="col-md-6" ${data.col_width === 'col-md-6' ? 'selected' : ''}>50% (Half Width)</option>
                                    <option value="col-md-4" ${data.col_width === 'col-md-4' ? 'selected' : ''}>33.3% (1/3 Width)</option>
                                    <option value="col-md-3" ${data.col_width === 'col-md-3' ? 'selected' : ''}>25% (1/4 Width)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-center">
                                <div class="form-check mt-3">
                                    <input class="form-check-input field-required" type="checkbox" value="1" onchange="updateRequiredBadge(this)" ${data.is_required ? 'checked' : ''}>
                                    <label class="form-check-label fw-bold">Bắt buộc nhập</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Nâng cao -->
                    <div class="tab-pane fade" id="adv_${uid}" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted small fw-bold">Mô tả phụ (Help Text)</label>
                                <input type="text" class="form-control form-control-sm adv-help-text" placeholder="Dòng hướng dẫn nhỏ bên dưới trường nhập liệu" value="${adv.help_text || ''}">
                            </div>
                            ${!['file'].includes(data.type) ? `
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Giá trị mặc định (Default Value)</label>
                                <input type="text" class="form-control form-control-sm adv-default-value" placeholder="Giá trị điền sẵn" value="${adv.default_value || ''}">
                            </div>
                            ` : ''}
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Custom CSS Class</label>
                                <input type="text" class="form-control form-control-sm adv-css-class" placeholder="VD: my-custom-input" value="${adv.css_class || ''}">
                            </div>
                            ${['text', 'email', 'tel'].includes(data.type) ? `
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Icon FontAwesome</label>
                                <input type="text" class="form-control form-control-sm adv-icon" placeholder="VD: fa-solid fa-user" value="${adv.icon || ''}">
                            </div>
                            ` : ''}
                            ${['radio', 'checkbox'].includes(data.type) ? `
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Kiểu hiển thị (Layout)</label>
                                <select class="form-select form-select-sm adv-layout">
                                    <option value="stacked" ${adv.layout === 'stacked' ? 'selected' : ''}>Xếp dọc (Stacked)</option>
                                    <option value="inline" ${adv.layout === 'inline' ? 'selected' : ''}>Xếp ngang (Inline)</option>
                                </select>
                            </div>
                            ` : ''}
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input adv-readonly" type="checkbox" value="1" ${adv.readonly ? 'checked' : ''}>
                                    <label class="form-check-label text-muted small">Chỉ đọc (Readonly / Không cho sửa)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab: Xác thực (Validation) -->
                    <div class="tab-pane fade" id="val_${uid}" role="tabpanel">
                        <div class="row">
                            ${['text', 'textarea'].includes(data.type) ? `
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Độ dài tối thiểu (Min Length)</label>
                                <input type="number" class="form-control form-control-sm val-min-length" placeholder="0" value="${adv.min_length || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Độ dài tối đa (Max Length)</label>
                                <input type="number" class="form-control form-control-sm val-max-length" placeholder="255" value="${adv.max_length || ''}">
                            </div>
                            ` : ''}
                            ${['file'].includes(data.type) ? `
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Định dạng file (Extensions)</label>
                                <input type="text" class="form-control form-control-sm val-allowed-ext" placeholder=".jpg, .png, .pdf" value="${adv.allowed_ext || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Kích thước tối đa (MB)</label>
                                <input type="number" class="form-control form-control-sm val-max-size" placeholder="5" value="${adv.max_size || ''}">
                            </div>
                            ` : ''}
                            ${['text', 'email', 'tel', 'textarea'].includes(data.type) ? `
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted small fw-bold">Biểu thức chính quy (Regex Pattern)</label>
                                <input type="text" class="form-control form-control-sm val-regex" placeholder="VD: ^[A-Za-z]+$" value="${adv.regex || ''}">
                                <small class="text-muted">Kiểm tra định dạng nâng cao bằng Regex.</small>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Tab: Logic -->
                    <div class="tab-pane fade" id="logic_${uid}" role="tabpanel">
                        <div class="alert alert-warning py-2 mb-3 small">
                            <strong>Logic hiển thị:</strong> Trường này sẽ chỉ hiển thị nếu trường được chọn có giá trị thỏa mãn điều kiện.
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted small fw-bold">Bật Logic hiển thị?</label>
                                <select class="form-select form-select-sm logic-enable" onchange="$(this).closest('.tab-pane').find('.logic-rules').toggle($(this).val() == '1')">
                                    <option value="0" ${!adv.logic_enable ? 'selected' : ''}>Không (Luôn hiển thị)</option>
                                    <option value="1" ${adv.logic_enable ? 'selected' : ''}>Có (Ẩn/Hiện có điều kiện)</option>
                                </select>
                            </div>
                        </div>
                        <div class="logic-rules row" style="display: ${adv.logic_enable ? 'flex' : 'none'};">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Trường điều kiện (Name của trường khác)</label>
                                <input type="text" class="form-control form-control-sm logic-field" placeholder="Ví dụ: loai_dich_vu" value="${adv.logic_field || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small fw-bold">Giá trị bằng (==)</label>
                                <input type="text" class="form-control form-control-sm logic-value" placeholder="Ví dụ: Khác" value="${adv.logic_value || ''}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    canvas.append(html);
}

function toggleSettings(btn) {
    $(btn).closest('.field-item').find('.field-settings').slideToggle(200);
}

function removeField(btn) {
    Swal.fire({
        title: 'Xóa trường này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Vâng, Xóa'
    }).then((result) => {
        if (result.isConfirmed) {
            $(btn).closest('.field-item').remove();
            if(canvas.children('.field-item').length === 0) {
                canvas.html('<div class="text-center text-muted mt-5" id="empty-state">Chưa có trường dữ liệu nào.</div>');
            }
        }
    });
}

function updateDisplayLabel(input) {
    $(input).closest('.field-item').find('.field-display-label').text($(input).val());
}

function updateRequiredBadge(checkbox) {
    if (checkbox.checked) {
        $(checkbox).closest('.field-item').find('.req-badge').show();
    } else {
        $(checkbox).closest('.field-item').find('.req-badge').hide();
    }
}

function saveFormBuilder() {
    let fields = [];
    let hasError = false;
    let names = [];
    
    $('.field-item').each(function() {
        let el = $(this);
        let type = el.data('type');
        let label = el.find('.field-label').val().trim();
        let name = el.find('.field-name').val().trim();
        let placeholder = el.find('.field-placeholder').length ? el.find('.field-placeholder').val().trim() : '';
        let col_width = el.find('.field-width').val();
        let is_required = el.find('.field-required').is(':checked') ? 1 : 0;
        
        let options = [];
        if (el.find('.field-options').length) {
            let ops = el.find('.field-options').val().split('\n');
            ops.forEach(o => {
                if(o.trim() !== '') options.push(o.trim());
            });
        }
        
        // --- Advanced Settings Extraction ---
        let adv = {};
        
        let helpText = el.find('.adv-help-text').val();
        if (helpText) adv.help_text = helpText.trim();
        
        let defaultVal = el.find('.adv-default-value').length ? el.find('.adv-default-value').val().trim() : '';
        if (defaultVal) adv.default_value = defaultVal;
        
        let cssClass = el.find('.adv-css-class').val();
        if (cssClass) adv.css_class = cssClass.trim();
        
        let icon = el.find('.adv-icon').length ? el.find('.adv-icon').val().trim() : '';
        if (icon) adv.icon = icon;
        
        let layout = el.find('.adv-layout').length ? el.find('.adv-layout').val() : '';
        if (layout) adv.layout = layout;
        
        adv.readonly = el.find('.adv-readonly').is(':checked');
        
        // Validation
        let minL = el.find('.val-min-length').length ? el.find('.val-min-length').val().trim() : '';
        if (minL) adv.min_length = minL;
        
        let maxL = el.find('.val-max-length').length ? el.find('.val-max-length').val().trim() : '';
        if (maxL) adv.max_length = maxL;
        
        let allowExt = el.find('.val-allowed-ext').length ? el.find('.val-allowed-ext').val().trim() : '';
        if (allowExt) adv.allowed_ext = allowExt;
        
        let maxSize = el.find('.val-max-size').length ? el.find('.val-max-size').val().trim() : '';
        if (maxSize) adv.max_size = maxSize;
        
        let regex = el.find('.val-regex').length ? el.find('.val-regex').val().trim() : '';
        if (regex) adv.regex = regex;
        
        // Logic
        let logicEnable = el.find('.logic-enable').val() === '1';
        adv.logic_enable = logicEnable;
        if (logicEnable) {
            adv.logic_field = el.find('.logic-field').val().trim();
            adv.logic_value = el.find('.logic-value').val().trim();
        }
        
        // --- End Advanced ---
        
        if (name === '') {
            hasError = 'Tên biến không được để trống.';
            return false; // break loop
        }
        if (names.includes(name)) {
            hasError = 'Tên biến "' + name + '" bị trùng lặp. Vui lòng đặt tên khác nhau cho mỗi trường.';
            return false;
        }
        names.push(name);
        
        fields.push({
            type: type,
            name: name,
            label: label,
            placeholder: placeholder,
            is_required: is_required,
            col_width: col_width,
            options: options,
            advanced_settings: adv
        });
    });
    
    if (hasError) {
        Swal.fire('Lỗi', hasError, 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_builder');
    formData.append('id', <?= $form->id ?>);
    formData.append('fields', JSON.stringify(fields));
    
    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';
    
    fetch('<?= route('admin.form.ajax') ?>', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire('Thành công', res.message, 'success');
        } else {
            Swal.fire('Lỗi', res.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Lỗi', 'Đã xảy ra lỗi hệ thống.', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    });
}
</script>

