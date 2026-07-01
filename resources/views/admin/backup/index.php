<?php $title = 'Sao lưu & Dọn dẹp Cache'; ?>

<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Sao lưu & Dọn dẹp Cache</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.dashboard') ?>">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Sao lưu & Cache
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Phần 1: Dọn dẹp hệ thống -->
            <div class="col-md-4">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-broom text-warning me-2"></i> Dọn dẹp Hệ thống</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Hệ thống sẽ xóa các file nhật ký (logs) cũ và làm mới bộ nhớ đệm (OPcache) để giải phóng dung lượng và giúp website chạy nhanh hơn.</p>
                        
                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3 border">
                            <div class="fs-1 text-secondary me-3"><i class="fa-solid fa-trash-can"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold">Dung lượng rác (Logs):</h6>
                                <?php 
                                    $sizeMB = $logSize / 1048576;
                                    $color = $sizeMB > 50 ? 'text-danger' : 'text-success';
                                ?>
                                <span class="fs-4 fw-bold <?= $color ?>"><?= number_format($sizeMB, 2) ?> MB</span>
                            </div>
                        </div>

                        <form action="<?= route('admin.backup.clear_cache') ?>" method="POST" id="form-clear-cache">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small uppercase">Tùy chọn dọn dẹp</label>
                                <select name="type" class="form-select form-select-lg">
                                    <option value="all">Dọn dẹp Toàn bộ (Logs + OPcache)</option>
                                    <option value="logs">Chỉ xóa file nhật ký (Logs)</option>
                                    <option value="opcache">Chỉ làm mới bộ nhớ đệm (OPcache)</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-warning w-100 fw-bold text-dark shadow-sm" onclick="confirmClearCache()">
                                <i class="fa-solid fa-broom"></i> Thực hiện Dọn dẹp
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card card-info card-outline mt-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa-solid fa-clock text-info me-2"></i> Tự động hóa (Cronjob)</h3>
                    </div>
                    <form action="<?= route('admin.backup.save_settings') ?>" method="POST">
                        <div class="card-body">
                            <?php 
                                $cronSettings = file_exists(dirname(dirname(dirname(__DIR__))) . '/storage/cron_settings.json') 
                                    ? json_decode(file_get_contents(dirname(dirname(dirname(__DIR__))) . '/storage/cron_settings.json'), true) 
                                    : ['enabled' => 0, 'interval_days' => 1, 'email' => ''];
                            ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Trạng thái Tự động Backup</label>
                                <select name="enabled" class="form-select">
                                    <option value="1" <?= $cronSettings['enabled'] == 1 ? 'selected' : '' ?>>Bật (Tự động chạy mỗi ngày)</option>
                                    <option value="0" <?= $cronSettings['enabled'] == 0 ? 'selected' : '' ?>>Tắt (Chỉ chạy thủ công)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Xóa bản cũ sau (Ngày)</label>
                                <input type="number" name="interval_days" class="form-control" value="<?= htmlspecialchars($cronSettings['interval_days']) ?>" min="1" max="30">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email nhận cảnh báo</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cronSettings['email']) ?>" placeholder="admin@domain.com">
                            </div>
                            <div class="alert alert-secondary small mb-0">
                                <i class="fa-solid fa-info-circle"></i> Vui lòng thiết lập lệnh Cron trên máy chủ trỏ về:<br>
                                <code>curl -s <?= url('cron.php?token=s4fe_cron_backup_2026_xyz') ?></code>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-info text-white"><i class="fa-solid fa-save"></i> Lưu cấu hình</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Phần 2: Quản lý Sao lưu -->
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fa-solid fa-database text-primary me-2"></i> Các bản Sao lưu (Database & Source)</h3>
                        <div class="d-flex gap-2 ms-auto">
                            <form action="<?= route('admin.backup.create') ?>" method="POST" id="form-create-backup">
                                <button type="button" class="btn btn-primary btn-sm shadow-sm" onclick="confirmCreateBackup()">
                                    <i class="fa-solid fa-database"></i> Sao lưu CSDL
                                </button>
                            </form>
                            <form action="<?= route('admin.backup.create_source') ?>" method="POST" id="form-create-source">
                                <button type="button" class="btn btn-dark btn-sm shadow-sm" onclick="confirmCreateSourceBackup()">
                                    <i class="fa-solid fa-file-zipper"></i> Nén Source Code
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tên bản sao lưu</th>
                                        <th>Dung lượng</th>
                                        <th>Thời gian tạo</th>
                                        <th class="text-end pe-4">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($backups)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Chưa có bản sao lưu nào.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($backups as $file): ?>
                                        <tr>
                                            <td class="ps-4 fw-medium text-primary">
                                                <i class="fa-regular fa-file-code me-2"></i> <?= htmlspecialchars($file['name']) ?>
                                            </td>
                                            <td><?= number_format($file['size'] / 1048576, 2) ?> MB</td>
                                            <td><?= date('d/m/Y H:i:s', $file['time']) ?></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group">
                                                    <?php if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'sql'): ?>
                                                    <button type="button" class="btn btn-sm btn-warning" title="Phục hồi" onclick="confirmRestoreBackup('<?= htmlspecialchars($file['name']) ?>')">
                                                        <i class="fa-solid fa-clock-rotate-left"></i> Phục hồi
                                                    </button>
                                                    <?php endif; ?>
                                                    <a href="<?= route('admin.backup.download', ['file' => $file['name']]) ?>" class="btn btn-sm btn-success text-white" title="Tải xuống">
                                                        <i class="fa-solid fa-download"></i> Tải về
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger text-white" title="Xóa" onclick="confirmDeleteBackup('<?= htmlspecialchars($file['name']) ?>')">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
                                                <form id="form-delete-<?= md5($file['name']) ?>" action="<?= route('admin.backup.delete', ['file' => $file['name']]) ?>" method="POST" style="display: none;"></form>
                                                <?php if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'sql'): ?>
                                                <form id="form-restore-<?= md5($file['name']) ?>" action="<?= route('admin.backup.restore', ['file' => $file['name']]) ?>" method="POST" style="display: none;"></form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 text-muted small bg-light border-top">
                            <i class="fa-solid fa-circle-info me-1"></i> Tệp tin sao lưu được lưu trữ bảo mật tại thư mục <code>storage/backups</code>. Trình duyệt không thể truy cập trực tiếp các file này.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmClearCache() {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Hành động này sẽ xóa toàn bộ file log và reset OPcache. Tuyệt đối an toàn cho dữ liệu web.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Có, dọn dẹp ngay',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang xử lý...',
                html: 'Vui lòng đợi trong giây lát.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            document.getElementById('form-clear-cache').submit();
        }
    });
}

