<?php
/**
 * Text — chuỗi đa ngữ (getTxt id|key).
 * List 9/3: bảng trái, nút Lưu sticky phải.
 */
$canEdit     = $d->checkPermission_edit($id_module) == 1;
$canDelete   = $d->checkPermission_dele($id_module) == 1;
$MODULE      = 'text';
$langs       = get_json('lang');
$items       = $items ?? [];
$textGroupOptions = $textGroupOptions ?? (function_exists('text_group_options') ? text_group_options() : []);

$saveUrl = admin_url(['p' => $MODULE, 'a' => 'save']);
?>

<?php component('page_header', [
    'title'      => 'Text giao diện',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Quản trị giao diện'],
        ['label' => 'Text'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <?php if ($canEdit): ?>
        <?php component('card_open', ['title' => 'Thêm text mới', 'icon' => 'fa-solid fa-plus']); ?>
        <form method="post" action="<?= e($saveUrl) ?>" class="row g-2">
            <div class="col-md-2">
                <?php component('form/select', [
                    'label'       => 'Nhóm',
                    'name'        => 'group_code',
                    'id'          => 'new_group',
                    'value'       => '',
                    'options'     => $textGroupOptions,
                    'placeholder' => '— Chọn nhóm —',
                ]); ?>
            </div>
            <div class="col-md-3">
                <?php component('form/input', [
                    'label' => 'Key (code)', 'name' => 'code', 'id' => 'new_code',
                    'placeholder' => 'form.label_name', 'value' => '',
                    'hint' => "getTxt('key') — để trống vẫn dùng ID",
                ]); ?>
            </div>
            <?php foreach ($langs as $lang):
                $lc = $lang['code'];
                ?>
                <div class="col-md-2">
                    <?php component('form/input', [
                        'label' => $lang['name'] ?? $lc,
                        'name'  => 'ten[' . $lc . ']',
                        'id'    => 'new_ten_' . $lc,
                        'value' => '',
                        'placeholder' => $lc,
                    ]); ?>
                </div>
            <?php endforeach; ?>
            <div class="col-md-auto">
                <button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary mb-3">
                    <i class="fa-solid fa-plus me-1"></i>Thêm
                </button>
            </div>
        </form>
        <?php component('card_close'); ?>
        <?php endif; ?>

        <form method="post" action="<?= e($saveUrl) ?>" id="form-text-bulk">
            <input type="hidden" name="bulk" value="1">

            <div class="row g-3">
                <div class="col-lg-9">
                    <?php component('card_open', [
                        'title' => 'Danh sách text',
                        'icon'  => 'fa-solid fa-language',
                        'body'  => 'none',
                    ]); ?>

                    <div class="table-responsive p-3">
                        <table class="table table-hover table-bordered align-middle table-admin mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center col-shrink" style="width:56px">ID</th>
                                    <th style="min-width:120px">Key</th>
                                    <th style="width:180px">Nhóm</th>
                                    <th>Các bản dịch</th>
                                    <th class="text-center col-shrink" style="width:70px">Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <?php /* empty when no DB rows */ component('empty_state', [
                                        'colspan' => 5,
                                        'message' => 'Không có text',
                                        'hint'    => 'Thêm mới ở form phía trên hoặc bỏ bộ lọc.',
                                    ]); ?>
                                <?php else: ?>
                                    <?php foreach ($items as $item):
                                        $tid = (int) $item['id'];
                                        $arr = json_decode((string) ($item['text'] ?? ''), true);
                                        if (!is_array($arr)) {
                                            $arr = [];
                                        }
                                        $codeVal  = (string) ($item['code'] ?? '');
                                        $groupVal = (string) ($item['group_code'] ?? '');
                                        $delUrl   = admin_url(['p' => $MODULE, 'a' => 'delete', 'id' => $tid]);
                                        $snippet  = $codeVal !== '' ? "getTxt('" . $codeVal . "')" : 'getTxt(' . $tid . ')';
                                        ?>
                                        <tr class="js-text-row"
                                            data-id="<?= $tid ?>"
                                            data-code="<?= e($codeVal) ?>"
                                            data-group="<?= e($groupVal) ?>"
                                            data-search="<?= e(mb_strtolower($tid . ' ' . $codeVal . ' ' . $groupVal . ' ' . implode(' ', $arr), 'UTF-8')) ?>">
                                            <td class="text-center col-shrink">
                                                <code class="user-select-all"><?= $tid ?></code>
                                            </td>
                                            <td>
                                                <?php if ($canEdit): ?>
                                                    <input type="text"
                                                           name="code[<?= $tid ?>]"
                                                           value="<?= e($codeVal) ?>"
                                                           class="form-control form-control-sm font-monospace"
                                                           placeholder="key.optional"
                                                           autocomplete="off">
                                                    <div class="form-text small text-truncate" title="<?= e($snippet) ?>">
                                                        <button type="button" class="btn btn-link btn-sm p-0 js-copy-snippet"
                                                                data-snippet="<?= e($snippet) ?>">
                                                            <i class="fa-regular fa-copy"></i> <?= e($snippet) ?>
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <code><?= e($codeVal !== '' ? $codeVal : '—') ?></code>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($canEdit): ?>
                                                    <select name="group_code[<?= $tid ?>]" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        <?php foreach ($textGroupOptions as $gCode => $gLabel): ?>
                                                            <option value="<?= e($gCode) ?>" <?= $groupVal === $gCode ? 'selected' : '' ?>>
                                                                <?= e($gLabel) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <?= e($groupVal !== '' ? ($textGroupOptions[$groupVal] ?? $groupVal) : '—') ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <?php foreach ($langs as $lang):
                                                        $lc = trim((string) ($lang['code'] ?? ''));
                                                        $ln = trim((string) ($lang['name'] ?? ''));
                                                        // Badge ngắn: luôn dùng mã ngôn ngữ (VI/EN…), không dùng name (có thể rỗng / Unicode)
                                                        $langBadge = $lc !== '' ? strtoupper($lc) : ($ln !== '' ? $ln : '?');
                                                        $val = (string) ($arr[$lc] ?? '');
                                                        ?>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text text-nowrap fw-semibold" style="min-width:2.75rem" title="<?= e($ln !== '' ? $ln : $langBadge) ?>"><?= e($langBadge) ?></span>
                                                            <?php if ($canEdit): ?>
                                                                <input type="text"
                                                                       class="form-control"
                                                                       name="ten[<?= $tid ?>][<?= e($lc) ?>]"
                                                                       value="<?= e($val) ?>"
                                                                       placeholder="<?= e($ln) ?>"
                                                                       autocomplete="off">
                                                            <?php else: ?>
                                                                <input type="text" class="form-control" value="<?= e($val) ?>" readonly>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td class="text-center col-shrink">
                                                <?php if ($canDelete): ?>
                                                    <a href="<?= e($delUrl) ?>"
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Xóa"
                                                       data-confirm-delete
                                                       data-name="text #<?= $tid ?><?= $codeVal !== '' ? ' (' . e($codeVal) . ')' : '' ?>"
                                                       data-child-count="0">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <tr id="js-text-filter-empty" class="d-none">
                                <td colspan="5" class="text-center text-secondary py-4">
                                    Không có dòng khớp bộ lọc.
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <?php component('card_close', ['body' => 'none']); ?>

                    <p class="small text-secondary mt-2 mb-0">
                        Front: <code>getTxt(12)</code> hoặc <code>getTxt('form.label_name')</code>.
                        Xóa ID có thể làm gãy template đang gọi số.
                    </p>
                </div>

                <div class="col-lg-3">
                    <div class="sticky-top d-flex flex-column gap-3" style="top: 1rem; z-index: 1020;">

                        <?php component('card_open', [
                            'title' => 'Tìm kiếm',
                            'icon'  => 'fa-solid fa-magnifying-glass',
                            'class' => 'border shadow-sm mb-0',
                        ]); ?>
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="js-text-filter-key">Từ khóa</label>
                                <input type="search" id="js-text-filter-key"
                                       class="form-control form-control-sm"
                                       placeholder="ID / key / nội dung…"
                                       autocomplete="off">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="js-text-filter-group">Nhóm</label>
                                <select id="js-text-filter-group" class="form-select form-select-sm">
                                    <option value="">— Tất cả nhóm —</option>
                                    <?php foreach ($textGroupOptions as $gCode => $gLabel): ?>
                                        <option value="<?= e($gCode) ?>"><?= e($gLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" id="js-text-filter-clear"
                                    class="btn btn-sm btn-outline-secondary w-100">
                                Xóa lọc
                            </button>
                            <div class="small text-secondary mt-3 mb-0 border-top pt-2">
                                Hiển thị:
                                <strong id="js-text-filter-count"><?= count($items) ?></strong>
                                / <?= count($items) ?> dòng
                                <div class="text-muted mt-1">Lọc tức thì (JS) · sắp xếp theo ID.</div>
                            </div>
                        <?php component('card_close'); ?>

                        <?php component('card_open', [
                            'title' => 'Lưu thay đổi',
                            'icon'  => 'fa-solid fa-floppy-disk',
                            'class' => 'border shadow-sm mb-0',
                        ]); ?>
                            <p class="small text-secondary mb-3">Sửa key / nhóm / bản dịch trên bảng, rồi bấm lưu tại đây.</p>
                            <?php if ($canEdit && !empty($items)): ?>
                                <button type="submit" name="capnhat" value="capnhat"
                                        class="btn btn-primary w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Lưu tất cả
                                </button>
                            <?php elseif (!$canEdit): ?>
                                <div class="alert alert-secondary py-2 small mb-0">Chỉ xem — không có quyền sửa.</div>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary w-100" disabled>Không có dữ liệu</button>
                            <?php endif; ?>
                        <?php component('card_close'); ?>

                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
(function () {
  document.querySelectorAll('.js-copy-snippet').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var s = btn.getAttribute('data-snippet') || '';
      function ok() {
        if (window.AdminUI && AdminUI.notify) AdminUI.notify.success('Đã copy: ' + s);
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(s).then(ok).catch(function () { window.prompt('Copy:', s); });
      } else {
        window.prompt('Copy:', s);
      }
    });
  });

  var keyInput = document.getElementById('js-text-filter-key');
  var groupSel = document.getElementById('js-text-filter-group');
  var clearBtn = document.getElementById('js-text-filter-clear');
  var countEl  = document.getElementById('js-text-filter-count');
  var emptyEl  = document.getElementById('js-text-filter-empty');
  var rows     = Array.prototype.slice.call(document.querySelectorAll('tr.js-text-row'));

  function applyFilter() {
    var q = (keyInput && keyInput.value ? keyInput.value : '').toLowerCase().trim();
    var g = groupSel && groupSel.value ? groupSel.value : '';
    var shown = 0;
    rows.forEach(function (tr) {
      var hay = tr.getAttribute('data-search') || '';
      var grpEl = tr.querySelector('select[name^="group_code"]');
      var grp = grpEl ? (grpEl.value || '') : (tr.getAttribute('data-group') || '');
      // live inputs: also match current key/translation values
      if (q) {
        var live = '';
        tr.querySelectorAll('input, select').forEach(function (el) {
          live += ' ' + (el.value || '');
        });
        hay = (hay + live).toLowerCase();
      }
      var okKey = !q || hay.indexOf(q) !== -1;
      var okGroup = !g || grp === g;
      var show = okKey && okGroup;
      tr.classList.toggle('d-none', !show);
      if (show) shown++;
    });
    if (countEl) countEl.textContent = String(shown);
    if (emptyEl) emptyEl.classList.toggle('d-none', shown !== 0 || rows.length === 0);
  }

  var timer = null;
  function debounced() {
    clearTimeout(timer);
    timer = setTimeout(applyFilter, 120);
  }

  if (keyInput) keyInput.addEventListener('input', debounced);
  if (groupSel) groupSel.addEventListener('change', applyFilter);
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (keyInput) keyInput.value = '';
      if (groupSel) groupSel.value = '';
      applyFilter();
      if (keyInput) keyInput.focus();
    });
  }
})();
</script>
