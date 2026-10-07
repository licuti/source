/**
 * menu-builder.js
 * Nestable menu editor — logic gọn, dễ bảo trì.
 *
 * Cần: jQuery, nestable2, (tuỳ chọn) AdminUI, fancybox
 * Data: window.MENU_INITIAL_ITEMS (JSON từ PHP)
 * Indent: tab (hiển thị width 4)
 */
(function ($) {
	'use strict';

	if (!$('#menu-editor').length) {
		return;
	}

	/* ===== Helpers ===== */
	function notify(type, msg) {
		if (window.AdminUI && AdminUI.notify && AdminUI.notify[type]) {
			AdminUI.notify[type](msg);
		} else {
			alert(msg);
		}
	}

	function confirmAsk(opts) {
		opts = opts || {};
		if (window.AdminUI && AdminUI.confirm && AdminUI.confirm.ask) {
			return AdminUI.confirm.ask({
				title: opts.title || 'Xác nhận',
				message: opts.message || '',
				icon: opts.icon || 'warning',
				danger: !!opts.danger,
				confirmText: opts.confirmText || 'Đồng ý',
				cancelText: opts.cancelText || 'Hủy'
			});
		}
		return $.Deferred(function (def) {
			def.resolve(window.confirm(opts.message || 'Bạn có chắc?'));
		}).promise();
	}

	/* ===== Schema field (1 nguồn sự thật) ===== */
	var MENU_FIELDS = {
		label:     { selector: 'input[data-name="label"]',     default: '' },
		url:       { selector: 'input[data-name="url"]',       default: '#' },
		class:     { selector: 'input[data-name="class"]',     default: '' },
		image:     { selector: '.item-image-input',            default: '' },
		style:     { selector: 'select[data-name="style"]',    default: 'default' },
		block:     { selector: 'select[data-name="block"]',    default: '' },
		target:    { selector: 'select[data-name="target"]',   default: '_self' },
		sub_label: { selector: 'input[data-name="sub_label"]', default: '' }
	};

	var menuEditor = $('#menu-editor');
	var uidCounter = 0;
	var PER_PAGE = 10;

	function generateId() {
		uidCounter += 1;
		return 'item-' + Date.now() + '-' + uidCounter;
	}

	/* ===== Panel cài đặt ===== */
	function closeAllSettings() {
		menuEditor.find('.item-settings').removeClass('is-open');
	}

	function openSettings($panel) {
		closeAllSettings();
		if ($panel && $panel.length) {
			$panel.addClass('is-open');
		}
	}

	/* ===== Nestable =====
	 * Nestable MOVE item vào .dd-dragel trên <body> (không clone).
	 * Không .remove() DOM form/settings khi kéo.
	 */
	menuEditor.nestable({
		maxDepth: 5,
		expandBtnHTML: '',
		collapseBtnHTML: '',
		onDragStart: function () {
		// Giữ panel đang mở (class is-open). Ghost ẩn form bằng CSS.
	}
	});

	/* Chặn Nestable bắt mousedown trên nút / form */
	menuEditor.on(
		'mousedown touchstart',
		'.dd-nodrag, .dd-nodrag *, .dd-expand, .dd-collapse, .item-settings, .item-settings *',
		function (e) {
			e.stopPropagation();
		}
	);

	/* ===== Tạo 1 item từ template ===== */
	function createMenuItem(options) {
		options = options || {};
		var newId = options.id || generateId();
		var $tpl = $($('#menu-item-template').html());

		var data = {
			id: newId,
			type: options.type || 'Liên kết tùy chỉnh',
			object_type: options.object_type || 'custom',
			object_id: options.object_id != null ? options.object_id : null,
			lang: options.lang || 'vi'
		};

		Object.keys(MENU_FIELDS).forEach(function (key) {
			data[key] = options[key] !== undefined ? options[key] : MENU_FIELDS[key].default;
		});

		$tpl.attr('data-id', newId).data(data);
		$tpl.find('.item-title').first().text(data.label || '(Không có tiêu đề)');
		$tpl.find('.item-type').first().text(data.type);

		Object.keys(MENU_FIELDS).forEach(function (key) {
			$tpl.find(MENU_FIELDS[key].selector).val(data[key]);
		});

		if (data.image) {
			$tpl.find('.item-image-preview').attr('src', '../media/images/' + data.image);
		}

		var suffix = 'menu_' + String(newId).replace(/[^a-zA-Z0-9_-]/g, '_');
		var hiddenId = 'hinh_anh_' + suffix;
		var previewId = 'review_hinh_anh_' + suffix;
		$tpl.find('.item-image-input').attr('id', hiddenId);
		$tpl.find('.item-image-preview').attr('id', previewId);
		$tpl.find('.item-image-picker')
			.addClass('js-fm-open iframe-btn')
			.attr({
				'href': 'ckfinder/ckfinder.html?type=Images',
				'data-fm-field': hiddenId,
				'data-fm-type': 'image',
				'data-fm-resource': 'Images',
				'data-fm-multiple': '0',
				'data-type': 'iframe'
			});

		// AdminFM (ckfinder-adapter) bat click mo CKFinder modal
		if (window.AdminFM && AdminFM.bind) {
			AdminFM.bind($tpl[0]);
		} else if ($.fn.fancybox) {
			$tpl.find('.iframe-btn').fancybox({
				type: 'iframe',
				autoScale: false
			});
		}

		return $tpl;
	}

	/* ===== Nguồn bên trái ===== */
	$('.source-box h3').on('click', function () {
		$(this).next('.source-content').slideToggle();
	});

	$('#add-custom-link').on('click', function () {
		var label = $('#c-menu-item-name').val().trim();
		var url = $('#c-menu-item-url').val().trim() || '#';
		if (!label) {
			notify('warning', 'Vui lòng nhập tên đường dẫn.');
			return;
		}

		var $tpl = createMenuItem({
			label: label,
			url: url,
			type: 'Liên kết tùy chỉnh',
			object_type: 'custom',
			object_id: null
		});
		menuEditor.find('.dd-list').first().append($tpl);
		refreshUI();
		$('#c-menu-item-name').val('');
		$('#c-menu-item-url').val('#');
	});

	$('.add-selected').on('click', function () {
		var $box = $(this).closest(".source-box");
		var $list = menuEditor.find('.dd-list').first();
		var count = 0;

		$box.find('.source-items input[type="checkbox"]:checked').each(function () {
			var $cb = $(this);
			var $tpl = createMenuItem({
				label: $cb.data('label'),
				url: $cb.data('url'),
				lang: $cb.data('lang'),
				type: $cb.data('type'),
				object_type: $cb.data('object-type'),
				object_id: $cb.data('object-id')
			});
			$list.append($tpl);
			$cb.prop('checked', false);
			count += 1;
		});

		$box.find('.select-all').prop('checked', false);
		if (count) {
			refreshUI();
		} else {
			notify('warning', 'Chọn ít nhất một mục.');
		}
	});

	/* ===== Tìm kiếm + phân trang nguồn ===== */
	$('.source-box').each(function () {
		var $box = $(this);
		var $content = $box.find('.source-content');
		if (!$content.length || $box.find("#c-menu-item-url").length) {
			return;
		}

		var $search = $box.find('.source-search');
		var $itemsContainer = $box.find('.source-items');
		var $allItems = $itemsContainer.children('.source-item');
		var $prev = $box.find('.pagination .prev');
		var $next = $box.find('.pagination .next');
		var $pageLabel = $box.find('.pagination .current-page');
		var $pager = $box.find('.pagination');
		var $selectAll = $box.find('.select-all');
		var page = 1;

		function currentLang() {
			return $('input[name="lang"]:checked').val() || 'all';
		}

		function filtered() {
			var q = ($search.val() || '').toLowerCase();
			var lang = currentLang();
			return $allItems.filter(function () {
				var $el = $(this);
				var text = $el.text().toLowerCase();
				var itemLang = String($el.find('input').data('lang') || '');
				var okLang = (lang === 'all' || itemLang === lang);
				var okQ = !q || text.indexOf(q) !== -1;
				return okLang && okQ;
			});
		}

		function renderPage() {
			var $f = filtered();
			var total = $f.length;
			var pages = Math.max(1, Math.ceil(total / PER_PAGE));
			if (page > pages) {
				page = pages;
			}

			$allItems.hide();
			$f.each(function (i) {
				if (i >= (page - 1) * PER_PAGE && i < page * PER_PAGE) {
					$(this).show();
				}
			});

			$pageLabel.text(page + ' / ' + pages);
			$prev.prop('disabled', page <= 1);
			$next.prop('disabled', page >= pages);
			$pager.toggleClass('hidden', total <= PER_PAGE);
		}

		$search.on('keyup', function () {
			page = 1;
			renderPage();
		});
		$prev.on('click', function () {
			if (page > 1) {
				page -= 1;
				renderPage();
			}
		});
		$next.on('click', function () {
			page += 1;
			renderPage();
		});
		$selectAll.on('change', function () {
			var on = this.checked;
			filtered().find('input[type="checkbox"]').prop('checked', on);
		});
		$('input[name="lang"]').on('change', function () {
			page = 1;
			renderPage();
		});

		renderPage();
	});

	/* ===== Quản lý menu ===== */
	$('#menu-selector').on('change', function () {
		var id = $(this).val();
		window.location.href = 'index.php?p=menu&a=man&menu=' + encodeURIComponent(id);
	});

	$('#create-new-menu').on('click', function (e) {
		e.preventDefault();
		var name = window.prompt('Tên menu mới:', 'Menu mới');
		if (name === null) {
			return;
		}
		name = $.trim(name);
		if (!name) {
			notify('warning', 'Vui lòng nhập tên menu.');
			return;
		}

		$.ajax({
			type: 'POST',
			url: 'sources/ajax.php',
			dataType: 'json',
			data: { do: 'create_menu', name: name },
			success: function (res) {
				if (res.status === 'success' && res.menu_id) {
					notify('success', res.message || 'Đã tạo menu');
					window.location.href = 'index.php?p=menu&a=man&menu=' + res.menu_id;
				} else {
					notify('error', res.message || 'Không tạo được menu');
				}
			},
			error: function () {
				notify('error', 'Không thể kết nối đến máy chủ.');
			}
		});
	});

	/* ===== Cài đặt item ===== */
	menuEditor.on('click', '.item-toggle', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $btn = $(this).closest('.item-toggle');
		var $item = $btn.closest('li.dd-item').first();
		var $panel = $item.children('.item-settings').first();
		if (!$panel.length) {
			return;
		}
		if ($panel.hasClass('is-open')) {
			$panel.removeClass('is-open');
		} else {
			openSettings($panel);
		}
	});

	menuEditor.on('click', '.item-cancel', function () {
		var $item = $(this).closest('li.dd-item');
		var data = $item.data();
		Object.keys(MENU_FIELDS).forEach(function (key) {
			var cfg = MENU_FIELDS[key];
			var val = data[key] !== undefined ? data[key] : cfg.default;
			$item.find(cfg.selector).val(val);
		});
		if (data.image) {
			$item.find('.item-image-preview').attr('src', '../media/images/' + data.image);
		} else {
			$item.find('.item-image-preview').attr('src', 'img/no-image.png');
		}
		$item.children('.item-settings').removeClass('is-open');
	});

	menuEditor.on('click', '.item-remove', function () {
		$(this).closest('li.dd-item').remove();
		refreshUI();
	});

	menuEditor.on('input change', '.item-input', function () {
		var $input = $(this);
		var $item = $input.closest('li.dd-item');
		var name = $input.data('name');
		var val = $input.val();
		if (name) {
			$item.data(name, val);
		}
		if (name === 'label') {
			$item.find('.item-title').first().text(val || '(Không có tiêu đề)');
		}
		if ($input.hasClass('item-image-input')) {
			$item.data('image', val);
			if (val) {
				$item.find('.item-image-preview').attr('src', '../media/images/' + val);
			}
		}
	});

	/* ===== Expand / collapse ===== */
	menuEditor.on('mousedown touchstart', '.dd-expand, .dd-collapse', function (e) {
		e.preventDefault();
		e.stopPropagation();
	});

	menuEditor.on('click', '.dd-expand', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $item = $(this).closest('li.dd-item').first();
		menuEditor.nestable('expandItem', $item);
		refreshExpandCollapseButtons();
	});

	menuEditor.on('click', '.dd-collapse', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var $item = $(this).closest('li.dd-item').first();
		menuEditor.nestable('collapseItem', $item);
		refreshExpandCollapseButtons();
	});

	/* ===== Xóa hàng loạt / chọn tất cả ===== */
	$('#delete-selected').on('click', function () {
		var $items = menuEditor.find('.item-select:checked').closest('li.dd-item');
		if (!$items.length) {
			notify('warning', 'Chọn ít nhất một mục.');
			return;
		}
		confirmAsk({
			title: 'Xóa mục đã chọn?',
			message: 'Xóa ' + $items.length + ' mục đã chọn?',
			danger: true,
			confirmText: 'Xóa'
		}).then(function (ok) {
			if (!ok) {
				return;
			}
			$items.remove();
			refreshUI();
		});
	});

	$('#select-all-items').on('click', function (e) {
		e.preventDefault();
		var $cbs = menuEditor.find('.item-select');
		var allOn = $cbs.length > 0 && $cbs.filter(":checked").length === $cbs.length;
		$cbs.prop('checked', !allOn);
	});

	/* ===== Xóa menu ===== */
	$('#delete-menu').on('click', function (e) {
		e.preventDefault();
		var menuId = $('#menu-selector').val();
		if (!menuId) {
			return;
		}
		confirmAsk({
			title: 'Xóa menu?',
			message: 'Xóa menu này và toàn bộ mục bên trong?',
			danger: true,
			confirmText: 'Xóa'
		}).then(function (ok) {
			if (!ok) {
				return;
			}
			$.ajax({
				type: 'POST',
				url: 'sources/ajax.php',
				dataType: 'json',
				data: { do: 'delete_menu', menu_id: menuId },
				success: function (res) {
					if (res.status === 'success') {
						notify('success', res.message || 'Đã xóa menu.');
						window.location.href = 'index.php?p=menu&a=man';
					} else {
						notify('error', res.message || 'Lỗi xóa menu.');
					}
				},
				error: function () {
					notify('error', 'Không thể kết nối đến máy chủ.');
				}
			});
		});
	});

	/* ===== Lưu menu ===== */
	$('#save-menu-button').on('click', function () {
		var $btn = $(this);
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
			type: 'POST',
			url: 'sources/ajax.php',
			dataType: 'json',
			data: {
				do: 'save_menu',
				json_data: JSON.stringify(payload)
			},
			beforeSend: function () {
				$btn.prop('disabled', true).text('Đang lưu...');
			},
			success: function (res) {
				if (res.status === 'success') {
					notify('success', res.message || 'Đã lưu.');
				} else {
					notify('error', res.message || 'Lưu thất bại.');
				}
			},
			error: function (xhr) {
				var msg = 'Lỗi máy chủ. Không thể lưu menu.';
				try {
					var j = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
					if (j && j.message) {
						msg = j.message;
					}
				} catch (err) { /* ignore */ }
				if (window.console && xhr && xhr.responseText) {
					console.error(xhr.responseText);
				}
				notify('error', msg);
			},
			complete: function () {
				$btn.prop('disabled', false).text('Lưu Menu');
			}
		});
	});

	/* ===== Serialize cây Nestable -> JSON ===== */
	function serializeMenu($list) {
		var out = [];
		$list.children('li.dd-item').each(function () {
			var $item = $(this);
			var data = {
				id: $item.data('id'),
				type: $item.data('type'),
				object_type: $item.data('object_type'),
				object_id: $item.data('object_id'),
				lang: $item.data('lang')
			};

			Object.keys(MENU_FIELDS).forEach(function (field) {
				var cfg = MENU_FIELDS[field];
				var value;
				if (field === 'image') {
					value = $item.find(cfg.selector).val();
				} else {
					value = $item.data(field);
				}
				data[field] = (value !== undefined && value !== null) ? value : cfg.default;
			});

			var $sub = $item.children('ol.dd-list');
			if ($sub.length) {
				data.children = serializeMenu($sub);
			}
			out.push(data);
		});
		return out;
	}

	/* ===== Lên / xuống 1 cấp ===== */
	function moveUpOne($item) {
		var $parentItem = $item.parent().closest('li.dd-item');
		if (!$parentItem.length) {
			return;
		}
		$item.insertAfter($parentItem);
		var $oldList = $parentItem.children('ol.dd-list');
		if ($oldList.length && !$oldList.children('li.dd-item').length) {
			$oldList.remove();
		}
	}

	function moveDownOne($item) {
		var $prev = $item.prev('li.dd-item');
		if (!$prev.length) {
			return;
		}
		var $sub = $prev.children('ol.dd-list');
		if (!$sub.length) {
			$sub = $('<ol class="dd-list"></ol>');
			$prev.append($sub);
		}
		$sub.append($item);
	}

	menuEditor.on('click', '.move-up', function () {
		moveUpOne($(this).closest('li.dd-item'));
		refreshUI();
	});

	menuEditor.on('click', '.move-down', function () {
		moveDownOne($(this).closest('li.dd-item'));
		refreshUI();
	});

	menuEditor.on('change', function () {
		refreshUI();
	});

	/* ===== Refresh UI ===== */
	function refreshExpandCollapseButtons() {
		menuEditor.find('li.dd-item').each(function () {
			var $item = $(this);
			var $expand = $item.children('.dd-item-bar').find('.dd-expand').first();
			var $collapse = $item.children('.dd-item-bar').find('.dd-collapse').first();
			var hasChildren = $item.children('ol.dd-list').children('li.dd-item').length > 0;

			if (!hasChildren) {
				$expand.addClass('hidden');
				$collapse.addClass('hidden');
				$item.removeClass('dd-collapsed');
				return;
			}

			if ($item.hasClass('dd-collapsed')) {
				$expand.removeClass('hidden');
				$collapse.addClass('hidden');
			} else {
				$expand.addClass('hidden');
				$collapse.removeClass('hidden');
			}
		});
	}

	function refreshUI() {
		var hasItems = menuEditor.find('li.dd-item').length > 0;
		$('#menu-empty').toggle(!hasItems);
		refreshExpandCollapseButtons();
	}

	/* ===== Load menu từ PHP ===== */
	function loadItemsTree(items, $list) {
		if (!items || !items.length) {
			return;
		}
		for (var i = 0; i < items.length; i++) {
			var item = items[i];
			var $tpl = createMenuItem(item);
			$list.append($tpl);
			if (item.children && item.children.length) {
				var $sub = $('<ol class="dd-list"></ol>');
				$tpl.append($sub);
				loadItemsTree(item.children, $sub);
			}
		}
	}

	try {
		var initial = window.MENU_INITIAL_ITEMS || [];
		loadItemsTree(initial, menuEditor.find('.dd-list').first());
		refreshUI();
	} catch (err) {
		if (window.console) {
			console.error('Menu load error:', err);
		}
		notify('error', 'Không tải được cấu trúc menu.');
	}
})(jQuery);
