<?php

namespace App\Controllers\Admin;

use App\Models\OrderModel;
use App\Models\OrderHistoryModel;
use App\Core\Request;

class OrderController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = OrderModel::orderBy('created_at', 'DESC');
        
        $keyword = $request->input('keyword');
        if (!empty($keyword)) {
            $kw = '%' . $keyword . '%';
            $query->whereRaw('(order_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)', [$kw, $kw, $kw]);
        }
        
        $order_status = $request->input('order_status');
        if ($order_status !== null && $order_status !== '') {
            $query->where('order_status', intval($order_status));
        }

        $payment_status = $request->input('payment_status');
        if ($payment_status !== null && $payment_status !== '') {
            $query->where('payment_status', intval($payment_status));
        }

        $date_range = $request->input('date_range');
        if (!empty($date_range)) {
            $dates = explode(' - ', $date_range);
            if (count($dates) == 2) {
                // Ensure dates are parsed correctly
                $start = trim($dates[0]) . ' 00:00:00';
                $end = trim($dates[1]) . ' 23:59:59';
                $query->whereRaw('created_at BETWEEN ? AND ?', [$start, $end]);
            }
        }

        $items = $query->paginate(20);

        return $this->render('admin.order.index', [
            'title' => 'Quản lý Đơn hàng',
            'items' => $items,
            'keyword' => $keyword,
            'order_status' => $order_status,
            'payment_status' => $payment_status,
            'date_range' => $date_range
        ]);
    }

    public function show(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $order = OrderModel::find($id);
        
        if (!$order) {
            return $this->redirect(route('admin.order.index'))->with('error', 'Không tìm thấy đơn hàng!');
        }

        // Fetch related items and history
        $items = \App\Models\OrderItemModel::where('order_id', $order->id)->get();
        $history = \App\Models\OrderHistoryModel::where('order_id', $order->id)->orderBy('created_at', 'DESC')->get();

        return $this->render('admin.order.show', [
            'title' => 'Chi tiết Đơn hàng: ' . $order->order_code,
            'order' => $order,
            'items' => $items,
            'history' => $history
        ]);
    }

    public function updateStatus(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $order = OrderModel::find($id);
        
        if (!$order) {
            return $this->redirect(route('admin.order.index'))->with('error', 'Đơn hàng không tồn tại.');
        }

        $new_order_status = $request->input('order_status');
        $new_payment_status = $request->input('payment_status');
        $note = $request->input('note', '');

        $statusChanged = false;
        $old_status = $order->order_status;
        $historyNote = '';

        if ($new_order_status !== null && $new_order_status != $order->order_status) {
            $order->order_status = $new_order_status;
            $statusChanged = true;
            $historyNote .= "Cập nhật trạng thái xử lý. ";
        }

        if ($new_payment_status !== null && $new_payment_status != $order->payment_status) {
            $order->payment_status = $new_payment_status;
            $statusChanged = true;
            $historyNote .= "Cập nhật trạng thái thanh toán. ";
        }

        if ($note) {
            $historyNote .= "Ghi chú: $note";
        }

        if ($statusChanged || $note) {
            $order->save();
            
            // Add to history
            OrderHistoryModel::create([
                'order_id' => $order->id,
                'status_from' => $old_status,
                'status_to' => $order->order_status,
                'note' => $historyNote,
                'created_by' => user()->id ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $save_action = $request->input('save_action');
            if ($save_action == 'exit') {
                return $this->redirect(route('admin.order.index'))->with('success', 'Cập nhật thành công!');
            }
            return $this->redirect(route('admin.order.show', ['id' => $order->id]))->with('success', 'Cập nhật thành công!');
        }

        $save_action = $request->input('save_action');
        if ($save_action == 'exit') {
            return $this->redirect(route('admin.order.index'))->with('warning', 'Không có sự thay đổi nào.');
        }
        return $this->redirect(route('admin.order.show', ['id' => $order->id]))->with('warning', 'Không có sự thay đổi nào.');
    }

    public function print(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $order = OrderModel::find($id);
        
        if (!$order) {
            die('Không tìm thấy đơn hàng!');
        }

        $items = \App\Models\OrderItemModel::where('order_id', $order->id)->get();

        $view = new \App\Core\View();
        return $view->render('admin.order.print', [
            'order' => $order,
            'items' => $items
        ]);
    }
}
