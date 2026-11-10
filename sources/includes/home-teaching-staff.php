<?php
    $block_teaching_staff = $d->getContent(668);
    $content_teaching_staff = $d->getContents(668);

    // Lấy giáo viên động từ LMS Profiles
    $dynamic_teachers = $d->safeFetch(
        "SELECT p.*, t.full_name, t.teacher_code 
         FROM #_lms_teacher_profiles p 
         JOIN #_lms_teachers t ON t.user_id = p.teacher_id 
         WHERE p.show_on_website = 1 
         ORDER BY COALESCE(p.so_thu_tu, 9999) ASC, t.full_name ASC"
    );
?>

<div class="block block-teaching-staff">
	<div class="container-fluid">
		<div class="heading-content text-center mb-4">
			<?= $block_teaching_staff['noi_dung'] ?? '<h2 class="title">ĐỘI NGŨ GIÁO VIÊN</h2>' ?>
		</div>
		<div class="wrapper-slide">
            <div class="swiper teaching-staff-slide">
                <div class="swiper-wrapper">
                    <?php if (!empty($dynamic_teachers)): ?>
                        <?php foreach ($dynamic_teachers as $dt): 
                            $dtImg = !empty($dt['avatar']) ? getImageUrl($dt['avatar']) : URLPATH . 'media/images/demo/img-teaching-staff-1.png';
                            $dtLink = URLPATH . 'giao-vien/' . $dt['slug'] . '.html';
                            $dtRole = !empty($dt['title_badge']) ? $dt['title_badge'] : 'Giáo viên Master Chuyên Anh';
                        ?>
                            <div class="swiper-slide">
                                <a href="<?= $dtLink ?>" class="text-decoration-none">
                                    <div class="box-teaching-staff">
                                    	<div class="box-thumbnail">
                                    		<div class="inner-thumbnail ratio ratio-1x1">
                                    			<img src="<?= $dtImg ?>" alt="<?= htmlspecialchars($dt['full_name']) ?>" class="image-cover" onerror="this.src='<?= URLPATH ?>media/images/demo/img-teaching-staff-1.png'">
                                    		</div>
                                    	</div>
                                    	<div class="box-content">
                                    		<h3 class="title"><?= htmlspecialchars($dt['full_name']) ?></h3>
                                    		<p class="position"><?= htmlspecialchars($dtRole) ?></p>
                                    	</div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback mẫu khi chưa có giáo viên nào bật hiển thị -->
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="<?= URLPATH ?>media/images/demo/img-teaching-staff-1.png" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="swiper-pagination swiper-pagination-simple pagination-teaching-staff"></div>
        </div>
	</div>
	<img src="<?= URLPATH ?>/media/images/demo/img-teaching-staff-1.png" alt="" class="img-cover">
	<div class="bg-cover" style="background-image: url('<?= getImageUrl($block_teaching_staff['background']) ?>'); background-position: center bottom; background-size: contain;"></div>
	<svg class="svg-clip-path">
	  	<clipPath id="clip-path-teaching-staff" clipPathUnits="objectBoundingBox"><path d="M0.033,0.29 A0.07,0.387,0,0,1,0.101,0 h0.798 a0.07,0.387,0,0,1,0.068,0.29 l0.033,0.71 H0"></path></clipPath>
	</svg>
</div>