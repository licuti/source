<?php
/**
 * LMS Form Component: form/rich_editor
 * Trình soạn thảo văn bản Rich Text chuẩn hoá theo mockup:
 *  - Tai-lieu-phat-trien/admin/nội quy.png (§11. Quản lý nội quy)
 *  - Tai-lieu-phat-trien/admin/chuyên đề mới.png (§18. Template báo cáo)
 *
 * Props:
 * @var string $name        Tên trường dữ liệu POST (VD: 'content', 'report_template')
 * @var string $label       Nhãn hiển thị khi bọc trong form/field_row
 * @var string $id          ID của control
 * @var string $value       Nội dung khởi tạo
 * @var string $placeholder Placeholder khi vùng soạn thảo trống (VD: 'Chỉnh sửa nội quy tại đây ...')
 * @var int    $min_height  Chiều cao tối thiểu vùng soạn thảo (px, mặc định: 360)
 * @var bool   $required    Bắt buộc nhập
 * @var bool   $readonly    Chế độ chỉ đọc
 * @var bool   $bare        True nếu render trực tiếp khung editor (không bọc cột trái/phải field_row)
 * @var string $hint        Gợi ý bên dưới
 * @var array  $row
 * @var array  $errors
 *
 * PHP 7.3 compatible.
 */

$name        = isset($name) ? (string) $name : 'content';
$label       = isset($label) ? (string) $label : '';
$placeholder = isset($placeholder) ? (string) $placeholder : 'Chỉnh sửa nội dung tại đây ...';
$minHeight   = isset($min_height) && (int) $min_height > 0 ? (int) $min_height : 360;
$required    = !empty($required);
$readonly    = !empty($readonly);
$hint        = isset($hint) ? (string) $hint : '';
$errors      = isset($errors) && is_array($errors) ? $errors : array();
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);
$editorId    = $id . '-editor';

if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $value = function_exists('lms_form_old')
        ? lms_form_old($postArr, $rowArr, $name, '')
        : (isset($rowArr[$name]) ? $rowArr[$name] : '');
}
$value = (string) $value;

// Chuẩn hoá nội dung hiển thị trong vùng contenteditable (giữ nguyên định dạng HTML an toàn hoặc chuyển xuống dòng nếu là text thuần)
$initialHtml = class_exists('\\Lms\\ClassRulesService')
    ? \Lms\ClassRulesService::formatForDisplay($value)
    : $value;

$fieldErr = isset($error) && $error !== ''
    ? $error
    : ($name !== '' && function_exists('lms_field_error') ? lms_field_error($errors, $name) : '');

