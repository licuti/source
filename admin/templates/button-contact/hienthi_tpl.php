<?php
/**
 * Nút liên hệ nổi (button contact).
 */
$canEdit = $d->checkPermission_edit($id_module) == 1;
$MODULE  = 'button-contact';
$items   = $items ?? [];
$saveUrl = admin_url(['p' => $MODULE, 'a' => 'save']);

$addBtn = '';
if ($canEdit) {
    $addBtn = '<button type="button" class="btn btn-sm btn-outline-primary" id="bc-add-row">'
        . '<i class="fa-solid fa-plus me-1"></i>Thêm nút</button>';
}
?>

<?php component('page_header', [
    'title'      => 'Nút liên hệ',
    'breadcrumb' => [
        ['label' => 'Trang chủ', 'url' => urladmin, 'icon' => 'fa-solid fa-gauge-high'],
        ['label' => 'Cấu hình website'],
        ['label' => 'Nút liên hệ'],
    ],
]); ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.css">
<style>
.bc-thumb {
	width: 72px;
	height: 72px;
	object-fit: contain;
	background: var(--bs-secondary-bg, #f8f9fa);
	border: 1px solid var(--bs-border-color, #dee2e6);
	border-radius: 0.375rem;
}
.bc-row .clr-field {
	display: block;
	width: 100%;
}
.bc-row .clr-field input {
	width: 100%;
}
.bc-row .drag-handle {
	cursor: grab;
	color: var(--bs-secondary-color);
	padding: 0.25rem;
	display: inline-flex;
}
.bc-row .drag-handle:active {
	cursor: grabbing;
}
</style>

<div class="app-content">
	<div class="container-fluid">

		<form method="post" action="<?= e($saveUrl) ?>" id="form-button-contact">

			<?php component('card_open', [
				'title' => 'Danh sách nút',
				'icon'  => 'fa-solid fa-hand-pointer',
				'body'  => 'none',
				'tools' => $addBtn,
			]); ?>

				<div class="table-responsive p-3">
					<table class="table table-hover table-bordered align-middle table-admin mb-0" id="bc-table">
						<thead>
							<tr>
								<th class="text-center col-shrink" style="width:44px" title="Kéo sắp xếp"></th>
								<th style="width:120px">Icon</th>
								<th>Tên</th>
								<th>Link</th>
								<th style="width:120px">Target</th>
								<th style="width:120px">Nền</th>
								<th style="width:120px">Nền (hover)</th>
								<th style="width:120px">Màu chữ</th>
								<th class="text-center col-shrink" style="width:70px">Xóa</th>
							</tr>
						</thead>
						<tbody id="bc-tbody"<?= $canEdit ? ' data-bc-sortable="1"' : '' ?>>
							<?php if (empty($items)): ?>
								<tr class="bc-empty-hint">
									<td colspan="9" class="text-center text-secondary py-4">
										Chưa có nút. Bấm <strong>Thêm nút</strong> để tạo.
									</td>
								</tr>
							<?php else: ?>
								<?php foreach ($items as $row):
									$rid    = (int) $row['id'];
									$img    = (string) ($row['image'] ?? '');
									$imgUrl = $img !== '' ? img_url($img) : 'img/no-image.png';
									$fid    = 'bc_img_' . $rid;
									?>
									<tr class="bc-row" data-id="<?= $rid ?>">
										<td class="text-center col-shrink">
											<?php if ($canEdit): ?>
												<span class="drag-handle bc-drag" title="Kéo sắp xếp">
													<i class="fa-solid fa-grip-vertical"></i>
												</span>
											<?php endif; ?>
											<input type="hidden" name="id[]" value="<?= $rid ?>">
										</td>
										<td>
											<div class="text-center">
												<img src="<?= e($imgUrl) ?>" alt="" class="bc-thumb mb-1 d-block mx-auto" id="review_<?= e($fid) ?>">
												<input type="hidden" name="image[]" id="<?= e($fid) ?>" value="<?= e($img) ?>" data-preview="image">
												<?php if ($canEdit): ?>
													<div class="d-flex flex-column gap-1">
														<a <?= function_exists('fm_button_attrs') ? fm_button_attrs([
															'field_id' => $fid,
															'type'     => 'image',
															'multiple' => false,
															'class'    => 'btn btn-sm btn-outline-primary iframe-btn js-fm-open py-0'
														]) : 'href="ckfinder/ckfinder.html?type=Images" class="btn btn-sm btn-outline-primary iframe-btn js-fm-open py-0" data-fm-driver="ckfinder" data-fm-field="' . e($fid) . '" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="0"' ?>>Chọn</a>
														<button type="button" class="btn btn-sm btn-outline-secondary bc-clear-img py-0"
																data-fid="<?= e($fid) ?>">Xóa ảnh</button>
													</div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<input type="text" name="name[]" class="form-control form-control-sm"
												   value="<?= e($row['name'] ?? '') ?>" placeholder="Tên hiển thị"
												   <?= $canEdit ? '' : 'readonly' ?>>
										</td>
										<td>
											<input type="text" name="link[]" class="form-control form-control-sm"
												   value="<?= e($row['link'] ?? '') ?>"
												   placeholder="https://… | tel:… | mailto:…"
												   <?= $canEdit ? '' : 'readonly' ?>>
										</td>
										<td>
											<select name="target[]" class="form-select form-select-sm" <?= $canEdit ? '' : 'disabled' ?>>
												<option value="_self" <?= ($row['target'] ?? '') === '_self' ? 'selected' : '' ?>>Cùng tab</option>
												<option value="_blank" <?= ($row['target'] ?? '') === '_blank' ? 'selected' : '' ?>>Tab mới</option>
											</select>
										</td>
										<td>
											<input type="text" name="color_background[]" class="form-control form-control-sm"
												   value="<?= e($row['color_background'] ?? '') ?>" placeholder="#000000"
												   data-coloris <?= $canEdit ? '' : 'readonly' ?>>
										</td>
										<td>
											<input type="text" name="color_background_alpha[]" class="form-control form-control-sm"
												   value="<?= e($row['color_background_alpha'] ?? '') ?>" placeholder="rgba(...)"
												   data-coloris <?= $canEdit ? '' : 'readonly' ?>>
										</td>
										<td>
											<input type="text" name="color_text[]" class="form-control form-control-sm"
												   value="<?= e($row['color_text'] ?? '') ?>" placeholder="#ffffff"
												   data-coloris <?= $canEdit ? '' : 'readonly' ?>>
										</td>
										<td class="text-center col-shrink">
											<?php if ($canEdit): ?>
												<button type="button" class="btn btn-sm btn-outline-danger bc-remove-row"
														data-id="<?= $rid ?>" title="Xóa">
													<i class="fa-solid fa-trash"></i>
												</button>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<?php if ($canEdit): ?>
					<div class="px-3 pb-3 d-flex flex-wrap gap-2 border-top pt-3">
						<button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary">
							<i class="fa-solid fa-floppy-disk me-1"></i>Lưu tất cả
						</button>
						<button type="button" class="btn btn-sm btn-outline-primary" id="bc-add-row-2">
							<i class="fa-solid fa-plus me-1"></i>Thêm nút
						</button>
						<span class="small text-secondary align-self-center">
							Kéo <i class="fa-solid fa-grip-vertical"></i> để đổi thứ tự, rồi bấm Lưu.
						</span>
					</div>
				<?php endif; ?>

			<?php component('card_close', ['body' => 'none']); ?>
		</form>

		<?php component('card_open', [
			'title' => 'Gợi ý link',
			'icon'  => 'fa-solid fa-lightbulb',
		]); ?>
			<div class="row g-2">
				<div class="col-md-4">
					<?php component('form/input', [
						'label' => 'Gọi điện',
						'name'  => '_hint_tel',
						'id'    => 'hint_tel',
						'value' => 'tel:0123456789',
						'attr'  => ['readonly' => true],
					]); ?>
				</div>
				<div class="col-md-4">
					<?php component('form/input', [
						'label' => 'Zalo chat',
						'name'  => '_hint_zalo',
						'id'    => 'hint_zalo',
						'value' => 'https://zalo.me/0123456789',
						'attr'  => ['readonly' => true],
					]); ?>
				</div>
				<div class="col-md-4">
					<?php component('form/input', [
						'label' => 'Email',
						'name'  => '_hint_mail',
						'id'    => 'hint_mail',
						'value' => 'mailto:info@example.com',
						'attr'  => ['readonly' => true],
					]); ?>
				</div>
			</div>
		<?php component('card_close'); ?>

	</div>
</div>

<template id="bc-row-template">
	<tr class="bc-row" data-id="0">
		<td class="text-center col-shrink">
			<span class="drag-handle bc-drag" title="Kéo sắp xếp">
				<i class="fa-solid fa-grip-vertical"></i>
			</span>
			<input type="hidden" name="id[]" value="">
		</td>
		<td>
			<div class="text-center">
				<img src="img/no-image.png" alt="" class="bc-thumb mb-1 d-block mx-auto" id="review___FID__">
				<input type="hidden" name="image[]" id="__FID__" value="" data-preview="image">
				<div class="d-flex flex-column gap-1">
					<a href="ckfinder/ckfinder.html?type=Images"
					   class="btn btn-sm btn-outline-primary iframe-btn js-fm-open py-0" data-fm-driver="ckfinder" data-fm-field="__FID__" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="0">Chọn</a>
					<button type="button" class="btn btn-sm btn-outline-secondary bc-clear-img py-0" data-fid="__FID__">Xóa ảnh</button>
				</div>
			</div>
		</td>
		<td><input type="text" name="name[]" class="form-control form-control-sm" placeholder="Tên hiển thị"></td>
		<td><input type="text" name="link[]" class="form-control form-control-sm" placeholder="https://… | tel:… | mailto:…"></td>
		<td>
			<select name="target[]" class="form-select form-select-sm">
				<option value="_self">Cùng tab</option>
				<option value="_blank">Tab mới</option>
			</select>
		</td>
		<td><input type="text" name="color_background[]" class="form-control form-control-sm" placeholder="#000000" data-coloris></td>
		<td><input type="text" name="color_background_alpha[]" class="form-control form-control-sm" placeholder="rgba(...)" data-coloris></td>
		<td><input type="text" name="color_text[]" class="form-control form-control-sm" placeholder="#ffffff" data-coloris></td>
		<td class="text-center col-shrink">
			<button type="button" class="btn btn-sm btn-outline-danger bc-remove-row" data-id="0" title="Xóa">
				<i class="fa-solid fa-trash"></i>
			</button>
		</td>
	</tr>
</template>

<script src="https://cdn.jsdelivr.net/gh/mdbassit/Coloris@latest/dist/coloris.min.js"></script>
<script>
$(function () {
	var $tbody = $('#bc-tbody');
	var newIndex = 0;

	// Color picker
	if (typeof Coloris !== 'undefined') {
		Coloris({
			el: '[data-coloris]',
			formatToggle: true,
			clearButton: true,
			clearLabel: 'Xóa',
			closeButton: true,
			closeLabel: 'Đóng'
		});
	}

	// Kéo thả (Sortable load ở footer index.php)
	function initSort() {
		if (typeof Sortable === 'undefined') return;
		var el = document.getElementById('bc-tbody');
		if (!el || !el.getAttribute('data-bc-sortable') || el._bcSort) return;
		el._bcSort = Sortable.create(el, {
			handle: '.bc-drag',
			draggable: 'tr.bc-row',
			animation: 150
		});
	}
	initSort();
	setTimeout(initSort, 300);

	// Thêm dòng
	function addRow() {
		$tbody.find('.bc-empty-hint').remove();
		var fid = 'bc_img_new_' + (newIndex++);
		var html = $('#bc-row-template').html().split('__FID__').join(fid);
		$tbody.append(html);
		$('.iframe-btn').fancybox({ type: 'iframe', autoScale: false });
		if (typeof Coloris !== 'undefined') {
			Coloris({ el: '[data-coloris]', formatToggle: true, clearButton: true, clearLabel: 'Xóa', closeButton: true, closeLabel: 'Đóng' });
		}
	}
	$('#bc-add-row, #bc-add-row-2').on('click', addRow);

	// Xóa ảnh preview
	$(document).on('click', '.bc-clear-img', function () {
		var fid = $(this).data('fid');
		$('#' + fid).val('');
		$('#review_' + fid).attr('src', 'img/no-image.png');
	});

	// Xóa dòng — dùng AdminUI (SweetAlert2 + toast đã cấu hình sẵn)
	$(document).on('click', '.bc-remove-row', function () {
		var id = parseInt($(this).data('id'), 10) || 0;
		var $tr = $(this).closest('tr');

		function afterRemove() {
			$tr.remove();
			if (!$tbody.find('tr.bc-row').length) {
				$tbody.html('<tr class="bc-empty-hint"><td colspan="9" class="text-center text-secondary py-4">Chưa có nút. Bấm <strong>Thêm nút</strong> để tạo.</td></tr>');
			}
		}

		function notifyOk(msg) {
			if (window.AdminUI && AdminUI.notify) {
				AdminUI.notify.success(msg);
			}
		}

		function notifyErr(msg) {
			if (window.AdminUI && AdminUI.notify) {
				AdminUI.notify.error(msg);
			}
		}

		if (!id) {
			afterRemove();
			notifyOk('Đã gỡ dòng chưa lưu.');
			return;
		}

		function doDelete() {
			$.post('./sources/ajax.php', { do: 'remove_button_contact', id: id })
				.done(function () {
					afterRemove();
					notifyOk('Đã xóa nút liên hệ.');
				})
				.fail(function () {
					notifyErr('Xóa thất bại. Vui lòng thử lại.');
				});
		}

		if (window.AdminUI && AdminUI.confirm) {
			AdminUI.confirm.ask({
				title: 'Xóa nút?',
				message: 'Xóa vĩnh viễn nút liên hệ này?',
				icon: 'warning',
				danger: true,
				confirmText: 'Xóa',
				cancelText: 'Hủy'
			}).then(function (ok) {
				if (ok) {
					doDelete();
				}
			});
		} else if (confirm('Xác nhận xóa nút này?')) {
			doDelete();
		}
	});
});
</script>
