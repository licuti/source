<?php
/**
 * PancakeProductSync
 *
 * Chịu trách nhiệm đồng bộ mảng dữ liệu lấy từ PancakeApiClient vào Database
 */
class PancakeProductSync
{
    protected $db;
    protected $apiClient;
    protected $languages;

    protected function makeSlug($name, $id_code = 0)
    {
        $slug = (string)$name;
        if (function_exists('slugify')) {
            $slug = slugify($slug);
        }

        if (empty($slug)) {
            $slug = 'san-pham';
        }

        // Kiểm tra xem slug đã trùng trong CSDL chưa
        $check = $this->db->safeFetchOne(
            "SELECT id FROM #_sanpham WHERE alias = ? AND id_code != ? LIMIT 1",
            [$slug, $id_code]
        );
        if ($check) {
            $slug .= '-' . rand(100, 999);
        }
        return $slug;
    }

    public function __construct($db, $apiClient)
    {
        global $config;
        $this->db = $db;
        $this->apiClient = $apiClient;
        
        // Tắt strict mode để tránh lỗi thiếu giá trị mặc định khi INSERT
        $this->db->query("SET SESSION sql_mode = ''");
        
        $this->languages = ['vi']; // Mặc định
        if (!empty($config['lang']) && is_array($config['lang'])) {
            $this->languages = array_column($config['lang'], 'code');
        }
    }

    public function pullAndSync($page = 1, $limit = 50, $selected_ids = [])
    {
        $response = $this->apiClient->getProducts($page, $limit);
        
        if (!$response['success']) {
            return ['success' => false, 'message' => $response['message']];
        }

        $products = $response['data']['data'] ?? $response['data'] ?? [];
        if (isset($response['data']['products'])) {
             $products = $response['data']['products'];
        }
        
        if (empty($products)) {
            return ['success' => true, 'total_synced' => 0, 'message' => 'No products found'];
        }

        // Lọc theo ID được chọn
        if (!empty($selected_ids)) {
            $products = array_filter($products, function($p) use ($selected_ids) {
                return in_array($p['id'] ?? '', $selected_ids);
            });
        }

        // Đảo ngược mảng để sản phẩm cũ nhất được insert trước (ID nhỏ), sản phẩm mới nhất insert sau (ID lớn)
        // Nhờ vậy khi web sort theo id DESC, sản phẩm mới nhất sẽ nằm trên cùng đúng chuẩn
        $products = array_reverse($products);

        $total_synced = 0;
        foreach ($products as $p) {
            if ($this->syncSingleProduct($p)) {
                $total_synced++;
            }
        }

        $totalPages = $response['data']['total_pages'] ?? 1;
        $hasMore = $page < $totalPages;

        return ['success' => true, 'total_synced' => $total_synced, 'message' => 'Synced page ' . $page, 'has_more' => $hasMore];
    }