ob_start();
?>
<div class="lms-rich-editor border rounded bg-white" data-rich-editor-root="<?= e($id) ?>">
    <?php if (!$readonly): ?>
        <div class="lms-rich-toolbar px-3 py-2 border-bottom bg-white" role="toolbar" aria-label="Thanh công cụ soạn thảo">
            <!-- Hàng 1: Font, Size, B, I, U, S, Màu chữ, Màu nền, X², X₂ -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <div style="width: 130px;">
                    <?php lms_component('form/select', array(
                        'name'    => '',
                        'id'      => $id . '-font',
                        'bare'    => true,
                        'base_class' => false, // control nhỏ gọn trong toolbar, không dùng style field LMS
                        'class'   => 'form-select form-select-sm border-0 fw-medium js-re-font',
                        'value'   => 'Arial, Helvetica, sans-serif',
                        'options' => array(
                            'Arial, Helvetica, sans-serif' => 'Sans Serif',
                            'Georgia, Times New Roman, serif' => 'Serif',
                            'Consolas, Courier New, monospace' => 'Monospace',
                        ),
                    )); ?>
                </div>

                <div style="width: 115px;">
                    <?php lms_component('form/select', array(
                        'name'    => '',
                        'id'      => $id . '-size',
                        'bare'    => true,
                        'base_class' => false,
                        'class'   => 'form-select form-select-sm border-0 fw-medium js-re-size',
                        'value'   => '3',
                        'options' => array(
                            '2' => 'Small',
                            '3' => 'Normal',
                            '5' => 'Large',
                            '6' => 'Huge',
                        ),
                    )); ?>
                </div>

                <div class="d-flex align-items-center gap-1 ms-1">
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-bold',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'bold', 'title' => 'In đậm (Ctrl+B)'),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-italic',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'italic', 'title' => 'In nghiêng (Ctrl+I)'),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-underline',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'underline', 'title' => 'Gạch chân (Ctrl+U)'),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-strikethrough',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'strikeThrough', 'title' => 'Gạch ngang chữ'),
                    )); ?>
                </div>

                <div class="d-flex align-items-center gap-1 ms-1">
                    <label class="lms-re-btn mb-0 d-inline-flex align-items-center justify-content-center position-relative" title="Màu chữ" style="cursor: pointer;">
                        <i class="fa-solid fa-font"></i>
                        <input type="color" class=" visually-hidden js-re-color" data-cmd="foreColor" value="#212529">
                    </label>
                    <label class="lms-re-btn mb-0 d-inline-flex align-items-center justify-content-center position-relative" title="Màu nền chữ" style="cursor: pointer;">
                        <i class="fa-solid fa-highlighter"></i>
                        <input type="color" class="visually-hidden js-re-color" data-cmd="hiliteColor" value="#fff3cd">
                    </label>
                </div>

                <div class="d-flex align-items-center gap-1 ms-1">
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-superscript',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'superscript', 'title' => 'Chỉ số trên (X²)'),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'variant' => 'light',
                        'size'    => 'sm',
                        'icon'    => 'fa-solid fa-subscript',
                        'class'   => 'lms-re-btn js-re-cmd',
                        'attrs'   => array('data-cmd' => 'subscript', 'title' => 'Chỉ số dưới (X₂)'),
                    )); ?>
                </div>
            </div>

            <!-- Hàng 2: H1, H2, Trích dẫn, Code, Danh sách số/chấm, Thụt lề, Căn lề, Link, Ảnh, Bảng, Xoá định dạng -->
            <div class="d-flex flex-wrap align-items-center gap-1">
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'label'   => 'H1',
                    'class'   => 'lms-re-btn fw-bold js-re-block',
                    'attrs'   => array('data-block' => 'H1', 'title' => 'Tiêu đề 1 (H1)'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'label'   => 'H2',
                    'class'   => 'lms-re-btn fw-bold js-re-block',
                    'attrs'   => array('data-block' => 'H2', 'title' => 'Tiêu đề 2 (H2)'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-quote-right',
                    'class'   => 'lms-re-btn js-re-block',
                    'attrs'   => array('data-block' => 'BLOCKQUOTE', 'title' => 'Trích dẫn'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-code',
                    'class'   => 'lms-re-btn js-re-block',
                    'attrs'   => array('data-block' => 'PRE', 'title' => 'Khối mã (Code)'),
                )); ?>

                <span class="vr mx-1 my-1"></span>

                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-list-ol',
                    'class'   => 'lms-re-btn js-re-cmd',
                    'attrs'   => array('data-cmd' => 'insertOrderedList', 'title' => 'Danh sách đánh số'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-list-ul',
                    'class'   => 'lms-re-btn js-re-cmd',
                    'attrs'   => array('data-cmd' => 'insertUnorderedList', 'title' => 'Danh sách gạch đầu dòng'),
                )); ?>

                <span class="vr mx-1 my-1"></span>

                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-outdent',
                    'class'   => 'lms-re-btn js-re-cmd',
                    'attrs'   => array('data-cmd' => 'outdent', 'title' => 'Giảm thụt lề'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-indent',
                    'class'   => 'lms-re-btn js-re-cmd',
                    'attrs'   => array('data-cmd' => 'indent', 'title' => 'Tăng thụt lề'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-align-left',
                    'class'   => 'lms-re-btn js-re-align',
                    'attrs'   => array('title' => 'Căn lề trái / giữa / phải'),
                )); ?>

                <span class="vr mx-1 my-1"></span>

                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-link',
                    'class'   => 'lms-re-btn js-re-link',
                    'attrs'   => array('title' => 'Chèn liên kết'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-regular fa-image',
                    'class'   => 'lms-re-btn js-re-image',
                    'attrs'   => array('title' => 'Chèn hình ảnh (URL)'),
                )); ?>
                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-table',
                    'class'   => 'lms-re-btn js-re-table',
                    'attrs'   => array('title' => 'Chèn bảng'),
                )); ?>

                <span class="vr mx-1 my-1"></span>

                <?php lms_component('button', array(
                    'type'    => 'button',
                    'variant' => 'light',
                    'size'    => 'sm',
                    'icon'    => 'fa-solid fa-remove-format',
                    'class'   => 'lms-re-btn js-re-clear',
                    'attrs'   => array('title' => 'Xoá định dạng'),
                )); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vùng soạn thảo / hiển thị Rich Text -->
    <div id="<?= e($editorId) ?>"
         class="lms-rich-content <?= $readonly ? 'p-4' : 'p-3' ?>"
         contenteditable="<?= $readonly ? 'false' : 'true' ?>"
         role="<?= $readonly ? 'region' : 'textbox' ?>"
         <?php if (!$readonly): ?>aria-multiline="true"<?php endif; ?>
         aria-label="<?= e($label !== '' ? $label : $placeholder) ?>"
         data-placeholder="<?= e($placeholder) ?>"
         style="min-height: <?= (int) $minHeight ?>px; outline: none;"><?= $initialHtml ?></div>

    <?php if (!$readonly): ?>
        <!-- Textarea ẩn chuẩn component form/textarea để đồng bộ dữ liệu POST -->
        <?php lms_component('form/textarea', array(
            'name'     => $name,
            'id'       => $id,
            'value'    => $value,
            'required' => $required,
            'bare'     => true,
            'base_class' => false,
            'class'    => 'd-none js-re-textarea',
        )); ?>
    <?php endif; ?>
