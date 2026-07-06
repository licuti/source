<?php
namespace App\Services;

use App\Models\GalleryModel;

class GalleryService {

    /**
     * Trích xuất dữ liệu cho một ngôn ngữ cụ thể từ request
     */
    private function extractLangData(array $inputData, string $lang, int $categoryId, int $status, int $sortOrder): array {
        $title = $inputData['title'][$lang] ?? '';
        $alias = empty($inputData['alias'][$lang]) ? str_slug($title) : $inputData['alias'][$lang];

        return [
            'title'           => $title,
            'slug'            => $alias,
            'description'     => $inputData['description'][$lang] ?? '',
            'content'         => $inputData['content'][$lang] ?? '',
            'seo_title'       => $inputData['seo_title'][$lang] ?? '',
            'seo_description' => $inputData['seo_description'][$lang] ?? '',
            'keyword'         => $inputData['keyword'][$lang] ?? '',
            'tags'            => $inputData['tags'][$lang] ?? '',
            'noindex'         => isset($inputData['noindex'][$lang]) ? 1 : 0,
            'nofollow'        => isset($inputData['nofollow'][$lang]) ? 1 : 0,
            'seo_head'        => $inputData['seo_head'][$lang] ?? '',
            'seo_body'        => $inputData['seo_body'][$lang] ?? '',
        ];
    }

    /**
     * Lưu mới hoặc cập nhật Gallery đa ngôn ngữ
     */
    public function saveGallery(array $inputData, array $langs, int $userId, ?int $id = null) {
        $categoryId = (int)($inputData['category_id'] ?? 0);
        $sortOrder = (int)($inputData['sort_order'] ?? 0);
        $statusVal = (isset($inputData['status']) && ($inputData['status'] == '1' || $inputData['status'] === 'publish')) ? 1 : 0;
        
        $image = $inputData['image'] ?? '';
        
        // Gallery array (from Dropzone component)
        $gallery = $inputData['gallery'] ?? [];
        if (!is_array($gallery)) {
            $gallery = [];
        }
        
        $jsonFields = [
            'title' => [], 'slug' => [], 'description' => [], 'content' => [],
            'seo_title' => [], 'seo_description' => [], 'keyword' => [], 'tags' => [],
            'noindex' => [], 'nofollow' => [], 'seo_head' => [], 'seo_body' => []
        ];

        // Lấy dữ liệu từng ngôn ngữ
        foreach ($langs as $langCode => $langName) {
            // Sửa lại thành lấy đúng mã ngôn ngữ
            $c = is_array($langName) ? $langName['code'] : $langCode;
            
            $langData = $this->extractLangData($inputData, $c, $categoryId, $statusVal, $sortOrder);
            foreach ($jsonFields as $field => $arr) {
                $jsonFields[$field][$c] = $langData[$field];
            }
        }

        if ($id) {
            $model = GalleryModel::find($id);
            if (!$model) return false;
        } else {
            $model = new GalleryModel();
            $model->created_by = $userId;
        }

        $model->category_id = $categoryId;
        $model->status = $statusVal;
        $model->sort_order = $sortOrder;
        $model->is_featured = isset($inputData['is_featured']) ? 1 : 0;
        
        // Xử lý upload ảnh bìa (avatar) - image_upload component trả về URL hoặc basename
        if (!empty($image)) {
            $model->image = basename($image);
        } elseif (!$id) {
            $model->image = '';
        }
        
        // Xử lý mảng gallery: chỉ lưu basename để tiết kiệm
        $processedGallery = [];
        foreach ($gallery as $imgUrl) {
            if (!empty($imgUrl)) {
                $processedGallery[] = basename($imgUrl);
            }
        }
        $model->gallery = json_encode($processedGallery, JSON_UNESCAPED_UNICODE);

        // Gán JSON
        foreach ($jsonFields as $field => $dataArray) {
            $model->{$field} = json_encode($dataArray, JSON_UNESCAPED_UNICODE);
        }

        $model->save();
        return $model->id;
    }
}
