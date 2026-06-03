<?php
// Variables passed from OrderController:
// $order_id, $phone, $order, $order_details, $history, $error, $status, $steps, $total_details_price

function getStepClass($step_val, $current_status)
{
    if ($current_status == 4)
        return ''; // Returned case
    if ($current_status >= $step_val) {
        return $current_status > $step_val ? 'completed' : 'active';
    }
    return '';
}
?>

<div class="block py-5 bg-light min-vh-100">
    <div class="container">
        <div class="tracking-wrapper">

            <!-- Search Section -->
            <div class="tracking-search-card mb-5">
                <div class="text-center mb-4">
                    <h1 class="fw-bold h2 mb-2">Tra cứu vận đơn</h1>
                    <p class="text-muted">Nhập mã đơn hàng và số điện thoại để theo dõi lộ trình đơn hàng của bạn.</p>
                </div>

                <form action="<?= route('order.tracking') ?>" method="GET" class="form-tracking">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-uppercase">Mã đơn hàng</label>
                            <input type="text" name="id" class="form-control" placeholder="Ví dụ: DH-12345"
                                value="<?= htmlspecialchars($order_id) ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-uppercase">Số điện thoại</label>
                            <input type="text" name="p" class="form-control"
                                placeholder="Nhập số điện thoại đã đặt hàng" value="<?= htmlspecialchars($phone) ?>"
                                required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-x text-white w-100 h-50 py-0 fw-bold bg-x">Tìm
                                kiếm</button>
                        </div>
                    </div>
                </form>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-4 mb-0 animate__animated animate__shakeX">
                        <i class="fa fa-exclamation-triangle me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
                <!-- Result Section -->
                <div class="animate__animated animate__fadeIn">

                    <!-- Progress Stepper -->
                    <div class="tracking-search-card mb-4">
                        <h4 class="fw-bold mb-5 border-bottom pb-3"><i class="fa fa-map-marker-alt text-x me-2"></i> Trạng
                            thái vận chuyển</h4>

                        <?php if ($status == 4): ?>
                            <div class="alert alert-warning text-center fw-bold py-3 mb-0">
                                <i class="fa fa-undo-alt me-2"></i> ĐƠN HÀNG ĐÃ ĐƯỢC HOÀN TRẢ
                            </div>
                        <?php else: ?>
                            <div class="tracking-stepper">
                                <?php foreach ($steps as $s): ?>
                                    <div class="step-item <?= getStepClass($s['active_val'], $status) ?>">
                                        <div class="step-icon">
                                            <i class="fa <?= $s['icon'] ?>"></i>
                                        </div>
                                        <div class="step-label"><?= $s['label'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-4">
                        <!-- Timeline -->
                        <div class="col-lg-7">
                            <div class="tracking-search-card h-100">
                                <h4 class="fw-bold mb-4 border-bottom pb-3">Chi tiết lộ trình</h4>
                                <div class="tracking-timeline">
                                    <?php if (!empty($history)): ?>
                                        <?php foreach ($history as $idx => $h): ?>
                                            <div class="timeline-item <?= $idx === 0 ? 'latest' : '' ?>">
                                                <div class="timeline-date"><?= date('H:i - d/m/Y', strtotime($h['ngay_xuly'])) ?>
                                                </div>
                                                <div class="timeline-status">
                                                    <?php
                                                    switch ($h['trang_thai_xuly']) {
                                                        case 1:
                                                            echo "Đang xử lý";
                                                            break;
                                                        case 2:
                                                            echo "Đang giao hàng";
                                                            break;
                                                        case 3:
                                                            echo "Đã giao hàng";
                                                            break;
                                                        case 4:
                                                            echo "Trả hàng";
                                                            break;
                                                        case 5:
                                                            echo "Đã thanh toán";
                                                            break;
                                                        case 6:
                                                            echo "Chưa thanh toán";
                                                            break;
                                                        default:
                                                            echo "Đặt hàng thành công";
                                                            break;
                                                    }
                                                    ?>
                                                </div>
                                                <?php if ($h['ghi_chu']): ?>
                                                    <div class="timeline-desc"><?= htmlspecialchars($h['ghi_chu']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="timeline-item latest">
                                            <div class="timeline-date">
                                                <?= date('H:i - d/m/Y', strtotime($order['ngay_dathang'])) ?></div>
                                            <div class="timeline-status">Đơn hàng đã được tiếp nhận</div>
                                            <div class="timeline-desc">Hệ thống đã ghi nhận đơn hàng mã <?= $order['ma_dh'] ?>.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order Info -->
                        <div class="col-lg-5">
                            <div class="tracking-search-card h-100">
                                <h4 class="fw-bold mb-4 border-bottom pb-3">Thông tin nhận hàng</h4>
                                <table class="table table-borderless small mb-0">
                                    <tr>
                                        <td class="text-muted" width="120">Người nhận:</td>
                                        <td class="fw-bold"><?= htmlspecialchars($order['ho_ten']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Điện thoại:</td>
                                        <td class="fw-bold"><?= htmlspecialchars($order['dien_thoai']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Địa chỉ:</td>
                                        <td class="fw-bold"><?= htmlspecialchars($order['dia_chi']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Phương thức:</td>
                                        <td class="fw-bold"><?= htmlspecialchars($order['thanh_toan']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tổng tiền:</td>
                                        <td class="fw-bold text-x h5 mb-0">
                                            <?= renderPrice($order['so_tien_giam'] + $order['phi_vanchuyen'] + $total_details_price) ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="tracking-search-card mt-4">
                        <h4 class="fw-bold mb-4 border-bottom pb-3">Sản phẩm đã đặt</h4>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">Sản phẩm</th>
                                        <th class="text-end">Đơn giá</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_details as $item): ?>
                                        <tr>
                                            <td width="60">
                                                <img src="<?= getImageUrl($item['hinh_sp']) ?>" width="50" height="50"
                                                    style="object-fit: cover; border-radius: 4px;">
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($item['ten_sp']) ?></div>
                                                <div class="text-danger small"><?= htmlspecialchars($item['thuoc_tinh']) ?>
                                                </div>
                                            </td>
                                            <td class="text-end"><?= renderPrice($item['gia_ban']) ?></td>
                                            <td class="text-center"><?= $item['so_luong'] ?></td>
                                            <td class="text-end fw-bold">
                                                <?= renderPrice($item['gia_ban'] * $item['so_luong']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            <?php elseif (!$error && $order_id): ?>
                <!-- This case should be handled by $error above, but as a fallback -->
            <?php endif; ?>

            <?php if (!$order && !$error): ?>
                <!-- Hint section for empty search -->
                <div class="text-center p-5 mt-4">
                    <i class="fa fa-search fa-4x text-light mb-3"></i>
                    <h5 class="text-muted">Bạn có thể tìm mã đơn hàng trong email xác nhận hoặc tin nhắn SMS gửi từ cửa
                        hàng.</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>