    public function syncSingleProduct($p, $isWebhook = false)
    {
        $sku = $p['sku'] ?? $p['custom_id'] ?? '';
        if (empty($sku)) return false;

        $pancake_id = $p['id'] ?? '';
        $name = $p['name'] ?? '';
        $firstVar = !empty($p['variations'][0]) ? $p['variations'][0] : [];

        $retailPrice = !empty($p['retail_price']) ? (int)$p['retail_price'] : (!empty($p['price']) ? (int)$p['price'] : (!empty($firstVar['retail_price']) ? (int)$firstVar['retail_price'] : (!empty($firstVar['price']) ? (int)$firstVar['price'] : 0)));
        $retailPriceAfterDiscount = isset($p['retail_price_after_discount']) ? (int)$p['retail_price_after_discount'] : (isset($firstVar['retail_price_after_discount']) ? (int)$firstVar['retail_price_after_discount'] : 0);
        if ($retailPriceAfterDiscount >= $retailPrice) $retailPriceAfterDiscount = 0;
        
        $hasStockInfo = false;
        $stock = 0;
        if (!empty($p['variations']) && is_array($p['variations'])) {
            foreach ($p['variations'] as $v) {
                if (isset($v['remain_quantity'])) {
                    $stock += (int)$v['remain_quantity'];
                    $hasStockInfo = true;
                }
            }
        } else {
            if (isset($p['remain_quantity'])) {
                $stock = (int)$p['remain_quantity'];
                $hasStockInfo = true;
            }
        }

        $description = !empty($p['note_product']) ? $p['note_product'] : (!empty($p['description']) ? $p['description'] : (!empty($p['note']) ? $p['note'] : ''));
        if ($description && strip_tags($description) === $description) {
            $description = nl2br($description);
        }

        $image = '';
        /* Đã ẩn theo yêu cầu không lấy ảnh
        if (!empty($p['images']) && is_array($p['images'])) {
            $image = $p['images'][0] ?? '';
        } elseif (!empty($p['image_url'])) {
            $image = $p['image_url'];
        } elseif (!empty($p['variations']) && is_array($p['variations'])) {
            // Pancake POS v1 trả ảnh bên trong biến thể thay vì sản phẩm gốc
            foreach ($p['variations'] as $v) {
                if (!empty($v['images']) && is_array($v['images'])) {
                    $image = $v['images'][0];
                    break;
                }
            }
        }
        */

        // 1. Đồng bộ Danh mục
        $id_loai = 0;
        if (!empty($p['category_ids_obj'])) {
            $id_loai = $this->syncCategories($p['category_ids_obj']);
        }

        // 2. Đồng bộ Sản phẩm chính
        $product = $this->db->safeFetchOne("SELECT id_code FROM #_sanpham WHERE pancake_product_id = ? OR ma_sp = ? LIMIT 1", [$pancake_id, $sku]);
        $id_code = 0;

        if ($product) {
            $id_code = $product['id_code'];
            $this->db->reset();
            $this->db->setTable('#_sanpham');
            $this->db->setWhere('id_code', $id_code);
            $updateData = [
                'gia' => $retailPrice,
                'khuyen_mai' => $retailPriceAfterDiscount,
                'cap_nhat' => time()
            ];
            if ($hasStockInfo) {
                $updateData['so_luong'] = $stock;
            }
            if ($image !== '') {
                $updateData['hinh_anh'] = $image;
            }
            if ($id_loai > 0) $updateData['id_loai'] = $id_loai;
            $this->db->update($updateData);
        } else {
            if ($isWebhook) {
                return false; // Không tự động tạo sản phẩm mới từ webhook
            }
            $this->db->reset();
            $this->db->setTable('cf_parent');
            $id_code = $this->db->insert(['ten' => $this->db->clear($name), 'type' => 4]);

            if ($id_code) {
                foreach ($this->languages as $lang) {
                    $alias = $this->makeSlug($name, $id_code);
                    $data_sp = [
                        'id_code' => $id_code,
                        'lang' => $lang,
                        'id_loai' => $id_loai,
                        'id_price' => 0, // Fix Field 'id_price' doesn't have a default value
                        'ten' => $this->db->clear($name),
                        'alias' => $alias,
                        'ma_sp' => $sku,
                        'gia' => $retailPrice,
                        'khuyen_mai' => $retailPriceAfterDiscount,
                        'so_luong' => $stock,
                        'mo_ta' => '',
                        'noi_dung' => $this->db->clear($description),
                        'title' => '',
                        'keyword' => '',
                        'des' => '',
                        'pancake_product_id' => $pancake_id,
                        'hinh_anh' => $image,
                        'ngay_dang' => time(),
                        'cap_nhat' => time(),
                        'hien_thi' => 1
                    ];
                    $this->db->reset();
                    $this->db->setTable('#_sanpham');
                    $this->db->insert($data_sp);
                }
            }
        }

        if (!$id_code) return false;

        // 3. Đồng bộ Thuộc tính
        $attrMap = [];
        if (!empty($p['product_attributes']) && is_array($p['product_attributes'])) {
            $attrMap = $this->syncAttributes($id_code, $p['product_attributes']);
            
            // Cập nhật lại cột product_attributes cho #_sanpham để admin có thể hiển thị trên giao diện Edit
            $product_attributes = [];
            foreach ($attrMap as $attr) {
                $product_attributes[] = [
                    'attr' => (string)$attr['id_code'],
                    'values' => array_map('strval', array_values($attr['values']))
                ];
            }
            $this->db->reset();
            $this->db->setTable('#_sanpham');
            $this->db->setWhere('id_code', $id_code);
            $this->db->update(['product_attributes' => json_encode($product_attributes)]);
        }

        // 4. Đồng bộ Biến thể
        if (!empty($p['variations']) && !empty($p['product_attributes'])) {
            $this->syncVariations($id_code, $p['variations'], $attrMap, $p);
        } else {
            // Xóa hết biến thể cũ nếu sản phẩm này trên CRM không có thuộc tính (là sản phẩm đơn)
            $this->db->reset();
            $this->db->setTable('#_sanpham_bienthe');
            $this->db->setWhere('id_sanpham', $id_code);
            $this->db->delete();
        }

        return true;
    }

