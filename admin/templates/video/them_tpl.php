<?php
/**
 * Video — Thêm / Sửa.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$isEdit    = isset($_GET['id']);
$editId    = $isEdit ? (int) $_GET['id'] : 0;
$MODULE    = 'video';
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
            "select * from #_video where id_code = " . $editId . " and lang = '" . $code . "'"
        ) ?: [];
    } else {
        $rowByLang[$code] = [];
    }
}

$firstLang = $langs[0]['code'] ?? '';
$main      = $rowByLang[$firstLang] ?? [];
$maVideo   = $main['ma_video'] ?? '';
?>

<?php component('page_header', [
    'title'      => 'Video',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Video', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">
        <form name="frm" method="post" action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data" data-warn-unsaved>
            <div class="row g-3">

                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>
                        <div class="card-body">
                            <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'video']); ?>
                            <div class="tab-content">

                                <?php foreach ($langs as $i => $lang):
                                    $code = $lang['code'];
                                    $row  = $rowByLang[$code] ?? [];
                                    $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                    $onKeyUpTen = "addText(this,'#alias_{$code}','#title_{$code}',{onlyIfEmpty:true});";
                                    ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="video-<?= e($code) ?>" role="tabpanel">
                                        <?php if ($isEdit): ?>
                                            <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                        <?php endif; ?>

                                        <?php component('form/input', [
                                            'label'       => 'Tên video' . $sfx,
                                            'name'        => 'ten[]',
                                            'id'          => 'ten_' . $code,
                                            'value'       => $row['ten'] ?? '',
                                            'placeholder' => 'Nhập tên video',
                                            'required'    => $i === 0,
                                            'attr'        => ['onkeyup' => $onKeyUpTen],
                                        ]); ?>

                                        <?php component('form/input', [
                                            'label'       => 'Đường dẫn' . $sfx,
                                            'name'        => 'alias[]',
                                            'id'          => 'alias_' . $code,
                                            'value'       => $row['alias'] ?? '',
                                            'placeholder' => 'Nhập đường dẫn',
                                        ]); ?>

                                        <?php component('form/editor', [
                                            'label'  => 'Nội dung' . $sfx,
                                            'name'   => 'noi_dung[]',
                                            'id'     => 'noi_dung_' . $code,
                                            'value'  => $row['noi_dung'] ?? '',
                                            'height' => '280px',
                                        ]); ?>

                                        <hr class="my-4">

                                        <?php component('form/seo_panel', [
                                            'lang'     => $code,
                                            'suffix'   => $sfx,
                                            'title'    => $row['title']    ?? '',
                                            'des'      => $row['des']      ?? '',
                                            'keyword'  => $row['keyword']  ?? '',
                                            'seo_head' => $row['seo_head'] ?? '',
                                            'seo_body' => $row['seo_body'] ?? '',
                                            'base_url' => defined('URLPATH') ? URLPATH : '',
                                            'name_id'  => 'ten_' . $code,
                                            'alias_id' => 'alias_' . $code,
                                        ]); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php component('card_close', ['body' => 'none']); ?>
                </div>

                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>
                        <?php component('form/image_picker', [
                            'label'  => 'Hình ảnh',
                            'name'   => 'hinh_anh',
                            'value'  => $main['hinh_anh'] ?? '',
                            'table'  => '_video',
                            'row_id' => $isEdit ? (string) $editId : '',
                            'height' => 150,
                        ]); ?>

                        <?php component('form/file_picker', [
                            'label'       => 'Video (file)',
                            'name'        => 'video',
                            'value'       => $main['video'] ?? '',
                            'table'       => '_video',
                            'row_id'      => $isEdit ? (string) $editId : '',
                            'type'        => 3,
                            'preview'     => 'video',
                            'height'      => 150,
                            'choose_text' => 'Chọn video',
                            'hint'        => 'Định dạng: mp4, webm…',
                        ]); ?>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Danh mục</label>
                            <select name="id_loai" class="form-select form-select-sm select2">
                                <option value="0">-- Chọn danh mục --</option>
                                <?= $loai ?? '' ?>
                            </select>
                        </div>

                        <?php component('form/youtube_input', [
                            'label'       => 'Mã YouTube',
                            'name'        => 'ma_video',
                            'id'          => 'ma_video',
                            'value'       => $maVideo,
                            'placeholder' => 'vd: dQw4w9WgXcQ',
                            'hint'        => 'Dán mã video hoặc URL youtube.com/watch?v=…',
                        ]); ?>

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
