<?php
/**
 * Sản phẩm — Thêm / Sửa.
 *
 * Bộ component admin/templates/components/ (Bootstrap 5 + AdminLTE 4).
 *
 * ⚠️ RÀNG BUỘC TÊN FIELD (đã chốt với controller):
 *  Form sinh ra $_POST giống bản cũ để admin/sources/san-pham.php
 *  (hàm luudulieu) vẫn nhận dữ liệu đúng. Đổi name=... phải sửa
 *  controller kèm theo.
 *
 * Per-language (mảng `[]`):
 *  ten[] alias[] slug[] dvt[] mo_ta[] noi_dung[] noi_dung_1[]
 *  noi_dung_2[] title[] des[] keyword[] seo_head[] seo_body[]
 *  id_row[] | nameinfo_<lang>[] | detailinfo_<lang>[]
 *
 * Đơn trị: id_loai ma_sp gia khuyen_mai weight
 *  so_thu_tu da_ban ma_video link_khac
 *  file_download (FILE) hinh_anh product_attributes album
 *
 * Cờ: hien_thi sp_moi sp_hot sp_sale sp_top tieu_bieu nofollow noindex
 */

$canEdit   = $d->checkPermission_edit($id_module) == 1;
$isEdit    = isset($_GET['id']);
$editId    = $isEdit ? (int) $_GET['id'] : 0;
$MODULE    = 'san-pham';
$langs     = get_json('lang');
$multiLang = count($langs) > 1;

$actionUrl = admin_url(array_filter([
    'p'    => $_REQUEST['p'] ?? $MODULE,
    'a'    => 'save',
    'id'   => $_REQUEST['id'] ?? '',
    'page' => $_REQUEST['page'] ?? '',
]));

