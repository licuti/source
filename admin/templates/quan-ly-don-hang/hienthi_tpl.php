<?php
/**
 * Quản lý đơn hàng — danh sách.
 */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'quan-ly-don-hang';
$items     = is_array($items ?? null) ? $items : [];
$page      = (int) ($_GET['page'] ?? 1);
$filter_status = $filter_status ?? ($_GET['status'] ?? '');
$filter_paid   = $filter_paid   ?? ($_GET['paid'] ?? '');
$filter_from   = $filter_from   ?? ($_GET['from'] ?? '');
$filter_to     = $filter_to     ?? ($_GET['to'] ?? '');
$order_stats   = is_array($order_stats ?? null)
    ? $order_stats
    : ['cho' => 0, 'thu' => 0, 'huy' => 0, 'ctv' => 0, 'count_new' => 0];

$currentSearch = [
    'col' => $_GET['search'] ?? '',
    'key' => $_GET['key'] ?? '',
];

// Prefetch subtotal + tính grand total giống chi tiết
$tongMap = [];
$ids = [];
foreach ($items as $it) {
    $ids[(int) $it['id']] = (int) $it['id'];
}
if ($ids) {
    $rows = $d->o_fet(
        'SELECT id_dh, COALESCE(SUM(gia_ban * so_luong),0) AS subtotal'
        . ' FROM #_dathang_chitiet WHERE id_dh IN (' . implode(',', $ids) . ')'
        . ' GROUP BY id_dh'
    );
    $sub = [];
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $sub[(int) $r['id_dh']] = (float) $r['subtotal'];
        }
    }
    foreach ($items as $it) {
        $oid = (int) $it['id'];
        $tongMap[$oid] = donhang_grand_total($it, $sub[$oid] ?? 0);
    }
}

$statusOpts = ['' => '— Tất cả trạng thái —'];
foreach (donhang_trangthai_map() as $k => $m) {
    $statusOpts[(string) $k] = $m['label'];
}
?>

