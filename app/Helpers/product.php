<?php
/**
 * ============================================================
 *  PRODUCT HELPERS
 *  Các hàm chuyên dụng để hiển thị các thành phần của Sản phẩm.
 * ============================================================
 */

if (!function_exists('renderPriceHTML')) {
    /**
     * Render HTML cho giá (Giá mới + Giá cũ)
     */
    function renderPriceHTML($currentPrice, $oldPrice = 0) {
        if ($currentPrice <= 0) {
            return '<span class="price-contact text-danger fw-bold">' . __('Liên hệ') . '</span>';
        }

        $html = '<span class="price-new fw-bold text-danger">' . renderPrice($currentPrice) . '</span>';
        if ($oldPrice > 0 && $oldPrice > $currentPrice) {
            $html .= ' <span class="price-old text-decoration-line-through text-muted ms-2 small">' . renderPrice($oldPrice) . '</span>';
        }
        return $html;
    }
}

if (!function_exists('renderProductPrice')) {
    /**
     * Hiển thị giá sản phẩm thông minh (Xử lý Khuyến mãi, Min-Max, Biến thể)
     */
    function renderProductPrice($product) {
        if (is_array($product)) $product = (object)$product;

        // 1. Tự động tính Min/Max Price từ Variants nếu có nạp variants
        $hasVariants = !empty($product->variants);
        $minPrice = 0;
        $maxPrice = 0;

        if ($hasVariants) {
            $prices = [];
            foreach ($product->variants as $v) {
                $v = is_array($v) ? (object)$v : $v;
                // Lấy giá bán hiện tại của biến thể (nhỏ nhất giữa gia và khuyen_mai)
                $v_gia = (float)($v->gia ?? 0);
                $v_km = (float)($v->khuyen_mai ?? 0);
                
                if ($v_km > 0) {
                    $prices[] = min($v_gia, $v_km);
                } elseif ($v_gia > 0) {
                    $prices[] = $v_gia;
                }
            }
            if (!empty($prices)) {
                $minPrice = min($prices);
                $maxPrice = max($prices);
            }
        }

        // 2. Nếu có biến thể và có khoảng giá
        if ($minPrice > 0) {
            if ($minPrice != $maxPrice) {
                return '<div class="price-range">' . 
                            '<span class="price-new fw-bold text-danger">' . renderPrice($minPrice) . '</span>' .
                            ' - ' .
                            '<span class="price-new fw-bold text-danger">' . renderPrice($maxPrice) . '</span>' .
                       '</div>';
            } else {
                // Nếu tất cả biến thể cùng giá, lấy giá đó so với giá gốc của SP (nếu có)
                $sp_gia = (float)($product->gia ?? 0);
                $sp_km = (float)($product->khuyen_mai ?? 0);
                $oldPrice = max($sp_gia, $sp_km, $minPrice);
                return renderPriceHTML($minPrice, ($oldPrice > $minPrice ? $oldPrice : 0));
            }
        }

        // 3. Sản phẩm thường không biến thể
        $sp_gia = (float)($product->gia ?? 0);
        $sp_km = (float)($product->khuyen_mai ?? 0);

        if ($sp_km > 0 && $sp_gia > 0) {
            $currentPrice = min($sp_gia, $sp_km);
            $oldPrice = max($sp_gia, $sp_km);
        } else {
            $currentPrice = ($sp_gia > 0) ? $sp_gia : $sp_km;
            $oldPrice = 0;
        }

        return renderPriceHTML($currentPrice, $oldPrice);
    }
}

if (!function_exists('getProductRating')) {
    function getProductRating($product_id) {
        // TODO: Implement actual rating logic
        return ['avg' => 0, 'total' => 0];
    }
}

