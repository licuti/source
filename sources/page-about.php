<?php
    $block_introduce = $d->getContent(750);
    $content_introduce = $d->getContents(750);
?>
<?php if ($block_introduce): ?>
	<div class="block">
		<div class="container-fluid">
			<div class="row g-4">
				<div class="col-lg-8 d-flex flex-column gap-4">
					<div class="box-content-about">
						<div class="heading-content"><?= $block_introduce['noi_dung'] ?></div>
						<img src="<?= getImageUrl($block_introduce['hinh_anh']) ?>" alt="<?= $block_introduce['ten'] ?>" class="">
					</div>
					<div class="wrapper-about-core-value mt-5">
						<div class="row">
							<?php foreach ($content_introduce as $key => $value): ?>
								<div class="col-6 col-lg-3">
									<div class="box-icon-about">
										<div class="icon">
											<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
										</div>
										<div class="content">
											<h3 class="title"><?= $value['ten'] ?></h3>
											<?= $value['noi_dung'] ?>
										</div>
									</div>
								</div>
							<?php endforeach ?>
						</div>
					</div>
					<?php include _source."/includes/block-register-test-free.php"; ?>
				</div>
				<div class="col-lg-4 d-flex flex-column gap-4">
					<?php include _source."/includes/block-course-list.php"; ?>
					<?php include _source."/includes/block-roadmap-consultation.php"; ?>
					<?php include _source."/includes/block-featured-post.php"; ?>
				</div>
			</div>
		</div>
		<img src="<?= URLPATH ?>media/images/demo/img-about-cover-1.png" alt="" class="position-absolute img-about-cover-1">
	</div>
<?php endif ?>