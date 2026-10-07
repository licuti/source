<?php
/**
 * Dashboard admin — AdminLTE 4 / Bootstrap 5 + component.
 * Widget / query / Chart.js chỉ chạy khi module tương ứng bật + có quyền
 * (admin_module_available → #_module_admin.hien_thi + checkPermission).
 */

if (!function_exists('dash_count')) {
    function dash_count($d, $sql)
    {
        $n = $d->num_rows($sql);
        return is_numeric($n) ? (int) $n : 0;
    }
}

if (!function_exists('dash_fmt_date')) {
    function dash_fmt_date($v)
    {
        if ($v === null || $v === '') {
            return '—';
        }
        if (is_numeric($v)) {
            $ts = (int) $v;
            return $ts > 0 ? date('d/m/Y H:i', $ts) : '—';
        }
        $ts = strtotime((string) $v);
        return $ts ? date('d/m/Y H:i', $ts) : e($v);
    }
}

if (!function_exists('dash_order_status_labels')) {
    function dash_order_status_labels()
    {
        return array(
            0 => 'Chưa xem',
            1 => 'Đang xử lý',
            2 => 'Đang giao',
            3 => 'Đã giao',
            4 => 'Trả hàng',
        );
    }
}

// —— Quyền module (cache trong admin_module_available) ——
$canSp      = function_exists('admin_module_available') && admin_module_available('san-pham');
$canBv      = function_exists('admin_module_available') && admin_module_available('bai-viet');
$canOrder   = function_exists('admin_module_available') && admin_module_available('quan-ly-don-hang');
$canLienhe  = function_exists('admin_module_available') && admin_module_available('lien-he');
$canTv      = function_exists('admin_module_available') && admin_module_available('thanh-vien');
$canDoanhThu = function_exists('admin_module_available') && admin_module_available('doanh-thu');

// —— Counts: chỉ query khi module bật ——
$countSp        = $canSp ? dash_count($d, "select id from #_sanpham where lang = 'vi'") : 0;
$countBaiviet   = $canBv ? dash_count($d, "select id from #_tintuc where lang = 'vi'") : 0;
$countDonhang   = $canOrder ? dash_count($d, 'select id from #_dathang') : 0;
$countLienheMoi = $canLienhe ? dash_count($d, 'select id from #_lienhe where da_xem = 0') : 0;
$countTv        = $canTv ? dash_count($d, 'select id from #_thanhvien') : 0;

// —— Lists (tái dùng biến sidebar nếu đã nạp; không query lại) ——
$preDon = (isset($donhangmoi) && is_array($donhangmoi)) ? $donhangmoi : array();
$preLh  = (isset($lienhemoi) && is_array($lienhemoi)) ? $lienhemoi : array();
$donhangmoi = array();
$lienhemoi  = array();
if ($canOrder) {
    if (!empty($preDon)) {
        $donhangmoi = array_slice($preDon, 0, 10);
    } else {
        $donhangmoi = $d->o_fet(
            'select id, ma_dh, ho_ten, dien_thoai, ngay_dathang, trangthai_xuly from #_dathang where trangthai_xuly = 0 order by id desc limit 10'
        ) ?: array();
    }
}
if ($canLienhe) {
    if (!empty($preLh)) {
        $lienhemoi = array_slice($preLh, 0, 10);
    } else {
        $lienhemoi = $d->o_fet(
            'select id, ho_ten, tieu_de, noi_dung, ngay_gui from #_lienhe where da_xem = 0 order by id desc limit 10'
        ) ?: array();
    }
}

// —— Chart data (chỉ khi quan-ly-don-hang) ——
$chartOrders = null;
$chartStatus = null;
$needCharts  = false;