<?php component('page_header', [
    'title'      => 'Quản lý đơn hàng',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị khách hàng'],
        ['label' => 'Đơn hàng'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-secondary">
                            Đang xử lý
                            <?php if (!empty($order_stats['count_new'])): ?>
                                <span class="badge text-bg-danger ms-1"><?= (int) $order_stats['count_new'] ?> mới</span>
                            <?php endif; ?>
                        </div>
                        <div class="fs-5 fw-semibold text-warning"><?= number_format($order_stats['cho'], 0, ',', '.') ?><sup>đ</sup></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-secondary">Đã thu (đã giao)</div>
                        <div class="fs-5 fw-semibold text-success"><?= number_format($order_stats['thu'], 0, ',', '.') ?><sup>đ</sup></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-secondary">Trả hàng</div>
                        <div class="fs-5 fw-semibold text-danger"><?= number_format($order_stats['huy'], 0, ',', '.') ?><sup>đ</sup></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-secondary">Chiết khấu CTV</div>
                        <div class="fs-5 fw-semibold"><?= number_format($order_stats['ctv'], 0, ',', '.') ?><sup>đ</sup></div>
                    </div>
                </div>
            </div>
        </div>

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ma_dh'      => 'Mã đơn',
                'ho_ten'     => 'Họ tên',
                'dien_thoai' => 'Điện thoại',
                'email'      => 'Email',
                'dia_chi'    => 'Địa chỉ',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => false,
            'filters'    => [
                [
                    'name'    => 'status',
                    'label'   => 'Trạng thái',
                    'width'   => '170px',
                    'value'   => (string) $filter_status,
                    'options' => $statusOpts,
                ],
                [
                    'name'    => 'paid',
                    'label'   => 'Thanh toán',
                    'width'   => '160px',
                    'value'   => (string) $filter_paid,
                    'options' => [
                        ''  => '— Tất cả TT —',
                        '0' => 'Chưa thanh toán',
                        '1' => 'Đã thanh toán',
                    ],
                ],
            ],
            'hidden'     => array_filter([
                'from' => $filter_from !== '' ? $filter_from : null,
                'to'   => $filter_to !== '' ? $filter_to : null,
            ]),
        ]); ?>
        <?php /* Lọc ngày đặt — form GET riêng, giữ filter list_bar */ ?>
        <form method="get" class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <input type="hidden" name="p" value="<?= e($MODULE) ?>">
            <input type="hidden" name="a" value="man">
            <input type="hidden" name="search" value="<?= e($currentSearch['col']) ?>">
            <input type="hidden" name="key" value="<?= e($currentSearch['key']) ?>">
            <input type="hidden" name="status" value="<?= e((string) $filter_status) ?>">
            <input type="hidden" name="paid" value="<?= e((string) $filter_paid) ?>">
            <label class="small text-secondary mb-0">Từ ngày</label>
            <input type="date" name="from" value="<?= e((string) $filter_from) ?>"
                   class="form-control form-control-sm" style="max-width:160px">
            <label class="small text-secondary mb-0">đến</label>
            <input type="date" name="to" value="<?= e((string) $filter_to) ?>"
                   class="form-control form-control-sm" style="max-width:160px">
            <button type="submit" class="btn btn-sm btn-outline-primary">Lọc ngày</button>
            <?php if ($filter_from !== '' || $filter_to !== ''): ?>
                <a class="btn btn-sm btn-outline-secondary"
                   href="<?= e(admin_url(array_filter([
                       'p' => $MODULE, 'a' => 'man',
                       'search' => $currentSearch['col'] ?: null,
                       'key' => $currentSearch['key'] ?: null,
                       'status' => $filter_status !== '' ? $filter_status : null,
                       'paid' => $filter_paid !== '' ? $filter_paid : null,
                   ]))) ?>">Bỏ lọc ngày</a>
            <?php endif; ?>
        </form>

        <?php component('card_open', ['body' => 'none']); ?>

            <form id="form" method="post"
                  action="<?= e(admin_url(['p' => $MODULE, 'a' => 'delete_all'])) ?>">

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">
                                    <input type="checkbox" id="check_all" class="form-check-input"
                                           aria-label="Chọn tất cả">
                                </th>
                                <th class="text-center col-shrink">STT</th>
                                <th class="col-shrink">Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th class="text-end col-shrink">Tổng TT</th>
                                <th class="text-center col-shrink">Ngày đặt</th>
                                <th class="col-shrink">Thanh toán</th>
                                <th class="text-center col-shrink">Xử lý</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 9,
                                    'message' => 'Không có đơn hàng nào',
                                    'hint'    => 'Thử đổi bộ lọc hoặc từ khoá.',
                                    'icon'    => 'fa-solid fa-cart-shopping',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 14);
                                foreach ($items as $i => $row):
                                    $id   = (int) $row['id'];
                                    $tong = $tongMap[$id] ?? 0;
                                    $name = (string) ($row['ho_ten'] ?? '');
                                    $isNew = (int) ($row['trangthai_xuly'] ?? 0) === 0;
                                ?>
                                    <tr class="<?= $isNew ? 'table-warning' : '' ?>">
                                        <td class="text-center">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => 'Chọn đơn ' . ($row['ma_dh'] ?? $id),
                                            ]); ?>
                                        </td>
                                        <td class="text-center"><?= $sttBase + $i + 1 ?></td>
                                        <td>
                                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id])) ?>"
                                               class="fw-semibold text-decoration-none">
                                                <?= e((string) ($row['ma_dh'] ?? '')) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($name) ?></div>
                                            <?php if (!empty($row['dien_thoai'])): ?>
                                                <div class="small text-secondary">
                                                    <i class="fa-solid fa-phone me-1"></i><?= e($row['dien_thoai']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['email'])): ?>
                                                <div class="small text-secondary">
                                                    <i class="fa-solid fa-envelope me-1"></i><?= e($row['email']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['dia_chi'])): ?>
                                                <div class="small text-secondary">
                                                    <i class="fa-solid fa-location-dot me-1"></i><?= e(truncate_text($row['dia_chi'], 80)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-danger fw-semibold text-nowrap">
                                            <?= number_format($tong, 0, ',', '.') ?><sup>đ</sup>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?= e((string) ($row['ngay_dathang'] ?? '')) ?>
                                        </td>
                                        <td>
                                            <div class="small mb-1"><?= e((string) ($row['thanh_toan'] ?? '')) ?></div>
                                            <?= donhang_thanhtoan_badge($row['trangthai_thanhtoan'] ?? 0) ?>
                                        </td>
                                        <td class="text-center">
                                            <?= donhang_trangthai_badge($row['trangthai_xuly'] ?? 0) ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php
                                            $viewBtn = '<a href="' . e(admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id])) . '"'
                                                . ' class="btn btn-xs btn-outline-info" title="Chi tiết">'
                                                . '<i class="fa-solid fa-eye"></i></a>';
                                            component('row_actions', [
                                                'edit_url'   => '',
                                                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]),
                                                'name'       => 'đơn ' . ($row['ma_dh'] ?? $id),
                                                'can_edit'   => false,
                                                'can_delete' => $canDelete,
                                                'extra'      => $viewBtn,
                                            ]);
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> đơn
                        <?php if (!empty($per_page) && $per_page > 0): ?>
                            &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                            / <strong><?= (int) ($total_page ?? 1) ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'status' => $filter_status !== '' ? $filter_status : null,
                        'paid'   => $filter_paid !== '' ? $filter_paid : null,
                        'from'   => $filter_from !== '' ? $filter_from : null,
                        'to'     => $filter_to !== '' ? $filter_to : null,
                    ]);
                    component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($per_page ?? 14),
                        'current'     => (int) ($current_page ?? $page),
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'keep_params' => $keepQuery,
                    ]);
                    ?>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>
