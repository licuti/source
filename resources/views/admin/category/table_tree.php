<?php foreach ($categories as $item): ?>
    <?php
    $level = $level ?? 0;
    $isSearch = $isSearch ?? false;
    
    // Tạo tiền tố thụt lề
    $prefix = '';
    if ($level > 0 && !$isSearch) {
        $prefix = '<span class="text-muted ms-' . ($level * 3) . '">|&mdash;&mdash; </span>';
    }

    // Hình ảnh
    $imgHtml = '';
    if ($item->image) {
        $imgHtml = '<img src="' . getImageUrl($item->image) . '" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">';
    } else {
        $imgHtml = '<span class="badge bg-light text-dark border">Trống</span>';
    }

    // Trạng thái (Toggle Switch)
    $checked = $item->status ? 'checked' : '';
    if (hasPermission('admin.category', 'edit')) {
        $statusHtml = '
            <div class="form-check form-switch d-flex justify-content-center">
                <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="' . $item->id . '" data-field="status" data-url="' . route('admin.category.updateStatusAjax') . '" ' . $checked . ' style="cursor: pointer; width: 2.5em; height: 1.25em;">
            </div>
        ';
    } else {
        $statusHtml = '
            <div class="form-check form-switch d-flex justify-content-center">
                <input class="form-check-input" type="checkbox" ' . $checked . ' disabled style="width: 2.5em; height: 1.25em;">
            </div>
        ';
    }
    ?>

    <tr class="wp-row">
        <td scope="row" class="text-center align-middle">
            <div class="form-check d-flex justify-content-center mb-0">
                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id ?>">
            </div>
        </td>
        <td class="text-center align-middle"><?= $imgHtml ?></td>
        <td class="align-middle">
            <?= $prefix ?><strong><a href="<?= route('admin.category.edit', ['id' => $item->id]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->title ?? '') ?></a></strong>
            <?php
            $actions = [];
            if (hasPermission('admin.category', 'edit')) {
                $actions['edit'] = [
                    'label' => 'Chỉnh sửa', 
                    'url' => route('admin.category.edit', ['id' => $item->id]), 
                    'class' => 'text-primary'
                ];
            }
            if (hasPermission('admin.category', 'delete')) {
                $actions['delete'] = [
                    'label' => 'Xóa', 
                    'url' => route('admin.category.destroy', ['id' => $item->id]), 
                    'class' => 'text-danger', 
                    'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa danh mục này cùng toàn bộ danh mục con (nếu có)?\')"'
                ];
            }
            if (!empty($actions)) {
                echo view('admin.components.row_actions', ['actions' => $actions]);
            }
            ?>
        </td>
        
        <!-- Ngôn ngữ -->
        <td class="text-center align-middle">
            <?php foreach ($langs as $l): ?>
                <?php
                $lCode = $l['code'];
                $hasTranslation = isset($translations[$item->id][$lCode]);
                $flagSrc = !empty($l['image']) ? getImageUrl($l['image']) : '';
                ?>
                <?php if ($hasTranslation): ?>
                    <a href="<?= route('admin.category.edit', ['id' => $item->id]) ?>?lang=<?= $lCode ?>" class="text-decoration-none d-inline-flex align-items-center me-2 mb-1" title="Sửa bản <?= htmlspecialchars($l['name']) ?>">
                        <?php if($flagSrc): ?>
                            <img src="<?= $flagSrc ?>" alt="<?= $lCode ?>" style="width: 20px; height: 14px; object-fit: cover; border-radius: 2px;" class="border shadow-sm me-1">
                        <?php else: ?>
                            <span class="badge bg-light text-dark border me-1"><?= strtoupper($lCode) ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= route('admin.category.create') ?>?lang=<?= $lCode ?>&source_id=<?= $item->id ?>" class="text-decoration-none d-inline-flex align-items-center me-2 mb-1" title="Thêm bản <?= htmlspecialchars($l['name']) ?>">
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="width: 20px; height: 14px; padding: 0; line-height: 12px;">+</span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </td>

        <td class="text-center align-middle"><?= $item->sort_order ?></td>
        <td class="text-center align-middle"><?= $statusHtml ?></td>
    </tr>

    <?php 
    // Đệ quy in con (chỉ in nếu không phải chế độ search)
    if (!empty($item->children) && !$isSearch): 
    ?>
        <?= view('admin.category.table_tree', [
            'categories' => $item->children, 
            'level' => $level + 1, 
            'isSearch' => $isSearch,
            'langs' => $langs,
            'translations' => $translations
        ]) ?>
    <?php endif; ?>

<?php endforeach; ?>
