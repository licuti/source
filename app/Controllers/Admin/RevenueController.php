<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\OrderModel;
use App\Models\OrderItemModel;

class RevenueController extends BaseAdminController
{
    public function index(Request $request)
    {
        // Date range filter
        $date_range = $request->input('date_range', '');
        $startDate = '';
        $endDate = '';

        // Xử lý Quick Filters nếu người dùng click
        $quickFilter = $request->input('quick_filter', '');
        if ($quickFilter === 'today') {
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            $date_range = date('d/m/Y') . ' - ' . date('d/m/Y');
        } elseif ($quickFilter === 'this_week') {
            $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            $date_range = date('d/m/Y', strtotime('monday this week')) . ' - ' . date('d/m/Y', strtotime('sunday this week'));
        } elseif ($quickFilter === 'this_month') {
            $startDate = date('Y-m-01 00:00:00');
            $endDate = date('Y-m-t 23:59:59');
            $date_range = date('01/m/Y') . ' - ' . date('t/m/Y');
        } elseif (!empty($date_range)) {
            $dates = explode(' - ', $date_range);
            if (count($dates) == 2) {
                // Assuming format DD/MM/YYYY
                $start = \DateTime::createFromFormat('d/m/Y', trim($dates[0]));
                $end = \DateTime::createFromFormat('d/m/Y', trim($dates[1]));
                if ($start && $end) {
                    $startDate = $start->format('Y-m-d 00:00:00');
                    $endDate = $end->format('Y-m-d 23:59:59');
                }
            }
        }

        // Nếu không có filter, mặc định lấy 30 ngày gần nhất
        if (empty($startDate)) {
            $startDate = date('Y-m-d 00:00:00', strtotime('-29 days'));
            $endDate = date('Y-m-d 23:59:59');
            $date_range = date('d/m/Y', strtotime('-29 days')) . ' - ' . date('d/m/Y');
        }

        // Tính khoảng thời gian của kỳ trước
        $periodStart = new \DateTime($startDate);
        $periodEnd = new \DateTime($endDate);
        $diff = $periodStart->diff($periodEnd);
        $days = $diff->days;

        $prevEndDateObj = (clone $periodStart)->modify('-1 day');
        $prevEndDate = $prevEndDateObj->format('Y-m-d 23:59:59');
        $prevStartDate = (clone $prevEndDateObj)->modify('-' . $days . ' days')->format('Y-m-d 00:00:00');

        // Tạo Query Builder gốc cho các thống kê hoàn thành (order_status = 3)
        $baseOrderQuery = OrderModel::where('order_status', 3)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate);

        $prevOrderQuery = OrderModel::where('order_status', 3)
            ->where('created_at', '>=', $prevStartDate)
            ->where('created_at', '<=', $prevEndDate);

        // 1. Tổng doanh thu & tổng đơn hàng (Kỳ này)
        $revData = (clone $baseOrderQuery)->select('SUM(grand_total) as total_revenue', 'COUNT(id) as total_orders')->first();
        $totalRevenue = (float) ($revData->total_revenue ?? 0);
        $totalOrdersCompleted = (int) ($revData->total_orders ?? 0);

        // Tổng doanh thu & đơn (Kỳ trước)
        $prevRevData = (clone $prevOrderQuery)->select('SUM(grand_total) as total_revenue', 'COUNT(id) as total_orders')->first();
        $prevTotalRevenue = (float) ($prevRevData->total_revenue ?? 0);
        $prevTotalOrdersCompleted = (int) ($prevRevData->total_orders ?? 0);

        // 2. Lợi nhuận (Profit)
        // Profit = SUM(qty * (price - cost_price))
        $profitData = OrderItemModel::select('SUM(db_order_items.quantity * (db_order_items.price - IFNULL(db_products.cost_price, 0))) as total_profit')
            ->join('db_orders', 'db_orders.id', '=', 'db_order_items.order_id')
            ->join('db_products', 'db_products.id', '=', 'db_order_items.product_id', 'LEFT')
            ->whereRaw('db_orders.order_status = 3')
            ->whereRaw('db_orders.created_at >= ?', [$startDate])
            ->whereRaw('db_orders.created_at <= ?', [$endDate])
            ->first();
        $totalProfit = (float) ($profitData->total_profit ?? 0);

