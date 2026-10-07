<?php
    $block_collection = $d->getContent(730);
    $content_collection = $d->getContents(730);
?>
<?php if ($block_collection): ?>
	<div class="block">
		<div class="container-fluid">
			<div class="heading-content text-center mb-3 mb-md-4">
				<?= $block_collection['noi_dung'] ?>
			</div>
			<div class="row g-3">
				<?php foreach ($content_collection as $key => $value): ?>
					<div class="col-lg-4">
						<div class="box-collection">
							<div class="box-thumbnail">
								<div class="inner-thumbnail ratio">
									<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">

								</div>
							</div>
							<div class="box-content">
								<h3 class="title"><?= $value['ten'] ?></h3>
								<a href="<?= $value['link'] ?>" class="btn-custom btn-white btn-outline">Xem thêm</a>
							</div>
						</div>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	</div>
<?php endif ?>