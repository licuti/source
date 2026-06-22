<?php

namespace App\Controllers\Admin;

use App\Models\TaxRateModel;
use App\Models\TaxClassModel;
use App\Models\LocationModel;
use App\Core\Request;

class TaxRateController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = TaxRateModel::query();
        $query->orderBy('id', 'DESC');
        $items = $query->get();

        // Get Tax Class names
        $taxClasses = TaxClassModel::where('lang', config('app.locale', 'vi'))->get();
        $classMap = array_column($taxClasses, 'name', 'id_code');

        return view('admin.tax_rate.index', [
            'items' => $items,
            'classMap' => $classMap,
            'title' => 'Biểu Phí Thuế'
        ]);
    }

    public function create()
    {
        $taxClasses = TaxClassModel::where('lang', config('app.locale', 'vi'))->where('is_active', 1)->get();
        $countries = LocationModel::where('type', 'country')->orderBy('name', 'ASC')->get();
        $provinces = [];
        
        return view('admin.tax_rate.form', [
            'title' => 'Thêm Biểu Phí Thuế',
            'taxClasses' => $taxClasses,
            'countries' => $countries,
            'provinces' => $provinces,
            'districts' => [],
            'wards' => [],
            'item' => []
        ]);
    }

    public function store(Request $request)
    {
        $data = [
            'shop_id' => $request->input('shop_id', 0),
            'tax_class_id' => $request->input('tax_class_id', 0),
            'country_id' => $request->input('country_id', 0),
            'province_id' => $request->input('province_id', 0),
            'district_id' => $request->input('district_id', 0),
            'ward_id' => $request->input('ward_id', 0),
            'name' => $request->input('name', ''),
            'rate' => floatval($request->input('rate', 0)),
            'is_compound' => $request->input('is_compound') !== null ? 1 : 0,
            'priority' => intval($request->input('priority', 0)),
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
        ];
        
        $insertedId = TaxRateModel::insertGetId($data);

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.tax_rate.edit', ['id' => $insertedId]))->with('success', 'Thêm Biểu Phí Thuế thành công!');
        }
        return $this->redirect(route('admin.tax_rate.index'))->with('success', 'Thêm Biểu Phí Thuế thành công!');
    }

    public function edit(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $item = TaxRateModel::find($id);
        if (!$item) return $this->redirect(route('admin.tax_rate.index'));

        $taxClasses = TaxClassModel::where('lang', config('app.locale', 'vi'))->get();
        $countries = LocationModel::where('type', 'country')->orderBy('name', 'ASC')->get();
        $provinces = [];
        $districts = [];
        $wards = [];
        
        if (!empty($item->country_id)) {
            $provinces = LocationModel::where('type', 'province')->where('parent_id', $item->country_id)->orderBy('name', 'ASC')->get();
        }
        
        if (!empty($item->province_id)) {
            $districts = LocationModel::where('type', 'district')->where('parent_id', $item->province_id)->orderBy('name', 'ASC')->get();
        }
        
        if (!empty($item->district_id)) {
            $wards = LocationModel::where('type', 'ward')->where('parent_id', $item->district_id)->orderBy('name', 'ASC')->get();
        }

        return view('admin.tax_rate.form', [
            'title' => 'Sửa Biểu Phí Thuế',
            'item' => $item->toArray(),
            'taxClasses' => $taxClasses,
            'countries' => $countries,
            'provinces' => $provinces,
            'districts' => $districts,
            'wards' => $wards
        ]);
    }

    public function update(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $item = TaxRateModel::find($id);
        if (!$item) return $this->redirect(route('admin.tax_rate.index'));

        $data = [
            'shop_id' => $request->input('shop_id', 0),
            'tax_class_id' => $request->input('tax_class_id', 0),
            'country_id' => $request->input('country_id', 0),
            'province_id' => $request->input('province_id', 0),
            'district_id' => $request->input('district_id', 0),
            'ward_id' => $request->input('ward_id', 0),
            'name' => $request->input('name', ''),
            'rate' => floatval($request->input('rate', 0)),
            'is_compound' => $request->input('is_compound') !== null ? 1 : 0,
            'priority' => intval($request->input('priority', 0)),
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
        ];
        
        TaxRateModel::where('id', $id)->update($data);

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.tax_rate.edit', ['id' => $id]))->with('success', 'Cập nhật thành công!');
        }
        return $this->redirect(route('admin.tax_rate.index'))->with('success', 'Cập nhật thành công!');
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        $allowedFields = ['is_active', 'is_compound'];
        if (!in_array($field, $allowedFields)) {
            return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
        }
        
        $item = TaxRateModel::find($id);
        if ($item) {
            TaxRateModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu!']);
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        if (TaxRateModel::where('id', $id)->delete()) {
            return $this->json(['success' => true, 'message' => 'Xóa Biểu Phí Thuế thành công!']);
        }
        return $this->json(['success' => false, 'message' => 'Lỗi hệ thống!']);
    }

}