$rowByLang = [];
if ($isEdit) {
    foreach ($langs as $lang) {
        $rowByLang[$lang['code']] = $d->safeFetchOne(
            'select * from #_sanpham where id_code = ? and lang = ?',
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

$rowAlbum = [];
if ($isEdit) {
    $rowAlbum = $d->o_fet(
        'select * from #_sanpham_hinhanh where id_sp = ' . $editId . ' order by stt ASC, id ASC'
    );
}

$showDvt   = get_json('product', 'don_vi_tinh') == 1;
$showInfo  = get_json('product', 'thong_so')    == 1;
$showVideo = get_json('product', 'video')        == 1;
$showFile  = get_json('product', 'file')         == 1;
$showMaSp  = get_json('product', 'ma_sp')        == 1;
$showGia   = get_json('product', 'gia')          == 1;
$showKM    = get_json('product', 'khuyen_mai')   == 1;

$ngayDangLocal = !empty($main['ngay_dang'])
    ? date('Y-m-d\TH:i', (int) $main['ngay_dang'])
    : '';
?>

<?php component('page_header', [
    'title'      => 'Sản phẩm',
    'subtitle'   => $isEdit ? '[Sửa]' : '[Thêm mới]',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Sản phẩm',  'url' => admin_url(['p' => $MODULE, 'a' => 'man'])],
        ['label' => $isEdit ? 'Sửa' : 'Thêm mới'],
    ],
]); ?>

<div class="app-content">
    <div class="container-fluid">

        <form name="frm" method="post"
              action="<?= e($actionUrl) ?>"
              enctype="multipart/form-data"
              data-warn-unsaved>

            <div class="row g-3">

                <!-- ============ Cột chính ============ -->
                <div class="col-lg-9">

                    <?php component('card_open', ['body' => 'none']); ?>
                        <div class="card-body">

                            <?php component('form/lang_tabs', ['langs' => $langs, 'prefix' => 'sp']); ?>

                            <div class="tab-content">

                                <?php foreach ($langs as $i => $lang):
                                    $code    = $lang['code'];
                                    $row     = $rowByLang[$code] ?? [];
                                    $isFirst = $i === 0;

                                    $nameId  = 'ten_'    . $code;
                                    $slugId  = 'slug_'   . $code;
                                    $aliasId = 'alias_'  . $code;
                                    $titleId = 'title_'  . $code;
                                    $moTaId  = 'mo_ta_'  . $code;
                                    $tdId    = 'noi_dung_'      . $code;
                                    $td1Id   = 'noi_dung_1_'    . $code;
                                    $td2Id   = 'noi_dung_2_'    . $code;
                                    ?>
                                    <div class="tab-pane fade <?= $isFirst ? 'show active' : '' ?>"
                                         id="sp-<?= e($code) ?>"
                                         role="tabpanel">

                                        <input type="hidden" name="id_row[]"
                                               value="<?= $isEdit && !empty($row['id']) ? (int) $row['id'] : '' ?>">

                                        <?php component('form/input', [
                                            'label'       => $multiLang ? 'Tên sản phẩm (' . $code . ')' : 'Tên sản phẩm',
                                            'name'        => 'ten[]',
                                            'id'          => $nameId,
                                            'value'       => $row['ten'] ?? '',
                                            'placeholder' => 'Nhập tên sản phẩm',
                                            'required'    => $isFirst,
                                            'attr'        => [
                                                'onkeyup' => $isFirst
                                                    ? sprintf(
                                                        "addText(this,'#%s','#%s',{onlyIfEmpty:true});",
                                                        e($aliasId), e($titleId)
                                                    )
                                                    : sprintf(
                                                        "addText(this,'','#%s',{onlyIfEmpty:true})",
                                                        e($titleId)
                                                    ),
                                            ],
                                        ]); ?>

                                        <?php component('form/input', [
                                            'label'       => $multiLang ? 'URL sản phẩm (alias) (' . $code . ')' : 'URL sản phẩm (alias)',
                                            'name'        => 'alias[]',
                                            'id'          => $aliasId,
                                            'value'       => $row['alias'] ?? '',
                                            'placeholder' => 'ten-san-pham.html',
                                            'hint'        => 'URL cuối cùng của sản phẩm. Tự động tạo từ tên; nhập tay nếu muốn tùy chỉnh.',
                                        ]); ?>

                                        <?php component('form/input', [
                                            'label'       => $multiLang ? 'Tiền tố URL (slug) (' . $code . ')' : 'Tiền tố URL (slug)',
                                            'name'        => 'slug[]',
                                            'id'          => $slugId,
                                            'value'       => $row['slug'] ?? '',
                                            'placeholder' => 'ten-san-pham',
                                            'hint'        => 'Phần đứng trước alias, dùng khi sản phẩm thuộc nhóm/danh mục có tiền tố riêng. Bỏ trống nếu không dùng.',
                                        ]); ?>

                                        <?php if ($showDvt): ?>
                                            <?php component('form/input', [
                                                'label'       => $multiLang ? 'Đơn vị tính (' . $code . ')' : 'Đơn vị tính',
                                                'name'        => 'dvt[]',
                                                'id'          => 'dvt_' . $code,
                                                'value'       => $row['dvt'] ?? '',
                                                'placeholder' => 'cái / hộp / chiếc ...',
                                            ]); ?>
                                        <?php endif; ?>

                                        <?php component('form/textarea', [
                                            'label'       => $multiLang ? 'Mô tả ngắn (' . $code . ')' : 'Mô tả ngắn',
                                            'name'        => 'mo_ta[]',
                                            'id'          => $moTaId,
                                            'value'       => $row['mo_ta'] ?? '',
                                            'placeholder' => 'Hiển thị ở trang danh sách và kết quả tìm kiếm.',
                                            'rows'        => 4,
                                            'counter'     => true,
                                            'max_hint'    => 200,
                                        ]); ?>




                                        <?php component('form/editor', [
                                            'label'  => $multiLang ? 'Nội dung chi tiết (' . $code . ')' : 'Nội dung chi tiết',
                                            'name'   => 'noi_dung[]',
                                            'id'     => $tdId,
                                            'value'  => $row['noi_dung'] ?? '',
                                            'height' => '350px',
                                        ]); ?>

                                        <?php component('form/seo_panel', [
                                            'lang'     => $code,
                                            'suffix'   => $multiLang ? ' (' . $code . ')' : '',
                                            'title'    => $row['title']    ?? '',
                                            'des'      => $row['des']      ?? '',
                                            'keyword'  => $row['keyword']  ?? '',
                                            'seo_head' => $row['seo_head'] ?? '',
                                            'seo_body' => $row['seo_body'] ?? '',
                                            'base_url' => URLPATH ?? '',
                                            'name_id'  => $nameId,
                                            'slug_id'  => $slugId,
                                            'alias_id' => $aliasId,
                                        ]); ?>

                                    </div><!-- /.tab-pane -->
                                <?php endforeach; ?>
                            </div><!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    <?php component('card_close', ['body' => 'none']); ?>

                    <?php @include 'accordion-variants.php' ?>

                </div>
                <!-- ============ /Cột chính ============ -->

                <!-- ============ Cột phụ ============ -->
                <div class="col-lg-3">

                    <?php component('card_open', ['body' => '']); ?>
                        <?php component('form/actions_bar', [
                            'back_url' => admin_url(['p' => $MODULE, 'a' => 'man']),
                            'can_edit' => $canEdit,
                        ]); ?>
                    <?php component('card_close', ['body' => '']); ?>

                    <?php component('card_open', ['title' => 'Thông tin đăng']); ?>
                        <?php component('form/input', [
                            'label'    => 'Ngày đăng',
                            'name'     => 'ngay_dang',
                            'id'       => 'ngay_dang',
                            'type'     => 'datetime-local',
                            'value'    => $ngayDangLocal,
                            'hint'     => $isEdit
                                ? (!empty($main['ngay_dang'])
                                    ? 'Hiện tại: ' . e(date('d/m/Y H:i', (int) $main['ngay_dang'])) . '. Bỏ trống nếu muốn giữ nguyên.'
                                    : 'Chưa có ngày đăng.')
                                : 'Để trống sẽ lấy giờ hiện tại.',
                        ]); ?>
                        <?php component('form/input', [
                            'label'    => 'Số thứ tự',
                            'name'     => 'so_thu_tu',
                            'id'       => 'so_thu_tu',
                            'value'    => (int) ($main['so_thu_tu'] ?? 0),
                            'type'     => 'number',
                            'min'      => 0,
                        ]); ?>
                    <?php component('card_close'); ?>

                    <?php component('card_open', ['title' => 'Hình ảnh chính']); ?>
                        <?php component('form/image_picker', [
                            'name'    => 'hinh_anh',
                            'value'   => $main['hinh_anh'] ?? '',
                            'table'   => '_sanpham',
                            'row_id'  => $isEdit ? (string) $editId : '',
                            'height'  => 180,
                            'label'   => 'Hình đại diện',
                        ]); ?>
                    <?php component('card_close'); ?>

                    <?php component('card_open', ['title' => 'Phân loại']); ?>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="id_loai">Danh mục</label>
                            <select name="id_loai" id="id_loai" class="form-select form-select-sm select2">
                                <option value="0">-- Chọn danh mục --</option>
                                <?= $loai ?? '' ?>
                            </select>
                        </div>
                    <?php component('card_close'); ?>

                    <?php component('card_open', ['title' => 'Trạng thái sản phẩm']); ?>
                        <div class="row g-2">
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Hiển thị',
                                    'name'    => 'hien_thi',
                                    'checked' => !$isEdit || ($main['hien_thi'] ?? 0) == 1,
                                ]); ?>
                            </div>
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Mới',
                                    'name'    => 'sp_moi',
                                    'checked' => ($main['sp_moi'] ?? 0) == 1,
                                ]); ?>
                            </div>
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Hot',
                                    'name'    => 'sp_hot',
                                    'checked' => ($main['sp_hot'] ?? 0) == 1,
                                ]); ?>
                            </div>
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Sale',
                                    'name'    => 'sp_sale',
                                    'checked' => ($main['sp_sale'] ?? 0) == 1,
                                ]); ?>
                            </div>
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Top',
                                    'name'    => 'sp_top',
                                    'checked' => ($main['sp_top'] ?? 0) == 1,
                                ]); ?>
                            </div>
                            <div class="col-6">
                                <?php component('form/checkbox', [
                                    'label'   => 'Tiêu biểu',
                                    'name'    => 'tieu_bieu',
                                    'checked' => ($main['tieu_bieu'] ?? 0) == 1,
                                ]); ?>
                            </div>
                        </div>
                    <?php component('card_close'); ?>

                    <?php if ($showFile): ?>
                        <?php component('card_open', ['title' => 'Tài liệu đính kèm', 'icon' => 'fa-solid fa-file-pdf']); ?>
                        <?php
                        $fileLink = $main['link_khac'] ?? '';
                        ?>
                            <?php if ($fileLink !== ""): ?>
                                <iframe class="w-100 mb-2 rounded border"
                                        src="https://docs.google.com/gview?url=<?= urlencode($fileLink) ?>&embedded=true"
                                        style="height:200px"
                                        frameborder="0"></iframe>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Upload file mới</label>
                                <input type="file" name="file_download" class="form-control form-control-sm">
                                <?php if (!empty($main["file"])): ?>
                                    <div class="form-text">
                                        File hiện tại: <code><?= e($main["file"]) ?></code>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" for="link_khac">Hoặc đường dẫn ngoài</label>
                                <input type="text" id="link_khac" name="link_khac"
                                       class="form-control form-control-sm"
                                       placeholder="https://..."
                                       value="<?= e($main['link_khac'] ?? '') ?>">
                            </div>
                        <?php component('card_close'); ?>
                    <?php endif; ?>

                </div><!-- /Cột phụ -->
            </div><!-- /.row g-3 -->

        </form>

    </div>
