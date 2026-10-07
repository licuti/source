<?php
/**
 * Mã khuyến mãi — danh sách.
 */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'ma-khuyen-mai';
$TABLE     = '#_khuyenmai';
$items     = is_array($items ?? null) ? $items : [];
$page      = (int) ($_GET['page'] ?? 1);
$filter_loai   = $filter_loai   ?? ($_GET['loai'] ?? '');
$filter_status = $filter_status ?? ($_GET['status'] ?? '');

$currentSearch = [
    'col' => $_GET['search'] ?? '',
    'key' => $_GET['key'] ?? '',
];
?>

<?php component('page_header', [
    'title'      => 'Mã khuyến mãi',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản lý bán hàng'],
        ['label' => 'Mã khuyến mãi'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten' => 'Tên chương trình',
                'ma'  => 'Mã KM',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
            'add_url'    => admin_url(['p' => $MODULE, 'a' => 'add']),
            'filters'    => [
                [
                    'name'    => 'loai',
                    'label'   => 'Loại',
                    'width'   => '160px',
                    'value'   => (string) $filter_loai,
                    'options' => [
                        ''  => '— Tất cả loại —',
                        '0' => 'Giảm tổng đơn',
                        '1' => 'Giảm phí ship',
                    ],
                ],
                [
                    'name'    => 'status',
                    'label'   => 'Trạng thái',
                    'width'   => '160px',
                    'value'   => (string) $filter_status,
                    'options' => [
                        ''         => '— Tất cả TT —',
                        'active'   => 'Đang áp dụng',
                        'soon'     => 'Chưa bắt đầu',
                        'expired'  => 'Hết hạn',
                        'full'     => 'Hết lượt',
                        'off'      => 'Tạm tắt',
                    ],
                ],
            ],
        ]); ?>

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
                                <th>Tên chương trình</th>
                                <th class="text-center col-shrink">Mã KM</th>
                                <th class="col-shrink">Loại</th>
                                <th class="text-end col-shrink">Giá trị</th>
                                <th class="text-center col-shrink">Lượt dùng</th>
                                <th class="col-shrink">Thời gian</th>
                                <th class="text-center col-shrink">Bật</th>
                                <th class="text-center col-shrink">Trạng thái</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 11,
                                    'message' => 'Chưa có mã khuyến mãi',
                                    'hint'    => 'Bấm Thêm mới hoặc đổi bộ lọc.',
                                    'icon'    => 'fa-solid fa-ticket',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 14);
                                foreach ($items as $i => $row):
                                    $id   = (int) $row['id'];
                                    $ma   = (string) ($row['ma'] ?? '');
                                    $used = (int) ($row['used_count'] ?? 0);
                                    $lim  = (int) ($row['gioi_han'] ?? 0);
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => 'Chọn ' . $ma,
                                            ]); ?>
                                        </td>
                                        <td class="text-center"><?= $sttBase + $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e((string) ($row['ten'] ?? '')) ?></div>
                                            <?php if ((int) ($row['dieu_kien'] ?? 0) > 0): ?>
                                                <div class="small text-secondary">
                                                    Đơn từ <?= number_format($row['dieu_kien']) ?>đ
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <code class="fw-bold user-select-all"><?= e($ma) ?></code>
                                        </td>
                                        <td>
                                            <span class="badge <?= (int) ($row['loai'] ?? 0) === 1 ? 'text-bg-info' : 'text-bg-primary' ?>">
                                                <?= e(km_loai_label($row['loai'] ?? 0)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap fw-semibold">
                                            <?= e(km_format_giatri($row)) ?>
                                            <?php if ((int) ($row['don_vi'] ?? 0) === 1 && (int) ($row['gia_tri_max'] ?? 0) > 0): ?>
                                                <div class="small text-secondary fw-normal">
                                                    max <?= number_format($row['gia_tri_max']) ?>đ
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'history', 'id' => $id])) ?>"
                                               class="text-decoration-none" title="Xem lịch sử">
                                                <span class="badge text-bg-secondary"><?= $used ?></span>
                                                / <?= $lim > 0 ? $lim : '∞' ?>
                                            </a>
                                        </td>
                                        <td class="small text-nowrap">
                                            <?= e((string) ($row['tu_ngay'] ?? '')) ?>
                                            <i class="fa-solid fa-arrow-right mx-1 text-secondary"></i>
                                            <?= e((string) ($row['den_ngay'] ?? '')) ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('switch_toggle', [
                                                'table'    => $TABLE,
                                                'col'      => 'hien_thi',
                                                'id'       => $id,
                                                'checked'  => ((int) ($row['hien_thi'] ?? 1) === 1),
                                                'disabled' => !$canEdit,
                                                'title'    => 'Bật / tạm tắt mã',
                                            ]); ?>
                                        </td>
                                        <td class="text-center">
                                            <?= km_status_badge($row, $used) ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php
                                            $histBtn = '<a href="' . e(admin_url(['p' => $MODULE, 'a' => 'history', 'id' => $id])) . '"'
                                                . ' class="btn btn-xs btn-outline-secondary" title="Lịch sử dùng">'
                                                . '<i class="fa-solid fa-clock-rotate-left"></i></a>';
                                            component('row_actions', [
                                                'edit_url'   => admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id]),
                                                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]),
                                                'name'       => $ma !== '' ? $ma : ($row['ten'] ?? ''),
                                                'can_edit'   => $canEdit,
                                                'can_delete' => $canDelete,
                                                'extra'      => $histBtn,
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
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> mã
                        <?php if (!empty($per_page) && $per_page > 0): ?>
                            &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                            / <strong><?= (int) ($total_page ?? 1) ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'loai'   => $filter_loai !== '' ? $filter_loai : null,
                        'status' => $filter_status !== '' ? $filter_status : null,
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