        $prevProfitData = OrderItemModel::select('SUM(db_order_items.quantity * (db_order_items.price - IFNULL(db_products.cost_price, 0))) as total_profit')
            ->join('db_orders', 'db_orders.id', '=', 'db_order_items.order_id')
            ->join('db_products', 'db_products.id', '=', 'db_order_items.product_id', 'LEFT')
            ->whereRaw('db_orders.order_status = 3')
            ->whereRaw('db_orders.created_at >= ?', [$prevStartDate])
            ->whereRaw('db_orders.created_at <= ?', [$prevEndDate])
            ->first();
        $prevTotalProfit = (float) ($prevProfitData->total_profit ?? 0);

        // 3. Đơn Hủy
        $cancelData = OrderModel::where('order_status', 4)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->select('SUM(grand_total) as lost_revenue', 'COUNT(id) as total_orders')
            ->first();
        $cancelRevenue = (float) ($cancelData->lost_revenue ?? 0);
        
        $prevCancelData = OrderModel::where('order_status', 4)
            ->where('created_at', '>=', $prevStartDate)
            ->where('created_at', '<=', $prevEndDate)
            ->select('SUM(grand_total) as lost_revenue', 'COUNT(id) as total_orders')
            ->first();
        $prevCancelRevenue = (float) ($prevCancelData->lost_revenue ?? 0);

        // 4. AOV
        $aov = $totalOrdersCompleted > 0 ? $totalRevenue / $totalOrdersCompleted : 0;
        $prevAov = $prevTotalOrdersCompleted > 0 ? $prevTotalRevenue / $prevTotalOrdersCompleted : 0;

        // Hàm tính phần trăm thay đổi
        $calcGrowth = function($current, $previous) {
            if ($previous == 0) return $current > 0 ? 100 : 0;
            return (($current - $previous) / $previous) * 100;
        };

        $growth = [
            'revenue' => $calcGrowth($totalRevenue, $prevTotalRevenue),
            'profit' => $calcGrowth($totalProfit, $prevTotalProfit),
            'orders' => $calcGrowth($totalOrdersCompleted, $prevTotalOrdersCompleted),
            'cancel' => $calcGrowth($cancelRevenue, $prevCancelRevenue),
            'aov' => $calcGrowth($aov, $prevAov),
        ];

        // 5. Biểu đồ doanh thu theo ngày
        $chartDataRaw = (clone $baseOrderQuery)
            ->select('DATE(created_at) as order_date', 'SUM(grand_total) as daily_revenue')
            ->groupBy('order_date')
            ->orderBy('order_date', 'ASC')
            ->get();

        $chartLabels = [];
        $chartRevenues = [];
        // Điền số 0 cho các ngày không có dữ liệu
        $dateRangePeriod = new \DatePeriod(new \DateTime($startDate), new \DateInterval('P1D'), (new \DateTime($endDate))->modify('+1 day'));

        $mappedData = [];
        foreach ($chartDataRaw as $row) {
            $mappedData[$row->order_date] = (float) $row->daily_revenue;
        }

        foreach ($dateRangePeriod as $dt) {
            $d = $dt->format('Y-m-d');
            $chartLabels[] = $dt->format('d/m');
            $chartRevenues[] = $mappedData[$d] ?? 0;
        }

        // 6. Cơ cấu trạng thái đơn hàng (Tất cả trạng thái)
        $statusDataRaw = OrderModel::select('order_status', 'COUNT(id) as count')
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->groupBy('order_status')
            ->get();
            
        $statusCounts = [];
        foreach ($statusDataRaw as $row) {
            $statusCounts[$row->order_status] = $row->count;
        }
        
        $statusData = [
            (int)($statusCounts[0] ?? 0),
            (int)($statusCounts[1] ?? 0),
            (int)($statusCounts[2] ?? 0),
            (int)($statusCounts[3] ?? 0),
            (int)($statusCounts[4] ?? 0),
        ];

        // 7. Phương thức thanh toán (Doughnut Chart 2)
        $paymentDataRaw = OrderModel::select('db_orders.payment_method_id', 'COUNT(db_orders.id) as count', 'db_payment_methods.name as method_name')
            ->join('db_payment_methods', 'db_payment_methods.id', '=', 'db_orders.payment_method_id', 'LEFT')
            ->whereRaw('db_orders.created_at >= ?', [$startDate])
            ->whereRaw('db_orders.created_at <= ?', [$endDate])
            ->groupBy('payment_method_id', 'method_name')
            ->get();
        
