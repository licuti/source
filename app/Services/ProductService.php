<?php
namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantAttributeModel;
use App\Models\AttributeModel;
use App\Models\AttributeValueModel;

class ProductService {

    /**
     * Trích xuất dữ liệu cho một ngôn ngữ cụ thể từ request
     */
    private function extractLangData(array $inputData, string $lang, int $categoryId, int $status, ?string $createdAt, bool $isUpdate = false): array {
        $title = $inputData['title'][$lang] ?? '';
        $slug = empty($inputData['slug'][$lang]) ? str_slug($title) : $inputData['slug'][$lang];

        // Nếu là thêm mới (!isUpdate) và seo_title rỗng thì lấy theo title
        // Nếu là cập nhật (isUpdate) thì có gì lưu nấy (để trống thì lưu rỗng)
        $seoTitle = $inputData['seo_title'][$lang] ?? '';
        if (!$isUpdate && empty($seoTitle)) {
            $seoTitle = $title;
        }

        $data = [
            'category_id'       => $categoryId,
            'lang'              => $lang,
            'gia_flash_sale'    => (int)($inputData['gia_flash_sale'] ?? 0),
            'flash_sale'        => isset($inputData['flash_sale']) ? 1 : 0,
            'flash_sale_start'  => !empty($inputData['flash_sale_start']) ? date('Y-m-d H:i:s', strtotime($inputData['flash_sale_start'])) : null,
            'flash_sale_end'    => !empty($inputData['flash_sale_end']) ? date('Y-m-d H:i:s', strtotime($inputData['flash_sale_end'])) : null,
            'low_stock_amount'  => (int)($inputData['low_stock_amount'] ?? 5),
            'title'             => $title,
            'slug'              => $slug,
            'sku'               => $inputData['sku'] ?? '',
            'barcode'           => $inputData['barcode'] ?? '',
            'description'       => $inputData['description'][$lang] ?? '',
            'content'           => $inputData['content'][$lang] ?? '',
            'specifications'    => $inputData['specifications'][$lang] ?? '',
            'thumbnail'         => $inputData['thumbnail'] ?? '',
            'gallery'           => isset($inputData['gallery']) ? json_encode($inputData['gallery'], JSON_UNESCAPED_UNICODE) : null,
            'price'             => (int)($inputData['price'] ?? 0),
            'promotional_price' => (int)($inputData['promotional_price'] ?? 0),
            'cost_price'        => (int)($inputData['cost_price'] ?? 0),
            'stock_quantity'    => (int)($inputData['stock_quantity'] ?? 0),
            'stock_status'      => $inputData['stock_status'] ?? 'in_stock',
            'weight'            => (float)($inputData['weight'] ?? 0),
            'length'            => (float)($inputData['length'] ?? 0),
            'width'             => (float)($inputData['width'] ?? 0),
            'height'            => (float)($inputData['height'] ?? 0),
            'unit'              => is_array($inputData['unit'] ?? '') ? ($inputData['unit'][$lang] ?? '') : ($inputData['unit'] ?? ''),
            'product_type'      => $inputData['product_type'] ?? 'simple',
            'brand_id'          => (int)($inputData['brand_id'] ?? 0),
            'seo_title'         => $seoTitle,
            'seo_description'   => $inputData['seo_description'][$lang] ?? '',
            'seo_keyword'       => $inputData['keyword'][$lang] ?? '',
            'seo_head'          => $inputData['seo_head'][$lang] ?? '',
            'seo_body'          => $inputData['seo_body'][$lang] ?? '',
            'noindex'           => isset($inputData['noindex'][$lang]) ? 1 : 0,
            'nofollow'          => isset($inputData['nofollow'][$lang]) ? 1 : 0,
            'status'            => $status,
            'is_featured'       => isset($inputData['is_featured']) ? 1 : 0,
            'is_new'            => isset($inputData['is_new']) ? 1 : 0,
            'is_hot'            => isset($inputData['is_hot']) ? 1 : 0,
            'is_sale'           => isset($inputData['is_sale']) ? 1 : 0,
            'product_attributes'=> isset($inputData['product_attributes']) ? json_encode($inputData['product_attributes'], JSON_UNESCAPED_UNICODE) : null,
        ];

        if ($createdAt) {
            $data['created_at'] = $createdAt;
        }

        return $data;
    }

