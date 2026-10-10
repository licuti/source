<?php
/** Nội dung — danh sách nhóm + bản ghi con */
$canEdit   = $d->checkPermission_edit($id_module) == 1;
$canDelete = $d->checkPermission_dele($id_module) == 1;
$MODULE    = 'noi-dung';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$nd_groups         = $nd_groups ?? [];
$nd_group_options  = $nd_group_options ?? [];
$nd_edit_group     = $nd_edit_group ?? null;

$isEditGroup = is_array($nd_edit_group) && !empty($_GET['id_loai']);
$editGroupId = $isEditGroup ? (int) $_GET['id_loai'] : 0;
$openParent  = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;

$firstCode = $langs[0]['code'] ?? 'vi';
$groupMain = $isEditGroup ? ($nd_edit_group[$firstCode] ?? []) : [];

$groupActionUrl = admin_url(array_filter([
    'p' => $MODULE, 'a' => 'save_group',
    'id_loai' => $isEditGroup ? $editGroupId : null,
]));

?>

<?php component('page_header', [
    'title' => 'Nội dung',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị giao diện'],
        ['label' => 'Nội dung'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row g-3">
            <div class="col-lg-8">
                <?php component('card_open', [
                    'title' => 'Nhóm nội dung',
                    'icon'  => 'fa-solid fa-folder-open',
                    'body'  => 'none',
                ]); ?>

                <div class="table-responsive p-3">
                    <table class="table table-hover table-bordered align-middle table-admin mb-0">
                        <thead>
                            <tr>
                                <th class="text-center col-shrink" style="width:180px">Nội dung</th>
                                <th>Tên nhóm</th>
                                <th class="text-center col-shrink">Hình</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody<?= $canEdit ? ' data-sortable data-table="#_content"' : '' ?>>
                        <?php if (empty($nd_groups)): ?>
                            <?php component('empty_state', [
                                'colspan' => 5,
                                'message' => 'Chưa có nhóm. Thêm nhóm ở cột bên phải.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($nd_groups as $g):
                                $gid  = (int) $g['id_code'];
                                $opts = $nd_group_options[$gid] ?? [];
                                $nOpt = count($opts);
                                $open = ($openParent === $gid);
                                $editG = admin_url(['p' => $MODULE, 'a' => 'man', 'id_loai' => $gid]);
                                $delG  = admin_url(['p' => $MODULE, 'a' => 'delete_group', 'id' => $gid]);
                                $addO  = admin_url(['p' => $MODULE, 'a' => 'add', 'parent' => $gid]);
                                $rowCls = 'nd-opts-' . $gid;
                                $gDisplayName = (!empty($g['label']) && trim((string) $g['label']) !== '') ? $g['label'] : ($g['ten'] ?? '');
                                ?>
                                <tr class="table-light">
                                    <td class="text-center col-shrink">
                                        <div class="d-flex gap-1 align-items-center">
                                            <?php if ($canEdit): ?>
                                            <a href="<?= e($addO) ?>" class="btn btn-sm btn-primary">
                                                <i class="fa-solid fa-plus me-1"></i>Thêm
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($nOpt > 0): ?>
                                            <button type="button" class="btn btn-sm btn-success js-nd-toggle"
                                                    data-target=".<?= e($rowCls) ?>"
                                                    aria-expanded="<?= $open ? 'true' : 'false' ?>">
                                                Xem <?= (int) $nOpt ?>
                                                <i class="fa-solid fa-chevron-down ms-1"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="fw-semibold"><?= e($gDisplayName) ?></td>
                                    <td class="text-center col-shrink">
                                        <?php component('thumb', ['file' => $g['hinh_anh'] ?? '', 'alt' => $gDisplayName]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('switch_toggle', [
                                            'table' => '#_category_noidung', 'col' => 'hien_thi',
                                            'id' => (int) ($g['id'] ?? 0),
                                            'checked' => ($g['hien_thi'] ?? 0) == 1,
                                            'disabled' => !$canEdit, 'title' => 'Hiển thị nhóm',
                                        ]); ?>
                                    </td>
                                    <td class="text-center col-shrink">
                                        <?php component('row_actions', [
                                            'edit_url' => $editG, 'delete_url' => $delG,
                                            'name' => $gDisplayName, 'child_count' => $nOpt,
                                            'can_edit' => $canEdit, 'can_delete' => $canDelete,
                                        ]); ?>
                                    </td>
                                </tr>
                                <?php foreach ($opts as $opt):
                                    $oid = (int) $opt['id_code'];
                                    $editO = admin_url(['p' => $MODULE, 'a' => 'edit', 'id' => $oid, 'parent' => $gid]);
                                    $delO  = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $oid, 'parent' => $gid]);
                                    ?>
                                    <tr class="<?= e($rowCls) ?><?= $open ? '' : ' d-none' ?>"
                                        data-id="<?= (int) $oid ?>"
                                        data-parent="<?= (int) $gid ?>">
                                        <td class="text-center col-shrink">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <?php if ($canEdit): ?>
                                                    <span class="drag-handle" title="Kéo để đổi thứ tự">
                                                        <i class="fa-solid fa-grip-vertical"></i>
                                                    </span>
                                                <?php endif; ?>
                                                <?php component('stt_input', [
                                                    'table' => '#_content', 'id' => $oid,
                                                    'value' => (int) ($opt['so_thu_tu'] ?? 0),
                                                    'can_edit' => $canEdit,
                                                ]); ?>
                                            </div>
                                        </td>
                                        <td class="ps-4">
                                            <span class="text-secondary me-1">↳</span>
                                            <a href="<?= e($editO) ?>" class="text-decoration-none">
                                                <?= e($opt['ten'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('thumb', ['file' => $opt['hinh_anh'] ?? '', 'alt' => $opt['ten'] ?? '']); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('switch_toggle', [
                                                'table' => '#_content', 'col' => 'hien_thi',
                                                'id' => $oid,
                                                'checked' => ($opt['hien_thi'] ?? 0) == 1,
                                                'disabled' => !$canEdit, 'title' => 'Hiển thị',
                                            ]); ?>
                                        </td>
                                        <td class="text-center col-shrink">
                                            <?php component('row_actions', [
                                                'edit_url' => $editO, 'delete_url' => $delO,
                                                'name' => $opt['ten'] ?? '',
                                                'can_edit' => $canEdit, 'can_delete' => $canDelete,
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
                        <i class="fa-solid fa-grip-vertical me-1"></i>Kéo icon để sắp xếp nội dung trong cùng nhóm.
                    </p>
                    <?php endif; ?>
                </div>
                <?php component('card_close', ['body' => 'none']); ?>
            </div>

            <div class="col-lg-4">
                <?php component('card_open', [
                    'title' => $isEditGroup ? 'Sửa nhóm' : 'Thêm nhóm',
                    'icon'  => 'fa-solid fa-folder-plus',
                ]); ?>

                <form method="post" action="<?= e($groupActionUrl) ?>" data-warn-unsaved>
                    <div class="row g-3">
                        <div class="col-6">
                            <?php component('form/image_picker', [
                                'label' => 'Hình ảnh', 'name' => 'hinh_anh',
                                'value' => $groupMain['hinh_anh'] ?? '',
                                'table' => '_category_noidung',
                                'row_id' => $isEditGroup ? (string) ($groupMain['id'] ?? '') : '',
                                'height' => 100,
                            ]); ?>
                        </div>
                        <div class="col-6">
                            <?php component('form/image_picker', [
                                'label' => 'Background', 'name' => 'background',
                                'value' => $groupMain['background'] ?? '',
                                'table' => '_category_noidung',
                                'row_id' => $isEditGroup ? (string) ($groupMain['id'] ?? '') : '',
                                'height' => 100,
                            ]); ?>
                        </div>
                    </div>

                    <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'ndg']); ?>
                    <div class="tab-content">
                        <?php foreach ($langs as $i => $lang):
                            $code = $lang['code'];
                            $row  = $isEditGroup ? ($nd_edit_group[$code] ?? []) : [];
                            $sfx  = $multiLang ? ' (' . $code . ')' : '';
                            ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                 id="ndg-<?= e($code) ?>" role="tabpanel">
                                <?php if ($isEditGroup): ?>
                                    <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                <?php endif; ?>
                                <?php component('form/input', [
                                    'label' => 'Label' . $sfx, 'name' => 'label[]',
                                    'id' => 'ndg_label_' . $code, 'value' => $row['label'] ?? '',
                                    'placeholder' => 'Label',
                                ]); ?>
                                <?php component('form/input', [
                                    'label' => 'Tên nhóm' . $sfx, 'name' => 'ten[]',
                                    'id' => 'ndg_ten_' . $code, 'value' => $row['ten'] ?? '',
                                    'placeholder' => 'Tên nhóm', 'required' => $i === 0,
                                ]); ?>
                                <?php component('form/input', [
                                    'label' => 'Liên kết' . $sfx, 'name' => 'link[]',
                                    'id' => 'ndg_link_' . $code, 'value' => $row['link'] ?? '',
                                ]); ?>

                                <?php component('form/editor', [
                                    'label'  => 'Nội dung' . $sfx,
                                    'name'   => 'noi_dung[]',
                                    'id'     => 'ndg_nd_' . $code,
                                    'value'  => $row['noi_dung'] ?? '',
                                    'height' => '350px',
                                ]); ?>



                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mb-2">
                        <?php component('form/checkbox', [
                            'label' => 'Nofollow', 'name' => 'nofollow',
                            'checked' => ($groupMain['nofollow'] ?? 0) == 1,
                            'inline' => true, 'switch' => false,
                        ]); ?>
                        <?php component('form/checkbox', [
                            'label' => 'Mở tab mới', 'name' => 'target',
                            'checked' => ($groupMain['target'] ?? 0) == 1,
                            'inline' => true, 'switch' => false,
                        ]); ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3 border-top pt-3">
                        <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i><?= $isEditGroup ? 'Hủy' : 'Làm mới' ?>
                        </a>
                        <?php if ($canEdit): ?>
                            <button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                <?= $isEditGroup ? 'Cập nhật' : 'Lưu' ?>
                            </button>
                            <button type="submit" name="capnhat" value="capnhat_sua" class="btn btn-sm btn-outline-primary">
                                <i class="fa-regular fa-floppy-disk me-1"></i>Lưu và Sửa
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
  document.querySelectorAll('.js-nd-toggle').forEach(function (btn) {
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
