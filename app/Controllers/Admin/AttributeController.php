<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ModuleModel;
use AttributeModel;
use AttributeValueModel;
use CfCodeModel;
use PDO;

class AttributeController extends BaseAdminController {
    
    /**
     * Hiển thị danh sách thuộc tính
     */
    public function index(Request $request) {
        $attributes = AttributeModel::query()
            ->where('lang', 'vi')
            ->orderBy('sap_xep', 'ASC')
            ->orderBy('id_code', 'DESC')
            ->get();
            
        // Map giá trị thuộc tính để đếm số lượng
        foreach ($attributes as $attr) {
            $values = AttributeValueModel::query()
                ->where('id_thuoctinh', $attr->id_code)
                ->where('lang', 'vi')
                ->get();
            $attr->value_count = count($values);
            $attr->values_preview = implode(', ', array_map(function($v) { return $v->ten; }, array_slice($values, 0, 5)));
        }

        return $this->render('admin.attribute.index', compact('attributes'));
    }

    /**
     * Mở form thêm mới
     */
    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $data_type_variation = [
            'select' => 'Lựa chọn (Select)',
            'color' => 'Màu sắc (Color)',
            'button' => 'Nút bấm (Button)',
            'text' => 'Chữ (Text)'
        ];

        return $this->render('admin.attribute.form', compact('langs', 'data_type_variation'));
    }

    /**
     * Mở form chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $cfCode = CfCodeModel::query()->where('id', $id)->first();
        if (!$cfCode) return $this->redirect(route('admin.attribute.index'));
        
        $attrQuery = AttributeModel::query();
        $attrQuery->use_lang = false; // Fetch all translations
        $translations = $attrQuery->where('id_code', $id)->get();
        
        // Chuyển hóa dữ liệu để render lên form
        $item = [
            'id' => $id, 
            'loai' => '',
            'sap_xep' => $cfCode->so_thu_tu,
            'hien_thi' => $cfCode->hien_thi
        ];
        
        foreach ($translations as $t) {
            $lang = $t->lang;
            $item["ten"][$lang] = $t->ten;
            $item["alias"][$lang] = $t->alias;
            $item["mo_ta"][$lang] = $t->mo_ta;
            if (empty($item["loai"])) {
                $item["loai"] = $t->loai;
            }
        }
        
        // Lấy danh sách Giá trị thuộc tính (Values)
        $valQuery = AttributeValueModel::query();
        $valQuery->use_lang = false;
        $allValues = $valQuery->where('id_thuoctinh', $id)->orderBy('id', 'ASC')->get();
        
        // Nhóm value theo id_code để đưa vào Repeater
        $itemValues = [];
        foreach ($allValues as $v) {
            if (!isset($itemValues[$v->id_code])) {
                $itemValues[$v->id_code] = [
                    'id_code' => $v->id_code,
                    'gia_tri' => $v->gia_tri
                ];
            }
            $itemValues[$v->id_code]['ten'][$v->lang] = $v->ten;
        }

        $data_type_variation = [
            'select' => 'Lựa chọn (Select)',
            'color' => 'Màu sắc (Color)',
            'button' => 'Nút bấm (Button)',
            'text' => 'Chữ (Text)'
        ];
        
        return $this->render('admin.attribute.form', compact('langs', 'item', 'itemValues', 'data_type_variation'));
    }

    /**
     * Lưu dữ liệu thêm mới
     */
    public function store(Request $request) {
        $sap_xep = (int)$request->input('sap_xep', 0);
        $loai = $request->input('loai', 'select');
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Lưu Attribute vào bảng gốc cf_code (module 4 = Sản phẩm)
        $id_code = CfCodeModel::insert([
            'ten' => $ten_vi,
            'module' => 4,
            'so_thu_tu' => $sap_xep,
            'hien_thi' => $hien_thi
        ]);

        if ($id_code) {
            $langs = config('lang', [['code' => 'vi']]);
            
            // 2. Lưu Attribute bản dịch
            foreach ($langs as $l) {
                $c = $l['code'];
                AttributeModel::insert([
                    'id_code' => $id_code,
                    'lang' => $c,
                    'ten' => $request->input('ten')[$c] ?? '',
                    'alias' => $request->input('alias')[$c] ?? '',
                    'mo_ta' => $request->input('mo_ta')[$c] ?? '',
                    'loai' => $loai,
                    'sap_xep' => $sap_xep,
                    'id_sanpham' => 0
                ]);
            }
            
            // 3. Xử lý lưu các Giá trị thuộc tính (Values) từ Repeater
            $this->saveValues($request, $id_code, $langs);
        }
        
        return $this->redirect(route('admin.attribute.index'));
    }

    /**
     * Lưu dữ liệu cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $sap_xep = (int)$request->input('sap_xep', 0);
        $loai = $request->input('loai', 'select');
        $hien_thi = $request->input('hien_thi') !== null ? 1 : 0;
        
        $tenInput = $request->input('ten', []);
        $ten_vi = $tenInput['vi'] ?? '';

        // 1. Cập nhật bảng gốc
        CfCodeModel::query()->where('id', $id)->update([
            'ten' => $ten_vi,
            'so_thu_tu' => $sap_xep,
            'hien_thi' => $hien_thi
        ]);

        // 2. Cập nhật hoặc tạo mới bản dịch Thuộc tính
        $langs = config('lang', [['code' => 'vi']]);
        foreach ($langs as $l) {
            $c = $l['code'];
            $attrQuery = AttributeModel::query();
            $attrQuery->use_lang = false;
            $exists = $attrQuery->where('id_code', $id)->where('lang', $c)->first();
            
            $data = [
                'ten' => $request->input('ten')[$c] ?? '',
                'alias' => $request->input('alias')[$c] ?? '',
                'mo_ta' => $request->input('mo_ta')[$c] ?? '',
                'loai' => $loai,
                'sap_xep' => $sap_xep
            ];
            
            if ($exists) {
                $uQ = AttributeModel::query();
                $uQ->use_lang = false;
                $uQ->where('id', $exists->id)->update($data);
            } else {
                $data['id_code'] = $id;
                $data['lang'] = $c;
                $data['id_sanpham'] = 0;
                AttributeModel::insert($data);
            }
        }
        
        // 3. Xử lý lưu các Giá trị thuộc tính (Values) từ Repeater
        $this->saveValues($request, $id, $langs, true);
        
        return $this->redirect(route('admin.attribute.index'));
    }
    
    /**
     * Hàm dùng chung xử lý lưu Giá trị thuộc tính (Repeater)
     */
    private function saveValues($request, $id_thuoctinh, $langs, $isUpdate = false) {
        $val_id_codes = $request->input('val_id_code', []);
        $val_gia_tri = $request->input('val_gia_tri', []);
        $val_ten = $request->input('val_ten', []); // Mảng đa chiều: val_ten[lang][index]
        
        $kept_id_codes = [];
        
        // Loop qua từng dòng được submit trong Repeater
        foreach ($val_id_codes as $index => $v_id_code) {
            $v_id_code = (int)$v_id_code;
            $gia_tri = $val_gia_tri[$index] ?? '';
            $ten_vi = $val_ten['vi'][$index] ?? '';
            
            if (trim($ten_vi) === '') continue; // Skip empty rows
            
            if ($v_id_code > 0) {
                // Update tồn tại
                CfCodeModel::query()->where('id', $v_id_code)->update(['ten' => $ten_vi]);
                $kept_id_codes[] = $v_id_code;
                
                // Cập nhật bản dịch cho Value
                foreach ($langs as $l) {
                    $c = $l['code'];
                    $t = $val_ten[$c][$index] ?? '';
                    
                    $vq = AttributeValueModel::query();
                    $vq->use_lang = false;
                    $exists = $vq->where('id_code', $v_id_code)->where('lang', $c)->first();
                    if ($exists) {
                        $u = AttributeValueModel::query();
                        $u->use_lang = false;
                        $u->where('id', $exists->id)->update([
                            'ten' => $t,
                            'gia_tri' => $gia_tri
                        ]);
                    } else {
                        AttributeValueModel::insert([
                            'id_thuoctinh' => $id_thuoctinh,
                            'gia_tri' => $gia_tri,
                            'ten' => $t,
                            'id_code' => $v_id_code,
                            'lang' => $c,
                            'id_sanpham' => 0
                        ]);
                    }
                }
            } else {
                // Thêm mới Value
                $new_id_code = CfCodeModel::insert([
                    'ten' => $ten_vi,
                    'module' => 4, // Sản phẩm
                    'so_thu_tu' => 0,
                    'hien_thi' => 1
                ]);
                if ($new_id_code) {
                    $kept_id_codes[] = $new_id_code;
                    foreach ($langs as $l) {
                        $c = $l['code'];
                        AttributeValueModel::insert([
                            'id_thuoctinh' => $id_thuoctinh,
                            'gia_tri' => $gia_tri,
                            'ten' => $val_ten[$c][$index] ?? '',
                            'id_code' => $new_id_code,
                            'lang' => $c,
                            'id_sanpham' => 0
                        ]);
                    }
                }
            }
        }
        
        // 4. Nếu là Update, xóa những giá trị cũ bị xóa khỏi DOM
        if ($isUpdate) {
            $valQuery = AttributeValueModel::query();
            $valQuery->use_lang = false;
            $allOldValues = $valQuery->where('id_thuoctinh', $id_thuoctinh)->get();
            $old_id_codes = array_unique(array_column($allOldValues, 'id_code'));
            
            $deleted_id_codes = array_diff($old_id_codes, $kept_id_codes);
            if (!empty($deleted_id_codes)) {
                $inQuery = implode(',', $deleted_id_codes);
                // Xóa trong bảng dịch
                $delQ = AttributeValueModel::query();
                $delQ->use_lang = false;
                $delQ->whereIn('id_code', $deleted_id_codes)->delete(); // Chú ý: Cần verify method whereIn có chạy tốt trong Model ko, nếu ko thì dùng PDO RAW
                
                // Vì Model base có thể không hỗ trợ whereIn hoàn chỉnh, an toàn nhất là lặp qua để xóa
                $pdo = \App\Core\Model::$pdo;
                $pdo->exec("DELETE FROM #_thuoctinh_giatri WHERE id_code IN ($inQuery)");
                $pdo->exec("DELETE FROM cf_code WHERE id IN ($inQuery)");
            }
        }
    }

    /**
     * Xóa 1 thuộc tính
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        // Xóa Thuộc tính
        CfCodeModel::query()->where('id', $id)->delete();
        AttributeModel::query()->where('id_code', $id)->delete();
        
        // Lấy danh sách Values để xóa
        $valQuery = AttributeValueModel::query();
        $valQuery->use_lang = false;
        $values = $valQuery->where('id_thuoctinh', $id)->get();
        
        $val_id_codes = array_unique(array_column($values, 'id_code'));
        if (!empty($val_id_codes)) {
            $inQuery = implode(',', $val_id_codes);
            $pdo = \App\Core\Model::$pdo;
            $pdo->exec("DELETE FROM #_thuoctinh_giatri WHERE id_thuoctinh = $id");
            $pdo->exec("DELETE FROM cf_code WHERE id IN ($inQuery)");
        }
        
        return $this->redirect(route('admin.attribute.index'));
    }
}
