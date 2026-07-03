<?php
$name = $name ?? '';
$value = $value ?? '';
$mode = $mode ?? 'htmlmixed'; // htmlmixed, css, javascript
$theme = $theme ?? 'dracula';
$rows = $rows ?? 5;
$id = 'code_editor_' . uniqid();

// Chỉ tải thư viện CDN 1 lần duy nhất cho dù component được gọi nhiều lần
if (empty($GLOBALS['codemirror_loaded'])):
    $GLOBALS['codemirror_loaded'] = true;
?>
<!-- CodeMirror CSS & Theme -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/<?= $theme ?>.min.css">

<!-- CodeMirror JS & Modes -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/htmlmixed/htmlmixed.min.js"></script>

<!-- Addons -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/closebrackets.min.js"></script>
<?php endif; ?>

<style>
    .CodeMirror {
        height: <?= $rows * 25 ?>px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        font-family: Consolas, Monaco, "Andale Mono", monospace;
        font-size: 14px;
    }
</style>

<textarea name="<?= $name ?>" id="<?= $id ?>" style="display: none;"><?= htmlspecialchars($value) ?></textarea>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var editor = CodeMirror.fromTextArea(document.getElementById("<?= $id ?>"), {
            mode: "<?= $mode ?>",
            theme: "<?= $theme ?>",
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            indentUnit: 4,
            indentWithTabs: false,
            lineWrapping: true
        });
        
        // Cập nhật lại giá trị textarea ẩn khi gõ phím để form submit lấy được dữ liệu
        editor.on('change', function() {
            document.getElementById("<?= $id ?>").value = editor.getValue();
        });
    });
</script>
