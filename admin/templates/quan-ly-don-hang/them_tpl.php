<?php
/**
 * Chi tiết đơn hàng (view only + form POST → a=save).
 * Không ghi DB trong template.
 */
$canEdit  = $d->checkPermission_edit($id_module) == 1;
$MODULE   = 'quan-ly-don-hang';
$order    = $order ?? ($items[0] ?? null);
$lines    = is_array($order_lines ?? null) ? $order_lines : [];
$logs     = is_array($order_logs ?? null) ? $order_logs : [];
$subtotal = (float) ($order_subtotal ?? 0);
$grand    = (float) ($order_grand ?? 0);

if (empty($order)) {
    echo '<div class="app-content"><div class="container-fluid">';
    echo '<div class="alert alert-warning">Không tìm thấy đơn hàng.</div>';
    echo '<a class="btn btn-sm btn-outline-secondary" href="' . e(admin_url(['p' => $MODULE, 'a' => 'man'])) . '">Quay lại</a>';
    echo '</div></div>';
    return;
}

$id   = (int) $order['id'];
$paid = (int) ($order['trangthai_thanhtoan'] ?? 0) === 1;
$st   = (int) ($order['trangthai_xuly'] ?? 0);
$giam = (float) ($order['so_tien_giam'] ?? 0);
$ship = (float) ($order['phi_vanchuyen'] ?? 0);
$box  = ((int) ($order['has_box'] ?? 0) === 1) ? (float) ($order['phi_thung'] ?? 0) : 0;
?>