if ($canOrder) {
    $days = 30;
    $labels = array();
    $valuesMap = array();
    for ($i = $days - 1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime('-' . $i . ' days'));
        $labels[] = date('d/m', strtotime($day));
        $valuesMap[$day] = 0;
    }
    $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $rows = $d->o_fet(
        "select ngay_dathang as d, count(id) as c from #_dathang"
        . " where ngay_dathang >= '" . $from . "'"
        . " group by ngay_dathang order by ngay_dathang asc"
    ) ?: array();
    foreach ($rows as $r) {
        $dk = isset($r['d']) ? substr((string) $r['d'], 0, 10) : '';
        if ($dk !== '' && isset($valuesMap[$dk])) {
            $valuesMap[$dk] = (int) $r['c'];
        }
    }
    $chartOrders = array(
        'labels' => $labels,
        'values' => array_values($valuesMap),
    );

    $statusLabels = dash_order_status_labels();
    $stRows = $d->o_fet(
        'select trangthai_xuly as st, count(id) as c from #_dathang group by trangthai_xuly'
    ) ?: array();
    $stMap = array();
    foreach ($stRows as $r) {
        $stMap[(int) $r['st']] = (int) $r['c'];
    }
    $sLabels = array();
    $sValues = array();
    foreach ($statusLabels as $code => $name) {
        $n = isset($stMap[$code]) ? $stMap[$code] : 0;
        if ($n > 0) {
            $sLabels[] = $name;
            $sValues[] = $n;
        }
    }
    // trạng thái lạ ngoài map
    foreach ($stMap as $code => $n) {
        if (!isset($statusLabels[$code]) && $n > 0) {
            $sLabels[] = 'Khác (' . $code . ')';
            $sValues[] = $n;
        }
    }
    if (!empty($sLabels)) {
        $chartStatus = array('labels' => $sLabels, 'values' => $sValues);
    }

    $needCharts = true;
}

// —— Stat cards ——
$stats = array();
if ($canSp) {
    $stats[] = array(
        'label' => 'Sản phẩm', 'value' => $countSp,
        'icon' => 'fa-solid fa-box', 'color' => 'success',
        'url' => admin_url(array('p' => 'san-pham', 'a' => 'man')),
    );
}
if ($canBv) {
    $stats[] = array(
        'label' => 'Bài viết', 'value' => $countBaiviet,
        'icon' => 'fa-solid fa-newspaper', 'color' => 'primary',
        'url' => admin_url(array('p' => 'bai-viet', 'a' => 'man')),
    );
}
if ($canTv) {
    $stats[] = array(
        'label' => 'Thành viên', 'value' => $countTv,
        'icon' => 'fa-solid fa-users', 'color' => 'info',
        'url' => admin_url(array('p' => 'thanh-vien', 'a' => 'man')),
    );
}
if ($canOrder) {
    $stats[] = array(
        'label' => 'Đơn hàng', 'value' => $countDonhang,
        'icon' => 'fa-solid fa-cart-shopping', 'color' => 'warning',
        'url' => admin_url(array('p' => 'quan-ly-don-hang', 'a' => 'man')),
    );
}
if ($canLienhe) {
    $stats[] = array(
        'label' => 'Liên hệ mới', 'value' => $countLienheMoi,
        'icon' => 'fa-solid fa-envelope', 'color' => 'danger',
        'url' => admin_url(array('p' => 'lien-he', 'a' => 'man')),
    );
}

$statCount = count($stats);
$statCol = 'col-6 col-lg-3';
if ($statCount === 1) {
    $statCol = 'col-12 col-md-6 col-lg-4';
} elseif ($statCount === 2) {
    $statCol = 'col-6 col-lg-6';
} elseif ($statCount === 3) {
    $statCol = 'col-6 col-lg-4';
} elseif ($statCount >= 5) {
    $statCol = 'col-6 col-md-4 col-xl';
}

$showCharts = $needCharts;
$showOrdersTable = $canOrder;
$showLienheTable = $canLienhe;
$tableCol = ($showOrdersTable && $showLienheTable) ? 'col-lg-6' : 'col-12';
$chartOrderCol = ($chartOrders && $chartStatus) ? 'col-lg-8' : 'col-12';
$chartStatusCol = ($chartOrders && $chartStatus) ? 'col-lg-4' : 'col-12';
?>

