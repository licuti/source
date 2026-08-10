<?php

namespace App\Services;

use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantAttributeModel;
use App\Core\Database\DB;
use App\Services\InventoryService;

class ProductService
{
    private function extractCommonData(array $input, int $categoryId, int $status, ?string $createdAt): array
    {
        $data = [
            'category_id'        => $categoryId,
            'status'             => $status,
            'price'              => (int)($input['price'] ?? 0),
            'promotional_price'  => (int)($input['promotional_price'] ?? 0),
            'cost_price'         => (int)($input['cost_price'] ?? 0),
            'flash_sale_price'   => (int)($input['flash_sale_price'] ?? 0),
            'stock_quantity'     => (int)($input['stock_quantity'] ?? 0),
            'low_stock_amount'   => (int)($input['low_stock_amount'] ?? 0),
            'tax_class_id'       => (int)($input['tax_class_id'] ?? 0),
            'brand_id'           => (int)($input['brand_id'] ?? 0),
            'weight'             => (float)($input['weight'] ?? 0),
            'length'             => (float)($input['length'] ?? 0),
            'width'              => (float)($input['width'] ?? 0),
            'height'             => (float)($input['height'] ?? 0),
            'sku'                => $input['sku'] ?? '',
            'barcode'            => $input['barcode'] ?? '',
            'thumbnail'          => $input['thumbnail'] ?? '',
            'stock_status'       => $input['stock_status'] ?? '',
            'product_type'       => $input['product_type'] ?? '',
            'is_featured'        => isset($input['is_featured']) ? 1 : 0,
            'is_new'             => isset($input['is_new']) ? 1 : 0,
            'is_hot'             => isset($input['is_hot']) ? 1 : 0,
            'is_sale'            => isset($input['is_sale']) ? 1 : 0,
            'flash_sale'         => isset($input['flash_sale']) ? 1 : 0,
            'gallery'            => isset($input['gallery']) ? json_encode($input['gallery'], JSON_UNESCAPED_UNICODE) : null,
            'product_attributes' => isset($input['product_attributes']) ? json_encode($input['product_attributes'], JSON_UNESCAPED_UNICODE) : null,
            'flash_sale_start'   => !empty($input['flash_sale_start']) ? date('Y-m-d H:i:s', strtotime($input['flash_sale_start'])) : null,
            'flash_sale_end'     => !empty($input['flash_sale_end']) ? date('Y-m-d H:i:s', strtotime($input['flash_sale_end'])) : null,
        ];

        if ($createdAt) {
            $data['created_at'] = $createdAt;
        }

        return $data;
    }

    private function extractTranslationData(array $input, string $lang, bool $isUpdate = false): array
    {
        $title    = $input['title'][$lang] ?? '';
        $slug     = empty($input['slug'][$lang]) ? str_slug($title) : $input['slug'][$lang];
        $seoTitle = $input['seo_title'][$lang] ?? '';

        if (!$isUpdate && empty($seoTitle)) {
            $seoTitle = $title;
        }

        return [
            'lang'            => $lang,
            'title'           => $title,
            'slug'            => $slug,
            'description'     => $input['description'][$lang] ?? '',
            'content'         => $input['content'][$lang] ?? '',
            'specifications'  => $input['specifications'][$lang] ?? '',
            'unit'            => is_array($input['unit'] ?? '') ? ($input['unit'][$lang] ?? '') : ($input['unit'] ?? ''),
            'seo_title'       => $seoTitle,
            'seo_description' => $input['seo_description'][$lang] ?? '',
            'seo_keyword'     => $input['keyword'][$lang] ?? '',
            'seo_head'        => $input['seo_head'][$lang] ?? '',
            'seo_body'        => $input['seo_body'][$lang] ?? '',
            'seo_schema'      => $input['seo_schema'][$lang] ?? '',
            'seo_canonical'   => $input['seo_canonical'][$lang] ?? '',
            'noindex'         => isset($input['noindex'][$lang]) ? 1 : 0,
            'nofollow'        => isset($input['nofollow'][$lang]) ? 1 : 0,
        ];
    }

