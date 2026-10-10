<?php
    $block_teaching_staff = $d->getContent(668);
    $content_teaching_staff = $d->getContents(668);
?>

<div class="block block-teaching-staff">
	<div class="container-fluid">
		<div class="heading-content text-center mb-4">
			<?= $block_teaching_staff['noi_dung'] ?>
		</div>
		<div class="wrapper-slide">
            <div class="swiper teaching-staff-slide">
                <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="https://media.dolenglish.vn/PUBLIC/MEDIA/co-nguyen-phan-thuy-linh-giao-vien-dol-english-online.jpg" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="https://media.dolenglish.vn/PUBLIC/MEDIA/co-nguyen-phan-thuy-linh-giao-vien-dol-english-online.jpg" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="https://media.dolenglish.vn/PUBLIC/MEDIA/co-nguyen-phan-thuy-linh-giao-vien-dol-english-online.jpg" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="https://media.dolenglish.vn/PUBLIC/MEDIA/co-nguyen-phan-thuy-linh-giao-vien-dol-english-online.jpg" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="box-teaching-staff">
                            	<div class="box-thumbnail">
                            		<div class="inner-thumbnail ratio ratio-1x1">
                            			<img src="https://media.dolenglish.vn/PUBLIC/MEDIA/co-nguyen-phan-thuy-linh-giao-vien-dol-english-online.jpg" class="image-cover">
                            		</div>
                            	</div>
                            	<div class="box-content">
                            		<h3 class="title">Ms. Duyên</h3>
                            		<p class="position">Flyers, Movers</p>
                            	</div>
                            </div>
                        </div>
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