<?php
namespace App\Services;

use App\Models\PostModel;

class PostService {

    /**
     * Trích xuất dữ liệu cho một ngôn ngữ cụ thể từ request
     */
    private function extractLangData(array $inputData, string $lang, int $categoryId, int $status, int $sortOrder, ?string $createdAt): array {
        $title = $inputData['title'][$lang] ?? '';
        $alias = empty($inputData['alias'][$lang]) ? str_slug($title) : $inputData['alias'][$lang];

        $data = [
            'category_id'     => $categoryId,
            'lang'            => $lang,
            'title'           => $title,
            'alias'           => $alias,
            'description'     => $inputData['description'][$lang] ?? '',
            'content'         => $inputData['content'][$lang] ?? '',
            'image'           => $inputData['image'] ?? '',
            'seo_title'       => $inputData['seo_title'][$lang] ?? '',
            'seo_description' => $inputData['seo_description'][$lang] ?? '',
            'keyword'         => $inputData['keyword'][$lang] ?? '',
            'tags'            => $inputData['tags'][$lang] ?? '',
            'noindex'         => isset($inputData['noindex'][$lang]) ? 1 : 0,
            'nofollow'        => isset($inputData['nofollow'][$lang]) ? 1 : 0,
            'seo_head'        => $inputData['seo_head'][$lang] ?? '',
            'seo_body'        => $inputData['seo_body'][$lang] ?? '',
            'seo_schema'      => $inputData['seo_schema'][$lang] ?? '',
            'seo_canonical'   => $inputData['seo_canonical'][$lang] ?? '',
            'sort_order'      => $sortOrder,
            'status'          => $status,
            'is_featured'     => isset($inputData['is_featured']) ? 1 : 0,
        ];

        if ($createdAt) {
            $data['created_at'] = $createdAt;
        }

        return $data;
    }

    /**
     * Lưu mới hoặc cập nhật bài viết đa ngôn ngữ
     *
     * @param array $inputData Dữ liệu thô từ Request
     * @param array $langs Cấu hình ngôn ngữ hệ thống
     * @param int $userId ID của user thực hiện
     * @param int|null $idCode (Chỉ update) ID Code của bài viết
     * @return int|bool Trả về id_code nếu thành công, false nếu thất bại
     */
    public function savePost(array $inputData, array $langs, int $userId, ?int $idCode = null) {
        $categoryId = (int)($inputData['category_id'] ?? 0);
        $sortOrder = (int)($inputData['sort_order'] ?? 0);
        
        // Lấy status từ form (checkbox / radio trả về 1 hoặc 0, mặc định 0)
        $statusVal = (isset($inputData['status']) && ($inputData['status'] == '1' || $inputData['status'] === 'publish')) ? 1 : 0;

        $createdAtInput = $inputData['created_at'] ?? null;
        $createdAt = $createdAtInput ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : null;
        $now = date('Y-m-d H:i:s');

        // Mảng chứa các logic update/insert
        $firstLang = $langs[0]['code'];

        if (!$idCode) { // INSERT MỚI
            if (!$createdAt) $createdAt = $now;

            $firstLangData = $this->extractLangData($inputData, $firstLang, $categoryId, $statusVal, $sortOrder, $createdAt);
            $firstLangData['id_code'] = 0;
            $firstLangData['created_by'] = $userId;
            $firstLangData['updated_at'] = $now;

            $insertedId = PostModel::insertGetId($firstLangData);
            if (!$insertedId) return false;

            // Update id_code cho bản ghi gốc
            PostModel::adminQuery()->where('id', $insertedId)->update(['id_code' => $insertedId]);

            // Thêm các ngôn ngữ còn lại
            foreach ($langs as $index => $l) {
                if ($index === 0) continue;
                $langData = $this->extractLangData($inputData, $l['code'], $categoryId, $statusVal, $sortOrder, $createdAt);
                $langData['id_code'] = $insertedId;
                $langData['created_by'] = $userId;
                $langData['updated_at'] = $now;
                PostModel::insert($langData);
            }

            return $insertedId;

        } else { // CẬP NHẬT
            foreach ($langs as $l) {
                $c = $l['code'];
                $exists = PostModel::adminQuery()->where('id_code', $idCode)->where('lang', $c)->first();
                
                $data = $this->extractLangData($inputData, $c, $categoryId, $statusVal, $sortOrder, $createdAt);
                $data['updated_by'] = $userId;
                $data['updated_at'] = $now;

                if ($exists) {
                    PostModel::adminQuery()->where('id', $exists->id)->update($data);
                } else {
                    $data['id_code'] = $idCode;
                    $data['created_by'] = $userId;
                    if (!$createdAt) $data['created_at'] = $now;
                    PostModel::insert($data);
                }
            }
            return $idCode;
        }
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

    /**
     * Lấy và chuyển hóa dữ liệu bài viết (gom đa ngôn ngữ) để hiển thị lên Form Edit
     */
    public function getPostForEdit(int $idCode): ?array {
        $translations = PostModel::adminQuery()->where('id_code', $idCode)->get();
        if (count($translations) == 0) return null;

        $firstPost = $translations[0];
        
        $item = [
            'id'          => $idCode, 
            'category_id' => $firstPost->category_id, 
            'sort_order'  => $firstPost->sort_order, 
            'status'      => $firstPost->status,
            'created_at'  => $firstPost->created_at,
            'is_featured' => $firstPost->is_featured,
            'image'       => $firstPost->image,
            'created_by'  => $firstPost->created_by
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["title"][$lang] = $t->title;
            $item["alias"][$lang] = $t->alias;
            $item["description"][$lang] = $t->description;
            $item["content"][$lang] = $t->content;
            $item["seo_title"][$lang] = $t->seo_title;
            $item["seo_description"][$lang] = $t->seo_description;
            $item["keyword"][$lang] = $t->keyword;
            $item["tags"][$lang] = $t->tags;
            $item["noindex"][$lang] = $t->noindex;
            $item["nofollow"][$lang] = $t->nofollow;
            $item["seo_head"][$lang] = $t->seo_head;
            $item["seo_body"][$lang] = $t->seo_body;
            if (empty($item["image"]) && !empty($t->image)) {
                $item["image"] = $t->image;
            }
        }
        
        return $item;
    }
}
