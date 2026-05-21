<script src="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
    .menu-source,
	.menu-structure {
	    background-color: #fff;
	    border: 1px solid #c3c4c7;
	    box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
	    padding: 15px;
	}

	.menu-source {
	    /*width: 300px;*/
	    flex-shrink: 0;
	}

	.menu-structure {
	    flex-grow: 1;
	}

	h2 {
	    font-size: 1.2em;
	    margin-top: 0;
	    padding-bottom: 10px;
	    border-bottom: 1px solid #c3c4c7;
	}

	.form-group {
	    margin-bottom: 10px;
	}

	.form-group label {
	    display: block;
	    font-weight: 600;
	    margin-bottom: 5px;
	}

	.form-group input,
	.form-group select {
	    width: 100%;
	    padding: 8px;
	    box-sizing: border-box;
	    border: 1px solid #8c8f94;
	    border-radius: 2px;
	}

	.button {
	    background-color: #2271b1;
	    color: #fff;
	    border: none;
	    padding: 8px 15px;
	    border-radius: 3px;
	    cursor: pointer;
	    font-size: 14px;
	}

	.button:hover {
	    background-color: #1e639a;
	}

	/* Thanh quản lý menu */
	#menu-management-bar {
	    margin-bottom: 15px;
	    display: flex;
	    align-items: center;
	    gap: 10px;
	}

	#menu-management-bar label {
	    font-weight: 600;
	}

	#menu-management-bar select {
	    padding: 6px;
	    border: 1px solid #8c8f94;
	    border-radius: 3px;
	    background: #fff;
	}

	#menu-management-bar a {
	    color: #2271b1;
	    text-decoration: underline;
	    cursor: pointer;
	}

	#menu-management-bar a:hover {
	    color: #1e639a;
	}

	/* Language selector */
	.language-selector {
	    margin-bottom: 15px;
	    padding: 10px;
	    border: 1px solid #c3c4c7;
	    background: #f6f7f7;
	}

	.language-selector h3 {
	    margin: 0 0 10px 0;
	    font-size: 1em;
	    font-weight: 600;
	}

	.language-selector label {
	    display: block;
	    margin-bottom: 5px;
	    cursor: pointer;
	}

	.language-selector input[type="radio"] {
	    margin-right: 5px;
	}

	/* Box mục chọn (trái) */
	.source-box {
	    border: 1px solid #c3c4c7;
	    margin-bottom: 10px;
	}

	.source-box h3 {
	    margin: 0;
	    padding: 10px;
	    font-size: 1em;
	    background: #f6f7f7;
	    cursor: pointer;
	}

	.source-content {
	    padding: 10px;
	    display: none;
	}

	.source-item {
	    margin-bottom: 5px;
	}

	.source-item label {
	    cursor: pointer;
	    font-weight: 400
	}

	/* Tìm kiếm */
	.source-search {
	    margin-bottom: 10px;
	    padding: 8px;
	    border: 1px solid #8c8f94;
	    border-radius: 4px;
	    background: #fff;
	    box-shadow: inset 0 1px 2px rgba(0, 0, 0, .05);
	    transition: border-color 0.2s;
	}

	.source-search:focus {
	    border-color: #2271b1;
	    outline: none;
	    box-shadow: 0 0 5px rgba(34, 113, 177, 0.3);
	}

	/* Phân trang */
	.pagination {
	    display: flex;
	    justify-content: center;
	    align-items: center;
	    margin-top: 10px;
	    gap: 5px;
	}

	.pagination button {
	    background: #f6f7f7;
	    border: 1px solid #c3c4c7;
	    padding: 5px 10px;
	    cursor: pointer;
	    border-radius: 3px;
	    font-size: 13px;
	}

	.pagination button:disabled {
	    opacity: 0.5;
	    cursor: not-allowed;
	}

	.pagination span {
	    font-weight: bold;
	    font-size: 13px;
	}

	.pagination.hidden {
	    display: none;
	}

	/* Chọn tất cả */
	.select-all-wrap {
	    margin-top: 10px;
	    margin-bottom: 10px;
	}

	.select-all-wrap label {
	    font-weight: normal;
	    cursor: pointer;
	}

	/* Nestable UI */
	.dd {
	    max-width: 100%;
	}

	.dd .dd-empty {
	    display: none !important;
	}

	.dd-handle {
	    display: flex;
	    justify-content: space-between;
	    align-items: center;
	    margin: 5px 0;
	    padding: 10px 15px;
	    color: #1d2327;
	    background: #fff;
	    border: 1px solid #c3c4c7;
	    border-radius: 3px;
	    cursor: move;
	    position: relative;
	}

	.dd-handle:hover {
	    background: #f6f7f7;
	}

	.handle-left {
	    display: flex;
	    align-items: center;
	    gap: 10px;
	}

	.item-title {
	    font-weight: 600;
	}

	.item-type {
	    font-size: 0.9em;
	    color: #646970;
	}

	.handle-actions {
	    display: flex;
	    align-items: center;
	    gap: 6px;
	}

	.handle-actions .icon-btn {
	    background: none;
	    border: 1px solid transparent;
	    color: #2271b1;
	    padding: 6px;
	    border-radius: 4px;
	    cursor: pointer;
	}

	.handle-actions .icon-btn:hover {
	    background: #e9eff6;
	    border-color: #d0e0f0;
	}

	.handle-actions .icon-btn i {
	    font-size: 14px;
	}

	.item-settings {
	    display: none;
	    padding: 15px;
	    background: #f6f7f7;
	    border: 1px solid #c3c4c7;
	    border-top: none;
	    margin-top: -6px;
	    margin-bottom: 5px;
	}

	.item-actions {
	    margin-top: 15px;
	    display: flex;
	    justify-content: flex-end;
	    align-items: center;
	}

	.item-actions .link-btn {
	    color: #2271b1;
	    text-decoration: underline;
	    cursor: pointer;
	    padding: 6px 10px;
	}

	.item-actions .link-btn:hover {
	    color: #1e639a;
	}

	.item-actions .link-btn.danger {
	    color: #b32d2e;
	}

	.item-actions .link-btn.danger:hover {
	    color: #d63638;
	}

	/* Placeholder khi rỗng */
	.menu-empty {
	    margin-top: 8px;
	    padding: 15px;
	    color: #666;
	    text-align: center;
	    background: #fafafa;
	    border: 1px dashed #ccc;
	    display: none;
	}

	.menu-locations {
	    margin-top: 16px;
	}

	.menu-locations label {
	    display: block;
	    margin-bottom: 5px;
	}

	.menu-footer {
	    margin-top: 20px;
	    display: flex;
	    justify-content: space-between;
	    gap: 10px;
	}

	.menu-footer-left {
	    display: flex;
	    align-items: center;
	    gap: 1rem;
	}

	.menu-footer-left a {
	    text-decoration: underline;
	}

	.menu-footer-left a#delete-menu {
	    color: #d63638;
	}

	.menu-footer-right {
	    margin-left: auto;
	}

	#menu-output{
		display: none;
	}

  </style>
