<?php

namespace App\Controllers\Admin;

use App\Models\PromoCodeModel;
use App\Models\PromoCodeUsageModel;
use App\Core\Request;

class PromoCodeController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = PromoCodeModel::query();
        
        $keyword = $request->input('keyword');
        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%')
                  ->orWhere('code', 'LIKE', '%' . $keyword . '%');
            });
        }
        
        $hien_thi = $request->input('hien_thi');
        if ($hien_thi !== null && $hien_thi !== '') {
            $query->where('is_active', $hien_thi);
        }

        $items = $query->orderBy('id', 'DESC')->paginate(20);

        return $this->render('admin.promo_code.index', [
            'items' => $items,
            'title' => 'Quản lý Mã Giảm Giá',
            'keyword' => $keyword,
            'hien_thi' => $hien_thi
        ]);
    }

    public function create()
    {
        return $this->render('admin.promo_code.form', [
            'title' => 'Thêm Mã Giảm Giá',
            'item' => []
        ]);
    }

    public function edit(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $item = PromoCodeModel::find($id);
        
        if (!$item) {
            return $this->redirect(route('admin.promo_code.index'))->with('error', 'Không tìm thấy mã giảm giá!');
        }

        return $this->render('admin.promo_code.form', [
            'title' => 'Sửa Mã Giảm Giá: ' . $item->code,
            'item' => $item
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        return $this->save($request, $id);
    }

    private function save(Request $request, $id = null)
    {
        $code = strtoupper(trim($request->input('code')));
        if (empty($code)) {
            return $this->back()->with('error', 'Mã giảm giá không được để trống!');
        }

        // Kiểm tra mã trùng lặp
        $checkQuery = PromoCodeModel::where('code', $code);
        if ($id) {
            $checkQuery->where('id', '!=', $id);
        }
        if ($checkQuery->exists()) {
            return $this->back()->with('error', 'Mã giảm giá này đã tồn tại!');
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if (strtotime($startDate) >= strtotime($endDate)) {
            return $this->back()->with('error', 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu!');
        }

        $discountType = intval($request->input('discount_type', 1));
        $discountValue = floatval($request->input('discount_value', 0));
        
        if ($discountType == 1 && $discountValue > 100) {
            return $this->back()->with('error', 'Giảm theo % không được vượt quá 100%!');
        }

        $data = [
            'shop_id' => intval($request->input('shop_id', 0)),
            'code' => $code,
            'name' => trim($request->input('name')),
            'description' => trim($request->input('description', '')),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'max_discount_amount' => floatval($request->input('max_discount_amount', 0)),
            'min_order_amount' => floatval($request->input('min_order_amount', 0)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'usage_limit' => intval($request->input('usage_limit', 0)),
            'usage_per_user' => intval($request->input('usage_per_user', 1)),
            'apply_to' => intval($request->input('apply_to', 1)),
            'is_active' => $request->input('is_active') !== null ? 1 : 0
        ];

        if ($id) {
            PromoCodeModel::where('id', $id)->update($data);
            $msg = 'Cập nhật mã giảm giá thành công!';
        } else {
            $id = PromoCodeModel::insertGetId($data);
            $msg = 'Thêm mã giảm giá thành công!';
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.promo_code.edit', ['id' => $id]))->with('success', $msg);
        }
        
        return $this->redirect(route('admin.promo_code.index'))->with('success', $msg);
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field', 'is_active');
        $value = $request->input('value', 0);
        
        if ($field !== 'is_active') {
            return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
        }
        
        $item = PromoCodeModel::find($id);
        if ($item) {
            PromoCodeModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu!']);
    }

    public function destroy(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        
        // Kiểm tra xem mã đã được sử dụng chưa
        if (PromoCodeUsageModel::where('promo_code_id', $id)->exists()) {
            return $this->json(['success' => false, 'message' => 'Không thể xóa mã đã có lịch sử sử dụng! Hãy tắt trạng thái thay vì xóa.']);
        }
        
        if (PromoCodeModel::where('id', $id)->delete()) {
            return $this->json(['success' => true, 'message' => 'Xóa mã giảm giá thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra, không thể xóa!']);
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'Không có mục nào được chọn!']);
        }
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($ids as $id) {
            if (!PromoCodeUsageModel::where('promo_code_id', $id)->exists()) {
                if (PromoCodeModel::where('id', $id)->delete()) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++; // Bỏ qua xóa nếu đã được sử dụng
            }
        }
        
        if ($successCount > 0) {
            $msg = "Đã xóa thành công {$successCount} mã giảm giá.";
            if ($errorCount > 0) {
                $msg .= " Bỏ qua {$errorCount} mã đã có lịch sử sử dụng hoặc lỗi.";
            }
            return $this->json(['success' => true, 'message' => $msg]);
        }
        
        return $this->json(['success' => false, 'message' => 'Không thể xóa các mã đã chọn (có thể đã được sử dụng)!']);
    }
    
    public function generateCodeAjax(Request $request)
    {
        $model = new PromoCodeModel();
        $code = $model->generateUniqueCode();
        return $this->json(['success' => true, 'code' => $code]);
    }
}
