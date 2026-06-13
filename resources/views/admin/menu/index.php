<?php
$breadcrumbActions = [];
?>
<?= view('admin.components.breadcrumb', [
    'title' => 'Quản lý Menu Website',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Menu Website', 'url' => '']
    ],
    'actions' => $breadcrumbActions
]) ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css"/>
<style>
    .source-box { border: 1px solid #dfdfdf; margin-bottom: 10px; background: #fff; border-radius: 4px; overflow: hidden; }
	.source-box h3 {
	    padding: 10px 15px;
	    margin: 0;
	    font-size: 14px;
	    background: #f8f9fa;
	    border-bottom: 1px solid #dfdfdf;
	    cursor: pointer;
	    display: flex;
	    justify-content: space-between;
	    align-items: center;
	}
	.source-box h3::after { content: '\25BC'; font-size: 12px; color: #666; }
	.source-box.active h3::after { content: '\25B2'; }
	.source-content { padding: 15px; display: none; }
	.source-search { width: 100%; padding: 6px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;}
	.source-items { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fcfcfc; border-radius: 4px;}
	.source-item { margin-bottom: 5px; }
	.source-item label { font-weight: normal; font-size: 13px; display: block; cursor: pointer; margin: 0; }

	.custom-link-form .form-group { margin-bottom: 10px; }
	.custom-link-form label { display: block; font-weight: normal; margin-bottom: 4px; font-size: 13px; }
	.custom-link-form input { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 4px;}

	.add-selected { margin-top: 10px; }
	.select-all-wrap { margin-top: 10px; font-size: 13px; }
	
	.language-selector { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
	.language-selector label { margin-right: 15px; font-weight: normal; cursor: pointer; }

	.menu-header { display: flex; justify-content: space-between; align-items: center; }
	.menu-selector { display: flex; align-items: center; gap: 10px; width: 100%; }
	.menu-name-input { font-size: 16px; padding: 5px 10px; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;}

	.dd { max-width: 100%; }
	.dd-handle { display: flex; justify-content: space-between; align-items: center; height: 40px; padding: 5px 10px; background: #fafafa; border: 1px solid #ccc; color: #333; font-weight: bold; border-radius: 4px; }
	.dd-item > button { height: 40px; margin-left: 5px; margin-top: 0; }
	.handle-left { display: flex; align-items: center; gap: 10px; }
	.item-type { font-size: 12px; color: #666; font-weight: normal; }
	.handle-actions { display: flex; align-items: center; gap: 5px; }
	.handle-actions button { background: none; border: none; cursor: pointer; color: #666; padding: 5px; }
	.handle-actions button:hover { color: #000; }

	.item-settings { display: none; padding: 15px; border: 1px solid #ccc; border-top: none; background: #fff; border-radius: 0 0 4px 4px; }
	.settings-row { display: flex; gap: 15px; margin-bottom: 10px; }
	.settings-col { flex: 1; }
	.item-settings label { display: block; font-size: 12px; font-weight: normal; color: #666; margin-bottom: 4px; }
	.item-settings input[type="text"], .item-settings select { width: 100%; padding: 5px; border: 1px solid #ddd; font-size: 13px; border-radius: 4px;}
	.settings-actions { margin-top: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
	.item-remove { color: #dc3232; cursor: pointer; text-decoration: underline; }
	.item-cancel { color: #0073aa; cursor: pointer; text-decoration: underline; }

	.menu-empty { margin-top: 8px; padding: 15px; color: #666; text-align: center; background: #fafafa; border: 1px dashed #ccc; display: none; border-radius: 4px;}
	.menu-locations { margin-top: 16px; background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #eee; }
    .menu-locations h3 { font-size: 15px; margin-bottom: 10px; margin-top: 0; }
	.menu-locations label { display: block; margin-bottom: 5px; font-weight:normal;}
	.menu-footer { margin-top: 20px; display: flex; justify-content: space-between; gap: 10px; border-top: 1px solid #eee; padding-top: 15px; }
	.menu-footer-left { display: flex; align-items: center; gap: 1rem; }

    .loading-text { font-style: italic; color: #888; font-size: 12px; text-align: center; padding: 10px; }
</style>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
    <!-- CỘT TRÁI: THÊM MỤC MENU -->
    <div class="col-md-4">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title">Thêm mục menu</h3>
            </div>
            <div class="card-body">
                <?php $langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]); ?>
                <?php if (count($langs) > 1): ?>
                <div class="language-selector">
                    <h3 style="font-size: 15px; margin-bottom: 10px;">Ngôn ngữ</h3>
                    <?php foreach ($langs as $key => $lg): ?>
                        <label><input type="radio" name="lang" value="<?= $lg['code'] ?>"> <?= $lg['name'] ?></label>
                    <?php endforeach ?>
                    <label><input type="radio" name="lang" value="all" checked> Tất cả</label>
                </div>
                <?php endif ?>

                <!-- LIÊN KẾT TỰ TẠO -->
                <div class="source-box">
                    <h3>Liên kết tự tạo</h3>
                    <div class="source-content custom-link-form">
                        <div class="form-group">
                            <label>URL</label>
                            <input type="text" id="c-menu-item-url" value="http://">
                        </div>
                        <div class="form-group">
                            <label>Tên đường dẫn</label>
                            <input type="text" id="c-menu-item-name">
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="add-custom-link">Thêm vào menu</button>
                    </div>
                </div>

                <!-- DANH MỤC -->
                <div class="source-box source-ajax" data-type="category" data-title="Danh mục">
                    <h3>Danh mục</h3>
                    <div class="source-content">
                        <input type="text" class="source-search" placeholder="Tìm kiếm...">
                        <div class="source-items"></div>
                        <hr style="border: 0; border-top: 1px solid #c3c4c7; margin: 10px 0;">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-selected">Thêm vào menu</button>
                        <div class="select-all-wrap">
                            <label><input type="checkbox" class="select-all"> Chọn tất cả</label>
                        </div>
                    </div>
                </div>

                <!-- SẢN PHẨM -->
                <div class="source-box source-ajax" data-type="sanpham" data-title="Sản phẩm">
                    <h3>Sản phẩm</h3>
                    <div class="source-content">
                        <input type="text" class="source-search" placeholder="Tìm kiếm...">
                        <div class="source-items"></div>
                        <hr style="border: 0; border-top: 1px solid #c3c4c7; margin: 10px 0;">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-selected">Thêm vào menu</button>
                        <div class="select-all-wrap">
                            <label><input type="checkbox" class="select-all"> Chọn tất cả</label>
                        </div>
                    </div>
                </div>

                <!-- BÀI VIẾT -->
                <div class="source-box source-ajax" data-type="tintuc" data-title="Bài viết">
                    <h3>Bài viết</h3>
                    <div class="source-content">
                        <input type="text" class="source-search" placeholder="Tìm kiếm...">
                        <div class="source-items"></div>
                        <hr style="border: 0; border-top: 1px solid #c3c4c7; margin: 10px 0;">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100 add-selected">Thêm vào menu</button>
                        <div class="select-all-wrap">
                            <label><input type="checkbox" class="select-all"> Chọn tất cả</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: CẤU TRÚC MENU -->
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Cấu trúc menu</h3>
                <div class="menu-selector">
                    <label class="mb-0" style="font-weight: normal; font-size: 14px;">Chọn menu để sửa:</label>
                    <select id="menu-selector" class="form-select form-select-sm" style="width: auto; display:inline-block;">
                        <?php foreach ($menus as $m): ?>
                            <option value="<?= $m->id ?>" <?= $m->id == $current_menu_id ? 'selected' : '' ?>><?= htmlspecialchars($m->name) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="button" class="btn btn-secondary btn-sm" id="select-menu-btn">Chọn</button>
                    <span style="margin: 0 10px; font-size: 14px;">hoặc</span>
                    <a href="#" id="create-new-menu" style="text-decoration:underline; color:#0073aa; font-size: 14px;">tạo menu mới</a>
                </div>
            </div>

            <div class="card-body">
                <?php if ($current_menu_id > 0): ?>
                <div>
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <label style="font-weight:bold; margin-bottom:0; white-space:nowrap;">Tên menu</label>
                        <input type="text" id="menu-name" class="menu-name-input form-control form-control-sm" value="<?= htmlspecialchars($current_menu->name) ?>">
                    </div>

                    <div style="border: 1px solid #dee2e6; border-radius: 4px; padding:15px; margin-top: 20px;">
                        <h4 class="mb-2" style="font-size: 16px;">Sắp xếp liên kết</h4>
                        <p style="font-size:13px; color:#666; margin-bottom:15px;">Kéo thả từng mục để sắp xếp thứ tự. Kéo sang phải để tạo menu con.</p>
                        
                        <div class="dd" id="menu-editor">
                            <div id="menu-empty" class="menu-empty">Chưa có mục nào trong menu</div>
                            <ol class="dd-list"></ol>
                        </div>

                        <div class="menu-locations mt-4">
                            <h3>Vị trí hiển thị</h3>
                            <?php 
                            $count_lang = count($langs);
                            ?>
                            <?php foreach ($menu_location as $key => $value): ?>
                                <?php 
                                    $is_active_lang = false; 
                                    foreach($saved_locations_for_current_menu as $sv){
                                        if($sv->location_name == $value->location_name && $sv->lang == $value->lang) {
                                            $is_active_lang = true;
                                            break;
                                        }
                                    }
                                ?>
                                <label>
                                    <input type="checkbox" class="menu-location" value="<?= $value->location_name.'_'.$value->lang ?>" <?= $is_active_lang ? 'checked' : '' ?>>
                                    <?php 
                                        echo htmlspecialchars($value->location_label);					                
                                        if ($count_lang > 1) {
                                            $current_lang_info = array_filter($langs, function($lg) use ($value) {
                                                return $lg['code'] == $value->lang;
                                            });
                                            $current_lang_info = reset($current_lang_info);
                                            if($current_lang_info) {
                                                echo ' <span class="text-muted">(' . htmlspecialchars($current_lang_info['name']) . ')</span>';
                                            }
                                        }
                                    ?>
                                </label>
                            <?php endforeach ?>
                        </div>

                        <div class="menu-footer">
                            <div class="menu-footer-left">
                                <a href="#" id="delete-menu" style="color:#dc3232; text-decoration:underline;">Xóa menu</a>
                            </div>
                            <button type="button" class="btn btn-primary" id="save-menu-button">Lưu Menu</button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <p>Vui lòng chọn một menu để chỉnh sửa hoặc tạo mới.</p>
                        <button type="button" class="btn btn-primary btn-sm" id="create-new-menu-empty">Tạo menu mới</button>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
        </div>
    </div>
</div>

<template id="menu-item-template">
    <li class="dd-item">
        <div class="dd-handle">
            <div class="handle-left">
                <input type="checkbox" class="item-select">
                <span class="item-title"></span>
                <span class="item-type">Tùy chỉnh</span>
            </div>
            <div class="handle-actions">
                <button type="button" class="item-toggle" title="Cài đặt">▼</button>
            </div>
        </div>
        <div class="item-settings">
            <p style="font-size:12px; margin-top:0;">Các cài đặt nâng cao cho mục menu này.</p>
            <div class="settings-row">
                <div class="settings-col">
                    <label>Tiêu đề</label>
                    <input type="text" class="item-input" data-name="label">
                </div>
                <div class="settings-col item-url-col">
                    <label>URL</label>
                    <input type="text" class="item-input" data-name="url">
                </div>
            </div>
            <div class="settings-row">
                <div class="settings-col">
                    <label>Class tùy chỉnh</label>
                    <input type="text" class="item-input" data-name="class" placeholder="vd: menu-item--custom">
                </div>
                <div class="settings-col">
                    <label>Kiểu menu</label>
                    <select class="item-input" data-name="style">
                        <option value="default">Mặc định</option>
                    </select>
                </div>
            </div>
            <div class="settings-row">
                <div class="settings-col">
                    <label>Block tùy chỉnh</label>
                    <select class="item-input" data-name="block">
                        <option value="">-- Không chọn --</option>
                    </select>
                </div>
                <div class="settings-col">
                    <label>Target</label>
                    <select class="item-input" data-name="target">
                        <option value="_self">Cửa sổ hiện tại</option>
                        <option value="_blank">Cửa sổ mới (_blank)</option>
                    </select>
                </div>
            </div>
            <div class="settings-row">
                <div class="settings-col">
                    <label>Ảnh</label>
                    <input type="text" class="item-input item-image-input" data-name="image" placeholder="Đường dẫn ảnh..." style="margin-bottom: 5px;">
                    <button type="button" class="btn btn-outline-secondary btn-sm item-image-picker" style="font-size: 12px;"><i class="fa fa-upload"></i> Chọn hình ảnh</button>
                    <div class="image-preview" style="margin-top: 10px; max-width: 100px;">
                        <img src="/admin/img/no-image.png" style="width: 100%; border: 1px solid #ddd; border-radius: 4px;" class="item-image-preview" onerror="this.src='/admin/img/no-image.png'">
                    </div>
                </div>
            </div>
            <div class="settings-actions">
                <a class="item-remove">Xóa</a>
                <a class="item-cancel">Hủy</a>
            </div>
        </div>
    </li>
</template>

<script src="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js"></script>
<script>
    $(document).ready(function () {
        var menuEditor = $('#menu-editor');
        if (!menuEditor.length) return;

        var idCounter = 1000;
        function generateId() { return idCounter++; }

        menuEditor.nestable({ maxDepth: 5, callback: function () { refreshUI(); } });
        
        menuEditor.on('mousedown touchstart', '.handle-actions button, .item-select, .item-settings, .item-settings *', function (e) {
            e.stopPropagation();
        });

        // 1. AJAX SEARCH CHO MENU SOURCE
        function loadAjaxSource($box) {
            var type = $box.data('type');
            var $itemsContainer = $box.find('.source-items');
            var keyword = $box.find('.source-search').val().trim();
            var lang = $('input[name="lang"]:checked').val() || 'all';

            $itemsContainer.html('<div class="loading-text">Đang tải...</div>');

            $.ajax({
                url: '<?= route('admin.menu.searchSource') ?>',
                type: 'GET',
                data: { type: type, q: keyword, lang: lang },
                success: function(res) {
                    if (res.status === 'success') {
                        var html = '';
                        res.data.forEach(function(item) {
                            html += '<div class="source-item"><label>';
                            html += '<input type="checkbox" value="' + escapeHtml(item.label) + '" ' +
                                    'data-label="' + escapeHtml(item.label) + '" ' +
                                    'data-url="' + escapeHtml(item.url) + '" ' +
                                    'data-lang="' + escapeHtml(item.lang) + '" ' +
                                    'data-type="' + escapeHtml(item.type) + '" ' +
                                    'data-object-type="' + escapeHtml(item.object_type) + '" ' +
                                    'data-object-id="' + escapeHtml(item.id) + '">';
                            html += ' ' + escapeHtml(item.label);
                            html += '</label></div>';
                        });
                        if (html === '') html = '<div class="loading-text">Không tìm thấy kết quả.</div>';
                        $itemsContainer.html(html);
                    }
                }
            });
        }

        // Escape HTML helper
        function escapeHtml(unsafe) {
            if(!unsafe) return '';
            return unsafe
                 .toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        // Debounce setup for search
        var searchTimeout;
        $('.source-search').on('keyup', function() {
            var $box = $(this).closest('.source-box');
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadAjaxSource($box);
            }, 300);
        });

        $('input[name="lang"]').on('change', function () {
            $('.source-box.source-ajax').each(function() {
                if($(this).hasClass('active')) {
                    loadAjaxSource($(this));
                }
            });
        });

        $('.source-box h3').click(function () {
            var $box = $(this).closest('.source-box');
            var $content = $box.find('.source-content');
            
            $content.slideToggle();
            $box.toggleClass('active');
            
            if ($box.hasClass('active') && $box.hasClass('source-ajax') && $box.find('.source-items').is(':empty')) {
                loadAjaxSource($box);
            }
        });

        function createMenuItem(options = {}) {
            const newId = options.id || generateId();
            const $tpl = $($('#menu-item-template').html());
            $tpl.attr('data-id', newId);

            var typeName = options.type || 'Tùy chỉnh';
            if (options.lang && options.lang !== 'vi') typeName += ' (' + options.lang + ')';

            $tpl.find('.item-title').text(options.label || 'Mục chưa đặt tên');
            $tpl.find('.item-type').text(typeName);

            const fields = ['label', 'url', 'class', 'style', 'block', 'target', 'image', 'type', 'object_type', 'object_id'];
            fields.forEach(function (f) {
                if (options[f] !== undefined) $tpl.data(f, options[f]);
                $tpl.find('.item-input[data-name="' + f + '"]').val(options[f] || '');
            });

            if (options.image) {
                $tpl.find('.item-image-preview').attr('src', options.image);
            }

            if (options.object_type !== 'custom' && options.object_type) {
                $tpl.find('.item-url-col').hide();
            }

            var hiddenInputId = 'image-' + newId;
            $tpl.find('.item-image-input').attr('id', hiddenInputId);
            $tpl.find('.item-image-picker').addClass('iframe-btn').attr(
                'href','/admin/filemanager/dialog.php?type=1&field_id=' + encodeURIComponent(hiddenInputId) + '&relative_url=1&multiple=0'
            );

            if ($.fn.fancybox) {
                $tpl.find('.iframe-btn').fancybox({
                    type: 'iframe',
                    autoScale: false,
                    afterClose: function () {
                        var href = this.href || $(this.element).attr('href');
                        if (!href) return;
                        var params = new URLSearchParams(href.split('?')[1]);
                        var field_id = params.get('field_id');
                        if (field_id) {
                            var val = $('#' + field_id).val();
                            $('#' + field_id).closest('.settings-col').find('.item-image-preview').attr('src', val);
                            $('#' + field_id).trigger('change');
                        }
                    }
                });
            }

            return $tpl;
        }

        $('#add-custom-link').click(function () {
            var label = $('#c-menu-item-name').val().trim();
            var url = $('#c-menu-item-url').val().trim() || '#';
            if (!label) return alert('Vui lòng nhập tên đường dẫn.');
            var $item = createMenuItem({ label: label, url: url, type: 'Tùy chỉnh', object_type: 'custom' });
            menuEditor.find('.dd-list').first().append($item);
            $('#c-menu-item-name').val('');
            $('#c-menu-item-url').val('http://');
            refreshUI();
        });

        $('.add-selected').click(function () {
            var $box = $(this).closest('.source-box');
            var $itemsList = menuEditor.find('.dd-list').first();
            $box.find('.source-items input[type="checkbox"]:checked').each(function () {
                var $cb = $(this);
                var itemOptions = {
                    label: $cb.data('label'),
                    url: $cb.data('url'),
                    type: $cb.data('type'),
                    object_type: $cb.data('object-type'),
                    object_id: $cb.data('object-id'),
                    lang: $cb.data('lang')
                };
                $itemsList.append(createMenuItem(itemOptions));
                $cb.prop('checked', false);
            });
            $box.find('.select-all').prop('checked', false);
            refreshUI();
        });

        $('.select-all').on('change', function () {
            var isChecked = $(this).prop('checked');
            $(this).closest('.source-content').find('input[type="checkbox"]').prop('checked', isChecked);
        });

        $('#menu-selector').on('change', function () {
            var menuId = $(this).val();
            if (menuId) window.location.href = "<?= route('admin.menu.index') ?>?menu=" + menuId;
        });

        $('#create-new-menu, #create-new-menu-empty').click(function (e) {
            e.preventDefault();
            var name = prompt('Nhập tên menu mới:');
            if (name) {
                $.ajax({
                    url: '<?= route('admin.menu.store') ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: { name: name },
                    success: function (res) {
                        if (res.status === 'success') {
                            window.location.href = "<?= route('admin.menu.index') ?>?menu=" + res.menu_id;
                        } else {
                            alert('Lỗi: ' + res.message);
                        }
                    }
                });
            }
        });

        menuEditor.on('click', '.item-toggle', function () {
            var $panel = $(this).closest('.dd-item').children('.item-settings');
            $panel.slideToggle();
        });

        menuEditor.on('click', '.item-cancel', function () {
            $(this).closest('.item-settings').slideUp();
        });

        menuEditor.on('click', '.item-remove', function () {
            if (confirm('Xóa mục này?')) $(this).closest('.dd-item').remove();
            refreshUI();
        });

        menuEditor.on('input change', '.item-input', function () {
            var $input = $(this);
            var key = $input.data('name');
            var val = $input.val();
            var $item = $input.closest('.dd-item');
            $item.data(key, val);
            if (key === 'label') $item.find('> .dd-handle .item-title').text(val || '(Chưa có tên)');
        });

        $('#delete-menu').click(function (e) {
            e.preventDefault();
            var menuId = $('#menu-selector').val();
            if (confirm('Bạn có chắc chắn muốn xóa menu này không? (Bao gồm tất cả liên kết bên trong)')) {
                $.ajax({
                    url: '<?= route('admin.menu.delete') ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: { menu_id: menuId },
                    success: function (res) {
                        if (res.status === 'success') {
                            window.location.href = '<?= route('admin.menu.index') ?>';
                        } else {
                            alert('Lỗi: ' + res.message);
                        }
                    }
                });
            }
        });

        $('#save-menu-button').click(function () {
            var locations = [];
            $('.menu-location:checked').each(function () {
                locations.push($(this).val());
            });

            var payload = {
                id: $('#menu-selector').val(),
                name: $('#menu-name').val().trim() || 'Menu chưa đặt tên',
                locations: locations,
                items: serializeMenu(menuEditor.find('.dd-list').first())
            };

            $.ajax({
                url: '<?= route('admin.menu.save') ?>',
                type: 'POST',
                dataType: 'json',
                data: { json_data: JSON.stringify(payload) },
                success: function (res) {
                    if (res.status === 'success') {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert('Lỗi: ' + res.message);
                    }
                }
            });
        });

        function serializeMenu($list) {
            var out = [];
            $list.children('li.dd-item').each(function () {
                var $item = $(this);
                var data = {
                    label: $item.data('label'),
                    url: $item.data('url'),
                    class: $item.data('class'),
                    style: $item.data('style'),
                    block: $item.data('block'),
                    target: $item.data('target'),
                    image: $item.data('image'),
                    type: $item.data('type'),
                    object_type: $item.data('object_type'),
                    object_id: $item.data('object_id')
                };
                var $subList = $item.children('ol.dd-list');
                if ($subList.length) data.children = serializeMenu($subList);
                out.push(data);
            });
            return out;
        }

        function refreshUI() {
            var hasItems = menuEditor.find('li.dd-item').length > 0;
            $('#menu-empty').toggle(!hasItems);
        }

        // BUILD TREE TỪ DB LÚC MỚI TẢI TRANG
        var menuDataFromPHP = <?= $current_menu_items_json ?>;

        function buildMenuTree(items, $listContainer) {
            if (!items || items.length === 0) return;
            for (var i = 0; i < items.length; i++) {
                var itm = items[i];
                var $el = createMenuItem(itm);
                $listContainer.append($el);
                if (itm.children && itm.children.length > 0) {
                    var $subList = $('<ol class="dd-list"></ol>');
                    $el.append($subList);
                    buildMenuTree(itm.children, $subList);
                }
            }
        }

        if (menuDataFromPHP.length > 0) {
            buildMenuTree(menuDataFromPHP, menuEditor.find('.dd-list').first());
            refreshUI();
        }
    });
</script>
