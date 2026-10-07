$(document).ready(function () {
    $('ul#menu>li>a[href="#"]').click(function () {
        $(this).next('ul').toggle();
        return false;
    });
    $('#search-input').change(function () {
        var p = $(this).attr('data-p');
        var col = $(this).val();
        var key = $('#key-search').val();
        window.location = "index.php?p=" + p + "&a=man&search=" + col + "&key=" + key;
    });
    $('#search-cate').change(function () {
        var p = $(this).attr('data-p');
        var col = $(this).val();
        window.location = "index.php?p=" + p + "&a=man&search=loai&key=" + col;
    });
    /*
     * Chọn tất cả dòng.
     *
     * LỖI CŨ: selector '.table input:checkbox' quét MỌI checkbox nằm trong
     * bảng, nên khi tick "chọn tất cả" thì các công tắc trạng thái
     * (hien_thi, menu, tieu_bieu, home...) cũng bị bật lên đồng loạt.
     * Đây là bật "giả" trên giao diện: .prop() không phát sự kiện change nên
     * không có AJAX nào chạy -> giao diện lệch hẳn với dữ liệu trong DB.
     *
     * Chỉ tick đúng các checkbox CHỌN DÒNG:
     *  - name="chk_child[]" : template cũ (bai-viet, san-pham, video...)
     *  - .js-row-check      : component mới (components/row_check.php)
     * Bỏ qua ô đang disabled (không có quyền xoá).
     */
    $('#check_all').change(function () {
        var $table = $(this).closest('table');
        var $scope = $table.length ? $table : $(document);

        $scope
            .find('input[type="checkbox"][name="chk_child[]"], input[type="checkbox"].js-row-check')
            .not(':disabled')
            .prop('checked', this.checked);
    });
    $('.a_stt').on('blur', function () {
        var table = $(this).attr('data-table');
        var col = $(this).attr('data-col');
        var id = $(this).attr('data-id');
        var val = $(this).val();
        $.ajax({
            url: './sources/ajax.php',
            type: "POST",
            data: { 'table': table, 'col': col, 'val': val, 'id': id, 'do': 'update_stt' },
            dataType: "json",
            success: function (result) {
                if (result === 1) {
                    $.notify("Cập nhật thành công", "success");
                } else {
                    $.notify("Đã sảy ra lỗi! ", "error");
                }
            }
        })
    });
    $('.a_stt2').on('change', function () {
        var table = $(this).attr('data-table');
        var col = $(this).attr('data-col');
        var id = $(this).attr('data-id');
        var val = $(this).val();
        if (val > 0) {
            $.ajax({
                url: './sources/ajax.php',
                type: "POST",
                data: { 'table': table, 'col': col, 'val': val, 'id': id, 'do': 'update_data' },
                dataType: "json",
                success: function (result) {
                    if (result === 1) {
                        $.notify("Cập nhật thành công", "success");
                    } else {
                        $.notify("Đã sảy ra lỗi! ", "error");
                    }
                }
            })
        }
    });
    $('.ajax-input').change('click', function () {
        var col = $(this).attr('data-col');
        var table = $(this).attr('data-table');
        var id = $(this).attr('data-id');
        var val = $(this).val();
        //alert(val);
        $.ajax({
            url: 'sources/ajax.php',
            type: "post",
            dataType: "text",
            data: { 'table': table, 'col': col, 'val': val, 'id': id, 'do': 'update_stt' },
            success: function (result) {
                if (result === '1') {
                    $.notify("Cập nhật thành công", "success");
                } else {
                    $.notify("Đã sảy ra lỗi! ", "error");
                }
            }
        });

    });
    $('.insert-ck').change('click', function () {
        var data_tv = $(this).attr('data-tv');
        var data_id = $(this).attr('data-id');
        var data_dv = $(this).attr('data-dv');
        var val = $(this).val();
        //alert(val);
        $.ajax({
            url: 'sources/ajax.php',
            type: "post",
            dataType: "text",
            data: { 'data_tv': data_tv, 'data_id': data_id, 'val': val, 'data_dv': data_dv, 'do': 'update_chietkhau' },
            success: function (result) {
                if (result === '1') {
                    $.notify("Cập nhật thành công", "success");
                } else {
                    $.notify("Đã sảy ra lỗi! ", "error");
                }
            }
        });
    });
});

