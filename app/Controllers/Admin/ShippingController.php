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

    public function storeMethod()
    {
        $data = [
            'shop_id' => 0,
            'name' => Request::input('name'),
            'carrier_code' => Request::input('carrier_code'),
            'is_api' => Request::input('is_api') ? 1 : 0,
            'is_active' => Request::input('is_active') !== null ? 1 : 0,
            'sort_order' => Request::input('sort_order', 0),
        ];

        // Xử lý API Config JSON
        if ($data['is_api']) {
            $apiKeys = Request::input('api_keys', []);
            $data['api_config'] = !empty($apiKeys) ? json_encode($apiKeys) : null;
        } else {
            $data['api_config'] = null;
        }

        ShippingMethodModel::create($data);
        return redirect(route('admin.shipping.index'))->with('success', 'Thêm phương thức thành công!');
    }

    public function editMethod($id)
    {
        $method = ShippingMethodModel::find($id);
        if (!$method || $method->shop_id != 0) return redirect(route('admin.shipping.index'));
        
        return view('admin.shipping.form_method', ['item' => $method]);
    }

    public function updateMethod($id)
    {
        $method = ShippingMethodModel::find($id);
        if (!$method || $method->shop_id != 0) return redirect(route('admin.shipping.index'));

        $data = [
            'name' => Request::input('name'),
            'carrier_code' => Request::input('carrier_code'),
            'is_api' => Request::input('is_api') ? 1 : 0,
            'is_active' => Request::input('is_active') !== null ? 1 : 0,
            'sort_order' => Request::input('sort_order', 0),
        ];

        if ($data['is_api']) {
            $apiKeys = Request::input('api_keys', []);
            $data['api_config'] = !empty($apiKeys) ? json_encode($apiKeys) : null;
        } else {
            $data['api_config'] = null;
        }

        $method->update($data);
        return redirect(route('admin.shipping.index'))->with('success', 'Cập nhật phương thức thành công!');
    }

    public function destroyMethod()
    {
        $id = Request::input('id');
        $method = ShippingMethodModel::find($id);
        if ($method && $method->shop_id == 0) {
            // Xóa cả rates liên quan
            ShippingRateModel::where('shipping_method_id', $id)->delete();
            $method->delete();
            return Response::json(['success' => true, 'message' => 'Đã xóa phương thức và bảng giá liên quan']);
        }
        return Response::json(['success' => false, 'message' => 'Không tìm thấy phương thức']);
    }

    // --- R A T E S ---

    public function rates($methodId)
    {
        $method = ShippingMethodModel::find($methodId);
        if (!$method || $method->shop_id != 0 || $method->is_api == 1) {
            return redirect(route('admin.shipping.index'));
        }

        $rates = ShippingRateModel::where('shipping_method_id', $methodId)->get();
        return view('admin.shipping.rates', [
            'method' => $method,
            'rates' => $rates
        ]);
    }

    public function createRate($methodId)
    {
        $method = ShippingMethodModel::find($methodId);
        if (!$method) return redirect(route('admin.shipping.index'));
        
        // Lấy danh sách quốc gia tạm thời (hoặc có model Country)
        $countries = ['VN' => 'Việt Nam', 'US' => 'Hoa Kỳ', 'JP' => 'Nhật Bản', '*' => 'Toàn cầu (Khác)'];
        
        // Lấy danh sách tỉnh thành VN
        global $pdo; // Lấy instance PDO để select db_thanhpho
        $stmt = $pdo->query("SELECT code, ten FROM db_thanhpho ORDER BY ten ASC");
        $provinces = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return view('admin.shipping.form_rate', [
            'method' => $method,
            'countries' => $countries,
            'provinces' => $provinces
        ]);
    }

    public function storeRate($methodId)
    {
        $data = [
            'shipping_method_id' => $methodId,
            'country_code' => Request::input('country_code', 'VN'),
            'province_code' => Request::input('province_code'),
            'district_code' => Request::input('district_code'),
            'ward_code' => Request::input('ward_code'),
            'base_fee' => Request::input('base_fee', 0),
            'extra_fee_per_kg' => Request::input('extra_fee_per_kg', 0),
            'free_weight_kg' => Request::input('free_weight_kg', 0),
            'is_active' => Request::input('is_active') !== null ? 1 : 0,
        ];
        
        if (empty($data['province_code'])) $data['province_code'] = null;
        if (empty($data['district_code'])) $data['district_code'] = null;
        if (empty($data['ward_code'])) $data['ward_code'] = null;

        ShippingRateModel::create($data);
        return redirect(route('admin.shipping.rates', $methodId))->with('success', 'Thêm biểu phí thành công!');
    }

    public function editRate($methodId, $rateId)
    {
        $method = ShippingMethodModel::find($methodId);
        $rate = ShippingRateModel::find($rateId);
        if (!$method || !$rate) return redirect(route('admin.shipping.index'));

        $countries = ['VN' => 'Việt Nam', 'US' => 'Hoa Kỳ', 'JP' => 'Nhật Bản', '*' => 'Toàn cầu (Khác)'];
        
        global $pdo;
        $stmt = $pdo->query("SELECT code, ten FROM db_thanhpho ORDER BY ten ASC");
        $provinces = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Load districts if province exists
        $districts = [];
        if ($rate->province_code) {
            $stmtD = $pdo->prepare("SELECT code, ten FROM db_huyen WHERE code_tinh = ? ORDER BY ten ASC");
            $stmtD->execute([$rate->province_code]);
            $districts = $stmtD->fetchAll(\PDO::FETCH_ASSOC);
        }

        return view('admin.shipping.form_rate', [
            'method' => $method,
            'item' => $rate,
            'countries' => $countries,
            'provinces' => $provinces,
            'districts' => $districts
        ]);
    }

    public function updateRate($methodId, $rateId)
    {
        $rate = ShippingRateModel::find($rateId);
        if (!$rate) return redirect(route('admin.shipping.index'));

        $data = [
            'country_code' => Request::input('country_code', 'VN'),
            'province_code' => Request::input('province_code'),
            'district_code' => Request::input('district_code'),
            'ward_code' => Request::input('ward_code'),
            'base_fee' => Request::input('base_fee', 0),
            'extra_fee_per_kg' => Request::input('extra_fee_per_kg', 0),
            'free_weight_kg' => Request::input('free_weight_kg', 0),
            'is_active' => Request::input('is_active') !== null ? 1 : 0,
        ];

        if (empty($data['province_code'])) $data['province_code'] = null;
        if (empty($data['district_code'])) $data['district_code'] = null;
        if (empty($data['ward_code'])) $data['ward_code'] = null;

        $rate->update($data);
        return redirect(route('admin.shipping.rates', $methodId))->with('success', 'Cập nhật biểu phí thành công!');
    }

    public function destroyRate()
    {
        $id = Request::input('id');
        $rate = ShippingRateModel::find($id);
        if ($rate) {
            $rate->delete();
            return Response::json(['success' => true, 'message' => 'Đã xóa biểu phí']);
        }
        return Response::json(['success' => false, 'message' => 'Không tìm thấy bản ghi']);
    }
}
