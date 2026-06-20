<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Models\PaymentMethodModel;

class PaymentController extends BaseAdminController
{
    public function index(Request $request)
    {
        $methods = PaymentMethodModel::orderBy('sort_order', 'ASC')
                                     ->orderBy('id', 'DESC')
                                     ->get();

        return view('admin.payment.index', [
            'methods' => $methods
        ]);
    }

    public function create(Request $request)
    {
        $langs = config('lang', [['code' => 'vi']]);
        return view('admin.payment.form', compact('langs'));
    }

    private function buildMethodData(Request $request, string $langCode): array
    {
        $apiKeys = $request->input('api_keys', []);
        
        return [
            'name' => $request->input('name')[$langCode] ?? '',
            'code' => $request->input('code'),
            'description' => $request->input('description')[$langCode] ?? '',
            'api_config' => !empty($apiKeys) ? json_encode($apiKeys) : null,
            'is_active' => $request->input('is_active') !== null ? 1 : 0,
            'sort_order' => $request->input('sort_order', 0),
        ];
    }

    public function store(Request $request)
    {
        $langs = config('lang', [['code' => 'vi']]);
        $firstLang = $langs[0]['code'];
        
        $firstLangData = $this->buildMethodData($request, $firstLang);
        $firstLangData['lang'] = $firstLang;
        $firstLangData['id_code'] = 0;
        
        // Ensure unique code
        $exists = PaymentMethodModel::where('code', $firstLangData['code'])->first();
        if ($exists) {
            return $this->redirect(route('admin.payment.create'))->with('error', 'Mã phương thức (Code) đã tồn tại!');
        }

        $insertedId = PaymentMethodModel::insertGetId($firstLangData);
        if ($insertedId) {
            $id_code = $insertedId;
            $pmQuery = PaymentMethodModel::query();
            $pmQuery->use_lang = false;
            $pmQuery->where('id', $insertedId)->update(['id_code' => $id_code]);
            
            foreach ($langs as $index => $l) {
                if ($index === 0) continue;
                $c = $l['code'];
                $langData = $this->buildMethodData($request, $c);
                $langData['id_code'] = $id_code;
                $langData['lang'] = $c;
                PaymentMethodModel::insert($langData);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.payment.edit', ['id' => $id_code ?? 0]))->with('success', 'Thêm phương thức thành công!');
        }
        return $this->redirect(route('admin.payment.index'))->with('success', 'Thêm phương thức thành công!');
    }

    public function edit(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $langs = config('lang', [['code' => 'vi']]);
        
        // Tìm bản ghi gốc
        $baseItem = PaymentMethodModel::find($id);
        if (!$baseItem) return $this->redirect(route('admin.payment.index'));

        // Load tất cả ngôn ngữ của bản ghi này
        $query = PaymentMethodModel::query();
        $query->use_lang = false;
        $translations = $query->where('id_code', $baseItem->id_code)->get();

        $itemData = [
            'id' => $baseItem->id,
            'id_code' => $baseItem->id_code,
            'code' => $baseItem->code,
            'api_config' => $baseItem->api_config,
            'is_active' => $baseItem->is_active,
            'sort_order' => $baseItem->sort_order,
            'name' => [],
            'description' => []
        ];

        foreach ($translations as $t) {
            $c = $t->lang;
            $itemData['name'][$c] = $t->name;
            $itemData['description'][$c] = $t->description;
        }

        return view('admin.payment.form', [
            'item' => $itemData,
            'langs' => $langs
        ]);
    }

    public function update(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $baseItem = PaymentMethodModel::find($id);
        if (!$baseItem) return $this->redirect(route('admin.payment.index'));

        $langs = config('lang', [['code' => 'vi']]);
        $code = $request->input('code');

        // Ensure unique code
        $exists = PaymentMethodModel::where('code', $code)->where('id_code', '!=', $baseItem->id_code)->first();
        if ($exists) {
            return $this->redirect(route('admin.payment.edit', ['id' => $id]))->with('error', 'Mã phương thức (Code) đã tồn tại!');
        }

        $query = PaymentMethodModel::query();
        $query->use_lang = false;
        $translations = $query->where('id_code', $baseItem->id_code)->get();
        $existingLangs = array_column($translations, 'id', 'lang');

        foreach ($langs as $l) {
            $c = $l['code'];
            $data = $this->buildMethodData($request, $c);

            if (isset($existingLangs[$c])) {
                $updateQuery = PaymentMethodModel::query();
                $updateQuery->use_lang = false;
                $updateQuery->where('id', $existingLangs[$c])->update($data);
            } else {
                $data['id_code'] = $baseItem->id_code;
                $data['lang'] = $c;
                PaymentMethodModel::insert($data);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.payment.edit', ['id' => $id]))->with('success', 'Cập nhật phương thức thành công!');
        }
        return $this->redirect(route('admin.payment.index'))->with('success', 'Cập nhật phương thức thành công!');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $method = PaymentMethodModel::find($id);
        if ($method) {
            $query = PaymentMethodModel::query();
            $query->use_lang = false;
            $query->where('id_code', $method->id_code)->delete();
            return Response::json(['success' => true, 'message' => 'Đã xóa phương thức thanh toán']);
        }
        return Response::json(['success' => false, 'message' => 'Không tìm thấy phương thức']);
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if ($field === 'is_active') {
            $method = PaymentMethodModel::find($id);
            if ($method) {
                $query = PaymentMethodModel::query();
                $query->use_lang = false;
                $query->where('id_code', $method->id_code)->update([$field => $value]);
                return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
            }
        }
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ hoặc lỗi!']);
    }
}
