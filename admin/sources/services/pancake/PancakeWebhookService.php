<?php
/**
 * PancakeWebhookService
 *
 * Xử lý các sự kiện Webhook dội về từ Pancake POS.
 */
class PancakeWebhookService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Hàm chính để xử lý payload từ Webhook.
     * Tùy thuộc vào 'event' hoặc cấu trúc payload, sẽ gọi đến các hàm xử lý tương ứng.
     */
    public function handleWebhook($payload)
    {
        if (empty($payload)) {
            return ['success' => false, 'message' => 'Empty payload'];
        }

        // Tùy theo cấu trúc chuẩn của Pancake POS webhook, có thể lấy type/event/event_type
        $event = $payload['event'] ?? $payload['type'] ?? $payload['event_type'] ?? '';

        // Tự động nhận diện nếu payload không có type rõ ràng
        if (empty($event)) {
            if (isset($payload['variations_warehouses'])) {
                $event = 'variations_warehouses';
            } elseif (isset($payload['order_number']) || isset($payload['order'])) {
                $event = 'orders';
            } elseif (isset($payload['variations']) || isset($payload['product_attributes'])) {
                $event = 'products';
            }
        }

        switch ($event) {
            case 'product_created':
            case 'product_updated':
            case 'products':
                return $this->handleProductUpdated($payload);
            
            case 'inventory_changed':
            case 'inventory':
            case 'variations_warehouses':
                return $this->handleInventoryChanged($payload);

            case 'order_created':
            case 'order_updated':
            case 'order_status_changed':
            case 'orders':
                return $this->handleOrderUpdated($payload);

            default:
                return ['success' => true, 'message' => 'Event not handled or unrecognized: ' . $event];
        }
    }

    /**
     * Xử lý sự kiện cập nhật Sản phẩm (Tên, SKU, Giá, Biến thể, Thuộc tính)
     */
    protected function handleProductUpdated($payload)
    {
        // Pancake webhook có thể gửi object sản phẩm trực tiếp ở root hoặc bọc trong 'data'
        $data = isset($payload['id']) ? $payload : ($payload['data'] ?? []);
        if (empty($data)) {
            return ['success' => false, 'message' => 'No product data'];
        }

        require_once __DIR__ . '/PancakeProductSync.php';
        $productSync = new PancakeProductSync($this->db, null);
        $result = $productSync->syncSingleProduct($data, true);

        if ($result) {
            return ['success' => true, 'message' => 'Product synced from webhook successfully'];
        }

        return ['success' => false, 'message' => 'Failed to sync product from webhook'];
    }

    /**
     * Xử lý sự kiện cập nhật Tồn kho (variations_warehouses)
     */
    protected function handleInventoryChanged($payload)
    {
        // Lấy danh sách items tồn kho cần cập nhật
        $items = [];
        if (!empty($payload['variations_warehouses']) && is_array($payload['variations_warehouses'])) {
            $items = $payload['variations_warehouses'];
        } elseif (!empty($payload['data']) && is_array($payload['data'])) {
            $items = isset($payload['data'][0]) ? $payload['data'] : [$payload['data']];
        } elseif (isset($payload['variation_id']) || isset($payload['id']) || isset($payload['sku'])) {
            $items = [$payload];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'No inventory items found in payload'];
        }

        $updatedCount = 0;

        foreach ($items as $item) {
            $sku = $item['sku'] ?? $item['custom_id'] ?? $item['display_id'] ?? '';
            $variation_id = $item['variation_id'] ?? $item['id'] ?? '';
            $stock = null;

            if (isset($item['remain_quantity'])) {
                $stock = (int)$item['remain_quantity'];
            } elseif (isset($item['available_stock'])) {
                $stock = (int)$item['available_stock'];
            } elseif (isset($item['quantity'])) {
                $stock = (int)$item['quantity'];
            }

            if ($stock === null || (empty($variation_id) && empty($sku))) {
                continue;
            }

            $matched = false;

            // 1. Tìm biến thể theo pancake_variation_id
            if (!empty($variation_id)) {
                $variant = $this->db->safeFetchOne("SELECT id, id_sanpham FROM #_sanpham_bienthe WHERE pancake_variation_id = ? LIMIT 1", [$variation_id]);
                if ($variant) {
                    $this->db->reset();
                    $this->db->setTable('#_sanpham_bienthe');
                    $this->db->setWhere('id', $variant['id']);
                    $this->db->update(['so_luong' => $stock, 'cap_nhat' => date('Y-m-d H:i:s')]);

                    // Cập nhật tổng tồn kho cho sản phẩm cha
                    $this->updateParentTotalStock($variant['id_sanpham']);
                    $matched = true;
                    $updatedCount++;
                } else {
                    // Kiểm tra xem variation_id có phải pancake_product_id của sản phẩm đơn không
                    $product = $this->db->safeFetchOne("SELECT id_code FROM #_sanpham WHERE pancake_product_id = ? LIMIT 1", [$variation_id]);
                    if ($product) {
                        $this->db->reset();
                        $this->db->setTable('#_sanpham');
                        $this->db->setWhere('id_code', $product['id_code']);
                        $this->db->update(['so_luong' => $stock, 'cap_nhat' => time()]);
                        $matched = true;
                        $updatedCount++;
                    }
                }
            }

            // 2. Tìm theo SKU (nếu chưa match)
            if (!$matched && !empty($sku)) {
                $variant = $this->db->safeFetchOne("SELECT id, id_sanpham FROM #_sanpham_bienthe WHERE ma_sp = ? LIMIT 1", [$sku]);
                if ($variant) {
                    $this->db->reset();
                    $this->db->setTable('#_sanpham_bienthe');
                    $this->db->setWhere('id', $variant['id']);
                    $this->db->update(['so_luong' => $stock, 'cap_nhat' => date('Y-m-d H:i:s')]);

                    $this->updateParentTotalStock($variant['id_sanpham']);
                    $updatedCount++;
                } else {
                    $product = $this->db->safeFetchOne("SELECT id_code FROM #_sanpham WHERE ma_sp = ? LIMIT 1", [$sku]);
                    if ($product) {
                        $this->db->reset();
                        $this->db->setTable('#_sanpham');
                        $this->db->setWhere('id_code', $product['id_code']);
                        $this->db->update(['so_luong' => $stock, 'cap_nhat' => time()]);
                        $updatedCount++;
                    }
                }
            }
        }

        if ($updatedCount > 0) {
            return ['success' => true, 'message' => "Updated stock for $updatedCount item(s)"];
        }

        return ['success' => false, 'message' => 'No matching product/variant found for stock update'];
    }

    /**
     * Cập nhật tổng tồn kho cho sản phẩm chính từ các biến thể
     */
    protected function updateParentTotalStock($id_sanpham)
    {
        if (empty($id_sanpham)) return;
        $row = $this->db->safeFetchOne("SELECT SUM(so_luong) as total_stock FROM #_sanpham_bienthe WHERE id_sanpham = ?", [$id_sanpham]);
        $totalStock = (int)($row['total_stock'] ?? 0);

        $this->db->reset();
        $this->db->setTable('#_sanpham');
        $this->db->setWhere('id_code', $id_sanpham);
        $this->db->update(['so_luong' => $totalStock, 'cap_nhat' => time()]);
    }

    /**
     * Xử lý sự kiện Đơn hàng (Cập nhật trạng thái)
     */
    protected function handleOrderUpdated($payload)
    {
        $data = $payload['data'] ?? $payload['order'] ?? (isset($payload['id']) || isset($payload['order_number']) ? $payload : []);
        if (empty($data)) return ['success' => false, 'message' => 'No order data in webhook payload'];

        require_once __DIR__ . '/PancakeOrderSync.php';
        
        $orderSync = new PancakeOrderSync($this->db, null);
        $result = $orderSync->syncSingleOrder($data);
        
        if ($result) {
            return ['success' => true, 'message' => 'Order synced from webhook successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to sync order from webhook'];
        }
    }
}
?>
