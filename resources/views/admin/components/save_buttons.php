<?php
/**
 * Component Save Buttons (Các nút chức năng Form)
 * 
 * @param string $back_url URL của nút Quay lại (Bắt buộc nếu dùng nút mặc định)
 * @param string $action_name Tên biến submit (mặc định 'save_action')
 * @param string $exit_value Value khi click Lưu (mặc định 'exit')
 * @param string $continue_value Value khi click Lưu và sửa (mặc định 'continue')
 * @param array $buttons Mảng chứa cấu hình các nút. Nếu truyền vào, sẽ override hoàn toàn 3 nút mặc định.
 */
$back_url = $back_url ?? '#';
$action_name = $action_name ?? 'save_action';

$buttons = $buttons ?? [
    [
        'type' => 'link',
        'url' => $back_url,
        'class' => 'btn btn-secondary btn-sm',
        'icon' => 'fa-solid fa-arrow-left',
        'text' => 'Quay lại'
    ],
    [
        'type' => 'submit',
        'name' => $action_name,
        'value' => $exit_value ?? 'exit',
        'class' => 'btn btn-primary btn-sm',
        'icon' => 'fa-solid fa-save',
        'text' => 'Lưu'
    ],
    [
        'type' => 'submit',
        'name' => $action_name,
        'value' => $continue_value ?? 'continue',
        'class' => 'btn btn-success btn-sm',
        'icon' => 'fa-solid fa-pen-to-square',
        'text' => 'Lưu và sửa'
    ]
];
?>
<div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
    <?php foreach ($buttons as $btn): ?>
        <?php if (($btn['type'] ?? 'submit') === 'link'): ?>
            <a href="<?= htmlspecialchars($btn['url'] ?? '#') ?>" class="<?= htmlspecialchars($btn['class'] ?? 'btn btn-secondary btn-sm') ?>" <?= !empty($btn['attrs']) ? render_attrs($btn['attrs']) : '' ?>>
                <?php if (!empty($btn['icon'])): ?><i class="<?= htmlspecialchars($btn['icon']) ?>"></i><?php endif; ?>
                <?= htmlspecialchars($btn['text'] ?? '') ?>
            </a>
        <?php else: ?>
            <button type="<?= htmlspecialchars($btn['type'] ?? 'submit') ?>" 
                    name="<?= htmlspecialchars($btn['name'] ?? '') ?>" 
                    value="<?= htmlspecialchars($btn['value'] ?? '') ?>" 
                    class="<?= htmlspecialchars($btn['class'] ?? 'btn btn-primary btn-sm') ?>"
                    <?= !empty($btn['attrs']) ? render_attrs($btn['attrs']) : '' ?>>
                <?php if (!empty($btn['icon'])): ?><i class="<?= htmlspecialchars($btn['icon']) ?>"></i><?php endif; ?>
                <?= htmlspecialchars($btn['text'] ?? '') ?>
            </button>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
