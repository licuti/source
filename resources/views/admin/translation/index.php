<?php $title = 'Quản lý Dịch Chuỗi Ngôn Ngữ'; ?>

<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Dịch chuỗi', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-check"></i> <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="card card-primary card-outline">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Danh sách Từ khóa</h3>
                        <div class="card-tools d-flex gap-2">
                            <!-- Nút Scan -->
                            <form action="<?= route('admin.translation.scan') ?>" method="POST" class="m-0" onsubmit="return confirm('Bạn có chắc chắn muốn quét toàn bộ mã nguồn để tìm từ khóa mới? Quá trình này có thể mất vài giây.');">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fa-solid fa-magnifying-glass"></i> Quét Hệ Thống
                                </button>
                            </form>
                            <!-- Nút Thêm mới -->
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTranslationModal">
                                <i class="fa-solid fa-plus"></i> Thêm Từ Khóa
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Khung tìm kiếm -->
                        <form action="<?= route('admin.translation.index') ?>" method="GET" class="mb-4">
                            <div class="input-group" style="max-width: 400px;">
                                <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm từ khóa hoặc bản dịch..." value="<?= htmlspecialchars($keyword) ?>">
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i></button>
                                <?php if ($keyword): ?>
                                    <a href="<?= route('admin.translation.index') ?>" class="btn btn-outline-secondary">Xóa tìm kiếm</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th style="width: 25%;">Khóa (Key)</th>
                                        <?php foreach ($languages as $code => $lang): ?>
                                            <th>
                                                <img src="<?= getImageUrl($lang['image'] ?? '') ?>" alt="<?= $code ?>" style="width: 20px;" onerror="this.style.display='none'"> 
                                                <?= $lang['name'] ?>
                                            </th>
                                        <?php endforeach; ?>
                                        <th style="width: 100px;" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($translations)): ?>
                                        <?php foreach ($translations as $item): ?>
                                            <?php 
                                                $texts = json_decode($item->text, true) ?: []; 
                                                $keyDisplay = $item->key_name ?: "<span class='text-muted'>[Khóa cũ ID: {$item->id}]</span>";
                                            ?>
                                            <tr>
                                                <td><?= $item->id ?></td>
                                                <td>
                                                    <strong><?= $keyDisplay ?></strong>
                                                </td>
                                                <?php foreach ($languages as $code => $lang): ?>
                                                    <td>
                                                        <textarea 
                                                            class="form-control translation-input" 
                                                            rows="1" 
                                                            data-id="<?= $item->id ?>" 
                                                            data-lang="<?= $code ?>"
                                                            placeholder="Nhập bản dịch..."
                                                        ><?= htmlspecialchars($texts[$code] ?? '') ?></textarea>
                                                    </td>
                                                <?php endforeach; ?>
                                                <td class="text-center">
                                                    <a href="<?= route('admin.translation.destroy', ['id' => $item->id]) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa từ khóa này?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="<?= count($languages) + 3 ?>" class="text-center">Chưa có dữ liệu từ khóa nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <ul class="pagination pagination-sm m-0 float-end">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>">«</a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>">»</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Từ Khóa -->
<div class="modal fade" id="addTranslationModal" tabindex="-1" aria-labelledby="addTranslationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= route('admin.translation.store') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTranslationModalLabel">Thêm Từ Khóa Dịch Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="key_name" class="form-label">Mã từ khóa (Key) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="key_name" name="key_name" required placeholder="VD: xin_chao, add_to_cart...">
                        <small class="text-muted">Nên viết liền không dấu, ngăn cách bằng dấu gạch dưới.</small>
                    </div>
                    <hr>
                    <p class="fw-bold mb-2">Bản dịch ban đầu:</p>
                    <?php foreach ($languages as $code => $lang): ?>
                        <div class="mb-3">
                            <label class="form-label"><img src="<?= getImageUrl($lang['image'] ?? '') ?>" alt="<?= $code ?>" style="width: 16px;" onerror="this.style.display='none'"> <?= $lang['name'] ?> (<?= $code ?>)</label>
                            <input type="text" class="form-control" name="text_<?= $code ?>" placeholder="Nhập bản dịch...">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Lưu Từ Khóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Thêm Toast Container cho thông báo -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <i class="fa-solid fa-check-circle me-2"></i> Đã lưu thay đổi.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.translation-input');
    let timer;
    const toastEl = document.getElementById('saveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });

    inputs.forEach(input => {
        // Tự động điều chỉnh chiều cao textarea
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Xử lý lưu tự động khi mất focus hoặc sau 1 giây ngừng gõ
        input.addEventListener('blur', saveTranslation);
    });

    function saveTranslation(e) {
        const el = e.target;
        const id = el.getAttribute('data-id');
        const lang = el.getAttribute('data-lang');
        const text = el.value;

        // Thêm loading UI
        el.style.backgroundColor = '#f8f9fa';

        const formData = new FormData();
        formData.append('id', id);
        formData.append('lang', lang);
        formData.append('text', text);

        fetch('<?= route("admin.translation.updateAjax") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            el.style.backgroundColor = '';
            if (data.success) {
                // Hiển thị khung màu xanh nhạt chớp nhẹ
                el.style.transition = 'background-color 0.5s ease';
                el.style.backgroundColor = '#d1e7dd';
                setTimeout(() => el.style.backgroundColor = '', 500);
                toast.show();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu.'));
            }
        })
        .catch(err => {
            el.style.backgroundColor = '';
            console.error('Lỗi lưu bản dịch:', err);
        });
    }
});
</script>
