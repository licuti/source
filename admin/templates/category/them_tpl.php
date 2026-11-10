<?php
/**
 * Danh mục — Thêm / Sửa.
 *
 * Bố cục theo tab: Thông tin | Nội dung | SEO.
 *
 * ⚠️ Tên các trường phải khớp với sources/category.php:
 *  - Mảng theo ngôn ngữ (đúng thứ tự get_json('lang')): ten[], slug[], alias[],
 *    url[], mo_ta[], noi_dung[], title[], keyword[], des[], seo_head[],
 *    seo_body[], id_row[]
 *  - Giá trị đơn: hinh_anh, banner, id_loai, module, so_thu_tu,
 *    hien_thi, nofollow, noindex
 *  - capnhat: 'capnhat' (lưu & thoát) | 'capnhat_sua' (lưu & ở lại)
 */

$canEdit  = $d->checkPermission_edit($id_module) == 1;
$isEdit   = isset($_GET['id']);
$editId   = $isEdit ? (int) $_GET['id'] : 0;
$MODULE   = 'category';
$langs    = get_json('lang');
$multiLang = count($langs) > 1;

// URL submit: giữ đúng các tham số controller đang đọc
$actionUrl = admin_url(array_filter([
    'p'        => $_REQUEST['p'] ?? $MODULE,
    'a'        => 'save',
    'id'       => $_REQUEST['id']       ?? '',
    'page'     => $_REQUEST['page']     ?? '',
    'loaitin'  => $_GET['loaitin']      ?? '',
    'template' => $_GET['template']     ?? '',
]));

/**
 * Nạp trước dữ liệu từng ngôn ngữ để phần render bên dưới chỉ lo hiển thị.
 */
$rowByLang = [];
foreach ($langs as $lang) {
    $rowByLang[$lang['code']] = $isEdit
        ? $d->safeFetchOne(
            "select * from #_category where id_code = ? and lang = ?",
            [$editId, $lang['code']]
        )
        : [];
}

// "Thông tin chung" lấy theo ngôn ngữ đầu tiên
$firstLang = $langs[0]['code'] ?? '';
$main      = $rowByLang[$firstLang] ?? [];
$moduleOptions = [];
foreach (($module ?? []) as $m) {
    $moduleOptions[$m['id']] = $m['title'];
}
?>

