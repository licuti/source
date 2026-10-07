<?php
/**
 * Liên hệ — danh sách (chuẩn UI bài viết: list_bar + bảng tĩnh + pagination).
 * Không dùng DataTables.
 */

$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'lien-he';
$items     = is_array($items ?? null) ? $items : [];
$page      = (int) ($_GET['page'] ?? 1);

$currentSearch = [
    'col' => $_GET['search'] ?? '',
    'key' => $_GET['key'] ?? '',
];

$unread = (int) ($unread_total ?? 0);
?>

<?php component('page_header', [
    'title'      => 'Khách hàng liên hệ',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị khách hàng'],
        ['label' => 'Khách hàng liên hệ'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ho_ten'   => 'Họ tên',
                'email'    => 'Email',
                'so_dien_thoai' => 'Điện thoại',
                'tieu_de'  => 'Tiêu đề',
                'noi_dung' => 'Nội dung',
                'dia_chi'  => 'Địa chỉ',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => false,
        ]); ?>

        <?php if ($unread > 0): ?>
            <div class="alert alert-info small py-2 px-3 mb-3 d-flex align-items-center gap-2">
                <i class="fa-solid fa-envelope-open-text"></i>
                <span>Có <strong><?= $unread ?></strong> liên hệ chưa xem.</span>
            </div>
        <?php endif; ?>

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
                                <th class="col-shrink">Khách hàng</th>
                                <th>Nội dung</th>
                                <th class="text-center col-shrink">Ngày gửi</th>
                                <th class="text-center col-shrink">Trạng thái</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 7,
                                    'message' => 'Không tìm thấy liên hệ nào',
                                    'hint'    => 'Thử đổi từ khoá tìm kiếm.',
                                    'icon'    => 'fa-regular fa-envelope',
                                ]); ?>
                            <?php else: ?>
                                <?php
                                $sttBase = ((int) ($current_page ?? $page) - 1) * (int) ($per_page ?? 14);
                                foreach ($items as $i => $row):
                                    $id       = (int) $row['id'];
                                    $isUnread = (int) ($row['da_xem'] ?? 0) === 0;
                                    $name     = (string) ($row['ho_ten'] ?? '');
                                ?>
                                    <tr class="<?= $isUnread ? 'table-warning' : '' ?>" data-contact-id="<?= $id ?>">
                                        <td class="text-center">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => 'Chọn liên hệ ' . $name,
                                            ]); ?>
                                        </td>
                                        <td class="text-center"><?= $sttBase + $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($name) ?></div>
                                            <?php if (!empty($row['so_dien_thoai'])): ?>
                                                <div class="small text-secondary">
                                                    <i class="fa-solid fa-phone me-1"></i><?= e($row['so_dien_thoai']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['email'])): ?>
                                                <div class="small text-secondary">
                                                    <i class="fa-solid fa-envelope me-1"></i><?= e($row['email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($row['tieu_de'] ?? '') !== ''): ?>
                                                <div class="fw-medium mb-1"><?= e($row['tieu_de']) ?></div>
                                            <?php endif; ?>
                                            <div class="text-secondary small">
                                                <?= e(truncate_text((string) ($row['noi_dung'] ?? ''), 150)) ?>
                                            </div>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            <?= e((string) ($row['ngay_gui'] ?? '')) ?>
                                        </td>
                                        <td class="text-center js-status-cell">
                                            <?php if ($isUnread): ?>
                                                <span class="badge text-bg-warning">Chưa xem</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-success">Đã xem</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php
                                            $viewBtn = '<button type="button" class="btn btn-xs btn-outline-info js-view-contact"'
                                                . ' data-id="' . e((string) $id) . '" title="Xem chi tiết">'
                                                . '<i class="fa-solid fa-eye"></i></button>';
                                            component('row_actions', [
                                                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]),
                                                'name'       => 'liên hệ của ' . $name,
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
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> liên hệ
                        <?php if (!empty($per_page) && $per_page > 0): ?>
                            &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                            / <strong><?= (int) ($total_page ?? 1) ?></strong>
                        <?php endif; ?>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
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
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalLabel">
                    <i class="fa-regular fa-envelope me-2"></i>Chi tiết liên hệ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="contactModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Xem chi tiết liên hệ (modal).
 * jQuery đã nạp ở <head>; $(fn) chạy sau khi parse xong body
 * → bootstrap + AdminUI ở footer đã sẵn sàng.
 */
$(function () {
    var $modal = $('#contactModal');
    var $body  = $('#contactModalBody');
    if (!$modal.length || !$body.length) {
        return;
    }

    var bsModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
        ? bootstrap.Modal.getOrCreateInstance($modal[0])
        : null;

    $(document).on('click', '.js-view-contact', function (e) {
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

        $.post('./sources/ajax.php', { do: 'get_lienhe', id: id })
            .done(function (html) {
                $body.html(html);
                // Đánh dấu đã xem trên UI (server đã cập nhật da_xem)
                $('tr[data-contact-id="' + id + '"]')
                    .removeClass('table-warning')
                    .find('.js-status-cell')
                    .html('<span class="badge text-bg-success">Đã xem</span>');
            })
            .fail(function () {
                $body.html('<div class="alert alert-danger mb-0">Không tải được nội dung.</div>');
            });
    });
});
</script>
