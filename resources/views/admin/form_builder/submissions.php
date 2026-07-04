<?php
$title = 'Hộp thư đến: ' . $form->name;
?>
<style>
.submission-row.unread {
    font-weight: bold;
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}
</style>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Hộp thư: <?= htmlspecialchars($form->name) ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= route('admin.form.index') ?>">Form liên hệ</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Hộp thư</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title">Danh sách thư liên hệ</h3>
                <div class="card-tools">
                    <form method="GET" class="d-flex" id="filterForm">
                        <select name="status" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="0" <?= request()->get('status') === '0' ? 'selected' : '' ?>>Mới / Chưa đọc</option>
                            <option value="1" <?= request()->get('status') === '1' ? 'selected' : '' ?>>Đã đọc</option>
                            <option value="2" <?= request()->get('status') === '2' ? 'selected' : '' ?>>Đã phản hồi</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">ID</th>
                                <th>Nội dung sơ lược</th>
                                <th width="150">Trạng thái</th>
                                <th width="150">IP</th>
                                <th width="180">Thời gian</th>
                                <th width="100" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($submissions) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Chưa có thư liên hệ nào.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $sub): 
                                    $data = json_decode($sub->data_payload, true);
                                    // Tìm trường đại diện để hiển thị (Ưu tiên: Tên, Email, Sđt)
                                    $preview = '';
                                    if (isset($data['ho_ten'])) $preview .= '<strong>' . htmlspecialchars($data['ho_ten']) . '</strong> - ';
                                    if (isset($data['email'])) $preview .= htmlspecialchars($data['email']) . ' - ';
                                    if (isset($data['sdt'])) $preview .= htmlspecialchars($data['sdt']);
                                    if (empty($preview)) {
                                        // Nếu không có, lấy giá trị đầu tiên trong mảng
                                        $preview = htmlspecialchars(current($data) ?: 'Không có dữ liệu');
                                    }
                                ?>
                                <tr class="submission-row <?= $sub->status == \App\Models\FormSubmissionModel::STATUS_NEW ? 'unread' : '' ?>" id="sub-row-<?= $sub->id ?>">
                                    <td><?= $sub->id ?></td>
                                    <td>
                                        <a href="javascript:void(0)" onclick="viewSubmission(<?= $sub->id ?>)" class="text-decoration-none text-dark d-block">
                                            <?= $preview ?>
                                        </a>
                                    </td>
                                    <td id="status-col-<?= $sub->id ?>">
                                        <?php if ($sub->status == \App\Models\FormSubmissionModel::STATUS_NEW): ?>
                                            <span class="badge text-bg-danger">Mới</span>
                                        <?php elseif ($sub->status == \App\Models\FormSubmissionModel::STATUS_READ): ?>
                                            <span class="badge text-bg-warning">Đã đọc</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Đã phản hồi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($sub->ip_address) ?></small></td>
                                    <td><?= date('d/m/Y H:i', strtotime($sub->created_at)) ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info text-white" onclick="viewSubmission(<?= $sub->id ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <?php if (hasPermission('admin.form', 'delete')): ?>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteSubmission(<?= $sub->id ?>)">
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
            <div class="card-footer">
                <?= $submissions->links() ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal View -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Thư liên hệ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <h6 class="border-bottom pb-2 mb-3">Thông tin khách gửi</h6>
                        <table class="table table-bordered table-striped" id="subDataTable">
                            <tbody>
                                <!-- Rendered via JS -->
                            </tbody>
                        </table>
                        <div class="text-muted small mt-2">
                            Gửi từ IP: <span id="subIp"></span> vào lúc <span id="subTime"></span>
                        </div>
                    </div>
                    <div class="col-md-5 border-start">
                        <h6 class="border-bottom pb-2 mb-3">Ghi chú / Phản hồi</h6>
                        
                        <div id="replyHistory" style="display: none;" class="mb-3">
                            <div class="alert alert-success p-2 small">
                                <strong>Đã phản hồi:</strong> <br>
                                <span id="replyContent"></span>
                            </div>
                        </div>
                        
                        <form id="replyForm">
                            <input type="hidden" id="replyId" name="id">
                            <input type="hidden" name="action" value="reply">
                            <div class="mb-2">
                                <label class="form-label text-muted small">Nội dung phản hồi (hoặc ghi chú nội bộ):</label>
                                <textarea class="form-control" name="reply_content" id="replyInput" rows="4" required placeholder="Nhập nội dung..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100" id="btnReply">
                                <i class="fa-solid fa-save"></i> Lưu & Đánh dấu đã xử lý
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ajaxUrl = '<?= route('admin.form.submission_ajax') ?>';
let viewModal;

document.addEventListener('DOMContentLoaded', function() {
    viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
    
    // Xử lý gửi form phản hồi
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btn = document.getElementById('btnReply');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';
        
        const formData = new FormData(this);
        
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
                    title: 'Thành công',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    // Update UI status to Replied
                    let id = document.getElementById('replyId').value;
                    document.getElementById('status-col-' + id).innerHTML = '<span class="badge text-bg-success">Đã phản hồi</span>';
                    viewModal.hide();
                });
            } else {
                Swal.fire('Lỗi', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Lưu & Đánh dấu đã xử lý';
            }
        });
    });
});

function viewSubmission(id) {
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
            const data = res.data;
            
            // Xóa class unread và đổi badge thành Đã đọc (nếu trước đó là Mới)
            let row = document.getElementById('sub-row-' + id);
            row.classList.remove('unread');
            if (data.status == 1) { // 1 = READ
                document.getElementById('status-col-' + id).innerHTML = '<span class="badge text-bg-warning">Đã đọc</span>';
            }
            
            // Render dữ liệu JSON
            let tableHtml = '';
            for (const [key, value] of Object.entries(data.data_payload)) {
                let displayKey = key;
                // Có thể làm đẹp tên key ở đây nếu muốn
                tableHtml += `
                    <tr>
                        <td width="40%" class="text-muted fw-bold">${displayKey}</td>
                        <td>${escapeHtml(value)}</td>
                    </tr>
                `;
            }
            document.getElementById('subDataTable').innerHTML = tableHtml;
            
            document.getElementById('subIp').innerText = data.ip_address;
            document.getElementById('subTime').innerText = data.created_at;
            
            // Reply Form
            document.getElementById('replyId').value = data.id;
            document.getElementById('replyInput').value = '';
            
            if (data.status == 2) { // 2 = REPLIED
                document.getElementById('replyHistory').style.display = 'block';
                document.getElementById('replyContent').innerText = data.reply_content;
                document.getElementById('btnReply').innerHTML = '<i class="fa-solid fa-save"></i> Cập nhật Ghi chú';
            } else {
                document.getElementById('replyHistory').style.display = 'none';
                document.getElementById('btnReply').innerHTML = '<i class="fa-solid fa-save"></i> Lưu & Đánh dấu đã xử lý';
            }
            
            document.getElementById('btnReply').disabled = false;
            
            viewModal.show();
        } else {
            Swal.fire('Lỗi', res.message, 'error');
        }
    });
}

function deleteSubmission(id) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Vâng, Xóa',
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
                    document.getElementById('sub-row-' + id).remove();
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        timer: 1000,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
}

function escapeHtml(unsafe) {
    if(typeof unsafe !== 'string') return unsafe;
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}
</script>
