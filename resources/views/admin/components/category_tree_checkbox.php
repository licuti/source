<?php
/**
 * Component Category Tree Checkbox
 * 
 * Các tham số truyền vào:
 * @param array  $categories Mảng đối tượng Category (có quan hệ children)
 * @param string $name Tên biến submit (mặc định: 'category_ids[]')
 * @param array  $selectedIds Mảng chứa các ID đã được chọn
 */

$name = $name ?? 'category_ids[]';
$selectedIds = $selectedIds ?? [];

if (!function_exists('renderCategoryCheckboxTree')) {
    function renderCategoryCheckboxTree($categories, $name, $selectedIds) {
        if (empty($categories)) return;
        echo '<ul class="list-unstyled ms-4 mb-0 border-start border-light border-2" style="border-style: dashed !important;">';
        foreach ($categories as $cat) {
            // Hỗ trợ cả array và object
            $catId = is_object($cat) ? ($cat->id ?? 0) : ($cat['id'] ?? 0);
            $catName = is_object($cat) ? ($cat->title ?? ($cat->ten ?? ($cat->name ?? ''))) : ($cat['title'] ?? ($cat['ten'] ?? ($cat['name'] ?? '')));
            $children = is_object($cat) ? ($cat->children ?? []) : ($cat['children'] ?? []);
            
            $isChecked = in_array($catId, $selectedIds) ? 'checked' : '';
            
            echo '<li class="mb-1 position-relative">';
            // Đường line ngang nối vào checkbox
            echo '<div style="position:absolute; width: 15px; height: 1px; border-top: 2px dashed #f8f9fa; top: 12px; left: -20px;"></div>';
            
            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '" value="' . $catId . '" id="cat_' . $catId . '_' . md5((string)$catId) . '" ' . $isChecked . '>';
            echo '<label class="form-check-label" for="cat_' . $catId . '_' . md5((string)$catId) . '">' . htmlspecialchars($catName) . '</label>';
            echo '</div>';
            
            if (!empty($children)) {
                renderCategoryCheckboxTree($children, $name, $selectedIds);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
}
?>

<div class="category-checkbox-tree border rounded p-3 bg-white" style="max-height: 350px; overflow-y: auto;">
    <?php 
        echo '<div style="margin-left: -1.5rem;">';
        renderCategoryCheckboxTree($categories ?? [], $name, $selectedIds); 
        echo '</div>';
    ?>
</div>

<style>
.category-checkbox-tree .form-check { margin-bottom: 0.2rem; }
.category-checkbox-tree .form-check-input { cursor: pointer; }
.category-checkbox-tree .form-check-label { cursor: pointer; font-size: 0.95rem; }
</style>
