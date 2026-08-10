<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Auth\AuthManager;
use App\Requests\Admin\PostRequest;
use App\Models\CategoryModel;
use App\Models\PostModel;
use App\Services\PostService;

class PostController extends BaseAdminController {
    
    private PostService $postService;
    private int $moduleId;
    protected string $routePrefix = 'admin.post';
    protected int $perPage = 10;

    public function __construct() {
        parent::__construct();
        $this->postService = new PostService();
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

        $postQuery = $this->getBaseQuery()
            ->where('lang', $currentLang);
        $postQuery = (new PostModel())->scopeOwnedByUser($postQuery, AuthManager::user());

        if ($status !== '') {
            $postQuery->where('status', $status);
        }
        if ($categoryId > 0) {
            $postQuery->join('post_category as pc', 'pc.post_id', '=', 'db_posts.id');
            $postQuery->where('pc.category_id', $categoryId);
        }
        if ($keyword !== '') {
            $postQuery->whereLike('title', $keyword);
        }

        $posts = $postQuery->orderBy('sort_order', 'ASC')
                           ->orderBy('id', 'DESC')
                           ->paginate($this->perPage);

        $categories = $this->getCategories();
        $langs = $this->langs;

        $ids = array_map(function($a) { return is_array($a) ? ($a['id_code'] ?? 0) : $a->id_code; }, $posts->items());
        $translations = [];
        if (!empty($ids)) {
            $allTrans = $this->getBaseQuery()->whereIn('id_code', $ids)->get('id_code, lang, id');
            foreach ($allTrans as $t) {
                $translations[$t->id_code][$t->lang] = $t->id;
            }
        }

        return $this->render('admin.post.index', compact('posts', 'keyword', 'status', 'categoryId', 'categories', 'langs', 'currentLang', 'translations'));
    }

    /**
     * Helper lấy dữ liệu form dùng chung
     */
    private function getFormData(Request $request, $item = []) {
        $langs = $this->langs;
        $categories = $this->getCategories();
        $langCode = $request->input('lang', $this->primaryLang);
        $currentLangName = $this->getLangName($langCode);
        
        $translations = [];
        if (!empty($item['id_code'])) {
            $allTrans = $this->getBaseQuery()->where('id_code', $item['id_code'])->get('id, lang');
            foreach ($allTrans as $t) {
                $translations[$t->lang] = $t->id;
            }
        }
        
        return compact('langs', 'categories', 'item', 'langCode', 'currentLangName', 'translations');
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $item = [];
        $sourceId = (int)$request->input('source_id', 0);
        
        if ($sourceId > 0) {
            $sourceItem = $this->getBaseQuery()->where('id_code', $sourceId)->first();
            if ($sourceItem) {
                $item = $sourceItem->toArray();
                $item['id'] = ''; // Clear row ID for new translation row
                $item['id_code'] = $sourceId;
                $item['source_id'] = $sourceId;
                
                // Fetch categories
                $item['category_ids'] = $sourceItem->getCategoryIds();
            }
        }

        return $this->render('admin.post.form', $this->getFormData($request, $item));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = (int)$id; // Row ID
        $langCode = $request->input('lang', $this->primaryLang);
        
        $itemObj = $this->getBaseQuery()->find($id);
        if (!$itemObj) return $this->redirect(route($this->routePrefix . '.index'));
        if (!$this->canModify($itemObj)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route($this->routePrefix . '.index'));
        }
        
        $idCode = $itemObj->id_code;
        
        if ($itemObj->lang !== $langCode) {
            $langObj = $this->getBaseQuery()
                ->where('id_code', $idCode)
                ->where('lang', $langCode)
                ->first();
                
            if ($langObj) {
                $itemObj = $langObj;
            } else {
                return $this->redirect(route($this->routePrefix . '.create', ['lang' => $langCode, 'source_id' => $idCode]));
            }
        }

        $item = $itemObj->toArray();
        
        $item['category_ids'] = $itemObj->getCategoryIds();
        
