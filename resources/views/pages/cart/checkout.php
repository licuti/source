<?php

// Nếu giỏ hàng rỗng -> redirect về trang giỏ hàng
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    $d->redirect(URLPATH . 'gio-hang.html');
}

// Reset phí ship khi load lại trang (F5) - Chỉ reset khi là GET request (truy cập lần đầu hoặc load lại)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['phi_ship']);
}

// Fetch Tax Settings
$setting_vat = $d->simple_fetch("SELECT vat_rate, vat_type FROM #_thongtin WHERE lang = '".LANG."' LIMIT 1");
$vat_rate = (double)($setting_vat['vat_rate'] ?? 0);
$vat_type = (int)($setting_vat['vat_type'] ?? 0);

// Xử lý đặt hàng
if (isset($_POST['dathang'])) {

    // Server-side validation
    if (empty($_POST['ho_ten']) || empty($_POST['dien_thoai']) || empty($_POST['dia_chi']) || empty($_POST['code_tinh'])) {
        $d->alert("Vui lòng nhập đầy đủ thông tin giao hàng!");
        $d->location(URLPATH . 'thanh-toan.html');
        exit;
    }

    $ma_dh = 'DH-' . randomString(5);
    token();
    
    $dia_chi_full = addslashes($_POST['dia_chi']);
    $huyen = $d->getHuyen($_POST['code_tinh'], 'ten', $_POST['code_huyen']);
    $tinh = $d->getTinh('ten', $_POST['code_tinh']);
    if (!empty($huyen['ten'])) $dia_chi_full .= ', ' . addslashes($huyen['ten']);
    if (!empty($tinh['ten'])) $dia_chi_full .= ', ' . addslashes($tinh['ten']);

    $data = [];
    $data['ma_dh']             = $ma_dh;
    $data['ho_ten']            = addslashes($_POST['ho_ten']);
    $data['dien_thoai']        = addslashes($_POST['dien_thoai']);
    $data['email']             = addslashes($_POST['email'] ?? '');
    $data['so_nha']            = addslashes($_POST['dia_chi']);
    $data['phuong']            = addslashes($_POST['code_xa'] ?? '');
    $data['quan']              = addslashes($_POST['code_huyen'] ?? '');
    $data['thanh_pho']         = addslashes($_POST['code_tinh'] ?? '');
    $data['dia_chi']           = $dia_chi_full;
    $data['loi_nhan']          = addslashes($_POST['ghi_chu'] ?? '');
    $data['thanh_toan']        = addslashes($_POST['phuongthucthanhtoan'] ?? '');
    $data['phi_vanchuyen']     = $_SESSION['phi_ship'] ?? 0;
    $data['ma_giamgia']        = $_SESSION['ma_sale'] ?? '';
    $data['so_tien_giam']      = $_SESSION['phi_sale'] ?? 0;
    $data['ngay_dathang']      = date('Y-m-d', time());
    $data['tinhtrang_donhang'] = 1;

    try {
        $d->db->beginTransaction();

        // Calculate VAT before insertion
        $subtotal_cart = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal_cart += ((int)$item['so_luong'] * (double)$item['gia']);
        }
        $so_tien_giam = $_SESSION['phi_sale'] ?? 0;
        $vat_res = calculateVAT($subtotal_cart - $so_tien_giam, $vat_rate, $vat_type);
        $data['vat_amount'] = $vat_res['amount'];

        $d->reset();
        $d->setTable('#_dathang');
        $id_dh = $d->insert($data);

        if (!$id_dh) {
            throw new Exception("Lỗi khi tạo mã đơn hàng.");
        }

        $_SESSION['ma_dh'] = $ma_dh;
        $tong = 0;

        foreach ($_SESSION['cart'] as $key => $value) {
            $id_sp = (int)$value['id_sp'];
            $so_luong = (int)$value['so_luong'];
            if ($id_sp <= 0 || $so_luong <= 0) continue;

            $row_sp = $d->simple_fetch("select ten,gia,khuyen_mai from #_sanpham where id_code = '$id_sp'");
            if (!$row_sp) continue;

            $gia_sp = $value['gia'];
            $tong += ($gia_sp * $so_luong);
            
            $data_ct = [];
            $data_ct['id_dh']      = $id_dh;
            $data_ct['ma_dh']      = $ma_dh;
            $data_ct['ten_sp']     = addslashes($row_sp['ten']);
            $data_ct['thuoc_tinh'] = addslashes($value['thuoctinh_text'] ?? '');
            $data_ct['gia_ban']    = $gia_sp;
            $data_ct['so_luong']   = $so_luong;
            $data_ct['id_sp']      = $id_sp;
            $data_ct['hinh_sp']    = addslashes($value['hinh_sp'] ?? '');
            
            $d->reset();
            $d->setTable('#_dathang_chitiet');
            $res_ct = $d->insert($data_ct);
            if (!$res_ct) {
                throw new Exception("Lỗi chi tiết đơn hàng: " . $row_sp['ten']);
            }

            // Trừ số lượng tồn kho
            if (!empty($value['thuoctinh']) && (int)$value['thuoctinh'] > 0) {
                $id_bienthe = (int)$value['thuoctinh'];
                $d->rawQuery("UPDATE #_sanpham_bienthe SET so_luong = GREATEST(0, so_luong - $so_luong) WHERE id = $id_bienthe");
            }
            $d->rawQuery("UPDATE #_sanpham SET so_luong = GREATEST(0, so_luong - $so_luong) WHERE id_code = $id_sp");
        }

        // Lưu lịch sử sử dụng mã giảm giá
        if (!empty($_SESSION['ma_sale'])) {
            $data_km_ls = [
                'ma_km'         => addslashes($_SESSION['ma_sale']),
                'id_thanhvien'  => (int)($_SESSION['id_login'] ?? 0),
                'id_donhang'    => (int)$id_dh,
                'email'         => addslashes($_POST['email'] ?? ''),
                'dien_thoai'    => addslashes($_POST['dien_thoai'] ?? ''),
                'ngay_dung'     => date('Y-m-d')
            ];
            $d->reset();
            $d->setTable('#_khuyenmai_ls');
            $d->insert($data_km_ls);
        }

        // Commit transaction
        $d->db->commit();

        unset($_SESSION['cart']);
        unset($_SESSION['phi_ship']);
        unset($_SESSION['phi_sale']);
        unset($_SESSION['ma_sale']);
        unset($_SESSION['giatri_sale']);

        // Redirect to success page
        $d->redirect(URLPATH . 'thanh-cong.html?order=' . $ma_dh);

    } catch (Exception $e) {
        $d->db->rollBack();
        $d->alert("Đăt hàng thất bại. " . $e->getMessage());
        $d->location(URLPATH . 'thanh-toan.html');
        exit;
    }
}

