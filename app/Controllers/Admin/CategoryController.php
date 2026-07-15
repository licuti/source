<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
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
        $page    = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;

        if ($keyword !== '' || $status !== '') {
            $allCategories = CategoryModel::getAllForAdmin();
            $filtered = [];
            foreach ($allCategories as $cat) {
                // $cat->title sẽ lấy tự động qua hook __get -> getTranslatedAttribute
                $matchKeyword = $keyword === '' || mb_stripos($cat->title, $keyword) !== false || (string)$cat->id === $keyword;
                $matchStatus = $status === '' || (string)$cat->status === $status;
                if ($matchKeyword && $matchStatus) {
                    $filtered[] = $cat;
                }
            }
            
            $totalRows = count($filtered);
            $totalPages = max(1, ceil($totalRows / $limit));
            $offset = ($page - 1) * $limit;
            
            $sliced = array_slice($filtered, $offset, $limit);
            $categories = new \App\Core\Paginator($sliced, $totalRows, $limit, $page);
            $isSearch = true;
        } else {
            // Chế độ xem mặc định: Cây danh mục (không phân trang)
            $categories = CategoryModel::getTreeForAdmin();
            $isSearch = false;
            $totalRows = CategoryModel::count();
            $totalPages = 1;
        }

        $langs = $this->langs;
        
        // Tạo mảng translations để view dùng hiển thị cờ ngôn ngữ:
        // Cấu trúc: $translations[$categoryId][$langCode] = $translationId
        $translations = [];
        $extractTranslations = function($cats) use (&$extractTranslations, &$translations) {
            foreach ($cats as $cat) {
                $translations[$cat->id] = [];
                if ($cat->relationLoaded('translations')) {
                    foreach ($cat->getRelation('translations') as $t) {
                        $translations[$cat->id][$t->lang] = $t->id;
                    }
                }
                if (!empty($cat->children)) {
                    $extractTranslations($cat->children);
                }
            }
        };
        $extractTranslations($categories);

        return $this->render('admin.category.index', compact('categories', 'isSearch', 'keyword', 'status', 'page', 'totalPages', 'totalRows', 'langs', 'translations', 'limit'));
    }

    /**
     * Helper tạo mảng dữ liệu dùng chung cho create và edit
     */
    private function getFormData(Request $request, $item = []) {
        $langs = $this->langs;
        $parentCategories = CategoryModel::getTreeForAdmin();
        $modules = ModuleModel::where('hide', 1)->orderBy('stt', 'ASC')->get();
        $langCode = $request->input('lang', $this->primaryLang);
        
        $currentLangName = 'Unknown';
        foreach ($langs as $l) {
            if ($l['code'] === $langCode) {
                $currentLangName = $l['name'];
                break;
            }
        }
        
        $translations = [];
        if (!empty($item['id'])) {
            $allTrans = CategoryTranslationModel::where('category_id', $item['id'])->get();
            foreach ($allTrans as $t) {
                $translations[$t->lang] = $t->id;
            }
        }
        
        return compact('langs', 'parentCategories', 'modules', 'item', 'langCode', 'currentLangName', 'translations');
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $item = [];
        $sourceId = (int)$request->input('source_id', 0);
        
        if ($sourceId > 0) {
            $sourceItem = CategoryModel::find($sourceId);
            if ($sourceItem) {
                $item = $sourceItem->toArray();
                $item['id'] = $sourceItem->id;
            }
        }
        
        return $this->render('admin.category.form', $this->getFormData($request, $item));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $itemObj = CategoryModel::find((int)$id);
        if (!$itemObj) return $this->redirect(route('admin.category.index'));
        
        $item = is_object($itemObj) && method_exists($itemObj, 'toArray') ? $itemObj->toArray() : (array)$itemObj;
        
        // Lấy bản dịch theo ngôn ngữ đang chọn để nạp vào form
        $langCode = $request->input('lang', $this->primaryLang);
        $translation = $itemObj->getTranslation($langCode);
        if ($translation) {
            $item = array_merge($item, $translation->toArray());
        } else {
            // Chưa có bản dịch cho ngôn ngữ này, xóa trắng các trường dịch
            foreach ($itemObj->getTranslatedAttributesArray() as $k => $v) {
                $item[$k] = '';
            }
        }
        
        return $this->render('admin.category.form', $this->getFormData($request, $item));
    }

    /**
     * Helper tạo mảng dữ liệu Category chung
     */
    private function buildCategoryData(array $data): array {
        return [
            'image'          => $data['image'] ?? '',
            'banner'         => $data['banner'] ?? '',
            'parent_id'      => (int)($data['parent_id'] ?? 0),
            'module'         => $data['module'] ?? 0,
            'sort_order'     => (int)($data['sort_order'] ?? 0),
            'status'         => isset($data['status']) ? 1 : 0,
            'is_featured'    => isset($data['is_featured']) ? 1 : 0,
        ];
    }

    /**
     * Helper tạo mảng dữ liệu Translation
     */
    private function buildTranslationData(array $data, int $categoryId, string $lang): array {
        return [
            'category_id'    => $categoryId,
            'lang'           => $lang,
            'title'          => $data['title'] ?? '',
            'slug'           => empty($data['slug']) ? str_slug($data['title'] ?? '') : $data['slug'],
            'description'    => $data['description'] ?? '',
            'content'        => $data['content'] ?? '',
            'seo_title'      => $data['seo_title'] ?? '',
            'keyword'        => $data['keyword'] ?? '',
            'seo_description'=> $data['seo_description'] ?? '',
            'seo_head'       => $data['seo_head'] ?? '',
            'seo_body'       => $data['seo_body'] ?? '',
            'seo_schema'     => $data['seo_schema'] ?? '',
            'seo_canonical'  => $data['seo_canonical'] ?? '',
        ];
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(CategoryRequest $request) {
        $validatedData = $request->validated();
        $lang = $validatedData['lang'] ?? $this->primaryLang;
        $sourceId = (int)($validatedData['id'] ?? 0); // Thêm bản dịch từ 1 category đã có
        
        if ($sourceId > 0) {
            $categoryId = $sourceId;
            $categoryData = $this->buildCategoryData($validatedData);
            CategoryModel::where('id', $categoryId)->update($categoryData);
        } else {
            $categoryData = $this->buildCategoryData($validatedData);
            $categoryData['created_at'] = $validatedData['created_at'] ?? date('Y-m-d H:i:s');
            $categoryId = CategoryModel::insertGetId($categoryData);
        }
        
        if ($categoryId) {
            $transData = $this->buildTranslationData($validatedData, $categoryId, $lang);
            CategoryTranslationModel::updateOrCreate(
                ['category_id' => $categoryId, 'lang' => $lang],
                $transData
            );
        }
        
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
        
        $categoryData = $this->buildCategoryData($validatedData);
        if (!empty($validatedData['created_at'])) {
            $categoryData['created_at'] = $validatedData['created_at'];
        }
        CategoryModel::where('id', $id)->update($categoryData);
        
        $transData = $this->buildTranslationData($validatedData, $id, $lang);
        CategoryTranslationModel::updateOrCreate(
            ['category_id' => $id, 'lang' => $lang],
            $transData
        );
        
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
        $id = (int)$id;
        
        $allIdsString = CategoryModel::getChildrenIds($id, true);
        $ids = array_filter(explode(',', $allIdsString));

        if (!empty($ids)) {
            CategoryTranslationModel::whereIn('category_id', $ids)->delete();
            CategoryModel::whereIn('id', $ids)->delete();
        }
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Xóa hàng loạt
     */
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        
        if (!empty($ids) && is_array($ids)) {
            $allIdsToDelete = [];
            foreach ($ids as $id) {
                $childIdsStr = CategoryModel::getChildrenIds($id, true);
                $childIds = array_filter(explode(',', $childIdsStr));
                $allIdsToDelete = array_merge($allIdsToDelete, $childIds);
            }

            $allIdsToDelete = array_unique($allIdsToDelete);

            if (!empty($allIdsToDelete)) {
                CategoryTranslationModel::whereIn('category_id', $allIdsToDelete)->delete();
                CategoryModel::whereIn('id', $allIdsToDelete)->delete();
            }
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
}
