<?php
namespace App\Services;

use App\Models\PostModel;

class PostService {

    /**
     * Lưu mới hoặc cập nhật bài viết (Polylang - dành cho 1 ngôn ngữ độc lập)
     */
    public function savePost(array $inputData, int $userId) {
        $categoryId = (int)($inputData['category_id'] ?? 0);
        $sortOrder = (int)($inputData['sort_order'] ?? 0);
        
        $statusVal = (isset($inputData['status']) && ($inputData['status'] == '1' || $inputData['status'] === 'publish')) ? 1 : 0;
        
        $lang = $inputData['lang'] ?? 'vi';
        $idCode = $inputData['id_code'] ?? null;
        $id = $inputData['id'] ?? null;

        $title = $inputData['title'] ?? '';
        $slug = empty($inputData['alias']) ? str_slug($title) : $inputData['alias'];

        $needsIdCodeUpdate = false;
        
        if ($id) {
            $model = PostModel::adminQuery()->qbFind($id);
            if (!$model) return false;
            $idCode = $model->id_code;
        } else {
            $model = new PostModel();
            $model->created_by = $userId;
            $model->lang = $lang;
            if (!$idCode) {
                $needsIdCodeUpdate = true;
            } else {
                $model->id_code = $idCode;
            }
        }

        $model->category_id = $categoryId;
        $model->title = $title;
        $model->alias = $slug;
        $model->description = $inputData['description'] ?? '';
        $model->content = $inputData['content'] ?? '';
        $model->image = $inputData['image'] ?? '';
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
        $model->sort_order = $sortOrder;
        $model->status = $statusVal;
        $model->is_featured = isset($inputData['is_featured']) ? 1 : 0;
        
        if (isset($inputData['created_at']) && !empty($inputData['created_at'])) {
            $model->created_at = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
        }
        
        $model->updated_by = $userId;
        $model->updated_at = date('Y-m-d H:i:s');

        $savedId = $model->save();
        
        if ($savedId && $needsIdCodeUpdate) {
            $model->id_code = $savedId;
            $model->save();
            return $savedId;
        }

        return $idCode ?: $savedId;
    }

    /**
     * Xóa bài viết và toàn bộ bản dịch
     */
    public function deletePost(int $idCode) {
        if ($idCode > 0) {
            return PostModel::adminQuery()->where('id_code', $idCode)->delete();
        }
        return false;
    }
}
