<?php

// Tính tổng giỏ hàng
$tong     = 0;
$phi_ship = $_SESSION['phi_ship'] ?? 0;
$so_giam  = $_SESSION['phi_sale'] ?? 0;

// Fetch Tax Settings
$setting_vat = $d->simple_fetch("SELECT vat_rate, vat_type FROM #_thongtin WHERE lang = '".LANG."' LIMIT 1");
$vat_rate = (double)($setting_vat['vat_rate'] ?? 0);
$vat_type = (int)($setting_vat['vat_type'] ?? 0);

// Lấy danh sách mã giảm giá còn khả dụng để hiển thị ngay khi vào trang
$_now_date    = date('Y-m-d');
$list_coupons = $d->o_fet(
    "SELECT km.ma, km.gia_tri, km.don_vi, km.dieu_kien,
            COUNT(ls.id) AS da_dung, km.gioi_han
     FROM #_khuyenmai km
     LEFT JOIN #_khuyenmai_ls ls ON ls.ma_km = km.ma
     WHERE (km.id_thanhvien = '' OR km.id_thanhvien IS NULL)
       AND (km.tu_ngay IS NULL OR km.tu_ngay < '2000-01-01' OR km.tu_ngay <= '$_now_date')
       AND (km.den_ngay IS NULL OR km.den_ngay < '2000-01-01' OR km.den_ngay >= '$_now_date')
     GROUP BY km.id
     HAVING (km.gioi_han = 0 OR da_dung < km.gioi_han)
     ORDER BY km.id DESC
     LIMIT 20"
);
?>

<div class="block">
    <div class="container-fluid">
        <?php if (isset($_SESSION['cart'])): ?>
            <div class="row">
                <!-- Danh sách sản phẩm -->
                <div class="col-lg-8">
                    <h3 class="fw-bold text-x"><?= $d->getTxt(58) ?></h3>
                    <p><?= $d->getTxt(49) ?> <span class="text-danger"><?= count($_SESSION['cart']) ?></span></p>

                    <div class="table-responsive shopping-summery">
                        <table class="table" id="cart-table">
                            <thead>
                                <tr>
                                    <th scope="col" colspan="2"><?= $d->getTxt(98) ?></th>
                                    <th scope="col"><?= $d->getTxt(38) ?></th>
                                    <th scope="col"><?= $d->getTxt(39) ?></th>
                                    <th scope="col"><?= $d->getTxt(97) ?></th>
                                    <th scope="col" class="text-center"><?= $d->getTxt(127) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($_SESSION['cart'] as $key => $value):
                                    if ((int) $value['id_sp'] <= 0)
                                        continue;
                                    $row_sp = $d->simple_fetch("select * from #_sanpham where id_code = '" . $value['id_sp'] . "' ");
                                    $gia_sp = $value['gia'];
                                    $tong = $tong + ($gia_sp * $value['so_luong']);
                                    ?>
                                    <tr id="cart-row-<?= $key ?>">
                                        <td class="col-image" style="width: 100px;">
                                            <div class="ratio ratio-1x1" style="width: 80px;">
                                                <img class="image-cover" src="<?= getImageUrl($value['hinh_anh']) ?>"
                                                    alt="<?= $row_sp['ten'] ?>">
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <?php
                                            $url_sp = route('product.show', $row_sp['alias']);
                                            // Handle variation params for URL if id_bienthe exists
                                            if (!empty($value['thuoctinh']) && (int)$value['thuoctinh'] > 0) {
                                                $id_bienthe = (int)$value['thuoctinh'];
                                                $attrs = $d->o_fet("SELECT btt.id_thuoctinh_giatri, tt.loai, ttg.alias, ttg.gia_tri, tt.ten as ten_thuoctinh, ttg.ten as ten_giatri 
                                                                    FROM #_sanpham_bienthe_thuoctinh btt
                                                                    JOIN #_thuoctinh tt ON btt.id_thuoctinh = tt.id_code AND tt.lang = '".LANG."'
                                                                    JOIN #_thuoctinh_giatri ttg ON btt.id_thuoctinh_giatri = ttg.id_code AND ttg.lang = '".LANG."'
                                                                    WHERE btt.id_bienthe = $id_bienthe");
                                                
                                                if (!empty($attrs)) {
                                                    $queryStr = '';
                                                    foreach ($attrs as $attr) {
                                                        // Fallback to alias if 'loai' is not defined or is generic 'label', 'select', etc.
                                                        $key_param = !empty($attr['loai']) && in_array($attr['loai'], ['color', 'size', 'image']) ? $attr['loai'] : str_to_alias($attr['ten_thuoctinh']);
                                                        $val_param = $attr['id_thuoctinh_giatri'];
                                                        $queryStr .= urlencode($key_param) . '=' . urlencode($val_param) . '&';
                                                    }
                                                    $queryStr = rtrim($queryStr, '&');
                                                    $url_sp .= '?' . $queryStr;
                                                }
                                            }
                                            ?>
                                            <a class="fw-bold" href="<?= $url_sp ?>"><?= $row_sp['ten'] ?></a>
                                            <?php if (!empty($value['thuoctinh_text'])): ?>
                                                <div style="font-size: 13px; color: #888; margin-top: 4px;">
                                                    <?= $value['thuoctinh_text'] ?></div>
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
                                                    data-price="<?= $gia_sp ?>" value="<?= $value['so_luong'] ?>" data-min="1"
                                                    data-max="100" />
                                                <a href="javascript:void(0)" class="btn-add qty-up" data-key="<?= $key ?>">+</a>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <div class="fw-bold text-x fs-18 row-total" id="total-<?= $key ?>">
                                                <?= renderPrice($gia_sp * $value['so_luong']) ?>
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
                        <a href="/" class="btn-custom btn-x">
                            <i class="fa fa-long-arrow-left" aria-hidden="true"></i> <?= $d->getTxt(99) ?>
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

                        <a href="<?= URLPATH ?>thanh-toan.html" class="btn-custom btn-x w-100 text-center d-block">
                            <?= $d->getTxt(26) ?> <i class="fa fa-long-arrow-right ms-2"></i>
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
                <p><?= $d->gettxt(42) ?></p>
                <a href="<?= _URLLANG ?>"><?= $d->gettxt(43) ?></a>
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