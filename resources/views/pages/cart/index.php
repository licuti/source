<?php
// Variables $tong, $phi_ship, $so_giam, $vat_rate, $vat_type, $list_coupons, and $cartItems are injected via CartController.
?>

<div class="block">
    <div class="container-fluid">
        <?php if (isset($_SESSION['cart'])): ?>
            <div class="row">
                <!-- Danh sách sản phẩm -->
                <div class="col-lg-8">
                    <h3 class="fw-bold text-x"><?= __(58) ?></h3>
                    <p><?= __(49) ?> <span class="text-danger"><?= count($_SESSION['cart']) ?></span></p>

                    <div class="table-responsive shopping-summery">
                        <table class="table" id="cart-table">
                            <thead>
                                <tr>
                                    <th scope="col" colspan="2"><?= __(98) ?></th>
                                    <th scope="col"><?= __(38) ?></th>
                                    <th scope="col"><?= __(39) ?></th>
                                    <th scope="col"><?= __(97) ?></th>
                                    <th scope="col" class="text-center"><?= __(127) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($cartItems as $item):
                                    $key = $item['key'];
                                    $gia_sp = $item['gia_sp'];
                                    $so_luong = $item['so_luong'];
                                    ?>
                                    <tr id="cart-row-<?= $key ?>">
                                        <td class="col-image" style="width: 100px;">
                                            <div class="ratio ratio-1x1" style="width: 80px;">
                                                <img class="image-cover" src="<?= $item['hinh_anh'] ?>"
                                                    alt="<?= htmlspecialchars($item['ten_sp']) ?>">
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <a class="fw-bold" href="<?= $item['url_sp'] ?>"><?= $item['ten_sp'] ?></a>
                                            <?php if (!empty($item['thuoctinh_text'])): ?>
                                                <div style="font-size: 13px; color: #888; margin-top: 4px;">
                                                    <?= $item['thuoctinh_text'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle">
                                            <span class="fw-bold text-x"><?= renderPrice($gia_sp) ?></span>
                                        </td>
                                        <td class="text-center" style="vertical-align: middle;">
                                            <div class="box-quantity box-quantity-cart">
                                                <a href="javascript:void(0)" class="btn-sub qty-down"
                                                    data-key="<?= $key ?>">-</a>
                                                <input type="text" class="num_sl" id="sl_<?= $key ?>" data-key="<?= $key ?>"
                                                    data-price="<?= $gia_sp ?>" value="<?= $so_luong ?>" data-min="1"
                                                    data-max="100" />
                                                <a href="javascript:void(0)" class="btn-add qty-up" data-key="<?= $key ?>">+</a>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-x fs-18 row-total" id="total-<?= $key ?>">
                                                <?= renderPrice($item['tong_gia']) ?>
                                            </div>
                                        </td>
                                        <td class="action text-center align-middle">
                                    <a href="javascript:void(0)" class="btn-delete-cart" data-key="<?= $key ?>" id="delete-<?= $key ?>">
                                        <i class="fa-regular fa-trash text-danger"></i>
                                    </a>
                                </td>
                                    </tr>
                                    <?php $i++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Nút tiếp tục mua sắm -->
                    <div class="d-flex justify-content-between mt-3">
                        <a href="<?= route('home') ?>" class="btn-custom btn-x">
                            <i class="fa fa-long-arrow-left" aria-hidden="true"></i> <?= __(99) ?>
                        </a>
                    </div>
                </div>

                <!-- Tóm tắt đơn hàng -->
                <div class="col-lg-4">
                    <div class="border rounded p-4" id="cart-summary">
                        <h5 class="fw-bold mb-3">Tóm tắt đơn hàng</h5>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính</span>
                            <span id="summary-subtotal" class="fw-bold"><?= renderPrice($tong) ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2 text-success" id="summary-discount-row"
                            <?php if ($so_giam <= 0): ?>style="display:none"<?php endif; ?>>
                            <span>Giảm giá</span>
                            <span class="fw-bold" id="summary-discount">- <?= renderPrice($so_giam) ?></span>
                        </div>

                        <?php if ($phi_ship > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí vận chuyển</span>
                                <span class="fw-bold"><?= renderPrice($phi_ship) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $vat_res = calculateVAT($tong - $so_giam, $vat_rate, $vat_type);
                        $vat_amount = $vat_res['amount'];
                        $tong_final = $vat_res['total'] + $phi_ship;
                        ?>
                        <?php if ($vat_amount > 0 || $vat_type > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Thuế VAT (<?= $vat_rate ?>%)</span>
                            <span id="summary-vat-amount" class="fw-bold"><?= renderPrice($vat_amount) ?></span>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between fs-5 mb-3">
                            <strong>Tổng cộng</strong>
                            <strong class="text-x"
                                id="summary-total"><?= renderPrice($tong_final) ?></strong>
                        </div>

                        <a href="<?= route('checkout.index') ?>" class="btn-custom btn-x w-100 text-center d-block">
                            <?= __(26) ?> <i class="fa fa-long-arrow-right ms-2"></i>
                        </a>

                        <!-- Mã giảm giá -->
                        <div class="mt-3" id="coupon-section">
                            <?php if (!empty($_SESSION['ma_sale'])): ?>
                            <div class="alert alert-success d-flex align-items-center justify-content-between py-2 mb-2" id="applied-coupon">
                                <span>
                                    <i class="fa-solid fa-tag me-1"></i>
                                    <strong><?= htmlspecialchars($_SESSION['ma_sale']) ?></strong>
                                    &nbsp;(<?= htmlspecialchars($_SESSION['giatri_sale'] ?? '') ?>)
                                </span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="btn-remove-sale" title="Xóa mã">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <?php endif; ?>

                            <div class="input-group">
                                <input type="text" class="form-control" id="ma_sale"
                                    placeholder="Nhập mã giảm giá"
                                    value="<?= htmlspecialchars($_SESSION['ma_sale'] ?? '') ?>">
                                <button class="btn btn-outline-secondary" type="button" onclick="check_sale()">Áp dụng</button>
                            </div>

                            <!-- Danh sách mã có sẵn (server-side) -->
                            <?php if (!empty($list_coupons)): ?>
                            <div class="mt-2">
                                <div style="font-size:12px;color:#999;margin-bottom:4px;">Mã có thể dùng:</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($list_coupons as $cp):
                                        $cp_suffix = ((int)$cp['don_vi'] === 1) ? '%' : 'd';
                                        $cp_lbl    = $cp['ma'] . ' (-' . $cp['gia_tri'] . $cp_suffix . ')';
                                        $cp_title  = $cp['dieu_kien'] > 0
                                                        ? 'Toi thieu ' . number_format($cp['dieu_kien']) . 'd'
                                                        : 'Khong gioi han gia tri don';
                                    ?>
                                    <span class="badge bg-light text-dark border"
                                          style="cursor:pointer;font-size:12px;"
                                          title="<?= htmlspecialchars($cp_title) ?>"
                                          onclick="check_sale('<?= addslashes($cp['ma']) ?>')">
                                        <i class="fa-solid fa-tag me-1" style="font-size:10px;"></i><?= htmlspecialchars($cp_lbl) ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif (isset($_GET['success']) && $_GET['success'] != ''):
            $donhang = $d->simple_fetch("select * from #_dathang where ma_dh = '" . $_GET['success'] . "'");
            if (count($donhang) == 0) {
                $d->location(URLPATH . $com . ".html");
                exit();
            }
            $donhang_ct = $d->o_fet("select * from #_dathang_chitiet where id_dh = " . $donhang['id'] . " ");
            ?>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="calculate-shiping my-5 p-3 border-radius-15 border">
                        <h3 class="mb-3 text-center">ĐẶT HÀNG THÀNH CÔNG</h3>
                        <p class="text-center"><span class="font-lg text-muted">Mã đơn hàng: </span><strong
                                class="text-brand"><?= $_GET['success'] ?></strong></p>
                        <div class="mb-2"><b>Khách hàng: </b><?= $donhang['ho_ten'] ?></div>
                        <div class="mb-2"><b>Điện thoại: </b><?= $donhang['dien_thoai'] ?></div>
                        <div class="mb-2"><b>Email: </b><?= $donhang['email'] ?></div>
                        <div class="mb-3"><b>Địa chỉ: </b><?= $donhang['dia_chi'] ?></div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <td>Tên SP</td>
                                    <td class="text-end">Đơn giá</td>
                                    <td class="text-end">SL</td>
                                    <td class="text-end">Thành tiền</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tong_sl = $tong_thanhtien = 0;
                                foreach ($donhang_ct as $dct):
                                    $tong_sl += $dct['so_luong'];
                                    $tong_thanhtien += $dct['gia_ban'] * $dct['so_luong'];
                                    ?>
                                    <tr>
                                        <td><?= $dct['ten_sp'] ?><br><small><?= $dct['thuoc_tinh'] ?></small></td>
                                        <td class="text-end"><?= renderPrice($dct['gia_ban']) ?></td>
                                        <td class="text-end"><?= number_format($dct['so_luong']) ?></td>
                                        <td class="text-end"><?= renderPrice($dct['gia_ban'] * $dct['so_luong']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2">Tổng:</th>
                                    <th class="text-end"><?= number_format($tong_sl) ?></th>
                                    <th class="text-end"><?= renderPrice($tong_thanhtien) ?></th>
                                </tr>
                                <?php if ($donhang['vat_amount'] > 0): ?>
                                <tr>
                                    <th colspan="3" class="text-end">Thuế VAT:</th>
                                    <th class="text-end"><?= renderPrice($donhang['vat_amount']) ?></th>
                                </tr>
                                <?php endif; ?>
                                <?php if ($donhang['phi_vanchuyen'] > 0): ?>
                                <tr>
                                    <th colspan="3" class="text-end">Phí vận chuyển:</th>
                                    <th class="text-end"><?= renderPrice($donhang['phi_vanchuyen']) ?></th>
                                </tr>
                                <?php endif; ?>
                                <?php if ($donhang['so_tien_giam'] > 0): ?>
                                <tr>
                                    <th colspan="3" class="text-end">Giảm giá:</th>
                                    <th class="text-end">- <?= renderPrice($donhang['so_tien_giam']) ?></th>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                        <div class="mb-2"><b>Ghi chú: </b><?= $donhang['loi_nhan'] ?></div>
                        <div class="mb-2"><b>Thanh toán: </b><?= $donhang['thanh_toan'] ?></div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="well text-center py-5" style="font-size: 18px;">
                <p><?= __(42) ?></p>
                <a href="<?= route('home') ?>"><?= __(43) ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    var cartPrices = <?php
    $cp = [];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $k => $v) {
            if ((int) $v['id_sp'] > 0) {
                $cp[$k] = (float) $v['gia'];
            }
        }
    }
    echo json_encode($cp);
    ?>;
    var phi_ship = <?= (float) ($phi_ship ?? 0) ?>;
    var so_giam = <?= (float) ($so_giam ?? 0) ?>;
</script>
<!-- Giỏ hàng JS di chuyển sang shop.js -->