function reloadUpStt() {
    $('.a_stt').on('blur', function () {
        var table = $(this).attr('data-table');
        var col = $(this).attr('data-col');
        var id = $(this).attr('data-id');
        var val = $(this).val();
        $.ajax({
            url: './sources/ajax.php',
            type: "POST",
            data: { 'table': table, 'col': col, 'val': val, 'id': id, 'do': 'update_stt' },
            dataType: "json",
            success: function (result) {
                if (result === 1) {
                    $.notify("Cập nhật thành công", "success");
                } else {
                    $.notify("Đã sảy ra lỗi! ", "error");
                }
            }
        })
    });
}

function copyToClipboard(element) {
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(element.val()).select();
    document.execCommand("copy");
    $temp.remove();
    $.notify("Đã sao chép", "success");
}

/**
 * Tự điền đường dẫn (alias) và tiêu đề SEO theo ô tên đang gõ.
 *
 * @param {Element} text   Ô nguồn (thường là "Tên danh mục / Tên bài viết")
 * @param {string}  target Selector ô alias. Truyền '' để không đụng tới.
 * @param {string}  title  Selector ô Title SEO. Truyền '' để không đụng tới.
 * @param {object}  opts   { onlyIfEmpty: true } -> KHÔNG ghi đè Title người
 *                         dùng đã tự viết. Bỏ trống = giữ nguyên hành vi cũ.
 *
 * VÌ SAO CÓ onlyIfEmpty:
 * Bản cũ luôn ghi đè Title (và slug). Với bài/danh mục ĐÃ LƯU, người dùng
 * thường đã viết Title SEO riêng, và đặc biệt slug có thể đã được search
 * engine index — gõ 1 ký tự vào ô Tên là toàn bộ công sức đó bị xoá, không
 * hề cảnh báo.
 *
 * Cách nhận biết "người dùng đã tự sửa": mỗi lần tự điền, ta ghi nhớ giá trị
 * vừa điền vào data('autofill'). Lần sau nếu nội dung ô vẫn đúng bằng giá trị
 * đó -> chưa ai đụng vào, tiếp tục đồng bộ. Nếu đã khác -> dừng hẳn.
 * Nhờ vậy: ô Title/slug trống vẫn được điền mượt theo từng ký tự, còn nội
 * dung có sẵn (hoặc đã bị sửa tay) thì tuyệt đối không bị đè.
 *
 * Tham số opts là TUỲ CHỌN nên mọi lời gọi cũ (19 chỗ) không đổi hành vi.
 */
function addText(text, target, title, opts) {
    var str = $(text).val();

    if (target) {
        applyField(target, locdau(str), opts);
    }

    if (!title) {
        return;
    }

    applyField(title, str, opts);
}

/**
 * Ghi giá trị vào 1 ô, có tôn trọng cờ onlyIfEmpty nếu được truyền.
 * Tách thành hàm riêng để áp dụng nhất quán cho cả alias lẫn Title.
 */
function applyField(selector, value, opts) {
    var $el = $(selector);
    if (!$el.length) { return; }

    if (opts && opts.onlyIfEmpty) {
        var current = $.trim($el.val());
        var autoFilled = $el.data('autofill');

        // Có nội dung mà KHÔNG phải do ta điền -> người dùng tự viết, bỏ qua
        if (current !== '' && current !== autoFilled) {
            return;
        }
    }

    $el.val(value);
    $el.data('autofill', value);
}

function seach(obj, tenp) {
    var loai = $(obj).val();
    var key = $("#search").val();
    if (loai == 1) {
        window.location = "index.php?p=" + tenp + "&a=man&seach=id&key=" + key;
    } else if (loai == 2) {
        window.location = "index.php?p=" + tenp + "&a=man&seach=name&key=" + key;
    } else if (loai == 3) {
        window.location = "index.php?p=" + tenp + "&a=man&seach=ma&key=" + key;
    }
}

function show(obj, tenp) {
    var show = $(obj).val();
    window.location = "index.php?p=" + tenp + "&a=man&hienthi=" + show;
}

function form_submit(obj) {
    if ($(obj).val() == "delete") {
        if (confirm("Bạn có chắc muốn xóa?")) {
            $("#form").submit();
        }
    }
}

