<?php
$title = $title ?? 'Chi tiết Đơn hàng';
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Đơn hàng', 'url' => route('admin.order.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            
            <!-- Left Column: Order details & items -->
            <div class="col-md-9">
                <!-- Customer Info -->
                <div class="card card-outline card-primary mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title fw-bold mb-0">Thông tin Khách hàng</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><i class="fa-solid fa-user text-muted me-2"></i> <strong><?= htmlspecialchars($order->customer_name) ?></strong></p>
                                <p class="mb-1"><i class="fa-solid fa-phone text-muted me-2"></i> <?= htmlspecialchars($order->customer_phone) ?></p>
                                <?php if ($order->customer_email): ?>
                                    <p class="mb-1"><i class="fa-solid fa-envelope text-muted me-2"></i> <?= htmlspecialchars($order->customer_email) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><i class="fa-solid fa-location-dot text-muted me-2"></i> <strong>Địa chỉ giao hàng:</strong></p>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($order->shipping_address)) ?></p>
                            </div>
                        </div>
                        <?php if ($order->customer_note): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Ghi chú của khách:</strong><br>
                            <?= nl2br(htmlspecialchars($order->customer_note)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Items -->
                <div class="card card-outline card-primary mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title fw-bold mb-0">Sản phẩm đã đặt</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">Đơn giá</th>
                                        <th class="text-center">SL</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item->product_image): ?>
                                                    <img src="<?= getImageUrl($item->product_image) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover" class="rounded me-3 border">
                                                <?php else: ?>
                                                    <div class="bg-light rounded me-3 border d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="fa fa-box text-muted"></i></div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($item->product_name) ?></div>
                                                    <?php if ($item->attributes_info): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($item->attributes_info) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center"><?= number_format($item->price) ?> đ</td>
                                        <td class="text-center">x<?= $item->quantity ?></td>
                                        <td class="text-end fw-bold"><?= number_format($item->total) ?> đ</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-end">
                        <div class="row justify-content-end">
                            <div class="col-md-6 col-lg-5">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Tạm tính:</span>
                                    <span><?= number_format($order->subtotal) ?> đ</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Phí vận chuyển:</span>
                                    <span><?= number_format($order->shipping_fee) ?> đ</span>
                                </div>
                                <?php if ($order->tax_amount > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Thuế:</span>
                                    <span><?= number_format($order->tax_amount) ?> đ</span>
                                </div>
                                <?php endif; ?>
                                <?php if ($order->discount_amount > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Giảm giá:</span>
                                    <span>-<?= number_format($order->discount_amount) ?> đ</span>
                                </div>
                                <?php endif; ?>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <strong class="fs-5">Cần thanh toán:</strong>
                                    <strong class="fs-5 text-danger"><?= number_format($order->grand_total) ?> đ</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Timeline -->
                <div class="card card-outline card-primary mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title fw-bold mb-0">Lịch sử cập nhật</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($history) > 0): ?>
                            <ul class="timeline mb-0">
                                <?php foreach ($history as $h): ?>
                                <li class="timeline-item">
                                    <div class="timeline-item-icon bg-primary"><i class="fa-solid fa-clock"></i></div>
                                    <div class="timeline-item-description">
                                        <span class="fw-bold"><?= date('d/m/Y H:i:s', strtotime($h->created_at)) ?></span> 
                                        - Thực hiện bởi: <?= $h->created_by == 0 ? 'Hệ thống/Khách' : 'Admin ID '.$h->created_by ?><br>
                                        <span class="text-muted"><?= htmlspecialchars($h->note) ?></span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted mb-0">Chưa có lịch sử cập nhật.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Status Update -->
            <div class="col-md-3">
                <div class="card card-outline card-secondary mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title fw-bold mb-0">Cập nhật Trạng thái</h5>
                    </div>
                        <form id="updateStatusForm" action="<?= route('admin.order.updateStatus', ['id' => $order->id]) ?>" method="POST">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái Xử lý</label>
                                    <select name="order_status" class="form-select">
                                        <option value="0" <?= $order->order_status == 0 ? 'selected' : '' ?>>Chờ xác nhận</option>
                                        <option value="1" <?= $order->order_status == 1 ? 'selected' : '' ?>>Đang chuẩn bị</option>
                                        <option value="2" <?= $order->order_status == 2 ? 'selected' : '' ?>>Đang giao hàng</option>
                                        <option value="3" <?= $order->order_status == 3 ? 'selected' : '' ?>>Đã hoàn thành</option>
                                        <option value="4" <?= $order->order_status == 4 ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Trạng thái Thanh toán</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="0" <?= $order->payment_status == 0 ? 'selected' : '' ?>>Chưa thanh toán</option>
                                        <option value="1" <?= $order->payment_status == 1 ? 'selected' : '' ?>>Đã thanh toán</option>
                                        <option value="2" <?= $order->payment_status == 2 ? 'selected' : '' ?>>Đã hoàn tiền</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ghi chú thêm (Nhật ký)</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Nhập ghi chú (không bắt buộc)"></textarea>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end gap-1 flex-wrap bg-light">
                                <a href="<?= route('admin.order.print', ['id' => $order->id]) ?>" target="_blank" class="btn btn-info btn-sm text-white">
                                    <i class="fa-solid fa-print"></i> In hóa đơn
                                </a>
                                <a href="<?= route('admin.order.index') ?>" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-arrow-left"></i> Quay lại
                                </a>
                                <button type="submit" name="save_action" value="exit" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-save"></i> Lưu
                                </button>
                                <button type="submit" name="save_action" value="continue" class="btn btn-success btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                                </button>
                            </div>
                        </form>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.timeline {
    list-style: none;
    padding: 0;
    position: relative;
}
.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 20px;
    width: 2px;
    background: #e9ecef;
}
.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-left: 50px;
}
.timeline-item-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    text-align: center;
    line-height: 40px;
    color: #fff;
    z-index: 1;
}
</style>