<?php 
$layout = 'admin.layouts.main'; 
$title = 'Cổng thanh toán';
?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Cổng thanh toán', 'url' => '']
    ],
    'actions' => [
        ['label' => 'Thêm cổng thanh toán', 'icon' => 'fa-plus', 'url' => route('admin.payment.create'), 'class' => 'btn-success btn-sm'],
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                    <form action="<?= route('admin.payment.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <a href="<?= route('admin.payment.create') ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-plus me-1"></i> Thêm mới
                        </a>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40" class="text-center"><i class="fa-solid fa-arrows-up-down text-muted"></i></th>
                                <th>Tên phương thức</th>
                                <th>Mã hệ thống (Code)</th>
                                <th width="120" class="text-center">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-items">
                            <?php if (empty($methods)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-credit-card fs-1 mb-2"></i><br>
                                    Chưa có phương thức thanh toán nào.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($methods as $item): ?>
                                <tr class="wp-row" data-id="<?= $item->id ?>">
                                    <td class="text-center align-middle">
                                        <i class="fa-solid fa-grip-vertical text-muted cursor-move handle" style="cursor: grab;"></i>
                                    </td>
                                    <td class="align-middle">
                                        <strong class="text-primary">
                                            <a href="<?= route('admin.payment.edit', ['id' => $item->id]) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($item->name) ?>
                                            </a>
                                        </strong>
                                        <?php 
                                        $actions = [
                                            'edit' => [
                                                'label' => 'Cấu hình', 
                                                'url' => route('admin.payment.edit', ['id' => $item->id]), 
                                                'class' => 'text-primary'
                                            ],
                                            'delete' => [
                                                'label' => 'Xóa', 
                                                'url' => '#', 
                                                'class' => 'text-danger btn-delete-payment',
                                                'attributes' => 'data-id="'.$item->id.'"'
                                            ]
                                        ];
                                        echo view('admin.components.row_actions', ['actions' => $actions]);
                                        ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($item->code) ?></span>
                                    </td>

                                    <td class="text-center align-middle">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input ajax-toggle-status" type="checkbox" 
                                                data-url="<?= route('admin.payment.updateStatusAjax') ?>"
                                                data-id="<?= $item->id ?>" data-table="db_payment_methods" data-field="is_active"
                                                <?= $item->is_active ? 'checked' : '' ?>>
                                        </div>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xóa phương thức
    $(document).on('click', '.btn-delete-payment', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (confirm('Bạn có chắc muốn xóa cổng thanh toán này? Các ngôn ngữ liên quan cũng sẽ bị xóa.')) {
            $.post('<?= route("admin.payment.destroy") ?>', {
                id: id,
                _token: '<?= csrf_token() ?>'
            }, function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    });

    // Khởi tạo Sortable
    if (typeof Sortable === 'undefined') {
        let script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
        script.onload = initSortable;
        document.head.appendChild(script);
    } else {
        initSortable();
    }

    function initSortable() {
        const el = document.getElementById('sortable-items');
        if (!el) return;
        
        new Sortable(el, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                let ids = [];
                $('#sortable-items tr').each(function() {
                    let id = $(this).data('id');
                    if (id) ids.push(id);
                });

                if (ids.length > 0) {
                    $.post('<?= route("admin.payment.update_sort") ?>', {
                        ids: ids,
                        _token: '<?= csrf_token() ?>'
                    }, function(res) {
                        if(res.success) {
                            toastr.success('Đã cập nhật thứ tự.');
                        } else {
                            toastr.error('Lỗi khi cập nhật thứ tự.');
                        }
                    });
                }
            }
        });
    }
});
</script>

<style>
.sortable-ghost { opacity: 0.4; background-color: #f8f9fa; }
</style>
