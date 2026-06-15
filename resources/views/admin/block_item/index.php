<?php
$title = "Quản lý Items: " . htmlspecialchars($block->name);
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khối giao diện', 'url' => route('admin.block.index')],
        ['name' => $block->name, 'url' => '']
    ],
    'actions' => [
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.block.index'), 'class' => 'btn-default']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <!-- HEADER: Bulk Action, Filter, Search -->
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    
                    <!-- TRÁI: Hành động hàng loạt -->
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <?php if(hasPermission('admin.block', 'can_delete')): ?>
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <option value="delete" data-url="<?= route('admin.block_item.destroy_multiple', ['block_id' => $block->id_code]) ?>" data-confirm="Bạn có chắc chắn muốn xóa các mục đã chọn?">Xóa</option>
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                        <?php endif; ?>
                    </div>

                    <!-- PHẢI: Bộ lọc, Tìm kiếm & Nút Thêm mới -->
                    <form action="<?= route('admin.block_item.index', ['block_id' => $block->id_code]) ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        
                        <!-- Lọc theo Trạng thái -->
                        <select name="status" class="form-select form-select-sm w-auto">
                            <option value="">Tất cả trạng thái</option>
                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>>Hiển thị</option>
                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>>Đã ẩn</option>
                        </select>

                        <!-- Ô tìm kiếm -->
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm nội dung..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </div>
                        
                        <!-- Nút Xóa lọc -->
                        <?php if (!empty($keyword) || ($status ?? '') !== ''): ?>
                            <a href="<?= route('admin.block_item.index', ['block_id' => $block->id_code]) ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>

                        <!-- Nút Thêm mới -->
                        <?php if(hasPermission('admin.block', 'can_add')): ?>
                        <a href="<?= route('admin.block_item.create', ['block_id' => $block->id_code]) ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới Item
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php
            $schema = $block->schema_config ?? [];
            if (is_string($schema)) {
                $schema = json_decode($schema, true) ?: [];
            }
            // Chỉ lấy tối đa 4 field để làm cột hiển thị tránh vỡ layout
            $displayFields = array_slice($schema, 0, 4);
            $colspan = count($displayFields) + 3; // + checkbox, STT, Status
            ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <div class="form-check d-flex justify-content-center mb-0">
                                    <input class="form-check-input check-all" type="checkbox" title="Chọn tất cả">
                                </div>
                            </th>
                            <th style="width: 40px;" class="text-center"></th>
                            <?php if(empty($displayFields)): ?>
                                <th>Nội dung Tóm tắt</th>
                            <?php else: ?>
                                <?php foreach($displayFields as $idx => $field): ?>
                                    <th <?= $idx === 0 ? '' : 'style="width: 15%"' ?>><?= htmlspecialchars($field['label']) ?></th>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <th style="width: 120px;" class="text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-items">
                        <?php if(!empty($items) && count($items) > 0): ?>
                            <?php foreach($items as $item): ?>
                            <?php 
                                $data = $item->data_payload ?? [];
                                if (is_string($data)) {
                                    $data = json_decode($data, true) ?: [];
                                }
                            ?>
                            <tr class="wp-row">
                                <th scope="row" class="text-center align-middle">
                                    <div class="form-check d-flex justify-content-center mb-0">
                                        <input class="form-check-input row-check" type="checkbox" value="<?= $item->id_code ?>">
                                    </div>
                                </th>
                                <td class="text-center align-middle cursor-move" title="Kéo thả để sắp xếp">
                                    <i class="fa-solid fa-grip-vertical text-muted"></i>
                                </td>
                                
                                <?php if(empty($displayFields)): ?>
                                    <td class="align-middle">
                                        <strong><a href="<?= route('admin.block_item.edit', ['block_id' => $block->id_code, 'id' => $item->id_code]) ?>" class="text-dark text-decoration-none">
                                            Item #<?= $item->id_code ?>
                                        </a></strong>
                                        <?php 
                                        $actions = [];
                                        if(hasPermission('admin.block', 'can_edit')) {
                                            $actions['edit'] = [
                                                'label' => 'Chỉnh sửa', 
                                                'url' => route('admin.block_item.edit', ['block_id' => $block->id_code, 'id' => $item->id_code]), 
                                                'class' => 'text-primary'
                                            ];
                                        }
                                        if(hasPermission('admin.block', 'can_delete')) {
                                            $actions['delete'] = [
                                                'label' => 'Xóa', 
                                                'url' => route('admin.block_item.destroy', ['block_id' => $block->id_code, 'id' => $item->id_code]), 
                                                'class' => 'text-danger confirm-delete',
                                                'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa mục này?"'
                                            ];
                                        }
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                <?php else: ?>
                                    <?php foreach($displayFields as $idx => $field): 
                                        $val = $data[$field['name']] ?? '';
                                    ?>
                                        <td class="align-middle">
                                            <?php if($idx === 0): ?>
                                                <!-- Cột đầu tiên chứa Link sửa và Row Actions -->
                                                <strong><a href="<?= route('admin.block_item.edit', ['block_id' => $block->id_code, 'id' => $item->id_code]) ?>" class="text-dark text-decoration-none">
                                                    <?php 
                                                    if($field['type'] === 'image' && $val) {
                                                        echo '<img src="'.getImageUrl($val).'" style="max-height: 40px; max-width: 80px; object-fit: cover; border-radius: 4px;" alt="image">';
                                                    } else {
                                                        echo htmlspecialchars(mb_substr(strip_tags((string)$val), 0, 50)) . (mb_strlen(strip_tags((string)$val)) > 50 ? '...' : '');
                                                        if(empty($val)) echo 'Item #' . $item->id_code;
                                                    }
                                                    ?>
                                                </a></strong>
                                                
                                                <!-- WP-Style Row Actions -->
                                                <?php 
                                                $actions = [];
                                                if(hasPermission('admin.block', 'can_edit')) {
                                                    $actions['edit'] = [
                                                        'label' => 'Chỉnh sửa', 
                                                        'url' => route('admin.block_item.edit', ['block_id' => $block->id_code, 'id' => $item->id_code]), 
                                                        'class' => 'text-primary'
                                                    ];
                                                }
                                                if(hasPermission('admin.block', 'can_delete')) {
                                                    $actions['delete'] = [
                                                        'label' => 'Xóa', 
                                                        'url' => route('admin.block_item.destroy', ['block_id' => $block->id_code, 'id' => $item->id_code]), 
                                                        'class' => 'text-danger confirm-delete',
                                                        'attributes' => 'data-confirm="Bạn có chắc chắn muốn xóa mục này?"'
                                                    ];
                                                }
                                                echo view('admin.components.row_actions', ['actions' => $actions]);
                                                ?>
                                            <?php else: ?>
                                                <!-- Các cột tiếp theo chỉ hiển thị dữ liệu -->
                                                <?php 
                                                if($field['type'] === 'image' && $val) {
                                                    echo '<img src="'.getImageUrl($val).'" style="max-height: 40px; max-width: 80px; object-fit: cover; border-radius: 4px;" alt="image">';
                                                } else {
                                                    echo htmlspecialchars(mb_substr(strip_tags((string)$val), 0, 40)) . (mb_strlen(strip_tags((string)$val)) > 40 ? '...' : '');
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                            data-id="<?= $item->id_code ?>" data-field="is_active" 
                                            data-url="<?= route('admin.block_item.update_status_ajax', ['block_id' => $block->id_code]) ?>" 
                                            <?= $item->is_active ? 'checked' : '' ?>>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $colspan ?>" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-file-lines fs-1 mb-2"></i><br>
                                    Chưa có mục nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white clearfix py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-muted small">
                        Hiển thị <?= count($items ?? []) ?> / <?= method_exists($items, 'total') ? $items->total() : 0 ?> mục
                    </div>
                    <div class="col-md-8 text-end pagination-right-sm">
                        <?= method_exists($items, 'links') ? $items->links() : '' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check all
    const checkAll = document.querySelector('.check-all');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            toggleBulkApply();
        });
    }

    // Row check
    document.querySelectorAll('.row-check').forEach(checkbox => {
        checkbox.addEventListener('change', toggleBulkApply);
    });

    function toggleBulkApply() {
        const checkedCount = document.querySelectorAll('.row-check:checked').length;
        const btnBulkApply = document.getElementById('btnBulkApply');
        const bulkActionSelect = document.getElementById('bulkActionSelect');
        if (btnBulkApply && bulkActionSelect) {
            btnBulkApply.disabled = checkedCount === 0 || bulkActionSelect.value === '';
        }
    }

    const bulkActionSelect = document.getElementById('bulkActionSelect');
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', toggleBulkApply);
    }

    // Bulk action apply
    const btnBulkApply = document.getElementById('btnBulkApply');
    if (btnBulkApply) {
        btnBulkApply.addEventListener('click', function() {
            const select = document.getElementById('bulkActionSelect');
            if (select.value === 'delete') {
                const option = select.options[select.selectedIndex];
                const url = option.getAttribute('data-url');
                const confirmMsg = option.getAttribute('data-confirm');
                
                const checkedIds = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
                
                if (checkedIds.length > 0) {
                    if (confirm(confirmMsg)) {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: 'ids=' + encodeURIComponent(JSON.stringify(checkedIds))
                        })
                        .then(res => res.json())
                        .then(res => {
                            if(res.success) {
                                alert(res.message);
                                window.location.reload();
                            } else {
                                alert(res.message);
                            }
                        });
                    }
                }
            }
        });
    }

    // Confirm delete
    document.querySelectorAll('.confirm-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if(!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Toggle status ajax
    document.querySelectorAll('.ajax-toggle-status').forEach(cb => {
        cb.addEventListener('change', function() {
            const url = this.getAttribute('data-url');
            const id = this.getAttribute('data-id');
            const field = this.getAttribute('data-field');
            const value = this.checked ? 1 : 0;
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `id=${id}&field=${field}&value=${value}`
            })
            .then(res => res.json())
            .then(res => {
                // optional: show toast
            });
        });
    });

    // Sortable JS
    if (typeof Sortable === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
        script.onload = initSortable;
        document.head.appendChild(script);
    } else {
        initSortable();
    }

    function initSortable() {
        const el = document.getElementById('sortable-items');
        if (el) {
            Sortable.create(el, {
                handle: '.cursor-move',
                animation: 150,
                onEnd: function (evt) {
                    const itemEls = el.querySelectorAll('.row-check');
                    const ids = Array.from(itemEls).map(cb => cb.value);
                    
                    fetch('<?= route('admin.block_item.update_sort', ['block_id' => $block->id_code]) ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'ids=' + encodeURIComponent(JSON.stringify(ids))
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            console.log('Sorted successfully');
                            // optional toast
                        } else {
                            alert('Lỗi: ' + res.message);
                        }
                    });
                }
            });
        }
    }
});
</script>
<style>
.cursor-move { cursor: grab; }
.cursor-move:active { cursor: grabbing; }
.sortable-ghost { opacity: 0.4; background-color: #f8f9fa; }
</style>