    protected function syncCategories($pancakeCategories)
    {
        $last_id_code = 100;
        foreach ($pancakeCategories as $cat) {
            $pan_id = $cat['id'];
            $name = $cat['name'];
            $parent_pan_id = $cat['parent_id'];

            // Nếu không có parent từ Pancake, thì parent gốc trên web là 100
            $parent_id_code = 100;
            if ($parent_pan_id) {
                $pRow = $this->db->safeFetchOne("SELECT id_code FROM #_category WHERE pancake_category_id = ? LIMIT 1", [$parent_pan_id]);
                if ($pRow) $parent_id_code = $pRow['id_code'];
            }

            $row = $this->db->safeFetchOne("SELECT id_code FROM #_category WHERE pancake_category_id = ? LIMIT 1", [$pan_id]);
            if ($row) {
                $last_id_code = $row['id_code'];
                
                // Cập nhật lại tên danh mục (Cơ chế ghi đè)
                $this->db->reset();
                $this->db->setTable('cf_code');
                $this->db->setWhere('id', $last_id_code);
                $this->db->update(['ten' => $this->db->clear($name), 'id_loai' => $parent_id_code]);
                
                $this->db->reset();
                $this->db->setTable('#_category');
                $this->db->setWhere('id_code', $last_id_code);
                $this->db->update([
                    'ten' => $this->db->clear($name),
                    'parent' => $parent_id_code,
                    'cap_nhat' => time()
                ]);
            } else {
                $this->db->reset();
                $this->db->setTable('cf_code');
                $id_code = $this->db->insert([
                    'ten' => $this->db->clear($name),
                    'id_loai' => $parent_id_code,
                    'module' => 4, // Module 4 cho danh mục sản phẩm
                    'so_thu_tu' => 0,
                    'hien_thi' => 1
                ]);
                
                if ($id_code) {
                    foreach ($this->languages as $lang) {
                        $alias = $this->makeSlug($name);
                        $this->db->reset();
                        $this->db->setTable('#_category');
                        $this->db->insert([
                            'id_code' => $id_code,
                            'pancake_category_id' => $pan_id,
                            'lang' => $lang,
                            'ten' => $this->db->clear($name),
                            'alias' => $alias,
                            'parent' => $parent_id_code,
                            'module' => 4, // Module 4
                            'hien_thi' => 1,
                            'ngay_dang' => time(),
                            'cap_nhat' => time()
                        ]);
                    }
                    $last_id_code = $id_code;
                }
            }
        }
        return $last_id_code;
    }

    protected function syncAttributes($id_code, $pancakeAttributes)
    {
        $map = [];
        foreach ($pancakeAttributes as $attr) {
            $attrName = $attr['name'];
            
            // Tìm thuộc tính toàn cục (id_sanpham = 0)
            $attrRow = $this->db->safeFetchOne("SELECT id_code FROM #_thuoctinh WHERE ten = ? AND id_sanpham = 0 LIMIT 1", [$attrName]);
            $attr_id_code = 0;

            if ($attrRow) {
                $attr_id_code = $attrRow['id_code'];
            } else {
                $this->db->reset();
                $this->db->setTable('cf_parent');
                $attr_id_code = $this->db->insert(['ten' => $this->db->clear($attrName), 'type' => 0]);
                
                foreach ($this->languages as $lang) {
                    $this->db->reset();
                    $this->db->setTable('#_thuoctinh');
                    $this->db->insert([
                        'id_code' => $attr_id_code,
                        'lang' => $lang,
                        'id_sanpham' => 0, // Lưu 0 để dùng chung cho mọi sản phẩm
                        'ten' => $this->db->clear($attrName),
                        'alias' => $this->db->createAlias($this->db->clear($attrName)),
                        'loai' => 'text',
                        'sap_xep' => 0
                    ]);
                }
            }

            $lowerAttrName = mb_strtolower(trim($attrName), 'UTF-8');
            if (!isset($map[$lowerAttrName])) {
                $map[$lowerAttrName] = [
                    'id_code' => $attr_id_code,
                    'values' => []
                ];
            }

            if (!empty($attr['values']) && is_array($attr['values'])) {
                foreach ($attr['values'] as $valName) {
                    // Tìm giá trị thuộc tính toàn cục (id_sanpham = 0)
                    $valRow = $this->db->safeFetchOne("SELECT id_code FROM #_thuoctinh_giatri WHERE ten = ? AND id_thuoctinh = ? AND id_sanpham = 0 LIMIT 1", [$valName, $attr_id_code]);
                    $val_id_code = 0;
                    if ($valRow) {
                        $val_id_code = $valRow['id_code'];
                    } else {
                        $this->db->reset();
                        $this->db->setTable('cf_parent');
                        $val_id_code = $this->db->insert(['ten' => $this->db->clear($valName), 'type' => 0]);

                        foreach ($this->languages as $lang) {
                            $this->db->reset();
                            $this->db->setTable('#_thuoctinh_giatri');
                            $this->db->insert([
                                'id_code' => $val_id_code,
                                'id_thuoctinh' => $attr_id_code,
                                'id_sanpham' => 0, // Lưu 0 để dùng chung
                                'lang' => $lang,
                                'ten' => $this->db->clear($valName),
                                'alias' => $this->db->createAlias($this->db->clear($valName))
                            ]);
                        }
                    }
                    $lowerValName = mb_strtolower(trim($valName), 'UTF-8');
                    $map[$lowerAttrName]['values'][$lowerValName] = $val_id_code;
                }
            }
        }
        return $map;
    }

