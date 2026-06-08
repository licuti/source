<?php foreach ($items as $item): ?>
    <?php $hasChild = !empty($item->children); ?>
    <li class="dd-item" data-id="<?= $item->id ?>">
        <div class="dd-handle">
            <span class="menu-icon"><i class="fa-solid <?= htmlspecialchars($item->icon ?: 'fa-circle') ?> text-muted"></i></span> 
            <strong><?= htmlspecialchars($item->name) ?></strong>
            
            <?php if ($item->badge_query): ?>
                <span class="bg-<?= htmlspecialchars($item->badge_color) ?> menu-badge">Badge</span>
            <?php endif; ?>
            
            <?php if (!$item->is_active): ?>
                <span class="badge bg-secondary ms-2">Ẩn</span>
            <?php endif; ?>
        </div>
        
        <div class="menu-item-actions">
            <a class="text-primary btn-edit" data-id="<?= $item->id ?>" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></a>
            <a href="<?= route('admin.system_menu.destroy', ['id' => $item->id]) ?>" class="text-danger btn-delete" title="Xóa"><i class="fa-solid fa-trash"></i></a>
        </div>
        
        <?php if ($hasChild): ?>
            <ol class="dd-list">
                <?= view('admin.system_menu.nestable_tree', ['items' => $item->children]) ?>
            </ol>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
