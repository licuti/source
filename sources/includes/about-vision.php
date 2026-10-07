<?php
    $block_vision = $d->getContent(727);
?>
<?php if ($block_vision): ?>
	<div class="block">
		<div class="container-fluid"> 
			<div class="row flex-lg-row-reverse align-items-center g-4 g-lg-5">
				<div class="col-lg-6">
					<div class="heading-content text-center">
						<?= $block_vision['noi_dung'] ?>	
						<?php if ($block_vision['link']): ?>
							<a href="<?= $block_vision['link'] ?>" class="btn-custom btn-x btn-outline mt-4"><?= $d->getTxt(12) ?></a>
						<?php endif ?>
						</div>
				</div>
				<div class="col-lg-6">
					<img src="<?= getImageUrl($block_vision['hinh_anh']) ?>" alt="<?= $block_vision['ten'] ?>" class="image-cover">
				</div>
			</div>
		</div>
	</div>
<?php endif ?>