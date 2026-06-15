<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\BlockModel;
use App\Models\BlockItemModel;

class BlockItemController extends BaseAdminController {
    
    public function index(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        
        $block = BlockModel::query()->where('id_code', $block_id)->first();
        if (!$block) {
            return $this->redirect(route('admin.block.index'))->with('error', 'Khối không tồn tại!');
        }

        $keyword = trim($request->input('keyword', ''));
        $status  = $request->input('status', '');
        $page    = (int)$request->input('page', 1);
        if ($page < 1) $page = 1;
        $limit = 10;

        $query = BlockItemModel::query()
            ->where('block_id', $block_id)
            ->where('lang', config('app.locale', 'vi'));
        
        if ($status !== '') {
            $query->where('is_active', $status);
        }
        
        // Note: For JSON search by keyword, MySQL 5.7+ supports JSON_SEARCH but it's complex. 
        // For now, we do a simple LIKE on the whole JSON string if keyword exists
        if ($keyword !== '') {
            $query->where('data_payload', 'LIKE', "%{$keyword}%");
        }

        $query->orderBy('sort_order', 'ASC')->orderBy('id', 'DESC');

        $totalRows = $query->count();
        $totalPages = max(1, ceil($totalRows / $limit));
        $offset = ($page - 1) * $limit;

        $items = $query->limit($limit, $offset)->get();

        return $this->render('admin.block_item.index', compact('items', 'block', 'keyword', 'status', 'page', 'totalPages', 'totalRows'));
    }

    public function create(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        
        $block = BlockModel::query()->where('id_code', $block_id)->first();
        if (!$block) return $this->redirect(route('admin.block.index'));

        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        return $this->render('admin.block_item.form', compact('langs', 'block'));
    }

    public function store(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $sort_order = (int)$request->input('sort_order', 0);
        $is_active = $request->input('is_active') ? 1 : 0;
        
        // Dynamic Fields from payload
        $dynamicData = $request->input('data_payload', []);
        
        // Find max id_code
        $maxIdCodeRow = BlockItemModel::query()->orderBy('id_code', 'DESC')->first();
        $newIdCode = $maxIdCodeRow ? $maxIdCodeRow->id_code + 1 : 1;

        foreach ($langs as $l) {
            $c = $l['code'];
            $payload = $dynamicData[$c] ?? [];
            
            $data = [
                'block_id' => $block_id,
                'id_code' => $newIdCode,
                'lang' => $c,
                'data_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'sort_order' => $sort_order,
                'is_active' => $is_active,
            ];
            BlockItemModel::create($data);
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.block_item.edit', ['block_id' => $block_id, 'id' => $newIdCode]))->with('success', 'Thêm mới mục thành công!');
        }
        return $this->redirect(route('admin.block_item.index', ['block_id' => $block_id]))->with('success', 'Thêm mới mục thành công!');
    }

    public function edit(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $id_code = is_array($params) ? ($params['id'] ?? $params[2] ?? $params[1] ?? 0) : 0;

        $block = BlockModel::query()->where('id_code', $block_id)->first();
        if (!$block) return $this->redirect(route('admin.block.index'));

        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $translations = BlockItemModel::query()->where('id_code', $id_code)->get();
        if (empty($translations)) {
            return $this->redirect(route('admin.block_item.index', ['block_id' => $block_id]))->with('error', 'Không tìm thấy mục!');
        }
        
        $firstItem = $translations[0];
        
        // Re-construct data payload per lang
        $payloadData = [];
        foreach($translations as $t) {
            $data = $t->data_payload ?? [];
            $payloadData[$t->lang] = is_string($data) ? (json_decode($data, true) ?: []) : $data;
        }
        $firstItem->parsed_payload = $payloadData;

        return $this->render('admin.block_item.form', compact('langs', 'block', 'firstItem'));
    }

    public function update(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $id_code = is_array($params) ? ($params['id'] ?? $params[2] ?? $params[1] ?? 0) : 0;

        $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
        
        $sort_order = (int)$request->input('sort_order', 0);
        $is_active = $request->input('is_active') ? 1 : 0;
        
        $dynamicData = $request->input('data_payload', []);

        foreach ($langs as $l) {
            $c = $l['code'];
            $payload = $dynamicData[$c] ?? [];
            
            $data = [
                'data_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'sort_order' => $sort_order,
                'is_active' => $is_active,
            ];

            $existing = BlockItemModel::query()->where('id_code', $id_code)->where('lang', $c)->first();
            if ($existing) {
                BlockItemModel::query()->where('id', $existing->id)->update($data);
            } else {
                $data['block_id'] = $block_id;
                $data['id_code'] = $id_code;
                $data['lang'] = $c;
                BlockItemModel::create($data);
            }
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.block_item.edit', ['block_id' => $block_id, 'id' => $id_code]))->with('success', 'Cập nhật mục thành công!');
        }
        return $this->redirect(route('admin.block_item.index', ['block_id' => $block_id]))->with('success', 'Cập nhật mục thành công!');
    }

    public function destroy(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $id_code = is_array($params) ? ($params['id'] ?? $params[2] ?? $params[1] ?? 0) : 0;
        
        BlockItemModel::query()->where('id_code', $id_code)->delete();
        
        return $this->redirect(route('admin.block_item.index', ['block_id' => $block_id]))->with('success', 'Đã xóa mục thành công!');
    }

    public function destroyMultiple(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return $this->json(['success' => false, 'message' => 'Không có mục nào được chọn.']);
        }

        BlockItemModel::query()->whereIn('id_code', $ids)->delete();

        return $this->json(['success' => true, 'message' => 'Xóa thành công các mục đã chọn!']);
    }

    public function updateStatusAjax(Request $request, $params) {
        $id_code = $request->input('id');
        $field = $request->input('field');
        $value = $request->input('value');
        
        if ($field === 'is_active') {
            BlockItemModel::query()->where('id_code', $id_code)->update(['is_active' => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
    }

    public function updateSort(Request $request, $params) {
        $block_id = is_array($params) ? ($params['block_id'] ?? $params[1] ?? $params[0] ?? 0) : $params;
        $idsStr = $request->input('ids', '[]');
        $ids = json_decode($idsStr, true);
        
        if (empty($ids) || !is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
        }

        // Cập nhật sort_order theo vị trí trong mảng $ids
        foreach ($ids as $index => $id_code) {
            BlockItemModel::query()
                ->where('block_id', $block_id)
                ->where('id_code', $id_code)
                ->update(['sort_order' => $index + 1]);
        }

        return $this->json(['success' => true, 'message' => 'Cập nhật thứ tự thành công!']);
    }
}
