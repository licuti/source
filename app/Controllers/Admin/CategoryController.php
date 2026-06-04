<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use CategoryModel;
use CfCodeModel;

class CategoryController extends BaseAdminController {
    
    /**
     * Hiển thị danh sách danh mục
     */
    public function index(Request $request) {
        $categories = CategoryModel::query()->where('lang', 'vi')->orderBy('so_thu_tu', 'ASC')->orderBy('id_code', 'DESC')->get();
        
        // Map tên danh mục cha cho dễ nhìn
        $parentMap = [];
        foreach ($categories as $cat) {
            $parentMap[$cat->id_code] = $cat->ten;
        }

        return $this->render('admin.category.index', compact('categories', 'parentMap'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        $parentCategories = CategoryModel::getTree();
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
            'id' => $id, 
            'id_loai' => $cfCode->id_loai, 
            'module' => $cfCode->module, 
            'so_thu_tu' => $cfCode->so_thu_tu, 
            'hien_thi' => $cfCode->hien_thi,
            'hinh_anh' => ''
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
        
        $parentCategories = CategoryModel::getTree();
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
            'ten' => $ten_vi,
            'hinh_anh' => $request->input('hinh_anh') ?? '',
            'id_loai' => $id_loai,
            'module' => $module,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi' => $hien_thi
        ]);

        if ($id_code) {
            // 2. Lưu vào bảng ngôn ngữ
            $langs = config('lang', [['code' => 'vi']]);
            foreach ($langs as $l) {
                $c = $l['code'];
                CategoryModel::insert([
                    'id_code' => $id_code,
                    'lang' => $c,
                    'ten' => $request->input('ten')[$c] ?? '',
                    'alias' => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                    'mo_ta' => $request->input('mo_ta')[$c] ?? '',
                    'noi_dung' => $request->input('noi_dung')[$c] ?? '',
                    'hinh_anh' => $request->input('hinh_anh') ?? '',
                    'id_loai' => $id_loai,
                    'module' => $module,
                    'so_thu_tu' => $so_thu_tu,
                    'hien_thi' => $hien_thi
                ]);
            }
        }
        
        return $this->redirect(route('admin.category.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $id_loai = (int)$request->input('id_loai', 0);
        $module = $request->input('module', 'san_pham');
        $so_thu_tu = (int)$request->input('so_thu_tu', 0);
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Cập nhật bảng gốc
        CfCodeModel::query()->where('id', $id)->update([
            'ten' => $ten_vi,
            'hinh_anh' => $request->input('hinh_anh') ?? '',
            'id_loai' => $id_loai,
            'module' => $module,
            'so_thu_tu' => $so_thu_tu,
            'hien_thi' => $hien_thi
        ]);

        // 2. Cập nhật hoặc tạo mới bản dịch
        $langs = config('lang', [['code' => 'vi']]);
        foreach ($langs as $l) {
            $c = $l['code'];
            $catQuery = CategoryModel::query();
            $catQuery->use_lang = false; // Bỏ qua bộ lọc ngôn ngữ toàn cục
            
            $exists = $catQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = [
                'ten' => $request->input('ten')[$c] ?? '',
                'alias' => empty($request->input('alias')[$c]) ? str_slug($request->input('ten')[$c] ?? '') : $request->input('alias')[$c],
                'mo_ta' => $request->input('mo_ta')[$c] ?? '',
                'noi_dung' => $request->input('noi_dung')[$c] ?? '',
                'hinh_anh' => $request->input('hinh_anh') ?? '',
                'id_loai' => $id_loai,
                'module' => $module,
                'so_thu_tu' => $so_thu_tu,
                'hien_thi' => $hien_thi
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
     * Xóa 1 dòng
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        CfCodeModel::query()->where('id', $id)->delete();
        CategoryModel::query()->where('id_code', $id)->delete();
        return $this->redirect(route('admin.category.index'));
    }

}