function on_check(obj, bang, cot, id) {
    var trangthai = 0;
    if ($(obj).is(':checked')) {
        trangthai = 1;
    }
    $.ajax({
        url: "./sources/aj_checkbox.php",
        type: 'POST',
        data: "do=checkbox&trangthai=" + trangthai + "&bang=" + bang + "&cot=" + cot + "&id=" + id,
        success: function (data) {
            $.notify("Cập nhật thành công", "success");
            console.log(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $.notify("Đã sảy ra lỗi! ", "error");
        },
    })
}

function xoa_img(bang, cot, id_code, folder) {
    if (confirm("Xác nhận xóa") == true) {
        $.ajax({
            url: "./sources/aj_checkbox.php",
            type: 'POST',
            data: "do=xoaimg&bang=" + bang + "&cot=" + cot + "&id=" + id_code + '&folder=' + folder,
            success: function (data) {
                if (data === '1') {
                    $.notify("Xóa thành công", "success");

                    // Chỉ reset giá trị + preview trong component picker.
                    // KHÔNG remove() cả khối (trước đây $('.'+cot).remove() khiến
                    // không chọn lại được ảnh/video).
                    var $input = $('#' + cot);
                    var preview = ($input.attr('data-preview') || 'image').toLowerCase();
                    var $wrap = $input.closest('[data-file-picker]');
                    if (!$wrap.length) {
                        $wrap = $input.closest('.' + cot);
                    }
                    if (!$wrap.length) {
                        $wrap = $input.parent();
                    }

                    $input.val('');

                    if (preview === 'video') {
                        var $v = $('#review_' + cot);
                        $v.removeAttr('src').addClass('d-none').hide();
                        try { $v.get(0) && $v.get(0).load && $v.get(0).load(); } catch (e) { }
                        $('#review_' + cot + '_empty').removeClass('d-none').show();
                    } else if (preview === 'file') {
                        $('#review_' + cot + '_wrap').addClass('d-none').hide();
                        $('#review_' + cot + '_empty').removeClass('d-none').show();
                        $('#review_' + cot + '_link').attr('href', '#').text('');
                    } else {
                        // image (image_picker / file_picker image)
                        var $img = $('#review_' + cot);
                        if ($img.length) {
                            $img.attr('src', 'img/no-image.png');
                        }
                    }

                    // Ẩn nút xóa trong component (không còn file đã lưu) và chuyển sang chế độ clear-image
                    var $delBtns = $wrap.find('button[data-action="xoa-img"], button[onclick*="xoa_img"]');
                    $delBtns.attr('data-action', 'clear-image').removeAttr('onclick').addClass('d-none').css('display', '');
                } else {
                    $.notify("Đã sảy ra lỗi! ", "error");
                }
            }
        })
    }
}

function locdau(str) {
    str = str.toLowerCase();
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
    str = str.replace(/đ/g, "d");
    str = str.replace(/!|@|\$|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\'| |\"|\&|\#|\[|\]|\;|\||\{|\}|~/g, "-");
    str = str.replace(/^\-+|\-+$/g, "-");
    str = str.replace(/\–/g, "-");
    str = str.replace(/\\/g, "-");
    str = str.replace(/-+-/g, "-");
    str = str.replace("’", "");
    str = str.replace("'", "");
    str = str.replace("`", "");
    return str;
}

function view_notif_all() {
    $.ajax({
        url: "sources/ajax.php",
        type: "post",
        dataType: "text",
        data: {
            do: 'view_notif_all'
        },
        success: function () {
            $('#notifications').html('0');
            $('.view_notif').removeClass('chuaxem');
            $.notify("Update successful", "success");
        }
    });
}
$('.view_notif').click(function () {
    var url = $(this).attr('data-url');
    var id = $(this).attr('data-id');
    $.ajax({
        url: "sources/ajax.php",
        type: "post",
        dataType: "text",
        data: {
            do: 'view_notif',
            id: id
        },
        success: function () {
            window.location = url;
        }
    });
});
$('.delete-notif').click(function () {
    var id = $(this).attr('data-id');
    $.ajax({
        url: "sources/ajax.php",
        type: "post",
        dataType: "text",
        data: {
            do: 'delete_notif',
            id: id
        },
        success: function () {
            $('#notif_' + id).remove();
        }
    });
});