<?php
/**
 * PancakeProductPusher
 *
 * Chịu trách nhiệm lấy dữ liệu từ cơ sở dữ liệu (CMS), chuyển đổi sang định dạng 
 * JSON Payload mà Pancake CRM yêu cầu và gọi API POST/PUT.
 */
class PancakeProductPusher
{
    protected $db;
    protected $apiClient;

    public function __construct($db, $apiClient)
    {
        $this->db = $db;
        $this->apiClient = $apiClient;
    }

    /**
     * Đồng bộ 1 sản phẩm lên CRM (Thêm mới hoặc Cập nhật)
     */
    public function pushProduct($id_code)
    {
        $data = $this->buildPayload($id_code);
        if (!$data) return false;

        $pancake_product_id = $data['id'] ?? '';
        $logFile = __DIR__ . '/../../../logs/pancake_push.log';

        if (!empty($pancake_product_id)) {
            // Cập nhật (PUT) - Yêu cầu bọc trong object 'data'
            $payload = ['data' => $data];
            $response = $this->apiClient->updateProduct($pancake_product_id, $payload);
        } else {
            // Thêm mới (POST) - Gửi thẳng object $data
            $response = $this->apiClient->createProduct($data);
            if ($response['success']) {
                $createdData = $response['data']['data'] ?? $response['data'] ?? [];
                if (!empty($createdData['id'])) {
                    $newPancakeId = $createdData['id'];
                    $this->db->reset();
                    $this->db->setTable('#_sanpham');
                    $this->db->setWhere('id_code', $id_code);
                    $this->db->update(['pancake_product_id' => $newPancakeId]);
                }
            }
        }

        // Ghi log kết quả đẩy dữ liệu để kiểm tra
        if (!$response['success']) {
            $msg = date('Y-m-d H:i:s') . " - Push Product ID $id_code FAILED: " . ($response['message'] ?? 'Unknown error') . "\n";
            @file_put_contents($logFile, $msg, FILE_APPEND);
        } else {
            $msg = date('Y-m-d H:i:s') . " - Push Product ID $id_code SUCCESS\n";
            @file_put_contents($logFile, $msg, FILE_APPEND);
        }

        return $response;
    }

    /**
     * Xóa mềm sản phẩm trên CRM
     */
    public function deleteProduct($pancake_product_id)
    {
        if (empty($pancake_product_id)) return false;
        
        $payload = [
            'data' => [
                'id' => $pancake_product_id,
                'is_removed' => true
            ]
        ];
        return $this->apiClient->updateProduct($pancake_product_id, $payload);
    }