function confirmCreateBackup() {
    Swal.fire({
        title: 'Tạo bản sao lưu CSDL?',
        text: "Hệ thống sẽ mất vài giây để xuất toàn bộ CSDL ra file .sql.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-plus"></i> Có, tạo bản sao lưu',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang trích xuất CSDL...',
                html: 'Tuyệt đối không đóng trình duyệt lúc này.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            document.getElementById('form-create-backup').submit();
        }
    });
}

function confirmCreateSourceBackup() {
    Swal.fire({
        title: 'Nén toàn bộ mã nguồn?',
        text: "Hệ thống sẽ nén toàn bộ thư mục web thành file .zip (bỏ qua cache/logs). Quá trình này có thể mất 1-3 phút tùy dung lượng.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#212529',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-file-zipper"></i> Bắt đầu nén',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang nén dữ liệu...',
                html: 'Tuyệt đối không đóng trình duyệt lúc này.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            document.getElementById('form-create-source').submit();
        }
    });
}

function confirmDeleteBackup(filename) {
    Swal.fire({
        title: 'Xóa bản sao lưu?',
        text: "Hành động này sẽ xóa vĩnh viễn tệp [" + filename + "] khỏi máy chủ!",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-trash"></i> Có, xóa tệp',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            let forms = document.querySelectorAll('form[action*="delete/' + filename + '"]');
            if (forms.length > 0) {
                forms[0].submit();
            }
        }
    });
}

function confirmRestoreBackup(filename) {
    Swal.fire({
        title: 'CẢNH BÁO NGUY HIỂM!',
        html: `Việc khôi phục sẽ <b>xóa sạch</b> toàn bộ dữ liệu hiện tại và thay thế bằng dữ liệu từ tệp <b>${filename}</b>.<br><br>Gõ chữ <b>RESTORE</b> vào ô bên dưới để xác nhận:`,
        icon: 'warning',
        input: 'text',
        inputAttributes: {
            autocapitalize: 'off'
        },
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa-solid fa-clock-rotate-left"></i> Phục hồi ngay',
        cancelButtonText: 'Hủy',
        preConfirm: (inputValue) => {
            if (inputValue !== 'RESTORE') {
                Swal.showValidationMessage('Bạn phải gõ chính xác chữ RESTORE (in hoa).');
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Đang khôi phục dữ liệu...',
                html: 'Quá trình này tuyệt đối không được gián đoạn. Vui lòng chờ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            let forms = document.querySelectorAll('form[action*="restore/' + filename + '"]');
            if (forms.length > 0) {
                forms[0].submit();
            }
        }
    });
}
</script>
