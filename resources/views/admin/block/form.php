<?php
$title = isset($firstItem) ? "Sửa Khối giao diện" : "Thêm Khối giao diện mới";

$alias = $_POST['alias'] ?? ($firstItem->alias ?? '');
$sort_order = $_POST['sort_order'] ?? ($firstItem->sort_order ?? 0);
$is_active = isset($firstItem) ? $firstItem->is_active : 1;

$schemaStr = $firstItem->schema_config ?? '[]';
if (empty($schemaStr)) $schemaStr = '[]';
if (is_array($schemaStr) || is_object($schemaStr)) {
    $schemaStr = json_encode($schemaStr, JSON_UNESCAPED_UNICODE);
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khối giao diện', 'url' => route('admin.block.index')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => [
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.block.index'), 'class' => 'btn-default']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= isset($firstItem) ? route('admin.block.update', ['id' => $firstItem->id_code]) : route('admin.block.store') ?>" method="POST" id="blockForm">
            <!-- Hidden schema config -->
            <input type="hidden" name="schema_config" id="schema_config_input" value="<?= htmlspecialchars($schemaStr) ?>">
            
            <div class="row">
                <div class="col-md-9">
                    <!-- LANGUAGE TABS -->
                    <div class="card card-outline card-primary mb-4">
                        <?php if (count($langs) > 1): ?>
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#pane-<?= $lang['code'] ?>"
                                        type="button" role="tab">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin chính</h5>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $lang): 
                                    $c = $lang['code'];
                                    $l_name = $_POST['name'][$c] ?? ($firstItem->lang_data[$c]['name'] ?? '');
                                    $l_desc = $_POST['description'][$c] ?? ($firstItem->lang_data[$c]['description'] ?? '');
                                    $l_image = $_POST['image'][$c] ?? ($firstItem->lang_data[$c]['image'] ?? '');
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="pane-<?= $c ?>" role="tabpanel">
                                    <?= view('admin.components.input', [
                                        'name' => "name[$c]",
                                        'value' => $l_name,
                                        'label' => 'Tên khối (Name) - ' . $lang['name'],
                                        'help_text' => 'Chỉ hiển thị trong Admin để dễ quản lý',
                                        'attrs' => ['required' => ($i === 0), 'placeholder' => 'Vd: Slide Trang Chủ']
                                    ]) ?>
                                    
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "description[$c]",
                                        'value' => $l_desc,
                                        'label' => 'Mô tả (Description) - ' . $lang['name'],
                                        'help_text' => 'Mô tả chi tiết về khối'
                                    ]) ?>
                                    
                                    <div class="mb-3">
                                        <?= view('admin.components.image_upload', [
                                            'name' => "image[$c]",
                                            'value' => $l_image,
                                            'label' => 'Ảnh đại diện khối - ' . $lang['name'],
                                            'help_text' => 'Hiển thị minh họa cho khối'
                                        ]) ?>
                                    </div>
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SCHEMA BUILDER -->
                    <div class="card card-outline card-info mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-layer-group text-info"></i> Cấu hình Fields cho Item</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-4">
                                Khối giao diện (Blocks) định nghĩa một cấu trúc dữ liệu gồm nhiều item lặp lại (VD: Slider, Đối tác). <br>
                                Vui lòng sử dụng <strong>Cấu hình Fields</strong> để xác định xem mỗi item bên trong khối này sẽ cần nhập những trường nào.
                            </p>
                            <div class="schema-builder-wrapper">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Các trường dữ liệu cho Item</h6>
                                    <button type="button" class="btn btn-sm btn-info text-white" onclick="SchemaBuilder.addField()">
                                        <i class="fa-solid fa-plus"></i> Thêm Field
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle" id="schemaFieldsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px">#</th>
                                                <th>Tên hiển thị (Label)</th>
                                                <th>Mã biến (Name)</th>
                                                <th>Loại field (Type)</th>
                                                <th style="width: 100px" class="text-center">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="schemaFieldsBody">
                                            <!-- Dynamic fields will be listed here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white"><h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5></div>
                        <div class="card-body bg-light">
                            <?= view('admin.components.input', [
                                'name' => 'alias',
                                'value' => $alias,
                                'label' => 'Mã gọi (Alias)',
                                'help_text' => 'Dùng để lập trình viên gọi ra ở Frontend',
                                'attrs' => ['required' => true, 'placeholder' => 'Vd: home_slider']
                            ]) ?>
                            
                            <?= view('admin.components.input', [
                                'type' => 'number',
                                'name' => 'sort_order',
                                'value' => $sort_order,
                                'label' => 'Số thứ tự',
                                'help_text' => 'Số nhỏ hiển thị trước.'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'is_active',
                                'checked' => $is_active,
                                'label' => 'Cho phép hiển thị'
                            ]) ?>
                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.block.index')]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thêm/Sửa Field -->
