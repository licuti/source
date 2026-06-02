<?php
// Variables passed from CheckoutController:
// $cartItems, $tong_tam_tinh, $phi_ship, $so_tien_giam, $vat_amount, 
// $vat_rate, $vat_type, $tong_thanh_toan, $phuongthucthanhtoan, $user_login, $d
?>
<div class="block">
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Form thông tin giao hàng -->
            <div class="col-lg-5 order-lg-1">
                <div class="box-form-checkout">
                    <h3 class="fw-bold text-x"><?= __(28) ?></h3>
                    <form method="POST" action="<?= route('checkout.store') ?>" class="form-checkout mt-3" id="form-checkout">
                        <div class="form-group">
                            <label><?= __(5) ?></label>
                            <input type="text" placeholder="<?= __(29) ?>" value="<?= htmlspecialchars($user_login['ho_ten'] ?? '') ?>" name="ho_ten" class="form-control" required />
                        </div>
                        <div class="form-group mt-3">
                            <label><?= __(6) ?></label>
                            <input type="text" placeholder="<?= __(32) ?>" value="<?= htmlspecialchars($user_login['dien_thoai'] ?? '') ?>" name="dien_thoai" class="form-control" required />
                        </div>
                        <div class="form-group mt-3">
                            <label>Email</label>
                            <input type="email" placeholder="<?= __(31) ?>" value="<?= htmlspecialchars($user_login['email'] ?? '') ?>" name="email" class="form-control" />
                        </div>

                        <div class="form-group mt-3">
                            <label><?= __(144) ?></label>
                            <select class="form-control" required name="code_tinh" id="code_tinh" onchange="get_huyen('code_tinh', 'code_huyen')">
                                <option value=""><?= __(145) ?></option>
                                <?php foreach ($d->getTinh('code,ten') as $tinh): ?>
                                <option value="<?= $tinh['code'] ?>"><?= $tinh['ten'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= __(146) ?></label>
                            <select class="form-control" required id="code_huyen" name="code_huyen" onchange="get_xa('code_huyen', 'code_xa')">
                                <option value=""><?= __(147) ?></option>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= __(146) ?></label>
                            <select class="form-control" required id="code_xa" name="code_xa" onchange="update_shipping_fee()">
                                <option value=""><?= __(147) ?></option>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= __(7) ?></label>
                            <input type="text" placeholder="<?= __(148) ?>" name="dia_chi" class="form-control" required />
                        </div>

                        <input type="hidden" name="phuongthucthanhtoan" id="txt_phuongthucthanhtoan" value="<?= $phuongthucthanhtoan[0]['ten'] ?? '' ?>" />

                        <div class="form-group mt-3">
                            <label><?= __(33) ?></label>
                            <textarea class="form-control" placeholder="<?= __(149) ?>" name="ghi_chu"></textarea>
                        </div>
                        <div class="mt-4">
                            <button class="btn-custom btn-x w-100" type="submit" name="dathang"><?= __(26) ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="col-lg-7 order-lg-2">
                <h3 class="fw-bold text-x"><?= __(58) ?></h3>
                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2"><?= __(98) ?></th>
                                <th class="text-center"><?= __(39) ?></th>
                                <th class="text-end"><?= __(97) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td style="width: 70px;">
                                    <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 6px;">
                                        <img class="image-cover" style="width:100%;height:100%;" src="<?= $item['hinh_anh'] ?>" alt="<?= htmlspecialchars($item['ten_sp']) ?>">
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <a class="fw-bold text-dark text-decoration-none" href="<?= $item['url_sp'] ?>"><?= $item['ten_sp'] ?></a>
                                    <?php if (!empty($item['thuoctinh_text'])): ?>
                                    <div style="font-size: 13px; color: #888;"><?= $item['thuoctinh_text'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-center">× <?= $item['so_luong'] ?></td>
                                <td class="align-middle text-end fw-bold text-x"><?= renderPrice($item['tong_gia']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tạm tính / Giảm / Tổng -->
                <div class="border rounded p-3 mt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= __(108) ?></span>
                        <span class="fw-bold"><?= renderPrice($tong_tam_tinh) ?></span>
                    </div>
                    <?php if ($so_tien_giam > 0 || !empty($_SESSION['ma_sale'])): ?>
                    <div class="d-flex justify-content-between mb-2 text-success" id="summary-discount-row" <?= ($so_tien_giam <= 0) ? 'style="display:none"' : '' ?>>
                        <span>Giảm giá <?php if(!empty($_SESSION['ma_sale'])) echo '<small class="text-muted">('.$_SESSION['ma_sale'].')</small>'?></span>
                        <span class="fw-bold" id="summary-discount">- <?= renderPrice($so_tien_giam) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($phi_ship >= 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <div class="d-flex flex-column">
                            <span>Phí vận chuyển</span>
                            <div id="ship-description" class="small text-muted" style="font-size: 12px;"></div>
                        </div>
                        <span class="fw-bold" id="checkout-shipping-fee"><?= renderPrice($phi_ship) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($vat_amount > 0 || $vat_type > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Thuế VAT (<?= $vat_rate ?>%)</span>
                        <span class="fw-bold" id="checkout-vat-amount"><?= renderPrice($vat_amount) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fs-5">
                        <strong>Tổng cộng</strong>
                        <strong class="text-x" id="checkout-total-amount"><?= renderPrice($tong_thanh_toan) ?></strong>
                    </div>
                </div>

                <!-- Phương thức thanh toán -->
                <?php if (!empty($phuongthucthanhtoan)): ?>
                <div class="mt-4">
                    <h5 class="fw-bold"><?= __(35) ?></h5>
                    <div class="d-flex gap-2 mt-2">
                        <?php foreach ($phuongthucthanhtoan as $pIdx => $pVal): ?>
                        <a class="btn btn-thanhtoan <?= $pIdx == 0 ? 'active' : '' ?>" data="<?= $pVal['id_code'] ?>">
                            <?= $pVal['ten'] ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($phuongthucthanhtoan as $pIdx => $pVal): ?>
                    <div class="mt-3 thanhtoan_content thanhtoan_content_<?= $pVal['id_code'] ?>" <?php if ($pIdx > 0): ?>style="display:none"<?php endif; ?>>
                        <?= $pVal['noi_dung'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Checkout JS di chuyển sang shop.js -->
<script>
    window.orderSubtotal = <?= (float)$tong_tam_tinh ?>;
</script>