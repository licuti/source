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
if ($openParent <= 0 && !empty($_GET['id_loai'])) {
    $openParent = (int) $_GET['id_loai'];
}
$scrollItem = isset($_GET['scroll_item']) ? (int) $_GET['scroll_item'] : 0;

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
                                <th class="text-center col-shrink" style="width:180px">Tùy chọn</th>
                                <th>Tên nhóm</th>
                                <th class="text-center col-shrink">Hình</th>
                                <th class="text-center col-shrink">Hiển thị</th>
                                <th class="text-center col-shrink">Tác vụ</th>
                            </tr>
                        </thead>
                        <tbody<?= $canEdit ? ' data-sortable data-table="#_filter_content"' : '' ?>>
                        <?php if (empty($filter_groups)): ?>
                            <?php component('empty_state', [
                                'colspan' => 5,
                                'message' => 'Chưa có nhóm filter. Thêm nhóm ở cột bên phải.',
                            ]); ?>
                        <?php else: ?>
                            <?php foreach ($filter_groups as $g):
                                $gid   = (int) $g['id_code'];
                                $opts  = $filter_group_options[$gid] ?? [];
                                $nOpt  = count($opts);
                                $hasScrollItem = false;
                                if ($scrollItem > 0) {
                                    foreach ($opts as $opt) {
                                        if ((int) ($opt['id_code'] ?? 0) === $scrollItem) {
                                            $hasScrollItem = true;
                                            break;
                                        }
                                    }
                                }
                                $open  = ($openParent === $gid || $hasScrollItem);
                                $editG = admin_url(['p' => $MODULE, 'a' => 'man', 'id_loai' => $gid]);
                                $delG  = admin_url(['p' => $MODULE, 'a' => 'delete_group', 'id' => $gid]);
                                $addO  = admin_url(['p' => $MODULE, 'a' => 'add', 'parent' => $gid]);
                                $rowCls = 'filter-opts-' . $gid;
                                ?>
                                <tr class="table-light" id="group-<?= $gid ?>" data-group-id="<?= $gid ?>">
                                    <td class="text-center col-shrink">
                                        <div class="d-flex gap-1 align-items-center">
                                            <?php if ($canEdit): ?>
                                            <a href="<?= e($addO) ?>" class="btn btn-sm btn-primary">
                                                <i class="fa-solid fa-plus me-1"></i>Thêm
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($nOpt > 0): ?>
                                            <button type="button" class="btn btn-sm btn-success js-filter-toggle" data-target=".<?= e($rowCls) ?>"
                                                    aria-expanded="<?= $open ? 'true' : 'false' ?>"> 
                                                Xem <span class="js-filter-group-count"><?= (int) $nOpt ?></span> <i class="fa-solid <?= $open ? 'fa-chevron-up' : 'fa-chevron-down' ?> ms-1"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
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
                                    <tr class="<?= e($rowCls) ?><?= $open ? '' : ' d-none' ?>"
                                        id="item-<?= (int) $oid ?>"
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
                                                    'table'    => '#_filter_content',
                                                    'id'       => $oid,
                                                    'value'    => (int) ($opt['so_thu_tu'] ?? 0),
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
                                                'ajax_delete'=> true,
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
                        <i class="fa-solid fa-grip-vertical me-1"></i>Kéo icon để sắp xếp tùy chọn trong cùng nhóm.
                    </p>
                    <?php endif; ?>
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

                    <div class="d-flex flex-wrap gap-2 mt-3 border-top pt-3">
                        <?php if ($isEditGroup): ?>
                            <a href="<?= e(admin_url(['p' => $MODULE, 'a' => 'man'])) ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-arrow-left me-1"></i>Hủy sửa
                            </a>
                        <?php endif; ?>
                        <?php if ($canEdit): ?>
                            <button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary ms-auto">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                <?= $isEditGroup ? 'Cập nhật nhóm' : 'Thêm nhóm' ?>
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
  // 1. Toggle hiển thị danh sách con trong nhóm
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

  // Cập nhật số lượng con của nhóm khi xóa
  function updateGroupCount(gid) {
    var gRow = document.getElementById('group-' + gid);
    if (!gRow) return;
    var remaining = document.querySelectorAll('tr.filter-opts-' + gid).length;
    var countSpan = gRow.querySelector('.js-filter-group-count');
    if (countSpan) {
      countSpan.textContent = remaining;
    }
    var toggleBtn = gRow.querySelector('.js-filter-toggle');
    if (remaining === 0) {
      if (toggleBtn) toggleBtn.classList.add('d-none');
    } else {
      if (toggleBtn) toggleBtn.classList.remove('d-none');
    }
  }

  // 2. Xóa từng item bằng AJAX
  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('[data-ajax-delete]');
    if (!btn) return;
    ev.preventDefault();
    ev.stopPropagation();

    var name = btn.getAttribute('data-name') || 'tùy chọn này';
    var row = btn.closest('tr');
    var gid = row ? row.getAttribute('data-parent') : null;
    var url = btn.getAttribute('href');

    AdminUI.confirm.delete({ name: name }).then(function (ok) {
      if (!ok) return;

      var reqUrl = url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';

      fetch(reqUrl, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
      .then(function (res) { return res.json(); })
      .then(function (res) {
        if (res && res.ok) {
          AdminUI.notify.success(res.message || 'Đã xóa tùy chọn.');
          if (row) {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            setTimeout(function () {
              row.remove();
              if (gid) updateGroupCount(gid);
            }, 300);
          }
        } else {
          AdminUI.notify.error((res && res.message) || 'Xóa thất bại.');
        }
      })
      .catch(function () {
        AdminUI.notify.error('Lỗi kết nối khi xóa.');
      });
    });
  });

  // 3. Tự động Scroll và Highlight phần tử vừa lưu / sửa
  var urlParams = new URLSearchParams(window.location.search);
  var scrollItemId = urlParams.get('scroll_item');
  var scrollGroupId = urlParams.get('scroll_group') || urlParams.get('id_loai');
  var targetEl = null;

  if (scrollItemId) {
    targetEl = document.getElementById('item-' + scrollItemId);
  }
  // Nếu không tìm thấy item hoặc chỉ truyền nhóm -> cuộn tới nhóm cha
  if (!targetEl && scrollGroupId) {
    targetEl = document.getElementById('group-' + scrollGroupId);
  }

  if (targetEl) {
    // Đảm bảo mở danh sách nhóm cha nếu cuộn tới item con
    var parentGid = targetEl.getAttribute('data-parent');
    if (parentGid) {
      document.querySelectorAll('.filter-opts-' + parentGid).forEach(function (r) {
        r.classList.remove('d-none');
      });
      var gRow = document.getElementById('group-' + parentGid);
      if (gRow) {
        var tBtn = gRow.querySelector('.js-filter-toggle');
        if (tBtn) {
          tBtn.setAttribute('aria-expanded', 'true');
          var icon = tBtn.querySelector('i');
          if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
          }
        }
      }
    }

    setTimeout(function () {
      targetEl.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
      targetEl.classList.add('table-warning');
      targetEl.style.transition = 'background-color 1.5s ease';
      setTimeout(function () {
        targetEl.classList.remove('table-warning');
      }, 2500);
    }, 200);
  }
})();
</script>
