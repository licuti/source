<?php
/**
 * Upload file — Thêm / Sửa.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$isEdit    = isset($_GET['id']);
$editId    = $isEdit ? (int) $_GET['id'] : 0;
$MODULE    = 'upload-file';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$actionUrl = admin_url(array_filter([
    'p'    => $_REQUEST['p'] ?? $MODULE,
    'a'    => 'save',
    'id'   => $_REQUEST['id'] ?? '',
    'page' => $_REQUEST['page'] ?? '',
]));

$rowByLang = [];
foreach ($langs as $lang) {
    $code = $lang['code'];
    if ($isEdit) {
        $rowByLang[$code] = $d->simple_fetch(
            "select * from #_files where id_code = " . $editId . " and lang = '" . $code . "'"
        ) ?: [];
    } else {
        $rowByLang[$code] = [];
    }
}
$firstLang = $langs[0]['code'] ?? '';
$main      = $rowByLang[$firstLang] ?? [];

$fileName = trim((string) ($main['file'] ?? ''));
$linkKhac = trim((string) ($main['link_khac'] ?? ''));
$loaiFile = $main['loai_file'] ?? 'pdf';
// File từ FileManager: media/files; file cũ có thể ở uploads/files hoặc media/images
$fileValue = $fileName;
?>

<?php component('page_header', [
    'title'      => 'Upload file',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Upload file', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form name="frm" method="post" action="<?= e($actionUrl) ?>" data-warn-unsaved>
            <div class="row g-3">
                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>
                        <div class="card-body">
                            <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'ufile']); ?>
                            <div class="tab-content">
                                <?php foreach ($langs as $i => $lang):
                                    $code = $lang['code'];
                                    $row  = $rowByLang[$code] ?? [];
                                    $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                    ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="ufile-<?= e($code) ?>" role="tabpanel">
                                        <?php if ($isEdit): ?>
                                            <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                        <?php endif; ?>

                                        <?php component('form/input', [
                                            'label'       => 'Tên file' . $sfx,
                                            'name'        => 'ten[]',
                                            'id'          => 'ten_' . $code,
                                            'value'       => $row['ten'] ?? '',
                                            'placeholder' => 'Nhập tên file',
                                            'required'    => $i === 0,
                                        ]); ?>
                                        <input type="hidden" name="alias[]" id="alias_<?= e($code) ?>"
                                               value="<?= e($row['alias'] ?? '') ?>">

                                        <?php component('form/textarea', [
                                            'label'       => 'Mô tả' . $sfx,
                                            'name'        => 'mo_ta[]',
                                            'id'          => 'mo_ta_' . $code,
                                            'value'       => $row['mo_ta'] ?? '',
                                            'rows'        => 4,
                                            'placeholder' => 'Mô tả ngắn',
                                        ]); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php component('card_close', ['body' => 'none']); ?>
                </div>
                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>

                        <?php component('form/file_picker', [
                            'label'       => 'Tệp tin',
                            'name'        => 'file',
                            'value'       => $fileValue,
                            'table'       => '_files',
                            'row_id'      => $isEdit ? (string) $editId : '',
                            'type'        => 2,
                            'preview'     => 'file',
                            'choose_text' => 'Chọn file (PDF, Word, Excel…)',
                            'hint'        => 'Chọn từ File Manager (media/files). Có thể tái sử dụng file đã tải lên.',
                        ]); ?>

                        <?php component('form/input', [
                            'label'       => 'Đường dẫn file khác',
                            'name'        => 'link_khac',
                            'id'          => 'link_khac',
                            'value'       => $linkKhac,
                            'placeholder' => 'https://... hoặc đường dẫn ngoài',
                            'hint'        => 'Dùng khi không chọn file từ File Manager',
                        ]); ?>

                        <?php component('form/select', [
                            'label'   => 'Loại file (khi dùng link khác)',
                            'name'    => 'loai_file',
                            'value'   => $loaiFile,
                            'options' => [
                                'pdf'  => 'PDF',
                                'docx' => 'WORD',
                                'xlsx' => 'EXCEL',
                                'mp4'  => 'Video',
                                'mp3'  => 'Âm thanh',
                            ],
                        ]); ?>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Danh mục</label>
                            <select name="id_loai" class="form-select form-select-sm select2">
                                <option value="0">-- Chọn danh mục --</option>
                                <?= $loai ?? '' ?>
                            </select>
                        </div>

                        <?php component('form/input', [
                            'label' => 'Số thứ tự',
                            'name'  => 'so_thu_tu',
                            'id'    => 'so_thu_tu',
                            'type'  => 'number',
                            'value' => (int) ($main['so_thu_tu'] ?? 0),
                        ]); ?>

                        <hr>

                        <?php component('form/checkbox', [
                            'label'   => 'Hiển thị',
                            'name'    => 'hien_thi',
                            'checked' => $isEdit ? (($main['hien_thi'] ?? 0) == 1) : true,
                        ]); ?>
                    <?php component('card_close'); ?>

                    <?php component('form/actions_bar', [
                        'back_url' => admin_url(['p' => $MODULE, 'a' => 'man']),
                        'can_edit' => $canEdit,
                    ]); ?>
                </div>
            </div>
        </form>
    </div>
</div>