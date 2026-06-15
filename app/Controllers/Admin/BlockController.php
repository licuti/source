<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\BlockModel;

class BlockController extends BaseAdminController {
    
    public function index(Request $request) {
        $keyword = trim($request->input('keyword', ''));
        $status  = $request->input('status', '');
        $page    = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;

        $query = BlockModel::query()->where('lang', config('app.locale', 'vi'));
        
        if ($keyword !== '') {
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                  ->orWhere('alias', 'LIKE', "%{$keyword}%");
            });
        }
        if ($status !== '') {
            $query->where('is_active', $status);
        }

        $query->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC');

        $totalRows = $query->count();
        $totalPages = max(1, ceil($totalRows / $limit));
        $offset = ($page - 1) * $limit;

        $items = $query->limit($limit, $offset)->get();

        return $this->render('admin.block.index', compact('items', 'keyword', 'status', 'page', 'totalPages', 'totalRows'));
    }

    public function create(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        return $this->render('admin.block.form', compact('langs'));
    }

    public function store(Request $request) {
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $name = $request->input('name', '');
        $alias = $request->input('alias', '');
        $schema_config = $request->input('schema_config', '[]');
        $sort_order = (int)$request->input('sort_order', 0);
        $is_active = $request->input('is_active') ? 1 : 0;
        
        // Find max id_code
        $maxIdCodeRow = BlockModel::query()->orderBy('id_code', 'DESC')->first();
        $newIdCode = $maxIdCodeRow ? $maxIdCodeRow->id_code + 1 : 1;

        foreach ($langs as $l) {
            $c = $l['code'];
            $data = [
                'id_code' => $newIdCode,
                'lang' => $c,
                'name' => $name,
                'alias' => $alias,
                'schema_config' => $schema_config,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
            ];
            BlockModel::create($data);
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.block.edit', ['id' => $newIdCode]))->with('success', 'Thêm mới khối thành công!');
        }
        return $this->redirect(route('admin.block.index'))->with('success', 'Thêm mới khối thành công!');
    }

    public function edit(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $translations = BlockModel::query()->where('id_code', $id_code)->get();
        if (empty($translations)) {
            return $this->redirect(route('admin.block.index'))->with('error', 'Không tìm thấy khối giao diện!');
        }
        
        $firstItem = $translations[0];

        return $this->render('admin.block.form', compact('langs', 'firstItem'));
    }

    public function update(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $name = $request->input('name', '');
        $alias = $request->input('alias', '');
        $schema_config = $request->input('schema_config', '[]');
        $sort_order = (int)$request->input('sort_order', 0);
        $is_active = $request->input('is_active') ? 1 : 0;

        foreach ($langs as $l) {
            $c = $l['code'];
            
            $data = [
                'name' => $name,
                'alias' => $alias,
                'schema_config' => $schema_config,
                'sort_order' => $sort_order,
                'is_active' => $is_active,
            ];

            $existing = BlockModel::query()->where('id_code', $id_code)->where('lang', $c)->first();
            if ($existing) {
                BlockModel::query()->where('id', $existing->id)->update($data);
            } else {
                $data['id_code'] = $id_code;
                $data['lang'] = $c;
                BlockModel::create($data);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.block.edit', ['id' => $id_code]))->with('success', 'Cập nhật khối thành công!');
        }
        return $this->redirect(route('admin.block.index'))->with('success', 'Cập nhật khối thành công!');
    }

    public function destroy(Request $request, $params) {
        $id_code = is_array($params) ? ($params['id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        
        // Cảnh báo: Việc xóa Block sẽ làm mồ côi các Block Items. Chúng ta nên xóa luôn các Items?
        // Hiện tại cứ xóa Block trước
        BlockModel::query()->where('id_code', $id_code)->delete();
        \App\Models\BlockItemModel::query()->where('block_id', $id_code)->delete();
        
        return $this->redirect(route('admin.block.index'))->with('success', 'Đã xóa khối và tất cả các items thuộc khối!');
    }

    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return $this->json(['success' => false, 'message' => 'Không có mục nào được chọn.']);
        }

        BlockModel::query()->whereIn('id_code', $ids)->delete();
        \App\Models\BlockItemModel::query()->whereIn('block_id', $ids)->delete();

        return $this->json(['success' => true, 'message' => 'Xóa thành công các khối đã chọn!']);
    }

    public function updateStatusAjax(Request $request) {
        $id_code = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if ($field === 'is_active') {
            BlockModel::query()->where('id_code', $id_code)->update(['is_active' => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
    }
}
