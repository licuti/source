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
    private function buildTranslationData(array $inputData, int $idCode, string $lang): array {
        $title = $inputData['title'] ?? '';
        return [
            'id_code'        => $idCode,
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
     * Lưu mới hoặc cập nhật bài viết (Single-Table Multi-language)
     */
    public function savePost(array $inputData, int $userId) {
        $id = (int)($inputData['id'] ?? 0); 
        $sourceId = (int)($inputData['source_id'] ?? 0); 
        $lang = $inputData['lang'] ?? 'vi';
        
        $postData = $this->buildPostData($inputData, $userId);

        $postId = \App\Core\Database\DB::transaction(function () use ($inputData, $postData, $id, $sourceId, $lang, $userId) {
            if ($id > 0) {
                // Update mode
                $row = PostModel::withoutGlobalScope('lang')->find($id);
                if (!$row) return false;
                
                $idCode = $row->id_code;
                
                unset($postData['created_by']);
                if (!empty($inputData['created_at'])) {
                    $postData['created_at'] = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
                }
                $postData['updated_at'] = date('Y-m-d H:i:s');
                PostModel::withoutGlobalScope('lang')->where('id_code', $idCode)->update($postData);
                
                $transData = $this->buildTranslationData($inputData, $idCode, $lang);
                PostModel::withoutGlobalScope('lang')->where('id', $id)->update($transData);
                
                $postId = $id;
            } else if ($sourceId > 0) {
                // Add translation mode
                unset($postData['created_by']);
                $postData['updated_at'] = date('Y-m-d H:i:s');
                PostModel::withoutGlobalScope('lang')->where('id_code', $sourceId)->update($postData);
                
                $transData = $this->buildTranslationData($inputData, $sourceId, $lang);
                $insertData = array_merge($postData, $transData);
                if (!empty($inputData['created_at'])) {
                    $insertData['created_at'] = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
                } else {
                    $insertData['created_at'] = date('Y-m-d H:i:s');
                }
                $insertData['updated_at'] = date('Y-m-d H:i:s');
                $postId = PostModel::insertGetId($insertData);
            } else {
                // Create mode
                $maxId = (int)(\App\Core\Database\DB::select("SELECT MAX(id_code) as max_id FROM db_posts")[0]['max_id'] ?? 0);
                $idCode = $maxId + 1;
                
                $transData = $this->buildTranslationData($inputData, $idCode, $lang);
                $insertData = array_merge($postData, $transData);
                if (!empty($inputData['created_at'])) {
                    $insertData['created_at'] = date('Y-m-d H:i:s', strtotime($inputData['created_at']));
                } else {
                    $insertData['created_at'] = date('Y-m-d H:i:s');
                }
                $insertData['updated_at'] = $insertData['created_at'];
                $postId = PostModel::insertGetId($insertData);
            }

            if ($postId) {
                // Sync categories (Pivot Table)
                $categoryIds = $inputData['category_ids'] ?? [];
                if (!is_array($categoryIds)) {
                    $categoryIds = empty($categoryIds) ? [] : [$categoryIds];
                }
                
                \App\Core\Database\DB::statement("DELETE FROM db_post_category WHERE post_id = ?", [$postId]);
                foreach ($categoryIds as $catId) {
                    if ($catId > 0) {
                        \App\Core\Database\DB::statement("INSERT INTO db_post_category (post_id, category_id) VALUES (?, ?)", [$postId, $catId]);
                    }
                }
            }
            
            return $postId;
        });

        return $postId;
    }

    /**
     * Xóa bài viết và toàn bộ bản dịch
     */
    public function deletePost($id) {
        $ids = is_array($id) ? $id : [$id];
        if (empty($ids)) return false;

        return \App\Core\Database\DB::transaction(function () use ($ids) {
            $posts = PostModel::withoutGlobalScope('lang')->whereIn('id', $ids)->get('id_code');
            $idCodes = array_column($posts, 'id_code');
            if (empty($idCodes)) return false;

            $allRows = PostModel::withoutGlobalScope('lang')->whereIn('id_code', $idCodes)->get('id');
            $allRowIds = array_column($allRows, 'id');

            if (!empty($allRowIds)) {
                $placeholders = implode(',', array_fill(0, count($allRowIds), '?'));
                \App\Core\Database\DB::statement("DELETE FROM db_post_category WHERE post_id IN ($placeholders)", $allRowIds);
            }

            return PostModel::withoutGlobalScope('lang')->whereIn('id_code', $idCodes)->delete();
        });
    }
}
