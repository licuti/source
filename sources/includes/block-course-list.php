<?php
    $block_course_list = $d->getContent(756);
    $content_course_list = $d->getContents(756);
?>

<?php if ($block_course_list): ?>
	<div class="box-course-list">
		<div class="box-title">
			<div class="icon">
				<img src="<?= getImageUrl($block_course_list['hinh_anh']) ?>" alt="<?= $block_course_list['ten'] ?>" class="image-contain">
			</div>
			<div class="title">CÁC KHÓA HỌC 1 KÈM 1 TẠI MASTER CHUYÊN ANH</div>
		</div>
		<div class="box-list">
			<?php foreach ($content_course_list as $key => $value): ?>
				<div class="list-item">
					<a href="<?= $value['link'] ?>">
						<div class="icon">
							<img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
						</div>
						<?= $value['ten'] ?>
						<i class="fa-solid fa-chevron-right"></i>
					</a>
				</div>
			<?php endforeach ?>
		</div>
	</div>
<?php endif ?>