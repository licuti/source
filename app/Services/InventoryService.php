<?php
namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductVariantModel;

class InventoryService {

    /**
     * Đồng bộ tồn kho từ các biến thể lên sản phẩm cha (chỉ dành cho sản phẩm biến thể).
     * Nếu là sản phẩm đơn giản (simple), giữ nguyên tồn kho hiện tại của nó.
     *
     * @param int $productId id_code của sản phẩm
     * @return void
     */
    public static function syncProductStock(int $productId) {
        $product = ProductModel::where('id_code', $productId)->first();
        
        if (!$product) {
            return;
        }

        // Nếu là sản phẩm biến thể, tính tổng tồn kho của các biến thể
        if ($product->product_type === 'variable') {
            $totalStock = ProductVariantModel::query()
                ->where('product_id', $productId)
                ->sum('stock_quantity');

            // Cập nhật lại tồn kho và trạng thái cho sản phẩm cha ở TẤT CẢ các bản dịch (lang)
            ProductModel::query()
                ->where('id_code', $productId)
                ->update([
                    'stock_quantity' => (int)$totalStock,
                    'stock_status'   => $totalStock > 0 ? 'in_stock' : 'out_of_stock'
                ]);
        }
    }

    /**
     * Trừ tồn kho khi có đơn hàng mới
     *
     * @param int $productId
     * @param int $variantId (truyền 0 nếu là sản phẩm đơn giản)
     * @param int $quantity Số lượng cần trừ
     * @return bool
     */
    public static function reduceStock(int $productId, int $variantId, int $quantity): bool {
        // Tương lai sẽ implement
        return true;
    }

    /**
     * Cộng lại tồn kho khi khách hủy đơn hoặc hoàn hàng
     *
     * @param int $productId
     * @param int $variantId
     * @param int $quantity
     * @return bool
     */
    public static function increaseStock(int $productId, int $variantId, int $quantity): bool {
        // Tương lai sẽ implement
        return true;
    }
}
