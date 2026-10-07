<?php
/**
 * Bài viết — Thêm / Sửa.
 *
 * Bộ component admin/templates/components/ (Bootstrap 5 + AdminLTE 4).
 * Giữ tên field y hệt bản cũ vì controller sources/bai-viet.php đọc
 * $_POST theo đúng tên này.
 *
 * Cấu trúc:
 *  - Cột chính 9/12: tab ngôn ngữ, nội dung chính, khối SEO
 *  - Cột phụ  3/12: thông tin chung (danh mục, ảnh, ngày đăng, STT, cờ hệ thống)
 *
 * Khối SEO có:
 *  - xem trước Google (SERP) cập nhật ngay khi gõ
 *  - đo độ dài Title/Description theo ngưỡng khuyến nghị
 *  - chip từ khóa
 *  - HTML header/footer thu gọn
 *
 * Tham số thứ 4 của addText() là cờ onlyIfEmpty: bảo vệ alias + Title khỏi
 * bị ghi đè nếu đã có sẵn.
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$isEdit    = isset($_GET['id']);
$editId    = $isEdit ? (int) $_GET['id'] : 0;
$MODULE    = 'bai-viet';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$actionUrl = admin_url(array_filter([
    'p'    => $_REQUEST['p'] ?? $MODULE,
    'a'    => 'save',
    'id'   => $_REQUEST['id'] ?? '',
    'page' => $_REQUEST['page'] ?? '',
]));

/**
 * Mỗi ngôn ngữ 1 dòng, nạp trước để render không phải query trong vòng lặp.
 */
$rowByLang = [];
if ($isEdit) {
    foreach ($langs as $lang) {
        $rowByLang[$lang['code']] = $d->safeFetchOne(
            'select * from #_tintuc where id_code = ? and lang = ?',
            [$editId, $lang['code']]
        );
    }
} else {
    foreach ($langs as $lang) {
        $rowByLang[$lang['code']] = [];
    }
}

$firstLang = $langs[0]['code'] ?? '';
$main      = $rowByLang[$firstLang] ?? [];

// Danh sách tag theo ngôn ngữ (giữ logic cũ: query theo #_tags)
$tagsByLang = [];
foreach ($langs as $lang) {
    $tagsByLang[$lang['code']] = $d->o_fet(
        "select ten from #_tags where lang = '" . $lang['code'] . "' order by ten"
    );
}

/*
 * Chuẩn bị giá trị cho datetime-local input (YYYY-MM-DDTHH:MM).
 * Cột `ngay_dang` lưu unix timestamp. Vì <input type="datetime-local"> yêu cầu
 * đúng định dạng, nếu rỗng thì bỏ trống -> khi submit, controller sẽ giữ
 * giá trị cũ (khi sửa) hoặc dùng time() (khi tạo).
 */
$ngayDangLocal = !empty($main['ngay_dang'])
    ? date('Y-m-d\TH:i', (int) $main['ngay_dang'])
    : '';
?>

