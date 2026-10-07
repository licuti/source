<?php
/**
 * Danh mục — Danh sách (cây đệ quy, không giới hạn cấp).
 * Toàn bộ danh mục nạp 1 lần ở đây để render cả cây, lọc nhanh client-side.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;

$TABLE  = '#_category';
$MODULE = $_GET['p'] ?? 'category';
$page   = $_GET['page'] ?? '';

// $isSearching: server trả danh sách phẳng (mọi cấp). Bình thường render cây.
$isSearching = isset($_GET['search']) && ($_GET['key'] ?? '') !== '';

$moduleTitles = [];
foreach ($d->o_fet("select id, title from #_module") as $row) {
    $moduleTitles[(string) $row['id']] = $row['title'];
}

$childrenMap = [];
if (!$isSearching) {
    $allRows = $d->o_fet(
        "select * from " . $TABLE . " where lang = '" . LANG . "' order by so_thu_tu asc, id desc"
    );
    foreach ($allRows as $row) {
        $childrenMap[(string) $row['id_loai']][] = $row;
    }
}

$COLSPAN = 9;

// Closure đệ quy: render 1 dòng + toàn bộ con-cháu.
$renderRow = function (array $item, int $level = 0) use (
    &$renderRow, $childrenMap, $moduleTitles, $canEdit, $canDelete, $TABLE, $MODULE, $page
) {
    $id       = !empty($item['id_code']) ? $item['id_code'] : $item['id'];
    $children = $childrenMap[(string) $id] ?? [];
    $editUrl  = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $id, 'page' => $page]);
    ?>
    <tr data-id="<?= e($id) ?>"
        data-parent="<?= e($item['id_loai']) ?>"
        data-name="<?= e($item['ten']) ?>">

        <!-- Tay cầm kéo thả + ô chọn dòng -->
        <td class="text-center col-shrink">
            <div class="d-flex align-items-center gap-1">
                <?php if ($canEdit): ?>
                    <span class="drag-handle" title="Kéo để đổi thứ tự">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </span>
                <?php endif; ?>
                <?php component('row_check', ['value' => $id, 'show' => $canDelete, 'label' => $item['ten']]); ?>
            </div>
        </td>
        <td class="text-center col-shrink">
            <?php component('stt_input', [
                'table'    => $TABLE,
                'col'      => 'so_thu_tu',
                'id'       => $id,
                'value'    => $item['so_thu_tu'],
                'can_edit' => $canEdit,
            ]); ?>
        </td>

        <!-- Tên: thụt lề theo cấp, có nút thu gọn nhánh con -->
        <td>
            <div class="tree-cell" data-level="<?= $level ?>" style="--level:<?= $level ?>">
                <?php if ($children): ?>
                    <button type="button" class="tree-toggle" data-target-id="<?= e($id) ?>"
                            title="Thu gọn / mở rộng">
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                <?php else: ?>
                    <span class="tree-spacer"></span>
                <?php endif; ?>

                <a href="<?= e($editUrl) ?>" class="tree-name"><?= e($item['ten']) ?></a>

                <?php if ($children): ?>
                    <span class="tree-count">(<?= count($children) ?>)</span>
                <?php endif; ?>
            </div>
        </td>

        <!-- Hình ảnh -->
        <td class="text-center col-shrink">
            <?php component('thumb', [
                'file' => $item['hinh_anh'],
                'url'  => $editUrl,
                'alt'  => $item['ten'],
            ]); ?>
        </td>

        <!-- Module -->
        <td class="text-center col-shrink">
            <span class="badge text-bg-light border">
                <?= e($moduleTitles[(string) $item['module']] ?? '—') ?>
            </span>
        </td>
        <?php
        $switches = [
            'tieu_bieu' => 'Nổi bật',
            'hien_thi'  => 'Hiển thị',
        ];
        foreach ($switches as $col => $label): ?>
            <td class="text-center col-shrink">
                <?php component('switch_toggle', [
                    'table'    => $TABLE,
                    'col'      => $col,
                    'id'       => $id,
                    'checked'  => ($item[$col] ?? 0) == 1,
                    'disabled' => !$canEdit,
                    'title'    => $label,
                ]); ?>
            </td>
        <?php endforeach; ?>
        <td class="col-shrink">
            <?php component('seo_status', [
                'noindex'  => ($item['noindex']  ?? 0) == 1,
                'nofollow' => ($item['nofollow'] ?? 0) == 1,
            ]); ?>
        </td>

        <!-- Tác vụ -->
        <td class="text-center col-shrink">
            <?php
            component('row_actions', [
                'edit_url'   => $editUrl,
                'delete_url' => admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $id, 'page' => $page]),
                'name'       => $item['ten'],
                'child_count' => count($children),
                'can_edit'   => $canEdit,
                'can_delete' => $canDelete,
            ]);
            ?>
        </td>
    </tr>
    <?php
    foreach ($children as $child) {
        $renderRow($child, $level + 1);
    }
};
?>

<?php component('page_header', [
    'title'      => 'Danh mục',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Cấu hình website'],
        ['label' => 'Danh mục'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php component('list_bar', [
            'module'     => $MODULE,
            'actions'    => ['delete' => 'Xóa'],
            'can_delete' => $canDelete,
            'can_edit'   => $canEdit,
        ]); ?>

        <?php component('card_open', [
            'title' => 'Danh sách danh mục',
            'body'  => 'none',
        ]); ?>

            <?php if (!$isSearching): ?>
                <div class="px-3 pt-3 pb-0">
                    <div class="input-group input-group-sm" style="max-width:320px">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" class="form-control" data-quick-filter
                               placeholder="Tìm nhanh trong danh mục...">
                    </div>
                </div>
            <?php else: ?>
                <div class="px-3 pt-3">
                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0 py-2">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>
                            Đang hiển thị kết quả tìm kiếm cho
                            "<strong><?= e($_GET['key']) ?></strong>" (dạng danh sách phẳng).
                        </span>
                        <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>"
                           class="btn btn-sm btn-outline-secondary ms-auto">Xóa lọc</a>
                    </div>
                </div>
            <?php endif; ?>

            <form id="form" method="post"
                  action="<?= e(admin_url(['p' => $MODULE, 'a' => 'delete_all'])) ?>">

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin"
                           data-tree-table="category">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">
                                    <input type="checkbox" id="check_all" class="form-check-input"
                                           aria-label="Chọn tất cả">
                                </th>
                                <th class="text-center col-shrink">STT</th>
                                <th class="col-name">Danh mục</th>
                                <th class="text-center col-shrink">Hình ảnh</th>
                                <th class="text-center col-shrink">Module</th>
                                <th class="text-center col-shrink">Nổi bật</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">SEO</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <?php /* Kéo thả SortableJS: bật khi không phải đang tìm kiếm
                                 và có quyền sửa (còn lại khoá cứng). */ ?>
                        <tbody <?= $canEdit && !$isSearching ? 'data-sortable data-table="' . e($TABLE) . '"' : '' ?>>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => $COLSPAN,
                                    'message' => $isSearching ? 'Không tìm thấy danh mục nào' : 'Chưa có danh mục',
                                    'hint'    => $isSearching
                                        ? 'Thử từ khóa khác hoặc xóa điều kiện lọc.'
                                        : 'Nhấn "Thêm mới" để tạo danh mục đầu tiên.',
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <?php $renderRow($item, 0); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

        <?php component('card_close', ['body' => 'none']); ?>
    </div>
</div>