    /**
     * Lưu mới hoặc cập nhật sản phẩm đa ngôn ngữ
     */
    public function saveProduct(array $inputData, array $langs, int $userId, ?int $idCode = null) {
        $langs = array_values($langs);
        $categoryId = (int)($inputData['category_id'] ?? 0);
        
        $statusVal = (!empty($inputData['status']) && in_array($inputData['status'], [1, '1', 'publish', 'on', 'true', true], true)) ? 1 : 0;

        $createdAtInput = $inputData['created_at'] ?? null;
        $createdAt = $createdAtInput ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : date('Y-m-d H:i:s');
        $now = date('Y-m-d H:i:s');

        $firstLang = $langs[0]['code'] ?? 'vi';

        if (!$idCode) { // INSERT MỚI
            $firstLangData = $this->extractLangData($inputData, $firstLang, $categoryId, $statusVal, $createdAt, false);
            $firstLangData['id_code'] = 0;
            
            // Handle DB fields if using standard timestamp vs UNIX
            $firstLangData['updated_at'] = $now;

            $insertedId = ProductModel::insertGetId($firstLangData);
            if (!$insertedId) return false;

            // Update id_code cho bản ghi gốc
            ProductModel::query()->where('id', $insertedId)->update(['id_code' => $insertedId]);
            $idCode = $insertedId;

            // Thêm các ngôn ngữ còn lại
            foreach ($langs as $index => $l) {
                if ($index === 0) continue;
                $langData = $this->extractLangData($inputData, $l['code'], $categoryId, $statusVal, $createdAt, false);
                $langData['id_code'] = $insertedId;
                $langData['updated_at'] = $now;
                ProductModel::insert($langData);
            }

        } else { // CẬP NHẬT
            foreach ($langs as $l) {
                $c = $l['code'];
                $query = ProductModel::query();
                $query->use_lang = false;
                $exists = $query->where('id_code', $idCode)->where('lang', $c)->first();
                
                $data = $this->extractLangData($inputData, $c, $categoryId, $statusVal, $createdAt, true);
                $data['updated_at'] = $now;

                if ($exists) {
                    $updQuery = ProductModel::query();
                    $updQuery->use_lang = false;
                    $updQuery->where('id', $exists->id)->update($data);
                } else {
                    $data['id_code'] = $idCode;
                    ProductModel::insert($data);
                }
            }
        }

        // Handle Variants
        if (isset($inputData['variants']) && is_array($inputData['variants'])) {
            $this->saveVariants($idCode, $inputData['variants']);
        } else {
            $this->deleteVariants($idCode);
        }

        // Đồng bộ tồn kho
        \App\Services\InventoryService::syncProductStock($idCode);

        return $idCode;
    }

    /**
     * Lưu biến thể sản phẩm
     */
    private function saveVariants(int $productId, array $variants) {
        $old_variants = ProductVariantModel::query()->where('product_id', $productId)->get();
        $old_ids = array_column((array)$old_variants, 'id');
        
        $submitted_ids = [];
        $updated_ids = [];
        $attributes_to_insert = [];
        
        foreach ($variants as $variant) {
            $variant_id = isset($variant['id']) ? (int)$variant['id'] : 0;
            
            $data_bienthe = [
                'product_id'        => $productId,
                'sku'               => trim($variant['sku'] ?? ''),
                'barcode'           => trim($variant['barcode'] ?? ''),
                'price'             => (int)($variant['price'] ?? 0),
                'promotional_price' => (int)($variant['promotional_price'] ?? 0),
                'gia_flash_sale'    => (int)($variant['gia_flash_sale'] ?? 0),
                'stock_quantity'    => (int)($variant['stock_quantity'] ?? 0),
                'weight'            => (float)($variant['weight'] ?? 0),
                'image'             => trim($variant['image'] ?? ''),
                'status'            => 1,
            ];

            if ($variant_id > 0 && in_array($variant_id, $old_ids)) {
                ProductVariantModel::query()->where('id', $variant_id)->update($data_bienthe);
                $id_bienthe = $variant_id;
                $submitted_ids[] = $variant_id;
                $updated_ids[] = $variant_id;
            } else {
                $id_bienthe = ProductVariantModel::insertGetId($data_bienthe);
                if ($id_bienthe) {
                    $submitted_ids[] = $id_bienthe;
                }
            }

            // Pivot attributes
            if ($id_bienthe && !empty($variant['attributes'])) {
                foreach ($variant['attributes'] as $attr_id => $attr_val_id) {
                    if (!empty($attr_val_id)) {
                        $attributes_to_insert[] = [
                            'variant_id' => $id_bienthe,
                            'attribute_id' => (int)$attr_id,
                            'attribute_value_id' => (int)$attr_val_id
                        ];
                    }
                }
            }
        }

        // Delete old pivot entries for updated variants
        if (!empty($updated_ids)) {
            ProductVariantAttributeModel::query()->whereIn('variant_id', $updated_ids)->delete();
        }

        // Insert new pivot entries
        foreach ($attributes_to_insert as $pivot) {
            ProductVariantAttributeModel::insert($pivot);
        }

        // Delete removed variants
        $ids_to_delete = array_diff($old_ids, $submitted_ids);
        if (!empty($ids_to_delete)) {
            ProductVariantAttributeModel::query()->whereIn('variant_id', $ids_to_delete)->delete();
            ProductVariantModel::query()->whereIn('id', $ids_to_delete)->delete();
        }
    }

