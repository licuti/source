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
                    <div class="card-header">
                        <h3 class="card-title">Danh sách Từ khóa Dịch</h3>
                        <div class="card-tools d-flex gap-2">
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#manageGroupsModal">
                                <i class="fa-solid fa-layer-group"></i> Quản lý Nhóm
                            </button>
                            <form action="<?= route('admin.translation.scan') ?>" method="POST" class="d-inline">
                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Bạn có muốn quét mã nguồn để tìm các từ khóa mới không? Quá trình này có thể mất vài giây.');">
                                    <i class="fa-solid fa-search"></i> Quét Hệ Thống
                                </button>
                            </form>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTranslationModal">
                                <i class="fa-solid fa-plus"></i> Thêm Từ Khóa
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Khung tìm kiếm và Lọc -->
                        <form action="<?= route('admin.translation.index') ?>" method="GET" class="mb-4">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select name="group_name" class="form-select">
                                        <option value="">-- Tất cả các nhóm --</option>
                                        <?php foreach ($groups as $g): ?>
                                            <option value="<?= htmlspecialchars($g) ?>" <?= ($groupFilter == $g) ? 'selected' : '' ?>>
                                                Nhóm: <?= htmlspecialchars($g) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group">
                                        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm từ khóa hoặc bản dịch..." value="<?= htmlspecialchars($keyword) ?>">
                                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Tìm kiếm</button>
                                        <?php if ($keyword || $groupFilter): ?>
                                            <a href="<?= route('admin.translation.index') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Thao tác hàng loạt -->
                        <div class="bulk-actions mb-3 p-2 bg-light border rounded d-flex align-items-center gap-2" style="display: none !important;" id="bulkActionPanel">
                            <span class="fw-bold"><span id="selectedCount">0</span> mục đã chọn:</span>
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <select class="form-select" id="bulkGroupName">
                                    <option value="">-- Chọn nhóm mới --</option>
                                    <option value="uncategorized">uncategorized</option>
                                    <?php foreach ($groups as $g): ?>
                                        <?php if($g !== 'uncategorized'): ?>
                                            <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <option value="_add_new_" class="text-primary fw-bold">[+ Thêm nhóm mới...]</option>
                                </select>
                                <button class="btn btn-primary" type="button" id="btnBulkGroup">Đổi Nhóm</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="checkAll">
                                        </th>
                                        <th style="width: 50px;">ID</th>
                                        <th style="width: 20%;">Nhóm (Group)</th>
                                        <th style="width: 25%;">Mã từ khóa (Key)</th>
                                        <th>Các bản dịch</th>
                                        <th style="width: 80px;" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($translations)): ?>
                                        <?php foreach ($translations as $item): ?>
                                            <?php 
                                                $texts = json_decode($item->text, true) ?: []; 
                                                $currentGroup = $item->group_name ?? 'uncategorized';
                                            ?>
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input row-check" value="<?= $item->id ?>">
                                                </td>
                                                <td class="align-middle"><?= $item->id ?></td>
                                                <td>
                                                    <select class="form-select form-select-sm group-input" data-id="<?= $item->id ?>">
                                                        <option value="uncategorized" <?= $currentGroup == 'uncategorized' ? 'selected' : '' ?>>uncategorized</option>
                                                        <?php foreach ($groups as $g): ?>
                                                            <?php if($g !== 'uncategorized'): ?>
                                                                <option value="<?= htmlspecialchars($g) ?>" <?= $currentGroup == $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                        <option value="_add_new_" class="text-primary fw-bold">[+ Thêm nhóm mới...]</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm key-input fw-bold" 
                                                        data-id="<?= $item->id ?>" 
                                                        value="<?= htmlspecialchars($item->key_name ?? '') ?>" 
                                                        placeholder="[Khóa cũ ID: <?= $item->id ?>]">
                                                </td>
                                                <td>
                                                    <?php foreach ($languages as $code => $lang): ?>
                                                        <div class="input-group input-group-sm mb-1">
                                                            <span class="input-group-text bg-white" style="width: 100px;">
                                                                <img src="<?= getImageUrl($lang['image'] ?? '') ?>" alt="<?= $code ?>" style="width: 16px; margin-right: 5px;" onerror="this.style.display='none'"> 
                                                                <small class="fw-bold"><?= htmlspecialchars($lang['name']) ?></small>
                                                            </span>
                                                            <textarea 
                                                                class="form-control form-control-sm translation-input" 
                                                                rows="1" 
                                                                data-id="<?= $item->id ?>" 
                                                                data-lang="<?= $code ?>"
                                                                placeholder="Nhập bản dịch..."
                                                            ><?= htmlspecialchars($texts[$code] ?? '') ?></textarea>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td class="text-center align-middle">
                                                    <a href="<?= route('admin.translation.destroy', ['id' => $item->id]) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa từ khóa này?');">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Chưa có dữ liệu từ khóa nào.</td>
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
                                    <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&group_name=<?= urlencode($groupFilter) ?>">«</a>
                                </li>
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&group_name=<?= urlencode($groupFilter) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= route('admin.translation.index') ?>?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&group_name=<?= urlencode($groupFilter) ?>">»</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Quản lý Nhóm -->
<div class="modal fade" id="manageGroupsModal" tabindex="-1" aria-labelledby="manageGroupsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageGroupsModalLabel">Quản lý Nhóm (Groups)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Việc đổi tên nhóm ở đây sẽ tự động cập nhật tên nhóm cho toàn bộ các từ khóa thuộc nhóm đó.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tên Nhóm</th>
                                <th style="width: 200px;" class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $g): ?>
                                <?php if($g !== 'uncategorized'): ?>
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control manage-group-input" value="<?= htmlspecialchars($g) ?>" data-old="<?= htmlspecialchars($g) ?>">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-primary btn-sm btn-save-group"><i class="fa-solid fa-save"></i> Lưu</button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-group" title="Xóa nhóm (đưa các từ khóa về uncategorized)"><i class="fa-solid fa-trash"></i> Xóa</button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (empty($groups) || (count($groups) === 1 && $groups[0] === 'uncategorized')): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Chưa có nhóm nào được tạo.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
    const keyInputs = document.querySelectorAll('.key-input');
    const groupInputs = document.querySelectorAll('.group-input');
    let timer;
    const toastEl = document.getElementById('saveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 2000 });

    inputs.forEach(input => {
        // Tự động điều chỉnh chiều cao textarea
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Xử lý lưu tự động khi mất focus
        input.addEventListener('blur', saveTranslation);
    });

    keyInputs.forEach(input => {
        input.addEventListener('blur', saveKey);
    });

    groupInputs.forEach(input => {
        input.addEventListener('change', saveGroup);
    });

    function showSuccess(el) {
        el.style.transition = 'background-color 0.5s ease';
        el.style.backgroundColor = '#d1e7dd';
        setTimeout(() => el.style.backgroundColor = '', 500);
        toast.show();
    }

    function saveTranslation(e) {
        const el = e.target;
        const id = el.getAttribute('data-id');
        const lang = el.getAttribute('data-lang');
        const text = el.value;

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
                showSuccess(el);
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu.'));
            }
        })
        .catch(err => {
            el.style.backgroundColor = '';
            console.error('Lỗi lưu bản dịch:', err);
        });
    }

    function saveKey(e) {
        const el = e.target;
        const id = el.getAttribute('data-id');
        const keyName = el.value.trim();

        el.style.backgroundColor = '#f8f9fa';

        const formData = new FormData();
        formData.append('id', id);
        formData.append('key_name', keyName);

        fetch('<?= route("admin.translation.updateKeyAjax") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            el.style.backgroundColor = '';
            if (data.success) {
                showSuccess(el);
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu.'));
            }
        })
        .catch(err => {
            el.style.backgroundColor = '';
            console.error('Lỗi lưu key:', err);
        });
    }

    function saveGroup(e) {
        const el = e.target;
        const id = el.getAttribute('data-id');
        let groupName = el.value.trim();

        if (groupName === '_add_new_') {
            const newName = prompt("Nhập tên nhóm mới:");
            if (newName && newName.trim() !== '') {
                groupName = newName.trim();
                // Add this new option to all selects
                const allSelects = document.querySelectorAll('.group-input, #bulkGroupName, select[name="group_name"]');
                allSelects.forEach(sel => {
                    const option = document.createElement('option');
                    option.value = groupName;
                    option.textContent = groupName;
                    sel.insertBefore(option, sel.lastElementChild);
                });
                el.value = groupName;
            } else {
                // Revert to original data if cancelled
                el.value = el.getAttribute('data-old-val') || 'uncategorized';
                return;
            }
        } else {
            el.setAttribute('data-old-val', groupName);
        }

        el.style.backgroundColor = '#f8f9fa';

        const formData = new FormData();
        formData.append('id', id);
        formData.append('group_name', groupName);

        fetch('<?= route("admin.translation.updateGroupAjax") ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            el.style.backgroundColor = '';
            if (data.success) {
                showSuccess(el);
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể lưu.'));
            }
        })
        .catch(err => {
            el.style.backgroundColor = '';
            console.error('Lỗi lưu nhóm:', err);
        });
    }

    // Cập nhật giá trị cũ khi focus select
    groupInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.setAttribute('data-old-val', this.value);
        });
    });

    // --- Bulk Action Logic ---
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.row-check');
    const bulkActionPanel = document.getElementById('bulkActionPanel');
    const selectedCount = document.getElementById('selectedCount');
    const btnBulkGroup = document.getElementById('btnBulkGroup');
    const bulkGroupName = document.getElementById('bulkGroupName');

    function updateBulkPanel() {
        const checked = document.querySelectorAll('.row-check:checked');
        selectedCount.textContent = checked.length;
        if (checked.length > 0) {
            bulkActionPanel.style.setProperty('display', 'flex', 'important');
        } else {
            bulkActionPanel.style.setProperty('display', 'none', 'important');
        }
        if (checkAll) {
            checkAll.checked = (checked.length === rowChecks.length && rowChecks.length > 0);
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowChecks.forEach(cb => cb.checked = this.checked);
            updateBulkPanel();
        });
    }

    rowChecks.forEach(cb => {
        cb.addEventListener('change', updateBulkPanel);
    });

    if (btnBulkGroup) {
        btnBulkGroup.addEventListener('click', function() {
            let groupName = bulkGroupName.value.trim();
            
            if (groupName === '_add_new_') {
                const newName = prompt("Nhập tên nhóm mới:");
                if (newName && newName.trim() !== '') {
                    groupName = newName.trim();
                    const allSelects = document.querySelectorAll('.group-input, #bulkGroupName, select[name="group_name"]');
                    allSelects.forEach(sel => {
                        const option = document.createElement('option');
                        option.value = groupName;
                        option.textContent = groupName;
                        sel.insertBefore(option, sel.lastElementChild);
                    });
                    bulkGroupName.value = groupName;
                } else {
                    bulkGroupName.value = '';
                    return;
                }
            }

            if (!groupName) {
                alert('Vui lòng chọn tên nhóm!');
                bulkGroupName.focus();
                return;
            }

            const checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) return;

            const ids = Array.from(checked).map(cb => cb.value);

            const formData = new FormData();
            ids.forEach(id => formData.append('ids[]', id));
            formData.append('group_name', groupName);

            const oldText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';
            this.disabled = true;

            fetch('<?= route("admin.translation.updateBulkGroupAjax") ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.innerHTML = oldText;
                this.disabled = false;
                if (data.success) {
                    checked.forEach(cb => {
                        const input = document.querySelector(`.group-input[data-id="${cb.value}"]`);
                        if (input) {
                            input.value = groupName;
                            showSuccess(input);
                        }
                    });
                    toast.show();
                    
                    if(checkAll) checkAll.checked = false;
                    rowChecks.forEach(cb => cb.checked = false);
                    updateBulkPanel();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể cập nhật hàng loạt.'));
                }
            })
            .catch(err => {
                this.innerHTML = oldText;
                this.disabled = false;
                console.error('Lỗi lưu nhóm hàng loạt:', err);
            });
        });
    }

    // --- Manage Groups Logic ---
    const btnSaveGroups = document.querySelectorAll('.btn-save-group');
    const btnDeleteGroups = document.querySelectorAll('.btn-delete-group');

    btnSaveGroups.forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const input = tr.querySelector('.manage-group-input');
            const oldName = input.getAttribute('data-old');
            const newName = input.value.trim();

            if (!newName) {
                alert('Tên nhóm không được để trống!');
                return;
            }
            if (newName === oldName) return;

            if (!confirm(`Bạn có chắc chắn muốn đổi tên nhóm "${oldName}" thành "${newName}" không? Mọi từ khóa đang dùng nhóm này sẽ bị thay đổi.`)) return;

            const formData = new FormData();
            formData.append('old_name', oldName);
            formData.append('new_name', newName);

            const oldHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            this.disabled = true;

            fetch('<?= route("admin.translation.renameGroupAjax") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Đã đổi tên nhóm thành công! Trang sẽ tải lại để cập nhật.');
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể đổi tên nhóm.'));
                    this.innerHTML = oldHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = oldHtml;
                this.disabled = false;
            });
        });
    });

    btnDeleteGroups.forEach(btn => {
        btn.addEventListener('click', function() {
            const tr = this.closest('tr');
            const input = tr.querySelector('.manage-group-input');
            const groupName = input.getAttribute('data-old');

            if (!confirm(`Bạn có chắc chắn muốn xóa nhóm "${groupName}" không? Các từ khóa thuộc nhóm này sẽ được đưa về dạng "uncategorized".`)) return;

            const formData = new FormData();
            formData.append('group_name', groupName);

            const oldHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            this.disabled = true;

            fetch('<?= route("admin.translation.deleteGroupAjax") ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Đã xóa nhóm thành công! Trang sẽ tải lại để cập nhật.');
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể xóa nhóm.'));
                    this.innerHTML = oldHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                this.innerHTML = oldHtml;
                this.disabled = false;
            });
        });
    });
});
</script>