if (!function_exists('renderProductStars')) {
    /**
     * Hiển thị icon sao đánh giá
     */
    function renderProductStars($product_id) {
        $rating = getProductRating($product_id); 
        if ($rating['total'] == 0) return '';

        $avg = (float)$rating['avg'];
        $html = '<div class="product-rating mb-1" title="' . $avg . ' stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $avg) {
                $html .= '<i class="fa-solid fa-star text-warning small"></i>';
            } elseif ($i - 0.5 <= $avg) {
                $html .= '<i class="fa-solid fa-star-half-stroke text-warning small"></i>';
            } else {
                $html .= '<i class="fa-regular fa-star text-muted small"></i>';
            }
        }
        $html .= ' <small class="text-muted" style="font-size: 11px;">(' . $rating['total'] . ')</small>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('getProductBadge')) {
    /**
     * Lấy các Badge (Mới, Hot, Sale)
     */
    function getProductBadge($product) {
        if (is_array($product)) $product = (object)$product;
        $html = '';

        if (!empty($product->sp_moi)) $html .= '<span class="badge-product badge-new">'.__('Mới').'</span>';
        if (!empty($product->sp_hot) || !empty($product->noi_bat)) $html .= '<span class="badge-product badge-hot">Hot</span>';
        
        // Tính % giảm giá
        $sp_gia = (float)($product->gia ?? 0);
        $sp_km = (float)($product->khuyen_mai ?? 0);
        if ($sp_gia > 0 && $sp_km > 0 && $sp_km != $sp_gia) {
            $old = max($sp_gia, $sp_km);
            $new = min($sp_gia, $sp_km);
            $percent = round((($old - $new) / $old) * 100);
            if ($percent > 0) {
                $html .= '<span class="badge-product badge-sale">-' . $percent . '%</span>';
            }
        }

        return $html;
    }
}

if (!function_exists('renderProductCategory')) {
    /**
     * Hiển thị tên danh mục cha kèm link
     */
    function renderProductCategory($product) {
        if (is_array($product)) $product = (object)$product;
        
        if (!empty($product->category)) {
            $cat = is_array($product->category) ? (object)$product->category : $product->category;
            return '<div class="box-category mb-1">' . 
                        '<a href="' . url($cat->slug) . '" class="text-muted text-decoration-none" style="font-size: 12px; text-transform: uppercase; font-weight: 600;">' . 
                            e($cat->ten) . 
                        '</a>' . 
                   '</div>';
        }
        
        return '';
    }
}

if (!function_exists('buildVariantAttributes')) {
    /**
     * Nhóm các thuộc tính biến thể để hiển thị UI chọn lựa
     */
    function buildVariantAttributes($variants) {
        $variant_attributes = [];
        
        foreach ($variants as $variant) {
            $variant = is_array($variant) ? (object)$variant : $variant;
            // Thuộc tính biến thể được lưu trong relations 'thuoctinh'
            $thuoctinh = $variant->thuoctinh ?? null;
            if (!empty($thuoctinh) && is_iterable($thuoctinh)) {
                foreach ($thuoctinh as $tt) {
                    // Dữ liệu từ các relation Eager Loaded
                    $attrData = !empty($tt->attribute) ? ((is_array($tt->attribute) ? (object)$tt->attribute : clone $tt->attribute)) : null;
                    $valData  = !empty($tt->value) ? ((is_array($tt->value) ? (object)$tt->value : clone $tt->value)) : null;
                    
                    if ($attrData && $valData) {
                        // Bỏ khối gán $variant->parsed_attributes[] vì JS đã map trực tiếp qua variant.thuoctinh
                        // Gom nhóm để render UI chọn lựa
                        $attr_id = $attrData->id_code;
                        if (!isset($variant_attributes[$attr_id])) {
                            $variant_attributes[$attr_id] = [
                                'id' => $attr_id,
                                'ten' => $attrData->ten,
                                'loai' => $attrData->loai,
                                'values' => []
                            ];
                        }
                        
                        $found = false;
                        foreach ($variant_attributes[$attr_id]['values'] as $val) {
                            if ($val['id'] == $valData->id_code) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $variant_attributes[$attr_id]['values'][] = [
                                'id' => $valData->id_code,
                                'ten' => $valData->ten,
                                'gia_tri' => $valData->gia_tri
                            ];
                        }
                    }
                }
            }
        }
        
        return $variant_attributes;
    }
}