<?php component('page_header', [
    'title'      => 'Bài viết',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Bài viết', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <form name="frm" method="post" action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data" data-warn-unsaved>

            <div class="row g-3">

                <!-- ============ Cột chính ============ -->
                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>

                        <div class="card-body">
                            <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'post']); ?>

                            <div class="tab-content">
                                <?php foreach ($langs as $i => $lang):
                                    $code = $lang['code'];
                                    $row  = $rowByLang[$code] ?? [];
                                    $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                    ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="post-<?= e($code) ?>" role="tabpanel">

                                        <?php if ($isEdit): ?>
                                            <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                        <?php endif; ?>

                                        <?php
                                        /*
                                         * Tên bài viết -> tự điền alias + Title SEO.
                                         * slug chỉ tự động điền khi THÊM MỚI; khi SỬA thì slug
                                         * giữ nguyên để không gãy URL đã index. Cờ
                                         * {onlyIfEmpty:true} bảo vệ các ô đã có sẵn hoặc đã
                                         * bị sửa tay (data('autofill')).
                                         */
                                         $onKeyUpTen = "addText(this,'#alias_{$code}','#title_{$code}',{onlyIfEmpty:true});";
                                         if (!$isEdit) {
                                             $onKeyUpTen .= " addText(this,'#slug_{$code}','',{onlyIfEmpty:true});";
                                         }

                                        component('form/input', [
                                            'label'       => 'Tên bài viết' . $sfx,
                                            'name'        => 'ten[]',
                                            'id'          => 'ten_' . $code,
                                            'value'       => $row['ten'] ?? '',
                                            'placeholder' => 'Nhập tên bài viết',
                                            'required'    => $i === 0,
                                            'attr'        => ['onkeyup' => $onKeyUpTen],
                                        ]);
                                        
                                         component('form/input', [
                                            'label'       => 'Đường dẫn' . $sfx,
                                            'name'        => 'alias[]',
                                            'id'          => 'alias_' . $code,
                                            'value'       => $row['alias'] ?? '',
                                            'placeholder' => 'Nhập đường dẫn',
                                            'hint'        => 'Chỉ dùng chữ thường, số và dấu gạch ngang. Để trống sẽ tự sinh từ tên.',
                                            'attr'        => ['onkeyup' => "addText(this,'#alias_{$code}')"],
                                        ]);

                                        /*
                                         * Tiền tố đường dẫn (slug): khi THÊM MỚI sẽ tự động
                                         * điền từ tên; khi SỬA thì nhập tay. Khi nhập, vẫn
                                         * format lại để đảm bảo chỉ chứa chữ thường, số và
                                         * dấu gạch ngang.
                                         */
                                         component('form/input', [
                                            'label'       => 'Tiền tố đường dẫn' . $sfx,
                                            'name'        => 'slug[]',
                                            'id'          => 'slug_' . $code,
                                            'value'       => $row['slug'] ?? '',
                                            'placeholder' => 'vd: bai-viet',
                                            'suffix'      => '/',
                                            'hint'        => 'Phần đứng trước đường dẫn. Bỏ trống nếu không dùng.',
                                            'attr'        => ['onkeyup' => "addText(this,'#slug_{$code}')"],
                                        ]);

                                        ?>

                                        <?php
                                        component('form/editor', [
                                            'label'   => 'Mô tả' . $sfx,
                                            'name'    => 'mo_ta[]',
                                            'id'      => 'mo_ta_' . $code,
                                            'value'   => $row['mo_ta'] ?? '',
                                            'height'  => '200px',
                                            'toolbar' => 'Basic',
                                        ]);

                                        component('form/editor', [
                                            'label'  => 'Nội dung' . $sfx,
                                            'name'   => 'noi_dung[]',
                                            'id'     => 'noi_dung_' . $code,
                                            'value'  => $row['noi_dung'] ?? '',
                                            'height' => '500px',
                                        ]);
                                        ?>

                                        <hr class="my-4">

                                        <?php
                                        component('form/seo_panel', [
                                            'lang'     => $code,
                                            'suffix'   => $sfx,
                                            'title'    => $row['title']     ?? '',
                                            'des'      => $row['des']       ?? '',
                                            'keyword'  => $row['keyword']   ?? '',
                                            'seo_head' => $row['seo_head']  ?? '',
                                            'seo_body' => $row['seo_body']  ?? '',
                                            'base_url' => defined('URLPATH') ? URLPATH : '',
                                            'name_id'  => 'ten_' . $code,
                                            'slug_id'  => 'slug_' . $code,
                                            'alias_id' => 'alias_' . $code,
                                        ]);
                                        ?>

                                        <hr class="my-4">

                                        <div class="row mb-3">
                                            <label class="col-sm-2 col-form-label fw-medium">
                                                Từ khoá<?= e($sfx) ?>
                                            </label>
                                            <div class="col-sm-10">
                                                <input type="text"
                                                       class="form-control form-control-sm"
                                                       id="tags_<?= e($code) ?>"
                                                       name="tags_hienthi[]"
                                                       value="<?= e($row['tags_hienthi'] ?? '') ?>"
                                                       placeholder="Các từ khoá, cách nhau bằng dấu phẩy">

                                                <?php
                                                $chips = $tagsByLang[$code] ?? [];
                                                if ($chips):
                                                ?>
                                                <div class="mt-2 d-flex flex-wrap gap-1">
                                                    <?php foreach ($chips as $tg):
                                                        $dem = $d->num_rows(
                                                            "select id from #_tintuc where tags_hienthi like '%"
                                                            . addslashes($tg['ten']) . "%'"
                                                        );
                                                    ?>
                                                        <a href="javascript:void(0)"
                                                           class="seo-tag"
                                                           onclick="chen_tags('<?= e($tg['ten']) ?>','<?= e($code) ?>',$(this))"
                                                           title="Bài viết dùng tag này: <?= (int) $dem ?>">
                                                            <?= e($tg['ten']) ?> (<?= (int) $dem ?>)
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php component('card_close', ['body' => 'none']); ?>
                </div>

                <!-- ============ Cột phụ ============ -->
                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>

                        <?php
                        component('form/image_picker', [
                            'label'  => 'Hình ảnh',
                            'name'   => 'hinh_anh',
                            'value'  => $main['hinh_anh'] ?? '',
                            'table'  => '_tintuc',
                            'row_id' => $isEdit ? (string) $editId : '',
                            'height' => 150,
                        ]);
                        ?>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Danh mục</label>
                            <select name="id_loai" class="form-control form-control-sm select2">
                                <option value="0">-- Chọn làm mục cha --</option>
                                <?= $loai ?? '' ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="ngay_dang">Ngày đăng</label>
                            <input type="datetime-local"
                                   class="form-control form-control-sm"
                                   name="ngay_dang" id="ngay_dang"
                                   value="<?= e($ngayDangLocal) ?>">
                            <div class="form-text">
                                <?php if ($isEdit): ?>
                                    Hiện tại: <?= !empty($main['ngay_dang']) ? e(date('d/m/Y H:i', (int) $main['ngay_dang'])) : '—' ?>.
                                    Bỏ trống nếu muốn giữ nguyên.
                                <?php else: ?>
                                    Để trống sẽ lấy giờ hiện tại.
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="so_thu_tu">Số thứ tự</label>
                            <input type="number" min="0" class="form-control form-control-sm"
                                   name="so_thu_tu" id="so_thu_tu"
                                   value="<?= (int) ($main['so_thu_tu'] ?? 0) ?>">
                        </div>

                        <hr>

                        <?php
                        component('form/checkbox', [
                            'label'   => 'Hiển thị',
                            'name'    => 'hien_thi',
                            'checked' => $isEdit ? (($main['hien_thi'] ?? 0) == 1) : true,
                        ]);

                        component('form/checkbox', [
                            'label'   => 'Nofollow',
                            'name'    => 'nofollow',
                            'checked' => ($main['nofollow'] ?? 0) == 1,
                            'hint'    => 'Không cho công cụ tìm kiếm đi theo các liên kết',
                        ]);

                        component('form/checkbox', [
                            'label'   => 'Noindex',
                            'name'    => 'noindex',
                            'checked' => ($main['noindex'] ?? 0) == 1,
                            'hint'    => 'Không cho công cụ tìm kiếm lập chỉ mục trang này',
                        ]);
                        ?>

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

<script>
/*
 * Trước khi submit form, format lại alias và slug để đảm bảo
 * chỉ chứa chữ thường, số và dấu gạch ngang (phòng trường hợp
 * người dùng dán nội dung hoặc JS onkeyup chưa kịp xử lý).
 */
document.querySelector('form[name="frm"]').addEventListener('submit', function () {
    document.querySelectorAll('input[name^="alias["], input[name^="slug["]').forEach(function (input) {
        input.value = locdau(input.value);
    });
});
</script>