    private function syncCategories(int $idCode, array $categoryIds)
    {
        $pivotTable   = ProductModel::getPrefix() . 'product_category';
        $productTable = ProductModel::tableName();

        $rows       = DB::select("SELECT id FROM {$productTable} WHERE id_code = ?", [$idCode]);
        $productIds = array_column($rows, 'id');
        if (empty($productIds)) {
            return;
        }

        $idList = implode(',', array_map('intval', $productIds));
        DB::statement("DELETE FROM {$pivotTable} WHERE product_id IN ($idList)");

        $insertData = [];
        foreach ($productIds as $pId) {
            foreach ($categoryIds as $catId) {
                if ((int)$catId > 0) {
                    $insertData[] = "(" . (int)$pId . "," . (int)$catId . ")";
                }
            }
        }

        if (!empty($insertData)) {
            DB::statement("INSERT INTO {$pivotTable} (product_id, category_id) VALUES " . implode(',', $insertData));
        }
    }

    private function syncVariantAttributes(int $variantId, array $attributes)
    {
        ProductVariantAttributeModel::where('variant_id', $variantId)->delete();

        $insertData = [];
        foreach ($attributes as $attrId => $attrValId) {
            if (!empty($attrValId)) {
                $insertData[] = [
                    'variant_id'         => $variantId,
                    'attribute_id'       => (int)$attrId,
                    'attribute_value_id' => (int)$attrValId,
                ];
            }
        }
        foreach ($insertData as $row) {
            ProductVariantAttributeModel::insert($row);
        }
    }

    public function saveProduct(array $inputData, array $langs, int $userId, ?int $idCode = null)
    {
        return DB::transaction(function () use ($inputData, $langs, $userId, $idCode) {
            $langs       = array_values($langs);
            $categoryIds = array_filter((array)($inputData['category_ids'] ?? []), fn($v) => (int)$v > 0);
            $categoryId  = !empty($categoryIds) ? (int)$categoryIds[0] : 0;
            $statusVal   = (!empty($inputData['status']) && in_array($inputData['status'], [1, '1', 'publish', 'on', 'true', true], true)) ? 1 : 0;

            $createdAtInput = $inputData['created_at'] ?? null;
            $createdAt      = $createdAtInput ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : date('Y-m-d H:i:s');
            $now            = date('Y-m-d H:i:s');

            $commonData               = $this->extractCommonData($inputData, $categoryId, $statusVal, $createdAt);
            $commonData['updated_at'] = $now;

            if (!$idCode) {
                $firstLang     = $langs[0]['code'] ?? 'vi';
                $firstLangData = array_merge($commonData, $this->extractTranslationData($inputData, $firstLang));
                $firstLangData['id_code'] = 0;

                $idCode = ProductModel::insertGetId($firstLangData);
                if (!$idCode) {
                    return false;
                }

                ProductModel::where('id', $idCode)->update(['id_code' => $idCode]);

                foreach (array_slice($langs, 1) as $l) {
                    $row            = array_merge($commonData, $this->extractTranslationData($inputData, $l['code']));
                    $row['id_code'] = $idCode;
                    ProductModel::insertGetId($row);
                }
            } else {
                foreach ($langs as $l) {
                    $exists   = ProductModel::withoutGlobalScope('lang')->where('id_code', $idCode)->where('lang', $l['code'])->first();
                    $langData = array_merge($commonData, $this->extractTranslationData($inputData, $l['code'], true));

                    if ($exists) {
                        ProductModel::withoutGlobalScope('lang')->where('id', $exists->id)->update($langData);
                    } else {
                        $langData['id_code'] = $idCode;
                        ProductModel::insertGetId($langData);
                    }
                }
            }

            $this->syncCategories($idCode, $categoryIds);

            if (isset($inputData['variants']) && is_array($inputData['variants'])) {
                $this->saveVariants($idCode, $inputData['variants']);
            } else {
                $this->deleteVariants($idCode);
            }

            InventoryService::syncProductStock($idCode);
            return $idCode;
        });
    }