        $paymentLabels = [];
        $paymentData = [];
        foreach ($paymentDataRaw as $row) {
            $paymentLabels[] = $row->method_name ?: 'Khác';
            $paymentData[] = (int) $row->count;
        }

        // 8. Top sản phẩm bán chạy (Top 10)
        $topProductsRaw = OrderItemModel::select('db_order_items.product_name', 'SUM(db_order_items.quantity) as qty', 'SUM(db_order_items.price * db_order_items.quantity) as rev')
            ->join('db_orders', 'db_orders.id', '=', 'db_order_items.order_id')
            ->whereRaw('db_orders.order_status = 3')
            ->whereRaw('db_orders.created_at >= ?', [$startDate])
            ->whereRaw('db_orders.created_at <= ?', [$endDate])
            ->groupBy('product_id', 'product_name')
            ->orderBy('qty', 'DESC')
            ->limit(10)
            ->get();

        $topProducts = [];
        foreach ($topProductsRaw as $prod) {
            $topProducts[] = [
                'product_name' => $prod->product_name,
                'qty' => $prod->qty,
                'rev' => $prod->rev
            ];
        }

        // 9. Top Khách hàng VIP
        $topSpendersRaw = OrderModel::select('customer_name', 'customer_phone', 'COUNT(id) as total_orders', 'SUM(grand_total) as total_spent')
            ->where('order_status', 3)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->groupBy('customer_id', 'customer_name', 'customer_phone')
            ->orderBy('total_spent', 'DESC')
            ->limit(10)
            ->get();

        $topSpenders = [];
        foreach ($topSpendersRaw as $cust) {
            $topSpenders[] = [
                'name' => $cust->customer_name ?: 'Khách vãng lai',
                'phone' => $cust->customer_phone,
                'orders' => $cust->total_orders,
                'spent' => $cust->total_spent
            ];
        }

        // Xuất Excel (CSV)
        if ($request->input('export') === 'csv') {
            return $this->exportCsv($startDate, $endDate, $totalRevenue, $totalProfit, $totalOrdersCompleted, $topProducts);
        }

        return $this->render('admin.revenue.index', [
            'date_range' => $date_range,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'totalOrders' => $totalOrdersCompleted,
            'cancelRevenue' => $cancelRevenue,
            'aov' => $aov,
            'growth' => $growth,
            'chartLabels' => json_encode($chartLabels),
            'chartRevenues' => json_encode($chartRevenues),
            'statusData' => json_encode($statusData),
            'paymentLabels' => json_encode($paymentLabels),
            'paymentData' => json_encode($paymentData),
            'topProducts' => $topProducts,
            'topSpenders' => $topSpenders,
            'quickFilter' => $quickFilter
        ]);
    }

    private function exportCsv($startDate, $endDate, $totalRevenue, $totalProfit, $totalOrders, $topProducts)
    {
        $filename = "bao_cao_doanh_thu_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        // Thêm BOM để Excel đọc đúng UTF-8
        fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
        
        fputcsv($output, ['BÁO CÁO DOANH THU KINH DOANH']);
        fputcsv($output, ['Từ ngày', $startDate, 'Đến ngày', $endDate]);
        fputcsv($output, []);
        
        fputcsv($output, ['CHỈ SỐ TỔNG QUAN']);
        fputcsv($output, ['Tổng doanh thu', number_format($totalRevenue, 0, ',', '.') . ' VNĐ']);
        fputcsv($output, ['Lợi nhuận ước tính', number_format($totalProfit, 0, ',', '.') . ' VNĐ']);
        fputcsv($output, ['Đơn thành công', $totalOrders]);
        fputcsv($output, []);
        
        fputcsv($output, ['TOP SẢN PHẨM BÁN CHẠY']);
        fputcsv($output, ['STT', 'Tên sản phẩm', 'Số lượng bán', 'Doanh thu']);
        
        foreach ($topProducts as $idx => $prod) {
            fputcsv($output, [
                $idx + 1,
                $prod['product_name'],
                $prod['qty'],
                number_format($prod['rev'], 0, ',', '.') . ' VNĐ'
            ]);
        }
        
        fclose($output);
        exit;
    }
}
