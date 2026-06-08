<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use CategoryModel;
use CfCodeModel;

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
                $matchKeyword = $keyword === '' || mb_stripos($cat->ten, $keyword) !== false || (string)$cat->id_code === $keyword;
                $matchStatus = $status === '' || (string)$cat->hien_thi === $status;
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
        
        $cfCode = CfCodeModel::query()->where('id', $id)->first();
        if (!$cfCode) return $this->redirect(route('admin.category.index'));
        
        $catQuery = CategoryModel::query();
        $catQuery->use_lang = false; // Fetch all translations
        $translations = $catQuery->where('id_code', $id)->get();
        
        // Chuyển hóa dữ liệu để render lên form
        $item = [
            'id'        => $id, 
            'id_loai'   => $cfCode->id_loai, 
            'module'    => $cfCode->module, 
            'so_thu_tu' => $cfCode->so_thu_tu, 
            'hien_thi'  => $cfCode->hien_thi,
            'hinh_anh'  => ''
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["ten"][$lang] = $t->ten;
            $item["alias"][$lang] = $t->alias;
            $item["mo_ta"][$lang] = $t->mo_ta;
            $item["noi_dung"][$lang] = $t->noi_dung;
            if (empty($item["hinh_anh"])) {
                $item["hinh_anh"] = $t->hinh_anh;
            }
        }
        
        $parentCategories = CategoryModel::getTreeForAdmin();
        $modules = ModuleModel::query()->where('hide', 1)->orderBy('stt', 'ASC')->get();
        
        return $this->render('admin.category.form', compact('langs', 'item', 'parentCategories', 'modules'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        $id_loai = (int)$request->input('id_loai', 0);
        $module = $request->input('module', 'san_pham');
        $so_thu_tu = (int)$request->input('so_thu_tu', 0);
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Lưu vào bảng gốc
        $id_code = CfCodeModel::insert([
            'ten'       => $ten_vi,
            'hinh_anh'  => $request->input('hinh_anh') ?? '',
            'id_loai'   => $id_loai,
            'module'    => $module,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi'  => $hien_thi
        ]);

        if ($id_code) {
            // 2. Lưu vào bảng ngôn ngữ
            $langs = config('lang', [['code' => 'vi']]);
            foreach ($langs as $l) {
                $c = $l['code'];
                CategoryModel::insert([
                    'id_code'   => $id_code,
                    'lang'      => $c,
                    'ten'       => $request->input('ten')[$c] ?? '',
                    'alias'     => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                    'mo_ta'     => $request->input('mo_ta')[$c] ?? '',
                    'noi_dung'  => $request->input('noi_dung')[$c] ?? '',
                    'hinh_anh'  => $request->input('hinh_anh') ?? '',
                    'id_loai'   => $id_loai,
                    'module'    => $module,
                    'so_thu_tu' => $so_thu_tu,
                    'hien_thi'  => $hien_thi
                ]);
            }
        }
        
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $id_loai = (int)$request->input('id_loai', 0);
        $module = $request->input('module', 'san_pham');
        $so_thu_tu = (int)$request->input('so_thu_tu', 0);
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // Kiểm tra chống loop (cha không thể nhận chính nó làm con)
        if ($id == $id_loai) {
            $id_loai = 0;
        }

        // 1. Cập nhật bảng gốc
        CfCodeModel::query()->where('id', $id)->update([
            'ten'       => $ten_vi,
            'hinh_anh'  => $request->input('hinh_anh') ?? '',
            'id_loai'   => $id_loai,
            'module'    => $module,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi'  => $hien_thi
        ]);

        // 2. Cập nhật hoặc tạo mới bản dịch
        $langs = config('lang', [['code' => 'vi']]);
        foreach ($langs as $l) {
            $c = $l['code'];
            $catQuery = CategoryModel::query();
            $catQuery->use_lang = false; // Bỏ qua bộ lọc ngôn ngữ toàn cục
            
            $exists = $catQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = [
                'ten'       => $request->input('ten')[$c] ?? '',
                'alias'     => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                'mo_ta'     => $request->input('mo_ta')[$c] ?? '',
                'noi_dung'  => $request->input('noi_dung')[$c] ?? '',
                'hinh_anh'  => $request->input('hinh_anh') ?? '',
                'id_loai'   => $id_loai,
                'module'    => $module,
                'so_thu_tu' => $so_thu_tu,
                'hien_thi'  => $hien_thi
            ];
            
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
        
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Cập nhật trạng thái hiển thị (hoặc bất kỳ trường boolean nào) qua AJAX
     */
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id');
        $field = $request->input('field', 'hien_thi'); // Mặc định là hien_thi
        $value = (int)$request->input('value', 0);

        // Danh sách các cột được phép update qua AJAX để bảo mật
        $allowedFields = ['hien_thi']; 
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ']);
        }

        if ($id > 0) {
            CfCodeModel::query()->where('id', $id)->update([$field => $value]);
            
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
                CfCodeModel::query()->where('id', $delId)->delete();
                
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
                CfCodeModel::query()->where('id', $delId)->delete();
                
                $catQuery = CategoryModel::query();
                $catQuery->use_lang = false;
                $catQuery->where('id_code', $delId)->delete();
            }
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
}
