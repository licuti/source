<?php
/**
 * Module — Danh sách module admin (cây cha/con) + form thêm/sửa.
 *
 * Controller: admin/sources/module.php
 */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'module';
$isEdit    = !empty($row_item);

// Build danh sách danh mục cha cho form select
$parentOptions = [0 => 'Danh mục chính'];
foreach ($items as $it) {
    $parentOptions[(int) $it['id']] = $it['name'];
}
?>

<?php component('page_header', [
    'title'      => 'Quản lý module',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản lý module'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-3">

            <!-- Cột trái: Danh sách -->
            <div class="col-lg-8">
                <?php component('card_open', [
                    'title' => 'Danh sách module',
                    'icon'  => 'fa-solid fa-list',
                ]); ?>

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink">STT</th>
                                <th>Tên module</th>
                                <th class="col-shrink">Alias</th>
                                <th class="text-center col-shrink">Loại</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody<?= $canEdit ? ' data-sortable data-table="#_module_admin"' : '' ?>>
                            <?php if (empty($items)): ?>
                                <?php component('empty_state', [
                                    'colspan' => 6,
                                    'message' => 'Chưa có module nào',
                                    'hint'    => 'Dùng form bên phải để thêm module mới.',
                                ]); ?>
                            <?php else: ?>
                                <?php foreach ($items as $parent): ?>
                                    <?php
                                    $children = $d->o_fet(
                                        "select * from #_module_admin where parent = " . (int) $parent['id'] . " order by so_thu_tu asc, id asc"
                                    );
                                    $parentEditUrl = admin_url(['p' => $MODULE, 'a' => 'man', 'id' => $parent['id']]);
                                    $parentDelUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $parent['id']]);
                                    ?>
                                    <tr class="table-light" data-id="<?= (int) $parent['id'] ?>" data-parent="0">
                                        <td class="text-center">
                                            <?php if ($canEdit): ?>
                                                <span class="drag-handle" title="Kéo để đổi thứ tự">
                                                    <i class="fa-solid fa-grip-vertical"></i>
                                                </span>
                                                <input type="hidden" class="a_stt" value="<?= (int) $parent['so_thu_tu'] ?>">
                                            <?php endif; ?>
                                            <span class="text-muted small"><?= (int) $parent['so_thu_tu'] ?></span>
                                        </td>
                                        <td><strong><?= e($parent['name']) ?></strong></td>
                                        <td><?= e($parent['alias']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">Danh mục</span>
                                        </td>
                                        <td class="text-center">
                                            <?php component('switch_toggle', [
                                                'table'    => '#_module_admin',
                                                'col'      => 'hien_thi',
                                                'id'       => $parent['id'],
                                                'checked'  => ($parent['hien_thi'] ?? 0) == 1,
                                                'disabled' => !$canEdit,
                                                'title'    => 'Hiển thị module',
                                            ]); ?>
                                        </td>
                                        <td class="text-center">
                                            <?php component('row_actions', [
                                                'edit_url'    => $parentEditUrl,
                                                'delete_url'  => $parentDelUrl,
                                                'name'        => $parent['name'],
                                                'child_count' => count($children),
                                                'can_edit'    => $canEdit,
                                                'can_delete'  => $canDelete,
                                            ]); ?>
                                        </td>
                                    </tr>

                                    <?php foreach ($children as $child): ?>
                                        <?php
                                        $childEditUrl = admin_url(['p' => $MODULE, 'a' => 'man', 'id' => $child['id']]);
                                        $childDelUrl  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $child['id']]);
                                        ?>
                                        <tr data-id="<?= (int) $child['id'] ?>" data-parent="<?= (int) $parent['id'] ?>">
                                            <td class="text-center">
                                                <?php if ($canEdit): ?>
                                                    <span class="drag-handle" title="Kéo để đổi thứ tự">
                                                        <i class="fa-solid fa-grip-vertical"></i>
                                                    </span>
                                                    <input type="hidden" class="a_stt" value="<?= (int) $child['so_thu_tu'] ?>">
                                                <?php endif; ?>
                                                <span class="text-muted small"><?= (int) $child['so_thu_tu'] ?></span>
                                            </td>
                                            <td class="ps-4">
                                                <i class="fa-solid fa-angle-right text-secondary me-2"></i>
                                                <?= e($child['name']) ?>
                                            </td>
                                            <td><?= e($child['alias']) ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary">Module con</span>
                                            </td>
                                            <td class="text-center">
                                                <?php component('switch_toggle', [
                                                    'table'    => '#_module_admin',
                                                    'col'      => 'hien_thi',
                                                    'id'       => $child['id'],
                                                    'checked'  => ($child['hien_thi'] ?? 0) == 1,
                                                    'disabled' => !$canEdit,
                                                    'title'    => 'Hiển thị module',
                                                ]); ?>
                                            </td>
                                            <td class="text-center">
                                                <?php component('row_actions', [
                                                    'edit_url'    => $childEditUrl,
                                                    'delete_url'  => $childDelUrl,
                                                    'name'        => $child['name'],
                                                    'child_count' => 0,
                                                    'can_edit'    => $canEdit,
                                                    'can_delete'  => $canDelete,
                                                ]); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($canEdit): ?>
                        <p class="small text-secondary mt-3 mb-0">
                            <i class="fa-solid fa-grip-vertical me-1"></i>Kéo icon để sắp xếp thứ tự module.
                        </p>
                    <?php endif; ?>
                </div>

                <?php component('card_close'); ?>
            </div>

            <!-- Cột phải: Form thêm/sửa -->
            <div class="col-lg-4">
                <?php component('card_open', [
                    'title' => $isEdit ? 'Sửa module' : 'Thêm module',
                    'icon'  => $isEdit ? 'fa-solid fa-pen-to-square' : 'fa-solid fa-plus',
                ]); ?>

                <form method="post" action="<?= e(admin_url(['p' => $MODULE, 'a' => 'save', 'id' => $isEdit ? $row_item['id'] : ''])) ?>">
                    <?= admin_csrf_field() ?>

                    <?php component('form/select', [
                        'label'   => 'Danh mục cha',
                        'name'    => 'parent',
                        'id'      => 'parent',
                        'value'   => $row_item['parent'] ?? 0,
                        'options' => $parentOptions,
                    ]); ?>

                    <?php component('form/input', [
                        'label'     => 'Tên module',
                        'name'      => 'name',
                        'id'        => 'name',
                        'value'     => $row_item['name'] ?? '',
                        'required'  => true,
                    ]); ?>

                    <?php component('form/input', [
                        'label'     => 'File xử lý (alias)',
                        'name'      => 'alias',
                        'id'        => 'alias',
                        'value'     => $row_item['alias'] ?? '',
                        'required'  => true,
                    ]); ?>

                    <?php component('form/input', [
                        'label'     => 'Số thứ tự',
                        'name'      => 'so_thu_tu',
                        'id'        => 'so_thu_tu',
                        'type'      => 'number',
                        'value'     => $row_item['so_thu_tu'] ?? 0,
                    ]); ?>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if ($isEdit): ?>
                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>" class="btn btn-sm btn-warning">
                                <i class="fa-solid fa-plus me-1"></i> Thêm mới
                            </a>
                        <?php endif; ?>
                        <button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary ms-auto">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            <?= $isEdit ? 'Cập nhật' : 'Thêm mới' ?>
                        </button>
                    </div>
                </form>

                <?php component('card_close'); ?>
            </div>

        </div>
    </div>
</div>
