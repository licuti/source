<?php
    $block_curriculum = $d->getContent(456);
    $block_curriculum_1 = $d->getContent(528);
    $content_curriculum_1 = $d->getContents(528);
    $block_curriculum_2 = $d->getContent(576);
    $content_curriculum_2 = $d->getContents(576);
    $block_curriculum_3 = $d->getContent(667);
    $content_curriculum_3 = $d->getContents(667);
?>

<?php if ($block_curriculum): ?>
	<div class="block">
		<div class="container-fluid">
			<div class="heading-content text-center mb-4">
				<?= $block_curriculum['noi_dung'] ?>
			</div>
			<div class="row g-4">
				<div class="col-12">
					<div class="box-curriculum">
						<div class="row">
							<div class="col-lg-8">
								<div class="box-header">
									<div class="icon">
										<img src="<?= getImageUrl($block_curriculum_1['hinh_anh']) ?>" alt="<?= $block_curriculum_1['ten'] ?>" class="image-contain">
									</div>
									<div class="title"><?= $block_curriculum_1['ten'] ?></div>
								</div>
								<div class="excerpt">
									<?= $block_curriculum_1['noi_dung'] ?>
								</div>
								<div class="d-flex gap-3 mt-4">
									<?php foreach ($content_curriculum_1 as $key => $value): ?>
										<a href="<?= $value['link'] ?>" class="btn-custom btn-x btn-outline bg-white w-100"><?= $value['ten'] ?></a>
									<?php endforeach ?>
								</div>
							</div>
						</div>
						<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_curriculum_1['background']) ?>');"></div>
					</div>
				</div>
				<div class="col-lg-9">
					<div class="box-curriculum">
						<div class="row">
							<div class="col-lg-8">
								<div class="box-header">
									<div class="icon">
										<img src="<?= getImageUrl($block_curriculum_2['hinh_anh']) ?>" alt="<?= $block_curriculum_2['ten'] ?>" class="image-contain">
									</div>
									<div class="title" style="background: linear-gradient(90deg, #1FB4FF 0%, #0234FA 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?= $block_curriculum_2['ten'] ?></div>
								</div>
								<div class="excerpt">
									<?= $block_curriculum_2['noi_dung'] ?>
								</div>
								<div class="row g-3 mt-2">
									<?php foreach ($content_curriculum_2 as $key => $value): ?>
										<div class="col-lg-3">
											<a href="<?= $value['link'] ?>" class="box-curriculum-certificate">
												<div class="icon">
													<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
												</div>
												<div class="title"><?= $value['ten'] ?></div>
											</a>
										</div>
									<?php endforeach ?>
								</div>
							</div>
						</div>
						<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_curriculum_2['background']) ?>');"></div>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="box-curriculum">
						<div class="row">
							<div class="col-lg-8">
								<div class="box-header">
									<div class="icon">
										<img src="<?= getImageUrl($block_curriculum_3['hinh_anh']) ?>" alt="<?= $block_curriculum_3['ten'] ?>" class="image-contain">
									</div>
									<div class="title" style="background: linear-gradient(90deg, #FFC31F 0%, #FA5B02 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?= $block_curriculum_3['ten'] ?></div>
								</div>
								<div class="excerpt">
									<?= $block_curriculum_3['noi_dung'] ?>
								</div>
							</div>
						</div>
						<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_curriculum_3['background']) ?>');"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_curriculum['background']) ?>');"></div>
	</div>
<?php endif ?>