        return $this->render('admin.post.form', $this->getFormData($request, $item));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(PostRequest $request) {
        $validatedData = $request->validated();

        $insertedId = $this->postService->savePost($request->all(), AuthManager::user()->id);

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
    public function update(PostRequest $request, $id) {
        $id = (int)$id;
        $validatedData = $request->validated();

        $firstPost = $this->getBaseQuery()->find($id);
        
        if (!$firstPost || !$this->canModify($firstPost)) {
            session('error', 'Bạn không có quyền chỉnh sửa bài viết này!');
            return $this->redirect(route($this->routePrefix . '.index'));
        }

        $inputData = $request->all();
        $inputData['id'] = $id;
        
        $this->postService->savePost($inputData, AuthManager::user()->id);
        
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

        if ($field === 'is_active') {
            $field = 'status';
        }

        if (!in_array($field, ['status', 'is_featured'])) {
            return $this->jsonError('Trường dữ liệu không hợp lệ');
        }

        $post = $this->getBaseQuery()->find($id);
        if (!$post) return $this->jsonError('ID không hợp lệ');

        if (!$this->canModify($post)) {
            return $this->jsonError('Bạn không có quyền sửa bài viết này!');
        }

        $updateVal = $value == 1 ? 1 : 0;
        
        $this->getBaseQuery()->where('id_code', $post->id_code)->update([$field => $updateVal]);
        
        $label = $field === 'is_featured' ? 'Nổi bật' : 'Trạng thái hiển thị';
        return $this->jsonSuccess($label . ' đã được cập nhật!');
    }

    /**
     * Xóa 1 dòng
     */
    public function destroyAjax(Request $request) {
        $id = (int)$request->input('id');
        
        $post = $this->getBaseQuery()->find($id);
        
        if (!$post) {
            return $this->jsonError('Không tìm thấy bài viết!');
        }
        
        if (!$this->canModify($post)) {
            return $this->jsonError('Bạn không có quyền xóa bài viết này!');
        }

        if ($this->postService->deletePost($id)) {
            return $this->jsonSuccess('Đã xóa bài viết thành công!');
        }
        
        return $this->jsonError('Không thể xóa bài viết!');
    }

    /**
     * Xóa hàng loạt
     */
    public function bulkDeleteAjax(Request $request) {
        $ids = $request->input('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return $this->jsonError('Không có mục nào được chọn!');
        }
        
        $posts = $this->getBaseQuery()->whereIn('id', $ids)->get();
        if (count($posts) === 0) {
            return $this->jsonError('Không tìm thấy mục nào để xóa!');
        }
        
        $allowedIds = [];
        $unauthorizedCount = 0;
        
        foreach ($posts as $post) {
            if ($this->canModify($post)) {
                $allowedIds[$post->id] = $post->id;
            } else {
                $unauthorizedCount++;
            }
        }
        
        if (empty($allowedIds)) {
            return $this->jsonError('Bạn không có quyền xóa các mục đã chọn!');
        }
        
        $allowedIds = array_values($allowedIds);
        $this->postService->deletePost($allowedIds);
        
        $msg = 'Đã xóa ' . count($allowedIds) . ' bài viết thành công!';
        if ($unauthorizedCount > 0) {
            $msg .= " Đã bỏ qua $unauthorizedCount mục do không có quyền.";
        }
        
        return $this->jsonSuccess($msg);
    }

    // ============================================================
    //  HELPER METHODS
    // ============================================================

    /**
     * Helper tạo query cơ bản bỏ qua scope ngôn ngữ
     */
    private function getBaseQuery() {
        return PostModel::withoutGlobalScope('lang');
    }

    /**
     * Lấy danh sách chuyên mục cho module Post
     */
    private function getCategories() {
        return CategoryModel::getTreeForAdminByModule($this->moduleId);
    }

    /**
     * Xử lý redirect sau khi lưu
     */
    private function handleSaveRedirect(Request $request, $id) {
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue' && $id) {
            return $this->redirect(route($this->routePrefix . '.edit', ['id' => $id]));
        } elseif ($saveAction === 'new') {
            return $this->redirect(route($this->routePrefix . '.create'));
        }
        return $this->redirect(route($this->routePrefix . '.index'));
    }
}