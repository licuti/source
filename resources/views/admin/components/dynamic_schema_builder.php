<!-- SCHEMA BUILDER TRIGGER -->
<div class="mb-4 p-3 border rounded bg-light text-center shadow-sm">
    <p class="text-muted small mb-2">Tính năng này cho phép bạn định nghĩa thêm các trường dữ liệu tùy chỉnh (không cần sửa code).</p>
    <button type="button" class="btn btn-outline-info fw-bold" data-bs-toggle="modal" data-bs-target="#schemaBuilderModal">
        <i class="fa-solid fa-layer-group me-1"></i> Quản lý Cấu hình Mở rộng (Dynamic Fields)
    </button>
</div>

<!-- SCHEMA BUILDER MODAL -->
<div class="modal fade" id="schemaBuilderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-layer-group"></i> Quản lý Cấu hình Mở rộng</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-4">
                    Bạn có thể kéo thả để sắp xếp, thêm, sửa, xóa các trường tùy ý. <br>
                    <strong>Lưu ý:</strong> Các thay đổi sẽ chỉ có hiệu lực sau khi bạn bấm <strong>"Lưu cấu hình"</strong> ở ngoài màn hình chính.
                </p>
                <div class="schema-builder-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Các trường dữ liệu</h6>
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
const SchemaBuilder = {
    schema: [],
    modal: null,

    init() {
        this.injectModal();
        try {
            this.schema = JSON.parse(document.getElementById('<?= $input_id ?? 'schema_config_input' ?>').value) || [];
        } catch(e) {
            this.schema = [];
        }
        this.modal = new bootstrap.Modal(document.getElementById('fieldModal'));
        this.renderTable();
        
        // Form submit sync
        const formEl = document.getElementById('<?= $form_id ?? 'mainForm' ?>');
        if (formEl) {
            formEl.addEventListener('submit', () => {
                document.getElementById('<?= $input_id ?? 'schema_config_input' ?>').value = JSON.stringify(this.schema);
            });
        }

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
                    .replace(/\\s+/g, "_")
                    .replace(/[^a-z0-9_]/g, "");
                document.getElementById('fieldName').value = name;
            }
        });
    },

    injectModal() {
        if (document.getElementById('fieldModal')) return;
        const modalHtml = `
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
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
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
        
        // Init Sortable
        const initSortable = () => {
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
        };

        if (typeof Sortable === 'undefined') {
            let script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
            script.onload = initSortable;
            document.head.appendChild(script);
        } else {
            initSortable();
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
        if(confirm('Bạn có chắc chắn muốn xóa field này? Dữ liệu cũ (nếu có) sẽ không hiển thị nữa.')) {
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