</div>
<!-- ============================================================
     Script riêng cho trang sản phẩm
     - Thêm / xoá dòng "Thông số kỹ thuật"
     - Hàm xoa_hinh_sp(id) — xóa ảnh trong bảng #_sanpham_hinhanh
       (KHÁC với xoá `hinh_anh` của image_picker, vì dữ liệu nằm
        ở bảng album riêng).
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    /*
     * Thông số kỹ thuật: cho phép thêm dòng động, mỗi dòng có
     * nameinfo_<lang>[] và detailinfo_<lang>[] (cùng index).
     * Controller ghép lại thành JSON.
     */
    document.querySelectorAll('[data-tskt-wrap]').forEach(function (wrap) {
        var pane = wrap.closest('.tab-pane');
        var addBtn = pane ? pane.querySelector('.tskt-add') : null;
        if (!addBtn) { return; }
        addBtn.addEventListener('click', function () {
            var row = wrap.querySelector('.tskt-row');
            if (!row) { return; }
            var clone = row.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            var btn = clone.querySelector('.tskt-del');
            if (btn) { btn.disabled = false; }
            wrap.appendChild(clone);
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.tskt-del');
            if (!btn || btn.disabled) { return; }
            // Không xoá nếu chỉ còn 1 dòng (tránh gửi mảng rỗng)
            if (wrap.querySelectorAll('.tskt-row').length <= 1) {
                wrap.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                return;
            }
            btn.closest('.tskt-row').remove();
        });
    });
});

/**
 * Xóa ảnh album.
 * Gọi AJAX tới admin/sources/ajax.php với do=xoa_anh_sp; backend xử lý bảng
 * #_sanpham_hinhanh. Khi xong thì gỡ khối <div id="album_<id>"> khỏi DOM.
 */
function xoa_hinh_sp(id){
    if(!confirm('Xác nhận xóa ảnh?')) return false;
    $.ajax({
        url: './sources/ajax.php',
        type: 'POST',
        data: 'id=' + id + '&do=xoa_anh_sp',
        success: function () {
            $('#album_' + id).remove();
        }
    });
}
</script>

