<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Database\DB;
use App\Models\CategoryModel;
use App\Models\CategoryTranslationModel;
use App\Requests\Admin\CategoryRequest;

class CategoryController extends BaseAdminController {
    
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
            
            // Tìm kiếm bằng SQL JOIN thay vì vòng lặp PHP trên RAM
            $query = CategoryModel::query()
                ->select('#_categories.*')
                ->join('#_category_translations as t', 't.category_id', '=', '#_categories.id')
                ->where('t.lang', $this->primaryLang);
                
            if ($keyword !== '') {
                $query->whereRaw("(t.title LIKE ? OR #_categories.id = ?)", ["%$keyword%", $keyword]);
            }
            if ($status !== '') {
                $query->where('#_categories.status', $status);
            }
            
            // QueryBuilder paginate() tự động đếm count() và hỗ trợ eager load qua with()
            $categories = $query->with('translations')
                                ->orderBy('#_categories.sort_order', 'ASC')
                                ->orderBy('#_categories.id', 'DESC')
                                ->paginate($limit, $page, 'page');
                                
            $totalRows = $categories->total();
            $totalPages = $categories->lastPage();
        } else {
            // Chế độ mặc định: Cây danh mục
            $isSearch = false;
            $categories = CategoryModel::getTreeForAdmin(); // getTreeForAdmin đã tự động eager load 'translations'
            $totalRows = CategoryModel::count();
            $totalPages = 1;
        }

        return $this->render('admin.category.index', compact('categories', 'isSearch', 'keyword', 'status', 'page', 'totalPages', 'totalRows', 'langs', 'limit'));
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
     * Tách Data Category chuẩn hóa (DRY)
     */
    private function extractCategoryData(array $validatedData, int $parentId): array {
        return [
            'image'       => $validatedData['image'] ?? '',
            'banner'      => $validatedData['banner'] ?? '',
            'parent_id'   => $parentId,
            'module'      => $validatedData['module'] ?? 0,
            'sort_order'  => (int)($validatedData['sort_order'] ?? 0),
            'status'      => isset($validatedData['status']) ? 1 : 0,
            'is_featured' => isset($validatedData['is_featured']) ? 1 : 0,
        ];
    }

    /**
     * Tách Data Dịch thuật chuẩn hóa (DRY)
     */
    private function extractTranslationData(array $validatedData, int $categoryId, string $lang): array {
        return [
            'category_id'     => $categoryId,
            'lang'            => $lang,
            'title'           => $validatedData['title'] ?? '',
            'slug'            => empty($validatedData['slug']) ? str_slug($validatedData['title'] ?? '') : $validatedData['slug'],
            'description'     => $validatedData['description'] ?? '',
            'content'         => $validatedData['content'] ?? '',
            'seo_title'       => $validatedData['seo_title'] ?? '',
            'keyword'         => $validatedData['keyword'] ?? '',
            'seo_description' => $validatedData['seo_description'] ?? '',
            'seo_head'        => $validatedData['seo_head'] ?? '',
            'seo_body'        => $validatedData['seo_body'] ?? '',
            'seo_schema'      => $validatedData['seo_schema'] ?? '',
            'seo_canonical'   => $validatedData['seo_canonical'] ?? '',
        ];
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $item         = [];
        $sourceId     = (int)$request->input('source_id', 0);
        $isTranslating = false; // Đang thêm bản dịch mới cho category có sẵn

        if ($sourceId > 0) {
            $sourceItem = CategoryModel::find($sourceId);
            if ($sourceItem) {
                $item          = $sourceItem->toArray();
                $item['id']    = $sourceItem->id;
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
        $itemObj = CategoryModel::query()->with('translations')->find((int)$id);
        if (!$itemObj) return $this->redirect(route('admin.category.index'));

        $item = $itemObj->toArray();
        $langCode = $request->input('lang', $this->primaryLang);
        $translation = $itemObj->getTranslation($langCode);

        if ($translation) {
            $translationData = $translation->toArray();
            unset($translationData['id']); // Ngăn đè ID của category bằng ID của bản dịch
            $item = array_merge($item, $translationData);
        } else {
            foreach ($itemObj->getTranslatedAttributesArray() as $k => $v) {
                $item[$k] = '';
            }
        }

        // Build map ['vi' => id, 'en' => id] để polylang widget biết bản dịch nào đã có
        $translationsMap = [];
        foreach ($itemObj->translations ?? [] as $t) {
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
        $parentId = (int)($validatedData['parent_id'] ?? 0);

        // Kiểm tra source trước transaction để tránh rollBack không cần thiết
        if ($sourceId > 0 && !CategoryModel::find($sourceId)) {
            return $this->redirect(route('admin.category.index'));
        }

        $categoryId = DB::transaction(function () use ($validatedData, $sourceId, $parentId, $lang) {
            $categoryData = $this->extractCategoryData($validatedData, $parentId);

            if ($sourceId > 0) {
                CategoryModel::where('id', $sourceId)->update($categoryData);
                $categoryId = $sourceId;
            } else {
                $categoryData['created_at'] = $validatedData['created_at'] ?? date('Y-m-d H:i:s');
                $categoryId = CategoryModel::insertGetId($categoryData);
            }

            CategoryTranslationModel::updateOrCreate(
                ['category_id' => $categoryId, 'lang' => $lang],
                $this->extractTranslationData($validatedData, $categoryId, $lang)
            );

            return $categoryId;
        });

        if (($validatedData['save_action'] ?? '') === 'continue') {
            return $this->redirect(route('admin.category.edit', ['id' => $categoryId, 'lang' => $lang]));
        }
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(CategoryRequest $request, $id) {
        $id = (int)$id;
        $validatedData = $request->validated();
        $lang = $validatedData['lang'] ?? $this->primaryLang;

        if (!CategoryModel::find($id)) {
            return $this->redirect(route('admin.category.index'));
        }

        // --- XỬ LÝ CHỐNG VÒNG LẶP ĐỆ QUY (INFINITE LOOP) ---
        $parentId = (int)($validatedData['parent_id'] ?? 0);
        if ($parentId === $id) {
            $parentId = 0;
        } elseif ($parentId > 0) {
            $childIds = array_filter(explode(',', CategoryModel::getChildrenIds($id, false)));
            if (in_array($parentId, $childIds)) {
                $parentId = 0;
            }
        }

        DB::transaction(function () use ($validatedData, $id, $parentId, $lang) {
            $updateData = $this->extractCategoryData($validatedData, $parentId);
            if (!empty($validatedData['created_at'])) {
                $updateData['created_at'] = $validatedData['created_at'];
            }
            CategoryModel::where('id', $id)->update($updateData);

            CategoryTranslationModel::updateOrCreate(
                ['category_id' => $id, 'lang' => $lang],
                $this->extractTranslationData($validatedData, $id, $lang)
            );
        });

        if (($validatedData['save_action'] ?? '') === 'continue') {
            return $this->redirect(route('admin.category.edit', ['id' => $id, 'lang' => $lang]));
        }
        return $this->redirect(route('admin.category.index'));
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
            CategoryModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
    }

    /**
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id  = (int)$id;
        $ids = array_filter(explode(',', CategoryModel::getChildrenIds($id, true)));

        if (!empty($ids)) {
            DB::transaction(function () use ($ids) {
                CategoryTranslationModel::whereIn('category_id', $ids)->delete();
                CategoryModel::whereIn('id', $ids)->delete();
            });
        }
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

        $allIdsToDelete = [];
        foreach ($ids as $id) {
            $childIds = array_filter(explode(',', CategoryModel::getChildrenIds((int)$id, true)));
            $allIdsToDelete = array_merge($allIdsToDelete, $childIds);
        }
        $allIdsToDelete = array_unique($allIdsToDelete);

        if (!empty($allIdsToDelete)) {
            try {
                DB::transaction(function () use ($allIdsToDelete) {
                    CategoryTranslationModel::whereIn('category_id', $allIdsToDelete)->delete();
                    CategoryModel::whereIn('id', $allIdsToDelete)->delete();
                });
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => 'Đã xảy ra lỗi khi xóa']);
            }
        }
        return $this->json(['success' => true]);
    }
}
