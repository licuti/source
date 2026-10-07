<?php
/**
 * Bình luận - đánh giá — danh sách (SP + tin tức).
 * Không dùng DataTables.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'binh-luan';
$TABLE     = '#_binhluan';
$items     = is_array($items ?? null) ? $items : [];
$page      = (int) ($_GET['page'] ?? 1);
$filter_type = $filter_type ?? ($_GET['type'] ?? '');

$currentSearch = [
    'col' => $_GET['search'] ?? '',
    'key' => $_GET['key'] ?? '',
];

// Prefetch SP + tin tức (tránh N+1)
$spMap  = [];
$tinMap = [];
$spIds  = [];
$tinIds = [];
foreach ($items as $it) {
    $sid = (int) ($it['id_sanpham'] ?? 0);
    $tid = (int) ($it['id_tin'] ?? 0);
    if ($sid > 0) {
        $spIds[$sid] = $sid;
    }
    if ($tid > 0) {
        $tinIds[$tid] = $tid;
    }
}
$lang = defined('LANG') ? LANG : 'vi';
$langSql = $d->clear($lang);

if ($spIds) {
    $rows = $d->o_fet(
        "SELECT id_code, ten, alias FROM #_sanpham WHERE lang = '{$langSql}'"
        . ' AND id_code IN (' . implode(',', array_map('intval', $spIds)) . ')'
    );
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $spMap[(int) $r['id_code']] = $r;
        }
    }
}
if ($tinIds) {
    $rows = $d->o_fet(
        "SELECT id_code, ten, alias FROM #_tintuc WHERE lang = '{$langSql}'"
        . ' AND id_code IN (' . implode(',', array_map('intval', $tinIds)) . ')'
    );
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $tinMap[(int) $r['id_code']] = $r;
        }
    }
}

$baseUrl = defined('URLPATH') ? URLPATH : '/';
?>

<?php component('page_header', [
    'title'      => 'Bình luận - đánh giá',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị khách hàng'],
        ['label' => 'Bình luận - đánh giá'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ho_ten'     => 'Họ tên',
                'email'      => 'Email',
                'dien_thoai' => 'Điện thoại',
                'noi_dung'   => 'Nội dung',
                'tieu_de'    => 'Tiêu đề',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => false,
            'filters'    => [
                [
                    'name'    => 'type',
                    'label'   => 'Loại bình luận',
                    'width'   => '170px',
                    'value'   => (string) $filter_type,
                    'options' => [
                        ''        => '— Tất cả loại —',
                        'sanpham' => 'Sản phẩm',
                        'tintuc'  => 'Tin tức',
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
                                <th class="text-center col-shrink">Loại</th>
                                <th class="col-name">Đối tượng</th>
                                <th class="col-shrink">Khách hàng</th>
                                <th>Nội dung</th>
                                <th class="text-center col-shrink">Ngày gửi</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 9,
                                    'message' => 'Không tìm thấy bình luận nào',
                                    'hint'    => 'Thử đổi từ khoá hoặc loại bình luận.',
                                    'icon'    => 'fa-regular fa-comments',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 14);
                                foreach ($items as $i => $row):
                                    $id    = (int) $row['id'];
                                    $name  = (string) ($row['ho_ten'] ?? '');
                                    $stars = max(0, min(5, (int) ($row['danh_gia'] ?? 0)));
                                    $spId  = (int) ($row['id_sanpham'] ?? 0);
                                    $tinId = (int) ($row['id_tin'] ?? 0);
                                    $loai  = $spId > 0 ? 'sanpham' : ($tinId > 0 ? 'tintuc' : 'khac');

                                    if ($loai === 'sanpham') {
                                        $obj     = $spMap[$spId] ?? null;
                                        $objName = $obj['ten'] ?? ('SP #' . $spId);
                                        $objUrl  = !empty($obj['alias']) ? $baseUrl . $obj['alias'] . '.html' : '';
                                        $badge   = '<span class="badge text-bg-primary">Sản phẩm</span>';
                                    } elseif ($loai === 'tintuc') {
                                        $obj     = $tinMap[$tinId] ?? null;
                                        $objName = $obj['ten'] ?? ('Tin #' . $tinId);
                                        $objUrl  = !empty($obj['alias']) ? $baseUrl . $obj['alias'] . '.html' : '';
                                        $badge   = '<span class="badge text-bg-info">Tin tức</span>';
                                    } else {
                                        $objName = '—';
                                        $objUrl  = '';
                                        $badge   = '<span class="badge text-bg-secondary">Khác</span>';
                                    }
                                ?>
                                    <tr data-review-id="<?= $id ?>">
                                        <td class="text-center">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => 'Chọn bình luận của ' . $name,
                                            ]); ?>
                                        </td>
                                        <td class="text-center"><?= $sttBase + $i + 1 ?></td>
                                        <td class="text-center"><?= $badge ?></td>
                                        <td>
                                            <?php if ($objUrl !== ''): ?>
                                                <a href="<?= e($objUrl) ?>" target="_blank" rel="noopener"
                                                   class="link-primary text-decoration-none">
                                                    <?= e($objName) ?>
                                                    <i class="fa-solid fa-arrow-up-right-from-square small ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-secondary"><?= e($objName) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($name) ?></div>
                                            <?php if (!empty($row['email'])): ?>
                                                <div class="small text-secondary"><?= e($row['email']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['dien_thoai'])): ?>
                                                <div class="small text-secondary"><?= e($row['dien_thoai']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($loai === 'sanpham'): ?>
                                            <div class="text-warning mb-1" title="<?= $stars ?>/5 sao">
                                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                                    <i class="fa-<?= $s <= $stars ? 'solid' : 'regular' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (($row['tieu_de'] ?? '') !== ''): ?>
                                                <div class="fw-medium mb-1"><?= e($row['tieu_de']) ?></div>
                                            <?php endif; ?>
                                            <div class="text-secondary small">
                                                <?= e(truncate_text((string) ($row['noi_dung'] ?? ''), 150)) ?>
                                            </div>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?= e((string) ($row['ngay'] ?? '')) ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('switch_toggle', [
                                                'table'    => $TABLE,
                                                'col'      => 'trang_thai',
                                                'id'       => $id,
                                                'checked'  => ((int) ($row['trang_thai'] ?? 0) === 1),
                                                'disabled' => !$canEdit,
                                                'title'    => 'Hiển thị bình luận trên site',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php
                                            $viewBtn = '<button type="button" class="btn btn-xs btn-outline-info js-view-review"'
                                                . ' data-id="' . e((string) $id) . '" title="Xem chi tiết">'
                                                . '<i class="fa-solid fa-eye"></i></button>';
                                            component('row_actions', [
                                                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]),
                                                'name'       => 'bình luận của ' . $name,
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
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> bình luận
                        <?php if (!empty($per_page) && $per_page > 0): ?>
                            &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                            / <strong><?= (int) ($total_page ?? 1) ?></strong>
                        <?php endif; ?>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'type'   => $filter_type !== '' ? $filter_type : null,
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

<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="fa-regular fa-comments me-2"></i>Chi tiết bình luận
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="reviewModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    var $modal = $('#reviewModal');
    var $body  = $('#reviewModalBody');
    if (!$modal.length || !$body.length) {
        return;
    }

    var bsModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
        ? bootstrap.Modal.getOrCreateInstance($modal[0])
        : null;

    $(document).on('click', '.js-view-review', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) {
            return;
        }

        $body.html(
            '<div class="text-center py-4 text-secondary">' +
            '<span class="spinner-border spinner-border-sm me-2"></span>Đang tải...</div>'
        );
        if (bsModal) {
            bsModal.show();
        }

        $.post('./sources/ajax.php', { do: 'get_binhluan', id: id })
            .done(function (html) {
                $body.html(html);
            })
            .fail(function () {
                $body.html('<div class="alert alert-danger mb-0">Không tải được nội dung.</div>');
            });
    });
});
</script>