</div>

<?php if (!$readonly): ?>
<script>
(function () {
    var initEditor = function () {
        var root = document.querySelector('[data-rich-editor-root="<?= e($id) ?>"]');
        if (!root || root.getAttribute('data-re-bound') === '1') {
            return;
        }
        root.setAttribute('data-re-bound', '1');

        var editor = document.getElementById('<?= e($editorId) ?>');
        var textarea = document.getElementById('<?= e($id) ?>');
        if (!editor || !textarea) {
            return;
        }

        var syncContent = function () {
            var html = editor.innerHTML.trim();
            var textOnly = (editor.textContent || editor.innerText || '').replace(/\u00a0/g, '').trim();
            var hasMedia = /<(img|table|hr)\b/i.test(html);
            if (!textOnly && !hasMedia) {
                editor.innerHTML = '';
                textarea.value = '';
            } else {
                textarea.value = html;
            }
        };

        // Đồng bộ ban đầu nếu editor rỗng để kích hoạt CSS :empty:before placeholder
        syncContent();

        editor.addEventListener('input', syncContent);
        editor.addEventListener('blur', syncContent);
        editor.addEventListener('keyup', syncContent);

        var parentForm = root.closest('form');
        if (parentForm) {
            parentForm.addEventListener('submit', syncContent);
        }

        // Lệnh định dạng cơ bản (B, I, U, S, list, indent, superscript, subscript)
        var cmdBtns = root.querySelectorAll('.js-re-cmd');
        for (var i = 0; i < cmdBtns.length; i++) {
            cmdBtns[i].addEventListener('click', function (e) {
                e.preventDefault();
                var cmd = this.getAttribute('data-cmd');
                if (cmd) {
                    editor.focus();
                    document.execCommand(cmd, false, null);
                    syncContent();
                }
            });
        }

        // Đổi font chữ
        var fontSel = root.querySelector('.js-re-font');
        if (fontSel) {
            fontSel.addEventListener('change', function () {
                editor.focus();
                document.execCommand('fontName', false, this.value);
                syncContent();
            });
        }

        // Đổi cỡ chữ
        var sizeSel = root.querySelector('.js-re-size');
        if (sizeSel) {
            sizeSel.addEventListener('change', function () {
                editor.focus();
                document.execCommand('fontSize', false, this.value);
                syncContent();
            });
        }

        // Màu chữ / màu nền
        var colorInputs = root.querySelectorAll('.js-re-color');
        for (var c = 0; c < colorInputs.length; c++) {
            colorInputs[c].addEventListener('input', function () {
                var cmd = this.getAttribute('data-cmd');
                if (cmd) {
                    editor.focus();
                    document.execCommand(cmd, false, this.value);
                    syncContent();
                }
            });
        }

        // Khối H1, H2, Blockquote, Code
        var blockBtns = root.querySelectorAll('.js-re-block');
        for (var b = 0; b < blockBtns.length; b++) {
            blockBtns[b].addEventListener('click', function (e) {
                e.preventDefault();
                var block = this.getAttribute('data-block');
                if (block) {
                    editor.focus();
                    document.execCommand('formatBlock', false, '<' + block + '>');
                    syncContent();
                }
            });
        }

        // Căn lề xoay vòng (Left -> Center -> Right)
        var alignStates = ['justifyLeft', 'justifyCenter', 'justifyRight'];
        var alignIdx = 0;
        var alignBtn = root.querySelector('.js-re-align');
        if (alignBtn) {
            alignBtn.addEventListener('click', function (e) {
                e.preventDefault();
                alignIdx = (alignIdx + 1) % alignStates.length;
                editor.focus();
                document.execCommand(alignStates[alignIdx], false, null);
                syncContent();
            });
        }

        // Chèn liên kết
        var linkBtn = root.querySelector('.js-re-link');
        if (linkBtn) {
            linkBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = window.prompt('Nhập đường dẫn liên kết (URL):', 'https://');
                if (url && url !== 'https://') {
                    editor.focus();
                    document.execCommand('createLink', false, url);
                    syncContent();
                }
            });
        }

        // Chèn ảnh
        var imgBtn = root.querySelector('.js-re-image');
        if (imgBtn) {
            imgBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var url = window.prompt('Nhập đường dẫn hình ảnh (URL):', 'https://');
                if (url && url !== 'https://') {
                    editor.focus();
                    document.execCommand('insertImage', false, url);
                    syncContent();
                }
            });
        }

        // Chèn bảng 2x2 mẫu
        var tableBtn = root.querySelector('.js-re-table');
        if (tableBtn) {
            tableBtn.addEventListener('click', function (e) {
                e.preventDefault();
                editor.focus();
                var tblHtml = '<table class="table table-bordered my-2"><tbody><tr><td>Ô 1</td><td>Ô 2</td></tr><tr><td>Ô 3</td><td>Ô 4</td></tr></tbody></table><p><br></p>';
                document.execCommand('insertHTML', false, tblHtml);
                syncContent();
            });
        }

        // Xoá định dạng
        var clearBtn = root.querySelector('.js-re-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                editor.focus();
                document.execCommand('removeFormat', false, null);
                document.execCommand('formatBlock', false, '<P>');
                syncContent();
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
})();
</script>
<?php endif; ?>
<?php
$controlHtml = ob_get_clean();

if (!empty($bare) || !empty($no_wrap) || !empty($no_row)) {
    echo $controlHtml;
    if ($hint !== '') {
        echo '<div class="lms-tf-hint mt-1">' . htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    echo $fieldErr;
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
