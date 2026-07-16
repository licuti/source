<?php

namespace App\Controllers;

use App\Models\SettingModel;

use App\Models\ProductVariantModel;

use App\Models\ProductVariantAttributeModel;

use App\Models\ProductModel;

use App\Models\CouponModel;

use App\Models\AttributeValueModel;

use App\Models\AttributeModel;

use App\Core\Request;

use App\Core\Response;

/**
 * CartController
 * Xử lý giỏ hàng: hiển thị, thêm, cập nhật, xóa, mã giảm giá, phí vận chuyển.
 * Đã tích hợp migrate từ: sources/ajax/ajax_cart.php
 */
class CartController extends Controller {

    /**
     * Hiển thị trang giỏ hàng
     */
    public function index(Request $request) {
        // Đăng ký URL dịch
        $translations = \App\Models\PageModel::where('view', 'pages/cart/index')->get();
        $urls = [];
        foreach ($translations as $t) {
            $urls[$t->lang] = route('cart.index.' . $t->lang);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        $tong     = 0;
        $phi_ship = $_SESSION['phi_ship'] ?? 0;
        $so_giam  = $_SESSION['phi_sale'] ?? 0;

        // Fetch Tax Settings
        $setting_vat = (new SettingModel())->getAll();
        $vat_rate = (double)($setting_vat['vat_rate'] ?? 0);
        $vat_type = (int)($setting_vat['vat_type'] ?? 0);

        // Fetch Coupons
        global $d;
        $_now_date = date('Y-m-d');
        $sql_coupons = "SELECT km.ma, km.gia_tri, km.don_vi, km.dieu_kien, COUNT(ls.id) AS da_dung, km.gioi_han
             FROM #_khuyenmai km
             LEFT JOIN #_khuyenmai_ls ls ON ls.ma_km = km.ma
             WHERE (km.id_thanhvien = '' OR km.id_thanhvien IS NULL)
               AND (km.tu_ngay IS NULL OR km.tu_ngay < '2000-01-01' OR km.tu_ngay <= '$_now_date')
               AND (km.den_ngay IS NULL OR km.den_ngay < '2000-01-01' OR km.den_ngay >= '$_now_date')
             GROUP BY km.id
             HAVING (km.gioi_han = 0 OR da_dung < km.gioi_han)
             ORDER BY km.id DESC
             LIMIT 20";
        $list_coupons = $d ? $d->o_fet($sql_coupons) : [];

        // Fetch Cart Items
        $cartItems = [];
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $value) {
                if ((int)$value['id_sp'] <= 0) continue;
                
                $row_sp = ProductModel::where('id_code', $value['id_sp'])->first();
                if (!$row_sp) continue;

                $gia_sp = $value['gia'];
                $so_luong = (int) $value['so_luong'];
                $tong += ($gia_sp * $so_luong);

                $url_sp = route('product.show', $row_sp->slug ?? '');
                
                if (!empty($value['thuoctinh']) && (int)$value['thuoctinh'] > 0) {
                    $id_bienthe = (int)$value['thuoctinh'];
                    if ($d) {
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
                }

                $cartItems[$key] = [
                    'key' => $key,
                    'id_sp' => $value['id_sp'],
                    'so_luong' => $so_luong,
                    'gia_sp' => $gia_sp,
                    'tong_gia' => $gia_sp * $so_luong,
                    'hinh_anh' => getImageUrl($value['hinh_anh']),
                    'ten_sp' => $row_sp->ten ?? '',
                    'url_sp' => $url_sp,
                    'thuoctinh_text' => $value['thuoctinh_text'] ?? ''
                ];
            }
        }

        return view('pages/cart/index', [
            'title'        => __('Giỏ hàng'),
            'tong'         => $tong,
            'phi_ship'     => $phi_ship,
            'so_giam'      => $so_giam,
            'vat_rate'     => $vat_rate,
            'vat_type'     => $vat_type,
            'list_coupons' => $list_coupons,
            'cartItems'    => $cartItems
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng (AJAX)
     * POST /ajax/cart/add
     */
    public function legacy(Request $request) {
        $action = $request->input('action') ?: $request->input('cmd');
        switch ($action) {
            case 'add-to-cart':
            case 'add_tocart':
                return $this->add($request);
            case 'update_qty':
                return $this->update($request);
            case 'delete_cart':
            case 'delete_item':
                return $this->remove($request);
            case 'check_sale':
                return $this->applyCoupon($request);
            case 'remove_sale':
                return $this->removeCoupon($request);
            case 'get_shipping_fee':
                return $this->shippingFee($request);
            default:
                return response()->json(['success' => false, 'message' => 'Legacy action not found'], 404);
        }
    }

    /**
     * Thêm sản phẩm vào giỏ hàng (AJAX)
     * POST /ajax/cart/add
     */
    public function add(Request $request) {
        $id_sp      = (int) $request->input('id_sp', 0);
        $id_bienthe = (int) $request->input('id_bienthe', $request->input('thuoctinh', 0));
        $so_luong   = max(1, (int) $request->input('so_luong', 1));

        if (!$id_sp) {
            return response()->json(['success' => false, 'message' => 'Thiếu ID sản phẩm'], 400);
        }

        $row_sp = ProductModel::where('id_code', $id_sp)->first();
        if (!$row_sp) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không tồn tại'], 404);
        }

        // Xác định giá & thông tin biến thể
        $thuoctinh_text = '';
        $hinh_anh       = $row_sp->hinh_anh;

        if ($id_bienthe) {
            $row_bienthe = ProductVariantModel::where('id', $id_bienthe)->first();
            if ($row_bienthe) {
                $gia = $row_bienthe->khuyen_mai > 0 ? $row_bienthe->khuyen_mai : $row_bienthe->gia;
                if ($row_bienthe->hinh_anh) $hinh_anh = $row_bienthe->hinh_anh;

                // Lấy tên thuộc tính ghép lại (vd: Màu sắc: Đỏ - Size: M)
                $attrs = ProductVariantAttributeModel::where('id_bienthe', $id_bienthe)->get();
                $attr_texts = [];
                foreach ($attrs as $attr) {
                    $attrName = AttributeModel::where('id_code', $attr->id_thuoctinh)->first();
                    $valName  = AttributeValueModel::where('id_code', $attr->id_thuoctinh_giatri)->first();
                    if ($attrName && $valName) {
                        $attr_texts[] = ($attrName->ten ?: '') . ': ' . ($valName->ten ?: $valName->gia_tri ?: '');
                    }
                }
                $thuoctinh_text = implode(' - ', $attr_texts);
            }
        } else {
            $gia = $row_sp->khuyen_mai > 0 ? $row_sp->khuyen_mai : $row_sp->gia;
        }

        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

        $key_cart = $id_sp . '_' . $id_bienthe;
        if (isset($_SESSION['cart'][$key_cart])) {
            $_SESSION['cart'][$key_cart]['so_luong'] += $so_luong;
        } else {
            $_SESSION['cart'][$key_cart] = [
                'id_sp'          => $id_sp,
                'hinh_anh'       => $hinh_anh,
                'gia'            => $gia ?? 0,
                'thuoctinh'      => $id_bienthe,
                'thuoctinh_text' => $thuoctinh_text,
                'so_luong'       => $so_luong,
            ];
        }

        return response()->json([
            'success' => true,
            'count'   => count($_SESSION['cart']),
            'message' => __('Đã thêm sản phẩm vào giỏ hàng'),
        ]);
    }

    /**
     * Cập nhật số lượng sản phẩm (AJAX)
     * POST /ajax/cart/update
     */
    public function update(Request $request) {
        $key_cart  = $request->input('key_cart', '');
        $so_luong  = max(1, (int) $request->input('so_luong', 1));

        if ($key_cart && isset($_SESSION['cart'][$key_cart])) {
            $_SESSION['cart'][$key_cart]['so_luong'] = $so_luong;
        }

        return response()->json(['success' => true]);
    }

    /**
     * Xóa sản phẩm khỏi giỏ (AJAX)
     * POST /ajax/cart/remove
     */
    public function remove(Request $request) {
        $key_cart = $request->input('key_cart', '');

        if ($key_cart && isset($_SESSION['cart'][$key_cart])) {
            unset($_SESSION['cart'][$key_cart]);
            if (empty($_SESSION['cart'])) unset($_SESSION['cart']);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Áp dụng mã giảm giá (AJAX)
     * POST /ajax/cart/coupon
     */
    public function applyCoupon(Request $request) {
        $ma_sale  = trim($request->input('ma_sale', ''));
        $tongdong = (float) $request->input('tong_dong', 0);
        $phiship  = (float) $request->input('phi_ship', 0);

        if (empty($ma_sale)) {
            return response()->json(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
        }

        $row_sale = CouponModel::where('ma', $ma_sale)->first();
        $res      = ['success' => false, 'message' => ''];

        if (!$row_sale) {
            $res['message'] = 'Mã giảm giá không tồn tại hoặc đã hết hạn.';
            return response()->json($res);
        }

        $now = date('Y-m-d');

        if (!empty($row_sale->tu_ngay) && $row_sale->tu_ngay !== '0000-00-00' && $now < $row_sale->tu_ngay) {
            $res['message'] = 'Mã giảm giá chưa đến ngày sử dụng.';
        } elseif (!empty($row_sale->den_ngay) && $row_sale->den_ngay !== '0000-00-00' && $now > $row_sale->den_ngay) {
            $res['message'] = 'Mã giảm giá đã quá hạn sử dụng.';
        } elseif ($row_sale->dieu_kien > 0 && $tongdong < $row_sale->dieu_kien) {
            $res['message'] = 'Đơn hàng tối thiểu ' . number_format($row_sale->dieu_kien) . 'đ để áp dụng mã này.';
        } else {
            // Nếu mã giảm phí ship mà chưa chọn địa chỉ (phiship=0), dùng giá trị mặc định tạm thời
            $is_estimated = false;
            if ($row_sale->loai == 1 && $phiship <= 0) {
                $setting = SettingModel::first();
                $phiship      = (float) ($setting->default_ship_phi ?? 30000);
                $is_estimated = true;
            }

            // Tính số tiền giảm
            if ($row_sale->don_vi == 1) { // Phần trăm %
                $price_sale = ($row_sale->loai == 0)
                    ? $tongdong * ($row_sale->gia_tri / 100)
                    : $phiship  * ($row_sale->gia_tri / 100);
                if ($row_sale->gia_tri_max > 0 && $price_sale > $row_sale->gia_tri_max) {
                    $price_sale = (float) $row_sale->gia_tri_max;
                }
            } else { // Cố định (đ)
                $price_sale = (float) $row_sale->gia_tri;
            }

            if ($row_sale->loai == 0 && $price_sale > $tongdong) $price_sale = $tongdong;
            if ($row_sale->loai != 0 && $price_sale > $phiship)  $price_sale = $phiship;

            $donvi = ($row_sale->don_vi == 1) ? '%' : 'đ';
            $_SESSION['ma_sale']     = $ma_sale;
            $_SESSION['giatri_sale'] = $row_sale->gia_tri . $donvi;
            $_SESSION['phi_sale']    = $price_sale;

            $res = [
                'success'      => true,
                'price_sale'   => $price_sale,
                'is_estimated' => $is_estimated,
                'label'        => $ma_sale . ' (-' . $row_sale->gia_tri . $donvi . ')',
            ];
        }

        return response()->json($res);
    }

    /**
     * Xóa mã giảm giá đang áp dụng (AJAX)
     * POST /ajax/cart/coupon/remove
     */
    public function removeCoupon(Request $request) {
        unset($_SESSION['ma_sale'], $_SESSION['giatri_sale'], $_SESSION['phi_sale']);
        return response()->json(['success' => true]);
    }

    /**
     * Lấy danh sách mã giảm giá hợp lệ (AJAX)
     * POST /ajax/cart/coupons
     */
    public function getCoupons(Request $request) {
        $now      = date('Y-m-d');
        $tong_don = (float) $request->input('tong_don', 0);

        $coupons = CouponModel::where('hien_thi', 1)->get();

        // Lọc theo ngày và điều kiện đơn hàng
        $valid = array_filter((array) $coupons, function($c) use ($now, $tong_don) {
            if (!empty($c->tu_ngay)  && $c->tu_ngay  !== '0000-00-00' && $now < $c->tu_ngay)  return false;
            if (!empty($c->den_ngay) && $c->den_ngay !== '0000-00-00' && $now > $c->den_ngay) return false;
            return true;
        });

        return response()->json(['success' => true, 'coupons' => array_values($valid)]);
    }

    /**
     * Tính phí vận chuyển (AJAX)
     * POST /ajax/cart/shipping-fee (hoặc qua legacy)
     */
    public function shippingFee(Request $request) {
        $code_tinh = trim($request->input('code_tinh', ''));
        $tong_don  = (float) $request->input('tong_don', 0);

        if (empty($code_tinh)) {
            return response()->json(['success' => false]);
        }

        // Logic tính phí ship mẫu
        $setting = SettingModel::first();
        $phi_ship = (float) ($setting->default_ship_phi ?? 30000);
        
        // Có thể áp dụng các bảng phí ship cụ thể theo tỉnh nếu có \ShippingFeeModel

        $res = [
            'success' => true,
            'phi_ship' => $phi_ship
        ];

        // Nếu đang có mã giảm giá áp dụng, cập nhật lại số tiền giảm vì phí ship đã thay đổi
        if (!empty($_SESSION['ma_sale'])) {
            $ma_sale = $_SESSION['ma_sale'];
            $row_sale = CouponModel::where('ma', $ma_sale)->first();
            if ($row_sale) {
                if ($row_sale->don_vi == 1) { // %
                    $price_sale = ($row_sale->loai == 0)
                        ? $tong_don * ($row_sale->gia_tri / 100)
                        : $phi_ship  * ($row_sale->gia_tri / 100);
                    if ($row_sale->gia_tri_max > 0 && $price_sale > $row_sale->gia_tri_max) {
                        $price_sale = (float) $row_sale->gia_tri_max;
                    }
                } else {
                    $price_sale = (float) $row_sale->gia_tri;
                }

                if ($row_sale->loai == 0 && $price_sale > $tong_don) $price_sale = $tong_don;
                if ($row_sale->loai != 0 && $price_sale > $phi_ship)  $price_sale = $phi_ship;

                $_SESSION['phi_sale'] = $price_sale;
                $res['so_tien_giam'] = $price_sale;
            }
        }

        return response()->json($res);
    }
}
