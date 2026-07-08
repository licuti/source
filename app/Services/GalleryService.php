<?php
namespace App\Services;

use App\Models\GalleryModel;

class GalleryService {

    /**
     * Lưu mới hoặc cập nhật Gallery (Dành cho 1 ngôn ngữ độc lập)
     */
    public function saveGallery(array $inputData, int $userId) {
        $categoryId = (int)($inputData['category_id'] ?? 0);
        $sortOrder = (int)($inputData['sort_order'] ?? 0);
        $statusVal = (isset($inputData['status']) && ($inputData['status'] == '1' || $inputData['status'] === 'publish')) ? 1 : 0;
        
        $image = $inputData['image'] ?? '';
        
        // Gallery array (from Dropzone component)
        $galleryInput = $inputData['gallery'] ?? [];
        if (!is_array($galleryInput)) {
            $galleryInput = [];
        }
        
        $processedGallery = [];
        foreach ($galleryInput as $imgUrl) {
            if (!empty($imgUrl)) {
                $processedGallery[] = $imgUrl;
            }
        }
        $galleryJson = json_encode($processedGallery, JSON_UNESCAPED_UNICODE);
        
        // Basic fields
        $lang = $inputData['lang'] ?? 'vi';
        $idCode = $inputData['id_code'] ?? null;
        $id = $inputData['id'] ?? null;

        $title = $inputData['title'] ?? '';
        $slug = empty($inputData['alias']) ? str_slug($title) : $inputData['alias'];

        // Kiểm tra update hay create
        $needsIdCodeUpdate = false;
        if ($id) {
            $model = GalleryModel::adminQuery()->qbFind($id);
            if (!$model) return false;
            $idCode = $model->id_code; // Bảo toàn id_code
        } else {
            $model = new GalleryModel();
            $model->created_by = $userId;
            $model->lang = $lang;
            if (!$idCode) {
                $needsIdCodeUpdate = true;
            } else {
                $model->id_code = $idCode;
            }
        }

        // Cập nhật các trường dùng chung
        $model->category_id = $categoryId;
        $model->status = $statusVal;
        $model->sort_order = $sortOrder;
        $model->is_featured = isset($inputData['is_featured']) ? 1 : 0;
        $model->gallery = $galleryJson;
        
        if (!empty($inputData['created_at'])) {
            $model->created_at = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
        }
        
        if (!empty($image)) {
            $model->image = $image;
        } elseif (!$id) {
            $model->image = '';
        }

        // Cập nhật các trường ngôn ngữ
        $model->title = $title;
        $model->slug = $slug;
        $model->description = $inputData['description'] ?? '';
        $model->content = $inputData['content'] ?? '';
        $model->seo_title = $inputData['seo_title'] ?? '';
        $model->seo_description = $inputData['seo_description'] ?? '';
        $model->keyword = $inputData['keyword'] ?? '';
        $model->tags = $inputData['tags'] ?? '';
        $model->noindex = isset($inputData['noindex']) ? 1 : 0;
        $model->nofollow = isset($inputData['nofollow']) ? 1 : 0;
        $model->seo_head = $inputData['seo_head'] ?? '';
        $model->seo_body = $inputData['seo_body'] ?? '';
        $model->seo_schema = $inputData['seo_schema'] ?? '';
        $model->seo_canonical = $inputData['seo_canonical'] ?? '';

        $model->save();
        
        if ($needsIdCodeUpdate) {
            $idCode = $model->id;
            $model->id_code = $idCode;
            $model->save();
        }

        // Đồng bộ chéo các trường dùng chung cho các bản dịch khác (nếu có)
        if ($idCode) {
            GalleryModel::adminQuery()
                ->where('id_code', $idCode)
                ->where('id', '!=', $model->id)
                ->update([
                    'category_id' => $model->category_id,
                    'image'       => $model->image,
                    'gallery'     => $model->gallery,
                    'status'      => $model->status,
                    'is_featured' => $model->is_featured,
                    'sort_order'  => $model->sort_order
                ]);
        }

        return $model->id;
    }
}
