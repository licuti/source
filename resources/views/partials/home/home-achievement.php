<?php
    $block_achievement = $d->getContent(456);
    $content_achievement = $d->getContents(456);
?>


<?php if ($block_achievement): ?>
	<div class="block block-achievement">
		<div class="container-fluid">
			<div class="row g-4 align-items-center">
				<div class="col-lg-6">
					<div class="box-video ratio ratio-16x9">
						<a href="<?= $block_achievement['link'] ?>" data-fancybox="video">
							<getImageUrl src="<?= getImageUrl($block_achievement['hinh_anh']) ?>" alt="<?= $block_achievement['ten'] ?>" class="image-cover">
						</a>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="px-md-4">
						<div class="box-title-main">
							<?= $block_achievement['noi_dung'] ?>	
						</div>
						<div class="group-achievement mt-4">
							<?php foreach ($content_achievement as $key => $value): ?>
								<div class="icon-box">
									<div class="icon">
										<getImageUrl src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
									</div>
									<div class="content">
										<?= $value['noi_dung'] ?>
									</div>
								</div>
							<?php endforeach ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>