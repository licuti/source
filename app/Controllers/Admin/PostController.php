<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use CategoryModel;
use PostModel;

class PostController extends BaseAdminController {
    
    /**
     * Helper kiểm tra quyền sở hữu hoặc quyền admin
     */
    private function canEditPost($createdBy) {
        $user = user();
        if ($user->is_admin == 1) return true;
        if ($user->id == $createdBy) return true;
        return false;
    }

    /**
     * Helper lọc query theo quyền sở hữu
     */
    private function applyOwnershipFilter($query) {
        $user = user();
        if ($user->is_admin != 1) {
            $query->where('created_by', $user->id);
        }
        return $query;
    }

    /**
     * Helper gom dữ liệu chung cho thao tác Thêm / Sửa
     */
    private function getPostData(Request $request, string $lang, int $categoryId, int $status, int $sortOrder, $createdAt = null): array {
        $titleInput = $request->input('title', []);
        $title = $titleInput[$lang] ?? '';
        $aliasInput = $request->input('alias', []);
        $alias = empty($aliasInput[$lang]) ? str_slug($title) : $aliasInput[$lang];

        $data = [
            'title'           => $title,
            'alias'           => $alias,
            'description'     => $request->input('description')[$lang] ?? '',
            'content'         => $request->input('content')[$lang] ?? '',
            'image'           => $request->input('image') ?? '',
            'seo_title'       => $request->input('seo_title')[$lang] ?? '',
            'seo_description' => $request->input('seo_description')[$lang] ?? '',
            'keyword'         => $request->input('keyword')[$lang] ?? '',
            'tags'            => $request->input('tags')[$lang] ?? '',
            'noindex'         => isset($request->input('noindex')[$lang]) ? 1 : 0,
            'nofollow'        => isset($request->input('nofollow')[$lang]) ? 1 : 0,
            'seo_head'        => $request->input('seo_head')[$lang] ?? '',
            'seo_body'        => $request->input('seo_body')[$lang] ?? '',
            'category_id'     => $categoryId,
            'sort_order'      => $sortOrder,
            'status'          => $status,
            'is_featured'     => $request->input('is_featured') !== null ? 1 : 0,
        ];

        if ($createdAt) {
            $data['created_at'] = $createdAt;
        }

        return $data;
    }