<?php component('page_header', [
    'title'      => 'Chi tiết đơn hàng',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Đơn hàng', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => (string) ($order['ma_dh'] ?? '')],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-secondary">Mã đơn:</span>
                <strong class="text-danger fs-5"><?= e((string) $order['ma_dh']) ?></strong>
                <?= donhang_trangthai_badge($st) ?>
                <?= donhang_thanhtoan_badge($order['trangthai_thanhtoan'] ?? 0) ?>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($canEdit && $st === 0): ?>
                    <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'accept', 'id' => $id])) ?>"
                       class="btn btn-sm btn-warning"
                       onclick="return confirm('Tiếp nhận đơn hàng này?');">
                        <i class="fa-solid fa-inbox me-1"></i>Tiếp nhận đơn
                    </a>
                <?php endif; ?>
                <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Danh sách
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <?php component('card_open', ['title' => 'Sản phẩm trong đơn']); ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:50px">STT</th>
                                <th>Sản phẩm</th>
                                <th class="text-end">Đơn giá</th>
                                <th class="text-end">SL</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lines)): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">Không có sản phẩm</td></tr>
                            <?php else: ?>
                                <?php foreach ($lines as $k => $ln): ?>
                                    <?php
                                        $displayName = (string) ($ln['ten_sp'] ?? '');
                                        $displayAttr = (string) ($ln['thuoc_tinh'] ?? '');

                                        // Fallback hiển thị thông minh nếu dữ liệu cũ lỡ lưu chuỗi JSON thô
                                        if (!empty($displayAttr) && (substr($displayAttr, 0, 1) === '{' || substr($displayAttr, 0, 1) === '[')) {
                                            $parsedJson = json_decode($displayAttr, true);
                                            if (is_array($parsedJson)) {
                                                if (($displayName === 'Sản phẩm CRM' || empty($displayName)) && !empty($parsedJson['name'])) {
                                                    $displayName = $parsedJson['name'];
                                                }
                                                
                                                $attrParts = [];
                                                if (!empty($parsedJson['attributes']) && is_array($parsedJson['attributes'])) {
                                                    foreach ($parsedJson['attributes'] as $at) {
                                                        $aVal = is_array($at) ? ($at['value'] ?? '') : $at;
                                                        if ($aVal) $attrParts[] = $aVal;
                                                    }
                                                } elseif (!empty($parsedJson['sort_variation_attributes'])) {
                                                    $attrParts[] = str_replace('|', ' - ', $parsedJson['sort_variation_attributes']);
                                                }
                                                $displayAttr = !empty($attrParts) ? implode(' - ', $attrParts) : '';
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $k + 1 ?></td>
                                        <td>
                                            <div><?= e($displayName) ?></div>
                                            <?php if (!empty($displayAttr)): ?>
                                                <div class="small text-danger"><?= e($displayAttr) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-nowrap"><?= number_format($ln['gia_ban'], 0, ',', '.') ?></td>
                                        <td class="text-end"><?= (int) $ln['so_luong'] ?></td>
                                        <td class="text-end text-nowrap"><?= number_format(((float) $ln['gia_ban']) * ((int) $ln['so_luong']), 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Tạm tính</th>
                                <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?></td>
                            </tr>
                            <?php if ($giam > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Giảm giá<?= !empty($order['ma_giamgia']) ? ' (' . e($order['ma_giamgia']) . ')' : '' ?></th>
                                <td class="text-end text-danger">-<?= number_format($giam, 0, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($ship > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Phí vận chuyển</th>
                                <td class="text-end"><?= number_format($ship, 0, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($box > 0): ?>
                            <tr>
                                <th colspan="4" class="text-end">Phí đóng thùng</th>
                                <td class="text-end"><?= number_format($box, 0, ',', '.') ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="4" class="text-end">Tổng thanh toán</th>
                                <td class="text-end text-danger fw-semibold"><?= number_format($grand, 0, ',', '.') ?><sup>đ</sup></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php component('card_close'); ?>
            </div>

            <div class="col-lg-4">
                <?php component('card_open', ['title' => 'Thông tin khách hàng']); ?>
                <table class="table table-sm mb-0">
                    <tr>
                        <th class="text-secondary" style="width:110px">Họ tên</th>
                        <td><?= e((string) ($order['ho_ten'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Điện thoại</th>
                        <td>
                            <?php if (!empty($order['dien_thoai'])): ?>
                                <a href="tel:<?= e($order['dien_thoai']) ?>"><?= e($order['dien_thoai']) ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Email</th>
                        <td>
                            <?php if (!empty($order['email'])): ?>
                                <a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Địa chỉ</th>
                        <td><?= e((string) ($order['dia_chi'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Ghi chú KH</th>
                        <td><?= e((string) ($order['loi_nhan'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">PTTT</th>
                        <td><?= e((string) ($order['thanh_toan'] ?? '')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-secondary">Ngày đặt</th>
                        <td><?= e((string) ($order['ngay_dathang'] ?? '')) ?></td>
                    </tr>
                </table>
                <?php component('card_close'); ?>
            </div>
            <div class="col-lg-3">
                <?php component('card_open', ['title' => 'Xử lý đơn hàng']); ?>

                <?php if (!$canEdit): ?>
                    <div class="alert alert-secondary small mb-0">Bạn không có quyền cập nhật đơn.</div>
                <?php elseif ($st === 0): ?>
                    <div class="alert alert-warning small">
                        Đơn mới chưa tiếp nhận. Bấm <strong>Tiếp nhận đơn</strong> ở trên để bắt đầu xử lý.
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(admin_url(['p' => $MODULE, 'a' => 'save', 'id' => $id])) ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <div class="mb-3">
                            <label class="form-label" for="trangthai">Trạng thái xử lý</label>
                            <select class="form-select form-select-sm" name="trangthai" id="trangthai">
                                <?php foreach (donhang_trangthai_map() as $k => $m): ?>
                                    <?php if ((int) $k === 0) { continue; } /* không cho về "Đơn mới" */ ?>
                                    <option value="<?= (int) $k ?>" <?= $st === (int) $k ? 'selected' : '' ?>>
                                        <?= e($m['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Thanh toán</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="trangthai_thanhtoan"
                                       id="paid0" value="0" <?= !$paid ? 'checked' : '' ?>>
                                <label class="form-check-label" for="paid0">Chưa TT</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="trangthai_thanhtoan"
                                       id="paid1" value="1" <?= $paid ? 'checked' : '' ?>>
                                <label class="form-check-label" for="paid1">Đã TT</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="ghichu">Ghi chú xử lý</label>
                            <textarea class="form-control form-control-sm" name="ghichu" id="ghichu" rows="3"
                                      placeholder="Nhập ghi chú..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Lưu cập nhật
                        </button>
                    </form>
                <?php endif; ?>

                <hr class="my-3">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mb-2" id="btn-toggle-ls">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Lịch sử
                    <span class="badge text-bg-secondary ms-1"><?= count($logs) ?></span>
                </button>
                <div id="list-ls" class="d-none">
                    <?php if (empty($logs)): ?>
                        <div class="small text-secondary">Chưa có lịch sử.</div>
                    <?php else: ?>
                        <?php foreach ($logs as $ls): ?>
                            <?php $code = (int) ($ls['trang_thai_xuly'] ?? 0); ?>
                            <div class="border-bottom pb-2 mb-2 small">
                                <div class="fw-semibold"><?= e(donhang_ls_label($code)) ?></div>
                                <?php if (($ls['ghi_chu'] ?? '') !== ''): ?>
                                    <div class="text-secondary fst-italic"><?= e($ls['ghi_chu']) ?></div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between text-secondary mt-1">
                                    <span><?= e(date('d/m/Y H:i', strtotime($ls['ngay_xuly'] ?? 'now'))) ?></span>
                                    <span><?= e((string) ($ls['nguoi_xuly'] ?? '')) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php component('card_close'); ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#btn-toggle-ls').on('click', function () {
        $('#list-ls').toggleClass('d-none');
    });
});
</script>
