<?php
namespace App\Controllers;

use App\Core\Response;

class CartControllerTemp extends Controller {
    public function index($request) {
        // Fetch Tax Settings
        $setting_vat = \SettingModel::first();
        $vat_rate = (double)($setting_vat->vat_rate ?? 0);
        $vat_type = (int)($setting_vat->vat_type ?? 0);

        // Lấy danh sách mã giảm giá còn khả dụng
        $now_date = date('Y-m-d');
        // Vì CouponModel chứa model cơ bản, ta có thể query bằng raw DB hoặc Model. 
        // Nhưng truy vấn gốc dùng GROUP BY nên query builder cơ bản có thể gặp khó khăn, ta sẽ dùng raw queries bằng $GLOBALS['d'] hoặc PDO.
        // Để không làm phức tạp quá, ta tạm dùng raw PDO từ Model::getConnection() hoặc DB wrapper hiện tại.
        $db = \Model::getConnection();
        $sql = "SELECT km.ma, km.gia_tri, km.don_vi, km.dieu_kien,
                    COUNT(ls.id) AS da_dung, km.gioi_han
             FROM db_khuyenmai km
             LEFT JOIN db_khuyenmai_ls ls ON ls.ma_km = km.ma
             WHERE (km.id_thanhvien = '' OR km.id_thanhvien IS NULL)
               AND (km.tu_ngay IS NULL OR km.tu_ngay < '2000-01-01' OR km.tu_ngay <= :now1)
               AND (km.den_ngay IS NULL OR km.den_ngay < '2000-01-01' OR km.den_ngay >= :now2)
             GROUP BY km.id
             HAVING (km.gioi_han = 0 OR da_dung < km.gioi_han)
             ORDER BY km.id DESC
             LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute(['now1' => $now_date, 'now2' => $now_date]);
        $list_coupons = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $cartItems = [];
        $tong = 0;

        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $value) {
                if ((int)$value['id_sp'] <= 0) continue;
                
                $row_sp = \ProductModel::where('id_code', $value['id_sp'])->first();
                if (!$row_sp) continue;

                $gia_sp = $value['gia'];
                $so_luong = (int) $value['so_luong'];
                $tong += ($gia_sp * $so_luong);

                $url_sp = route('product.show', $row_sp->alias ?? '');
                
                // Construct parameters for variant URL
                if (!empty($value['thuoctinh']) && (int)$value['thuoctinh'] > 0) {
                    $id_bienthe = (int)$value['thuoctinh'];
                    $sql_attr = "SELECT btt.id_thuoctinh_giatri, tt.loai, ttg.alias, ttg.gia_tri, tt.ten as ten_thuoctinh, ttg.ten as ten_giatri 
                                 FROM db_sanpham_bienthe_thuoctinh btt
                                 JOIN db_thuoctinh tt ON btt.id_thuoctinh = tt.id_code AND tt.lang = :lang1
                                 JOIN db_thuoctinh_giatri ttg ON btt.id_thuoctinh_giatri = ttg.id_code AND ttg.lang = :lang2
                                 WHERE btt.id_bienthe = :id_bienthe";
                    $stmt_attr = $db->prepare($sql_attr);
                    $stmt_attr->execute(['lang1' => LANG, 'lang2' => LANG, 'id_bienthe' => $id_bienthe]);
                    $attrs = $stmt_attr->fetchAll(\PDO::FETCH_ASSOC);
                    
                    if (!empty($attrs)) {
                        $queryStr = '';
                        foreach ($attrs as $attr) {
                            $key_param = !empty($attr['loai']) && in_array($attr['loai'], ['color', 'size', 'image']) 
                                         ? $attr['loai'] 
                                         : str_to_alias($attr['ten_thuoctinh']);
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
                    'ten_sp' => $row_sp->ten ?? '',
                    'url_sp' => $url_sp,
                    'thuoctinh_text' => $value['thuoctinh_text'] ?? ''
                ];
            }
        }

        return view('pages/cart/index', [
            'title' => __('Giỏ hàng'),
            'tong' => $tong,
            'vat_rate' => $vat_rate,
            'vat_type' => $vat_type,
            'list_coupons' => $list_coupons,
            'cartItems' => $cartItems,
            'phi_ship' => $_SESSION['phi_ship'] ?? 0,
            'so_giam' => $_SESSION['phi_sale'] ?? 0
        ]);
    }
}
