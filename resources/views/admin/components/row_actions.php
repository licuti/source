<?php
/**
 * Component for WordPress style row actions
 * 
 * Bắt buộc thẻ `<tr>` chứa component này phải có class `wp-row` 
 * để hiệu ứng hover hoạt động.
 * 
 * @param array $actions Array of actions. Example:
 * [
 *     'edit' => ['label' => 'Chỉnh sửa', 'url' => '...', 'class' => 'text-primary'],
 *     'delete' => ['label' => 'Xóa', 'url' => '...', 'class' => 'text-danger', 'attributes' => 'onclick="return confirm(\'...\')"']
 * ]
 */
$actions = $actions ?? [];
if (empty($actions)) return;
?>
<div class="wp-row-actions mt-1" style="font-size: 13px; visibility: hidden; opacity: 0; transition: all 0.2s;">
    <?php 
    $total = count($actions);
    $i = 0;
    foreach ($actions as $key => $action): 
        $i++;
        $class = $action['class'] ?? 'text-secondary';
        $attributes = $action['attributes'] ?? '';
    ?>
        <span class="<?= htmlspecialchars($key) ?>">
            <a href="<?= $action['url'] ?>" class="<?= htmlspecialchars($class) ?> text-decoration-none" <?= $attributes ?>>
                <?= htmlspecialchars($action['label']) ?>
            </a>
            <?= $i < $total ? ' | ' : '' ?>
        </span>
    <?php endforeach; ?>
</div>