    public function updateProductStatus(int $idCode, string $field): bool
    {
        return DB::transaction(function () use ($idCode, $field) {
            $product = ProductModel::withoutGlobalScope('lang')->where('id_code', $idCode)->first();
            if (!$product) {
                return false;
            }

            $newVal = ($product->{$field} == 1) ? 0 : 1;
            ProductModel::withoutGlobalScope('lang')->where('id_code', $idCode)->update([$field => $newVal]);
            return true;
        });
    }

    public function deleteProduct(int $idCode)
    {
        if ($idCode <= 0) {
            return false;
        }

        return DB::transaction(function () use ($idCode) {
            $this->deleteVariants($idCode);
            $pivotTable   = ProductModel::getPrefix() . 'product_category';
            $productTable = ProductModel::tableName();
            DB::statement("DELETE FROM {$pivotTable} WHERE product_id IN (SELECT id FROM {$productTable} WHERE id_code = ?)", [$idCode]);
            return ProductModel::withoutGlobalScope('lang')->where('id_code', $idCode)->delete();
        });
    }

    public function deleteProducts(array $idCodes)
    {
        if (empty($idCodes)) {
            return false;
        }

        return DB::transaction(function () use ($idCodes) {
            $variants = ProductVariantModel::whereIn('product_id', $idCodes)->get();
            if (!empty($variants)) {
                $variantIds = array_column((array)$variants, 'id');
                ProductVariantAttributeModel::whereIn('variant_id', $variantIds)->delete();
                ProductVariantModel::whereIn('product_id', $idCodes)->delete();
            }

            $pivotTable   = ProductModel::getPrefix() . 'product_category';
            $productTable = ProductModel::tableName();
            $idList       = implode(',', array_map('intval', $idCodes));
            DB::statement("DELETE FROM {$pivotTable} WHERE product_id IN (SELECT id FROM {$productTable} WHERE id_code IN ($idList))");

            return ProductModel::withoutGlobalScope('lang')->whereIn('id_code', $idCodes)->delete();
        });
    }

    public function getProductForEdit(int $idCode): ?array
    {
        $translations = ProductModel::withoutGlobalScope('lang')->where('id_code', $idCode)->get();
        if (empty($translations)) {
            return null;
        }

        $p = $translations[0];

        $item = [
            'id'                 => $idCode,
            'category_ids'       => $p->getCategoryIds(),
            'category_id'        => $p->category_id,
            'brand_id'           => $p->brand_id,
            'tax_class_id'       => $p->tax_class_id,
            'product_type'       => $p->product_type,
            'status'             => $p->status,
            'stock_status'       => $p->stock_status,
            'price'              => $p->price,
            'promotional_price'  => $p->promotional_price,
            'cost_price'         => $p->cost_price,
            'flash_sale_price'   => $p->flash_sale_price,
            'stock_quantity'     => $p->stock_quantity,
            'low_stock_amount'   => $p->low_stock_amount,
            'weight'             => $p->weight,
            'length'             => $p->length,
            'width'              => $p->width,
            'height'             => $p->height,
            'sku'                => $p->sku,
            'barcode'            => $p->barcode,
            'thumbnail'          => $p->thumbnail,
            'is_featured'        => $p->is_featured,
            'is_new'             => $p->is_new,
            'is_hot'             => $p->is_hot,
            'is_sale'            => $p->is_sale,
            'flash_sale'         => $p->flash_sale,
            'flash_sale_start'   => $p->flash_sale_start,
            'flash_sale_end'     => $p->flash_sale_end,
            'gallery'            => json_decode($p->gallery ?? '[]', true),
            'product_attributes' => json_decode($p->product_attributes ?? '[]', true),
        ];

        foreach ($translations as $t) {
            $lang                           = $t->lang;
            $item['title'][$lang]           = $t->title;
            $item['slug'][$lang]            = $t->slug;
            $item['description'][$lang]     = $t->description;
            $item['content'][$lang]         = $t->content;
            $item['specifications'][$lang]  = $t->specifications;
            $item['unit'][$lang]            = $t->unit;
            $item['seo_title'][$lang]       = $t->seo_title;
            $item['seo_description'][$lang] = $t->seo_description;
            $item['keyword'][$lang]         = $t->seo_keyword;
            $item['seo_head'][$lang]        = $t->seo_head;
            $item['seo_body'][$lang]        = $t->seo_body;
            $item['seo_schema'][$lang]      = $t->seo_schema;
            $item['seo_canonical'][$lang]   = $t->seo_canonical;
            $item['noindex'][$lang]         = $t->noindex;
            $item['nofollow'][$lang]        = $t->nofollow;
        }

        $item['variants'] = $this->getVariantsForEdit($idCode);
        return $item;
    }

