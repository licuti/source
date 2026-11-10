<?php
/**
 * LMS Form Component: form/avatar_picker
 * Bộ chọn và xem trước ảnh đại diện (kèm nút huỷ tròn đỏ).
 *
 * Props:
 * @var string $name        Tên file input (default: 'avatar_file')
 * @var string $remove_name Tên hidden flag xoá ảnh (default: 'remove_avatar')
 * @var string $label       Nhãn trường (default: 'Ảnh đại diện')
 * @var string $value       URL ảnh hiện tại
 * @var string $subtext     Dòng chú thích định dạng (default: '(png,jpg,jpeg)')
 * @var string $hint
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : 'avatar_file';
$remove_name = isset($remove_name) ? (string) $remove_name : 'remove_avatar';
$label       = isset($label) ? (string) $label : 'Ảnh đại diện';
$value       = isset($value) ? (string) $value : '';
$subtext     = isset($subtext) ? (string) $subtext : '(png,jpg,jpeg)';
$hint        = isset($hint) ? (string) $hint : '';
$idPrefix    = 'lms-av-' . str_replace(array('[', ']', '_'), array('', '', '-'), $name);

ob_start();
?>
<div class="lms-tf-avatar-group" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-wrap">
    <div class="lms-tf-avatar-btn-wrap">
        <button type="button" class="lms-tf-btn-choose-img" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-btn">Chọn hình</button>
        <?php if ($subtext !== ''): ?>
            <span class="lms-tf-avatar-subtext"><?= htmlspecialchars($subtext, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <input type="file" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-file"
               name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
               accept=".png,.jpg,.jpeg" style="display:none;">
        <input type="hidden" name="<?= htmlspecialchars($remove_name, ENT_QUOTES, 'UTF-8') ?>"
               id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-remove" value="0">
    </div>

    <div class="lms-tf-avatar-preview-box" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-box"
         style="<?= $value !== '' ? '' : 'display:none;' ?>">
        <img src="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar preview"
             id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-img" class="lms-tf-avatar-img">
        <button type="button" class="lms-tf-avatar-remove" id="<?= htmlspecialchars($idPrefix, ENT_QUOTES, 'UTF-8') ?>-del" title="Xoá hình">
            <i class="fa-solid fa-circle-xmark"></i>
        </button>
    </div>
</div>

<script>
(function() {
    var prefix = <?= json_encode($idPrefix) ?>;
    var btn = document.getElementById(prefix + '-btn');
    var fileInput = document.getElementById(prefix + '-file');
    var box = document.getElementById(prefix + '-box');
    var img = document.getElementById(prefix + '-img');
    var delBtn = document.getElementById(prefix + '-del');
    var removeFld = document.getElementById(prefix + '-remove');

    if (!btn || btn.dataset.inited) return;
    btn.dataset.inited = '1';

    btn.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                box.style.display = 'block';
                if (removeFld) removeFld.value = '0';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    if (delBtn) {
        delBtn.addEventListener('click', function() {
            fileInput.value = '';
            img.src = '';
            box.style.display = 'none';
            if (removeFld) removeFld.value = '1';
        });
    }
})();
</script>
<?php
$controlHtml = ob_get_clean();

lms_component('form/field_row', array(
    'label'    => $label,
    'name'     => $name,
    'required' => false,
    'control'  => $controlHtml,
    'hint'     => $hint,
    'errors'   => isset($errors) ? $errors : array(),
));
