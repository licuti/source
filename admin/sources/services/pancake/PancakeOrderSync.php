<?php
/**
 * PancakeOrderSync
 *
 * Đồng bộ đơn hàng từ Pancake CRM về cơ sở dữ liệu (CMS)
 */
class PancakeOrderSync
{
    protected $db;
    protected $apiClient;

    public function __construct($db, $apiClient)
    {
        $this->db = $db;
        $this->apiClient = $apiClient;
    }

    /**
     * Đồng bộ danh sách đơn hàng từ CRM
     */
    public function pullAndSync($page = 1, $limit = 50)
    {
        $response = $this->apiClient->getOrders($page, $limit);
        
        if (!$response['success']) {
            return ['success' => false, 'message' => $response['message']];
        }

        $responseData = $response['data']['data'] ?? $response['data'] ?? [];
        
        $orders = [];
        if (isset($responseData['entries'])) {
            $orders = $responseData['entries'];
        } elseif (is_array($responseData) && isset($responseData[0])) {
            $orders = $responseData;
        }

        if (empty($orders)) {
            return ['success' => true, 'total_synced' => 0, 'message' => 'No orders found'];
        }

        $orders = array_reverse($orders); // Để đơn cũ vào trước, đơn mới vào sau
        $total_synced = 0;

        foreach ($orders as $o) {
            if ($this->syncSingleOrder($o)) {
                $total_synced++;
            }
        }

        return ['success' => true, 'total_synced' => $total_synced, 'message' => 'Synced page ' . $page];
    }