<?php component('page_header', array(
    'title'      => 'Dashboard',
    'breadcrumb' => array(
        array('label' => 'Trang chủ', 'url' => 'index.php', 'icon' => 'fa-solid fa-gauge-high'),
        array('label' => 'Tổng quan'),
    ),
)); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php if ($statCount === 0 && !$showCharts && !$showOrdersTable && !$showLienheTable): ?>
            <div class="alert alert-light border shadow-sm">
                <i class="fa-solid fa-circle-info me-2"></i>
                Chưa có module tổng quan nào được bật hoặc bạn chưa được cấp quyền.
                Hãy dùng menu bên trái để làm việc.
            </div>
        <?php endif; ?>

        <?php if ($statCount > 0): ?>
        <div class="row g-3 mb-3">
            <?php foreach ($stats as $st): ?>
                <div class="<?= e($statCol) ?>">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="rounded-3 bg-<?= e($st['color']) ?> bg-opacity-10 text-<?= e($st['color']) ?> d-flex align-items-center justify-content-center"
                                 style="width:48px;height:48px;flex-shrink:0">
                                <i class="<?= e($st['icon']) ?> fs-5"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-muted small text-truncate"><?= e($st['label']) ?></div>
                                <div class="fs-4 fw-semibold lh-1"><?= (int) $st['value'] ?></div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 pt-0">
                            <a href="<?= e($st['url']) ?>" class="small text-decoration-none">
                                Xem chi tiết <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($showCharts): ?>
        <div class="row g-3 mb-3">
            <?php if ($chartOrders): ?>
            <div class="<?= e($chartOrderCol) ?>">
                <?php
                $orderTools = '<a class="btn btn-sm btn-outline-secondary" href="'
                    . e(admin_url(array('p' => 'quan-ly-don-hang', 'a' => 'man')))
                    . '">Đơn hàng</a>';
                if ($canDoanhThu) {
                    $orderTools .= ' <a class="btn btn-sm btn-outline-primary" href="'
                        . e(admin_url(array('p' => 'doanh-thu', 'a' => 'man')))
                        . '">Doanh thu</a>';
                }
                component('card_open', array(
                    'title' => 'Đơn hàng 30 ngày gần nhất',
                    'icon'  => 'fa-solid fa-chart-column',
                    'tools' => $orderTools,
                ));
                ?>
                <?php if (array_sum($chartOrders['values']) <= 0): ?>
                    <div class="text-center text-muted small py-5">Chưa có đơn hàng trong 30 ngày qua.</div>
                <?php else: ?>
                    <div style="height:280px;position:relative">
                        <canvas id="dashChartOrders" aria-label="Biểu đồ đơn hàng"></canvas>
                    </div>
                    <script type="application/json" id="dash-chart-orders-data"><?= json_encode($chartOrders, JSON_UNESCAPED_UNICODE) ?></script>
                <?php endif; ?>
                <?php component('card_close'); ?>
            </div>
            <?php endif; ?>

            <?php if ($chartStatus): ?>
            <div class="<?= e($chartStatusCol) ?>">
                <?php component('card_open', array(
                    'title' => 'Trạng thái đơn hàng',
                    'icon'  => 'fa-solid fa-chart-pie',
                )); ?>
                <div style="height:280px;position:relative">
                    <canvas id="dashChartStatus" aria-label="Biểu đồ trạng thái"></canvas>
                </div>
                <script type="application/json" id="dash-chart-status-data"><?= json_encode($chartStatus, JSON_UNESCAPED_UNICODE) ?></script>
                <?php component('card_close'); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($showOrdersTable || $showLienheTable): ?>
        <div class="row g-3">
            <?php if ($showOrdersTable): ?>
            <div class="<?= e($tableCol) ?>">
                <?php component('card_open', array(
                    'title' => 'Đơn hàng mới',
                    'icon'  => 'fa-solid fa-bag-shopping',
                    'tools' => '<a class="btn btn-sm btn-outline-secondary" href="' . e(admin_url(array('p' => 'quan-ly-don-hang', 'a' => 'man'))) . '">Tất cả</a>',
                    'body'  => 'none',
                )); ?>
                <div class="card-body p-0">
                    <?php if (empty($donhangmoi)): ?>
                        <div class="p-4 text-center text-muted small">Không có đơn hàng chờ xử lý.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:3rem">#</th>
                                        <th>Mã ĐH</th>
                                        <th>Khách hàng</th>
                                        <th class="text-center">Ngày</th>
                                        <th class="text-center" style="width:5rem"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donhangmoi as $i => $row):
                                        $oid = (int) (isset($row['id']) ? $row['id'] : 0);
                                        $ten = isset($row['ho_ten']) ? $row['ho_ten'] : '—';
                                        $ma  = isset($row['ma_dh']) ? $row['ma_dh'] : ('#' . $oid);
                                        $ngay = isset($row['ngay_dathang']) ? $row['ngay_dathang'] : '';
                                        ?>
                                        <tr>
                                            <td class="text-center text-muted"><?= $i + 1 ?></td>
                                            <td class="small text-nowrap"><?= e($ma) ?></td>
                                            <td class="text-truncate" style="max-width:12rem"><?= e($ten) ?></td>
                                            <td class="text-center small text-nowrap"><?= dash_fmt_date($ngay) ?></td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="<?= e(admin_url(array('p' => 'quan-ly-don-hang', 'a' => 'edit', 'id' => $oid))) ?>">Xem</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php component('card_close', array('body' => 'none')); ?>
            </div>
            <?php endif; ?>

            <?php if ($showLienheTable): ?>
            <div class="<?= e($tableCol) ?>">
                <?php component('card_open', array(
                    'title' => 'Liên hệ mới',
                    'icon'  => 'fa-solid fa-inbox',
                    'tools' => '<a class="btn btn-sm btn-outline-secondary" href="' . e(admin_url(array('p' => 'lien-he', 'a' => 'man'))) . '">Tất cả</a>',
                    'body'  => 'none',
                )); ?>
                <div class="card-body p-0">
                    <?php if (empty($lienhemoi)): ?>
                        <div class="p-4 text-center text-muted small">Không có liên hệ chưa xem.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:3rem">#</th>
                                        <th>Họ tên</th>
                                        <th>Nội dung</th>
                                        <th class="text-center">Ngày</th>
                                        <th class="text-center" style="width:5rem"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lienhemoi as $i => $row):
                                        $lid = (int) (isset($row['id']) ? $row['id'] : 0);
                                        $td  = trim((string) (isset($row['tieu_de']) ? $row['tieu_de'] : ''));
                                        $nd  = truncate_text(strip_tags((string) (isset($row['noi_dung']) ? $row['noi_dung'] : '')), 80);
                                        $preview = ($td !== '' ? $td . ': ' : '') . $nd;
                                        ?>
                                        <tr>
                                            <td class="text-center text-muted"><?= $i + 1 ?></td>
                                            <td class="text-truncate" style="max-width:8rem"><?= e(isset($row['ho_ten']) ? $row['ho_ten'] : '') ?></td>
                                            <td class="small text-truncate" style="max-width:14rem"><?= e($preview) ?></td>
                                            <td class="text-center small text-nowrap"><?= dash_fmt_date(isset($row['ngay_gui']) ? $row['ngay_gui'] : '') ?></td>
                                            <td class="text-center">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-info js-dash-lienhe"
                                                        data-id="<?= $lid ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#dashLienheModal">Chi tiết</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php component('card_close', array('body' => 'none')); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php if ($showLienheTable): ?>