<?php component('page_header', [
    'title'      => 'Danh mục',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Danh mục', 'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <form name="frm" method="post" action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data" data-warn-unsaved>

            <div class="row g-3">

                <!-- Cột chính -->
                <div class="col-lg-9">
                    <?php component('card_open', ['body' => 'none']); ?>

                        <div class="card-body">
                            <?php // Tabs ngôn ngữ (chỉ hiện khi có nhiều ngôn ngữ)
                            component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'cat']); ?>

                            <div class="tab-content">
                                <?php foreach ($langs as $i => $lang):
                                    $code = $lang['code'];
                                    $row  = $rowByLang[$code] ?? [];
                                    $sfx  = $multiLang ? ' (' . $code . ')' : '';
                                    ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="cat-<?= e($code) ?>" role="tabpanel">

                                        <?php if ($isEdit): ?>
                                            <input type="hidden" name="id_row[]" value="<?= e($row['id'] ?? '') ?>">
                                        <?php endif; ?>

                                        <?php
                                        /*
                                         * Tên danh mục -> tự điền Đường dẫn + Title SEO.
                                         *
                                         * THÊM MỚI: sinh cả alias lẫn title.
                                         * SỬA     : KHÔNG đụng alias (đổi alias là gãy link đã
                                         *           được Google index).
                                         *
                                         * Tham số thứ 4 {onlyIfEmpty:true} là phần vừa sửa lỗi:
                                         * trước đây khi SỬA, gõ vào ô Tên vẫn ghi đè Title SEO,
                                         * xoá mất tiêu đề người dùng đã tự viết mà không báo gì.
                                         * Nay chỉ tự điền khi ô Title đang trống (hoặc nội dung
                                         * vẫn đúng bằng giá trị do chính JS điền trước đó).
                                         *
                                         * Dùng cờ này cho CẢ 2 chế độ: thêm mới mà người dùng đã
                                         * chủ động gõ Title riêng thì cũng không nên bị đè.
                                         */
                                        $onKeyUp = $isEdit
                                            ? "addText(this,'','#title_{$code}',{onlyIfEmpty:true})"
                                            : "addText(this,'#alias_{$code}','#title_{$code}',{onlyIfEmpty:true})";

                                        component('form/input', [
                                            'label'       => 'Tên danh mục' . $sfx,
                                            'name'        => 'ten[]',
                                            'id'          => 'ten_' . $code,
                                            'value'       => $row['ten'] ?? '',
                                            'placeholder' => 'Nhập tên danh mục',
                                            'required'    => $i === 0,
                                            'attr'        => ['onkeyup' => $onKeyUp],
                                        ]);

                                        component('form/input', [
                                            'label'       => 'Tiền tố đường dẫn' . $sfx,
                                            'name'        => 'slug[]',
                                            'id'          => 'slug_' . $code,
                                            'value'       => $row['slug'] ?? '',
                                            'placeholder' => 'vd: danh-muc',
                                            'suffix'      => '/',
                                            'attr'        => ['onkeyup' => "addText(this,'#slug_{$code}')"],
                                            'hint'        => 'Phần đứng trước đường dẫn. Để trống nếu không dùng.',
                                        ]);

                                        component('form/input', [
                                            'label'       => 'Đường dẫn' . $sfx,
                                            'name'        => 'alias[]',
                                            'id'          => 'alias_' . $code,
                                            'value'       => $row['alias'] ?? '',
                                            'placeholder' => 'Nhập đường dẫn',
                                            'attr'        => ['onkeyup' => "addText(this,'#alias_{$code}')"],
                                            'hint'        => 'Chỉ dùng chữ thường, số và dấu gạch ngang.',
                                        ]);

                                        component('form/input', [
                                            'label'       => 'Đường dẫn khác' . $sfx,
                                            'name'        => 'url[]',
                                            'id'          => 'url_' . $code,
                                            'value'       => $row['url'] ?? '',
                                            'placeholder' => 'Nhập url tùy chỉnh (nếu có)',
                                        ]);

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
                                            'height' => '400px',
                                        ]);
                                        ?>

                                        <hr class="my-4">

                                        <?php
                                        /*
                                         * Khối SEO gom vào 1 component riêng, có xem trước
                                         * kết quả Google (SERP) cập nhật ngay khi gõ.
                                         *
                                         * Tên field và id vẫn giữ nguyên như cũ
                                         * (title[]/des[]/keyword[]/seo_head[]/seo_body[] và
                                         * id "title_{code}") nên controller cùng hàm addText()
                                         * trong admin.js không bị ảnh hưởng.
                                         */
                                        component('form/seo_panel', [
                                            'lang'     => $code,
                                            'suffix'   => $sfx,
                                            'title'    => $row['title']    ?? '',
                                            'des'      => $row['des']      ?? '',
                                            'keyword'  => $row['keyword']  ?? '',
                                            'seo_head' => $row['seo_head'] ?? '',
                                            'seo_body' => $row['seo_body'] ?? '',
                                            'base_url' => defined('URLPATH') ? URLPATH : '',
                                            'name_id'  => 'ten_' . $code,
                                            'slug_id'  => 'slug_' . $code,
                                            'alias_id' => 'alias_' . $code,
                                        ]);
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    <?php component('card_close', ['body' => 'none']); ?>
                </div>

                <!-- Cột phụ -->
                <div class="col-lg-3">
                    <?php component('card_open', ['title' => 'Thông tin chung']); ?>

                        <?php
                        component('form/image_picker', [
                            'label'  => 'Hình ảnh',
                            'name'   => 'hinh_anh',
                            'value'  => $main['hinh_anh'] ?? '',
                            'table'  => '_category',
                            'row_id' => $isEdit ? (string) $editId : '',
                            'height' => 150,
                        ]);

                        component('form/image_picker', [
                            'label'  => 'Banner',
                            'name'   => 'banner',
                            'value'  => $main['banner'] ?? '',
                            'table'  => '_category',
                            'row_id' => $isEdit ? (string) $editId : '',
                            'height' => 110,
                        ]);
                        ?>

                        <?php
                        // Danh mục cha: $loaibv là HTML <option> do $d->array_category() sinh ra
                        component('form/select', [
                            'label'        => 'Danh mục cha',
                            'name'         => 'id_loai',
                            'id'           => 'id_loai',
                            'class'        => 'select2',
                            'options'      => ['0' => '-- Chọn làm mục cha --'],
                            'options_html' => $loaibv ?? '',
                        ]);

                        component('form/select', [
                            'label'   => 'Module',
                            'name'    => 'module',
                            'id'      => 'cat_module',
                            'options' => $moduleOptions,
                            'value'   => $items[0]['module'] ?? '',
                        ]);

                        component('form/input', [
                            'label' => 'Số thứ tự',
                            'name'  => 'so_thu_tu',
                            'id'    => 'so_thu_tu',
                            'type'  => 'number',
                            'value' => $main['so_thu_tu'] ?? 0,
                        ]);
                        ?>

                        <hr>

                        <?php
                        /*
                         * Khi THÊM MỚI: mặc định bật "Hiển thị".
                         * Khi SỬA: lấy đúng trạng thái đang lưu.
                         */
                        component('form/checkbox', [
                            'label'   => 'Hiển thị',
                            'name'    => 'hien_thi',
                            'checked' => $isEdit ? (($items[0]['hien_thi'] ?? 0) == 1) : true,
                        ]);

                        component('form/checkbox', [
                            'label'   => 'Nofollow',
                            'name'    => 'nofollow',
                            'checked' => ($main['nofollow'] ?? 0) == 1,
                            'hint'    => 'Không cho công cụ tìm kiếm đi theo các link trong trang',
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
                        'back_url' => admin_url(['p' => $MODULE, 'a' => 'man', 'page' => $_REQUEST['page'] ?? '']),
                        'can_edit' => $canEdit,
                    ]); ?>
                </div>
            </div>
        </form>
    </div>
</div>