<?php
    $block_core_value = $d->getContent(668);
?>
<?php if ($block_about): ?>

	<div class="block">
		<div class="container-fluid">
			<div class="row justify-content-center mb-3 mb-md-4">
				<div class="col-lg-10">
					<div class="heading-content">
						<?= $block_core_value['noi_dung'] ?>
					</div>
				</div>
			</div>
			<img src="<?= getImageUrl($block_core_value['hinh_anh']) ?>" alt="<?= $block_core_value['ten'] ?>" class="image-cover">
		</div>
	</div>
<?php endif ?>