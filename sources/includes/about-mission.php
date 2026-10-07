<?php
    $block_mission = $d->getContent(729);
?>
<?php if ($block_mission): ?>
	<div class="block pt-0">
		<div class="container-fluid"> 
			<div class="row align-items-center g-4 g-lg-5">
				<div class="col-lg-6">
					<div class="heading-content text-center">
						<?= $block_mission['noi_dung'] ?>	
						<?php if ($block_mission['link']): ?>
							<a href="<?= $block_mission['link'] ?>" class="btn-custom btn-x btn-outline mt-4"><?= $d->getTxt(12) ?></a>
						<?php endif ?>
						</div>
				</div>
				<div class="col-lg-6">
					<img src="<?= getImageUrl($block_mission['hinh_anh']) ?>" alt="<?= $block_mission['ten'] ?>" class="image-cover">
				</div>
			</div>
		</div>
	</div>
<?php endif ?>