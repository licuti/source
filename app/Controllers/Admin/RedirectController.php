<?php
namespace App\Controllers\Admin;

use App\Models\RedirectModel;
use App\Core\Request;

class RedirectController extends BaseAdminController {
    
    public function index(Request $request) {
        $keyword = $request->input('keyword', '');
        
        $query = RedirectModel::query();
        
        if (!empty($keyword)) {
            $query->where('old_url', 'LIKE', "%{$keyword}%")
                  ->orWhere('new_url', 'LIKE', "%{$keyword}%");
        }
        
        $query->orderBy('id', 'DESC');
        
        $items = $query->paginate(20);
        
        return $this->render('admin.redirect.index', compact('items', 'keyword'));
    }
    
    public function create(Request $request) {
        return $this->render('admin.redirect.form');
    }
    
    public function store(Request $request) {
        $data = [
            'old_url' => trim($request->input('old_url', '')),
            'new_url' => trim($request->input('new_url', '')),
            'status' => $request->input('status') !== null ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if (empty($data['old_url']) || empty($data['new_url'])) {
            return $this->redirect(route('admin.redirect.create'))->with('error', 'Vui lòng nhập đầy đủ Link cũ và Link mới');
        }
        
        // Cắt bỏ domain nếu có, chỉ lấy path để so sánh dễ hơn
        $data['old_url'] = parse_url($data['old_url'], PHP_URL_PATH) ?? $data['old_url'];
        
        $saveAction = $request->input('save_action', 'exit');
        
        try {
            $id = RedirectModel::insertGetId($data);
            $msg = 'Thêm chuyển hướng thành công';
            if ($saveAction === 'continue') {
                return $this->redirect(route('admin.redirect.edit', ['id' => $id]))->with('success', $msg);
            }
            return $this->redirect(route('admin.redirect.index'))->with('success', $msg);
        } catch (\Exception $e) {
            return $this->redirect(route('admin.redirect.create'))->with('error', 'Lỗi: Link cũ có thể đã tồn tại');
        }
    }
    
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $item = RedirectModel::find($id);
        if (!$item) {
            return $this->redirect(route('admin.redirect.index'))->with('error', 'Bản ghi không tồn tại');
        }
        return $this->render('admin.redirect.form', compact('item'));
    }
    
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $item = RedirectModel::find($id);
        if (!$item) {
            return $this->redirect(route('admin.redirect.index'))->with('error', 'Bản ghi không tồn tại');
        }
        
        $data = [
            'old_url' => trim($request->input('old_url', '')),
            'new_url' => trim($request->input('new_url', '')),
            'status' => $request->input('status') !== null ? 1 : 0,
        ];
        
        $data['old_url'] = parse_url($data['old_url'], PHP_URL_PATH) ?? $data['old_url'];
        
        $saveAction = $request->input('save_action', 'exit');
        
        try {
            RedirectModel::where('id', $id)->update($data);
            $msg = 'Cập nhật thành công';
            if ($saveAction === 'continue') {
                return $this->redirect(route('admin.redirect.edit', ['id' => $id]))->with('success', $msg);
            }
            return $this->redirect(route('admin.redirect.index'))->with('success', $msg);
        } catch (\Exception $e) {
            return $this->redirect(route('admin.redirect.edit', ['id' => $id]))->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        RedirectModel::where('id', $id)->delete();
        return $this->redirect(route('admin.redirect.index'))->with('success', 'Đã xóa bản ghi');
    }
    
    public function destroyMultiple(Request $request) {
        $ids = $request->input('ids', []);
        if (!empty($ids) && is_array($ids)) {
            RedirectModel::whereIn('id', $ids)->delete();
            return $this->json(['success' => true]);
        }
        return $this->json(['success' => false, 'message' => 'Chưa chọn bản ghi nào']);
    }
    
    public function updateStatusAjax(Request $request) {
        $id = (int)$request->input('id');
        $value = (int)$request->input('value', 0);
        
        RedirectModel::where('id', $id)->update(['status' => $value]);
        return $this->json(['success' => true]);
    }
}
