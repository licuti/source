<?php
/**
 * LMS Form Component: form/file_picker
 * Bộ chọn và quản lý file upload đồng bộ giao diện với hệ thống LMS Form.
 *
 * Props:
 * @var string $name        Tên trường input file (VD: 'test_file')
 * @var string $label       Nhãn trường
 * @var string $id          ID input (tự sinh nếu không truyền)
 * @var string $accept      Định dạng chấp nhận (VD: '.pdf,.doc,.docx,.xls,.xlsx')
 * @var bool   $required    Bắt buộc chọn
 * @var string $value       Đường dẫn file hiện tại (nếu có)
 * @var string $delete_name Tên checkbox xoá file hiện tại (VD: 'delete_test_file')
 * @var string $button_text Chữ trên nút chọn file (mặc định: 'Chọn file')
 * @var string $hint        Dòng gợi ý phía dưới
 * @var array  $row
 * @var array  $errors
 *
 * PHP 7.3 compatible.
 */

$name        = isset($name) ? (string) $name : 'file';
$label       = isset($label) ? (string) $label : '';
$accept      = isset($accept) ? (string) $accept : '.pdf,.doc,.docx,.xls,.xlsx';
$required    = !empty($required);
$value       = isset($value) ? (string) $value : '';
$delete_name = isset($delete_name) ? (string) $delete_name : '';
$button_text = isset($button_text) ? (string) $button_text : 'Chọn file';
$hint        = isset($hint) ? (string) $hint : '';
$errors      = isset($errors) && is_array($errors) ? $errors : array();
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);
$idPrefix    = 'lms-fp-' . str_replace(array('[', ']', '_'), array('', '', '-'), $name);

ob_start();
?>
<div class="lms-tf-file-picker" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-wrap">
    <!-- Input file ẩn thực tế -->
    <input type="file"
           name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
           id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"
           class="d-none"
           accept="<?= htmlspecialchars($accept, ENT_QUOTES, 'UTF-8') ?>"
           <?= $required ? 'required' : '' ?>>

    <!-- Khung chọn file đồng bộ chiều cao và style với .lms-tf-input -->
    <div class="lms-tf-file-box">
        <button type="button" class="lms-tf-file-btn" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-btn">
            <i class="fa-solid fa-arrow-up-from-bracket me-1"></i> <?= htmlspecialchars($button_text, ENT_QUOTES, 'UTF-8') ?>
        </button>
        <span class="lms-tf-file-text" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-text">
            Chưa có file nào được chọn
        </span>
        <button type="button" class="lms-tf-file-clear d-none" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-clear" title="Huỷ file vừa chọn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Hiển thị file đã có (trường hợp sửa / xem chi tiết) -->
    <?php if ($value !== ''): ?>
        <div class="lms-tf-file-current mt-2">
            <div class="d-flex align-items-center gap-2 overflow-hidden flex-grow-1">
                <?php
                $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                $iconClass = 'fa-solid fa-file text-secondary';
                if ($ext === 'pdf') {
                    $iconClass = 'fa-solid fa-file-pdf text-danger';
                } elseif (in_array($ext, array('doc', 'docx'), true)) {
                    $iconClass = 'fa-solid fa-file-word text-primary';
                } elseif (in_array($ext, array('xls', 'xlsx'), true)) {
                    $iconClass = 'fa-solid fa-file-excel text-success';
                }
                ?>
                <i class="<?= $iconClass ?> fs-5 flex-shrink-0"></i>
                <span class="text-muted small flex-shrink-0">Hiện tại:</span>
                <a href="<?= e(lms_upload_url($value)) ?>" target="_blank" rel="noopener" class="fw-semibold text-truncate text-decoration-none">
                    <?= e(basename($value)) ?>
                </a>
                <a href="<?= e(lms_upload_url($value)) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light py-0 px-2 flex-shrink-0 text-muted" title="Mở file trong tab mới">
                    <i class="fa-solid fa-arrow-up-right-from-square small"></i>
                </a>
            </div>
            <?php if ($delete_name !== ''): ?>
                <label class="form-check-label text-danger small ms-2 d-inline-flex align-items-center gap-1 flex-shrink-0" style="cursor: pointer;">
                    <input type="checkbox" name="<?= htmlspecialchars($delete_name, ENT_QUOTES, 'UTF-8') ?>" value="1" class="form-check-input mt-0">
                    <span>Xoá file này</span>
                </label>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var prefix = <?= json_encode($idPrefix) ?>;
    var btn = document.getElementById(prefix + '-btn');
    var fileInput = document.getElementById(<?= json_encode($id) ?>);
    var textSpan = document.getElementById(prefix + '-text');
    var clearBtn = document.getElementById(prefix + '-clear');

    if (!btn || !fileInput || btn.dataset.inited) return;
    btn.dataset.inited = '1';

    btn.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files.length > 0) {
            var f = fileInput.files[0];
            var sizeStr = '';
            if (f.size > 1048576) {
                sizeStr = ' (' + (f.size / 1048576).toFixed(1) + ' MB)';
            } else if (f.size > 1024) {
                sizeStr = ' (' + Math.round(f.size / 1024) + ' KB)';
            }
            textSpan.textContent = f.name + sizeStr;
            textSpan.classList.add('has-file');
            if (clearBtn) clearBtn.classList.remove('d-none');
        } else {
            textSpan.textContent = 'Chưa có file nào được chọn';
            textSpan.classList.remove('has-file');
            if (clearBtn) clearBtn.classList.add('d-none');
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.value = '';
            textSpan.textContent = 'Chưa có file nào được chọn';
            textSpan.classList.remove('has-file');
            clearBtn.classList.add('d-none');
        });
    }
})();
</script>
<?php
$controlHtml = ob_get_clean();

if (!empty($bare) || !empty($no_wrap) || !empty($no_row)) {
    echo $controlHtml;
    return;
}

lms_component('form/field_row', array(
    'label'    => $label,
    'name'     => $name,
    'id'       => $id,
    'align'    => 'start',
    'required' => $required,
    'control'  => $controlHtml,
    'hint'     => $hint,
    'errors'   => $errors,
));
