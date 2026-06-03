<?php

namespace App\Controllers;

/**
 * CheckoutController
 * Xử lý trang thanh toán.
 */
class CheckoutController extends Controller {
    /**
     * Hiển thị trang thanh toán
     */
    public function index($request) {
        // Nếu giỏ hàng trống → redirect về trang giỏ hàng
        if (empty($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
            header('Location: ' . route('cart.index'));
            exit;
        }

        global $d;

        // Reset phí ship khi load lại trang (F5) - Chỉ reset khi là GET request (truy cập lần đầu hoặc load lại)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['phi_ship']);
        }

        // Fetch Tax Settings
        $setting_vat = (new \SettingModel())->getAll();
        $vat_rate = (double)($setting_vat['vat_rate'] ?? 0);
        $vat_type = (int)($setting_vat['vat_type'] ?? 0);

        // Tính tổng giỏ hàng & lấy items
        $tong_tam_tinh = 0;
        $cartItems = [];
        foreach ($_SESSION['cart'] as $key => $value) {
            if ((int)$value['id_sp'] <= 0) continue;
            
            $r_sp = $d->simple_fetch("select * from #_sanpham where id_code = '" . $value['id_sp'] . "' ");
            if (!$r_sp) continue;

            $gia_sp = (float)$value['gia'];
            $so_luong = (int)$value['so_luong'];
            $tong_tam_tinh += $gia_sp * $so_luong;

            $url_sp = route('product.show', $r_sp['alias']);
            
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
                        $key_param = !empty($attr['loai']) && in_array($attr['loai'], ['color', 'size', 'image']) ? $attr['loai'] : str_slug($attr['ten_thuoctinh']);
                        $val_param = $attr['id_thuoctinh_giatri'];
                        $queryStr .= urlencode($key_param) . '=' . urlencode($val_param) . '&';
                    }
                    $queryStr = rtrim($queryStr, '&');
                    $url_sp .= '?' . $queryStr;
                }
            }

            $cartItems[$key] = [
                'key' => $key,
                'id_sp' => $value['id_sp'],
                'so_luong' => $so_luong,
                'gia_sp' => $gia_sp,
                'tong_gia' => $gia_sp * $so_luong,
                'hinh_anh' => getImageUrl($value['hinh_anh']),
                'ten_sp' => $r_sp['ten'],
                'url_sp' => $url_sp,
                'thuoctinh_text' => $value['thuoctinh_text'] ?? ''
            ];
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
                    $setting_ship = (new \SettingModel())->getAll();
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

        $user_login = $_SESSION['user_login'] ?? ['ho_ten' => '', 'dien_thoai' => '', 'email' => ''];

        return view('pages/cart/checkout', [
            'title'               => 'Thanh toán',
            'cartItems'           => $cartItems,
            'tong_tam_tinh'       => $tong_tam_tinh,
            'phi_ship'            => $phi_ship,
            'so_tien_giam'        => $so_tien_giam,
            'vat_amount'          => $vat_amount,
            'vat_rate'            => $vat_rate,
            'vat_type'            => $vat_type,
            'tong_thanh_toan'     => $tong_thanh_toan,
            'phuongthucthanhtoan' => $phuongthucthanhtoan,
            'user_login'          => $user_login,
            'd'                   => $d
        ]);
    }

    public function store($request) {
        global $d;

        // Nếu giỏ hàng rỗng -> redirect về trang giỏ hàng
        if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
            header('Location: ' . route('cart.index'));
            exit;
        }

        // Validate required fields
        if (empty($_POST['ho_ten']) || empty($_POST['dien_thoai']) || empty($_POST['dia_chi']) || empty($_POST['code_tinh'])) {
            $d->alert("Vui lòng nhập đầy đủ thông tin giao hàng!");
            $d->location(route('checkout.index'));
            exit;
        }

        // Fetch Tax Settings
        $setting_vat = (new \SettingModel())->getAll();
        $vat_rate = (double)($setting_vat['vat_rate'] ?? 0);
        $vat_type = (int)($setting_vat['vat_type'] ?? 0);

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
                throw new \Exception("Lỗi khi tạo mã đơn hàng.");
            }

            $_SESSION['ma_dh'] = $ma_dh;
            
            foreach ($_SESSION['cart'] as $key => $value) {
                $id_sp = (int)$value['id_sp'];
                $so_luong = (int)$value['so_luong'];
                if ($id_sp <= 0 || $so_luong <= 0) continue;

                $row_sp = $d->simple_fetch("select ten,gia,khuyen_mai from #_sanpham where id_code = '$id_sp'");
                if (!$row_sp) continue;

                $gia_sp = $value['gia'];
                
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
                    throw new \Exception("Lỗi chi tiết đơn hàng: " . $row_sp['ten']);
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
            header('Location: ' . URLPATH . 'thanh-cong.html?order=' . $ma_dh);
            exit;

        } catch (\Exception $e) {
            $d->db->rollBack();
            $d->alert("Đăt hàng thất bại. " . $e->getMessage());
            $d->location(route('checkout.index'));
            exit;
        }
    }
}
