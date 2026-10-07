<?php
/**
 * Bài viết — Danh sách (tìm theo tên/id/danh mục, xoá mảng, có phân trang).
 * Dùng component list_bar đặt NGOÀI card_open để gộp lọc + bulk-action + thêm mới.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'bai-viet';
$TABLE     = '#_tintuc';
$page      = (int) ($_GET['page'] ?? 1);

$currentSearch = [
    'col'  => $_GET['search'] ?? '',
    'key'  => $_GET['key']    ?? '',
    'loai' => (int) ($_GET['loai'] ?? 0),
];

// Danh mục thuộc module Bài viết (id=3), dựng sẵn cây với thụt lề, đánh selected luôn.
$filterLoai = $d->array_category(0, '= ', (string) $currentSearch['loai'], 3);

$catNameMap = [];
foreach ($d->o_fet("select id_code, ten from #_category where lang = '" . LANG . "'") as $c) {
    $catNameMap[(string) $c['id_code']] = $c['ten'];
}
?>

<?php component('page_header', [
    'title'      => 'Bài viết',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Bài viết', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => 'Danh sách'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten'     => 'Tên bài viết',
                'id_code' => 'ID bài viết',
                'alias'   => 'Đường dẫn',
                'title'   => 'Title SEO',
                'keyword' => 'Từ khoá',
            ],
            'category'   => $filterLoai,
            'show_loai'  => true,
            'current'    => $currentSearch,
            'reset_url'  => admin_url(['p' => $MODULE, 'a' => 'man']),
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
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
                                <th class="col-name">Bài viết</th>
                                <th class="col-shrink">Danh mục</th>
                                <th class="text-center col-shrink">Hình ảnh</th>
                                <th class="text-center col-shrink">Ngày đăng</th>
                                <th class="text-center col-shrink">Nổi bật</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">SEO</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)):?>
                                <?php component('empty_state', [
                                    'colspan' => 10,
                                    'message' => 'Không tìm thấy bài viết nào',
                                    'hint'    => 'Thử đổi từ khoá hoặc danh mục.',
                                ]); ?>
                            <?php else: foreach ($items as $it):
                                $editUrl = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $it['id_code'], 'page' => $page]);
                                $delUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $it['id_code'], 'page' => $page]);
                                ?>
                                <tr data-id="<?= e($it['id_code']) ?>" data-name="<?= e($it['ten']) ?>">
                                    <td class="text-center col-shrink">
                                        <?php component('row_check', [
                                            'value' => $it['id_code'],
                                            'show'  => $canDelete,
                                            'label' => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('stt_input', [
                                            'table'    => $TABLE,
                                            'col'      => 'so_thu_tu',
                                            'id'       => $it['id_code'],
                                            'value'    => $it['so_thu_tu'] ?? 0,
                                            'can_edit' => $canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="col-name">
                                        <a href="<?= e($editUrl) ?>" class="fw-medium text-decoration-none">
                                            <?= e($it['ten']) ?>
                                        </a>
                                    </td>
                                    <td class="col-shrink">
                                        <?= e($catNameMap[(string) ($it['id_loai'] ?? '')] ?? '—') ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('thumb', [
                                            'file' => $it['hinh_anh'] ?? '',
                                            'url'  => $editUrl,
                                            'alt'  => $it['ten'],
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink small text-nowrap">
                                        <?= !empty($it['ngay_dang']) ? e(date('d/m/Y H:i', (int) $it['ngay_dang'])) : '—' ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table'    => $TABLE,
                                            'col'      => 'noi_bat',
                                            'id'       => $it['id_code'],
                                            'checked'  => ($it['noi_bat'] ?? 0) == 1,
                                            'disabled' => !$canEdit,
                                            'title'    => 'Bài viết nổi bật',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table'    => $TABLE,
                                            'col'      => 'hien_thi',
                                            'id'       => $it['id_code'],
                                            'checked'  => ($it['hien_thi'] ?? 0) == 1,
                                            'disabled' => !$canEdit,
                                            'title'    => 'Hiển thị trên site',
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
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="px-3 pb-3">
                    <div class="d-flex justify-content-between align-items-center small text-secondary">
                        <span>
                            Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> bài viết
                            <?php if (!empty($per_page) && $per_page > 0): ?>
                                &middot; Trang <strong><?= (int) ($current_page ?? $page) ?></strong>
                                / <strong><?= (int) ($total_page ?? 1) ?></strong>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col']  !== '' ? $currentSearch['col']  : null,
                        'key'    => $currentSearch['key']  !== '' ? $currentSearch['key']  : null,
                        'loai'   => $currentSearch['loai'] >   0 ? $currentSearch['loai'] : null,
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
