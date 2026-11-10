<?php
    $block_highlights = $d->getContent(1434);
    $content_highlights = $d->getContents(1434);
    $block_program = $d->getContent(1462);
    $content_program = $d->getContents(1462);
?>


<div class="block">
	<div class="container-fluid">
		<div class="row g-3">
			<div class="col-lg-12">
				<div class="heading-content">
					<h2 class=""><span class="text-gradient">KHÓA HỌC</span></h2>
					<p>Nội dung giới thiệu sơ lược về các đầu sách của Master Chuyên Anh</p>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="wrapper-slide">
		            <div class="swiper course-slide">
		                <div class="swiper-wrapper">
		                	<?php foreach ($content_program as $key => $value): ?>
		                        <div class="swiper-slide">
		                            <div class="box-course">
		                            	<div class="box-thumbnail">
		                            		<div class="inner-thumbnail ratio ratio-16x9">
		                                		<img src="<?= getImageUrl($value['background']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
		                            		</div>
		                            	</div>
		                            	<div class="box-content">
		                            		<h3 class="title"><?= $value['ten'] ?></h3>
		                            	</div>
		                            </div>
		                        </div>
		                	<?php endforeach ?>
		                </div>
		            </div>
		            <div class="swiper-pagination swiper-pagination-simple pagination-course"></div>
		        </div>
			</div>
			<div class="col-lg-3 d-flex flex-column gap-4">
				<?php include _source."/includes/block-course-list.php"; ?>
				<?php include _source."/includes/block-roadmap-consultation.php"; ?>
			</div>
		</div>
	</div>
	<div class="bg-cover" style="background-image: url('<?= URLPATH ?>/media/images/demo/about.png'); background-size: contain; background-position: right bottom;"></div>
</div>

<div class="block pt-0">
	<div class="container-fluid">
		<div class="row g-4 mt-4">
			<div class="col-lg-9 d-flex flex-column gap-4">
				<div class="wrapper-curriculum-highlights">
					<h3 class="mb-3 mb-md-4 fs-24 fw-semibold text-center"><?= $block_highlights['ten'] ?></h3>
					<div class="row">
						<?php foreach ($content_highlights as $key => $value): ?>
							<div class="col-lg-4">
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
			<div class="col-lg-3">
				<?php include _source."/includes/block-featured-post.php"; ?>
			</div>
		</div>
	</div>
</div>