<div class="modal fade" id="fieldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fieldModalTitle">Thêm Field</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="fieldForm">
                    <input type="hidden" id="fieldIndex" value="-1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nhãn (Label) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fieldLabel" required placeholder="Vd: Hình ảnh">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tên biến (Name) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fieldName" required placeholder="Vd: image" pattern="^[a-zA-Z0-9_]+$">
                        <div class="form-text">Viết liền không dấu, chỉ dùng chữ, số và dấu gạch dưới.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Loại (Type) <span class="text-danger">*</span></label>
                        <select class="form-select" id="fieldType" required>
                            <option value="text">Text (Văn bản ngắn)</option>
                            <option value="textarea">Textarea (Văn bản dài)</option>
                            <option value="richtext">Richtext (Trình soạn thảo)</option>
                            <option value="image">Image (Hình ảnh)</option>
                            <option value="number">Number (Số)</option>
                            <option value="link">Link (Liên kết)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="SchemaBuilder.saveField()">Lưu Field</button>
            </div>
        </div>
    </div>
</div>

<script>
const SchemaBuilder = {
    schema: [],
    modal: null,

    init() {
        try {
            this.schema = JSON.parse(document.getElementById('schema_config_input').value) || [];
        } catch(e) {
            this.schema = [];
        }
        this.modal = new bootstrap.Modal(document.getElementById('fieldModal'));
        this.renderTable();
        
        // Form submit sync
        document.getElementById('blockForm').addEventListener('submit', () => {
            document.getElementById('schema_config_input').value = JSON.stringify(this.schema);
        });

        // Auto-generate name from label
        document.getElementById('fieldLabel').addEventListener('keyup', function() {
            if(document.getElementById('fieldIndex').value == -1) {
                let name = this.value.toLowerCase()
                    .replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a")
                    .replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e")
                    .replace(/ì|í|ị|ỉ|ĩ/g, "i")
                    .replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o")
                    .replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u")
                    .replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y")
                    .replace(/đ/g, "d")
                    .replace(/\s+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
                document.getElementById('fieldName').value = name;
            }
        });
    },

    renderTable() {
        const tbody = document.getElementById('schemaFieldsBody');
        tbody.innerHTML = '';
        if(this.schema.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Chưa có field nào. Vui lòng thêm field.</td></tr>`;
            return;
        }

        this.schema.forEach((field, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center align-middle cursor-move"><i class="fa-solid fa-grip-vertical text-muted"></i></td>
                <td class="align-middle fw-bold">${field.label}</td>
                <td class="align-middle text-primary"><code>${field.name}</code></td>
                <td class="align-middle"><span class="badge bg-secondary">${field.type}</span></td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-light text-primary me-1" onclick="SchemaBuilder.editField(${index})" title="Sửa"><i class="fa-solid fa-pen"></i></button>
                    <button type="button" class="btn btn-sm btn-light text-danger" onclick="SchemaBuilder.deleteField(${index})" title="Xóa"><i class="fa-solid fa-trash"></i></button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Init Sortable if available
        if(typeof Sortable !== 'undefined') {
            new Sortable(tbody, {
                animation: 150,
                handle: '.cursor-move',
                onEnd: (evt) => {
                    const movedItem = this.schema.splice(evt.oldIndex, 1)[0];
                    this.schema.splice(evt.newIndex, 0, movedItem);
                }
            });
        }
    },

    addField() {
        document.getElementById('fieldForm').reset();
        document.getElementById('fieldIndex').value = -1;
        document.getElementById('fieldModalTitle').innerText = 'Thêm Field';
        this.modal.show();
    },

    editField(index) {
        const field = this.schema[index];
        document.getElementById('fieldIndex').value = index;
        document.getElementById('fieldLabel').value = field.label;
        document.getElementById('fieldName').value = field.name;
        document.getElementById('fieldType').value = field.type;
        document.getElementById('fieldModalTitle').innerText = 'Sửa Field';
        this.modal.show();
    },

    saveField() {
        const index = parseInt(document.getElementById('fieldIndex').value);
        const label = document.getElementById('fieldLabel').value.trim();
        const name = document.getElementById('fieldName').value.trim();
        const type = document.getElementById('fieldType').value;

        if(!label || !name) {
            alert('Vui lòng nhập Nhãn và Tên biến!');
            return;
        }
        
        if(!/^[a-zA-Z0-9_]+$/.test(name)) {
            alert('Tên biến chỉ được chứa chữ cái, số và dấu gạch dưới!');
            return;
        }

        const fieldData = { label, name, type };

        if(index === -1) {
            // Check duplicate name
            if(this.schema.find(f => f.name === name)) {
                alert('Tên biến này đã tồn tại!');
                return;
            }
            this.schema.push(fieldData);
        } else {
            this.schema[index] = fieldData;
        }

        this.renderTable();
        this.modal.hide();
    },

    deleteField(index) {
        if(confirm('Bạn có chắc chắn muốn xóa field này? Dữ liệu cũ của các items có thể bị mất nếu bạn xóa.')) {
            this.schema.splice(index, 1);
            this.renderTable();
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    SchemaBuilder.init();
});
</script>
<style>
.cursor-move { cursor: grab; }
.cursor-move:active { cursor: grabbing; }
</style>
