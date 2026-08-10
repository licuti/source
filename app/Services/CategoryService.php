<?php
namespace App\Services;

use App\Models\CategoryModel;
use App\Core\Database\DB;

class CategoryService {

    /**
     * Tách Data Category chuẩn hóa (DRY)
     */
    private function extractCategoryData(array $validatedData, int $parentId): array {
        return [
            'image'       => $validatedData['image'] ?? '',
            'banner'      => $validatedData['banner'] ?? '',
            'parent_id'   => $parentId,
            'module'      => $validatedData['module'] ?? 0,
            'sort_order'  => (int)($validatedData['sort_order'] ?? 0),
            'status'      => isset($validatedData['status']) ? 1 : 0,
            'is_featured' => isset($validatedData['is_featured']) ? 1 : 0,
        ];
    }

    /**
     * Tách Data Dịch thuật chuẩn hóa (DRY)
     */
    private function extractTranslationData(array $validatedData, int $idCode, string $lang): array {
        return [
            'id_code'         => $idCode,
            'lang'            => $lang,
            'title'           => $validatedData['title'] ?? '',
            'slug'            => empty($validatedData['slug']) ? str_slug($validatedData['title'] ?? '') : $validatedData['slug'],
            'description'     => $validatedData['description'] ?? '',
            'content'         => $validatedData['content'] ?? '',
            'seo_title'       => $validatedData['seo_title'] ?? '',
            'keyword'         => $validatedData['keyword'] ?? '',
            'seo_description' => $validatedData['seo_description'] ?? '',
            'seo_head'        => $validatedData['seo_head'] ?? '',
            'seo_body'        => $validatedData['seo_body'] ?? '',
            'seo_schema'      => $validatedData['seo_schema'] ?? '',
            'seo_canonical'   => $validatedData['seo_canonical'] ?? '',
        ];
    }

    /**
     * Lưu mới hoặc thêm bản dịch Danh mục
     */
    public function saveCategory(array $validatedData, string $primaryLang): int {
        $lang     = $validatedData['lang'] ?? $primaryLang;
        $sourceId = (int)($validatedData['id'] ?? 0);
        $parentId = (int)($validatedData['parent_id'] ?? 0);

        return DB::transaction(function () use ($validatedData, $sourceId, $parentId, $lang) {
            $categoryData = $this->extractCategoryData($validatedData, $parentId);

            if ($sourceId > 0) {
                CategoryModel::withoutGlobalScope('lang')->where('id_code', $sourceId)->update($categoryData);
                $categoryId = $sourceId;

                $exists = CategoryModel::withoutGlobalScope('lang')
                    ->where('id_code', $sourceId)
                    ->where('lang', $lang)
                    ->first();

                $translationData = $this->extractTranslationData($validatedData, $sourceId, $lang);

                if ($exists) {
                    CategoryModel::withoutGlobalScope('lang')->where('id', $exists->id)->update($translationData);
                } else {
                    $insertData = array_merge($categoryData, $translationData);
                    $insertData['created_at'] = $validatedData['created_at'] ?? date('Y-m-d H:i:s');
                    CategoryModel::insert($insertData);
                }
            } else {
                $maxId = (int)(DB::select("SELECT MAX(id_code) as max_id FROM db_categories")[0]['max_id'] ?? 0);
                $id_code = $maxId + 1;

                $categoryData['id_code'] = $id_code;
                $categoryData['lang'] = $lang;
                $categoryData['created_at'] = $validatedData['created_at'] ?? date('Y-m-d H:i:s');

                $translationData = $this->extractTranslationData($validatedData, $id_code, $lang);

                $insertData = array_merge($categoryData, $translationData);
                CategoryModel::insert($insertData);
                $categoryId = $id_code;
            }

            return $categoryId;
        });
    }

    /**
     * Cập nhật Danh mục (Hỗ trợ chống Loop)
     */
    public function updateCategory(int $id, array $validatedData, string $primaryLang): int {
        $lang = $validatedData['lang'] ?? $primaryLang;

        // --- XỬ LÝ CHỐNG VÒNG LẶP ĐỆ QUY (INFINITE LOOP) ---
        $parentId = (int)($validatedData['parent_id'] ?? 0);
        if ($parentId === $id) {
            $parentId = 0;
        } elseif ($parentId > 0) {
            $childIds = array_filter(explode(',', CategoryModel::getChildrenIds($id, false)));
            if (in_array($parentId, $childIds)) {
                $parentId = 0;
            }
        }

        return DB::transaction(function () use ($validatedData, $id, $parentId, $lang) {
            $updateData = $this->extractCategoryData($validatedData, $parentId);
            if (!empty($validatedData['created_at'])) {
                $updateData['created_at'] = $validatedData['created_at'];
            }
            CategoryModel::withoutGlobalScope('lang')->where('id_code', $id)->update($updateData);

            $translationData = $this->extractTranslationData($validatedData, $id, $lang);

            $exists = CategoryModel::withoutGlobalScope('lang')
                ->where('id_code', $id)
                ->where('lang', $lang)
                ->first();

            if ($exists) {
                CategoryModel::withoutGlobalScope('lang')->where('id', $exists->id)->update($translationData);
            } else {
                $insertData = array_merge($updateData, $translationData);
                $insertData['created_at'] = $validatedData['created_at'] ?? date('Y-m-d H:i:s');
                CategoryModel::insert($insertData);
            }

            return $id;
        });
    }

    /**
     * Xóa 1 danh mục và các con cháu của nó
     */
    public function deleteCategory(int $id): bool {
        $ids = array_filter(explode(',', CategoryModel::getChildrenIds($id, true)));
        if (!empty($ids)) {
            CategoryModel::withoutGlobalScope('lang')->whereIn('id_code', $ids)->delete();
            return true;
        }
        return false;
    }

    /**
     * Xóa hàng loạt danh mục
     */
    public function deleteMultipleCategories(array $ids): bool {
        $allIdsToDelete = [];
        foreach ($ids as $id) {
            $childIds = array_filter(explode(',', CategoryModel::getChildrenIds((int)$id, true)));
            $allIdsToDelete = array_merge($allIdsToDelete, $childIds);
        }
        $allIdsToDelete = array_unique($allIdsToDelete);

        if (!empty($allIdsToDelete)) {
            CategoryModel::withoutGlobalScope('lang')->whereIn('id_code', $allIdsToDelete)->delete();
            return true;
        }
        return false;
    }
}
