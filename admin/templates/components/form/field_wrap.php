<?php
/**
 * Component: form/field_wrap
 * Khung bọc 1 field dạng dọc (label trên, control dưới) — giống .form-group.
 *
 * Props:
 * @var string     $label    Nhãn
 * @var string     $for      (tuỳ chọn) id của control để gắn <label for>
 * @var array|null $col      null = layout dọc (mặc định).
 *                           Truyền [label, control] theo grid 12 nếu muốn layout ngang.
 * @var bool       $required Hiện dấu * đỏ
 * @var string     $hint     (tuỳ chọn) chú thích dưới control
 * @var string     $note     (tuỳ chọn) HTML nhỏ dưới label
 * @var string     $slot     HTML của control
 * @var string     $mode     '' | 'open' | 'close'
 */
$label    = $label    ?? '';
$for      = $for      ?? '';
// Mặc định dọc. Chỉ dùng row/col khi caller truyền mảng $col.
if (!isset($col) || $col === null || $col === false) {
    $col = null;
}
$required = !empty($required);
$hint     = $hint     ?? '';
$note     = $note     ?? '';
$slot     = $slot     ?? '';
$mode     = $mode     ?? '';

$useVertical = !is_array($col);
?>
<?php if ($mode !== 'close'): ?>
<?php if ($useVertical): ?>
<div class="mb-3">
    <?php if ($label !== ''): ?>
    <label class="form-label fw-medium" <?= $for !== '' ? 'for="' . e($for) . '"' : '' ?>>
        <?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
    </label>
    <?php endif; ?>
    <?php if ($note !== ''): ?><div class="small text-secondary mb-1"><?= $note ?></div><?php endif; ?>
    <?= $slot ?>
<?php else:
    $labelCol   = (int) ($col[0] ?? 2);
    $controlCol = (int) ($col[1] ?? (12 - $labelCol));
?>
<div class="row mb-3">
    <div class="col-sm-<?= $labelCol ?>">
        <label class="form-label mb-0 pt-1 fw-medium" <?= $for !== '' ? 'for="' . e($for) . '"' : '' ?>>
            <?= e($label) ?><?php if ($required): ?> <span class="text-danger">*</span><?php endif; ?>
        </label>
        <?php if ($note !== ''): ?><div class="small text-secondary"><?= $note ?></div><?php endif; ?>
    </div>
    <div class="col-sm-<?= $controlCol ?>">
        <?= $slot ?>
<?php endif; ?>
<?php endif; ?>
<?php if ($mode !== 'open'): ?>
    <?php if ($hint !== ''): ?><div class="form-text"><?= e($hint) ?></div><?php endif; ?>
<?php if ($useVertical): ?>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
