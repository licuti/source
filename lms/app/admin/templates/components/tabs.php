<?php
/**
 * LMS Shared Component: tabs
 * Thanh tab điều hướng dùng chung (Bootstrap nav + class `lms-tabs-nav`).
 * Mỗi tab là một link; trạng thái active do source truyền vào, component không
 * tự so khớp URL để dùng được cho cả tab theo query string lẫn theo route.
 *
 * Props:
 * @var array  $items      Danh sách tab: [['label' => string, 'href' => string, 'active' => bool, 'attrs' => array], ...]
 * @var string $aria_label Nhãn điều hướng (mặc định: 'Tabs')
 * @var string $class      Class CSS bổ sung cho <nav>
 *
 * PHP 7.3 compatible.
 */

$items     = isset($items) && is_array($items) ? $items : array();
$ariaLabel = isset($aria_label) && $aria_label !== '' ? (string) $aria_label : 'Tabs';
$extraCls  = isset($class) ? (string) $class : '';

if ($items === array()) {
    return;
}

$classes = array('nav', 'lms-tabs-nav');
if ($extraCls !== '') {
    $classes[] = $extraCls;
}
?>
<nav class="<?= e(implode(' ', $classes)) ?>" role="tablist" aria-label="<?= e($ariaLabel) ?>">
    <?php foreach ($items as $item): ?>
        <?php
        $label  = isset($item['label']) ? (string) $item['label'] : '';
        $href   = isset($item['href']) ? (string) $item['href'] : '';
        $active = !empty($item['active']);
        $attrs  = isset($item['attrs']) && is_array($item['attrs']) ? $item['attrs'] : array();

        $attrPairs = array();
        foreach ($attrs as $k => $v) {
            if (is_bool($v)) {
                if ($v) {
                    $attrPairs[] = e((string) $k);
                }
            } else {
                $attrPairs[] = e((string) $k) . '="' . e((string) $v) . '"';
            }
        }
        ?>
        <a class="nav-link<?= $active ? ' active' : '' ?>"
           href="<?= e($href) ?>"
           role="tab"
           aria-selected="<?= $active ? 'true' : 'false' ?>"
           <?= $active ? 'aria-current="page"' : '' ?>
           <?= $attrPairs !== array() ? implode(' ', $attrPairs) : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
