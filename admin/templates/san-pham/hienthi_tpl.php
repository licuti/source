<?php
/**
 * Sản phẩm — Danh sách.
 * Tìm theo tên/id/mã/loại, xoá mảng, phân trang server-side.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'san-pham';
$TABLE     = '#_sanpham';

$currentSearch = [
    'col'  => $_GET['search'] ?? '',
    'key'  => $_GET['key']    ?? '',
    'loai' => (int) ($_GET['loai'] ?? 0),
];

$catNameMap = [];
foreach ($d->o_fet("select id_code, ten from #_category where lang = '" . LANG . "'") as $c) {
    $catNameMap[(string) $c['id_code']] = $c['ten'];
}
?>

<?php component('page_header', [
    'title'      => 'Sản phẩm',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Sản phẩm'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten'     => 'Tên sản phẩm',
                'id_code' => 'ID sản phẩm',
                'ma_sp'   => 'Mã sản phẩm (SKU)',
            ],
            'category'   => $loai,
            'show_loai'  => true,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
            'extra_actions' => $canEdit ? [[
                'url'   => admin_url(['p' => $MODULE, 'a' => 'variation']),
                'label' => 'Biến thể sản phẩm',
                'icon'  => 'fa-solid fa-shapes',
                'class' => 'btn-outline-primary',
            ]] : [],
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
                                <th class="col-shrink">SKU</th>
                                <th class="col-shrink">Danh mục</th>
                                <th>Tên sản phẩm</th>
                                <th class="text-center col-shrink">Giá</th>
                                <th class="text-center col-shrink">Hình ảnh</th>
                                <th class="text-center col-shrink">Trạng thái</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">SEO</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 10,
                                    'message' => 'Chưa có sản phẩm',
                                    'hint'    => 'Nhấn "Thêm mới" để tạo sản phẩm đầu tiên.',
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?> 
                                <?php
                                    $id      = $it['id_code'];
                                    $editUrl = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id]);
                                    $delUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id]);
                                ?>
                                <tr>
                                    <td class="text-center col-shrink">
                                        <?php component('row_check', [
                                            'value' => $id,
                                            'show'  => $canDelete,
                                            'label' => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('stt_input', [
                                            'table'    => $TABLE,
                                            'col'      => 'so_thu_tu',
                                            'id'       => $id,
                                            'value'    => $it['so_thu_tu'],
                                            'can_edit' => $canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="col-shrink">
                                        <?= e($it['ma_sp'] ?? '—') ?>
                                    </td>
                                    <td class="col-shrink">
                                        <?= e($catNameMap[(string) $it['id_loai']] ?? '—') ?>
                                    </td>
                                    <td>
                                        <a href="<?= e($editUrl) ?>"><?= e($it['ten']) ?></a>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php if (($it['khuyen_mai'] ?? 0) > 0): ?>
                                            <span class="text-danger"><?= number_format((float) $it['khuyen_mai'], 0, ',', '.') ?>đ</span><br>
                                            <span class="text-decoration-line-through text-muted small"><?= number_format((float) $it['gia'], 0, ',', '.') ?>đ</span>
                                        <?php else: ?>
                                            <span class="text-danger"><?= number_format((float) $it['gia'], 0, ',', '.') ?>đ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('thumb', [
                                            'file' => $it['hinh_anh'] ?? '',
                                            'url'  => $editUrl,
                                            'alt'  => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('multi_switches', [
                                            'table'    => $TABLE,
                                            'switches' => [
                                                'sp_moi'  => 'Mới',
                                                'sp_hot'  => 'Hot',
                                                'sp_sale' => 'Sale',
                                                'sp_top'  => 'Top',
                                            ],
                                            'row'      => $it,
                                            'disabled' => !$canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table'    => $TABLE,
                                            'col'      => 'hien_thi',
                                            'id'       => $id,
                                            'checked'  => ($it['hien_thi'] ?? 0) == 1,
                                            'disabled' => !$canEdit,
                                            'title'    => 'Hiển thị',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('seo_status', [
                                            'noindex'  => ($it['noindex']  ?? 0) == 1,
                                            'nofollow' => ($it['nofollow'] ?? 0) == 1,
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
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
                        </tbody>                    </table>
                </div>

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> sản phẩm
                        &middot; Trang <strong><?= (int) ($current_page ?? 1) ?></strong>
                        / <strong><?= (int) ($total_page ?? 1) ?></strong>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'loai'   => $currentSearch['loai'] >   0 ? (string) $currentSearch['loai'] : null,
                    ]);
                    component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($limit ?? 12),
                        'current'     => (int) ($current_page ?? 1),
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'keep_params' => $keepQuery,
                    ]);
                    ?>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>