    protected function syncVariations($id_code, $pancakeVariations, $attrMap, $productData)
    {
        require_once __DIR__ . '/../VariantService.php';
        $variantService = new VariantService($this->db);

        $variantsToSave = [];
        
        foreach ($pancakeVariations as $var) {
            $sku = $var['custom_id'] ?? ($var['display_id'] ?? '');
            if (empty($sku)) continue;

            $retailPrice = isset($var['retail_price']) ? (int)$var['retail_price'] : (isset($var['price']) ? (int)$var['price'] : 0);
            $retailPriceAfterDiscount = isset($var['retail_price_after_discount']) ? (int)$var['retail_price_after_discount'] : 0;
            if ($retailPriceAfterDiscount >= $retailPrice) $retailPriceAfterDiscount = 0;
            $hasVarStock = false;
            $stock = 0;
            if (isset($var['remain_quantity'])) {
                $stock = (int)$var['remain_quantity'];
                $hasVarStock = true;
            }
            $image = ''; // !empty($var['images']) ? $var['images'][0] : ''; (Đã ẩn theo yêu cầu không lấy ảnh)
            
            $mappedAttributes = [];
            
            if (!empty($var['attributes']) || !empty($var['fields'])) {
                 $varAttrs = !empty($var['attributes']) ? $var['attributes'] : $var['fields'];
                 foreach ($varAttrs as $varAttr) {
                     $aName = mb_strtolower(trim($varAttr['name'] ?? ''), 'UTF-8');
                     $aVal = mb_strtolower(trim($varAttr['value'] ?? ''), 'UTF-8');
                     if (isset($attrMap[$aName]['values'][$aVal])) {
                         $attrId = $attrMap[$aName]['id_code'];
                         $valId = $attrMap[$aName]['values'][$aVal];
                         $mappedAttributes[$attrId] = $valId;
                     }
                 }
            } elseif (!empty($var['sort_variation_attributes']) && !empty($productData['product_attributes'])) {
                 $sort_str = (string) $var['sort_variation_attributes'];
                 $j = 0;
                 foreach ($productData['product_attributes'] as $attrDef) {
                     $aName = $attrDef['name'];
                     $part = substr($sort_str, $j * 3, 3);
                     if (strlen($part) == 3) {
                         $val_idx = intval(substr($part, 1, 2)) - 1;
                         if (isset($attrDef['values'][$val_idx])) {
                             $aVal = $attrDef['values'][$val_idx];
                             $aNameLower = mb_strtolower(trim($aName), 'UTF-8');
                             $aValLower = mb_strtolower(trim($aVal), 'UTF-8');
                             if (isset($attrMap[$aNameLower]['values'][$aValLower])) {
                                 $attrId = $attrMap[$aNameLower]['id_code'];
                                 $valId = $attrMap[$aNameLower]['values'][$aValLower];
                                 $mappedAttributes[$attrId] = $valId;
                             }
                         }
                     }
                     $j++;
                 }
            }

            $existing = $this->db->safeFetchOne("SELECT id, so_luong, ma_sp, hinh_anh FROM #_sanpham_bienthe WHERE pancake_variation_id = ? OR ma_sp = ? LIMIT 1", [$var['id'] ?? '', $sku]);
            $variantId = $existing ? $existing['id'] : 0;
            if (!$hasVarStock && $existing) {
                $stock = (int)$existing['so_luong'];
            }
            if ($existing) {
                $sku = $existing['ma_sp']; // Giữ nguyên SKU cũ nếu đã tồn tại
                if ($image === '') {
                    $image = $existing['hinh_anh']; // Giữ nguyên hình ảnh cũ
                }
            }

            $variantsToSave[] = [
                'id' => $variantId,
                'ma_sp' => $sku,
                'gia' => $retailPrice,
                'khuyen_mai' => $retailPriceAfterDiscount,
                'so_luong' => $stock,
                'hinh_anh' => $image,
                'attributes' => $mappedAttributes,
                'pancake_variation_id' => $var['id'] ?? ''
            ];
        }

        $variantService->saveVariants($id_code, $variantsToSave, []);
    }
}
?>
