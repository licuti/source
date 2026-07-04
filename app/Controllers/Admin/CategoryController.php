<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use App\Models\CategoryModel;

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
            // Giải pháp 2: Trả về danh sách phẳng để lọc và phân trang
            $allCategories = CategoryModel::getAllForAdmin();
            $filtered = [];
            foreach ($allCategories as $cat) {
                $matchKeyword = $keyword === '' || mb_stripos($cat->title, $keyword) !== false || (string)$cat->id_code === $keyword;
                $matchStatus = $status === '' || (string)$cat->status === $status;
                if ($matchKeyword && $matchStatus) {
                    $filtered[] = $cat;
                }
            }
            
            $totalRows = count($filtered);
            $totalPages = max(1, ceil($totalRows / $limit));
            $offset = ($page - 1) * $limit;
            
            $categories = array_slice($filtered, $offset, $limit);
            $isSearch = true;
        } else {
            // Chế độ xem mặc định: Cây danh mục (không phân trang)
            $categories = CategoryModel::getTreeForAdmin();
            $isSearch = false;
            $totalRows = count(CategoryModel::getAllForAdmin());
            $totalPages = 1;
        }

        return $this->render('admin.category.index', compact('categories', 'isSearch', 'keyword', 'status', 'page', 'totalPages', 'totalRows'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $parentCategories = CategoryModel::getTreeForAdmin();
        $modules = ModuleModel::query()->where('hide', 1)->orderBy('stt', 'ASC')->get();
        return $this->render('admin.category.form', compact('langs', 'parentCategories', 'modules'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $catQuery = CategoryModel::query();
        $catQuery->use_lang = false; // Fetch all translations
        $translations = $catQuery->where('id_code', $id)->get();
        if (empty($translations)) return $this->redirect(route('admin.category.index'));
        
        $first = $translations[0];
        // Chuyển hóa dữ liệu để render lên form
        $item = [
            'id'          => $id, 
            'parent_id'   => $first->parent_id, 
            'module'      => $first->module, 
            'sort_order'  => $first->sort_order, 
            'status'   => $first->status,
            'is_featured' => $first->is_featured,
            'image'       => $first->image,
            'banner'      => $first->banner,
            'created_at'  => $first->created_at,
            'updated_at'  => $first->updated_at
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["title"][$lang] = $t->title;
            $item["slug"][$lang] = $t->slug;
            $item["description"][$lang] = $t->description;
            $item["content"][$lang] = $t->content;
            $item["seo_title"][$lang] = $t->seo_title;
            $item["keyword"][$lang] = $t->keyword;
            $item["seo_description"][$lang] = $t->seo_description;
            $item["seo_head"][$lang] = $t->seo_head;
            $item["seo_body"][$lang] = $t->seo_body;
        }
        
        $parentCategories = CategoryModel::getTreeForAdmin();
        $modules = ModuleModel::query()->where('hide', 1)->orderBy('stt', 'ASC')->get();
        
        return $this->render('admin.category.form', compact('langs', 'item', 'parentCategories', 'modules'));
    }

    /**
     * Helper tạo mảng dữ liệu category chung
     */
    private function buildCategoryData(Request $request, string $langCode): array {
        return [
            'title'          => $request->input('title')[$langCode] ?? '',
            'slug'           => empty($request->input('slug')[$langCode]) ? str_slug($request->input('title')[$langCode] ?? '') : $request->input('slug')[$langCode],
            'description'    => $request->input('description')[$langCode] ?? '',
            'content'        => $request->input('content')[$langCode] ?? '',
            'seo_title'      => $request->input('seo_title')[$langCode] ?? '',
            'keyword'        => $request->input('keyword')[$langCode] ?? '',
            'seo_description'=> $request->input('seo_description')[$langCode] ?? '',
            'seo_head'       => $request->input('seo_head')[$langCode] ?? '',
            'seo_body'       => $request->input('seo_body')[$langCode] ?? '',
            'image'          => $request->input('image', ''),
            'banner'         => $request->input('banner', ''),
            'parent_id'      => (int)$request->input('parent_id', 0),
            'module'         => $request->input('module', 0),
            'sort_order'     => (int)$request->input('sort_order', 0),
            'status'      => $request->input('status') !== null ? 1 : 0,
            'is_featured'    => $request->input('is_featured') !== null ? 1 : 0,
        ];
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        $langs = config('lang', [['code' => 'vi']]);
        $firstLang = $langs[0]['code'];
        
        $firstLangData = $this->buildCategoryData($request, $firstLang);
        $firstLangData['lang'] = $firstLang;
        $firstLangData['id_code'] = 0;
        $firstLangData['created_at'] = $request->input('created_at', date('Y-m-d H:i:s'));
        
        $insertedId = CategoryModel::insertGetId($firstLangData);
        if ($insertedId) {
            $id_code = $insertedId;
            $catQuery = CategoryModel::query();
            $catQuery->use_lang = false;
            $catQuery->where('id', $insertedId)->update(['id_code' => $id_code]);
            
            foreach ($langs as $index => $l) {
                if ($index === 0) continue;
                $c = $l['code'];
                $langData = $this->buildCategoryData($request, $c);
                $langData['id_code'] = $id_code;
                $langData['lang'] = $c;
                $langData['created_at'] = $firstLangData['created_at'];
                CategoryModel::insert($langData);
            }
        }
        
        if (($request->input('save_action') ?? '') === 'continue') {
            return $this->redirect(route('admin.category.edit', ['id' => $id_code ?? 0]));
        }
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $parent_id = (int)$request->input('parent_id', 0);
        
        // Kiểm tra chống loop (cha không thể nhận chính nó làm con)
        if ($id == $parent_id) {
            // we won't set it to self, the builder will use the updated value. However, we should just let builder use request->input.
            // to fix we override $_POST basically
            $_POST['parent_id'] = 0; 
        }

        $langs = config('lang', [['code' => 'vi']]);
        foreach ($langs as $l) {
            $c = $l['code'];
            $catQuery = CategoryModel::query();
            $catQuery->use_lang = false; 
            $exists = $catQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = $this->buildCategoryData($request, $c);
            // created_at is only updated here if the user changed it in the UI.
            if ($request->input('created_at')) {
                $data['created_at'] = $request->input('created_at');
            }
            
            if ($exists) {
                $updateQuery = CategoryModel::query();
                $updateQuery->use_lang = false;
                $updateQuery->where('id', $exists->id)->update($data);
            } else {
                $data['id_code'] = $id;
                $data['lang'] = $c;
                CategoryModel::insert($data);
            }
        }
        
        if (($request->input('save_action') ?? '') === 'continue') {
            return $this->redirect(route('admin.category.edit', ['id' => $id]));
        }
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Cập nhật trạng thái hiển thị (hoặc bất kỳ trường boolean nào) qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id');
        $field = $request->input('field', 'status'); // Mặc định là status
        $value = (int)$request->input('value', 0);

        // Danh sách các cột được phép update qua AJAX để bảo mật
        $allowedFields = ['status', 'is_featured'];
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ']);
        }

        if ($id > 0) {
            // Update in db_categories
            $catQuery = CategoryModel::query();
            $catQuery->use_lang = false;
            $catQuery->where('id_code', $id)->update([$field => $value]);

            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
    }

    /**
     * Xóa 1 dòng (xóa luôn cả các danh mục con nếu có)
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        // Lấy tất cả danh mục con của ID này
        $allIdsString = CategoryModel::getChildrenIds($id, true);
        $ids = array_filter(explode(',', $allIdsString));

        if (!empty($ids)) {
            foreach ($ids as $delId) {
                $catQuery = CategoryModel::query();
                $catQuery->use_lang = false;
                $catQuery->where('id_code', $delId)->delete();
            }
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

            foreach ($allIdsToDelete as $delId) {
                $catQuery = CategoryModel::query();
                $catQuery->use_lang = false;
                $catQuery->where('id_code', $delId)->delete();
            }
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
}
