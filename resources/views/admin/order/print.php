<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn - <?= htmlspecialchars($order->order_code) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; margin: 0; padding: 20px; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header-left h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header-right { text-align: right; }
        .details-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .bill-to h3, .order-details h3 { margin-top: 0; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th, table.items td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        table.items th { background-color: #f8f9fa; font-weight: bold; }
        table.items td.text-right, table.items th.text-right { text-align: right; }
        table.items td.text-center, table.items th.text-center { text-align: center; }
        .totals-section { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-table td { padding: 5px 0; }
        .totals-table td.text-right { text-align: right; }
        .totals-table .grand-total { font-weight: bold; font-size: 18px; border-top: 2px solid #333; padding-top: 10px; }
        .footer { margin-top: 50px; text-align: center; font-style: italic; color: #777; }
        
        @media print {
            .invoice-box { box-shadow: none; border: none; padding: 0; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice-box">
        <div class="header">
            <div class="header-left">
                <h1>Hóa Đơn Bán Hàng</h1>
                <p style="margin: 5px 0 0 0;">Cửa hàng Demo</p>
            </div>
            <div class="header-right">
                <strong>Mã đơn:</strong> <?= htmlspecialchars($order->order_code) ?><br>
                <strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($order->created_at)) ?>
            </div>
        </div>

        <div class="details-section">
            <div class="bill-to" style="width: 48%">
                <h3>Thông tin khách hàng</h3>
                <strong><?= htmlspecialchars($order->customer_name) ?></strong><br>
                ĐT: <?= htmlspecialchars($order->customer_phone) ?><br>
                Email: <?= htmlspecialchars($order->customer_email) ?><br>
                Địa chỉ: <?= nl2br(htmlspecialchars($order->shipping_address)) ?>
            </div>
            <div class="order-details" style="width: 48%">
                <h3>Trạng thái</h3>
                Trạng thái thanh toán: 
                <strong>
                    <?php
                    if ($order->payment_status == 1) echo 'Đã thanh toán';
                    elseif ($order->payment_status == 2) echo 'Đã hoàn tiền';
                    else echo 'Chưa thanh toán';
                    ?>
                </strong><br><br>
                <?php if ($order->customer_note): ?>
                    <strong>Ghi chú:</strong> <?= nl2br(htmlspecialchars($order->customer_note)) ?>
                <?php endif; ?>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th width="50" class="text-center">STT</th>
                    <th>Tên sản phẩm</th>
                    <th width="100" class="text-right">Đơn giá</th>
                    <th width="80" class="text-center">Số lượng</th>
                    <th width="120" class="text-right">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): ?>
                <tr>
                    <td class="text-center"><?= $i++ ?></td>
                    <td>
                        <?= htmlspecialchars($item->product_name) ?>
                        <?php if ($item->attributes_info): ?>
                            <br><small style="color:#666">(<?= htmlspecialchars($item->attributes_info) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?= number_format($item->price) ?> đ</td>
                    <td class="text-center"><?= $item->quantity ?></td>
                    <td class="text-right"><strong><?= number_format($item->total) ?> đ</strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Tạm tính:</td>
                    <td class="text-right"><?= number_format($order->subtotal) ?> đ</td>
                </tr>
                <tr>
                    <td>Phí vận chuyển:</td>
                    <td class="text-right"><?= number_format($order->shipping_fee) ?> đ</td>
                </tr>
                <?php if ($order->tax_amount > 0): ?>
                <tr>
                    <td>Thuế:</td>
                    <td class="text-right"><?= number_format($order->tax_amount) ?> đ</td>
                </tr>
                <?php endif; ?>
                <?php if ($order->discount_amount > 0): ?>
                <tr>
                    <td>Giảm giá:</td>
                    <td class="text-right">-<?= number_format($order->discount_amount) ?> đ</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="grand-total">TỔNG CỘNG:</td>
                    <td class="text-right grand-total"><?= number_format($order->grand_total) ?> đ</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Cảm ơn quý khách đã mua hàng!
        </div>
    </div>
</body>
</html>
