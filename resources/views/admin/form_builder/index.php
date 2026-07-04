<?php
$title = 'Quản lý Form liên hệ';
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Form liên hệ', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title">Danh sách Form</h3>
                <div class="card-tools">
                    <?php if (hasPermission('admin.form', 'add')): ?>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($forms) || count($forms) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Chưa có form nào. Hãy tạo form đầu tiên!</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($forms as $item): ?>
                            <tr class="wp-row">
                                <td><?= $item->id ?></td>
                                <td>
                                    <strong><a href="<?= route('admin.form.builder', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->name) ?></a></strong>
                                    
                                    <!-- WP-Style Row Actions -->
                                    <?php 
                                    $actions = [];
                                    if (hasPermission('admin.form', 'edit')) {
                                        $actions['builder'] = [
                                            'label' => 'Thiết kế', 
                                            'url' => route('admin.form.builder', ['id' => $item->id]), 
                                            'class' => 'text-primary'
                                        ];
                                        $actions['edit'] = [
                                            'label' => 'Cấu hình', 
                                            'url' => 'javascript:void(0)', 
                                            'class' => 'text-info',
                                            'attributes' => 'onclick="openEditModal(' . $item->id . ')"'
                                        ];
                                    }
                                    if (hasPermission('admin.form', 'delete')) {
                                        $actions['delete'] = [
                                            'label' => 'Xóa', 
                                            'url' => 'javascript:void(0)', 
                                            'class' => 'text-danger',
                                            'attributes' => 'onclick="deleteForm(' . $item->id . ')"'
                                        ];
                                    }
                                    echo view('admin.components.row_actions', ['actions' => $actions]);
                                    ?>
                                </td>
                                <td><code>[form code="<?= htmlspecialchars($item->code) ?>"]</code></td>
                                <td>
                                    <?php if (hasPermission('admin.form', 'view')): ?>
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
    <div class="modal-dialog modal-lg">
        <form id="formBuilderForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalTitle">Thêm Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-3" id="formSettingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">Cài đặt chung</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-admin-mail" type="button" role="tab">Mail Admin</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-customer-mail" type="button" role="tab">Mail Khách</button>
                    </li>
                </ul>

                <div class="tab-content p-3">
                    <!-- Tab General -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
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
                        
                        <div class="mb-3" id="formSuccessMessageGroup" style="display: none;">
                            <label class="form-label">Lời cảm ơn (Sau khi gửi thành công)</label>
                            <textarea class="form-control" name="success_message" id="formSuccessMessage" rows="3" placeholder="Cảm ơn bạn đã liên hệ..."></textarea>
                        </div>
                        
                        <div class="mb-3 form-check" id="formActiveGroup" style="display: none;">
                            <input type="checkbox" class="form-check-input" name="is_active" id="formIsActive" value="1" checked>
                            <label class="form-check-label" for="formIsActive">Kích hoạt</label>
                        </div>
                    </div>

                    <!-- Tab Admin Mail -->
                    <div class="tab-pane fade" id="tab-admin-mail" role="tabpanel">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="admin_mail_enable" id="adminMailEnable" value="1">
                            <label class="form-check-label fw-bold text-primary" for="adminMailEnable">Bật gửi thông báo cho Admin</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email nhận thông báo</label>
                            <input type="email" class="form-control" name="email_to" id="formEmailTo" placeholder="admin@example.com">
                            <div class="form-text">Gửi email thông báo khi có người gửi form.</div>
                        </div>
                        <div class="alert alert-info py-2 small mb-3" id="adminMailAvailableFields" style="display: none;">
                            <strong>Các biến có sẵn:</strong> <span></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề Email</label>
                            <input type="text" class="form-control" name="admin_mail_subject" id="adminMailSubject" placeholder="Có liên hệ mới từ {ho_ten}">
                            <div class="form-text">Sử dụng <code>{ten_bien}</code> để chèn dữ liệu động.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung Email</label>
                            <textarea class="form-control" name="admin_mail_body" id="adminMailBody" rows="5" placeholder="Chào admin, bạn nhận được liên hệ mới..."></textarea>
                            <div class="form-text">Sử dụng HTML. Ví dụ: <code>&lt;strong&gt;Tên:&lt;/strong&gt; {ho_ten}</code></div>
                        </div>
                    </div>

                    <!-- Tab Customer Mail -->
                    <div class="tab-pane fade" id="tab-customer-mail" role="tabpanel">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="customer_mail_enable" id="customerMailEnable" value="1">
                            <label class="form-check-label fw-bold text-primary" for="customerMailEnable">Bật gửi phản hồi tự động cho Khách (Autoresponder)</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trường chứa Email khách hàng</label>
                            <input type="text" class="form-control" name="customer_mail_field" id="customerMailField" placeholder="Ví dụ: email">
                            <div class="form-text">Điền "Tên biến" (Name) của trường nhập Email trong form.</div>
                        </div>
                        <div class="alert alert-info py-2 small mb-3" id="customerMailAvailableFields" style="display: none;">
                            <strong>Các biến có sẵn:</strong> <span></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề Email phản hồi</label>
                            <input type="text" class="form-control" name="customer_mail_subject" id="customerMailSubject" placeholder="Cảm ơn bạn đã liên hệ!">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nội dung Email phản hồi</label>
                            <textarea class="form-control" name="customer_mail_body" id="customerMailBody" rows="5" placeholder="Chào {ho_ten}, chúng tôi đã nhận được thông tin..."></textarea>
                        </div>
                    </div>
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
    
    // Reset tabs
    new bootstrap.Tab(document.querySelector('#formSettingsTabs button[data-bs-target="#tab-general"]')).show();
    
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
            
            document.getElementById('formSuccessMessageGroup').style.display = 'block';
            document.getElementById('formSuccessMessage').value = res.data.success_message || '';
            
            document.getElementById('formActiveGroup').style.display = 'block';
            document.getElementById('formIsActive').checked = res.data.is_active == 1;
            
            // Load mail settings
            let mailSettings = {
                admin: { enable: false, subject: '', body: '' },
                customer: { enable: false, field: '', subject: '', body: '' }
            };
            
            if (res.data.mail_settings) {
                try {
                    mailSettings = JSON.parse(res.data.mail_settings);
                } catch(e) {}
            }
            
            document.getElementById('adminMailEnable').checked = mailSettings?.admin?.enable || false;
            document.getElementById('adminMailSubject').value = mailSettings?.admin?.subject || '';
            document.getElementById('adminMailBody').value = mailSettings?.admin?.body || '';
            
            document.getElementById('customerMailEnable').checked = mailSettings?.customer?.enable || false;
            document.getElementById('customerMailField').value = mailSettings?.customer?.field || '';
            document.getElementById('customerMailSubject').value = mailSettings?.customer?.subject || '';
            document.getElementById('customerMailBody').value = mailSettings?.customer?.body || '';
            
            document.getElementById('formModalTitle').innerText = 'Sửa cấu hình Form';
            
            // Load available fields
            if (res.data.fields && res.data.fields.length > 0) {
                let vars = res.data.fields.map(f => `<code>{${f.name}}</code>`).join(', ');
                document.getElementById('adminMailAvailableFields').style.display = 'block';
                document.getElementById('adminMailAvailableFields').querySelector('span').innerHTML = vars;
                
                document.getElementById('customerMailAvailableFields').style.display = 'block';
                document.getElementById('customerMailAvailableFields').querySelector('span').innerHTML = vars;
            } else {
                document.getElementById('adminMailAvailableFields').style.display = 'none';
                document.getElementById('customerMailAvailableFields').style.display = 'none';
            }
            
            // Reset tabs
            new bootstrap.Tab(document.querySelector('#formSettingsTabs button[data-bs-target="#tab-general"]')).show();
            
            formModal.show();
        } else {
            Swal.fire('Lỗi', res.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Lỗi', 'Không thể lấy dữ liệu form.', 'error');
    });
}

function deleteForm(id) {
    Swal.fire({
        title: 'Bạn có chắc muốn xóa?',
        text: "Mọi dữ liệu form và thư liên hệ sẽ bị xóa vĩnh viễn!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Vâng, xóa nó!'
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
                    window.location.reload();
                } else {
                    Swal.fire('Lỗi', res.message, 'error');
                }
            });
        }
    });
}
</script>