    private function saveVariants(int $productId, array $variants)
    {
        $oldIds       = array_column((array)ProductVariantModel::where('product_id', $productId)->get(), 'id');
        $submittedIds = [];

        foreach ($variants as $variant) {
            $variantId = (int)($variant['id'] ?? 0);
            $data = [
                'product_id'        => $productId,
                'sku'               => trim($variant['sku'] ?? ''),
                'barcode'           => trim($variant['barcode'] ?? ''),
                'price'             => (int)($variant['price'] ?? 0),
                'promotional_price' => (int)($variant['promotional_price'] ?? 0),
                'flash_sale_price'  => (int)($variant['flash_sale_price'] ?? 0),
                'stock_quantity'    => (int)($variant['stock_quantity'] ?? 0),
                'weight'            => (float)($variant['weight'] ?? 0),
                'image'             => trim($variant['image'] ?? ''),
                'status'            => 1,
            ];

            if ($variantId > 0 && in_array($variantId, $oldIds)) {
                ProductVariantModel::where('id', $variantId)->update($data);
                $savedId = $variantId;
            } else {
                $savedId = ProductVariantModel::insertGetId($data);
            }

            if ($savedId) {
                $submittedIds[] = $savedId;
                if (!empty($variant['attributes'])) {
                    $this->syncVariantAttributes($savedId, $variant['attributes']);
                }
            }
        }

        $idsToDelete = array_diff($oldIds, $submittedIds);
        if (!empty($idsToDelete)) {
            ProductVariantAttributeModel::whereIn('variant_id', $idsToDelete)->delete();
            ProductVariantModel::whereIn('id', $idsToDelete)->delete();
        }
    }

    private function deleteVariants(int $productId)
    {
        $variantIds = array_column((array)ProductVariantModel::where('product_id', $productId)->get(), 'id');
        if (!empty($variantIds)) {
            ProductVariantAttributeModel::whereIn('variant_id', $variantIds)->delete();
            ProductVariantModel::where('product_id', $productId)->delete();
        }
    }

    private function getVariantsForEdit(int $productId): array
    {
        $variants = ProductVariantModel::with([
            'thuoctinh' => function ($q) {
                $q->with([
                    'attribute' => fn($q2) => $q2->where('lang', LANG),
                    'value'     => fn($q2) => $q2->where('lang', LANG),
                ]);
            }
        ])->where('product_id', $productId)->get();

        return array_map(function ($v) {
            $attrPairs = [];
            foreach ($v->getRelation('thuoctinh') ?? [] as $pivot) {
                $attrPairs[$pivot->attribute_id] = $pivot->attribute_value_id;
            }
            return [
                'id'                => $v->id,
                'sku'               => $v->sku,
                'barcode'           => $v->barcode,
                'price'             => $v->price,
                'promotional_price' => $v->promotional_price,
                'stock_quantity'    => $v->stock_quantity,
                'weight'            => $v->weight,
                'image'             => $v->image,
                'status'            => $v->status,
                'attributes'        => $attrPairs,
            ];
        }, (array)$variants);
    }
}
