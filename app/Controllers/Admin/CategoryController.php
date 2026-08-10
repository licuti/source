<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\CategoryModel;
use App\Requests\Admin\CategoryRequest;
use App\Services\CategoryService;

class CategoryController extends BaseAdminController {
    
    private CategoryService $categoryService;

    public function __construct() {
        parent::__construct();
        $this->categoryService = new CategoryService();
    }
    
    /**
     * Hiển thị danh mục dạng cây hoặc danh sách tìm kiếm
     */
    public function index(Request $request) {
        $keyword = trim($request->input('keyword', ''));
        $status  = $request->input('status', '');
        $page    = max(1, (int)$request->input('page', 1));
        $limit   = 10;
        $langs   = $this->langs;
        
        if ($keyword !== '' || $status !== '') {
            $isSearch = true;
            
            $query = CategoryModel::withoutGlobalScope('lang')
                ->where('lang', $this->primaryLang);
                
            if ($keyword !== '') {
                $query->whereRaw("(title LIKE ? OR id_code = ?)", ["%$keyword%", $keyword]);
            }
            if ($status !== '') {
                $query->where('status', $status);
            }
            
            $categories = $query->orderBy('sort_order', 'ASC')
                                ->orderBy('id', 'DESC')
                                ->paginate($limit, $page, 'page');
                                
            $totalRows = $categories->total();
            $totalPages = $categories->lastPage();
        } else {
            $isSearch = false;
            $categories = CategoryModel::getTreeForAdmin(); 
            $totalRows = CategoryModel::withoutGlobalScope('lang')->where('lang', $this->primaryLang)->count();
            $totalPages = 1;
        }

        // Build map ['id_code' => ['vi' => id, 'en' => id]]
        $allTrans = CategoryModel::withoutGlobalScope('lang')->get('id_code, lang, id');
        $translations = [];
        foreach ($allTrans as $t) {
            $translations[$t->id_code][$t->lang] = $t->id;
        }

        return $this->render('admin.category.index', compact('categories', 'isSearch', 'keyword', 'status', 'page', 'totalPages', 'totalRows', 'langs', 'limit', 'translations'));
    }

    /**
     * Helper tạo mảng dữ liệu dùng chung cho form
     */
    private function getFormData(Request $request, $item = [], array $translations = []) {
        $langs = $this->langs;
        $parentCategories = CategoryModel::getTreeForAdmin();
        $modules = $this->getActiveModules();
        $langCode = $request->input('lang', $this->primaryLang);
        $currentLangName = $this->getLangName($langCode);
        
        return compact('langs', 'parentCategories', 'modules', 'item', 'langCode', 'currentLangName', 'translations');
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $item         = [];
        $sourceId     = (int)$request->input('source_id', 0);
        $isTranslating = false; 

        if ($sourceId > 0) {
            $sourceItem = CategoryModel::withoutGlobalScope('lang')->where('id_code', $sourceId)->first();
            if ($sourceItem) {
                $item          = $sourceItem->toArray();
                $item['id']    = ''; 
                $item['id_code'] = $sourceId;
                $isTranslating = true;
            }
        }

        $formData = $this->getFormData($request, $item);
        $formData['isTranslating'] = $isTranslating;
        return $this->render('admin.category.form', $formData);
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = (int)$id; 
        $langCode = $request->input('lang', $this->primaryLang);

        $itemObj = CategoryModel::withoutGlobalScope('lang')
            ->where('id_code', $id)
            ->where('lang', $langCode)
            ->first();

        if (!$itemObj) {
            $primaryObj = CategoryModel::withoutGlobalScope('lang')
                ->where('id_code', $id)
                ->where('lang', $this->primaryLang)
                ->first();

            if (!$primaryObj) return $this->redirect(route('admin.category.index'));

            $item = $primaryObj->toArray();
            $item['id'] = ''; 
            $item['lang'] = $langCode;
            $item['title'] = '';
            $item['slug'] = '';
            $item['description'] = '';
            $item['content'] = '';
            $item['seo_title'] = '';
            $item['seo_description'] = '';
            $item['keyword'] = '';
            $item['seo_head'] = '';
            $item['seo_body'] = '';
            $item['seo_schema'] = '';
            $item['seo_canonical'] = '';
        } else {
            $item = $itemObj->toArray();
        }

        $translationsMap = [];
        $allTrans = CategoryModel::withoutGlobalScope('lang')->where('id_code', $id)->get('id, lang');
        foreach ($allTrans as $t) {
            $translationsMap[$t->lang] = $t->id;
        }

        return $this->render('admin.category.form', $this->getFormData($request, $item, $translationsMap));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(CategoryRequest $request) {
        $validatedData = $request->validated();
        $lang     = $validatedData['lang'] ?? $this->primaryLang;
        $sourceId = (int)($validatedData['id'] ?? 0);

        if ($sourceId > 0 && !CategoryModel::withoutGlobalScope('lang')->where('id_code', $sourceId)->first()) {
            return $this->redirect(route('admin.category.index'));
        }

        $categoryId = $this->categoryService->saveCategory($validatedData, $this->primaryLang);

        return $this->handleSaveRedirect($request, $categoryId, $lang);
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(CategoryRequest $request, $id) {
        $id = (int)$id; 
        $validatedData = $request->validated();
        $lang = $validatedData['lang'] ?? $this->primaryLang;

        if (!CategoryModel::withoutGlobalScope('lang')->where('id_code', $id)->first()) {
            return $this->redirect(route('admin.category.index'));
        }

        $categoryId = $this->categoryService->updateCategory($id, $validatedData, $this->primaryLang);

        return $this->handleSaveRedirect($request, $categoryId, $lang);
    }

    /**
     * Cập nhật trạng thái hiển thị qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id'); 
        $field = $request->input('field', 'status');
        $value = (int)$request->input('value', 0);

        $allowedFields = ['status', 'is_featured'];
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ']);
        }

        if ($id > 0) {
            CategoryModel::withoutGlobalScope('lang')->where('id_code', $id)->update([$field => $value]);
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = (int)$id; 
        $this->categoryService->deleteCategory($id);
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (empty($ids) || !is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào hợp lệ']);
        }

        if ($this->categoryService->deleteMultipleCategories($ids)) {
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Đã xảy ra lỗi khi xóa']);
    }

    /**
     * Xử lý redirect sau khi lưu
     */
    private function handleSaveRedirect(Request $request, int $id, string $lang) {
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue' && $id) {
            return $this->redirect(route('admin.category.edit', ['id' => $id, 'lang' => $lang]));
        }
        return $this->redirect(route('admin.category.index'));
    }
}
