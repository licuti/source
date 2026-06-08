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
    if ($item->hinh_anh) {
        $imgHtml = '<img src="' . getImageUrl($item->hinh_anh) . '" alt="Image" class="img-thumbnail" style="height: 45px; width: auto; object-fit: cover;">';
    } else {
        $imgHtml = '<span class="badge bg-light text-dark border">Trống</span>';
    }

    // Trạng thái (Toggle Switch)
    $checked = $item->hien_thi ? 'checked' : '';
    $statusHtml = '
        <div class="form-check form-switch d-flex justify-content-center">
            <input class="form-check-input ajax-toggle-status" type="checkbox" data-id="' . $item->id_code . '" data-field="hien_thi" data-url="' . route('admin.category.updateStatusAjax') . '" ' . $checked . ' style="cursor: pointer; width: 2.5em; height: 1.25em;">
        </div>
    ';
    ?>

    <tr class="wp-row">
        <th scope="row" class="text-center align-middle">
            <div class="form-check d-flex justify-content-center mb-0">
                <input class="form-check-input row-check" type="checkbox" value="<?= $item->id_code ?>">
            </div>
        </th>
        <td class="text-center align-middle"><?= $imgHtml ?></td>
        <td class="align-middle">
            <?= $prefix ?><strong><a href="<?= route('admin.category.edit', ['id' => $item->id_code]) ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($item->ten) ?></a></strong>
            <?= view('admin.components.row_actions', [
                'actions' => [
                    'edit' => [
                        'label' => 'Chỉnh sửa', 
                        'url' => route('admin.category.edit', ['id' => $item->id_code]), 
                        'class' => 'text-primary'
                    ],
                    'delete' => [
                        'label' => 'Xóa', 
                        'url' => route('admin.category.destroy', ['id' => $item->id_code]), 
                        'class' => 'text-danger', 
                        'attributes' => 'onclick="return confirm(\'Bạn có chắc chắn muốn xóa danh mục này cùng toàn bộ danh mục con (nếu có)?\')"'
                    ]
                ]
            ]) ?>
        </td>
        <td class="text-center align-middle"><?= $item->so_thu_tu ?></td>
        <td class="text-center align-middle"><?= $statusHtml ?></td>
    </tr>

    <?php 
    // Đệ quy in con (chỉ in nếu không phải chế độ search)
    if (!empty($item->children) && !$isSearch): 
    ?>
        <?= view('admin.category.table_tree', [
            'categories' => $item->children, 
            'level' => $level + 1, 
            'isSearch' => $isSearch
        ]) ?>
    <?php endif; ?>

<?php endforeach; ?>
