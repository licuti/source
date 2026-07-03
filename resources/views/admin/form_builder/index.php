<?php
$title = 'Quản lý Form liên hệ';
ob_start();
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Quản lý Form liên hệ</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Form liên hệ</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title">Danh sách Form</h3>
                <div class="card-tools">
                    <?php if (check_permission('add')): ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddModal()">
                        <i class="fa-solid fa-plus"></i> Thêm Form mới
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Tên Form</th>
                            <th>Shortcode</th>
                            <th>Hộp thư đến</th>
                            <th width="150" class="text-center">Trạng thái</th>
                            <th width="250" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms) || count($forms) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Chưa có form nào. Hãy tạo form đầu tiên!</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($forms as $item): ?>
                            <tr>
                                <td><?= $item->id ?></td>
                                <td><strong><?= htmlspecialchars($item->name) ?></strong></td>
                                <td><code>[form code="<?= htmlspecialchars($item->code) ?>"]</code></td>
                                <td>
                                    <?php if (check_permission('view')): ?>
                                    <a href="<?= route('admin.form.submissions', ['id' => $item->id]) ?>" class="btn btn-sm btn-outline-info">
                                        <i class="fa-regular fa-envelope"></i> Xem thư 
                                        <?php if ($item->unread_count > 0): ?>
                                            <span class="badge text-bg-danger ms-1"><?= $item->unread_count ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Không có quyền</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($item->is_active): ?>
                                        <span class="badge text-bg-success">Đang bật</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Đang tắt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (check_permission('edit')): ?>
                                    <a href="<?= route('admin.form.builder', ['id' => $item->id]) ?>" class="btn btn-warning btn-sm" title="Thiết kế Form">
                                        <i class="fa-solid fa-pen-ruler"></i> Build
                                    </a>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openEditModal(<?= $item->id ?>)" title="Sửa cấu hình">
                                        <i class="fa-solid fa-gear"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <?php if (check_permission('delete')): ?>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="deleteForm(<?= $item->id ?>)" title="Xóa Form">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formBuilderForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle">Thêm Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="mb-3">
                    <label class="form-label">Tên Form <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" id="formName" required placeholder="VD: Liên hệ chính">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Mã Shortcode <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" id="formCode" required placeholder="VD: contact-form-1">
                    <div class="form-text">Dùng để nhúng ra ngoài Frontend. Không được viết có dấu, không khoảng trắng.</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email nhận thông báo</label>
                    <input type="email" class="form-control" name="email_to" id="formEmailTo" placeholder="admin@example.com">
                    <div class="form-text">Gửi email thông báo khi có người gửi form. Để trống nếu không cần.</div>
                </div>
                
                <div class="mb-3" id="formSuccessMessageGroup" style="display: none;">
                    <label class="form-label">Lời cảm ơn (Sau khi gửi thành công)</label>
                    <textarea class="form-control" name="success_message" id="formSuccessMessage" rows="3" placeholder="Cảm ơn bạn đã liên hệ..."></textarea>
                </div>
                
                <div class="mb-3 form-check" id="formActiveGroup" style="display: none;">
                    <input type="checkbox" class="form-check-input" name="is_active" id="formIsActive" value="1" checked>
                    <label class="form-check-label" for="formIsActive">Kích hoạt</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary" id="btnSave">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<script>
const ajaxUrl = '<?= route('admin.form.ajax') ?>';
let formModal;

document.addEventListener('DOMContentLoaded', function() {
    formModal = new bootstrap.Modal(document.getElementById('formModal'));
    
    // Auto generate code from name
    document.getElementById('formName').addEventListener('keyup', function() {
        if(document.getElementById('formAction').value === 'create') {
            let name = this.value;
            let code = name.toLowerCase().replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a')
                .replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e')
                .replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i')
                .replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o')
                .replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u')
                .replace(/ý|ỳ|ỷ|ỹ|ỵ/gi, 'y')
                .replace(/đ/gi, 'd')
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9\-]/g, '')
                .replace(/-+/g, '-');
            document.getElementById('formCode').value = code;
        }
    });
    
    // Submit form
    document.getElementById('formBuilderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnSave');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';
        
        const formData = new FormData(this);
        
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire('Lỗi', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Lưu lại';
            }
        })
        .catch(err => {
            Swal.fire('Lỗi', 'Đã xảy ra lỗi, vui lòng thử lại.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Lưu lại';
        });
    });
});

function openAddModal() {
    document.getElementById('formBuilderForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('formId').value = '';
    document.getElementById('formModalTitle').innerText = 'Thêm Form mới';
    
    document.getElementById('formSuccessMessageGroup').style.display = 'none';
    document.getElementById('formActiveGroup').style.display = 'none';
    
    formModal.show();
}

function openEditModal(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = res.data.id;
            document.getElementById('formName').value = res.data.name;
            document.getElementById('formCode').value = res.data.code;
            document.getElementById('formEmailTo').value = res.data.email_to || '';
            
            document.getElementById('formSuccessMessage').value = res.data.success_message || '';
            document.getElementById('formIsActive').checked = res.data.is_active == 1;
            
            document.getElementById('formSuccessMessageGroup').style.display = 'block';
            document.getElementById('formActiveGroup').style.display = 'block';
            
            document.getElementById('formModalTitle').innerText = 'Sửa cấu hình Form';
            formModal.show();
        } else {
            Swal.fire('Lỗi', res.message, 'error');
        }
    });
}

function deleteForm(id) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Xóa Form sẽ xóa toàn bộ Fields và các Thư liên hệ thuộc Form này. Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Vâng, Xóa nó!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                }
            });
        }
    });
}
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
