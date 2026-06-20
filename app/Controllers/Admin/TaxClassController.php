<?php

namespace App\Controllers\Admin;

use App\Models\TaxClassModel;
use App\Core\Request;

class TaxClassController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = TaxClassModel::query();
        $query->where('lang', config('app.locale', 'vi'));
        $items = $query->orderBy('id', 'DESC')->get();

        return view('admin.tax_class.index', [
            'items' => $items,
            'title' => 'Quản lý Nhóm Thuế'
        ]);
    }

    public function create()
    {
        $langs = config('lang', ['vi' => ['code' => 'vi']]);
        return view('admin.tax_class.form', [
            'title' => 'Thêm Nhóm Thuế',
            'langs' => $langs,
            'item' => []
        ]);
    }

    private function handleDefaultStatus($isDefault, $idCodeToIgnore = null)
    {
        if ($isDefault) {
            $updateQuery = TaxClassModel::query();
            $updateQuery->use_lang = false;
            if ($idCodeToIgnore) {
                $updateQuery->where('id_code', '!=', $idCodeToIgnore);
            }
            $updateQuery->update(['is_default' => 0]);
        }
    }

    public function store(Request $request)
    {
        $langs = config('lang', ['vi' => ['code' => 'vi']]);
        $firstLang = current($langs)['code'];
        
        $isDefault = $request->input('is_default') !== null ? 1 : 0;
        
        $firstLangData = [
            'name' => $request->input('name')[$firstLang] ?? '',
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
            'is_default' => $isDefault,
            'lang' => $firstLang,
            'id_code' => 0
        ];
        
        $insertedId = TaxClassModel::insertGetId($firstLangData);
        if ($insertedId) {
            $id_code = $insertedId;
            $pmQuery = TaxClassModel::query();
            $pmQuery->use_lang = false;
            $pmQuery->where('id', $insertedId)->update(['id_code' => $id_code]);
            
            $this->handleDefaultStatus($isDefault, $id_code);
            
            foreach ($langs as $l) {
                $c = $l['code'];
                if ($c === $firstLang) continue;
                
                $langData = [
                    'name' => $request->input('name')[$c] ?? '',
                    'is_active' => $firstLangData['is_active'],
                    'is_default' => $firstLangData['is_default'],
                    'lang' => $c,
                    'id_code' => $id_code
                ];
                TaxClassModel::insert($langData);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.tax_class.edit', ['id' => $id_code ?? 0]))->with('success', 'Thêm Nhóm Thuế thành công!');
        }
        return $this->redirect(route('admin.tax_class.index'))->with('success', 'Thêm Nhóm Thuế thành công!');
    }

    public function edit(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $langs = config('lang', ['vi' => ['code' => 'vi']]);
        
        $baseItem = TaxClassModel::find($id);
        if (!$baseItem) return $this->redirect(route('admin.tax_class.index'));

        $query = TaxClassModel::query();
        $query->use_lang = false;
        $translations = $query->where('id_code', $baseItem->id_code)->get();

        $itemData = [
            'id' => $baseItem->id,
            'id_code' => $baseItem->id_code,
            'is_active' => $baseItem->is_active,
            'is_default' => $baseItem->is_default,
            'name' => []
        ];

        foreach ($translations as $t) {
            $itemData['name'][$t->lang] = $t->name;
        }

        return view('admin.tax_class.form', [
            'title' => 'Sửa Nhóm Thuế',
            'item' => $itemData,
            'langs' => $langs
        ]);
    }

    public function update(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $baseItem = TaxClassModel::find($id);
        if (!$baseItem) return $this->redirect(route('admin.tax_class.index'));

        $langs = config('lang', ['vi' => ['code' => 'vi']]);
        
        $isDefault = $request->input('is_default') !== null ? 1 : 0;
        $isActive = $request->input('is_active') !== null ? 1 : 0;
        
        $this->handleDefaultStatus($isDefault, $baseItem->id_code);

        $query = TaxClassModel::query();
        $query->use_lang = false;
        $translations = $query->where('id_code', $baseItem->id_code)->get();
        $existingLangs = array_column($translations, 'id', 'lang');

        foreach ($langs as $l) {
            $c = $l['code'];
            $data = [
                'name' => $request->input('name')[$c] ?? '',
                'is_active' => $isActive,
                'is_default' => $isDefault
            ];

            if (isset($existingLangs[$c])) {
                $updateQuery = TaxClassModel::query();
                $updateQuery->use_lang = false;
                $updateQuery->where('id', $existingLangs[$c])->update($data);
            } else {
                $data['id_code'] = $baseItem->id_code;
                $data['lang'] = $c;
                TaxClassModel::insert($data);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.tax_class.edit', ['id' => $id]))->with('success', 'Cập nhật Nhóm Thuế thành công!');
        }
        return $this->redirect(route('admin.tax_class.index'))->with('success', 'Cập nhật Nhóm Thuế thành công!');
    }

    public function destroy(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $baseItem = TaxClassModel::find($id);
        
        if ($baseItem) {
            $query = TaxClassModel::query();
            $query->use_lang = false;
            $query->where('id_code', $baseItem->id_code)->delete();
            return $this->json(['success' => true, 'message' => 'Đã xóa nhóm thuế']);
        }
        
        return $this->json(['success' => false, 'message' => 'Không tìm thấy nhóm thuế']);
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        $allowedFields = ['is_active'];
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
        }
        
        $baseItem = TaxClassModel::find($id);
        if ($baseItem) {
            $query = TaxClassModel::query();
            $query->use_lang = false;
            $query->where('id_code', $baseItem->id_code)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu!']);
    }
}
