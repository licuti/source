<?php
	$featured_post = $d->o_fet("select * from #_tintuc where id_loai=276 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 1");
?>

<div class="box-featured-post">
	<div class="box-featured-header d-flex justify-content-between align-items-center mb-3">
		<h3 class="text-gradient mb-0">BÀI ĐĂNG  NỔI BẬT</h3>
		<a href="" class="view-all text-decoration-underline fs-14 fw-medium">Xem tất cả</a>
	</div>
	<div class="box-featured-list">
		<?php foreach ($featured_post as $key => $value): ?>
			<a <?= createAlias($value) ?> class="featured-item">
				<div class="thumbnail">
					<div class="ratio ratio-4x3">
						<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
					</div>
				</div>
				<div class="content">
					<h4 class="title"><?= $value['ten'] ?></h4>
					<div class="date mb-1"><?= date('d-m-Y', $value['ngay_dang']) ?></div>
					<div class="excerpt"><?= $value['mo_ta'] ?></div>
				</div>
			</a>
		<?php endforeach ?>
	</div>
</div>