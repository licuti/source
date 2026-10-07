<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    ob_start();
    error_reporting(0);
    define('_source_lib','../lib/');
    global $d;
    global $lang;
    require_once __DIR__ . '/../../admin/lib/bootstrap.php';
    $d = new func_index($config['database']);
    include_once _source_lib."lang.php";
    include_once _source_lib."info.php";
    include_once _source_lib."function.php";
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_POST['do']) ? validate_content($_POST['do']) : '');

    // Xử lý cập nhật số lượng trong giỏ hàng (từ AJAX cart page)
    if ($action === 'update_qty') {
        $key_update = $_POST['key_cart'];
        $sl_update  = max(1, (int)$_POST['so_luong']);
        if (isset($_SESSION['cart'][$key_update])) {
            $item = $_SESSION['cart'][$key_update];
            $id_sp = (int)$item['id_sp'];
            $id_bienthe = (int)$item['thuoctinh'];
            
            $stock_available = 0;
            if ($id_bienthe > 0) {
                $row_bienthe = $d->safeFetchOne("SELECT so_luong FROM #_sanpham_bienthe WHERE id = ?", array($id_bienthe));
                if ($row_bienthe) $stock_available = (int)$row_bienthe['so_luong'];
            } else {
                $row_sp = $d->safeFetchOne("SELECT so_luong FROM #_sanpham WHERE id_code = ?", array($id_sp));
                if ($row_sp) $stock_available = (int)$row_sp['so_luong'];
            }

            if ($stock_available < $sl_update) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ tồn kho (Còn lại: ' . $stock_available . ').']);
                exit();
            }

            $_SESSION['cart'][$key_update]['so_luong'] = $sl_update;
        }
        echo json_encode(['success' => true]);
        exit();
    }

    // Xóa sản phẩm khỏi giỏ hàng
    if ($action === 'delete_item') {
        $key_delete = $_POST['key_cart'];
        if (isset($_SESSION['cart'][$key_delete])) {
            unset($_SESSION['cart'][$key_delete]);
            if (empty($_SESSION['cart'])) {
                unset($_SESSION['cart']);
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }

    // Cập nhật lại HTML của Offcanvas Cart
    if ($action === 'get_offcanvas_cart') {
        $pageCart = $d->getCate(122);
        $pageCheckout = $d->getCate(254);
        
        $total_offcanvas = 0;
        ob_start();
        ?>
        <div class="cart-offcanvas-list">
            <?php if (!empty($_SESSION['cart'])): ?>
                <?php
                foreach ($_SESSION['cart'] as $key => $item):
                    $total_offcanvas += $item['gia'] * $item['so_luong'];
                    ?>
                    <div class="d-flex mb-3 pb-3 border-bottom offcanvas-cart-item" data-price="<?= $item['gia'] ?>"
                        data-key="<?= $key ?>">
                        <img src="<?= Img($item['hinh_anh']) ?>" alt="Product" class="img-thumbnail me-3"
                            style="width: 70px; height: 70px; object-fit: cover;">
                        <div class="flex-grow-1 position-relative">
                            <?php
                            $temp_sp = $d->simple_fetch("SELECT ten FROM #_sanpham WHERE id_code = {$item['id_sp']}");
                            $sp_name = $temp_sp ? $temp_sp['ten'] : 'Sản phẩm';
                            ?>
                            <h6 class="mb-1 text-truncate pe-4" style="max-width: 200px;"><?= $sp_name ?></h6>
                            <button type="button" class="btn-close btn-delete-offcanvas position-absolute text-danger"
                                aria-label="Xóa" data-key="<?= $key ?>" style="top:2px; right:0; font-size: 0.65rem;"></button>
                            <?php if (!empty($item['thuoctinh_text'])): ?>
                                <small class="text-muted d-block"><?= $item['thuoctinh_text'] ?></small>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-y"><?= renderPrice($item['gia']) ?></span>
                                <div class="input-group input-group-sm" style="width: 90px;">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="down"
                                        data-key="<?= $key ?>">-</button>
                                    <input type="text" class="form-control text-center px-1 offcanvas-qty-input"
                                        value="<?= $item['so_luong'] ?>" data-key="<?= $key ?>">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="up"
                                        data-key="<?= $key ?>">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-cart-circle-xmark fa-3x mb-3"></i>
                    <p>Giỏ hàng đang trống.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($_SESSION['cart'])): ?>
            <div class="offcanvas-footer border-top p-3 bg-light">
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Tổng tiền:</span>
                    <span class="fw-bold text-y fs-5 offcanvas-total-price"><?= renderPrice($total_offcanvas) ?></span>
                </div>
                <a <?= createAlias($pageCart) ?> class="btn-custom btn-outline btn-x w-100 mb-2">Xem giỏ hàng</a>
                <a <?= createAlias($pageCheckout) ?> class="btn-custom btn-solid btn-y w-100"
                    style="background-color: var(--cl-x, #f59e0b); border-color: var(--cl-x, #f59e0b);">Thanh toán ngay</a>
            </div>
        <?php endif; ?>
        <?php
        $html = ob_get_clean();
        echo json_encode(['success' => true, 'html' => $html]);
        exit();
    }

    // Lấy danh sách mã giảm giá hợp lệ để hiển thị cho người dùng chọn
    if ($action === 'get_coupons') {
        $now      = date('Y-m-d');
        $tong_don = (float)($_POST['tong_don'] ?? 0);

        $coupons = $d->safeFetch(
            "SELECT km.ma, km.gia_tri, km.don_vi, km.dieu_kien, km.den_ngay, km.gioi_han,
                    COUNT(ls.id) AS da_dung
             FROM   #_khuyenmai km
             LEFT JOIN #_khuyenmai_ls ls ON ls.ma_km = km.ma
             WHERE  km.hien_thi = 1
               AND  (km.id_thanhvien = '' OR km.id_thanhvien IS NULL)
               AND  (km.tu_ngay IS NULL OR km.tu_ngay < '2000-01-01' OR km.tu_ngay <= ?)
               AND  (km.den_ngay IS NULL OR km.den_ngay < '2000-01-01' OR km.den_ngay >= ?)
             GROUP BY km.id
             HAVING (km.gioi_han = 0 OR da_dung < km.gioi_han)
             ORDER BY km.id DESC
             LIMIT 20",
            array($now, $now)
        );

        echo json_encode(['success' => true, 'coupons' => $coupons ?: []]);
        exit();
    }

    // Áp dụng mã giảm giá
    if ($action === 'check_sale') {
        $ma_sale  = trim($_POST['ma_sale']);
        $tongdong = (float)$_POST['tong_dong'];
        $phiship  = (float)($_POST['phi_ship'] ?? 0);

        $row_sale = $d->safeFetchOne("SELECT * FROM #_khuyenmai WHERE ma = ?", array($ma_sale));
        $res = ['success' => false, 'message' => ''];

        // Nếu mã giảm phí vận chuyển mà phí ship truyền lên = 0 (đang ở giỏ hàng, chưa chọn địa chỉ)
        // Ta lấy phí ship mặc định từ setting để làm mốc tính toán tạm thời
        $is_estimated = false;
        if ($row_sale && $row_sale['loai'] == 1 && $phiship <= 0) {
            $setting_ship = $d->safeFetchOne("SELECT default_ship_phi FROM #_thongtin WHERE lang = ? LIMIT 1", array(LANG));
            $phiship = (float)($setting_ship['default_ship_phi'] ?? 30000);
            $is_estimated = true;
        }

        if (!$row_sale) {
            $res['message'] = 'Mã giảm giá không tồn tại hoặc đã hết hạn.';
        } elseif (isset($row_sale['hien_thi']) && (int)$row_sale['hien_thi'] === 0) {
            $res['message'] = 'Mã giảm giá hiện không còn hiệu lực.';
        } else {
            $now = date('Y-m-d');
            if (!empty($row_sale['tu_ngay']) && $row_sale['tu_ngay'] !== '0000-00-00' && $now < $row_sale['tu_ngay']) {
                $res['message'] = 'Mã giảm giá chưa đến ngày sử dụng.';
            } elseif (!empty($row_sale['den_ngay']) && $row_sale['den_ngay'] !== '0000-00-00' && $now > $row_sale['den_ngay']) {
                $res['message'] = 'Mã giảm giá đã quá hạn sử dụng.';
            } elseif ($row_sale['dieu_kien'] > 0 && $tongdong < $row_sale['dieu_kien']) {
                $res['message'] = 'Đơn hàng tối thiểu ' . number_format($row_sale['dieu_kien']) . 'đ để áp dụng mã này.';
            } else {
                if ($row_sale['gioi_han'] > 0) {
                    $used = count($d->safeFetch("SELECT id FROM #_khuyenmai_ls WHERE ma_km = ?", array($ma_sale)));
                    if ($used >= $row_sale['gioi_han']) {
                        $res['message'] = 'Mã giảm giá này đã hết lượt sử dụng.';
                    }
                }
                if (empty($res['message']) && !empty($row_sale['id_thanhvien'])) {
                    $arr_member = explode(',', trim($row_sale['id_thanhvien'], ','));
                    if (!isset($_SESSION['id_login']) || !in_array($_SESSION['id_login'], $arr_member)) {
                        $res['message'] = 'Mã giảm giá này không áp dụng cho tài khoản của bạn.';
                    }
                }
                if (empty($res['message'])) {
                    // Kiểm tra dùng 1 lần trên mỗi khách (Dựa trên ID thành viên hoặc Email/SĐT)
                    if (isset($_SESSION['id_login'])) {
                        $used_me = $d->safeFetchOne(
                            "SELECT id FROM #_khuyenmai_ls WHERE ma_km = ? AND id_thanhvien = ? LIMIT 1",
                            array($ma_sale, $_SESSION['id_login'])
                        );
                    } else {
                        // Nếu là khách vãng lai, kiểm tra theo email/sđt gửi lên (nếu có nhấm ở checkout)
                        $email = $_POST['email'] ?? '';
                        $phone = $_POST['phone'] ?? '';
                        if ($email != '' || $phone != '') {
                            $used_me = $d->safeFetchOne(
                                "SELECT id FROM #_khuyenmai_ls WHERE ma_km = ? AND (email = ? OR dien_thoai = ?) LIMIT 1",
                                array($ma_sale, $email, $phone)
                            );
                        }
                    }
                    if (isset($used_me) && $used_me) {
                        $res['message'] = 'Bạn đã sử dụng mã giảm giá này rồi.';
                    }
                }
                if (empty($res['message'])) {
                    $donvi = ($row_sale['don_vi'] == 1) ? '%' : 'đ';
                    if ($row_sale['don_vi'] == 1) {
                        $price_sale = ($row_sale['loai'] == 0)
                            ? $tongdong * ($row_sale['gia_tri'] / 100)
                            : $phiship  * ($row_sale['gia_tri'] / 100);
                        
                        // Áp dụng mức giảm tối đa nếu có
                        if ($row_sale['gia_tri_max'] > 0 && $price_sale > $row_sale['gia_tri_max']) {
                            $price_sale = (float)$row_sale['gia_tri_max'];
                        }
                    } else {
                        $price_sale = (float)$row_sale['gia_tri'];
                    }
                    if ($row_sale['loai'] == 0 && $price_sale > $tongdong) $price_sale = $tongdong;
                    if ($row_sale['loai'] != 0 && $price_sale > $phiship)  $price_sale = $phiship;

                    $_SESSION['ma_sale']     = $ma_sale;
                    $_SESSION['giatri_sale'] = $row_sale['gia_tri'] . $donvi;
                    $_SESSION['phi_sale']    = $price_sale;

                    $res['success']    = true;
                    $res['price_sale'] = $price_sale;
                    $res['is_estimated'] = $is_estimated;
                    $res['phi_ship_temp'] = $phiship;
                    $res['label']      = $ma_sale . ' (-' . $row_sale['gia_tri'] . $donvi . ')';
                }
            }
        }
        echo json_encode($res);
        exit();
    }

    // Xóa mã giảm giá đang áp dụng
    if ($action === 'remove_sale') {
        unset($_SESSION['ma_sale'], $_SESSION['giatri_sale'], $_SESSION['phi_sale']);
        echo json_encode(['success' => true]);
        exit();
    }

    // Tính phí vận chuyển dựa trên khu vực
    if ($action === 'get_shipping_fee') {
        $id_tinh  = $_POST['code_tinh'] ?? '';
        $id_huyen = $_POST['code_huyen'] ?? '';
        $id_xa    = $_POST['code_xa'] ?? '';
        $tong_don = (float)($_POST['tong_don'] ?? 0);
        $has_box  = isset($_POST['has_box']) && ($_POST['has_box'] == 'true' || $_POST['has_box'] == 1);

        // Tính tổng cân nặng của đơn hàng
        $total_weight = 0;
        if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
            foreach ($_SESSION['cart'] as $item) {
                $id_sp = (int)$item['id_sp'];
                $id_bienthe = (int)($item['thuoctinh'] ?? 0);
                $qty = (int)$item['so_luong'];
                
                $weight = 0;
                if ($id_bienthe > 0) {
                    // Ưu tiên lấy cân nặng từ biến thể
                    $v_weight = $d->safeFetchOne("SELECT weight FROM #_sanpham_bienthe WHERE id = ? LIMIT 1", array($id_bienthe));
                    if ($v_weight && $v_weight['weight'] > 0) {
                        $weight = (float)$v_weight['weight'];
                    }
                }

                // Nếu không có biến thể hoặc biến thể không có cân nặng, lấy từ sản phẩm gốc
                if ($weight <= 0) {
                    $p_weight = $d->safeFetchOne("SELECT weight FROM #_sanpham WHERE id_code = ? LIMIT 1", array($id_sp));
                    if ($p_weight) {
                        $weight = (float)$p_weight['weight'];
                    }
                }

                $total_weight += $weight * $qty;
            }
        }

        $ship_res = get_shipping_fee($id_tinh, $id_huyen, $id_xa, $tong_don, $total_weight, $has_box);
        $phi_ship = $ship_res['fee'];
        $phi_desc = $ship_res['description'];
        
        $_SESSION['phi_ship'] = $phi_ship;

        // Nếu đang áp dụng mã giảm giá, cần tính toán lại mức giảm (đặc biệt là cho loại giảm ship)
        if (!empty($_SESSION['ma_sale'])) {
            $ma_sale = $_SESSION['ma_sale'];
            $row_sale = $d->safeFetchOne("SELECT * FROM #_khuyenmai WHERE ma = ?", array($ma_sale));
            if ($row_sale) {
                if ($row_sale['don_vi'] == 1) { // %
                    $price_sale = ($row_sale['loai'] == 0)
                        ? $tong_don * ($row_sale['gia_tri'] / 100)
                        : $phi_ship * ($row_sale['gia_tri'] / 100);
                    
                    if ($row_sale['gia_tri_max'] > 0 && $price_sale > $row_sale['gia_tri_max']) {
                        $price_sale = (float)$row_sale['gia_tri_max'];
                    }
                } else { // Fixed
                    $price_sale = (float)$row_sale['gia_tri'];
                }
                
                // Ràng buộc: không giảm quá giá trị gốc (quan trọng cho giảm ship)
                if ($row_sale['loai'] == 0 && $price_sale > $tong_don) $price_sale = $tong_don;
                if ($row_sale['loai'] != 0 && $price_sale > $phi_ship) $price_sale = $phi_ship;

                $_SESSION['phi_sale'] = $price_sale;
            }
        }

        // --- Tùy chỉnh nhãn Miễn phí ---
        // Cách 1: Hiển thị 0đ
        // $phi_ship_format = renderPrice($phi_ship); 
        
        // Cách 2: Hiển thị chữ "Miễn phí" (Bỏ comment dòng dưới để bật)
        if($phi_ship == 0) $phi_ship_format = '<span class="text-success">Miễn phí</span>';
        else $phi_ship_format = renderPrice($phi_ship);

        // Tính lại tổng thanh toán (Tổng đơn + Ship - Giảm giá)
        $so_tien_giam = $_SESSION['phi_sale'] ?? 0;
        $tong_thanh_toan = $tong_don + $phi_ship - $so_tien_giam;

        echo json_encode([
            'success' => true,
            'phi_ship' => $phi_ship,
            'phi_ship_format' => $phi_ship_format,
            'phi_desc' => $phi_desc,
            'so_tien_giam' => $so_tien_giam,
            'so_tien_giam_format' => renderPrice($so_tien_giam),
            'tong_thanh_toan' => $tong_thanh_toan,
            'tong_thanh_toan_format' => renderPrice($tong_thanh_toan),
            'total_weight' => $total_weight,
            'box_phi' => $ship_res['box_phi'] ?? 0
        ]);
        exit();
    }

    if ($action === 'add-to-cart') {
        $so_luong       =   $_POST['so_luong'] ? (int)$_POST['so_luong'] : 1;
        $id_sp          =   (int)$_POST['id_sp'];
        $id_bienthe     =   isset($_POST['id_bienthe']) ? (int)$_POST['id_bienthe'] : intval($_POST['thuoctinh']);
        $gia            =   $_POST['gia'] ? $_POST['gia'] : 0;

        $row_sp         =   $d->safeFetchOne("select * from #_sanpham where id_code = ? " . _where_lang, array($id_sp));
        
        // Nếu có biến thể, lấy dữ liệu biến thể
        $thuoctinh_text = '';
        if ($id_bienthe) {
            $row_bienthe = $d->safeFetchOne("SELECT * FROM #_sanpham_bienthe WHERE id = ?", array($id_bienthe));
            if ($row_bienthe) {
                $gia = $row_bienthe['khuyen_mai'] > 0 ? $row_bienthe['khuyen_mai'] : $row_bienthe['gia'];
                if($row_bienthe['hinh_anh']) {
                    $row_sp['hinh_anh'] = $row_bienthe['hinh_anh']; // Xài hình biến thể nếu có
                }

                // Lấy tên các thuộc tính ghép lại (vd: Color: Red - Size: M)
                $attrs = $d->safeFetch(
                    "SELECT tt.ten as ten_thuoctinh, ttg.ten as ten_giatri
                     FROM #_sanpham_bienthe_thuoctinh btt
                     JOIN #_thuoctinh tt ON btt.id_thuoctinh = tt.id_code AND tt.lang = ?
                     JOIN #_thuoctinh_giatri ttg ON btt.id_thuoctinh_giatri = ttg.id_code AND ttg.lang = ?
                     WHERE btt.id_bienthe = ?",
                    array(LANG, LANG, $id_bienthe)
                );
                $attr_texts = [];
                foreach ($attrs as $attr) {
                    $attr_texts[] = $attr['ten_thuoctinh'] . ': ' . $attr['ten_giatri'];
                }
                $thuoctinh_text = implode(' - ', $attr_texts);
            }
        } else {
            if ($row_sp['gia']) {
                $gia = $row_sp['gia'];
            }
            if($row_sp['khuyen_mai']){
                $gia = $row_sp['khuyen_mai'];
            }
            // Fallback for variant products if no id_bienthe sent (Listing add-to-cart)
            if (!$gia || $gia == 0) {
                 $row_min_v = $d->safeFetchOne(
                     "SELECT MIN(IF(khuyen_mai > 0, khuyen_mai, gia)) as min_p FROM #_sanpham_bienthe WHERE id_sanpham = ?",
                     array($id_sp)
                 );
                 if ($row_min_v && $row_min_v['min_p'] > 0) {
                     $gia = $row_min_v['min_p'];
                 }
            }
        }

        $key_cart  = $id_sp.'_'.$id_bienthe;
        if(count($row_sp)>0){
            $stock_available = 0;
            if ($id_bienthe && isset($row_bienthe)) {
                $stock_available = (int)$row_bienthe['so_luong'];
            } else {
                $stock_available = (int)$row_sp['so_luong'];
            }
            
            $current_cart_qty = isset($_SESSION['cart'][$key_cart]) ? $_SESSION['cart'][$key_cart]['so_luong'] : 0;
            $new_qty = $current_cart_qty + $so_luong;
            
            if ($stock_available < $new_qty) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sản phẩm không đủ tồn kho (Còn lại: ' . $stock_available . ').'
                ]);
                exit();
            }

            if (isset($_SESSION['cart'][$key_cart])) {
                $_SESSION['cart'][$key_cart]['so_luong'] += $so_luong;
            } else {
                $_SESSION['cart'][$key_cart]['id_sp']          = $id_sp;
                $_SESSION['cart'][$key_cart]['hinh_anh']       = $row_sp['hinh_anh'];
                $_SESSION['cart'][$key_cart]['ma_sp']          = '';
                $_SESSION['cart'][$key_cart]['gia']            = $gia;
                $_SESSION['cart'][$key_cart]['thuoctinh']      = $id_bienthe;
                $_SESSION['cart'][$key_cart]['thuoctinh_text'] = $thuoctinh_text;
                $_SESSION['cart'][$key_cart]['so_luong']       = $so_luong;
            }
            $res = [
                'success' => true,
                'count'   => count($_SESSION['cart']),
                'message' => 'Đã thêm sản phẩm vào giỏ hàng'
            ];
            echo json_encode($res);
            exit();
        }
    }
?>