<?php
    $block_advantage = $d->getContent(1438);
    $content_advantage = $d->getContents(1438);
?>

<div class="block pb-4">
	<div class="container-fluid">
		<div class="row g-4">
			<div class="col-lg-8">
				<?= $row['noi_dung'] ?>
			</div>
			<div class="col-lg-4 d-flex flex-column gap-4">
				<?php include _source."/includes/block-course-list.php"; ?>
				<?php include _source."/includes/block-roadmap-consultation.php"; ?>
			</div>
		</div>
	</div>
</div>


<div class="block">
	<div class="container-fluid">
		<div class="row justify-content-center">
			<div class="col-lg-8">
				<h2 class="heading-title text-center mb-3 mb-md-4"><?= $block_advantage['ten'] ?></h2>
			</div>
		</div>
		<div class="row g-4">
			<?php foreach ($content_advantage as $key => $value): ?>
				<div class="col-6 col-lg-3">
					<div class="box-advantage">
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
</div>
<?php
	$block_program = $d->getContent(1462);
    $content_highlights = $d->getContents(1462);
?>

<div class="block py-0">
	<div class="container-fluid">
		<h2 class="heading-title mb-3 mb-md-4"><span class="text-gradient">CHƯƠNG TRÌNH</span></h2>
		<div class="wrapper-slide">
            <div class="swiper program-slide">
                <div class="swiper-wrapper">
                    <?php foreach ($content_highlights as $key => $value): ?>
                        <div class="swiper-slide">
                            <div class="box-program">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio">
                                		<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title"><?= $value['ten'] ?></h3>
                            		<?= $value['noi_dung'] ?>
                            	</div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="swiper-pagination swiper-pagination-simple program-pagination"></div>
        </div>
	</div>
</div>


<div class="block">
	<div class="container-fluid">
		<div class="row">
			<div class="col-lg-7">
				<?php include _source."/includes/block-register-test-free.php"; ?>
			</div>
			<div class="col-lg-5">
				<?php include _source."/includes/block-roadmap-consultation.php"; ?>
			</div>
		</div>
	</div>
</div>