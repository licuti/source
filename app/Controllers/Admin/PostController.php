<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Validator;
use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Services\PostService;

class PostController extends BaseAdminController {
    
    private PostService $postService;
    private array $langs;
    private string $primaryLang;
    private int $moduleId;

    public function __construct() {
        parent::__construct();
        $this->postService = new PostService();
        $this->langs       = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $this->primaryLang = config('locale', 'vi');
        $this->moduleId    = config('modules.post');
    }

    /**
     * Hiển thị danh sách bài viết
     */
    public function index(Request $request) {
        $keyword    = trim($request->input('keyword', ''));
        $status     = $request->input('status', '');
        $categoryId = (int)$request->input('category_id', 0);
        $page       = max(1, (int)$request->input('page', 1));
        
        $currentLang = $request->input('lang', $this->primaryLang);

        $postQuery = PostModel::adminQuery()
            ->where('lang', $currentLang)
            ->ownedByUser(user());

        if ($status !== '')      $postQuery->where('status', $status);
        if ($categoryId > 0)     $postQuery->where('category_id', $categoryId);
        if ($keyword !== '')     $postQuery->whereLike('title', $keyword);

        $posts = $postQuery->orderBy('sort_order', 'ASC')
                           ->orderBy('id', 'DESC')
                           ->paginate(10);

        $categories = $this->getCategories();
        $langs = $this->langs;

        $idCodes = array_map(function($a) { return is_array($a) ? ($a['id_code'] ?? 0) : $a->id_code; }, $posts->items());
        $translations = [];
        if (!empty($idCodes)) {
            $allTrans = PostModel::adminQuery()
                ->whereIn('id_code', $idCodes)
                ->get();
            foreach ($allTrans as $t) {
                $translations[$t->id_code][$t->lang] = $t->id;
            }
        }

        return $this->render('admin.post.index', compact('posts', 'keyword', 'status', 'categoryId', 'categories', 'langs', 'currentLang', 'translations'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langCode = $request->input('lang', $this->primaryLang);
        $sourceId = (int)$request->input('source_id', 0);
        
        $item = [];
        if ($sourceId > 0) {
            $sourceItem = PostModel::adminQuery()->where('id_code', $sourceId)->first();
            if ($sourceItem) {
                $item['id_code'] = $sourceItem->id_code;
                $item['category_id'] = $sourceItem->category_id;
                $item['image'] = $sourceItem->image;
                $item['status'] = $sourceItem->status;
                $item['sort_order'] = $sourceItem->sort_order;
                $item['is_featured'] = $sourceItem->is_featured;
            }
        }
        
        $langs = $this->langs;
        $categories = $this->getCategories();
        
        $currentLangItem = collect($langs)->firstWhere('code', $langCode);
        $currentLangName = $currentLangItem ? $currentLangItem['name'] : 'Unknown';
        
        $translations = [];
        if (!empty($item['id_code'])) {
            $allTrans = PostModel::adminQuery()->where('id_code', $item['id_code'])->get('id, lang');
            foreach ($allTrans as $t) {
                $translations[$t->lang] = $t->id;
            }
        }
        
        return $this->render('admin.post.form', compact('langs', 'categories', 'item', 'langCode', 'currentLangName', 'translations'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = $this->parseId($id);
        
        $item = PostModel::adminQuery()->qbFind($id);
        
        if (!$item) {
            return $this->redirect(route('admin.post.index'));
        }
        
        if (!$this->canModify($item)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }
        
        // Convert array/object for the view compatibility
        $item = is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array)$item;
        
        $langs = $this->langs;
        $categories = $this->getCategories();
        
        $langCode = $item['lang'];
        $currentLangItem = collect($langs)->firstWhere('code', $langCode);
        $currentLangName = $currentLangItem ? $currentLangItem['name'] : 'Unknown';
        
        $translations = [];
        if (!empty($item['id_code'])) {
            $allTrans = PostModel::adminQuery()->where('id_code', $item['id_code'])->get('id, lang');
            foreach ($allTrans as $t) {
                $translations[$t->lang] = $t->id;
            }
        }
        
        return $this->render('admin.post.form', compact('langs', 'categories', 'item', 'langCode', 'currentLangName', 'translations'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        if (!$this->validatePost($request)) {
            return $this->redirect(route('admin.post.create'));
        }

        $insertedId = $this->postService->savePost($request->all(), user()->id);

        if ($insertedId) {
            session('success', 'Thêm bài viết thành công!');
        } else {
            session('error', 'Có lỗi xảy ra khi tạo bài viết.');
        }
        
        return $this->handleSaveRedirect($request, $insertedId);
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = $this->parseId($id);
        
        if (!$this->validatePost($request)) {
            return $this->redirect(route('admin.post.edit', ['id' => $id]));
        }

        $firstPost = PostModel::adminQuery()->qbFind($id);
        
        if (!$this->canModify($firstPost)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        $this->postService->savePost($request->all(), user()->id);
        
        session('success', 'Cập nhật bài viết thành công!');
        return $this->handleSaveRedirect($request, $id);
    }

    /**
     * Cập nhật trạng thái hiển thị qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id    = (int)$request->input('id');
        $field = $request->input('field', 'status');
        $value = (int)$request->input('value', 0);

        if ($field === 'is_active' || $field === 'hien_thi') {
            $field = 'status';
        }

        if (!in_array($field, ['status', 'is_featured'])) {
            return $this->jsonError('Trường dữ liệu không hợp lệ');
        }

        $post = PostModel::adminQuery()->where('id_code', $id)->first();
        if (!$post) return $this->jsonError('ID không hợp lệ');

        if (!$this->canModify($post)) {
            return $this->jsonError('Bạn không có quyền sửa bài viết này!');
        }

        $updateVal = $value == 1 ? 1 : 0;
        
        PostModel::adminQuery()->where('id_code', $id)->update([$field => $updateVal]);
        
        $label = $field === 'is_featured' ? 'Nổi bật' : 'Trạng thái hiển thị';
        return $this->jsonSuccess($label . ' đã được cập nhật!');
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = $this->parseId($id);
        
        $post = PostModel::adminQuery()->where('id_code', $id)->first();
        
        if (!$this->canModify($post)) {
            session('error', 'Bạn không có quyền xóa bài viết này!');
            return $this->redirect(route('admin.post.index'));
        }

        if ($this->postService->deletePost($id)) {
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
                $post = PostModel::adminQuery()->where('id_code', $id)->first();
                if ($this->canModify($post)) {
                    $this->postService->deletePost($id);
                    $deletedCount++;
                }
            }
            return $this->json(['success' => true, 'message' => "Đã xóa thành công {$deletedCount} bài viết."]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }

    // ============================================================
    //  HELPER METHODS
    // ============================================================

    /**
     * Lấy danh sách chuyên mục cho module Post
     */
    private function getCategories() {
        return CategoryModel::getTreeForAdminByModule($this->moduleId);
    }

    /**
     * Xác thực quyền chỉnh sửa/xóa bài viết
     */
    private function canModify($post): bool {
        if (!$post) return false;
        
        $createdBy = is_array($post) ? ($post['created_by'] ?? 0) : $post->created_by;
        return ($createdBy == user()->id || user()->is_admin == 1);
    }

    /**
     * Validation dùng chung cho Store và Update
     */
    private function validatePost(Request $request): bool {
        $validator = new Validator($request->all(), [
            "title" => 'required|max:255'
        ], [
            "title.required" => 'Vui lòng nhập Tiêu đề bài viết.',
            "title.max"      => 'Tiêu đề bài viết không được vượt quá 255 ký tự.'
        ]);

        if ($validator->fails()) {
            session('error', $validator->firstError());
            return false;
        }
        return true;
    }

    /**
     * Xử lý ID từ Router (Có thể là chuỗi, mảng do catch-all)
     */
    private function parseId($id): int {
        return (int)(is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id);
    }

    /**
     * Xử lý redirect sau khi lưu
     */
    private function handleSaveRedirect(Request $request, $id) {
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue' && $id) {
            return $this->redirect(route('admin.post.edit', ['id' => $id]));
        } elseif ($saveAction === 'new') {
            return $this->redirect(route('admin.post.create'));
        }
        return $this->redirect(route('admin.post.index'));
    }
}