    private function deleteVariants(int $productId) {
        $variants = ProductVariantModel::query()->where('product_id', $productId)->get();
        if (!empty($variants)) {
            $variantIds = array_column((array)$variants, 'id');
            ProductVariantAttributeModel::query()->whereIn('variant_id', $variantIds)->delete();
            ProductVariantModel::query()->where('product_id', $productId)->delete();
        }
    }

    /**
     * Xóa sản phẩm và toàn bộ bản dịch, biến thể
     */
    public function deleteProduct(int $idCode) {
        if ($idCode > 0) {
            $this->deleteVariants($idCode);
            $query = ProductModel::query();
            $query->use_lang = false;
            return $query->where('id_code', $idCode)->delete();
        }
        return false;
    }

    /**
     * Lấy và chuyển hóa dữ liệu sản phẩm (gom đa ngôn ngữ) để hiển thị lên Form Edit
     */
    public function getProductForEdit(int $idCode): ?array {
        $query = ProductModel::query();
        $query->use_lang = false;
        $translations = $query->where('id_code', $idCode)->get();
        if (count($translations) == 0) return null;

        $firstPost = $translations[0];
        
        $item = [
            'id'                => $idCode, 
            'category_id'       => $firstPost->category_id,
            'brand_id'          => $firstPost->brand_id,
            'product_type'      => $firstPost->product_type,
            'sku'               => $firstPost->sku,
            'barcode'           => $firstPost->barcode,
            'thumbnail'         => $firstPost->thumbnail,
            'gallery'           => json_decode($firstPost->gallery ?? '[]', true),
            'price'             => $firstPost->price,
            'promotional_price' => $firstPost->promotional_price,
            'gia_flash_sale'    => $firstPost->gia_flash_sale,
            'flash_sale'        => $firstPost->flash_sale,
            'flash_sale_start'  => $firstPost->flash_sale_start,
            'flash_sale_end'    => $firstPost->flash_sale_end,
            'cost_price'        => $firstPost->cost_price,
            'stock_quantity'    => $firstPost->stock_quantity,
            'stock_status'      => $firstPost->stock_status,
            'low_stock_amount'  => $firstPost->low_stock_amount ?? 5,
            'weight'            => $firstPost->weight,
            'length'            => $firstPost->length,
            'width'             => $firstPost->width,
            'height'            => $firstPost->height,
            'is_featured'       => $firstPost->is_featured,
            'is_new'            => $firstPost->is_new,
            'is_hot'            => $firstPost->is_hot,
            'is_sale'           => $firstPost->is_sale,
            'status'            => $firstPost->status,
            'product_attributes'=> json_decode($firstPost->product_attributes ?? '[]', true),
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["title"][$lang] = $t->title;
            $item["slug"][$lang] = $t->slug;
            $item["description"][$lang] = $t->description;
            $item["content"][$lang] = $t->content;
            $item["specifications"][$lang] = $t->specifications;
            $item["unit"][$lang] = $t->unit;
            $item["seo_title"][$lang] = $t->seo_title;
            $item["seo_description"][$lang] = $t->seo_description;
            $item["keyword"][$lang] = $t->seo_keyword;
            $item["seo_head"][$lang] = $t->seo_head;
            $item["seo_body"][$lang] = $t->seo_body;
            $item["noindex"][$lang] = $t->noindex;
            $item["nofollow"][$lang] = $t->nofollow;
            if (empty($item["thumbnail"]) && !empty($t->thumbnail)) {
                $item["thumbnail"] = $t->thumbnail;
            }
        }

        // Load Variants
        $variants = ProductVariantModel::query()->where('product_id', $idCode)->get();
        ProductVariantModel::loadNestedAttributes($variants);
        
        $item['variants'] = [];
        foreach ($variants as $v) {
            $attrPairs = [];
            if (!empty($v->getRelation('thuoctinh'))) {
                foreach ($v->getRelation('thuoctinh') as $pivot) {
                    $attrPairs[$pivot->attribute_id] = $pivot->attribute_value_id;
                }
            }
            $item['variants'][] = [
                'id' => $v->id,
                'sku' => $v->sku,
                'barcode' => $v->barcode,
                'price' => $v->price,
                'promotional_price' => $v->promotional_price,
                'stock_quantity' => $v->stock_quantity,
                'weight' => $v->weight,
                'image' => $v->image,
                'status' => $v->status,
                'attributes' => $attrPairs
            ];
        }
        
        return $item;
    }
}
