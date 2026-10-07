<?php
/**
 * Upload file — Danh sách.
 * Phân trang server-side (component pagination), không dùng AJAX/JS.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'upload-file';
$TABLE     = '#_files';
$page      = (int) ($current_page ?? ($_GET['page'] ?? 1));

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
    'title'      => 'Upload file',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Upload file'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'action'     => 'man',
            'columns'    => [
                'ten'       => 'Tên file',
                'id_code'   => 'ID',
                'loai_file' => 'Loại file',
            ],
            'category'   => $loai ?? '',
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
                                    <input type="checkbox" id="check_all" class="form-check-input" aria-label="Chọn tất cả">
                                </th>
                                <th class="text-center col-shrink">STT</th>
                                <th class="col-shrink">Danh mục</th>
                                <th class="col-shrink">Loại</th>
                                <th>Tên file</th>
                                <th>Đường dẫn / File</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 8,
                                    'message' => 'Chưa có file nào',
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($items as $it):
                                    $id      = (int) $it['id_code'];
                                    $editUrl = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id, 'page' => $page]);
                                    $delUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id, 'page' => $page]);
                                    $catName = $catNameMap[(string) ($it['id_loai'] ?? '')] ?? '—';
                                    ?>
                                    <tr>
                                        <td class="text-center col-shrink">
                                            <?php component('row_check', [
                                                'value' => $id,
                                                'show'  => $canDelete,
                                                'label' => $it['ten'] ?? '',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('stt_input', [
                                                'table'    => $TABLE,
                                                'id'       => $id,
                                                'value'    => (int) ($it['so_thu_tu'] ?? 0),
                                                'can_edit' => $canEdit,
                                            ]); ?>
                                        </td>
                                        <td class="col-shrink"><?= e($catName) ?></td>
                                        <td class="col-shrink">
                                            <span class="badge text-bg-secondary"><?= e($it['loai_file'] ?: '—') ?></span>
                                        </td>
                                        <td>
                                            <a href="<?= e($editUrl) ?>" class="fw-medium text-decoration-none">
                                                <?= e($it['ten'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td class="small text-break">
                                            <?php
                                            $fp = trim((string) ($it['file'] ?? ''));
                                            $lk = trim((string) ($it['link_khac'] ?? ''));
                                            if ($fp !== '') {
                                                // FM: media/files | legacy: media/images, uploads/files
                                                if (function_exists('media_url')) {
                                                    $href = media_url($fp, 'file');
                                                    if (strpos(str_replace('\\', '/', $fp), '/') === false) {
                                                        $base = basename($fp);
                                                        $root = dirname(__DIR__, 3);
                                                        if (is_file($root . '/media/files/' . $base)) {
                                                            $href = media_url($base, 'file');
                                                        } elseif (is_file($root . '/media/images/' . $base)) {
                                                            $href = media_url($base, 'image');
                                                        } elseif (is_file($root . '/uploads/files/' . $base)) {
                                                            $href = '../uploads/files/' . $base;
                                                        }
                                                    }
                                                } else {
                                                    $href = '../media/files/' . ltrim($fp, '/');
                                                }
                                                echo '<a href="' . e($href) . '" target="_blank" rel="noopener" class="text-decoration-none">' . e(basename($fp)) . '</a>';
                                            } elseif ($lk !== '') {
                                                echo '<a href="' . e($lk) . '" target="_blank" rel="noopener" class="text-decoration-none">' . e($lk) . '</a>';
                                            } else {
                                                echo '—';
                                            }
                                            ?>
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
                                            <?php component('row_actions', [
                                                'edit_url'    => $editUrl,
                                                'delete_url'  => $delUrl,
                                                'name'        => $it['ten'] ?? '',
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

                <div class="px-3 pb-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="small text-secondary">
                        Tổng: <strong><?= (int) ($total_records ?? 0) ?></strong> file
                        &middot; Trang <strong><?= (int) $page ?></strong>
                        / <strong><?= max(1, (int) ($total_page ?? 1)) ?></strong>
                    </div>

                    <?php
                    $keepQuery = array_filter([
                        'search' => $currentSearch['col'] !== '' ? $currentSearch['col'] : null,
                        'key'    => $currentSearch['key'] !== '' ? $currentSearch['key'] : null,
                        'loai'   => $currentSearch['loai'] > 0 ? $currentSearch['loai'] : null,
                    ]);
                    component('pagination', [
                        'total'       => (int) ($total_records ?? 0),
                        'per_page'    => (int) ($limit ?? 20),
                        'current'     => (int) $page,
                        'base_url'    => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'keep_params' => $keepQuery,
                    ]);
                    ?>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>