    /**
     * Hiển thị danh sách bài viết
     */
    public function index(Request $request) {
        $keyword = trim($request->input('keyword', ''));
        $status = $request->input('status', ''); // Update from hien_thi
        $categoryId = (int)$request->input('category_id', 0);
        $page = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;

        $postQuery = PostModel::query();
        $postQuery->use_lang = false;
        // Chỉ lấy 1 ngôn ngữ làm đại diện để đếm và hiển thị (VD: 'vi')
        $postQuery->where('lang', 'vi');
        
        $postQuery = $this->applyOwnershipFilter($postQuery);

        if ($status !== '') {
            $postQuery->where('status', $status);
        }
        
        if ($categoryId > 0) {
            $postQuery->where('category_id', $categoryId);
        }

        if ($keyword !== '') {
            $postQuery->whereLike('title', $keyword);
        }

        $posts = $postQuery->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC')->paginate($limit);

        $categories = CategoryModel::getTreeForAdminByModule(config('modules.post'));

        return $this->render('admin.post.index', compact('posts', 'keyword', 'status', 'categoryId', 'categories'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $categories = CategoryModel::getTreeForAdminByModule(config('modules.post'));
        return $this->render('admin.post.form', compact('langs', 'categories'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $postQuery = PostModel::query();
        $postQuery->use_lang = false; // Lấy tất cả ngôn ngữ
        $translations = $postQuery->where('id_code', $id)->get();
        
        if (count($translations) == 0) {
            return $this->redirect(route('admin.post.index'));
        }
        
        $firstPost = $translations[0];
        
        if (!$this->canEditPost($firstPost->created_by)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        // Chuyển hóa dữ liệu để render lên form (Dùng English Keys)
        $item = [
            'id'          => $id, 
            'category_id' => $firstPost->category_id, 
            'sort_order'  => $firstPost->sort_order, 
            'status'      => $firstPost->status,
            'created_at'  => $firstPost->created_at,
            'is_featured' => $firstPost->is_featured,
            'image'       => $firstPost->image
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
            if (empty($item["image"])) {
                $item["image"] = $t->image;
            }
        }
        
        $categories = CategoryModel::getTreeForAdminByModule(config('modules.post'));
        
        return $this->render('admin.post.form', compact('langs', 'item', 'categories'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        $titleInput = $request->input('title', []);
        
        // Backend Validation: Chặn tiêu đề rỗng
        if (empty($titleInput['vi'])) {
            session('error', 'Vui lòng nhập Tiêu đề bài viết (Tiếng Việt).');
            return $this->redirect(route('admin.post.create'));
        }

        $categoryId = (int)$request->input('category_id', 0);
        $sortOrder = (int)$request->input('sort_order', 0);
        $status = $request->input('status') !== null ? 1 : 0;
        
        $createdAtInput = $request->input('created_at');
        $createdAt = $createdAtInput ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : date('Y-m-d H:i:s');
        
        $userId = user()->id;
        $now = date('Y-m-d H:i:s');
        $langs = config('lang', [['code' => 'vi']]);

        // 1. Tạo bản ghi ngôn ngữ đầu tiên (vi) bằng insertGetId để tránh Race Condition
        $firstLang = $langs[0]['code'];
        $firstLangData = $this->getPostData($request, $firstLang, $categoryId, $status, $sortOrder, $createdAt);
        $firstLangData['id_code'] = 0; // Gán tạm thời
        $firstLangData['lang'] = $firstLang;
        $firstLangData['created_by'] = $userId;
        $firstLangData['updated_at'] = $now;

        $insertedId = PostModel::insertGetId($firstLangData);

        if ($insertedId) {
            // 2. Cập nhật lại id_code cho bản ghi đầu tiên
            $updateQuery = PostModel::query();
            $updateQuery->use_lang = false;
            $updateQuery->where('id', $insertedId)->update(['id_code' => $insertedId]);

            // 3. Thêm các bản ghi dịch thuật còn lại dùng chung id_code
            foreach ($langs as $index => $l) {
                if ($index === 0) continue; // Bỏ qua bản ghi đầu tiên đã insert
                
                $c = $l['code'];
                $langData = $this->getPostData($request, $c, $categoryId, $status, $sortOrder, $createdAt);
                $langData['id_code'] = $insertedId;
                $langData['lang'] = $c;
                $langData['created_by'] = $userId;
                $langData['updated_at'] = $now;
                
                PostModel::insert($langData);
            }

            session('success', 'Thêm bài viết thành công!');
        } else {
            session('error', 'Có lỗi xảy ra khi tạo bài viết.');
        }
        
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue' && $insertedId) {
            return $this->redirect(route('admin.post.edit', ['id' => $insertedId]));
        } elseif ($saveAction === 'new') {
            return $this->redirect(route('admin.post.create'));
        }
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $titleInput = $request->input('title', []);
        
        // Backend Validation: Chặn tiêu đề rỗng
        if (empty($titleInput['vi'])) {
            session('error', 'Vui lòng nhập Tiêu đề bài viết (Tiếng Việt).');
            return $this->redirect(route('admin.post.edit', ['id' => $id]));
        }

        $postQuery = PostModel::query();
        $postQuery->use_lang = false;
        $translations = $postQuery->where('id_code', $id)->get();
        
        if (count($translations) > 0 && !$this->canEditPost($translations[0]->created_by)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        $categoryId = (int)$request->input('category_id', 0);
        $sortOrder = (int)$request->input('sort_order', 0);
        $status = $request->input('status') !== null ? 1 : 0;
        
        $createdAtInput = $request->input('created_at');
        $createdAt = $createdAtInput ? date('Y-m-d H:i:s', strtotime($createdAtInput)) : null;

        $langs = config('lang', [['code' => 'vi']]);
        $userId = user()->id;
        $now = date('Y-m-d H:i:s');
        
        foreach ($langs as $l) {
            $c = $l['code'];
            $postQuery = PostModel::query();
            $postQuery->use_lang = false;
            
            $exists = $postQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = $this->getPostData($request, $c, $categoryId, $status, $sortOrder, $createdAt);
            $data['updated_by'] = $userId;
            $data['updated_at'] = $now;
            
            if ($exists) {
                $updateQuery = PostModel::query();
                $updateQuery->use_lang = false;
                $updateQuery->where('id', $exists->id)->update($data);
            } else {
                $data['id_code'] = $id;
                $data['lang'] = $c;
                $data['created_by'] = $userId;
                if (!$createdAt) $data['created_at'] = $now;
                PostModel::insert($data);
            }
        }
        
        session('success', 'Cập nhật bài viết thành công!');
        
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.post.edit', ['id' => $id]));
        } elseif ($saveAction === 'new') {
            return $this->redirect(route('admin.post.create'));
        }
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Cập nhật trạng thái hiển thị qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id');
        $field = $request->input('field', 'status');
        $value = (int)$request->input('value', 0);

        $allowedFields = ['status', 'is_featured']; 
        // Fallback for legacy ajax requests
        if ($field === 'is_active' || $field === 'hien_thi') {
            $field = 'status';
        }

        if (!in_array($field, $allowedFields)) {
            return $this->jsonError('Trường dữ liệu không hợp lệ');
        }

        if ($id > 0) {
            $postQuery = PostModel::query();
            $postQuery->use_lang = false;
            $post = $postQuery->where('id_code', $id)->first();
            
            if ($post && !$this->canEditPost($post->created_by)) {
                return $this->jsonError('Bạn không có quyền sửa bài viết này!');
            }

            $updateQuery = PostModel::query();
            $updateQuery->use_lang = false;
            $label = $field === 'is_featured' ? 'Nổi bật' : 'Trạng thái hiển thị';
            
            $updateQuery->where('id_code', $id)->update([$field => $value]);

            return $this->jsonSuccess($label . ' đã được cập nhật!');
        }
        return $this->jsonError('ID không hợp lệ');
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $postQuery = PostModel::query();
        $postQuery->use_lang = false;
        $post = $postQuery->where('id_code', $id)->first();
        
        if ($post && !$this->canEditPost($post->created_by)) {
            session('error', 'Bạn không có quyền xóa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        if ($id > 0) {
            $delQuery = PostModel::query();
            $delQuery->use_lang = false;
            $delQuery->where('id_code', $id)->delete();
            
            session('success', 'Đã xóa bài viết thành công!');
        }
        
        return $this->redirect(route('admin.post.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        
        if (!empty($ids) && is_array($ids)) {
            $deletedCount = 0;
            foreach ($ids as $id) {
                $postQuery = PostModel::query();
                $postQuery->use_lang = false;
                $post = $postQuery->where('id_code', $id)->first();
                
                if ($post && $this->canEditPost($post->created_by)) {
                    $delQuery = PostModel::query();
                    $delQuery->use_lang = false;
                    $delQuery->where('id_code', $id)->delete();
                    $deletedCount++;
                }
            }
            return $this->json(['success' => true, 'message' => "Đã xóa thành công {$deletedCount} bài viết."]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
}

