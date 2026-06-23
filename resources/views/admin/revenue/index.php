<?php $title = 'Thống kê & Doanh thu'; ?>

<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Doanh thu', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        
        <!-- Toolbar / Bộ lọc thời gian -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 bg-white p-3 rounded-3 shadow-sm border-0">
            <div class="mb-3 mb-md-0 d-flex flex-wrap gap-2">
                <?php $qf = $quickFilter ?? ''; ?>
                <a href="<?= route('admin.revenue.index') ?>?quick_filter=today" class="btn <?= $qf === 'today' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm">Hôm nay</a>
                <a href="<?= route('admin.revenue.index') ?>?quick_filter=this_week" class="btn <?= $qf === 'this_week' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm">Tuần này</a>
                <a href="<?= route('admin.revenue.index') ?>?quick_filter=this_month" class="btn <?= $qf === 'this_month' ? 'btn-primary' : 'btn-outline-secondary' ?> btn-sm">Tháng này</a>
                <a href="<?= route('admin.revenue.index') ?>?export=csv&date_range=<?= urlencode($date_range) ?>" class="btn btn-success btn-sm ms-md-3"><i class="fa-solid fa-file-excel me-1"></i> Xuất Excel (CSV)</a>
            </div>
            
            <form action="<?= route('admin.revenue.index') ?>" method="GET" class="d-flex align-items-center m-0 gap-2">
                <?= view('admin.components.daterange_picker', ['value' => $date_range]) ?>
                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm"><i class="fa-solid fa-filter"></i> Lọc dữ liệu</button>
            </form>
        </div>

        <?php 
        // Helper function for growth badge
        function renderGrowthBadge($growthPct) {
            if ($growthPct > 0) {
                return '<span class="badge bg-success bg-opacity-10 text-success ms-2"><i class="fa-solid fa-arrow-trend-up"></i> +' . number_format($growthPct, 1) . '%</span>';
            } elseif ($growthPct < 0) {
                return '<span class="badge bg-danger bg-opacity-10 text-danger ms-2"><i class="fa-solid fa-arrow-trend-down"></i> ' . number_format($growthPct, 1) . '%</span>';
            }
            return '<span class="badge bg-secondary bg-opacity-10 text-secondary ms-2"><i class="fa-solid fa-minus"></i> 0%</span>';
        }
        ?>

        <!-- 5 Thẻ chỉ số tổng quan -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-muted fw-semibold mb-0 small text-uppercase">Doanh Thu</p>
                            <div class="bg-primary bg-opacity-10 text-primary rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1"><?= number_format($totalRevenue, 0, ',', '.') ?>đ</h4>
                        <div class="small mt-2">
                            So với kỳ trước <?= renderGrowthBadge($growth['revenue'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-muted fw-semibold mb-0 small text-uppercase">Lợi Nhuận Ước Tính</p>
                            <div class="bg-success bg-opacity-10 text-success rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-sack-dollar"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-success"><?= number_format($totalProfit, 0, ',', '.') ?>đ</h4>
                        <div class="small mt-2">
                            So với kỳ trước <?= renderGrowthBadge($growth['profit'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-muted fw-semibold mb-0 small text-uppercase">Đơn Hàng</p>
                            <div class="bg-info bg-opacity-10 text-info rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-shopping-cart"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1"><?= number_format($totalOrders, 0, ',', '.') ?></h4>
                        <div class="small mt-2">
                            So với kỳ trước <?= renderGrowthBadge($growth['orders'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-muted fw-semibold mb-0 small text-uppercase">AOV (TB Đơn)</p>
                            <div class="bg-warning bg-opacity-10 text-warning rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-calculator"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1"><?= number_format($aov, 0, ',', '.') ?>đ</h4>
                        <div class="small mt-2">
                            So với kỳ trước <?= renderGrowthBadge($growth['aov'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="text-muted fw-semibold mb-0 small text-uppercase">Tổn thất (Đơn hủy)</p>
                            <div class="bg-danger bg-opacity-10 text-danger rounded p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-danger"><?= number_format($cancelRevenue, 0, ',', '.') ?>đ</h4>
                        <div class="small mt-2">
                            So với kỳ trước <?= renderGrowthBadge($growth['cancel'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Biểu đồ Đường -->
            <div class="col-lg-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0">Biểu đồ Doanh thu</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Trạng thái đơn -->
            <div class="col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0">Trạng thái đơn hàng</h6>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <canvas id="statusChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Phương thức thanh toán -->
            <div class="col-lg-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0">Thanh toán</h6>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <canvas id="paymentChart" style="min-height: 200px; height: 200px; max-height: 200px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="row g-4">
            <!-- Top sản phẩm -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2">
                        <h6 class="fw-bold mb-0">Top Sản Phẩm Bán Chạy</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Sản phẩm</th>
                                        <th class="text-center">Đã bán</th>
                                        <th class="text-end pe-3">Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($topProducts as $item): ?>
                                        <tr>
                                            <td class="ps-3 fw-medium">
                                                <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($item['product_name']) ?>">
                                                    <?= htmlspecialchars($item['product_name']) ?>
                                                </div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?= $item['qty'] ?></span></td>
                                            <td class="text-end text-success fw-semibold pe-3"><?= number_format($item['rev'], 0, ',', '.') ?>đ</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top khách hàng VIP -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-2">
                        <h6 class="fw-bold mb-0">Top Khách Hàng VIP</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Khách hàng</th>
                                        <th class="text-center">Số đơn</th>
                                        <th class="text-end pe-3">Tổng chi tiêu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($topSpenders)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach($topSpenders as $cust): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?= htmlspecialchars($cust['name']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($cust['phone']) ?></div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info border border-info-subtle"><?= $cust['orders'] ?></span></td>
                                            <td class="text-end text-primary fw-semibold pe-3"><?= number_format($cust['spent'], 0, ',', '.') ?>đ</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Đăng ký JS cho view -->
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. Biểu đồ Đường (Doanh thu)
    const ctxLine = document.getElementById('revenueChart').getContext('2d');
    
    // Tạo gradient fill
    let gradientLine = ctxLine.createLinearGradient(0, 0, 0, 400);
    gradientLine.addColorStop(0, 'rgba(13, 110, 253, 0.4)');
    gradientLine.addColorStop(1, 'rgba(13, 110, 253, 0.0)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: <?= $chartLabels ?>,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: <?= $chartRevenues ?>,
                backgroundColor: gradientLine,
                borderColor: '#0d6efd',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#0d6efd',
                pointBorderWidth: 2,
                pointRadius: 4,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#e9ecef' },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000) + 'Tr';
                            if (value >= 1000) return (value / 1000) + 'k';
                            return value;
                        }
                    }
                },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Biểu đồ Doughnut (Trạng thái đơn)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: ['Mới', 'Đang xử lý', 'Đang giao', 'Hoàn thành', 'Đã hủy'],
            datasets: [{
                data: <?= $statusData ?>,
                backgroundColor: ['#6c757d', '#0dcaf0', '#ffc107', '#198754', '#dc3545'],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: {size: 11} } }
            }
        }
    });

    // 3. Biểu đồ Doughnut (Phương thức thanh toán)
    const ctxPayment = document.getElementById('paymentChart').getContext('2d');
    new Chart(ctxPayment, {
        type: 'doughnut',
        data: {
            labels: <?= $paymentLabels ?>,
            datasets: [{
                data: <?= $paymentData ?>,
                backgroundColor: ['#0d6efd', '#20c997', '#fd7e14', '#6610f2', '#e83e8c'],
                borderWidth: 0,
                cutout: '70%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: {size: 11} } }
            }
        }
    });
});
</script>
