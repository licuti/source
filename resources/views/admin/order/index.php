<?php
$title = $title ?? 'Quản lý Đơn hàng';
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => []
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header wp-toolbar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div id="bulkActionPanel" class="d-flex align-items-center flex-wrap gap-2">
                        <select id="bulkActionSelect" class="form-select form-select-sm w-auto">
                            <option value="">Hành động hàng loạt</option>
                            <!-- Bulk actions here if needed -->
                        </select>
                        <button type="button" id="btnBulkApply" class="btn btn-outline-secondary btn-sm" disabled>Áp dụng</button>
                    </div>
                    
                    <form action="<?= route('admin.order.index') ?>" method="GET" class="d-flex align-items-center flex-wrap gap-2 m-0">
                        <select name="order_status" class="form-select form-select-sm w-auto">
                            <option value="">Trạng thái đơn</option>
                            <option value="0" <?= ($order_status ?? '') === '0' ? 'selected' : '' ?>>Chờ xác nhận</option>
                            <option value="1" <?= ($order_status ?? '') === '1' ? 'selected' : '' ?>>Đang chuẩn bị</option>
                            <option value="2" <?= ($order_status ?? '') === '2' ? 'selected' : '' ?>>Đang giao hàng</option>
                            <option value="3" <?= ($order_status ?? '') === '3' ? 'selected' : '' ?>>Đã hoàn thành</option>
                            <option value="4" <?= ($order_status ?? '') === '4' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                        
                        <select name="payment_status" class="form-select form-select-sm w-auto">
                            <option value="">Thanh toán</option>
                            <option value="0" <?= ($payment_status ?? '') === '0' ? 'selected' : '' ?>>Chưa thanh toán</option>
                            <option value="1" <?= ($payment_status ?? '') === '1' ? 'selected' : '' ?>>Đã thanh toán</option>
                            <option value="2" <?= ($payment_status ?? '') === '2' ? 'selected' : '' ?>>Đã hoàn tiền</option>
                        </select>
                        
                        <input type="text" name="date_range" class="form-control form-control-sm daterange-picker" style="width: 180px;" placeholder="Ngày tạo..." value="<?= htmlspecialchars($date_range ?? '') ?>">
                        
                        <div class="input-group input-group-sm w-auto">
                            <input type="text" name="keyword" class="form-control" placeholder="Mã ĐH, SĐT..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                        </div>

                        <?php if (!empty($keyword) || ($order_status ?? '') !== '' || ($payment_status ?? '') !== '' || !empty($date_range)): ?>
                            <a href="<?= route('admin.order.index') ?>" class="btn btn-link btn-sm text-decoration-none text-muted">Hủy lọc</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0 text-sm">
                        <thead class="table-light">
                            <tr>
                                <th width="40" class="text-center">
                                    <input class="form-check-input check-all" type="checkbox" title="Chọn tất cả">
                                </th>
                                <th>Mã Đơn</th>
                                <th>Khách hàng</th>
                                <th>Ngày tạo</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="wp-row">
                                        <td class="text-center">
                                            <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item->order_code) ?></strong>
                                            <?php 
                                            $row_actions = [
                                                'view' => [
                                                    'label' => 'Xem chi tiết', 
                                                    'url' => route('admin.order.show', ['id' => $item->id]), 
                                                    'class' => 'text-primary'
                                                ],
                                                'print' => [
                                                    'label' => 'In hóa đơn', 
                                                    'url' => route('admin.order.print', ['id' => $item->id]), 
                                                    'class' => 'text-secondary',
                                                    'attributes' => 'target="_blank"'
                                                ]
                                            ];
                                            echo view('admin.components.row_actions', ['actions' => $row_actions]);
                                            ?>
                                        </td>
                                        <td>
                                            <div><strong><?= htmlspecialchars($item->customer_name) ?></strong></div>
                                            <div class="text-muted small"><?= htmlspecialchars($item->customer_phone) ?></div>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($item->created_at)) ?></td>
                                        <td><strong class="text-danger"><?= number_format($item->grand_total) ?> đ</strong></td>
                                        <td>
                                            <?php if ($item->payment_status == 1): ?>
                                                <span class="badge bg-success">Đã thanh toán</span>
                                            <?php elseif ($item->payment_status == 2): ?>
                                                <span class="badge bg-warning">Đã hoàn tiền</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Chưa thanh toán</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stClasses = [
                                                0 => 'bg-secondary', // Chờ
                                                1 => 'bg-info',      // Chuẩn bị
                                                2 => 'bg-primary',   // Đang giao
                                                3 => 'bg-success',   // Hoàn thành
                                                4 => 'bg-danger',    // Đã hủy
                                            ];
                                            $stNames = [
                                                0 => 'Chờ xác nhận',
                                                1 => 'Đang chuẩn bị',
                                                2 => 'Đang giao',
                                                3 => 'Hoàn thành',
                                                4 => 'Đã hủy'
                                            ];
                                            ?>
                                            <span class="badge <?= $stClasses[$item->order_status] ?? 'bg-secondary' ?>">
                                                <?= $stNames[$item->order_status] ?? 'N/A' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Không tìm thấy đơn hàng nào!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FOOTER: Pagination -->
            <?php if (isset($items) && method_exists($items, 'links')): ?>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Hiển thị <b><?= count($items) ?></b> / <b><?= $items->total() ?></b> mục
                        </div>
                        <div class="pagination-wrapper mb-0">
                            <?= $items->links() ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof jQuery !== 'undefined' && $.fn.daterangepicker) {
        $('.daterange-picker').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Xóa',
                applyLabel: 'Áp dụng',
                format: 'YYYY-MM-DD'
            }
        });
        $('.daterange-picker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });
        $('.daterange-picker').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    }
});
</script>
