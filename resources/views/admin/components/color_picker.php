<?php
/**
 * Component Color Picker (Sử dụng thư viện Pickr)
 * 
 * Các tham số truyền vào:
 * @param string $name Tên biến submit (bắt buộc)
 * @param string $value Giá trị mặc định (HEX, RGB, HSL...), mặc định '#000000'
 * @param string $label Nhãn hiển thị bên ngoài
 * @param string $id ID tuỳ chỉnh (mặc định sẽ tự sinh nếu không truyền)
 */

$name = $name ?? '';
$value = $value ?? '#000000';
$label = $label ?? 'Chọn màu';
$id = $id ?? 'color_picker_' . uniqid();

// Xử lý value rỗng (nếu DB chưa có dữ liệu)
if (empty($value)) {
    $value = '#000000';
}
?>

<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= $label ?></label>
    <?php endif; ?>
    
    <div class="d-flex align-items-center gap-2">
        <!-- Nút bấm chứa Color Picker -->
        <div id="pickr_<?= $id ?>"></div>
        
        <!-- Input ẩn lưu giá trị cho form submit -->
        <input type="text" name="<?= htmlspecialchars($name) ?>" id="input_<?= $id ?>" value="<?= htmlspecialchars((string)$value) ?>" class="form-control form-control-sm" style="max-width: 120px;" readonly>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const pickr = Pickr.create({
        el: '#pickr_<?= $id ?>',
        theme: 'nano', // 'classic', 'monolith', 'nano'
        default: '<?= htmlspecialchars((string)$value) ?>',
        
        swatches: [
            '#0f0f13', // Nền đen mặc định của chế độ bảo trì
            '#3b82f6', // Primary Blue
            '#ef4444', // Danger Red
            '#10b981', // Success Green
            '#f59e0b', // Warning Yellow
            '#6366f1', // Indigo
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#ffffff'  // White
        ],

        components: {
            preview: true,
            opacity: true,
            hue: true,

            interaction: {
                hex: true,
                rgba: true,
                hsla: true,
                input: true,
                clear: true,
                save: true
            }
        },
        i18n: {
            'btn:save': 'Lưu',
            'btn:clear': 'Xóa',
        }
    });

    pickr.on('save', (color, instance) => {
        const inputEl = document.getElementById('input_<?= $id ?>');
        if (color) {
            inputEl.value = color.toHEXA().toString();
        } else {
            inputEl.value = '';
        }
        pickr.hide();
    });

    // Cập nhật live value khi kéo thanh trượt
    pickr.on('change', (color, source, instance) => {
        const inputEl = document.getElementById('input_<?= $id ?>');
        if (color) {
            inputEl.value = color.toHEXA().toString();
        }
    });
});
</script>
