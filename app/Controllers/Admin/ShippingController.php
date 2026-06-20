<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\ShippingMethodModel;
use App\Models\ShippingRateModel;

class ShippingController extends BaseAdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $methods = ShippingMethodModel::where('shop_id', 0)->orderBy('sort_order', 'ASC')->get();
        return view('admin.shipping.index', ['methods' => $methods]);
    }

    public function createMethod()
    {
        return view('admin.shipping.form_method');
    }

    public function storeMethod(Request $request)
    {
        $data = [
            'shop_id' => 0,
            'name' => $request->input('name'),
            'carrier_code' => $request->input('carrier_code'),
            'is_api' => $request->input('is_api') ? 1 : 0,
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
            'sort_order' => $request->input('sort_order', 0),
        ];

        // Xử lý API Config JSON
        if ($data['is_api']) {
            $apiKeys = $request->input('api_keys', []);
            $data['api_config'] = !empty($apiKeys) ? json_encode($apiKeys) : null;
        } else {
            $data['api_config'] = null;
        }

        $method = ShippingMethodModel::create($data);
        
        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shipping.edit_method', ['id' => $method->id]))->with('success', 'Thêm phương thức thành công!');
        }
        return $this->redirect(route('admin.shipping.index'))->with('success', 'Thêm phương thức thành công!');
    }

    public function editMethod(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $method = ShippingMethodModel::find($id);
        if (!$method || $method->shop_id != 0) return $this->redirect(route('admin.shipping.index'));
        
        return view('admin.shipping.form_method', ['item' => $method]);
    }

    public function updateMethod(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $method = ShippingMethodModel::find($id);
        if (!$method || $method->shop_id != 0) return $this->redirect(route('admin.shipping.index'));

        $data = [
            'name' => $request->input('name'),
            'carrier_code' => $request->input('carrier_code'),
            'is_api' => $request->input('is_api') ? 1 : 0,
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
            'sort_order' => $request->input('sort_order', 0),
        ];

        if ($data['is_api']) {
            $apiKeys = $request->input('api_keys', []);
            $data['api_config'] = !empty($apiKeys) ? json_encode($apiKeys) : null;
        } else {
            $data['api_config'] = null;
        }

        $method->update($data);

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shipping.edit_method', ['id' => $id]))->with('success', 'Cập nhật phương thức thành công!');
        }
        return $this->redirect(route('admin.shipping.index'))->with('success', 'Cập nhật phương thức thành công!');
    }

    public function destroyMethod(Request $request)
    {
        $id = $request->input('id');
        $method = ShippingMethodModel::find($id);
        if ($method && $method->shop_id == 0) {
            // Xóa cả rates liên quan
            ShippingRateModel::where('shipping_method_id', $id)->delete();
            $method->delete();
            return Response::json(['success' => true, 'message' => 'Đã xóa phương thức và bảng giá liên quan']);
        }
        return Response::json(['success' => false, 'message' => 'Không tìm thấy phương thức']);
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if ($field === 'is_active') {
            ShippingMethodModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
    }

    // --- R A T E S ---

    public function rates(Request $request, $params = [])
    {
        $methodId = is_array($params) ? ($params['methodId'] ?? 0) : $params;
        $method = ShippingMethodModel::find($methodId);
        if (!$method) return $this->redirect(route('admin.shipping.index'));

        // Dùng Query Builder để tránh lỗi kết nối PDO khi dùng raw query
        $rates = ShippingRateModel::with('province')
                                  ->with('district')
                                  ->with('ward')
                                  ->where('shipping_method_id', $methodId)
                                  ->orderBy('priority', 'DESC')
                                  ->orderBy('id', 'DESC')
                                  ->get();

        $countries = ['VN' => 'Việt Nam', 'US' => 'Hoa Kỳ', 'JP' => 'Nhật Bản', '*' => 'Toàn cầu (Khác)'];

        return view('admin.shipping.rates', [
            'method' => $method,
            'rates' => $rates,
            'countries' => $countries
        ]);
    }

    public function createRate(Request $request, $params = [])
    {
        $methodId = is_array($params) ? ($params['methodId'] ?? 0) : $params;
        $method = ShippingMethodModel::find($methodId);
        if (!$method) return $this->redirect(route('admin.shipping.index'));
        
        // Lấy danh sách quốc gia tạm thời (hoặc có model Country)
        $countries = ['VN' => 'Việt Nam', 'US' => 'Hoa Kỳ', 'JP' => 'Nhật Bản', '*' => 'Toàn cầu (Khác)'];
        
        // Load available provinces
        $provinces = \App\Models\ProvinceModel::orderBy('ten', 'ASC')->get('code, ten');

        return view('admin.shipping.form_rate', [
            'method' => $method,
            'countries' => $countries,
            'provinces' => $provinces
        ]);
    }

    public function storeRate(Request $request, $params = [])
    {
        // TODO: Kiểm tra lại lỗi lưu form bị đẩy ra ngoài Frontend 404 (URL sai hoặc route config sai)
        $methodId = is_array($params) ? ($params['methodId'] ?? 0) : $params;
        $data = [
            'shipping_method_id' => $methodId,
            'country_code' => $request->input('country_code', 'VN'),
            'province_code' => $request->input('province_code'),
            'district_code' => $request->input('district_code'),
            'ward_code' => $request->input('ward_code'),
            'base_fee' => $request->input('base_fee', 0),
            'extra_fee_per_kg' => $request->input('extra_fee_per_kg', 0),
            'free_weight_kg' => $request->input('free_weight_kg', 0),
            'estimated_time' => $request->input('estimated_time', ''),
            'priority' => $request->input('priority', 0),
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
        ];
        
        if (empty($data['province_code'])) $data['province_code'] = null;
        if (empty($data['district_code'])) $data['district_code'] = null;
        if (empty($data['ward_code'])) $data['ward_code'] = null;

        $rate = ShippingRateModel::create($data);

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shipping.edit_rate', ['methodId' => $methodId, 'rateId' => $rate->id]))->with('success', 'Thêm biểu phí thành công!');
        }
        return $this->redirect(route('admin.shipping.rates', ['methodId' => $methodId]))->with('success', 'Thêm biểu phí thành công!');
    }

    public function editRate(Request $request, $params = [])
    {
        $methodId = is_array($params) ? ($params['methodId'] ?? 0) : 0;
        $rateId = is_array($params) ? ($params['rateId'] ?? 0) : 0;
        
        $method = ShippingMethodModel::find($methodId);
        $rate = ShippingRateModel::find($rateId);
        if (!$method || !$rate) return $this->redirect(route('admin.shipping.index'));

        $countries = ['VN' => 'Việt Nam', 'US' => 'Hoa Kỳ', 'JP' => 'Nhật Bản', '*' => 'Toàn cầu (Khác)'];
        // Load available provinces
        $provinces = \App\Models\ProvinceModel::orderBy('ten', 'ASC')->get('code, ten');

        // Load districts if province exists
        $districts = [];
        if ($rate->province_code) {
            $districts = \App\Models\DistrictModel::where('code_tinh', $rate->province_code)
                                                  ->orderBy('ten', 'ASC')
                                                  ->get('code, ten');
        }

        return view('admin.shipping.form_rate', [
            'method' => $method,
            'item' => $rate,
            'countries' => $countries,
            'provinces' => $provinces,
            'districts' => $districts
        ]);
    }

    public function updateRate(Request $request, $params = [])
    {
        $methodId = is_array($params) ? ($params['methodId'] ?? 0) : 0;
        $rateId = is_array($params) ? ($params['rateId'] ?? 0) : 0;

        $rate = ShippingRateModel::find($rateId);
        if (!$rate) return $this->redirect(route('admin.shipping.index'));

        $data = [
            'country_code' => $request->input('country_code', 'VN'),
            'province_code' => $request->input('province_code'),
            'district_code' => $request->input('district_code'),
            'ward_code' => $request->input('ward_code'),
            'base_fee' => $request->input('base_fee', 0),
            'extra_fee_per_kg' => $request->input('extra_fee_per_kg', 0),
            'free_weight_kg' => $request->input('free_weight_kg', 0),
            'estimated_time' => $request->input('estimated_time', ''),
            'priority' => $request->input('priority', 0),
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
        ];

        if (empty($data['province_code'])) $data['province_code'] = null;
        if (empty($data['district_code'])) $data['district_code'] = null;
        if (empty($data['ward_code'])) $data['ward_code'] = null;

        $rate->update($data);

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shipping.edit_rate', ['methodId' => $methodId, 'rateId' => $rateId]))->with('success', 'Cập nhật biểu phí thành công!');
        }
        return $this->redirect(route('admin.shipping.rates', ['methodId' => $methodId]))->with('success', 'Cập nhật biểu phí thành công!');
    }

    public function destroyRate(Request $request)
    {
        $id = $request->input('id');
        $rate = ShippingRateModel::find($id);
        if ($rate) {
            $rate->delete();
            return Response::json(['success' => true, 'message' => 'Đã xóa biểu phí']);
        }
        return Response::json(['success' => false, 'message' => 'Không tìm thấy bản ghi']);
    }

    public function updateRateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if ($field === 'is_active') {
            ShippingRateModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái biểu phí thành công!']);
        }
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
    }
}