// Tính tổng giỏ hàng
$tong_tam_tinh = 0;
foreach ($_SESSION['cart'] as $key => $value) {
    if ((int)$value['id_sp'] > 0) {
        $tong_tam_tinh += $value['gia'] * $value['so_luong'];
    }
}
$phi_ship = $_SESSION['phi_ship'] ?? 0;

// Tính lại phi_sale dựa trên mốc phi_ship hiện tại (có thể là 0 nếu vừa F5)
if (!empty($_SESSION['ma_sale'])) {
    $ma_sale = $_SESSION['ma_sale'];
    $row_sale = $d->simple_fetch("SELECT * FROM #_khuyenmai WHERE ma = '$ma_sale'");
    if ($row_sale) {
        $temp_phiship = $phi_ship;
        // Nếu giảm phí ship mà chưa có phí ship (đang = 0), lấy phí mặc định làm tạm tính
        if ($row_sale['loai'] == 1 && $phi_ship <= 0) {
            $setting_ship = $d->simple_fetch("SELECT default_ship_phi FROM #_thongtin WHERE lang = '".LANG."' LIMIT 1");
            $temp_phiship = (float)($setting_ship['default_ship_phi'] ?? 30000);
        }
        
        if ($row_sale['don_vi'] == 1) { // %
            $price_sale = ($row_sale['loai'] == 0)
                ? $tong_tam_tinh * ($row_sale['gia_tri'] / 100)
                : $temp_phiship * ($row_sale['gia_tri'] / 100);
            
            if ($row_sale['gia_tri_max'] > 0 && $price_sale > $row_sale['gia_tri_max']) {
                $price_sale = (float)$row_sale['gia_tri_max'];
            }
        } else { // Fixed
            $price_sale = (float)$row_sale['gia_tri'];
        }
        
        // Ràng buộc
        if ($row_sale['loai'] == 0 && $price_sale > $tong_tam_tinh) $price_sale = $tong_tam_tinh;
        if ($row_sale['loai'] != 0 && $price_sale > $temp_phiship) $price_sale = $temp_phiship;
        
        $_SESSION['phi_sale'] = $price_sale;
    }
}

$so_tien_giam  = $_SESSION['phi_sale'] ?? 0;
$vat_res = calculateVAT($tong_tam_tinh - $so_tien_giam, $vat_rate, $vat_type);
$vat_amount = $vat_res['amount'];
$tong_thanh_toan = max(0, $vat_res['total'] + $phi_ship);

// Lấy phương thức thanh toán
$phuongthucthanhtoan = $d->getContents(372);