<div class="content-wrapper">
	<section class="content-header">
		<h1>Menu</h1>
		<ol class="breadcrumb">
			<li><a href="<?=urladmin?>"><i class="fa fa-dashboard"></i>Quản trị giao diện</a></li>
			<li class="active">Menu</li>
		</ol>
	</section>
	<!-- Main content -->
    <section class="content">
        <div class="box box-primary">
            <div class="box-body">
            	<div class="row">
            		<div class="col-md-3">
						<!-- Cột trái: nguồn menu -->
						<div class="menu-source">
							<h2>Thêm mục menu</h2>
							<!-- Language selector -->
							<?php if (count(get_json('lang')) > 1): ?>
							<div class="language-selector">
								<h3>Ngôn ngữ</h3>
								<?php foreach (get_json('lang') as $key => $lg): ?>
									<label><input type="radio" name="lang" value="<?= $lg['code'] ?>" <?= $key == 0 ? 'checked' : '' ?>> <?= $lg['name'] ?></label>
								<?php endforeach ?>
								<label><input type="radio" name="lang" value="all"> Tất cả</label>
							</div>
							<?php endif ?>
							<!-- Box Danh mục sản phẩm -->
							<?php foreach ($menu_sources as $key => $source): ?>
								<div class="source-box" data-title="<?= $source['title'] ?? $key ?>">
									<h3><?= $source['title'] ?></h3>
									<div class="source-content">
										<input type="text" class="source-search" placeholder="Tìm kiếm danh mục...">
										<div class="source-items">
											<?php 
								                if (!empty($source['items'])) {
								                    echo renderMenuSourceItems($source['items']); 
								                }
								            ?>
										</div>
										<hr style="border: 0; border-top: 1px solid #c3c4c7; margin: 10px 0;">
										<button class="button add-selected">Thêm vào menu</button>
										<div class="select-all-wrap">
											<label><input type="checkbox" class="select-all"> Chọn tất cả</label>
										</div>
										<div class="pagination">
											<button class="prev">Prev</button>
											<span class="current-page">1 / 2</span>
											<button class="next">Next</button>
										</div>
									</div>
								</div>
							<?php endforeach ?>
							<!-- Box Liên kết tùy chỉnh -->
							<div class="source-box">
								<h3>Liên kết tùy chỉnh</h3>
								<div class="source-content">
									<div class="form-group">
										<label for="c-menu-item-url">URL</label>
										<input type="text" id="c-menu-item-url" placeholder="https://..." value="#">
									</div>
									<div class="form-group">
										<label for="c-menu-item-name">Tên đường dẫn</label>
										<input type="text" id="c-menu-item-name" placeholder="Tên menu">
									</div>
									<button type="button" id="add-custom-link" class="button">Thêm vào menu</button>
								</div>
							</div>
						</div>            			
            		</div>
            		<div class="col-md-9">
						<!-- Cột phải: cấu trúc -->
						<div class="menu-structure">
							<div id="menu-management-bar">
								<label for="menu-selector">Chọn menu để sửa:</label>
								<select id="menu-selector">
									<?php foreach ($menus as $key => $value): ?>
										<option value="<?= $value['id'] ?>" <?= $value['id'] == $current_menu_id ? 'selected' : '' ?>><?= $value['name'] ?></option>
									<?php endforeach ?>
								</select>
								<span>hoặc <a href="#" id="create-new-menu">tạo menu mới</a>.</span>
							</div>
							<h2>Cấu trúc menu</h2>
							<!-- Tên menu -->
							<div class="form-group">
								<label for="menu-name">Tên menu</label>
								<input type="text" id="menu-name" placeholder="Nhập tên menu" value="<?= $current_menu['name'] ?>">
							</div>
							<!-- Khu vực kéo thả -->
							<div class="dd" id="menu-editor">
							<ol class="dd-list"></ol>
						</div>
						<div id="menu-empty" class="menu-empty">Chưa có mục nào trong menu</div>
						<!-- Vị trí menu (checkbox, có thể nhiều vị trí) -->
						<div class="menu-locations">
						    <h3>Vị trí Menu</h3>
						    <?php 
						    	$active_langs_config = get_json('lang');
						    	$count_lang = count($active_langs_config);
						    ?>

						    <?php foreach ($menu_location as $key => $value): ?>
						        <?php 
						            $is_active_lang = false; 
						            $current_lang_info = [];

						            foreach ($active_langs_config as $lang_item) {
						                if ($value['lang'] === $lang_item['code']) {
						                    $is_active_lang = true;
						                    $current_lang_info = $lang_item;
						                    break;
						                }
						            }

						            if (!$is_active_lang) {
						                continue;
						            }
						        ?>
						        
						        <label>
						            <input type="checkbox" class="menu-location" value="<?= $value['location_name'].'_'.$value['lang'] ?>" <?= $value['menu_id'] == $current_menu_id ? 'checked' : '' ?>>
						            <?php 
						                echo $value['location_label'];					                
						                if ($count_lang > 1) {
						                    echo ' (' . $current_lang_info['name'] . ')';
						                }
						            ?>
						        </label>
						    <?php endforeach ?>
						</div>
						<!-- Footer -->
						<div class="menu-footer">
							<div class="menu-footer-left">
								<button type="button" id="delete-selected" class="button" style="background:#b32d2e;">Xóa mục đã chọn</button>
								<a href="" id="select-all-items">Chọn tất cả</a>
								<a href="" id="delete-menu">Xóa menu</a>
							</div>
							<div class="menu-footer-right">
								<button type="button" id="save-menu-button" class="button">Lưu Menu</button>
							</div>
						</div>
						<br>
						<textarea id="menu-output" spellcheck="false" style="width:100%;height:200px;"></textarea>
						</div>
            			<!-- Template menu item -->
						<template id="menu-item-template">
							<li class="dd-item">
								<div class="dd-handle">
									<div class="handle-left">
										<input type="checkbox" class="item-select">
										<span class="item-title"></span>
										<span class="item-type">Tùy chỉnh</span>
									</div>
									<div class="handle-actions">
										<button class="icon-btn dd-expand hidden" title="Mở nhánh"><i class="fa-solid fa-plus"></i></button>
										<button class="icon-btn dd-collapse hidden" title="Thu gọn nhánh"><i class="fa-solid fa-minus"></i></button>
										<button class="icon-btn item-toggle" title="Cài đặt"><i class="fa-solid fa-gear"></i></button>
									</div>
								</div>
								<div class="item-settings">
									<div class="form-group">
										<label>Tiêu đề</label>
										<input type="text" class="item-input" data-name="label" placeholder="Tên hiển thị">
									</div>
									<div class="form-group">
										<label>URL</label>
										<input type="text" class="item-input" data-name="url" placeholder="https://...">
									</div>
									<div class="form-group">
										<label>Class tùy chỉnh</label>
										<input type="text" class="item-input" data-name="class" placeholder="vd: menu-item--custom">
									</div>
									<!-- Kiểu menu (theo từng item) -->
									<div class="form-group">
										<label>Kiểu menu</label>
										<select class="item-input" data-name="style">
											<option value="default">Mặc định</option>
											<!-- <option value="container">Container</option> -->
											<!-- <option value="full">Full width</option> -->
										</select>
									</div>
									<!-- Block tùy chỉnh (theo từng item) -->
									<div class="form-group">
										<label>Block tùy chỉnh</label>
										<select class="item-input" data-name="block">
											<option value="">-- Không chọn --</option>
											<!-- <option value="block-client">block-client</option> -->
											<!-- <option value="block-review">block-review</option> -->
											<!-- <option value="block-banner">block-banner</option> -->
										</select>
									</div>
									<!-- Target (theo từng item) -->
									<div class="form-group">
										<label>Target</label>
										<select class="item-input" data-name="target">
											<option value="_self">Mở trong cùng tab</option>
											<option value="_blank">Mở trong tab mới</option>
											<option value="_parent">Mở trong frame cha</option>
											<option value="_top">Mở trong toàn bộ cửa sổ</option>
										</select>
									</div>
									<!-- Ảnh (để sẵn hidden + link theo CMS của bạn) -->
									<div class="form-group">
									    <label>Ảnh</label>
									    <input type="hidden" class="item-input item-image-input" data-name="image" value="">
									    <a href="#" class="btn btn-upload2 iframe-btn item-image-picker" style="display: inline-block; margin-top: 5px;">
									       <i class="fa fa-upload"></i> Chọn hình ảnh
									    </a>
									    <div class="image-preview" style="margin-top: 10px; max-width: 200px;">
									        <img src="img/no-image.png" style="width: 100%;" class="item-image-preview">
									    </div>
									</div>
									<div class="item-actions">
										<a class="link-btn move-up">Lên 1 cấp</a>
										<a class="link-btn move-down">Xuống 1 cấp</a>
										<a class="link-btn item-cancel">Hủy</a>
										<a class="link-btn danger item-remove">Xóa</a>
									</div>
								</div>
							</li>
						</template>
            		</div>
            	</div>
            </div>
        </div>
    </section>
