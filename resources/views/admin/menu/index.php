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
	.menu-selector { display: flex; align-items: center; gap: 10px; margin-left: auto; }
	.menu-name-input { font-size: 16px; padding: 5px 10px; max-width: 300px; border: 1px solid #ddd; border-radius: 4px;}

	.dd { max-width: 100%; }
	.dd-handle { display: flex; justify-content: space-between; align-items: center; height: 40px; padding: 5px 10px 5px 35px; background: #fafafa; border: 1px solid #ccc; color: #333; font-weight: bold; border-radius: 4px; font-size: 14px; margin: 0; }
	.dd-item{ margin-top: 4px; }
    .dd-item > button { position: absolute; left: 5px; top: 10px; z-index: 10; margin: 0; padding: 0; }
	.handle-left { display: flex; align-items: center; gap: 10px; }
	.item-type { font-size: 12px; color: #666; font-weight: normal; }
	.handle-actions { display: flex; align-items: center; gap: 5px; }
	.handle-actions button { background: none; border: none; cursor: pointer; color: #666; padding: 5px; }
	.handle-actions button:hover { color: #000; }

	.item-settings { display: none; padding: 15px; border: 1px solid #ccc; border-top: none; background: #fff; border-radius: 0 0 4px 4px; }
	.settings-actions { margin-top: 15px; display: flex; align-items: center; gap: 8px; font-size: 14px; }
	.item-remove { color: #dc3232; cursor: pointer; text-decoration: underline; }
	.item-cancel { color: #0073aa; cursor: pointer; text-decoration: underline; }

	.dd-empty {
		margin: 15px 0; 
		padding: 40px 20px; 
		color: #6c757d; 
		text-align: center; 
		background: #f8f9fa; 
		border: 2px dashed #ced4da; 
		border-radius: 8px; 
		font-size: 14px;
		min-height: 100px;
		display: flex;
		align-items: center;
		justify-content: center;
		background-image: none !important;
	}
	.dd-empty::before {
		content: "Chưa có mục nào. Hãy kéo thả các mục từ cột bên trái vào đây!";
		display: block;
	}
	.menu-locations label {
        display: block;
        margin-bottom: 5px;
        font-weight: normal;
    }
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
                    <h3 class="mb-3 fs-6">Ngôn ngữ</h3>
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
                <div class="source-box source-ajax" data-type="product" data-title="Sản phẩm">
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
                <div class="source-box source-ajax" data-type="post" data-title="Bài viết">
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
                    <label class="mb-0" style="font-size: 14px;">Chọn menu để sửa:</label>
                    <select id="menu-selector" class="form-select form-select-sm d-inline-block" style="width: auto;">
                        <?php foreach ($menus as $m): ?>
                            <option value="<?= $m->id ?>" <?= $m->id == $current_menu_id ? 'selected' : '' ?>><?= htmlspecialchars($m->name) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button type="button" class="btn btn-secondary btn-sm" id="select-menu-btn">Chọn</button>
                    <span style="font-size: 14px;">hoặc</span>
                    <a href="#" id="create-new-menu" class="text-primary" style="font-size: 14px;">tạo menu mới</a>
                </div>
            </div>

            <div class="card-body">
                <?php if ($current_menu_id > 0): ?>
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <label style="font-weight:bold; margin-bottom:0; white-space:nowrap;">Tên menu</label>
                        <input type="text" id="menu-name" class="menu-name-input form-control form-control-sm" value="<?= htmlspecialchars($current_menu->name) ?>">
                    </div>

                    <div class="border rounded-2 p-3">
                        <h3 class="mb-2 fs-6">Sắp xếp liên kết</h3>
                        <p class="text-secondary" style="font-size: 14px;">Kéo thả từng mục để sắp xếp thứ tự. Kéo sang phải để tạo menu con.</p>
                        
                        <div class="dd" id="menu-editor">
                            <ol class="dd-list"></ol>
                        </div>

                        <div class="menu-locations bg-light border rounded-2 mt-4 p-3">
                            <h3 class="mb-3 fs-6">Vị trí hiển thị</h3>
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
                                <label class="menu-location-item" data-lang="<?= $value->lang ?>" style="display: block;">
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

                        <div class="menu-footer d-flex justify-content-between gap-2 border-top mt-4 pt-3">
                            <div class="d-flex gap-3">
                                <a href="#" id="delete-selected-items" class="text-danger">Xóa mục đã chọn</a>
                                <a href="#" id="delete-menu" class="text-danger">Xóa menu</a>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="save-menu-button">Lưu Menu</button>
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
            <p style="font-size:12px;">Các cài đặt nâng cao cho mục menu này.</p>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Tiêu đề</label>
                    <input type="text" class="form-control form-control-sm item-input" data-name="label">
                </div>
                <div class="col-md-6 mb-2 item-url-col">
                    <label class="form-label" style="font-size: 12px;">URL</label>
                    <input type="text" class="form-control form-control-sm item-input" data-name="url">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Class tùy chỉnh</label>
                    <input type="text" class="form-control form-control-sm item-input" data-name="class" placeholder="vd: menu-item--custom">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Thuộc tính Rel</label>
                    <select class="form-control form-control-sm item-input" data-name="rel">
                        <option value="">-- Trống --</option>
                        <option value="nofollow">nofollow</option>
                        <option value="noopener">noopener</option>
                        <option value="noreferrer">noreferrer</option>
                        <option value="sponsored">sponsored</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Kiểu menu</label>
                    <select class="form-control form-control-sm item-input" data-name="style">
                        <option value="default">Mặc định</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Block tùy chỉnh</label>
                    <select class="form-control form-control-sm item-input" data-name="block">
                        <option value="">-- Không chọn --</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Target</label>
                    <select class="form-control form-control-sm item-input" data-name="target">
                        <option value="_self">Cửa sổ hiện tại</option>
                        <option value="_blank">Cửa sổ mới (_blank)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label" style="font-size: 12px;">Ảnh</label>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control item-input item-image-input" data-name="image" placeholder="Đường dẫn ảnh...">
                        <button type="button" class="btn btn-outline-secondary item-image-picker" title="Chọn hình ảnh">
                            <i class="fa fa-upload"></i>
                        </button>
                    </div>
                    <div class="image-preview" style="max-width: 100px;">
                        <img src="/admin/img/no-image.png" class="item-image-preview w-100 border rounded-2" onerror="this.src='/admin/img/no-image.png'">
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
<?php if (!defined('CKFINDER_SCRIPT_LOADED')): ?>
    <?php define('CKFINDER_SCRIPT_LOADED', true); ?>
    <script src="/assets/admin/ckfinder/ckfinder.js"></script>
<?php endif; ?>
<script>
    $(document).ready(function () {
        var menuEditor = $('#menu-editor');
        if (!menuEditor.length) return;

        var idCounter = 1000;
        function generateId() { return idCounter++; }

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
            var currentLang = $(this).val();

            // Lọc Ajax Source (luôn load lại tất cả hộp)
            $('.source-box.source-ajax').each(function() {
                loadAjaxSource($(this));
            });

            // Lọc menu location
            if (currentLang === 'all') {
                $('.menu-location-item').show();
            } else {
                $('.menu-location-item').hide();
                $('.menu-location-item[data-lang="' + currentLang + '"]').show();
            }
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

        // [HƯỚNG DẪN THÊM FIELD MỚI CHO MENU ITEM]
        // Để thêm 1 trường dữ liệu mới (ví dụ 'badge'):
        // 1. Thêm tên trường 'badge' vào mảng MENU_FIELDS bên dưới.
        // 2. Thêm HTML: <input class="item-input" data-name="badge"> vào bên trong <template id="menu-item-template">.
        // 3. Trong DB: Thêm cột `badge` vào bảng `menu_items`.
        // 4. Trong Model: Thêm 'badge' vào $allowedFields của MenuItemModel (nếu có dùng).
        // 5. Trong MenuService.php -> flattenMenuTree(): Thêm `'badge' => $item['badge'] ?? ''` vào mảng $itemData.
        const MENU_FIELDS = ['label', 'url', 'class', 'rel', 'style', 'block', 'target', 'image', 'type', 'object_type', 'object_id'];

        function createMenuItem(options = {}) {
            const newId = options.id || generateId();
            const $tpl = $($('#menu-item-template').html());
            $tpl.attr('data-id', newId);

            var typeName = options.type || 'Tùy chỉnh';
            if (options.lang && options.lang !== 'vi') typeName += ' (' + options.lang + ')';

            $tpl.find('.item-title').text(options.label || 'Mục chưa đặt tên');
            $tpl.find('.item-type').text(typeName);

            MENU_FIELDS.forEach(function (f) {
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
            $tpl.find('.item-image-picker').on('click', function() {
                CKFinder.modal({
                    chooseFiles: true,
                    width: 800,
                    height: 600,
                    onInit: function(finder) {
                        finder.on('files:choose', function(evt) {
                            var file = evt.data.files.first();
                            var url = file.getUrl();
                            $tpl.find('.item-image-input').val(url).trigger('change');
                            $tpl.find('.item-image-preview').attr('src', url);
                        });
                        finder.on('file:choose:resizedImage', function(evt) {
                            var url = evt.data.resizedUrl;
                            $tpl.find('.item-image-input').val(url).trigger('change');
                            $tpl.find('.item-image-preview').attr('src', url);
                        });
                    }
                });
            });

            return $tpl;
        }

        $('#add-custom-link').click(function () {
            var label = $('#c-menu-item-name').val().trim();
            var url = $('#c-menu-item-url').val().trim() || '#';
            if (!label) return AppNotify.warning('Vui lòng nhập tên đường dẫn.');
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
                            AppNotify.error('Lỗi: ' + res.message);
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
            var $btn = $(this);
            AppNotify.confirm('Bạn có chắc chắn muốn xóa mục này khỏi cấu trúc menu không?', function() {
                $btn.closest('.dd-item').remove();
                refreshUI();
            });
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
            AppNotify.confirm('Bạn có chắc chắn muốn xóa menu này không? (Bao gồm tất cả liên kết bên trong)', function() {
                $.ajax({
                    url: '<?= route('admin.menu.delete') ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: { menu_id: menuId },
                    success: function (res) {
                        if (res.status === 'success') {
                            window.location.href = '<?= route('admin.menu.index') ?>';
                        } else {
                            AppNotify.error('Lỗi: ' + res.message);
                        }
                    }
                });
            });
        });

        $('#delete-selected-items').click(function(e) {
            e.preventDefault();
            var $checked = menuEditor.find('.item-select:checked');
            if ($checked.length === 0) return AppNotify.warning('Chưa chọn mục nào để xóa.');
            AppNotify.confirm('Bạn có chắc chắn muốn xóa ' + $checked.length + ' mục đã chọn khỏi menu?', function() {
                $checked.closest('.dd-item').remove();
                refreshUI();
            });
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
                        AppNotify.success(res.message);
                    } else {
                        AppNotify.error('Lỗi: ' + res.message);
                    }
                }
            });
        });

        function serializeMenu($list) {
            var out = [];
            $list.children('li.dd-item').each(function () {
                var $item = $(this);
                var data = {};
                
                // Tự động map dữ liệu từ mảng cấu hình chung
                MENU_FIELDS.forEach(function(f) {
                    data[f] = $item.data(f);
                });

                var $subList = $item.children('ol.dd-list');
                if ($subList.length) data.children = serializeMenu($subList);
                out.push(data);
            });
            return out;
        }

        function refreshUI() {
            var hasItems = menuEditor.find('li.dd-item').length > 0;
            if (hasItems) {
                // Xóa thẻ .dd-empty do Nestable tự sinh ra nếu có item
                menuEditor.find('> .dd-empty').remove();
            } else {
                // Thêm lại thẻ .dd-empty nếu không có item nào
                if (menuEditor.find('> .dd-empty').length === 0) {
                    menuEditor.append('<div class="dd-empty"></div>');
                }
            }
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

        menuEditor.nestable({ maxDepth: 5, callback: function () { refreshUI(); } });

        // Lần đầu tải trang: Tự động tải AJAX cho các hộp dữ liệu để có sẵn kết quả
        $('.source-box.source-ajax').each(function() {
            loadAjaxSource($(this));
        });
    });
</script>