    /**
     * Đồng bộ 1 đơn hàng cụ thể
     */
    public function syncSingleOrder($o)
    {
        $pancake_order_id = $o['id'] ?? '';
        $custom_id = $o['custom_id'] ?? '';
        if (empty($pancake_order_id)) return false;

        $name = !empty($o['bill_full_name']) ? $o['bill_full_name'] : ($o['contact']['name'] ?? 'Khách hàng Pancake');
        $phone = !empty($o['bill_phone_number']) ? $o['bill_phone_number'] : ($o['contact']['phone_number'] ?? '');
        $email = !empty($o['bill_email']) ? $o['bill_email'] : ($o['contact']['email'] ?? '');
        $note = $o['note'] ?? '';
        $surcharge = (int)($o['surcharge'] ?? 0);
        $discount = (int)($o['discount'] ?? ($o['total_discount'] ?? 0));
        
        // Địa chỉ
        $address = $o['extra_infor']['address'] ?? '';
        
        $statusId = $this->mapStatusToLocal($o['status'] ?? 'new');
        
        $orderCode = $custom_id ?: ('CRM-' . strtoupper(substr(md5($pancake_order_id), 0, 8)));

        // Kiểm tra xem đơn hàng đã tồn tại chưa
        $existing = $this->db->safeFetchOne("SELECT id FROM #_dathang WHERE pancake_order_id = ? OR ma_dh = ? LIMIT 1", [$pancake_order_id, $orderCode]);
        $id_dh = 0;

        $orderData = [
            'ho_ten' => $name,
            'dien_thoai' => $phone,
            'email' => $email,
            'dia_chi' => $address,
            'so_nha' => '',
            'phuong' => 0,
            'quan' => 0,
            'thanh_pho' => 0,
            'thanh_toan' => '',
            'loi_nhan' => $note,
            'phi_vanchuyen' => $surcharge,
            'so_tien_giam' => $discount,
            'tinhtrang_donhang' => $statusId,
            'trangthai_xuly' => $statusId,
            'trangthai_thanhtoan' => 0,
            'pancake_order_id' => $pancake_order_id,
            'ngay_capnhat' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            $id_dh = $existing['id'];
            $this->db->reset();
            $this->db->setTable('#_dathang');
            $this->db->setWhere('id', $id_dh);
            $updateOk = $this->db->update($orderData);
            if (!$updateOk) echo "Update failed for order: {$pancake_order_id}\n";
            
            // Xóa chi tiết cũ để thêm lại
            $this->db->reset();
            $this->db->setTable('#_dathang_chitiet');
            $this->db->setWhere('id_dh', $id_dh);
            $this->db->delete();
            
        } else {
            $orderData['ma_dh'] = $orderCode;
            $orderData['ngay_dathang'] = date('Y-m-d', strtotime($o['inserted_at'] ?? 'now'));
            
            $this->db->reset();
            $this->db->setTable('#_dathang');
            $id_dh = $this->db->insert($orderData);
            if (!$id_dh) {
                echo "Insert failed for order: {$pancake_order_id}. Error: " . print_r($this->db->getLastError(), true) . "\n";
            }
        }

        if ($id_dh && !empty($o['items'])) {
            foreach ($o['items'] as $item) {
                // Tên sản phẩm: Lấy ưu tiên từ item name -> variation_info -> variation
                $itemName = '';
                if (!empty($item['name'])) {
                    $itemName = $item['name'];
                } elseif (!empty($item['variation_info']['name'])) {
                    $itemName = $item['variation_info']['name'];
                } elseif (!empty($item['variation']['product']['name'])) {
                    $itemName = $item['variation']['product']['name'];
                } elseif (!empty($item['variation']['name'])) {
                    $itemName = $item['variation']['name'];
                } else {
                    $itemName = 'Sản phẩm CRM';
                }

                $quantity = (int)($item['quantity'] ?? 1);
                $retailPrice = (int)($item['price'] ?? ($item['retail_price'] ?? 0));
                $importPrice = (int)($item['import_price'] ?? ($item['variation_info']['import_price'] ?? 0));
                $image = $item['images'][0] ?? ($item['variation_info']['images'][0] ?? '');
                $itemSku = $item['custom_id'] ?? ($item['variation_info']['custom_id'] ?? ($item['variation']['custom_id'] ?? ''));
                
                $id_sp = 0;
                if (!empty($itemSku)) {
                    $sp = $this->db->safeFetchOne("SELECT id_code FROM #_sanpham WHERE ma_sp = ? LIMIT 1", [$itemSku]);
                    if ($sp) $id_sp = $sp['id_code'];
                }

                // Xử lý chuỗi thuộc tính hiển thị (thuoc_tinh), tránh lưu JSON thô
                $attrStr = '';
                $vInfo = $item['variation_info'] ?? ($item['variation'] ?? null);
                if (!empty($vInfo) && is_array($vInfo)) {
                    $attrParts = [];
                    if (!empty($vInfo['attributes']) && is_array($vInfo['attributes'])) {
                        foreach ($vInfo['attributes'] as $attr) {
                            if (is_array($attr)) {
                                $aName = $attr['name'] ?? ($attr['attribute_name'] ?? '');
                                $aVal = $attr['value'] ?? ($attr['attribute_value'] ?? '');
                                if ($aName && $aVal) {
                                    $attrParts[] = "$aName: $aVal";
                                } elseif ($aVal) {
                                    $attrParts[] = $aVal;
                                }
                            } elseif (is_string($attr)) {
                                $attrParts[] = $attr;
                            }
                        }
                    } elseif (!empty($vInfo['sort_variation_attributes'])) {
                        $attrParts[] = str_replace('|', ' - ', $vInfo['sort_variation_attributes']);
                    }

                    if (!empty($attrParts)) {
                        $attrStr = implode(' - ', $attrParts);
                    }
                } elseif (is_string($vInfo) && !empty($vInfo)) {
                    if (substr($vInfo, 0, 1) !== '{' && substr($vInfo, 0, 1) !== '[') {
                        $attrStr = $vInfo;
                    }
                }

                $itemData = [
                    'id_dh' => $id_dh,
                    'ma_dh' => $orderCode,
                    'id_sp' => $id_sp,
                    'ten_sp' => $itemName,
                    'so_luong' => $quantity,
                    'gia_goc' => $importPrice,
                    'gia_ban' => $retailPrice,
                    'hinh_sp' => $image,
                    'thuoc_tinh' => $attrStr
                ];
                
                $this->db->reset();
                $this->db->setTable('#_dathang_chitiet');
                $this->db->insert($itemData);
            }
        }

        return true;
    }

    /**
     * Chuyển đổi trạng thái Pancake CRM sang local ID (trangthai_xuly)
     */
    protected function mapStatusToLocal($statusStr)
    {
        $statusStr = strtolower($statusStr);
        switch ($statusStr) {
            case 'new':
                return 0; // Đơn mới
            case 'processing':
            case 'confirmed':
                return 1; // Đang xử lý
            case 'shipped':
            case 'delivering':
                return 2; // Đang giao
            case 'successful':
            case 'completed':
                return 3; // Đã giao
            case 'cancelled':
            case 'returned':
                return 4; // Trả hàng / Hủy
            default:
                return 0; // Mặc định Đơn mới
        }
    }
}