    /**
     * Xây dựng JSON payload từ Database
     */
    protected function buildPayload($id_code)
    {
        $product = $this->db->safeFetchOne("SELECT * FROM #_sanpham WHERE id_code = ? AND lang = 'vi' LIMIT 1", [$id_code]);
        if (!$product) return false;

        // Đồng bộ chuẩn 2 chiều: Giá vốn (import_price) = gia, Giá bán (retail_price) = khuyen_mai (fallback gia)
        $importPrice = (int)($product['gia'] ?? 0);
        $khuyenMai   = (int)($product['khuyen_mai'] ?? 0);
        $retailPrice = ($khuyenMai > 0) ? $khuyenMai : $importPrice;

        $data = [
            'name' => $product['ten'],
            'import_price' => $importPrice,
            'retail_price' => $retailPrice,
            'remain_quantity' => (int)$product['so_luong'],
            'custom_id' => $product['ma_sp'] ?: ('WEB-' . $id_code),
            'description' => strip_tags($product['mo_ta'] ?? ''), // Đã thêm đồng bộ mô tả
            'is_removed' => false,
        ];

        // Gửi danh mục nếu có
        if (!empty($product['id_loai'])) {
            $cat = $this->db->safeFetchOne("SELECT pancake_category_id FROM #_category WHERE id_code = ? LIMIT 1", [$product['id_loai']]);
            if ($cat && !empty($cat['pancake_category_id'])) {
                $data['category_ids'] = [$cat['pancake_category_id']];
            }
        }

        if (!empty($product['pancake_product_id'])) {
            $data['id'] = $product['pancake_product_id'];
        }

        // Hình ảnh sản phẩm (Gửi URL đầy đủ) - Đã ẩn theo yêu cầu không lấy ảnh
        /*
        $images = [];
        if (!empty($product['hinh_anh'])) {
            $images[] = preg_match('#^(https?:)?//#i', $product['hinh_anh']) ? $product['hinh_anh'] : urladmin . 'media/images/' . $product['hinh_anh'];
        }
        $album = $this->db->safeFetch("SELECT hinh_anh FROM #_sanpham_hinhanh WHERE id_sp = ? ORDER BY stt ASC", [$id_code]);
        foreach ($album as $img) {
            if (!empty($img['hinh_anh'])) {
                $images[] = preg_match('#^(https?:)?//#i', $img['hinh_anh']) ? $img['hinh_anh'] : urladmin . 'media/images/' . $img['hinh_anh'];
            }
        }
        if (!empty($images)) {
            $data['images'] = $images;
        }
        */

        // --- CẤU TRÚC BIẾN THỂ VÀ THUỘC TÍNH ---
        
        $dbVariants = $this->db->safeFetch("SELECT * FROM #_sanpham_bienthe WHERE id_sanpham = ?", [$id_code]);
        
        // Chỉ xử lý thuộc tính và biến thể nếu SẢN PHẨM CÓ BIẾN THỂ
        if (!empty($dbVariants)) {
            $attrMap = []; // Để lookup id -> name cho biến thể
            $valMap = [];  // Để lookup id -> value cho biến thể

            // Lấy tất cả thuộc tính có thể dùng
            $dbAttributes = $this->db->safeFetch("SELECT id_code, ten FROM #_thuoctinh WHERE id_sanpham IN (0, ?) AND lang = 'vi'", [$id_code]);
            
            // Tìm xem thực sự các biến thể đang dùng những thuộc tính / giá trị nào
            $usedAttrs = [];
            $usedValues = [];
            $variantAttrsMap = []; // map variant_id => array of {name, value}

            foreach ($dbVariants as $var) {
                $varAttrs = $this->db->safeFetch("SELECT id_thuoctinh, id_thuoctinh_giatri FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = ?", [$var['id']]);
                foreach ($varAttrs as $va) {
                    $usedAttrs[$va['id_thuoctinh']] = true;
                    $usedValues[$va['id_thuoctinh_giatri']] = true;
                }
                $variantAttrsMap[$var['id']] = $varAttrs;
            }

            $attributesPayload = [];
            foreach ($dbAttributes as $attr) {
                $attrId = $attr['id_code'];
                if (!isset($usedAttrs[$attrId])) continue; // Bỏ qua nếu không biến thể nào dùng

                $attrName = $attr['ten'];
                $attrMap[$attrId] = $attrName;

                $dbValues = $this->db->safeFetch("SELECT id_code, ten FROM #_thuoctinh_giatri WHERE id_thuoctinh = ? AND id_sanpham IN (0, ?) AND lang = 'vi'", [$attrId, $id_code]);
                
                $valArr = [];
                foreach ($dbValues as $val) {
                    if (isset($usedValues[$val['id_code']])) {
                        $valMap[$val['id_code']] = $val['ten'];
                        $valArr[] = $val['ten'];
                    }
                }
                if (!empty($valArr)) {
                    $attributesPayload[] = [
                        'name' => $attrName,
                        'values' => $valArr
                    ];
                }
            }

            if (!empty($attributesPayload)) {
                $data['product_attributes'] = $attributesPayload;
            }

            // Xây dựng Payload cho từng biến thể
            $variationsPayload = [];
            foreach ($dbVariants as $var) {
                $varId = $var['id'];
                $varCustomId = $var['ma_sp'] ?: ('WEB-VAR-' . $varId);

                $vImport = (int)($var['gia'] ?? 0);
                $vGiaKM  = (int)($var['khuyen_mai'] ?? 0);
                $vRetail = ($vGiaKM > 0) ? $vGiaKM : $vImport;

                $varData = [
                    'custom_id' => $varCustomId,
                    'import_price' => $vImport,
                    'retail_price' => $vRetail,
                    'remain_quantity' => (int)$var['so_luong']
                ];

                /* Đã ẩn theo yêu cầu không lấy ảnh
                if (!empty($var['hinh_anh'])) {
                    $varData['images'] = [preg_match('#^(https?:)?//#i', $var['hinh_anh']) ? $var['hinh_anh'] : urladmin . 'media/images/' . $var['hinh_anh']];
                }
                */

                $varAttrArr = [];
                if (isset($variantAttrsMap[$varId])) {
                    foreach ($variantAttrsMap[$varId] as $va) {
                        $aId = $va['id_thuoctinh'];
                        $vId = $va['id_thuoctinh_giatri'];
                        if (isset($attrMap[$aId]) && isset($valMap[$vId])) {
                            $varAttrArr[] = [
                                'name' => $attrMap[$aId],
                                'value' => $valMap[$vId]
                            ];
                        }
                    }
                }
                
                if (!empty($varAttrArr)) {
                    $varData['attributes'] = $varAttrArr;
                }

                $variationsPayload[] = $varData;
            }

            if (!empty($variationsPayload)) {
                $data['variations'] = $variationsPayload;
            }
        } else {
            // Trường hợp sản phẩm ĐƠN GIẢN (không biến thể trên Web):
            // CRM vẫn tạo 1 biến thể mặc định ngầm. Ta cần gửi biến thể mặc định này để CRM cập nhật giá bán/giá vốn của biến thể đó.
            $data['variations'] = [
                [
                    'custom_id' => $data['custom_id'],
                    'import_price' => $importPrice,
                    'retail_price' => $retailPrice,
                    'remain_quantity' => (int)$product['so_luong']
                ]
            ];
        }

        return $data;
    }
}
?>
