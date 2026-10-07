<?php
    $block_about = $d->getContent(667);
?>
<?php if ($block_about): ?>
	<div class="block block-home-about">
		<div class="container-fluid"> 
			<div class="row align-items-center g-4 g-lg-5">
				<div class="col-lg-6">
					<div class="heading-content"><?= $block_about['noi_dung'] ?></div>
					<?php if ($block_about['link']): ?>
						<a href="<?= $block_about['link'] ?>" class="btn-custom btn-x btn-outline mt-4"><?= $d->getTxt(12) ?></a>
					<?php endif ?>
				</div>
				<div class="col-lg-6">
					<img src="<?= getImageUrl($block_about['hinh_anh']) ?>" alt="<?= $block_about['ten'] ?>" class="image-cover">
				</div>
			</div>
		</div>
	</div>
<?php endif ?>