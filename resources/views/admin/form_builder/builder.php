<?php
$title = 'Thiết kế Form: ' . $form->name;
ob_start();
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

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Thiết kế Form: <?= htmlspecialchars($form->name) ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= route('admin.form.index') ?>">Form liên hệ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thiết kế</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="row">
            <!-- Sidebar: Available Fields -->
            <div class="col-md-3 mb-4">
                <div class="card shadow">
                    <div class="card-header text-bg-primary">
                        <h3 class="card-title">Thêm Trường (Fields)</h3>
                    </div>
                    <div class="card-body p-0 builder-sidebar">
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
            
            <!-- Canvas: Form Builder -->
            <div class="col-md-9">
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Cấu trúc Form</h3>
                        <button type="button" class="btn btn-success" onclick="saveFormBuilder()">
                            <i class="fa-solid fa-save"></i> Lưu Cấu Trúc
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fa-solid fa-info-circle"></i> Bấm vào nút bên trái để thêm trường. Kéo thả để thay đổi vị trí. Bấm biểu tượng ✏️ để cấu hình chi tiết từng trường.
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
            renderFieldHTML({
                id: f.id,
                type: f.type,
                name: f.name,
                label: f.label,
                placeholder: f.placeholder,
                is_required: f.is_required == 1,
                optionsText: optionsText
            });
        });
    } else {
        canvas.html('<div class="text-center text-muted mt-5 id="empty-state">Chưa có trường dữ liệu nào.</div>');
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
        optionsText: 'Tùy chọn 1\nTùy chọn 2\nTùy chọn 3'
    };
    
    renderFieldHTML(fieldData);
}

function renderFieldHTML(data) {
    let optionsHtml = '';
    if (['select', 'radio', 'checkbox'].includes(data.type)) {
        optionsHtml = `
            <div class="mb-2">
                <label class="form-label text-muted small">Các tùy chọn (Mỗi dòng 1 tùy chọn):</label>
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
            
            <div class="field-settings">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-muted small">Nhãn hiển thị (Label)</label>
                        <input type="text" class="form-control form-control-sm field-label" value="${data.label}" oninput="updateDisplayLabel(this)">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label text-muted small">Tên biến (Name - không dấu)</label>
                        <input type="text" class="form-control form-control-sm field-name" value="${data.name}">
                    </div>
                    ${['text', 'email', 'tel', 'textarea'].includes(data.type) ? `
                    <div class="col-md-12 mb-2">
                        <label class="form-label text-muted small">Gợi ý mờ (Placeholder)</label>
                        <input type="text" class="form-control form-control-sm field-placeholder" value="${data.placeholder || ''}">
                    </div>
                    ` : ''}
                    <div class="col-md-12">
                        ${optionsHtml}
                        <div class="form-check mt-2">
                            <input class="form-check-input field-required" type="checkbox" value="1" onchange="updateRequiredBadge(this)" ${data.is_required ? 'checked' : ''}>
                            <label class="form-check-label text-muted small">Bắt buộc nhập</label>
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
        let is_required = el.find('.field-required').is(':checked') ? 1 : 0;
        
        let options = [];
        if (el.find('.field-options').length) {
            let ops = el.find('.field-options').val().split('\n');
            ops.forEach(o => {
                if(o.trim() !== '') options.push(o.trim());
            });
        }
        
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
            options: options
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

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
