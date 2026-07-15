<?php
namespace App\Services;

use App\Models\PostModel;

class PostService {

    /**
     * Helper tạo mảng dữ liệu Post chung
     */
    private function buildPostData(array $inputData, int $userId): array {
        return [
            'image'       => $inputData['image'] ?? '',
            'noindex'     => isset($inputData['noindex']) ? 1 : 0,
            'nofollow'    => isset($inputData['nofollow']) ? 1 : 0,
            'seo_head'    => $inputData['seo_head'] ?? '',
            'seo_body'    => $inputData['seo_body'] ?? '',
            'sort_order'  => (int)($inputData['sort_order'] ?? 0),
            'status'      => (isset($inputData['status']) && ($inputData['status'] == '1' || $inputData['status'] === 'publish')) ? 1 : 0,
            'is_featured' => isset($inputData['is_featured']) ? 1 : 0,
            'created_by'  => $userId,
            'updated_by'  => $userId,
        ];
    }

    /**
     * Helper tạo mảng dữ liệu Translation
     */
    private function buildTranslationData(array $inputData, int $postId, string $lang): array {
        $title = $inputData['title'] ?? '';
        return [
            'post_id'        => $postId,
            'lang'           => $lang,
            'title'          => $title,
            'slug'           => empty($inputData['slug']) ? str_slug($title) : $inputData['slug'],
            'description'    => $inputData['description'] ?? '',
            'content'        => $inputData['content'] ?? '',
            'seo_title'      => $inputData['seo_title'] ?? '',
            'seo_description'=> $inputData['seo_description'] ?? '',
            'keyword'        => $inputData['keyword'] ?? '',
            'tags'           => $inputData['tags'] ?? '',
        ];
    }

    /**
     * Lưu mới hoặc cập nhật bài viết (Two-Table Translatable + Pivot Categories)
     */
    public function savePost(array $inputData, int $userId) {
        $id = (int)($inputData['id'] ?? 0);
        $sourceId = (int)($inputData['source_id'] ?? 0); // Thêm bản dịch từ bài viết có sẵn
        $lang = $inputData['lang'] ?? 'vi';
        
        $postData = $this->buildPostData($inputData, $userId);

        if ($id > 0) {
            // Update mode
            $postId = $id;
            unset($postData['created_by']); // Do not update created_by on edit
            if (!empty($inputData['created_at'])) {
                $postData['created_at'] = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
            }
            $postData['updated_at'] = date('Y-m-d H:i:s');
            PostModel::where('id', $postId)->update($postData);
        } else if ($sourceId > 0) {
            // Add translation mode
            $postId = $sourceId;
            unset($postData['created_by']);
            $postData['updated_at'] = date('Y-m-d H:i:s');
            PostModel::where('id', $postId)->update($postData);
        } else {
            // Create mode
            if (!empty($inputData['created_at'])) {
                $postData['created_at'] = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
            } else {
                $postData['created_at'] = date('Y-m-d H:i:s');
            }
            $postData['updated_at'] = $postData['created_at'];
            $postId = PostModel::insertGetId($postData);
        }

        if ($postId) {
            // Lưu bản dịch
            $transData = $this->buildTranslationData($inputData, $postId, $lang);
            \App\Models\PostTranslationModel::updateOrCreate(
                ['post_id' => $postId, 'lang' => $lang],
                $transData
            );

            // Cập nhật Categories (Pivot Table)
            // Lấy danh sách ID danh mục từ request
            $categoryIds = $inputData['category_ids'] ?? [];
            if (!is_array($categoryIds)) {
                $categoryIds = empty($categoryIds) ? [] : [$categoryIds];
            }
            
            // Sync categories thủ công qua DB
            \App\Core\Database\DB::table('post_category')->where('post_id', $postId)->delete();
            foreach ($categoryIds as $catId) {
                if ($catId > 0) {
                    \App\Core\Database\DB::table('post_category')->insert([
                        'post_id' => $postId,
                        'category_id' => $catId
                    ]);
                }
            }
        }

        return $postId;
    }

    /**
     * Xóa bài viết và toàn bộ bản dịch (Cascade delete sẽ tự xử lý ở DB nếu có, nhưng ở đây xóa thủ công cho chắc)
     */
    public function deletePost($id) {
        $ids = is_array($id) ? $id : [$id];
        if (empty($ids)) return false;

        // Xóa bản dịch
        \App\Models\PostTranslationModel::whereIn('post_id', $ids)->delete();
        
        // Xóa pivot
        \App\Core\Database\DB::table('post_category')->whereIn('post_id', $ids)->delete();
        
        // Xóa bảng chính
        return PostModel::whereIn('id', $ids)->delete();
    }
}
