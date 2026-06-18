<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\ShopModel;

class ShopController extends BaseAdminController {
    
    private array $langs;

    public function __construct() {
        parent::__construct();
        $this->langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
    }
    
    public function index(Request $request) {
        $keyword = trim($request->input('keyword', ''));
        $status  = $request->input('status', '');
        $page    = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 20;

        $query = ShopModel::where('lang', config('app.locale', 'vi'));
        
        if ($keyword !== '') {
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                  ->orWhere('phone', 'LIKE', "%{$keyword}%")
                  ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        $query->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC');
        $items = $query->paginate($limit);

        return $this->render('admin.shop.index', compact('items', 'keyword', 'status'));
    }

    public function create(Request $request) {
        $langs = $this->langs;
        return $this->render('admin.shop.form', compact('langs'));
    }

    public function store(Request $request) {
        $langs = $this->langs;
        
        $nameArr = $request->input('name', []);
        $slug = $request->input('slug', '');
        $descriptionArr = $request->input('description', []);
        $addressArr = $request->input('address', []);
        
        // Common fields
        $phone = $request->input('phone', '');
        $email = $request->input('email', '');
        $map_iframe = $request->input('map_iframe', '');
        $logo = $request->input('logo', '');
        $banner = $request->input('banner', '');
        $sort_order = (int)$request->input('sort_order', 0);
        $status = $request->has('status') ? 1 : 0;
        
        // Find max id_code
        $maxIdCodeRow = ShopModel::orderBy('id_code', 'DESC')->first();
        $newIdCode = $maxIdCodeRow ? $maxIdCodeRow->id_code + 1 : 1;

        foreach ($langs as $l) {
            $c = $l['code'];
            $data = [
                'id_code' => $newIdCode,
                'lang' => $c,
                'name' => $nameArr[$c] ?? '',
                'slug' => $slug,
                'description' => $descriptionArr[$c] ?? '',
                'address' => $addressArr[$c] ?? '',
                'phone' => $phone,
                'email' => $email,
                'map_iframe' => $map_iframe,
                'logo' => $logo,
                'banner' => $banner,
                'sort_order' => $sort_order,
                'status' => $status,
            ];
            ShopModel::create($data);
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shop.edit', ['id' => $newIdCode]))->with('success', 'Thêm gian hàng thành công!');
        }
        return $this->redirect(route('admin.shop.index'))->with('success', 'Thêm gian hàng thành công!');
    }

    public function edit(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[0] ?? 0) : $params;
        $langs = $this->langs;
        
        $translations = ShopModel::withoutLang()->where('id_code', $id_code)->get();
        if (empty($translations)) {
            return $this->redirect(route('admin.shop.index'))->with('error', 'Không tìm thấy gian hàng!');
        }
        
        $firstItem = $translations[0];
        $langData = [];
        foreach($translations as $t) {
            $langData[$t->lang] = [
                'name' => $t->name,
                'description' => $t->description,
                'address' => $t->address,
            ];
        }
        $firstItem->lang_data = $langData;

        return $this->render('admin.shop.form', compact('langs', 'firstItem'));
    }

    public function update(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[0] ?? 0) : $params;
        $langs = $this->langs;
        
        $nameArr = $request->input('name', []);
        $slug = $request->input('slug', '');
        $descriptionArr = $request->input('description', []);
        $addressArr = $request->input('address', []);
        
        // Common fields
        $phone = $request->input('phone', '');
        $email = $request->input('email', '');
        $map_iframe = $request->input('map_iframe', '');
        $logo = $request->input('logo', '');
        $banner = $request->input('banner', '');
        $sort_order = (int)$request->input('sort_order', 0);
        $status = $request->has('status') ? 1 : 0;

        foreach ($langs as $l) {
            $c = $l['code'];
            
            $data = [
                'name' => $nameArr[$c] ?? '',
                'slug' => $slug,
                'description' => $descriptionArr[$c] ?? '',
                'address' => $addressArr[$c] ?? '',
                'phone' => $phone,
                'email' => $email,
                'map_iframe' => $map_iframe,
                'logo' => $logo,
                'banner' => $banner,
                'sort_order' => $sort_order,
                'status' => $status,
            ];

            $existing = ShopModel::withoutLang()->where('id_code', $id_code)->where('lang', $c)->first();
            if ($existing) {
                ShopModel::withoutLang()->where('id', $existing->id)->update($data);
            } else {
                $data['id_code'] = $id_code;
                $data['lang'] = $c;
                ShopModel::create($data);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.shop.edit', ['id' => $id_code]))->with('success', 'Cập nhật gian hàng thành công!');
        }
        return $this->redirect(route('admin.shop.index'))->with('success', 'Cập nhật gian hàng thành công!');
    }

    public function destroy(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        
        ShopModel::withoutLang()->where('id_code', $id_code)->delete();
        
        return $this->redirect(route('admin.shop.index'))->with('success', 'Đã xóa gian hàng thành công!');
    }

    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return $this->json(['success' => false, 'message' => 'Không có mục nào được chọn.']);
        }

        ShopModel::withoutLang()->whereIn('id_code', $ids)->delete();

        return $this->json(['success' => true, 'message' => 'Xóa thành công các gian hàng đã chọn!']);
    }

    public function updateStatusAjax(Request $request) {
        $id_code = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if (in_array($field, ['status', 'sort_order'])) {
            ShopModel::withoutLang()->where('id_code', $id_code)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
    }
}
