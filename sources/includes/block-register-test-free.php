<?php
    $block_register = $d->getContent(772);
?>
<?php if ($block_register): ?>
	<div class="box-register d-flex flex-column flex-md-row justify-content-between align-items-center">
		<div class="box-left d-flex align-items-center gap-3">
			<div class="icon">
				<img src="<?= getImageUrl($block_register['hinh_anh']) ?>" alt="<?= $block_register['ten'] ?>" class="image-contain">
			</div>
			<div class="content">
				<h3 class="title"><?= $block_register['ten'] ?></h3>
				<?= $block_register['noi_dung'] ?>
			</div>
		</div>
		<div class="box-right flex-shrink-0">
			<button class="btn-custom btn-x btn-solid" data-bs-toggle="modal" data-bs-target="#modal-register-contact">ĐĂNG KÝ NGAY</button>
		</div>
	</div>
<?php endif ?>