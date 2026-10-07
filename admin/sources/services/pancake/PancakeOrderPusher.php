<?php
/**
 * PancakeOrderPusher
 *
 * Chịu trách nhiệm lấy dữ liệu đơn hàng từ cơ sở dữ liệu (CMS), chuyển đổi sang định dạng 
 * JSON Payload mà Pancake CRM yêu cầu và gọi API POST/PUT.
 */
class PancakeOrderPusher
{
    protected $db;
    protected $apiClient;

    public function __construct($db, $apiClient)
    {
        $this->db = $db;
        $this->apiClient = $apiClient;
    }

    /**
     * Đồng bộ 1 đơn hàng lên CRM (Thêm mới hoặc Cập nhật)
     */
    public function pushOrder($id_dh)
    {
        $payload = $this->buildPayload($id_dh);
        if (!$payload) return false;

        $pancake_order_id = $payload['order']['id'] ?? '';
        
        if (!empty($pancake_order_id)) {
            // Cập nhật (PUT)
            $response = $this->apiClient->updateOrder($pancake_order_id, $payload);
        } else {
            // Thêm mới (POST)
            $response = $this->apiClient->createOrder($payload);
            if ($response['success']) {
                $createdData = $response['data']['data'] ?? $response['data'] ?? [];
                if (!empty($createdData['id'])) {
                    $newPancakeId = $createdData['id'];
                    $this->db->reset();
                    $this->db->setTable('#_dathang');
                    $this->db->setWhere('id', $id_dh);
                    $this->db->update(['pancake_order_id' => $newPancakeId]);
                }
            }
        }

        return $response;
    }

    /**
     * Xây dựng JSON payload từ Database
     */
    protected function buildPayload($id_dh)
    {
        $order = $this->db->safeFetchOne("SELECT * FROM #_dathang WHERE id = ? LIMIT 1", [$id_dh]);
        if (!$order) return false;

        $addressArr = [];
        if (!empty($order['so_nha'])) $addressArr[] = $order['so_nha'];
        if (!empty($order['phuong'])) $addressArr[] = $order['phuong'];
        if (!empty($order['quan'])) $addressArr[] = $order['quan'];
        if (!empty($order['thanh_pho'])) $addressArr[] = $order['thanh_pho'];
        $fullAddress = !empty($addressArr) ? implode(', ', $addressArr) : $order['dia_chi'];

        // Get Pancake Address IDs
        $province_id = $order['thanh_pho'];
        $district_id = $order['quan'];
        $commune_id = $order['phuong'];

        $tinh = $this->db->safeFetchOne("SELECT pancake_id FROM #_thanhpho WHERE code = ? LIMIT 1", [$province_id]);
        if (!empty($tinh['pancake_id'])) $province_id = $tinh['pancake_id'];
        
        $huyen = $this->db->safeFetchOne("SELECT pancake_id FROM #_huyen WHERE code = ? LIMIT 1", [$district_id]);
        if (!empty($huyen['pancake_id'])) $district_id = $huyen['pancake_id'];
        
        $xa = $this->db->safeFetchOne("SELECT pancake_id FROM #_xa WHERE code = ? LIMIT 1", [$commune_id]);
        if (!empty($xa['pancake_id'])) $commune_id = $xa['pancake_id'];

        // Map status
        $status = $this->mapStatus($order['trangthai_xuly']);

        $orderData = [
            'custom_id' => $order['ma_dh'],
            'status' => $status,
            'bill_full_name' => $order['ho_ten'],
            'bill_phone_number' => $order['dien_thoai'],
            'bill_email' => $order['email'],
            'note' => $order['loi_nhan'],
            'surcharge' => (int)$order['phi_vanchuyen'],
            'discount' => (int)$order['so_tien_giam'],
            'shipping_address' => [
                'full_name' => $order['ho_ten'],
                'phone_number' => $order['dien_thoai'],
                'address' => $order['so_nha'],
                'full_address' => $fullAddress,
                'province_id' => $province_id,
                'district_id' => $district_id,
                'commune_id' => $commune_id
            ]
        ];

        if (!empty($order['pancake_order_id'])) {
            $orderData['id'] = $order['pancake_order_id'];
        }

        // Build items
        $items = [];
        $dbItems = $this->db->safeFetch("SELECT * FROM #_dathang_chitiet WHERE id_dh = ?", [$id_dh]);
        
        foreach ($dbItems as $it) {
            $itemData = [
                'name' => $it['ten_sp'],
                'quantity' => (int)$it['so_luong'],
                'price' => (int)$it['gia_ban'],
                'import_price' => (int)$it['gia_goc'],
                'custom_id' => 'ITEM-' . $it['id']
            ];

            // Nếu có hình ảnh
            if (!empty($it['hinh_sp'])) {
                $itemData['images'] = [urladmin . 'media/images/' . $it['hinh_sp']];
            }
            
            // Tìm pancake_product_id từ sản phẩm gốc
            if (!empty($it['id_sp'])) {
                $sp = $this->db->safeFetchOne("SELECT pancake_product_id, ma_sp FROM #_sanpham WHERE id_code = ? LIMIT 1", [$it['id_sp']]);
                if ($sp) {
                    if (!empty($sp['pancake_product_id'])) {
                        $itemData['product_id'] = $sp['pancake_product_id'];
                    }
                    if (!empty($sp['ma_sp'])) {
                        $itemData['custom_id'] = $sp['ma_sp'];
                    }
                }
            }

            // Tìm pancake_variation_id từ biến thể
            if (!empty($it['id_bienthe'])) {
                $bt = $this->db->safeFetchOne("SELECT pancake_variation_id, ma_sp FROM #_sanpham_bienthe WHERE id = ? LIMIT 1", [$it['id_bienthe']]);
                if ($bt) {
                    if (!empty($bt['pancake_variation_id'])) {
                        $itemData['variation_id'] = $bt['pancake_variation_id'];
                    }
                    if (!empty($bt['ma_sp'])) {
                        $itemData['custom_id'] = $bt['ma_sp'];
                    }
                }
            }

            // Gắn thuộc tính nếu có
            if (!empty($it['thuoc_tinh'])) {
                $itemData['variation_info'] = [
                    'name' => $it['thuoc_tinh'],
                    'retail_price' => (int)$it['gia_ban']
                ];
            }

            $items[] = $itemData;
        }

        if (!empty($items)) {
            $orderData['items'] = $items;
        }

        return ['order' => $orderData];
    }

    /**
     * Map ID trạng thái từ Web sang chuỗi Status của CRM
     */
    protected function mapStatus($statusId)
    {
        // trangthai_xuly: 0-Đơn mới, 1-Đang xử lý, 2-Đang giao, 3-Đã giao, 4-Trả hàng
        $statusId = (int)$statusId;
        switch ($statusId) {
            case 0:
                return 0; // Đơn mới
            case 1:
                return 1; // Đang xử lý
            case 2:
                return 2; // Đang giao
            case 3:
                return 3; // Đã giao
            case 4:
                return 6; // Hủy / Trả hàng
            default:
                return 0;
        }
    }
}
