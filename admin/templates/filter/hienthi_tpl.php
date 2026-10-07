<?php
/**
 * Filter — Danh sách nhóm + tùy chọn (expand).
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'filter';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$filter_groups         = $filter_groups ?? [];
$filter_group_options  = $filter_group_options ?? [];
$filter_edit_group     = $filter_edit_group ?? null;

$isEditGroup = is_array($filter_edit_group) && !empty($_GET['id_loai']);
$editGroupId = $isEditGroup ? (int) $_GET['id_loai'] : 0;
$openParent  = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;

$firstCode = $langs[0]['code'] ?? 'vi';
$groupMain = $isEditGroup ? ($filter_edit_group[$firstCode] ?? []) : [];

$groupActionUrl = admin_url(array_filter([
    'p'       => $MODULE,
    'a'       => 'save_group',
    'id_loai' => $isEditGroup ? $editGroupId : null,
]));


?>

<?php component('page_header', [
    'title'      => 'Bộ lọc (Filter)',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị giao diện'],
        ['label' => 'Bộ lọc'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <div class="row g-3">
            <div class="col-lg-8">
                <?php component('card_open', [
                    'title' => 'Nhóm filter',
                    'icon'  => 'fa-solid fa-filter',
                    'body'  => 'none',
                ]); ?>

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin mb-0">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink" style="width:140px">Tùy chọn</th>
                                <th class="text-center col-shrink">STT</th>
                                <th>Tên nhóm</th>
                                <th class="text-center col-shrink">Hình</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($filter_groups)): ?>
                            <?php component('empty_state', [
                                'colspan' => 6,
                                'message' => 'Chưa có nhóm filter. Thêm nhóm ở cột bên phải.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($filter_groups as $g):
                                $gid   = (int) $g['id_code'];
                                $opts  = $filter_group_options[$gid] ?? [];
                                $nOpt  = count($opts);
                                $open  = ($openParent === $gid);
                                $editG = admin_url(['p' => $MODULE, 'a' => 'man', 'id_loai' => $gid]);
                                $delG  = admin_url(['p' => $MODULE, 'a' => 'delete_group', 'id' => $gid]);
                                $addO  = admin_url(['p' => $MODULE, 'a' => 'add', 'parent' => $gid]);
                                $rowCls = 'filter-opts-' . $gid;
                                ?>
                                <tr class="table-light">
                                    <td class="text-center col-shrink">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <?php if ($nOpt > 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success js-filter-toggle" data-target=".<?= e($rowCls) ?>"
                                                    aria-expanded="<?= $open ? 'true' : 'false' ?>">
                                                <i class="fa-solid fa-chevron-down me-1"></i><?= (int) $nOpt ?>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($canEdit): ?>
                                            <a href="<?= e($addO) ?>" class="btn btn-xs btn-primary btn-sm">
                                                <i class="fa-solid fa-plus me-1"></i>Thêm
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('stt_input', [
                                            'table'    => '#_filter',
                                            'id'       => $gid,
                                            'value'    => (int) ($g['so_thu_tu'] ?? 0),
                                            'can_edit' => $canEdit,
                                        ]); ?>
                                    </td>
                                    <td class="fw-semibold"><?= e($g['ten'] ?? '') ?></td>
                                    <td class="text-center col-shrink">
                                        <?php component('thumb', [
                                            'file' => $g['hinh_anh'] ?? '',
                                            'alt'  => $g['ten'] ?? '',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table'    => '#_filter',
                                            'col'      => 'hien_thi',
                                            'id'       => $gid,
                                            'checked'  => ($g['hien_thi'] ?? 0) == 1,
                                            'disabled' => !$canEdit,
                                            'title'    => 'Hiển thị nhóm',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('row_actions', [
                                            'edit_url'   => $editG,
                                            'delete_url' => $delG,
                                            'name'       => $g['ten'] ?? '',
                                            'child_count'=> $nOpt,
                                            'can_edit'   => $canEdit,
                                            'can_delete' => $canDelete,
                                        ]); ?>
                                    </td>
                                </tr>
                                <?php foreach ($opts as $opt):
                                    $oid = (int) $opt['id_code'];
                                    $editO = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $oid, 'parent' => $gid]);
                                    $delO  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $oid, 'parent' => $gid]);
                                    ?>
                                    <tr class="<?= e($rowCls) ?><?= $open ? '' : ' d-none' ?>">
                                        <td class="text-center col-shrink"></td>
                                        <td class="text-center col-shrink">
                                            <?php component('stt_input', [
                                                'table'    => '#_filter_content',
                                                'id'       => $oid,
                                                'value'    => (int) ($opt['so_thu_tu'] ?? 0),
                                                'can_edit' => $canEdit,
                                            ]); ?>
                                        </td>
                                        <td class="ps-4">
                                            <span class="text-secondary me-1">↳</span>
                                            <a href="<?= e($editO) ?>" class="text-decoration-none">
                                                <?= e($opt['ten'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('thumb', [
                                                'file' => $opt['hinh_anh'] ?? '',
                                                'alt'  => $opt['ten'] ?? '',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('switch_toggle', [
                                                'table'    => '#_filter_content',
                                                'col'      => 'hien_thi',
                                                'id'       => $oid,
                                                'checked'  => ($opt['hien_thi'] ?? 0) == 1,
                                                'disabled' => !$canEdit,
                                                'title'    => 'Hiển thị',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('row_actions', [
                                                'edit_url'   => $editO,
                                                'delete_url' => $delO,
                                                'name'       => $opt['ten'] ?? '',
                                                'can_edit'   => $canEdit,
                                                'can_delete' => $canDelete,
                                            ]); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php component('card_close', ['body' => 'none']); ?>
            </div>

            <div class="col-lg-4">
                <?php component('card_open', [
                    'title' => $isEditGroup ? 'Sửa nhóm filter' : 'Thêm nhóm filter',
                    'icon'  => 'fa-solid fa-folder-plus',
                ]); ?>

                <form method="post" action="<?= e($groupActionUrl) ?>" data-warn-unsaved>
                    <?php component('form/image_picker', [
                        'label'  => 'Hình ảnh',
                        'name'   => 'hinh_anh',
                        'value'  => $groupMain['hinh_anh'] ?? '',
                        'table'  => '_filter',
                        'row_id' => $isEditGroup ? (string) $editGroupId : '',
                        'height' => 120,
                    ]); ?>

                    <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'fgrp']); ?>
                    <div class="tab-content">
                        <?php foreach ($langs as $i => $lang):
                            $code = $lang['code'];
                            $row  = $isEditGroup ? ($filter_edit_group[$code] ?? []) : [];
                            $sfx  = $multiLang ? ' (' . $code . ')' : '';
                            ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                 id="fgrp-<?= e($code) ?>" role="tabpanel">
                                <?php if ($isEditGroup): ?>
                                    <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                <?php endif; ?>

                                <?php component('form/input', [
                                    'label'       => 'Tên nhóm' . $sfx,
                                    'name'        => 'ten[]',
                                    'id'          => 'grp_ten_' . $code,
                                    'value'       => $row['ten'] ?? '',
                                    'placeholder' => 'VD: Màu sắc, Kích thước…',
                                    'required'    => $i === 0,
                                ]); ?>

                                

                                <?php component('form/editor', [
                                    'label'  => 'Nội dung' . $sfx,
                                    'name'   => 'noi_dung[]',
                                    'id'     => 'grp_nd_' . $code,
                                    'value'  => $row['noi_dung'] ?? '',
                                    'height' => '180px',
                                ]); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php component('form/input', [
                        'label'       => 'Số thứ tự',
                        'name'        => 'so_thu_tu',
                        'id'          => 'grp_so_thu_tu',
                        'type'        => 'number',
                        'value'       => $groupMain['so_thu_tu'] ?? 0,
                        'placeholder' => '0',
                    ]); ?>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php if ($isEditGroup): ?>
                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>"
                               class="btn btn-sm btn-outline-secondary">Hủy sửa</a>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <button type="submit" class="btn btn-sm btn-primary ms-auto">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                <?= $isEditGroup ? 'Cập nhật nhóm' : 'Thêm nhóm' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <?php component('card_close'); ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
  document.querySelectorAll('.js-filter-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sel = btn.getAttribute('data-target');
      if (!sel) return;
      var open = btn.getAttribute('aria-expanded') === 'true';
      document.querySelectorAll(sel).forEach(function (row) {
        if (open) row.classList.add('d-none');
        else row.classList.remove('d-none');
      });
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      var icon = btn.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-chevron-down', open);
        icon.classList.toggle('fa-chevron-up', !open);
      }
    });
  });
})();
</script>
