<?php
/**
 * Component: form/editor
 * Textarea + khởi tạo CKEditor 4 với cấu hình filemanager dùng chung.
 *
 * Trước đây mỗi view tự copy-paste khối CKEDITOR.replace(...) kèm 3 dòng
 * filebrowser*Url. Component này gom về 1 chỗ.
 *
 * Props:
 * @var string $label
 * @var string $name
 * @var string $id      BẮT BUỘC và phải duy nhất trên trang
 * @var mixed  $value
 * @var array|null $col  null = layout dọc (mặc định)
 * @var string $height  vd '250px'
 * @var string $toolbar '' | 'Basic'
 * @var string $hint
 */
$label   = $label   ?? '';
$name    = $name    ?? '';
$id      = $id      ?? '';
$value   = $value   ?? '';
$col     = $col     ?? null;
$height  = $height  ?? '300px';
$toolbar = $toolbar ?? '';
$hint    = $hint    ?? '';

$control = '<textarea class="form-control form-control-sm" name="' . e($name) . '" id="' . e($id) . '" rows="5">'
    . e($value) . '</textarea>';

component('form/field_wrap', [
    'label' => $label,
    'for'   => $id,
    'col'   => $col,
    'hint'  => $hint,
    'slot'  => $control,
]);
?>
<script>
(function () {
    if (typeof CKEDITOR === 'undefined') { return; }

    var id = <?= json_encode($id) ?>;

    // Tránh khởi tạo 2 lần nếu component bị render trùng id
    if (CKEDITOR.instances[id]) { return; }

    var fmOpts = <?= function_exists('fm_ckeditor_options') ? json_encode(fm_ckeditor_options(), JSON_UNESCAPED_SLASHES) : '{' . json_encode('filebrowserBrowseUrl') . ':"/admin/ckfinder/ckfinder.html?type=Files",' . json_encode('filebrowserImageBrowseUrl') . ':"/admin/ckfinder/ckfinder.html?type=Images",' . json_encode('filebrowserUploadUrl') . ':"/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files",' . json_encode('filebrowserImageUploadUrl') . ':"/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images"}' ?>;
    var cfg = {
        height: <?= json_encode($height) ?><?= $toolbar !== '' ? ",\n        toolbar: " . json_encode($toolbar) : '' ?>
    };
    for (var k in fmOpts) { if (fmOpts.hasOwnProperty(k)) { cfg[k] = fmOpts[k]; } }
    CKEDITOR.replace(id, cfg);
})();
</script>