<?php
/**
 * Flash Sale — Danh sach.
 * Dung component list_bar dat NGOAI card_open de gop loc + bulk-action + them moi.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'flash-sale';
$TABLE     = '#_flash_sale';
$page      = (int) ($_GET['page'] ?? 1);

$currentSearch = [
    'col' => $_GET['search'] ?? '',
    'key' => $_GET['key']    ?? '',
];
?>

<?php component('page_header', [
    'title'      => 'Flash Sale',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Flash Sale', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => 'Danh sách'],
    ],
]); ?>

<div class='app-content'>
    <div class='container-fluid'>

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten' => 'Tên chương trình',
            ],
            'show_loai'  => false,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
        ]); ?>

        <?php component('card_open', ['body' => 'none']); ?>

            <form id='form' method='post'
                  action='<?= e(admin_url(['p' => $MODULE, 'a' => 'delete_all'])) ?>'>

                <div class='table-responsive p-3'>
                    <table class='table table-hover table-bordered align-middle table-admin'>
                        <thead>
                            <tr>
                                <th class='text-center col-shrink'>
                                    <input type='checkbox' id='check_all' class='form-check-input'
                                           aria-label='Chọn tất cả'>
                                </th>
                                <th class='text-center col-shrink'>STT</th>
                                <th>Tên chương trình</th>
                                <th class='col-shrink'>Thời gian áp dụng</th>
                                <th class='text-center col-shrink'>Sản phẩm</th>
                                <th class='text-center col-shrink'>Hiển thị</th>
                                <th class='text-center col-shrink'>Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', ['colspan' => 7, 'message' => 'Chưa có chương trình Flash Sale nào']); ?>
                            <?php else: ?>
                                <?php foreach ($items as $key => $it):
                                    $stt = ($offset ?? 0) + $key + 1;
                                    $editUrl = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $it['id']]);
                                    $delUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $it['id']]);
                                    $tuNgay  = !empty($it['tu_ngay']) ? date('d/m/Y H:i', strtotime($it['tu_ngay'])) : '—';
                                    $denNgay = !empty($it['den_ngay']) ? date('d/m/Y H:i', strtotime($it['den_ngay'])) : '—';
                                ?>
                                    <tr>
                                        <td class='text-center col-shrink'>
                                            <?php component('row_check', [
                                                'value' => $it['id'],
                                                'show'  => $canDelete,
                                                'label' => 'Chọn chương trình ' . ($it['ten'] ?? ''),
                                            ]); ?>
                                        </td>
                                        <td class='text-center col-shrink'><?= (int) $stt ?></td>
                                        <td class='fw-medium'><?= e($it['ten']) ?></td>
                                        <td class='col-shrink'>
                                            <?= $tuNgay ?> <i class='fa-solid fa-arrow-right text-secondary mx-1'></i> <?= $denNgay ?>
                                        </td>
                                        <td class='text-center col-shrink'>
                                            <span class='badge bg-info'><?= (int) ($it['sp_count'] ?? 0) ?></span>
                                        </td>
                                        <td class='text-center col-shrink'>
                                            <?php component('switch_toggle', [
                                                'table'    => $TABLE,
                                                'col'      => 'hien_thi',
                                                'id'       => $it['id'],
                                                'checked'  => ($it['hien_thi'] ?? 0) == 1,
                                                'disabled' => !$canEdit,
                                                'title'    => 'Hiển thị trên site',
                                            ]); ?>
                                        </td>
                                        <td class='text-center col-shrink'>
                                            <?php component('row_actions', [
                                                'edit_url'    => $editUrl,
                                                'delete_url'  => $delUrl,
                                                'name'        => $it['ten'],
                                                'child_count' => 0,
                                                'can_edit'    => $canEdit,
                                                'can_delete'  => $canDelete,
                                            ]); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class='px-3 pb-3'>
                    <div class='d-flex justify-content-between align-items-center small text-secondary'>
                        <span>
                            Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> chương trình
                            <?php if (!empty($per_page) && $per_page > 0): ?>
                                &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                                / <strong><?= (int) ($total_page ?? 1) ?></strong>
                            <?php endif; ?>
                        </span>
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
