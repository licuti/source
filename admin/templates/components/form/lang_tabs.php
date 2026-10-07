<?php
/**
 * Component: form/lang_tabs
 * Tabs chọn ngôn ngữ (chuẩn Bootstrap 5: data-bs-toggle, .nav-link, .show).
 * Chỉ render tabs khi hệ thống có nhiều hơn 1 ngôn ngữ.
 *
 * Props:
 * @var array  $langs  Mảng ngôn ngữ từ get_json('lang'):
 *                     [['code' => 'vi', 'name' => 'Tiếng Việt'], ...]
 * @var string $prefix (tuỳ chọn) tiền tố id pane để tránh trùng khi 1 trang
 *                     có nhiều bộ tab
 */
$langs  = $langs  ?? [];
$prefix = $prefix ?? 'lang';

if (count($langs) <= 1) {
    return; // 1 ngôn ngữ thì không cần tabs
}
?>
<ul class="nav nav-tabs mb-3" role="tablist">
    <?php foreach ($langs as $i => $lang):
        $code = $lang['code'] ?? '';
        $name = $lang['name'] ?? $code;
        $pane = $prefix . '-' . $code;
        ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                    id="<?= e($pane) ?>-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#<?= e($pane) ?>"
                    type="button"
                    role="tab"
                    aria-controls="<?= e($pane) ?>"
                    aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                <?= e($name) ?>
            </button>
        </li>
    <?php endforeach; ?>
</ul>