<div class="modal fade" id="dashLienheModal" tabindex="-1" aria-labelledby="dashLienheLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dashLienheLabel">Chi tiết liên hệ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="dash-body-lienhe">
                <div class="text-muted small">Đang tải…</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var body = document.getElementById('dash-body-lienhe');
    if (!body) return;
    document.querySelectorAll('.js-dash-lienhe').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-id'), 10) || 0;
            body.innerHTML = '<div class="text-muted small">Đang tải…</div>';
            if (!id) {
                body.innerHTML = '<div class="alert alert-warning mb-0">ID không hợp lệ.</div>';
                return;
            }
            var done = function (html) {
                body.innerHTML = html || '<div class="alert alert-warning mb-0">Không có dữ liệu.</div>';
            };
            if (window.AdminUI && AdminUI.http && AdminUI.http.post) {
                AdminUI.http.post('./sources/ajax.php', { do: 'get_lienhe', id: id })
                    .then(function (res) { done(res && res.text ? res.text : ''); })
                    .catch(function () { done('<div class="alert alert-danger mb-0">Lỗi tải dữ liệu.</div>'); });
                return;
            }
            if (window.jQuery) {
                jQuery.post('sources/ajax.php', { do: 'get_lienhe', id: id })
                    .done(function (html) { done(html); })
                    .fail(function () { done('<div class="alert alert-danger mb-0">Lỗi tải dữ liệu.</div>'); });
            }
        });
    });
})();
</script>
<?php endif; ?>

<?php if ($showCharts && ((isset($chartOrders) && array_sum($chartOrders['values']) > 0) || !empty($chartStatus))): ?>
<script src="public/plugin/chartjs/chart.min.js"></script>
<script src="js/dashboard-charts.js"></script>
<?php endif; ?>