?>
<div class="block">
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Form thông tin giao hàng -->
            <div class="col-lg-5 order-lg-1">
                <div class="box-form-checkout">
                    <h3 class="fw-bold text-x"><?= $d->getTxt(28) ?></h3>
                    <form method="POST" action="" class="form-checkout mt-3" id="form-checkout">
                        <div class="form-group">
                            <label><?= $d->getTxt(5) ?></label>
                            <input type="text" placeholder="<?= $d->getTxt(29) ?>" value="<?= $user_login['ho_ten'] ?>" name="ho_ten" class="form-control" required />
                        </div>
                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(6) ?></label>
                            <input type="text" placeholder="<?= $d->getTxt(32) ?>" value="<?= $user_login['dien_thoai'] ?>" name="dien_thoai" class="form-control" required />
                        </div>
                        <div class="form-group mt-3">
                            <label>Email</label>
                            <input type="email" placeholder="<?= $d->getTxt(31) ?>" value="<?= $user_login['email'] ?>" name="email" class="form-control" />
                        </div>

                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(144) ?></label>
                            <select class="form-control" required name="code_tinh" id="code_tinh" onchange="get_huyen('code_tinh', 'code_huyen')">
                                <option value=""><?= $d->getTxt(145) ?></option>
                                <?php foreach ($d->getTinh('code,ten') as $tinh): ?>
                                <option value="<?= $tinh['code'] ?>"><?= $tinh['ten'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(146) ?></label>
                            <select class="form-control" required id="code_huyen" name="code_huyen" onchange="get_xa('code_huyen', 'code_xa')">
                                <option value=""><?= $d->getTxt(147) ?></option>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(146) ?></label>
                            <select class="form-control" required id="code_xa" name="code_xa" onchange="update_shipping_fee()">
                                <option value=""><?= $d->getTxt(147) ?></option>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(7) ?></label>
                            <input type="text" placeholder="<?= $d->getTxt(148) ?>" name="dia_chi" class="form-control" required />
                        </div>

                        <input type="hidden" name="phuongthucthanhtoan" id="txt_phuongthucthanhtoan" value="<?= $phuongthucthanhtoan[0]['ten'] ?? '' ?>" />

                        <div class="form-group mt-3">
                            <label><?= $d->getTxt(33) ?></label>
                            <textarea class="form-control" placeholder="<?= $d->getTxt(149) ?>" name="ghi_chu"></textarea>
                        </div>
                        <div class="mt-4">
                            <button class="btn-custom btn-x w-100" type="submit" name="dathang"><?= $d->getTxt(26) ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="col-lg-7 order-lg-2">
                <h3 class="fw-bold text-x"><?= $d->getTxt(58) ?></h3>
                <div class="table-responsive mt-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2"><?= $d->getTxt(98) ?></th>
                                <th class="text-center"><?= $d->getTxt(39) ?></th>
                                <th class="text-end"><?= $d->getTxt(97) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $cart_key => $cart_value):
                                if ((int)$cart_value['id_sp'] <= 0) continue;
                                $r_sp   = $d->simple_fetch("select * from #_sanpham where id_code = '" . $cart_value['id_sp'] . "' ");
                                $g_sp   = $cart_value['gia'];
                            ?>
                            <tr>
                                <td style="width: 70px;">
                                    <div style="width: 60px; height: 60px; overflow: hidden; border-radius: 6px;">
                                        <img class="image-cover" style="width:100%;height:100%;" src="<?= getImageUrl($cart_value['hinh_anh']) ?>" alt="<?= htmlspecialchars($r_sp['ten']) ?>">
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <?php
                                    $url_sp = route('product.show', $r_sp['alias']);
                                    // Handle variation params for URL
                                    if (!empty($cart_value['thuoctinh']) && (int)$cart_value['thuoctinh'] > 0) {
                                        $id_bienthe = (int)$cart_value['thuoctinh'];
                                        $attrs = $d->o_fet("SELECT btt.id_thuoctinh_giatri, tt.loai, ttg.alias, ttg.gia_tri, tt.ten as ten_thuoctinh, ttg.ten as ten_giatri 
                                                            FROM #_sanpham_bienthe_thuoctinh btt
                                                            JOIN #_thuoctinh tt ON btt.id_thuoctinh = tt.id_code AND tt.lang = '".LANG."'
                                                            JOIN #_thuoctinh_giatri ttg ON btt.id_thuoctinh_giatri = ttg.id_code AND ttg.lang = '".LANG."'
                                                            WHERE btt.id_bienthe = $id_bienthe");
                                        
                                        if (!empty($attrs)) {
                                            $queryStr = '';
                                            foreach ($attrs as $attr) {
                                                $key_param = !empty($attr['loai']) && in_array($attr['loai'], ['color', 'size', 'image']) ? $attr['loai'] : str_to_alias($attr['ten_thuoctinh']);
                                                $val_param = $attr['id_thuoctinh_giatri'];
                                                $queryStr .= urlencode($key_param) . '=' . urlencode($val_param) . '&';
                                            }
                                            $queryStr = rtrim($queryStr, '&');
                                            $url_sp .= '?' . $queryStr;
                                        }
                                    }
                                    ?>
                                    <a class="fw-bold text-dark text-decoration-none" href="<?= $url_sp ?>"><?= $r_sp['ten'] ?></a>
                                    <?php if (!empty($cart_value['thuoctinh_text'])): ?>
                                    <div style="font-size: 13px; color: #888;"><?= $cart_value['thuoctinh_text'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-center">× <?= $cart_value['so_luong'] ?></td>
                                <td class="align-middle text-end fw-bold text-x"><?= renderPrice($g_sp * $cart_value['so_luong']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tạm tính / Giảm / Tổng -->
                <div class="border rounded p-3 mt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= $d->getTxt(108) ?></span>
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
                    <h5 class="fw-bold"><?= $d->getTxt(35) ?></h5>
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