</div>

<script>
    $(function () {
      	var menuEditor = $('#menu-editor');
      	var uidCounter = 0;

      	const MENU_FIELDS = {
      		label: { 
      			selector: 'input[data-name="label"]',
      			default: ''
      		},
			url: {
				selector: 'input[data-name="url"]',
				default: '#'
			},
			class: {
				selector: 'input[data-name="class"]',  default: '' },
			image: { selector: '.item-image-input', default: '' },
			style: { selector: 'select[data-name="style"]', default: 'default' },
			block: { selector: 'select[data-name="block"]', default: '' },
			target: { selector: 'select[data-name="target"]',default: '_self' } 
		};

      	function generateId() {
        	uidCounter++;
        	return 'item-' + Date.now() + '-' + uidCounter;
      	}

      	// Khởi tạo Nestable — không tự render expand/collapse
      	menuEditor.nestable({ maxDepth: 5, expandBtnHTML: '', collapseBtnHTML: '' });

      	// Ngăn kéo-thả khi tương tác trong settings & nút
      	menuEditor.on('mousedown touchstart', '.handle-actions button, .item-select, .item-settings, .item-settings *', function (e) {
        	e.stopPropagation();
      	});

      	// ====== TẠO ITEM ======
      	function createMenuItem(options = {}) {

		    // 1. Lấy ID (từ options nếu là Tải, hoặc tạo mới nếu là Thêm)
		    const newId = options.id || generateId();
		    const $tpl = $($('#menu-item-template').html());

		    // 2. Tạo dataToStore (kết hợp options và defaults)
		    var dataToStore = {
		        id: newId,
		        // Metadata (không có trong MENU_FIELDS)
		        type: options.type || 'Liên kết tùy chỉnh',
		        object_type: options.object_type || 'custom',
		        object_id: options.object_id || null,
		        lang: options.lang || 'vi'
		    };

		    // 3. Dùng MENU_FIELDS để gán các trường cài đặt
		    Object.keys(MENU_FIELDS).forEach(key => {
		        dataToStore[key] = (options[key] !== undefined) ? options[key] : MENU_FIELDS[key].default;
		    });

		    // 4. Gán data vào thẻ <li>
		    $tpl.attr('data-id', newId).data(dataToStore);

		    // 5. Fill UI (Tiêu đề, Loại)
		    $tpl.find('.item-title').text(dataToStore.label || '(Không có tiêu đề)');
		    $tpl.find('.item-type').text(dataToStore.type);

		    // 6. Fill UI (Tất cả các trường trong form cài đặt)
		    Object.keys(MENU_FIELDS).forEach(key => {
		        const field = MENU_FIELDS[key];
		        $tpl.find(field.selector).val(dataToStore[key]);
		    });

		    // 7. Xử lý Image Preview (nếu là Tải menu)
		    if (dataToStore.image) {
		        $tpl.find('.item-image-preview').attr('src', '../img_data/images/' + dataToStore.image);
		    }

		    // 8. Xử lý Filemanager ID (theo quy ước của bạn)
		    var unique_suffix = 'menu_' + newId;
		    var hiddenInputId = 'hinh_anh_' + unique_suffix;
		    var previewImageId = 'review_hinh_anh_' + unique_suffix;

		    $tpl.find('.item-image-input').attr('id', hiddenInputId);
		    $tpl.find('.item-image-preview').attr('id', previewImageId);
		    $tpl.find('.item-image-picker').attr(
		        'href','filemanager/dialog.php?type=1&field_id=' + encodeURIComponent(hiddenInputId) + '&relative_url=1&multiple=0'
		    );

		    // 9. Kích hoạt Fancybox (cho item này)
		    $tpl.find('.iframe-btn').fancybox({
		        type: 'iframe',
		        autoScale: false,
		        afterClose: function () {
		            // Tự động trigger 'change' để lưu data-image
		            var href = this.href || $(this.element).attr('href');
		            if (!href) return;
		            var params = new URLSearchParams(href.split('?')[1]);
		            var field_id = params.get('field_id');
		            if (field_id) {
		                jQuery('#' + field_id).trigger('change');
		            }
		        }
		    });
		    console.log(dataToStore);
		    // 10. Trả về $tpl (Không append, không refreshUI)
		    return $tpl;
		}

     	// ====== NGUỒN BÊN TRÁI ======
     	$('.source-box h3').click(function () {
        	$(this).next('.source-content').slideToggle();
      	});

      	$('#add-custom-link').click(function () {
			var label = $('#c-menu-item-name').val().trim();
			var url = $('#c-menu-item-url').val().trim() || '#';
			if (!label) return alert('Vui lòng nhập tên đường dẫn.');

	    	// TẠO MỘT OBJECT TÙY CHỌN (options)
		    var itemOptions = {
		        label: label,
		        url: url,
		        type: 'Liên kết tùy chỉnh',
		        object_type: 'custom',
		        object_id: null
		    };

	    	// Gọi hàm createMenuItem với object này
	    	var $tpl = createMenuItem(itemOptions);

	    	// Append và Refresh (Vì createMenuItem giờ trả về $tpl)
	    	menuEditor.find('.dd-list').first().append($tpl);
	    	refreshUI();

	    	// Reset form (Code này của bạn đã đúng)
			$('#c-menu-item-name').val('');
			$('#c-menu-item-url').val('#');
		});

      	$('.add-selected').click(function () {
		    var $box = $(this).closest('.source-box');
		    var $itemsList = menuEditor.find('.dd-list').first();

		    // Chỉ lấy các checkbox trong .source-items, loại bỏ .select-all
		    $box.find('.source-items input[type="checkbox"]:checked').each(function () {
		        var $cb = $(this); // Biến đại diện cho checkbox

		        // Tạo một object tùy chọn (options)
		        var itemOptions = {
		            label: $cb.data('label'),
		            url: $cb.data('url'),
		            lang: $cb.data('lang'),
		            type: $cb.data('type'),
		            object_type: $cb.data('object-type'),
		            object_id: $cb.data('object-id')
		        };

		        // Gọi hàm createMenuItem với object này
		        var $tpl = createMenuItem(itemOptions);

		        // Append (vì createMenuItem không tự append)
		        $itemsList.append($tpl);

		        // Bỏ check (giữ nguyên)
		        $cb.prop('checked', false);
		    });

		    // Reset checkbox "Chọn tất cả" (giữ nguyên)
		    $box.find('.select-all').prop('checked', false);

		    // Refresh UI (quan trọng: gọi 1 lần sau khi vòng lặp kết thúc)
		    refreshUI();
		});

      // ====== TÌM KIẾM, PHÂN TRANG VÀ CHỌN TẤT CẢ THEO NGÔN NGỮ ======
      $('.source-box:not(:last-child) .source-content').each(function () {
        var $content = $(this);
        var $search = $content.find('.source-search');
        var $selectAll = $content.find('.select-all');
        var $itemsContainer = $content.find('.source-items');
        var $allItems = $itemsContainer.children('.source-item').detach(); // Lấy tất cả items gốc
        var $pagination = $content.find('.pagination');
        var $prev = $pagination.find('.prev');
        var $next = $pagination.find('.next');
        var $currentPage = $pagination.find('.current-page');
        var itemsPerPage = 12; // Số item mỗi trang
        var currentPage = 1;
        var filteredItems = $allItems; // Ban đầu là tất cả
        var selectedLang = $('input[name="lang"]:checked').val(); // Ngôn ngữ mặc định

        function updatePagination() {
          // Lọc theo ngôn ngữ
          var langFilteredItems = selectedLang === 'all' 
            ? $allItems 
            : $allItems.filter(function () {
                return $(this).find('input').data('lang') === selectedLang;
              });

          // Lọc theo tìm kiếm
          var query = $search.val().toLowerCase();
          filteredItems = langFilteredItems.filter(function () {
            return $(this).text().toLowerCase().includes(query);
          });

          var totalItems = filteredItems.length;
          var totalPages = Math.ceil(totalItems / itemsPerPage);
          $currentPage.text(currentPage + ' / ' + (totalPages || 1));

          // Ẩn/hiện phân trang dựa trên số lượng mục
          if (totalItems <= itemsPerPage) {
            $pagination.addClass('hidden');
          } else {
            $pagination.removeClass('hidden');
            $prev.prop('disabled', currentPage === 1);
            $next.prop('disabled', currentPage === totalPages || totalPages === 0);
          }

          // Hiển thị items cho trang hiện tại
          var start = (currentPage - 1) * itemsPerPage;
          var end = start + itemsPerPage;
          $itemsContainer.empty().append(filteredItems.slice(start, end));

          // Cập nhật trạng thái "Chọn tất cả" dựa trên các checkbox hiển thị
          var $visibleCheckboxes = $itemsContainer.find('input[type="checkbox"]');
          $selectAll.prop('checked', $visibleCheckboxes.length > 0 && $visibleCheckboxes.filter(':checked').length === $visibleCheckboxes.length);
        }

        // Tìm kiếm
        $search.on('keyup', function () {
          currentPage = 1; // Reset về trang 1
          updatePagination();
        });

        // Phân trang nút
        $prev.click(function () {
          if (currentPage > 1) {
            currentPage--;
            updatePagination();
          }
        });

        $next.click(function () {
          var totalPages = Math.ceil(filteredItems.length / itemsPerPage);
          if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
          }
        });

        // Chọn tất cả
        $selectAll.on('change', function () {
          var isChecked = $(this).prop('checked');
          $itemsContainer.find('input[type="checkbox"]').prop('checked', isChecked);
        });

        // Cập nhật "Chọn tất cả" khi checkbox riêng thay đổi
        $itemsContainer.on('change', 'input[type="checkbox"]', function () {
          var $visibleCheckboxes = $itemsContainer.find('input[type="checkbox"]');
          $selectAll.prop('checked', $visibleCheckboxes.length > 0 && $visibleCheckboxes.filter(':checked').length === $visibleCheckboxes.length);
        });

        // Lọc theo ngôn ngữ
        $('input[name="lang"]').on('change', function () {
          selectedLang = $(this).val();
          currentPage = 1; // Reset về trang 1
          updatePagination();
        });

        // Khởi tạo
        updatePagination();
      });

      // ====== QUẢN LÝ MENU ======
		$('#menu-selector').on('change', function () {
		    var menuId = $(this).val();
		    if (menuId) {
		        // Tự động điều hướng trang đến URL mới với ?menu={id_menu}
		        window.location.href = "index.php?p=menu&a=man&menu=" + menuId;
		    }
		});

		$('#create-new-menu').click(function (e) {
		  e.preventDefault();
		  var name = prompt('Nhập tên menu mới:');
		  if (!name) return;

		  $.ajax({
		    url: 'sources/ajax.php',
		    type: 'POST',
		    dataType: 'json',
		    data: {
	        	do: 'create_menu',
	        	name: name,
	        },
		    success: function (res) {
		      if (res.status === 'success') {
		        const newId = res.menu_id;

		        const $selector = $('#menu-selector');
		        $selector.append('<option value="' + newId + '">' + name + '</option>');
		        $selector.val(newId);

		        $('#menu-name').val(name);

		        menuEditor.find('.dd-list').empty();
		        refreshUI();

		        alert('✅ Menu "' + name + '" đã được tạo.');

		        window.location.href = "index.php?p=menu&a=man&menu=" + newId;
		      } else {
		        alert('Lỗi: ' + res.message);
		      }
		    },
		    error: function () {
		      alert('Không thể kết nối đến máy chủ.');
		    }
		  });
		});



      // ====== CÀI ĐẶT ITEM ======
    menuEditor.on('click', '.item-toggle', function () {
        var $item = $(this).closest('.dd-item');
        var $panel = $item.children('.item-settings');
        if (!$panel.is(':visible')) {
         	// Tạo một đối tượng backup rỗng
        	var backupData = {};
        
        	// TỰ ĐỘNG SAO LƯU (lặp qua MENU_FIELDS)
        	// Thay vì viết cứng 'label', 'url', 'class'...
        	Object.keys(MENU_FIELDS).forEach(function(key) {
            	backupData[key] = $item.data(key);
        	});

        	// Lưu đối tượng backup vào .data()
			$item.data('__backup', backupData);
        }
        $panel.slideToggle(200);
    });

      // Hủy — khôi phục snapshot
      menuEditor.on('click', '.item-cancel', function () {
	    var $item = $(this).closest('.dd-item');
	    
	    // Lấy đối tượng backup (đã được tạo bởi 'item-toggle')
	    var backup = $item.data('__backup') || {};

	    // TỰ ĐỘNG KHÔI PHỤC (lặp qua MENU_FIELDS)
	    Object.keys(MENU_FIELDS).forEach(function(key) {
	        
	        // Lấy config của trường này
	        const config = MENU_FIELDS[key]; 
	        
	        // 4. Lấy giá trị:
	        // Ưu tiên giá trị backup. Nếu không có (undefined), thì dùng giá trị default
	        var value = (backup[key] !== undefined) 
	                    ? backup[key] 
	                    : config.default;

	        // Khôi phục data (lưu vào bộ nhớ jQuery)
	        $item.data(key, value);

	        // Khôi phục UI (gán .val() cho input/select)
	        $item.find(config.selector).val(value);

	        // Xử lý các UI đặc biệt (nếu có)
	        // Nếu là 'label', cập nhật cả .item-title
	        if (key === 'label') {
	            $item.find('.item-title').text(value || '(Không có tiêu đề)');
	        }
	        
	        // Nếu là 'image', cập nhật cả <img> preview
	        if (key === 'image') {
	            var $preview = $item.find('.item-image-preview');
	            if (value) {
	                $preview.attr('src', '../img_data/images/' + value);
	            } else {
	                $preview.attr('src', 'img/no-image.png');
	            }
	        }
	    });

	    // Đóng panel
	    $item.children('.item-settings').slideUp(200);
	});

      // Xóa item
      menuEditor.on('click', '.item-remove', function () {
        if (confirm('Xóa mục này?')) $(this).closest('.dd-item').remove();
        refreshUI();
      });

      // Cập nhật dữ liệu khi chỉnh form
      menuEditor.on('input change', '.item-input', function () {
        var $input = $(this);
        var key = $input.data('name');
        var val = $input.val();
        var $item = $input.closest('.dd-item');

        $item.data(key, val);
        if (key === 'label') {
          $item.find('.item-title').text(val || '(Không có tiêu đề)');
        }
        if (key === 'image') {
          var preview = val ? ('Đã chọn: ' + val) : '';
          $item.find('.image-preview').text(preview);
        }
      });

      // ====== EXPAND / COLLAPSE ổn định ======
      menuEditor.on('click', '.dd-expand', function (e) {
        e.preventDefault();
        var $item = $(this).closest('li.dd-item');
        menuEditor.nestable('expandItem', $item);
        $item.removeClass('dd-collapsed');
        refreshExpandCollapseButtons();
      });

      menuEditor.on('click', '.dd-collapse', function (e) {
        e.preventDefault();
        var $item = $(this).closest('li.dd-item');
        menuEditor.nestable('collapseItem', $item);
        refreshExpandCollapseButtons();
      });

      // ====== XÓA HÀNG LOẠT ======
      $('#delete-selected').click(function () {
        var $items = menuEditor.find('.item-select:checked').closest('.dd-item');
        if (!$items.length) return alert('Chọn ít nhất một mục.');
        if (!confirm('Xóa ' + $items.length + ' mục đã chọn?')) return;
        $items.remove();
        refreshUI();
      });

      $('#select-all-items').on('click', function (e) {
        e.preventDefault();
        
        var $checkboxes = menuEditor.find('.item-select');

        // Kiểm tra xem TẤT CẢ đã được chọn chưa
        var allChecked = $checkboxes.length > 0 && $checkboxes.length === $checkboxes.filter(':checked').length;

        // Đặt trạng thái mới (nghịch đảo của 'allChecked')
        var newState = !allChecked;

        $checkboxes.prop('checked', newState);
    });

    $('#delete-menu').click(function (e) {
		  e.preventDefault();

		  var menuId = $('#menu-selector').val();
		  if (!menuId) {
		    	alert('Vui lòng chọn menu cần xóa.');
		    	return;
		  }

		  if (!confirm('Bạn có chắc chắn muốn xóa menu này cùng toàn bộ mục của nó không?')) {
		    return;
		  }

		  $.ajax({
		    type: 'POST',
		    url: 'sources/ajax.php',
		    dataType: 'json',
		    data: { menu_id: menuId },
		    data: {
	        	do: 'delete_menu',
	        	menu_id: menuId,
	        },
		    success: function (res) {
		      if (res.status === 'success') {
		        alert('✅ Menu đã được xóa thành công.');
		        window.location.href = 'index.php?p=menu&a=man';
		      } else {
		        alert('❌ Lỗi: ' + res.message);
		      }
		    },
		    error: function () {
		      	alert('Không thể kết nối đến máy chủ.');
		    }
		  });
		});


    $('#save-menu-button').click(function () {
	    var $button = $(this);
	    
	    // 1. Thu thập dữ liệu (bạn đã làm)
	    var locations = [];
	    $('.menu-location:checked').each(function () {
	        locations.push($(this).val());
	    });

	    var menuData = {
	        id: $('#menu-selector').val(),
	        name: $('#menu-name').val().trim() || 'Menu chưa đặt tên',
	        locations: locations, // Đây là mảng ['primary_vi', 'footer_en']...
	        items: serializeMenu(menuEditor.find('.dd-list').first()) // Mảng item
	    };

    	$('#menu-output').val(JSON.stringify(menuData, null, 2));
	    // 2. Gửi bằng AJAX
	    $.ajax({
	        type: "POST",
	        url: "sources/ajax.php", // Đường dẫn tới file PHP xử lý
	        data: {
	        	do: 'save_menu',
	        	json_data: JSON.stringify(menuData)
	        }, // Gửi toàn bộ đối tượng dưới dạng JSON
	        dataType: "json",
	        beforeSend: function() {
	            $button.text('Đang lưu...').prop('disabled', true);
	        },

	        success: function(response) {
	            // Phản hồi từ PHP (ví dụ: {status: 'success', message: 'Đã lưu!'})
	            if(response.status === 'success') {
	                alert(response.message);
	                // (Tùy chọn) Cập nhật lại UI nếu cần, ví dụ load lại ID item
	            } else {
	                alert('Lỗi: ' + response.message);
	            }
	        },

	        error: function(xhr, status, error) {
	            alert("Lỗi máy chủ. Không thể lưu menu.");
	            console.error(xhr.responseText);
	        },
	        
	        complete: function() {
	             $button.text('Lưu Menu').prop('disabled', false);
	        }
	    });
	});




      // ====== SERIALIZE (đọc toàn bộ cấu trúc menu đang hiển thị (trong phần kéo-thả) và chuyển nó thành một mảng JSON)======
      function serializeMenu($list) {
	    var out = [];
	    $list.children('li.dd-item').each(function () {
	        var $item = $(this);

	        // 1. Lấy Metadata (Thủ công)
	        var data = {
	            id: $item.data('id'),
	            type: $item.data('type'),
	            object_type: $item.data('object_type'), // Sửa 'object' thành 'object_type'
	            object_id: $item.data('object_id'),
	            lang: $item.data('lang')
	        };

	        // 2. Lấy Settings (Tự động qua MENU_FIELDS)
	        Object.keys(MENU_FIELDS).forEach(function (field) {
	            var config = MENU_FIELDS[field];
	            var value;
	            if (field === 'image') {
	                // Đọc trực tiếp từ input
	                value = $item.find(config.selector).val();
	            } else {
	                value = $item.data(field);
	            }
	            // Gán giá trị, nếu rỗng thì dùng default
	            data[field] = (value !== undefined && value !== null) ? value : config.default;
	        });

	        // 3. Xử lý children
	        var $sub = $item.children('ol.dd-list');
	        if ($sub.length && $sub.children('li.dd-item').length) {
	            data.children = serializeMenu($sub);
	        }
	        out.push(data);
	    });
	    return out;
	}

      // ====== LÊN/XUỐNG 1 VỊ TRÍ ======
      function moveUpOne($item) {
        var $prev = $item.prev('.dd-item');
        if ($prev.length) {
          $item.insertBefore($prev);
        } else {
          var $parentItem = $item.parent().closest('.dd-item');
          if ($parentItem.length) {
            $item.insertBefore($parentItem);
          }
        }
        menuEditor.trigger('change');
      }

      function moveDownOne($item) {
        var $next = $item.next('.dd-item');
        if ($next.length) {
          $item.insertAfter($next);
        } else {
          var $parentItem = $item.parent().closest('.dd-item');
          if ($parentItem.length) {
            $item.insertAfter($parentItem);
          }
        }
        menuEditor.trigger('change');
      }

      menuEditor.on('click', '.move-up', function () {
        moveUpOne($(this).closest('.dd-item'));
        refreshUI();
      });

      menuEditor.on('click', '.move-down', function () {
        moveDownOne($(this).closest('.dd-item'));
        refreshUI();
      });

      // ====== REFRESH UI ======
      menuEditor.on('change', function () {
        refreshUI();
      });

      function refreshExpandCollapseButtons() {
        menuEditor.find('li.dd-item').each(function () {
          var $item = $(this);
          var $expand = $item.find('> .dd-handle .dd-expand');
          var $collapse = $item.find('> .dd-handle .dd-collapse');
          var hasChildren = $item.children('ol.dd-list').children('li.dd-item').length > 0;

          if (!hasChildren) {
            $expand.addClass('hidden');
            $collapse.addClass('hidden');
            $item.removeClass('dd-collapsed');
          } else {
            if ($item.hasClass('dd-collapsed')) {
              $expand.removeClass('hidden');
              $collapse.addClass('hidden');
            } else {
              $expand.addClass('hidden');
              $collapse.removeClass('hidden');
            }
          }
        });
      }

      function refreshUI() {
        var hasItems = menuEditor.find('li.dd-item').length > 0;
        $('#menu-empty').toggle(!hasItems);
        refreshExpandCollapseButtons();
      }

      // ====== DEMO MẪU ======
      // 1. Lấy dữ liệu JSON từ PHP
var menuDataFromPHP = <?= $current_menu_items_json ?>;

/**
 * 2. HÀM TẢI MENU (PHIÊN BẢN ĐÚNG)
 * Hàm này rất đơn giản, nó chỉ lặp và gọi createMenuItem
 */
function buildMenuTree(items, $listContainer) {
 if (!items || items.length === 0) return;

 for (var i = 0; i < items.length; i++) {
 var item = items[i]; // item từ JSON chính là 'options'
 
 // 1. Chỉ cần gọi createMenuItem. 
        // Nó sẽ tự động làm TẤT CẢ mọi việc:
 // - Gán data (label, url, class, image, object_type...)
 // - Gán UI (điền vào form)
 // - Gán ID ảnh và Href
 // - Kích hoạt FancyBox (với 'afterClose' đã sửa lỗi)
 var $tpl = createMenuItem(item); 
 
 // 2. Append $tpl vào đúng nơi
 $listContainer.append($tpl);

// 3. Đệ quy (nếu có con)
if (item.children && item.children.length > 0) {
			 var $subList = $('<ol class="dd-list"></ol>');
			 $tpl.append($subList);
			 buildMenuTree(item.children, $subList);
				}
}
}

// 3. BẮT ĐẦU TẢI MENU KHI MỞ TRANG
try {
    var $rootList = menuEditor.find('.dd-list').first();
	buildMenuTree(menuDataFromPHP, $rootList);
	refreshUI(); // Cập nhật UI (nút expand/collapse, thông báo rỗng)
} catch (e) {
    console.error("Lỗi khi tải và xây dựng menu:", e);
}
    });
  </script>