<?php

namespace App\Controllers;

/**
 * OrderController
 * Quản lý đơn hàng ở phía người dùng.
 */
class OrderController extends Controller {
    
    /**
     * Trang tra cứu vận đơn
     * GET /tra-cuu
     */
    public function tracking($request) {
        $order_id = $request->input('id', '');
        $phone = $request->input('p', '');

        $order = null;
        $order_details = [];
        $history = [];
        $error = '';
        $total_details_price = 0;

        global $d;

        if ($order_id && $phone) {
            $order_id_safe = addslashes($order_id);
            $phone_safe = addslashes($phone);

            $order = $d->simple_fetch("SELECT * FROM #_dathang WHERE ma_dh = '$order_id_safe' AND dien_thoai = '$phone_safe' LIMIT 1");
            
            if (!$order) {
                $error = "Không tìm thấy đơn hàng phù hợp với thông tin đã cung cấp. Vui lòng kiểm tra lại Mã đơn hàng hoặc Số điện thoại.";
            } else {
                $order_id_db = (int) $order['id'];
                
                $order_details = $d->o_fet("SELECT * FROM #_dathang_chitiet WHERE id_dh = $order_id_db ORDER BY id ASC");
                $history = $d->o_fet("SELECT * FROM #_dathang_xuly WHERE id_dh = $order_id_db ORDER BY ngay_xuly DESC, id DESC");
                
                // Tính tổng tiền chi tiết (để tránh query logic trên view)
                $res_total = $d->simple_fetch("SELECT SUM(gia_ban*so_luong) as total FROM #_dathang_chitiet WHERE id_dh = $order_id_db");
                $total_details_price = (float)($res_total['total'] ?? 0);
            }
        }

        // Map status for Stepper
        $status = (int) ($order['trangthai_xuly'] ?? -1);
        $steps = [
            ['label' => 'Đặt hàng thành công', 'icon' => 'fa-check', 'active_val' => 0],
            ['label' => 'Đang xử lý', 'icon' => 'fa-hourglass-half', 'active_val' => 1],
            ['label' => 'Đang giao hàng', 'icon' => 'fa-truck', 'active_val' => 2],
            ['label' => 'Đã giao hàng', 'icon' => 'fa-handshake', 'active_val' => 3],
        ];

        // Đăng ký URL dịch
        $translations = \App\Models\PageModel::where('view', 'pages/order-tracking')->get();
        $urls = [];
        foreach ($translations as $t) {
            $urls[$t->lang] = route('order.tracking.' . $t->lang);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        return view('pages/order-tracking', [
            'order_id'            => $order_id,
            'phone'               => $phone,
            'order'               => $order,
            'order_details'       => $order_details,
            'history'             => $history,
            'error'               => $error,
            'status'              => $status,
            'steps'               => $steps,
            'total_details_price' => $total_details_price,
            'title'               => 'Tra cứu vận đơn',
            'd'                   => $d
        ]);
    }
}
